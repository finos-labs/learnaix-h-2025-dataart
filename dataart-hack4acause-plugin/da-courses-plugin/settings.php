<?php
defined('MOODLE_INTERNAL') || die();

if ($hassiteconfig) {
    $settings = new admin_settingpage('local_da_courses', get_string('settings:heading', 'local_da_courses'));
    
    // Add a clickable item under: Site administration → Plugins → Local plugins
    $ADMIN->add('localplugins', new admin_externalpage(
        'local_da_courses_admin',                                
        get_string('navmenu', 'local_da_courses'),              
        new moodle_url('/local/da_courses/index.php'),          
        'local/da_courses:view'                                  
    ));

    $settings->add(new admin_setting_configtext(
        'local_da_courses/endpoint',
        get_string('settings:endpoint', 'local_da_courses'),
        get_string('settings:endpoint_desc', 'local_da_courses'),
        'http://localhost:5000/upload',
        PARAM_URL
    ));

    $settings->add(new admin_setting_configtext(
        'local_da_courses/apikey',
        get_string('settings:apikey', 'local_da_courses'),
        get_string('settings:apikey_desc', 'local_da_courses'),
        '',
        PARAM_ALPHANUMEXT
    ));

    $ADMIN->add('localplugins', $settings);
}