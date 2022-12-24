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

global $CFG, $DB, $PAGE, $OUTPUT;

require_once(__DIR__ . '/../../config.php');
require_once(__DIR__ . '/lib.php');
require_once(__DIR__ . '/form.php');

$id = optional_param('id', 0, PARAM_INT);
$delete = optional_param('delete', 0, PARAM_INT);
$returnurl = optional_param('returnurl', new moodle_url('/blocks/course_rating/manage.php'), PARAM_URL);

require_login();

$context = context_system::instance();
$PAGE->set_context($context);
$PAGE->set_pagelayout('admin');

require_capability('block/course_rating:manage', $context);

$params['id'] = $id;

$baseurl = new moodle_url('/blocks/course_rating/edit.php', $params);
$PAGE->set_url($baseurl);

if ($id) {
    $title = get_string('edit');
    $template = $DB->get_record('course_rating_templates', array('id' => $id), '*', MUST_EXIST);

    $is_used = block_course_rating_is_used($id);
} else {
    $title = get_string('add');

    $template = new stdClass();

    $is_used = false;
}

if ($delete) {
    if ($is_used) {
        redirect($returnurl, 'Шаблон используется - удаление невозможно!', 5, \core\output\notification::NOTIFY_ERROR);
    }

    $DB->delete_records('course_rating_templates', array('id' => $id));


    redirect($returnurl);
}

$PAGE->set_heading($title);
$PAGE->set_title($title);

$managefeeds = new moodle_url('/blocks/course_rating/manage.php');
$PAGE->navbar->add(get_string('blocks'));
$PAGE->navbar->add(get_string('pluginname', 'block_course_rating'));
$PAGE->navbar->add(get_string('manage', 'block_course_rating'));
$PAGE->navbar->add($title);

error_reporting(E_ALL);

echo $OUTPUT->header();

$mform = new block_course_rating_form(null, $is_used);

$mform->set_data($template);

if ($mform->is_cancelled()) {
    redirect($returnurl);

} else if ($data = $mform->get_data()) {
    $template->usermodified = $USER->id;
    $template->timemodified = time();
    $template->data = $data->data;
    $template->name = $data->name;

    if (empty($template->id)) {
        $template->timecreated = $template->timemodified;

        $DB->insert_record('course_rating_templates', $template, false);
    } else {
        $DB->update_record('course_rating_templates', $template);
    }

    redirect($returnurl);
}

$mform->display();

echo $OUTPUT->footer();
