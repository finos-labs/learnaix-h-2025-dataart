<?php
namespace local_da_courses\external;

defined('MOODLE_INTERNAL') || die();

// These bring the external function framework into scope.
require_once($CFG->libdir . '/externallib.php');
require_once($CFG->libdir . '/filelib.php'); // for \curl

use external_api;
use external_function_parameters;
use external_value;
use external_single_structure;

class qa extends external_api {

    public static function update_qa_parameters(): external_function_parameters {
        return new external_function_parameters([
            'id'        => new external_value(PARAM_INT,  'Question row id', VALUE_REQUIRED),
            'qtext'     => new external_value(PARAM_TEXT, 'Question text',   VALUE_REQUIRED),
            'atext'     => new external_value(PARAM_TEXT, 'Answer text',     VALUE_REQUIRED),
        ]);
    }

    public static function update_qa($id, $qtext, $atext): array {
        global $DB;

        // Validate request & security.
        $params = self::validate_parameters(self::update_qa_parameters(), [
            'id' => $id, 'qtext' => $qtext, 'atext' => $atext,
        ]);
        $context = \context_system::instance();
        self::validate_context($context);
        require_capability('local/da_courses:manage', $context);
        require_sesskey();

        // Ensure row exists.
        $rec = $DB->get_record('da_courses_questions', ['id' => $params['id']], '*', MUST_EXIST);

        // Update.
        $update = (object)[
            'id'     => $rec->id,
            'q_text' => $params['qtext'],
            'a_text' => $params['atext'],
        ];
        $DB->update_record('da_courses_questions', $update);

        return [
            'ok'    => true,
            'id'    => $rec->id,
            'qtext' => $params['qtext'],
            'atext' => $params['atext'],
        ];
    }

    public static function update_qa_returns() {
        return new external_single_structure([
            'ok'        => new external_value(PARAM_BOOL, 'Result'),
            'id'        => new external_value(PARAM_INT,  'Row id'),
            'qtext'     => new external_value(PARAM_TEXT, 'Updated question'),
            'atext'     => new external_value(PARAM_TEXT, 'Updated answer'),
        ]);
    }
}
