<?php
require("../../config.php");
require_once($CFG->dirroot.'/enrol/twocheckout/lib.php');
require_once($CFG->libdir.'/enrollib.php');
require_once($CFG->libdir . '/filelib.php');

$return_data = array();
foreach ($_REQUEST as $key => $value) {
    $return_data["$key"] = urlencode($value);
}

$plugin = enrol_get_plugin('twocheckout');

if (! $user = $DB->get_record("user", array("id"=>$return_data['userid']))) {
    message_twocheckout_error_to_admin("Not a valid user id", $return_data);
}

if (! $course = $DB->get_record("course", array("id"=>$return_data['courseid']))) {
    message_twocheckout_error_to_admin("Not a valid course id", $return_data);
}

if (! $context = context_course::instance($course->id, IGNORE_MISSING)) {
    message_twocheckout_error_to_admin("Not a valid context id", $return_data);
}

if (! $plugin_instance = $DB->get_record("enrol", array("id"=>$return_data['instanceid'], "status"=>0))) {
    message_twocheckout_error_to_admin("Not a valid instance id", $return_data);
}

if (!$course = $DB->get_record("course", array("id"=>$return_data['courseid']))) {
    message_twocheckout_error_to_admin("Not a valid course", $return_data);
}

// Check that amount paid is the correct amount
if ( (float) $plugin_instance->cost <= 0 ) {
    $cost = (float) $plugin->get_config('cost');
} else {
    $cost = (float) $plugin_instance->cost;
}

if ($return_data['total'] < $cost) {
    $cost = format_float($cost, 2);
    message_twocheckout_error_to_admin("Amount paid is not enough.", $return_data);
}

// Validate reponse from 2Checkout
if ($return_data['demo'] == 'Y') {
    $return_data['order_number'] = 1;
}

if ($return_data['key'] != strtoupper(md5($plugin->get_config('twocheckoutsecret_word') . $plugin->get_config('twocheckoutseller_id') . $return_data['order_number'] . sprintf("%01.2f",$cost)))) {
    error_log('order_number: ' . $return_data['order_number'] . 'total: ' . $cost . ' key: ' . $key);
    message_twocheckout_error_to_admin("Could not validate response from 2Checkout.", $return_data);
} else {

    if ($plugin_instance->enrolperiod) {
        $timestart = time();
        $timeend   = $timestart + $plugin_instance->enrolperiod;
    } else {
        $timestart = 0;
        $timeend   = 0;
    }

    // Enrol user
    $plugin->enrol_user($plugin_instance, $user->id, $plugin_instance->roleid, $timestart, $timeend);

    $users = get_users_by_capability($context, 'moodle/course:update', 'u.*', 'u.id ASC', '', '', '', '', false, true);
    $teacher = array_shift($users);
}

// Setup redirect destination and write record to the 2Checkout table
$coursecontext = context_course::instance($course->id, IGNORE_MISSING);
$context = context_course::instance($course->id, MUST_EXIST);
$PAGE->set_context($context);

require_login();

if (!empty($SESSION->wantsurl)) {
    $destination = $SESSION->wantsurl;
    unset($SESSION->wantsurl);
} else {
    $destination = "$CFG->wwwroot/course/view.php?id=$course->id";
}

$fullname = format_string($course->fullname, true, array('context' => $context));

if (is_enrolled($context, NULL, '', true)) {
    $data = new stdClass();
    $data->item_name            = $return_data['li_0_name'];
    $data->courseid             = $return_data['courseid'];
    $data->userid               = $return_data['userid'];
    $data->instanceid           = $return_data['instanceid'];
    $data->order_number         = $return_data['order_number'];
    $data->payment_type         = $return_data['pay_method'];
    $data->timeupdated          = time();
    $DB->insert_record("enrol_twocheckout", $data);

// Send Notifications
    $mailstudents = $plugin->get_config('mailstudents');
    $mailteachers = $plugin->get_config('mailteachers');
    $mailadmins   = $plugin->get_config('mailadmins');
    $shortname = format_string($course->shortname, true, array('context' => $context));

    if ($users = get_users_by_capability($context, 'moodle/course:update', 'u.*', 'u.id ASC',
                                            '', '', '', '', false, true)) {
        $users = sort_by_roleassignment_authority($users, $context);
        $teacher = array_shift($users);
    } else {
        $teacher = false;
    }

    if (!empty($mailstudents)) {
        $a = new stdClass();
        $a->coursename = format_string($course->fullname, true, array('context' => $coursecontext));
        $a->profileurl = "$CFG->wwwroot/user/view.php?id=$user->id";

        $eventdata = new \core\message\message();
        $eventdata->modulename        = 'moodle';
        $eventdata->component         = 'enrol_twocheckout';
        $eventdata->name              = 'twocheckout_enrolment';
        $eventdata->userfrom          = $teacher;
        $eventdata->userto            = $user;
        $eventdata->subject           = get_string("enrolmentnew", 'enrol', $shortname);
        $eventdata->fullmessage       = get_string('welcometocoursetext', '', $a);
        $eventdata->fullmessageformat = FORMAT_PLAIN;
        $eventdata->fullmessagehtml   = '';
        $eventdata->smallmessage      = '';
        message_send($eventdata);

    }

    if (!empty($mailteachers)) {
        $a = new stdClass();
        $a->course = format_string($course->fullname, true, array('context' => $coursecontext));
        $a->user = fullname($user);

        $eventdata = new \core\message\message();
        $eventdata->modulename        = 'moodle';
        $eventdata->component         = 'enrol_twocheckout';
        $eventdata->name              = 'twocheckout_enrolment';
        $eventdata->userfrom          = $user;
        $eventdata->userto            = $teacher;
        $eventdata->subject           = get_string("enrolmentnew", 'enrol', $shortname);
        $eventdata->fullmessage       = get_string('enrolmentnewuser', 'enrol', $a);
        $eventdata->fullmessageformat = FORMAT_PLAIN;
        $eventdata->fullmessagehtml   = '';
        $eventdata->smallmessage      = '';
        message_send($eventdata);
    }

    if (!empty($mailadmins)) {
        $a = new stdClass();
        $a->course = format_string($course->fullname, true, array('context' => $coursecontext));
        $a->user = fullname($user);
        $admins = get_admins();
        foreach ($admins as $admin) {
            $eventdata = new \core\message\message();
            $eventdata->modulename        = 'moodle';
            $eventdata->component         = 'enrol_twocheckout';
            $eventdata->name              = 'twocheckout_enrolment';
            $eventdata->userfrom          = $user;
            $eventdata->userto            = $admin;
            $eventdata->subject           = get_string("enrolmentnew", 'enrol', $shortname);
            $eventdata->fullmessage       = get_string('enrolmentnewuser', 'enrol', $a);
            $eventdata->fullmessageformat = FORMAT_PLAIN;
            $eventdata->fullmessagehtml   = '';
            $eventdata->smallmessage      = '';
            message_send($eventdata);
        }
    }

    redirect($destination, get_string('paymentthanks', '', $fullname));



} else {   /// Somehow they aren't enrolled yet!  :-(
    $PAGE->set_url($destination);
    echo $OUTPUT->header();
    $a = new stdClass();
    $a->teacher = get_string('defaultcourseteacher');
    $a->fullname = $fullname;
    notice(get_string('paymentsorry', '', $a), $destination);
}


//--- HELPER FUNCTIONS --------------------------------------------------------------------------------------


function message_twocheckout_error_to_admin($subject, $data) {
    echo $subject;
    $admin = get_admin();
    $site = get_site();

    $message = "$site->fullname:  Transaction failed.\n\n$subject\n\n";

    foreach ($data as $key => $value) {
        $message .= "$key => $value\n";
    }

    $eventdata = new \core\message\message();
    $eventdata->modulename        = 'moodle';
    $eventdata->component         = 'enrol_twocheckout';
    $eventdata->name              = 'twocheckout_enrolment';
    $eventdata->userfrom          = $admin;
    $eventdata->userto            = $admin;
    $eventdata->subject           = "2Checkout ERROR: ".$subject;
    $eventdata->fullmessage       = $message;
    $eventdata->fullmessageformat = FORMAT_PLAIN;
    $eventdata->fullmessagehtml   = '';
    $eventdata->smallmessage      = '';
    message_send($eventdata);
}
