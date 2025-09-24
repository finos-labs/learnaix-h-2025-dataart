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

class sync extends external_api {

    public static function sync_questions_parameters(): external_function_parameters {
        return new external_function_parameters([
            'courseid'  => new external_value(PARAM_INT, 'Course id in da_courses', VALUE_REQUIRED),
            'fileid'    => new external_value(PARAM_INT, 'Course file id in da_courses', VALUE_REQUIRED),
            'qaids'     => new external_value(PARAM_TEXT, 'Course question ids in da_courses', VALUE_REQUIRED),
        ]);
    }

    public static function sync_questions($courseid, $fileid, $qaids): array {
        global $DB;

        $params = self::validate_parameters(self::sync_questions_parameters(), [
            'courseid' => $courseid,
            'fileid'   => $fileid,
            'qaids'    => $qaids,
        ]);

        $qa_ids = explode('+', (string)$qaids);
        if (empty($qa_ids)) {
            return ['error' => 'No question ids provided', 'debug_info' => $qaids];
        }

        // Call external endpoint: GET <base>/courses/{courseid}/questions
        $endpoint = (string)get_config('local_da_courses', 'endpoint');

        if (!$endpoint) {
            throw new \moodle_exception('Endpoint not configured (local_da_courses/endpoint).');
        }

        $data = [
            'quiz' => [
                'questions' => []
            ]
        ];
        try {
            $curl = curl_init();

            $postfields = [
                'course_id' => $courseid,
            ];

            $flask_url = $endpoint;
            // Set cURL options
            curl_setopt_array($curl, array(
                CURLOPT_URL => $flask_url,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => $postfields,
                CURLOPT_HTTPHEADER => [
                    'Accept: application/json'
                ],
                CURLOPT_TIMEOUT => 90,
                CURLOPT_CONNECTTIMEOUT => 30
            ));

            // Execute the request
            $response = curl_exec($curl);
            $curl_error = curl_error($curl);

            // Close cURL
            curl_close($curl);

            if ($curl_error) {
                throw new \moodle_exception('curl error: ' . $curl_error, 'local_da_courses');
            }
            $data = json_decode($response, true);
        } catch (\Throwable $e) {
            // 🔍 Make debugging easier during dev
            debugging('[da_courses] resync_questions fatal: '.$e->getMessage().' @ '.$e->getFile().':'.$e->getLine(), DEBUG_DEVELOPER);
            // Return a JSON error via Moodle exception so the JS catch sees details (with developer debugging on)
            throw new \moodle_exception('generalexceptionmessage', 'error', '', null,
                $e->getMessage().' @ '.$e->getFile().':'.$e->getLine());
        }
        
        // Build a map by id, supporting keys id/q_text/atext variants.
        $updated = [];

        foreach ($data['quiz']['questions'] as $question) {
            if (!$qa_ids) {
                break;
            }
            $q_id = array_shift($qa_ids);
            // Update.
            $update = (object)[
                'id'     => $q_id,
                'q_text' => $question['question'],
                'a_text' => $question['answer'],
            ];
            $updated[] = [
                'id'    => (int)$q_id,
                'qtext' => (string)$question['question'],
                'atext' => (string)$question['answer'],
            ];
            $DB->update_record('da_courses_questions', $update);
        }

        return [
            'ok'        => true,
            'courseid'  => $courseid,
            'count'     => count($updated),
            'updated'   => $updated,
        ];

    }

    public static function sync_questions_returns() {
        return new external_single_structure([
            'ok'       => new external_value(PARAM_BOOL, 'Result'),
            'courseid' => new external_value(PARAM_INT, 'Course id'),
            'count'    => new external_value(PARAM_INT, 'Updated rows count'),
            'updated'  => new external_multiple_structure(new external_single_structure([
                'id'    => new external_value(PARAM_INT,  'Question id'),
                'qtext' => new external_value(PARAM_TEXT, 'New question text'),
                'atext' => new external_value(PARAM_TEXT, 'New answer text'),
            ])),
        ]);
    }
}
