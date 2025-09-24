<?php
namespace local_da_courses\external;

defined('MOODLE_INTERNAL') || die();


// These bring the external function framework into scope.
require_once($CFG->libdir . '/externallib.php');
require_once($CFG->libdir . '/filelib.php'); // for \curl

use external_api;
use external_function_parameters;
use external_single_structure;
use external_value;
use moodle_exception;

class fetch extends external_api {
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'endpoint' => new external_value(PARAM_RAW_TRIMMED, 'Optional endpoint override', VALUE_DEFAULT, '')
        ]);
    }

    public static function execute(string $endpoint = ''): array {
        global $CFG, $USER;

        self::validate_context(\context_system::instance());
        require_capability('moodle/site:config', \context_system::instance());

        // Choose endpoint: from args or plugin config.
        if (empty($endpoint)) {
            $endpoint = get_config('local_da_courses', 'apiendpoint');
            if (empty($endpoint)) {
                throw new moodle_exception('missingconfig', 'local_da_courses', '', 'apiendpoint');
            }
        }

        // Do the server-side HTTP request using Moodle curl.
        require_once($CFG->libdir . '/filelib.php');
        $curl = new \curl();
        $headers = [
            'Content-Type: application/json',
            'Accept: application/json',
        ];
        $options = [
            'CURLOPT_TIMEOUT'     => 15,                           // string key, not constant
            'CURLOPT_HTTPHEADER'  => $headers, // headers belong in options
            // 'CURLOPT_FOLLOWLOCATION' => true,                   // (optional) add more options like this
        ];
        $decoded = json_decode('{"filename": "string"}', true);
        $raw = $curl->post($endpoint, json_encode($decoded, JSON_UNESCAPED_UNICODE), $options);
        $info = $curl->get_info();
        $status = isset($info['http_code']) ? (int)$info['http_code'] : 0;

        // Try to decode JSON; if not JSON, return as text.
        $decoded = json_decode($raw, true);
        if (json_last_error() === JSON_ERROR_NONE) {
            return [
                'ok' => true,
                'status' => $status,
                'json' => json_encode($decoded, JSON_UNESCAPED_UNICODE), // return as string for strict schema
                'text' => ''
            ];
        } else {
            return [
                'ok' => ($status >= 200 && $status < 300),
                'status' => $status,
                'json' => '',
                'text' => (string)$raw
            ];
        }
    }

    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'ok'     => new external_value(PARAM_BOOL, 'Request success heuristic'),
            'status' => new external_value(PARAM_INT, 'HTTP status code'),
            'json'   => new external_value(PARAM_RAW, 'JSON string if response is JSON, else empty'),
            'text'   => new external_value(PARAM_RAW, 'Raw text if not JSON, else empty'),
        ]);
    }
}
