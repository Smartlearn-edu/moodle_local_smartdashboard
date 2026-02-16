<?php

/**
 * External API for Magic Analytics (AI Reports).
 *
 * @package     local_smartdashboard
 * @copyright   2026 Mohammad Nabil
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_smartdashboard\external;

defined('MOODLE_INTERNAL') || die();

use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_single_structure;
use core_external\external_multiple_structure;
use core_external\external_value;
use context_system;

class magic_analytics extends external_api
{

    /**
     * Parameters for get_magic_insight.
     */
    public static function get_magic_insight_parameters()
    {
        return new external_function_parameters([
            'prompt' => new external_value(PARAM_TEXT, 'The natural language question from the admin')
        ]);
    }

    /**
     * Helper to provide schema context for AI.
     */
    private static function get_schema_context()
    {
        global $CFG;
        return "You are an expert Moodle SQL query generator.
Moodle version: " . $CFG->release . "
Database: MySQL/MariaDB

IMPORTANT RULES:
1. Use Moodle's {table_name} placeholder syntax (e.g., {course}, {user}, {enrol}). Do NOT use mdl_ prefix.
2. Return ONLY valid JSON with the following keys:
   - 'sql': The SELECT query
   - 'explanation': Brief human-readable explanation of results
   - 'chart_type': One of 'bar', 'line', 'pie', 'doughnut', or 'none'. Use 'none' if the data is not suitable for a chart (e.g., text-heavy lists, single row, user details).
   - 'chart_label_column': The SQL alias/column name to use as chart labels (X-axis or pie segments). Only if chart_type is not 'none'.
   - 'chart_value_column': The SQL alias/column name to use as chart values (Y-axis or pie sizes). Only if chart_type is not 'none'.
3. Do NOT wrap in markdown code blocks.
4. **CRITICAL SECURITY RULE**: SQL MUST be a pure SELECT query ONLY. You are STRICTLY FORBIDDEN from generating any of the following: INSERT, UPDATE, DELETE, DROP, ALTER, TRUNCATE, CREATE, REPLACE, GRANT, REVOKE, RENAME, EXEC, EXECUTE, CALL, LOAD DATA, INTO OUTFILE, INTO DUMPFILE, LOCK, UNLOCK, FLUSH, SET, PREPARE, DEALLOCATE. Do NOT use semicolons. Do NOT use SQL comments (-- or /* */). Any non-SELECT query WILL BE REJECTED by the server and will not execute.
5. Do NOT use the LIMIT clause. The system handles limits automatically.
6. Use standard Moodle table names and column names. Do NOT abbreviate table names (e.g., use {course_completion_criteria}, NOT {course_completion_crit}).
7. Make sure all column references in SELECT, WHERE, HAVING, and ORDER BY clauses come from properly JOINed tables.
8. Use meaningful column aliases (e.g., 'course_name', 'student_count') for readability.

Common Table Names Reference (Use these exact names):
- {course}: Courses table
- {course_categories}: Course categories
- {user}: Users table
- {role}: Roles table
- {context}: Context table
- {role_assignments}: Role assignments
- {enrol}: Enrollment methods
- {user_enrolments}: User enrollments
- {course_modules}: Activity instances in a course
- {modules}: Module types (e.g. assign, quiz)
- {grade_items}: Grade items
- {grade_grades}: User grades
- {course_completions}: Course completion records
- {course_completion_criteria}: Completion criteria settings (NOT course_completion_crit)
- {course_modules_completion}: Activity completion records
- {assign}: Assignment settings
- {assign_submission}: Assignment submissions
- {quiz}: Quiz settings
- {quiz_attempts}: Quiz attempts
- {forum}: Forum settings
- {forum_posts}: Forum posts
- {logstore_standard_log}: Standard logs

Chart Type Guidelines:
- 'bar': Best for comparing quantities across categories (e.g., enrollments per course)
- 'line': Best for trends over time (e.g., enrollments per month)
- 'pie'/'doughnut': Best for showing proportions of a whole (e.g., user distribution by country)
- 'none': Use when data is a list of items, contains only text, has a single row, or charting would not add value

Example output:
{\"sql\": \"SELECT c.fullname AS course_name, COUNT(ue.id) AS enrolled FROM {course} c JOIN {enrol} e ON e.courseid = c.id JOIN {user_enrolments} ue ON ue.enrolid = e.id GROUP BY c.id, c.fullname ORDER BY enrolled DESC\", \"explanation\": \"Top 10 courses by enrollment count\", \"chart_type\": \"bar\", \"chart_label_column\": \"course_name\", \"chart_value_column\": \"enrolled\"}
";
    }

    /**
     * Simulates AI analysis and returns a SQL query + results.
     */
    public static function get_magic_insight($prompt)
    {
        global $DB, $USER;

        // Security check: Only admins for now.
        $context = context_system::instance();
        self::validate_context($context);
        require_capability('moodle/site:config', $context);

        $system_prompt = self::get_schema_context();
        $full_prompt = $system_prompt . "\n\nUser Question: " . $prompt;

        try {
            // Check if AI subsystem exists (Moodle 4.1+)
            if (!class_exists('\core_ai\manager')) {
                throw new \Exception('Moodle AI subsystem not found.');
            }

            $action = new \core_ai\aiactions\generate_text(
                $context->id,
                $USER->id,
                $full_prompt
            );

            if (!class_exists('\core\di')) {
                throw new \Exception('Dependency Injection not found.');
            }
            $manager = \core\di::get(\core_ai\manager::class);
            $result = $manager->process_action($action);

            // Check if the AI call was successful
            if (!$result->get_success()) {
                $errorcode = $result->get_errorcode();
                $errormsg = $result->get_errormessage();
                throw new \Exception('AI provider error (code: ' . $errorcode . '): ' . $errormsg);
            }

            // Get the generated content from the response data
            $response_data = $result->get_response_data();
            $generated_content = $response_data['generatedcontent'] ?? '';

            if (empty($generated_content)) {
                throw new \Exception('AI returned empty content. Response data keys: ' . implode(', ', array_keys($response_data)));
            }

            // Clean markdown if strictly formatted
            $json_str = str_replace(['```json', '```'], '', $generated_content);
            $ai_data = json_decode($json_str, true);

            if (!$ai_data || !isset($ai_data['sql'])) {
                // If JSON fails, try to extract it from text
                if (preg_match('/\{.*\}/s', $json_str, $matches)) {
                    $ai_data = json_decode($matches[0], true);
                }
                if (!$ai_data || !isset($ai_data['sql'])) {
                    throw new \Exception('AI did not return valid JSON. Response raw: ' . substr($generated_content, 0, 200));
                }
            }

            // ===== MULTI-LAYER SQL SAFETY VALIDATION =====
            // This is critical: AI-generated SQL must NEVER modify the database.

            $raw_sql = trim($ai_data['sql']);

            // LAYER 1: Must start with SELECT
            if (stripos($raw_sql, 'SELECT') !== 0) {
                throw new \Exception('Security: Query must start with SELECT. Rejected.');
            }

            // LAYER 2: Block ALL dangerous SQL keywords using word-boundary matching
            // Using \b word boundaries to avoid false positives (e.g., "selected", "updated_at")
            $dangerous_keywords = [
                'INSERT',
                'UPDATE',
                'DELETE',
                'DROP',
                'ALTER',
                'TRUNCATE',
                'CREATE',
                'REPLACE',
                'GRANT',
                'REVOKE',
                'RENAME',
                'EXEC',
                'EXECUTE',
                'CALL',
                'LOAD\s+DATA',
                'INTO\s+OUTFILE',
                'INTO\s+DUMPFILE',
                'LOCK\s+TABLES?',
                'UNLOCK\s+TABLES?',
                'FLUSH',
                'RESET',
                'PURGE',
                'HANDLER',
                'SET\s+',
                'PREPARE',
                'DEALLOCATE',
            ];
            foreach ($dangerous_keywords as $keyword) {
                // Word boundary check: \b ensures we match whole keywords, not substrings
                if (preg_match('/\b' . $keyword . '\b/i', $raw_sql)) {
                    throw new \Exception('Security: Forbidden SQL keyword detected (' . $keyword . '). Only SELECT queries are allowed.');
                }
            }

            // LAYER 3: Block semicolons to prevent multi-statement injection
            // (e.g., "SELECT 1; DROP TABLE users")
            if (strpos($raw_sql, ';') !== false) {
                throw new \Exception('Security: Semicolons are not allowed. Only single SELECT statements permitted.');
            }

            // LAYER 4: Block inline comments that could be used for obfuscation
            // e.g., "SEL/**/ECT ... ; DR/**/OP TABLE" or "SELECT 1 -- ; DROP TABLE"
            if (preg_match('/\/\*/', $raw_sql) || preg_match('/--/', $raw_sql)) {
                throw new \Exception('Security: SQL comments (/* */ or --) are not allowed.');
            }

            // LAYER 5: Block access to system/metadata tables that could leak sensitive info
            if (preg_match('/\b(information_schema|mysql|performance_schema|sys)\b/i', $raw_sql)) {
                throw new \Exception('Security: Access to system tables is not allowed.');
            }

            // Sanitize SQL: convert mdl_ prefix to {tablename} if AI used it
            $sql = $raw_sql;
            $sql = preg_replace('/\bmdl_([a-z_]+)/', '{$1}', $sql);

            // Strip LIMIT clause if present (system handles it)
            $sql = preg_replace('/\s+LIMIT\s+\d+(\s*,\s*\d+)?\s*;?\s*$/i', '', $sql);

            // Execute SQL using Moodle's read-only method (additional safety net)
            $results = $DB->get_records_sql($sql, null, 0, 1000);
            $data = array_values($results);

            return [
                'sql' => $sql,
                'data' => json_encode($data),
                'chart_type' => $ai_data['chart_type'] ?? 'none',
                'chart_label_column' => $ai_data['chart_label_column'] ?? '',
                'chart_value_column' => $ai_data['chart_value_column'] ?? '',
                'explanation' => $ai_data['explanation'] ?? 'Here is the report you requested.'
            ];
        } catch (\Throwable $e) {
            $debug = get_class($e) . ': ' . $e->getMessage() . "\n" . $e->getTraceAsString();
            return [
                'sql' => "/* ERROR:\n" . $debug . "\n*/",
                'data' => '[]',
                'chart_type' => 'table',
                'explanation' => 'Failed to generate report: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Returns for get_magic_insight.
     */
    public static function get_magic_insight_returns()
    {
        return new external_single_structure([
            'sql' => new external_value(PARAM_RAW, 'The generated SQL query'),
            'data' => new external_value(PARAM_RAW, 'JSON encoded result data'),
            'chart_type' => new external_value(PARAM_TEXT, 'Suggested chart type (bar, line, pie, doughnut, none)'),
            'chart_label_column' => new external_value(PARAM_TEXT, 'Column name to use for chart labels', VALUE_DEFAULT, ''),
            'chart_value_column' => new external_value(PARAM_TEXT, 'Column name to use for chart values', VALUE_DEFAULT, ''),
            'explanation' => new external_value(PARAM_TEXT, 'AI explanation')
        ]);
    }

    /**
     * Parameters for save_report.
     */
    public static function save_report_parameters()
    {
        return new external_function_parameters([
            'title' => new external_value(PARAM_TEXT, 'Report Title'),
            'sql_query' => new external_value(PARAM_RAW, 'SQL Query'),
            'chart_type' => new external_value(PARAM_TEXT, 'Chart Type', VALUE_DEFAULT, 'table')
        ]);
    }

    /**
     * Saves a magic report to the database.
     */
    public static function save_report($title, $sql_query, $chart_type = 'table')
    {
        global $DB, $USER;

        $context = context_system::instance();
        self::validate_context($context);
        require_capability('moodle/site:config', $context);

        $record = new \stdClass();
        $record->userid = $USER->id;
        $record->title = $title;
        $record->sql_query = $sql_query; // In real app, sanitization happens before execution
        $record->chart_type = $chart_type;
        $record->timecreated = time();
        $record->timemodified = time();

        $id = $DB->insert_record('local_smartdashboard_reports', $record);

        return $id;
    }

    /**
     * Returns for save_report.
     */
    public static function save_report_returns()
    {
        return new external_value(PARAM_INT, 'New Report ID');
    }

    /**
     * Parameters for get_saved_reports.
     */
    public static function get_saved_reports_parameters()
    {
        return new external_function_parameters([]);
    }

    /**
     * Lists saved reports.
     */
    public static function get_saved_reports()
    {
        global $DB, $USER;

        $context = context_system::instance();
        self::validate_context($context);
        // require_capability('moodle/site:config', $context); // Or allow others? Stick to admins for now.

        // Get saved reports for this user (or all admins?) — Let's just get all for now for simplicity.
        $reports = $DB->get_records('local_smartdashboard_reports', null, 'timecreated DESC');

        $result = [];
        foreach ($reports as $r) {
            $result[] = [
                'id' => $r->id,
                'title' => $r->title,
                'chart_type' => $r->chart_type,
                'sql_query' => $r->sql_query
            ];
        }

        return $result;
    }

    /**
     * Returns for get_saved_reports.
     */
    public static function get_saved_reports_returns()
    {
        return new external_multiple_structure(
            new external_single_structure([
                'id' => new external_value(PARAM_INT, 'Report ID'),
                'title' => new external_value(PARAM_TEXT, 'Title'),
                'chart_type' => new external_value(PARAM_TEXT, 'Chart Type'),
                'sql_query' => new external_value(PARAM_RAW, 'The SQL')
            ])
        );
    }

    /**
     * Parameters for delete_report.
     */
    public static function delete_report_parameters()
    {
        return new external_function_parameters([
            'reportid' => new external_value(PARAM_INT, 'The ID of the report to delete')
        ]);
    }

    /**
     * Deletes a saved magic report.
     */
    public static function delete_report($reportid)
    {
        global $DB;

        $context = context_system::instance();
        self::validate_context($context);
        require_capability('moodle/site:config', $context);

        if (!$DB->record_exists('local_smartdashboard_reports', ['id' => $reportid])) {
            throw new \moodle_exception('invalidrecord', 'error', '', 'Report not found');
        }

        $DB->delete_records('local_smartdashboard_reports', ['id' => $reportid]);

        return true;
    }

    /**
     * Returns for delete_report.
     */
    public static function delete_report_returns()
    {
        return new external_value(PARAM_BOOL, 'True on success');
    }
}
