<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Plugin version and other meta-data are defined here.
 *
 * @package     local_greetings
 * @copyright   2025 Your Name <you@example.com>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../config.php');
require_once($CFG->dirroot. '/local/greetings/lib.php');

$context = context_system::instance();
$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/local/greetings/index.php'));
$PAGE->set_pagelayout('standard');
$PAGE->set_title(get_string('pluginname', 'local_greetings'));
$PAGE->set_heading(get_string('pluginname', 'local_greetings'));

require_login();
if (isguestuser()) {
    throw new moodle_exception('noguest');
}

$messageform = new \local_greetings\form\message_form();

if ($data = $messageform->get_data()) {
    $message = required_param('message', PARAM_TEXT);

    if (!empty($message)) {
        $record = new stdClass;
        $record->message = $message;
        $record->timecreated = time();
        $record->userid = $USER->id;

        $DB->insert_record('local_greetings_messages', $record);
    }
}


echo $OUTPUT->header();
if (isloggedin()) {
    $usergreeting = local_greetings_get_greeting($USER);
} else {
    $usergreeting = get_string('greetinguser', 'local_greetings');
}

$action = optional_param('action', '', PARAM_ALPHA);
$id = optional_param('id', 0, PARAM_INT);
if ($action === 'del' && $id > 0) {
    try {
        require_sesskey();
        if ($DB->record_exists('local_greetings_messages', ['id' => $id])) {
            $DB->delete_records('local_greetings_messages', ['id' => $id]);
            redirect(new moodle_url('/local/greetings/index.php'), 'Запись удалена.', null, \core\output\notification::NOTIFY_SUCCESS);
        } else {
            redirect(new moodle_url('/local/greetings/index.php'), 'Запись не найдена.', null, \core\output\notification::NOTIFY_ERROR);
        }
    } catch (Exception $e) {
        redirect(new moodle_url('/local/greetings/index.php'), 'Ошибка: ' . $e->getMessage(), null, \core\output\notification::NOTIFY_ERROR);
    }
}

$templatedata = ['usergreeting' => $usergreeting];

echo $OUTPUT->render_from_template('local_greetings/greeting_message', $templatedata);
$messageform->display();
$userfields = \core_user\fields::for_name()->with_identity($context);
$userfieldssql = $userfields->get_sql('u');

$sql = "SELECT m.id, m.message, m.timecreated, m.userid {$userfieldssql->selects}
          FROM {local_greetings_messages} m
     LEFT JOIN {user} u ON u.id = m.userid
      ORDER BY timecreated DESC";

$messages = $DB->get_records_sql($sql);
$cardbackgroundcolor = get_config('local_greetings', 'messagecardbgcolor');
$templatedata = [
    'messages' => array_values($messages),
    'cardbackgroundcolor' => $cardbackgroundcolor,
];
echo $OUTPUT->render_from_template('local_greetings/messages', $templatedata);
echo $OUTPUT->footer();

