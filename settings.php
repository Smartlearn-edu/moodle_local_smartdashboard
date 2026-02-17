<?php
defined('MOODLE_INTERNAL') || die;

if ($hassiteconfig) {
    $settings = new admin_settingpage('local_smartdashboard', get_string('pluginname', 'local_smartdashboard'));
    $ADMIN->add('localplugins', $settings);

    $settings->add(new admin_setting_heading(
        'local_smartdashboard/student_icons_heading',
        get_string('student_icons_heading', 'local_smartdashboard'),
        get_string('student_icons_desc', 'local_smartdashboard')
    ));

    for ($i = 1; $i <= 10; $i++) {
        $settings->add(new admin_setting_heading(
            'local_smartdashboard/icon_heading_' . $i,
            get_string('icon_heading', 'local_smartdashboard', $i),
            ''
        ));

        $settings->add(new admin_setting_configtext(
            'local_smartdashboard/icon_name_' . $i,
            get_string('icon_name', 'local_smartdashboard'),
            '',
            '',
            PARAM_TEXT
        ));

        $settings->add(new admin_setting_configtext(
            'local_smartdashboard/icon_class_' . $i,
            get_string('icon_class', 'local_smartdashboard'),
            get_string('icon_class_desc', 'local_smartdashboard'),
            'fa-star',
            PARAM_TEXT
        ));

        $settings->add(new admin_setting_configtext(
            'local_smartdashboard/icon_url_' . $i,
            get_string('icon_url', 'local_smartdashboard'),
            '',
            '#',
            PARAM_URL
        ));
    }
}
