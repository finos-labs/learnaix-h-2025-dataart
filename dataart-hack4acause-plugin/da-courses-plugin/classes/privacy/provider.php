<?php
namespace local_da_courses\privacy;

defined('MOODLE_INTERNAL') || die();

/**
 * This plugin does not store personal data beyond what Moodle already holds in file storage.
 */
class provider implements \core_privacy\local\metadata\null_provider {
    public static function get_reason(): string {
        return 'No personal data stored by local_da_courses.';
    }
}
