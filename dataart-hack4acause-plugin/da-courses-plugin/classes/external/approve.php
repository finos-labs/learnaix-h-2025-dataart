<?php
namespace local_da_courses\external;

defined('MOODLE_INTERNAL') || die();

// These bring the external function framework into scope.
require_once($CFG->libdir . '/externallib.php');
require_once($CFG->libdir . '/filelib.php');

use external_api;
use external_function_parameters;
use external_value;
use external_multiple_structure;
use external_single_structure;

class approve extends external_api {

    public static function approve_questions_parameters(): external_function_parameters {
        return new external_function_parameters([
            'qaid'  => new external_value(PARAM_INT, 'Question id in da_courses', VALUE_REQUIRED),
            'isapproved' => new external_value(PARAM_INT, 'Approval status', VALUE_REQUIRED),
        ]);
    }

    public static function approve_questions($qaid, $isapproved): array {
        global $DB;

        $params = self::validate_parameters(self::approve_questions_parameters(), [
            'qaid'    => $qaid,
            'isapproved' => $isapproved,
        ]);

        
        try {
            $context = \context_system::instance();
            self::validate_context($context);
            require_capability('local/da_courses:manage', $context);
            require_sesskey();

            // Ensure row exists.
            $rec = $DB->get_record('da_courses_questions', ['id' => $qaid]);
            
            if (!$rec) {
                throw new \moodle_exception('invalidrecord', 'error', '', 'Question ID '.$qaid);
            }

            $update = (object)[
                'id'     => $rec->id,
                'isapproved' => $isapproved,
            ];
            
            // $updated[] = [
            //     'id'    => (int)$rec->id,
            //     'qtext' => (string)$rec->q_text . 'UPDATED!',
            //     'atext' => (string)$rec->q_text . 'UPDATED!',
            // ];
            $DB->update_record('da_courses_questions', $update);

            return [
                'ok' => true,
            ];
        } catch (\Throwable $e) {
            // 🔍 Make debugging easier during dev
            debugging('[da_courses] approve_questions fatal: '.$e->getMessage().' @ '.$e->getFile().':'.$e->getLine(), DEBUG_DEVELOPER);
            // Return a JSON error via Moodle exception so the JS catch sees details (with developer debugging on)
            throw new \moodle_exception('generalexceptionmessage', 'error', '', null,
                $e->getMessage().' @ '.$e->getFile().':'.$e->getLine());
        }
    }

    public static function approve_questions_returns() {
        return new external_single_structure([
            'ok'       => new external_value(PARAM_BOOL, 'Result'),
        ]);
    }
}
