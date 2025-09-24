<?php
namespace local_da_courses\external;

defined('MOODLE_INTERNAL') || die();

// These bring the external function framework into scope.
require_once($CFG->libdir . '/externallib.php');
require_once($CFG->libdir . '/filelib.php');

use external_api;
use external_function_parameters;
use external_value;
use external_single_structure;

class approve_all extends external_api {

    public static function approve_all_questions_parameters(): external_function_parameters {
        return new external_function_parameters([
            'courseid'  => new external_value(PARAM_INT, 'Course id in da_courses', VALUE_REQUIRED),
        ]);
    }

    public static function approve_all_questions($courseid): array {
        global $DB;

        $params = self::validate_parameters(self::approve_all_questions_parameters(), [
            'courseid' => $courseid,
        ]);
        
        try {
            // Ensure the course exists.
            $questions = $DB->get_recordset('da_courses_questions', ['course_id' => $courseid]);

            $context = \context_system::instance();
            self::validate_context($context);
            require_capability('local/da_courses:manage', $context);
            require_sesskey();

            foreach ($questions as $rec) {
                // Update.
                $update = (object)[
                    'id'     => $rec->id,
                    'isapproved' => 1,
                ];
                $DB->update_record('da_courses_questions', $update);
            }

            return [
                'ok' => true,
            ];
        } catch (\Throwable $e) {
            // 🔍 Make debugging easier during dev
            debugging('[da_courses] approve_all_questions fatal: '.$e->getMessage().' @ '.$e->getFile().':'.$e->getLine(), DEBUG_DEVELOPER);
            // Return a JSON error via Moodle exception so the JS catch sees details (with developer debugging on)
            throw new \moodle_exception('generalexceptionmessage', 'error', '', null,
                $e->getMessage().' @ '.$e->getFile().':'.$e->getLine());
        }
    }

    public static function approve_all_questions_returns() {
        return new external_single_structure([
            'ok' => new external_value(PARAM_BOOL, 'Result'),
        ]);
    }
}
