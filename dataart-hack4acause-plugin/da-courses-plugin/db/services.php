<?php
// local/da_courses/db/services.php
defined('MOODLE_INTERNAL') || die();

$functions = [
    'local_da_courses_fetch' => [
        'classname'   => 'local_da_courses\external\fetch',
        'methodname'  => 'execute',
        'classpath'   => '', // autoloaded via PSR-4
        'description' => 'Fetch data from external API and return JSON or text',
        'type'        => 'read',
        'ajax'        => true, // IMPORTANT: allow AJAX
        'capabilities'=> 'moodle/site:config' // match your check in execute()
    ],
];

$functions['local_da_courses_update_qa'] = [
    'classname'    => 'local_da_courses\external\qa',
    'methodname'   => 'update_qa',
    'description'  => 'Update a question/answer row in da_courses_questions',
    'type'         => 'write',
    'ajax'         => true,
    'capabilities' => 'local/da_courses:manage',
];

$functions['local_da_courses_sync_questions'] = [
    'classname'    => 'local_da_courses\external\sync',
    'methodname'   => 'sync_questions',
    'description'  => 'Fetch questions via external API and update unchecked (not approved) ones for a course',
    'type'         => 'write',
    'ajax'         => true,
    'capabilities' => 'local/da_courses:manage',
];

$functions['local_da_courses_approve_questions'] = [
    'classname'    => 'local_da_courses\external\approve',
    'methodname'   => 'approve_questions',
    'description'  => 'Approve selected questions for a course',
    'type'         => 'write',
    'ajax'         => true,
    'capabilities' => 'local/da_courses:manage',
];

$functions['local_da_courses_approve_all_questions'] = [
    'classname'    => 'local_da_courses\external\approve_all',
    'methodname'   => 'approve_all_questions',
    'description'  => 'Fetch all questions via external API and update all for a course',
    'type'         => 'write',
    'ajax'         => true,
    'capabilities' => 'local/da_courses:manage',
];

$services = [];
