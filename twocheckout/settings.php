<?php

defined('MOODLE_INTERNAL') || die();

if ($ADMIN->fulltree) {

    //--- settings ------------------------------------------------------------------------------------------
    $settings->add(new admin_setting_heading('enrol_twocheckout_settings', '', get_string('pluginname_desc', 'enrol_twocheckout')));

    $settings->add(new admin_setting_configtext('enrol_twocheckout/twocheckoutseller_id', get_string('seller_id', 'enrol_twocheckout'), get_string('seller_id_desc', 'enrol_twocheckout'), '', 0));

    $settings->add(new admin_setting_configtext('enrol_twocheckout/twocheckoutsecret_word', get_string('secret_word', 'enrol_twocheckout'), get_string('secret_word_desc', 'enrol_twocheckout'), '', 0));

    $settings->add(new admin_setting_configcheckbox('enrol_twocheckout/mailstudents', get_string('mailstudents', 'enrol_twocheckout'), '', 0));

    $settings->add(new admin_setting_configcheckbox('enrol_twocheckout/mailteachers', get_string('mailteachers', 'enrol_twocheckout'), '', 0));

    $settings->add(new admin_setting_configcheckbox('enrol_twocheckout/mailadmins', get_string('mailadmins', 'enrol_twocheckout'), '', 0));

    //--- enrol instance defaults ----------------------------------------------------------------------------
    $settings->add(new admin_setting_heading('enrol_twocheckout_defaults',
        get_string('enrolinstancedefaults', 'admin'), get_string('enrolinstancedefaults_desc', 'admin')));

    $options = array(ENROL_INSTANCE_ENABLED  => get_string('yes'),
                     ENROL_INSTANCE_DISABLED => get_string('no'));
    $settings->add(new admin_setting_configselect('enrol_twocheckout/status',
        get_string('status', 'enrol_twocheckout'), get_string('status_desc', 'enrol_twocheckout'), ENROL_INSTANCE_DISABLED, $options));

    $settings->add(new admin_setting_configtext('enrol_twocheckout/cost', get_string('cost', 'enrol_twocheckout'), '', 0, PARAM_FLOAT, 4));

    $twocheckoutcurrencies = array('USD' => 'US Dollars',
                              'ARS' => 'Argentina Peso',
                              'AUD' => 'Australian Dollars',
                              'BRL' => 'Brazilian Real',
                              'GBP' => 'GBP-Sterlings',
                              'CAD' => 'Canadian Dollars',
                              'DKK' => 'Danish Kroner',
                              'EUR' => 'Euros',
                              'HKD' => 'Hong Kong Dollars',
                              'INR' => 'Indian Rupee',
                              'ILS' => 'Israel Shekel',
                              'JPY' => 'Japanese Yen',
                              'LTL' => 'Lithuania Litas',
                              'MYR' => 'Malaysia Ringgit',
                              'MXN' => 'Mexican Peso',
                              'NZD' => 'New Zealand Dollars',
                              'NOK' => 'Norwegian Kroner',
                              'PHP' => 'Philippines Peso',
                              'RON' => 'Romania Leu',
                              'RUB' => 'Russian Federation Ruble',
                              'SGD' => 'Singapore Dollar',
                              'ZAR' => 'South African Rand',
                              'SEK' => 'Swedish Kroner',
                              'CHF' => 'Swiss Francs',
                              'TRY' => 'Turkish Lira',
                              'AED' => 'United Arab Emirates Dirham'
                             );
    $settings->add(new admin_setting_configselect('enrol_twocheckout/currency', get_string('currency', 'enrol_twocheckout'), '', 'USD', $twocheckoutcurrencies));

    if (!during_initial_install()) {
        $options = get_default_enrol_roles(context_system::instance());
        $student = get_archetype_roles('student');
        $student = reset($student);
        $settings->add(new admin_setting_configselect('enrol_twocheckout/roleid',
            get_string('defaultrole', 'enrol_twocheckout'), get_string('defaultrole_desc', 'enrol_twocheckout'), $student->id, $options));
    }

    $settings->add(new admin_setting_configduration('enrol_twocheckout/enrolperiod',
        get_string('enrolperiod', 'enrol_twocheckout'), get_string('enrolperiod_desc', 'enrol_twocheckout'), 0));
}
