<?php

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir.'/formslib.php');

class enrol_twocheckout_edit_form extends moodleform {

    function definition() {
        $mform = $this->_form;

        list($instance, $plugin, $context) = $this->_customdata;

        $mform->addElement('header', 'header', get_string('pluginname', 'enrol_twocheckout'));

        $mform->addElement('text', 'name', get_string('custominstancename', 'enrol'));

        $options = array(ENROL_INSTANCE_ENABLED  => get_string('yes'),
                         ENROL_INSTANCE_DISABLED => get_string('no'));
        $mform->addElement('select', 'status', get_string('status', 'enrol_twocheckout'), $options);
        $mform->setDefault('status', $plugin->get_config('status'));

        $mform->addElement('text', 'cost', get_string('cost', 'enrol_twocheckout'), array('size'=>4));
        $mform->setDefault('cost', $plugin->get_config('cost'));

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
        $mform->addElement('select', 'currency', get_string('currency', 'enrol_twocheckout'), $twocheckoutcurrencies);
        $mform->setDefault('currency', $plugin->get_config('currency'));

        if ($instance->id) {
            $roles = get_default_enrol_roles($context, $instance->roleid);
        } else {
            $roles = get_default_enrol_roles($context, $plugin->get_config('roleid'));
        }
        $mform->addElement('select', 'roleid', get_string('assignrole', 'enrol_twocheckout'), $roles);
        $mform->setDefault('roleid', $plugin->get_config('roleid'));


        $mform->addElement('duration', 'enrolperiod', get_string('enrolperiod', 'enrol_twocheckout'), array('optional' => true, 'defaultunit' => 86400));
        $mform->setDefault('enrolperiod', $plugin->get_config('enrolperiod'));
        $mform->addHelpButton('enrolperiod', 'enrolperiod', 'enrol_twocheckout');

        $mform->addElement('date_selector', 'enrolstartdate', get_string('enrolstartdate', 'enrol_twocheckout'), array('optional' => true));
        $mform->setDefault('enrolstartdate', 0);
        $mform->addHelpButton('enrolstartdate', 'enrolstartdate', 'enrol_twocheckout');

        $mform->addElement('date_selector', 'enrolenddate', get_string('enrolenddate', 'enrol_twocheckout'), array('optional' => true));
        $mform->setDefault('enrolenddate', 0);
        $mform->addHelpButton('enrolenddate', 'enrolenddate', 'enrol_twocheckout');

        $mform->addElement('hidden', 'id');
        $mform->addElement('hidden', 'courseid');

        $this->add_action_buttons(true, ($instance->id ? null : get_string('addinstance', 'enrol')));

        $this->set_data($instance);
    }

    function validation($data, $files) {
        global $DB, $CFG;
        $errors = parent::validation($data, $files);

        list($instance, $plugin, $context) = $this->_customdata;

        if ($data['status'] == ENROL_INSTANCE_ENABLED) {
            if (!empty($data['enrolenddate']) and $data['enrolenddate'] < $data['enrolstartdate']) {
                $errors['enrolenddate'] = get_string('enrolenddaterror', 'enrol_twocheckout');
            }

            if (!is_numeric($data['cost'])) {
                $errors['cost'] = get_string('costerror', 'enrol_twocheckout');

            }
        }

        return $errors;
    }
}
