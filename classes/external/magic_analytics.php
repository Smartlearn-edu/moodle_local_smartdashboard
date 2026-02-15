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
     * Simulates AI analysis and returns a SQL query + results.
     */
    public static function get_magic_insight($prompt)
    {
        global $DB;

        // Security check: Only admins for now.
        $context = context_system::instance();
        self::validate_context($context);
        require_capability('moodle/site:config', $context);

        // --- MOCK AI LOGIC ---
        // In a real implementation, we would call an AI API here.
        // For now, we return a hardcoded response for "Low Completion Rates".

        $explanation = "I've analyzed enrollment and completion data to find courses with the lowest completion rates. Here are the bottom 5.";

        // This query calculates completion rate = (completed / enrolled) * 100
        $sql = "SELECT c.fullname, 
                       COUNT(DISTINCT ue.userid) AS enrolled,
                       SUM(CASE WHEN cc.timecompleted > 0 THEN 1 ELSE 0 END) AS completed,
                       ROUND((SUM(CASE WHEN cc.timecompleted > 0 THEN 1 ELSE 0 END) * 100.0 / NULLIF(COUNT(DISTINCT ue.userid), 0)), 1) AS completion_rate
                FROM {course} c
                JOIN {enrol} e ON e.courseid = c.id
                JOIN {user_enrolments} ue ON ue.enrolid = e.id
                LEFT JOIN {course_completions} cc ON cc.course = c.id AND cc.userid = ue.userid
                WHERE c.id != 1
                GROUP BY c.id, c.fullname
                HAVING COUNT(DISTINCT ue.userid) > 0
                ORDER BY completion_rate ASC, enrolled DESC
                LIMIT 5";

        // Execute the query safely
        $results = $DB->get_records_sql($sql);
        $data = array_values($results); // Remove keys

        return [
            'sql' => $sql,
            'data' => json_encode($data),
            'chart_type' => 'bar',
            'explanation' => $explanation
        ];
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
}
