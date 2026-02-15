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
        return "You are a Moodle SQL expert. Generate a SQL query compatible with Moodle's database structure.
Tables:
- {course}: id, fullname, shortname, category
- {user}: id, username, firstname, lastname, email, city, country, lastaccess, suspended, deleted
- {role_assignments}: id, roleid (3=teacher, 5=student), contextid, userid
- {context}: id, contextlevel, instanceid (joins context to course/module)
- {enrol}: id, enrol (method), courseid
- {user_enrolments}: id, enrolid, userid, status (0=active)
- {course_completions}: id, course, userid, timecompleted
- {grade_items}: id, courseid, itemname, itemtype, grademin, grademax
- {grade_grades}: id, itemid, userid, finalgrade
- {modules}: id, name (assignment, quiz etc)
- {course_modules}: id, course, module, instance

Rules:
1. Use {table_name} syntax.
2. Return ONLY valid JSON with keys: 'sql', 'explanation', 'chart_type' (bar, line, pie, table).
3. Do not include markdown formatting (```json).
4. SQL must be SELECT only. No DELETE, UPDATE, DROP.
5. Limit results to 20 rows unless specified.
6. For 'completion rates', use logic: (completed / enrolled) * 100.
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
                throw new \moodle_exception('error', 'core', '', 'Moodle AI subsystem not found.');
            }

            $action = new \core_ai\aiactions\generate_text(
                $context->id,
                $USER->id,
                $full_prompt
            );

            $manager = \core\di::get(\core_ai\manager::class);
            $result = $manager->process_action($action);

            if (method_exists($result, 'get_generatedcontent')) {
                $generated_content = $result->get_generatedcontent();
            } elseif (method_exists($result, 'get_response')) {
                // Older/Alternative implementation
                $response = $result->get_response();
                $generated_content = $response['generatedcontent'] ?? '';
            } else {
                throw new \moodle_exception('error', 'core', '', 'AI Response object has unknown methods: ' . implode(', ', get_class_methods($result)));
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
                    throw new \moodle_exception('error', 'core', '', 'AI did not return valid JSON. Response: ' . substr($generated_content, 0, 100));
                }
            }

            // Safety check
            if (stripos(trim($ai_data['sql']), 'SELECT') !== 0) {
                throw new \moodle_exception('error', 'core', '', 'AI generated a non-SELECT query.');
            }

            // Execute SQL
            $results = $DB->get_records_sql($ai_data['sql']);
            $data = array_values($results);

            return [
                'sql' => $ai_data['sql'],
                'data' => json_encode($data),
                'chart_type' => $ai_data['chart_type'] ?? 'table',
                'explanation' => $ai_data['explanation'] ?? 'Here is the report you requested.'
            ];
        } catch (\Exception $e) {
            // Return error as a result so UI handles it gracefully
            return [
                'sql' => '-- Error: ' . $e->getMessage(),
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
            'data' => new external_value(PARAM_RAW, 'JSON encoded result data'), // Returning RAW JSON for flexibility
            'chart_type' => new external_value(PARAM_TEXT, 'Suggested chart type'),
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
