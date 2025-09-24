<?php
defined('MOODLE_INTERNAL') || die();

/**
 * Extra uninstall cleanup for local_da_courses.
 * Note: core already removes tables defined in install.xml.
 */
function xmldb_local_da_courses_uninstall(): bool {
    global $DB;
    $dbman = $DB->get_manager();

    // Example: ensure table is gone (only needed for tables NOT in install.xml).
    $table = new xmldb_table('da_courses');
    if ($dbman->table_exists($table)) {
        $dbman->drop_table($table);
    }

    // Optional: remove plugin file areas.
    $fs = get_file_storage();
    $fs->delete_area_files(context_system::instance()->id, 'local_da_courses', 'coursepdfs');

    return true;
}
