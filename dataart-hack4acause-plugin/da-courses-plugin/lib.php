<?php
defined('MOODLE_INTERNAL') || die();

/**
 * Serve files for local_da_courses fileareas.
 *
 * URL pattern:
 * pluginfile.php/{contextid}/local_da_courses/{filearea}/{itemid}/{filepath}/{filename}
 */
function local_da_courses_pluginfile($course, $cm, $context, $filearea, $args, $forcedownload, array $options = []) {
    require_login();
    require_capability('local/da_courses:view', $context);

    if ($context->contextlevel !== CONTEXT_SYSTEM) {
        return false;
    }
    if ($filearea !== 'coursepdfs') {
        return false;
    }

    $itemid = (int)array_shift($args); // our course_id
    $filepath = '/';
    $filename = array_pop($args);

    if ($args) {
        $filepath .= implode('/', $args) . '/';
    }

    $fs = get_file_storage();
    $file = $fs->get_file($context->id, 'local_da_courses', 'coursepdfs', $itemid, $filepath, $filename);
    if (!$file || $file->is_directory()) {
        return false;
    }

    send_stored_file($file, 0, 0, $forcedownload, $options);
}

function local_da_courses_extend_navigation(global_navigation $nav) {
    if (has_capability('local/da_courses:view', context_system::instance())) {
        $url  = new moodle_url('/local/da_courses/index.php');
        $text = get_string('pluginname', 'local_da_courses');

        // Add node with custom class
        $node = $nav->add(
            $text,
            $url,
            navigation_node::TYPE_CUSTOM,
            null,
            'local_da_courses_nav'
        );

        // Append a CSS class to the <li>/<a>
        $node->add_class('da-courses-link');
    }
}

