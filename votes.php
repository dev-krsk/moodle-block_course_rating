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

require_once(__DIR__ . '/../../config.php');
require_once(__DIR__ . '/lib.php');
require_once(__DIR__ . '/form.php');

$templateid = required_param('templateid', PARAM_INT);
$courseid = required_param('courseid', PARAM_INT);
$page = optional_param('page', 0, PARAM_INT);


$course = get_course($courseid);
$context = context_course::instance($course->id);

require_login($course);

$params['templateid'] = $templateid;
$params['courseid'] = $courseid;
$params['page'] = $page;
$baseurl = new moodle_url('/blocks/course_rating/votes.php', $params);
$PAGE->set_url($baseurl);

$PAGE->set_pagelayout('course');

$title = get_string('votes', 'block_course_rating');
$PAGE->set_heading($title);
$PAGE->set_title($title);

$PAGE->navbar->add($title);

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('votes:heading', 'block_course_rating'), 3);

$answers = block_course_rating_get_answers_paging($courseid, $templateid, $page);
$templates = block_course_rating_get_questions($templateid);

$table = new html_table();
$table->attributes['class'] = 'generaltable';

$table->head = array(
    get_string('votes:table:date', 'block_course_rating'),
    get_string('votes:table:answers', 'block_course_rating'),
    get_string('votes:table:feedback', 'block_course_rating'),
);

//$table->size = array('70%', '20%', '10%');

if (is_siteadmin()) {
    array_unshift($table->head, get_string('votes:table:fio', 'block_course_rating'));
    //array_unshift($table->size, '0%');


    array_push($table->head, get_string('actions', 'moodle'));
    //array_push($table->size, '0%');
}

$data = array();

foreach ($answers['data'] as $item) {
    $line = [];

    $line[] = date_format_string($item->timecreated, '%d.%m.%Y %H:%M');

    $userAnswers = preg_split('@#@', $item->answers, -1, PREG_SPLIT_NO_EMPTY);

    $answer = "";

    foreach ($userAnswers as $key => $userAnswer) {
        $answer .= $templates[$key] .
            ' <div style="width: 50px">' .
            \block_course_rating\renderer::get_svg($userAnswer * 100 / 5, $courseid, $item->userid, $key) . '</div>';
        $answer .= '<br/>';
    }

    $line[] = $answer;
    $line[] = $item->feedback;

    if (is_siteadmin()) {
        $fio = html_writer::link(new moodle_url('/user/view.php', array('id' => $item->userid)), $item->fio);

        array_unshift($line, $fio);

        $buttons = html_writer::link(
            new moodle_url('/blocks/course_rating/delete_vote.php', array(
                'timecreated' => $item->timecreated,
                'courseid' => $courseid,
                'templateid' => $templateid,
                'usercreated' => $item->userid,
                'returnurl' => $baseurl,
            )),
            $OUTPUT->pix_icon('t/delete', get_string('delete')),
            array(
                'title' => get_string('delete'),
                'onClick' => 'javascript:return confirm(\'Вы точно хотите удалить анкету голосования?\');'
            )
        );

        array_push($line, $buttons);
    }

    $data[] = $line;
}

if (\count($data) > 0) {
    $table->data = $data;
} else {
    $empty = new html_table_cell('Нет данных');
    $empty->colspan = \count($table->head);

    $table->data = [new html_table_row([$empty])];
}

echo $OUTPUT->paging_bar($answers['total'], $page, 25, $baseurl);

echo html_writer::table($table);

echo $OUTPUT->paging_bar($answers['total'], $page, 25, $baseurl);

echo $OUTPUT->footer();






