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
require_once($CFG->libdir . '/tablelib.php');

$page = optional_param('page', 0, PARAM_INT);

require_login();

$context = context_system::instance();
$PAGE->set_context($context);
$PAGE->set_pagelayout('admin');

require_capability('block/course_rating:manage', $context);

$params['page'] = $page;

$baseurl = new moodle_url('/blocks/course_rating/manage.php', $params);
$PAGE->set_url($baseurl);

$title = get_string('manage', 'block_course_rating');
$PAGE->set_heading($title);
$PAGE->set_title($title);

$managefeeds = new moodle_url('/blocks/course_rating/manage.php');
$PAGE->navbar->add(get_string('blocks'));
$PAGE->navbar->add(get_string('pluginname', 'block_course_rating'));
$PAGE->navbar->add($title);

echo $OUTPUT->header();

$templates = block_course_rating_templates($page);

$table = new html_table();
$table->attributes['class'] = 'generaltable';

$table->head = array(
    get_string('manage:name', 'block_course_rating'),
    get_string('manage:is_use', 'block_course_rating'),
    get_string('actions', 'moodle')
);
$table->size = array('70%', '20%', '10%');
$table->align = array('left', 'center', 'center');

$data = array();

foreach ($templates['data'] as $template) {
    $line = array();

    $value = $template->name;
    $value .= '<ul>';
    foreach (preg_split('@#@', $template->data, -1, PREG_SPLIT_NO_EMPTY) as $item) {
        $value .= "<li>$item</li>";
    }
    $value .= '</ul>';

    $line[] = $value;

    $used = $template->is_used;
    $line[] = $used ? get_string('yes') : get_string('no');

    $buttons = "";
    $buttons .= html_writer::link(
        new moodle_url('/blocks/course_rating/edit.php', array('id' => $template->id)),
        $OUTPUT->pix_icon('t/edit', get_string('edit')),
        array('title' => get_string('edit'))
    );

    if (!$used) {
        $buttons .= html_writer::link(
            new moodle_url('/blocks/course_rating/edit.php', array('id' => $template->id, 'delete' => 1)),
            $OUTPUT->pix_icon('t/delete', get_string('delete')),
            array(
                'title' => get_string('delete'),
                'onClick' => 'javascript:return confirm(\'Вы точно хотите удалить шаблон?\');'
            )
        );
    }

    $line[] = $buttons;
    $data[] = $line;
}

if (\count($data) > 0) {
    $table->data = $data;
} else {
    $empty = new html_table_cell('Нет данных');
    $empty->colspan = \count($table->head);

    $table->data = [new html_table_row([$empty])];
}

echo $OUTPUT->heading($title, 3, 'main');

echo $OUTPUT->paging_bar($templates['total'], $page, 25, $baseurl);

echo html_writer::table($table);

echo $OUTPUT->paging_bar($templates['total'], $page, 25, $baseurl);

echo $OUTPUT->container_start('buttons');
echo $OUTPUT->single_button(new moodle_url('edit.php'), get_string('manage:add', 'block_course_rating'));
echo $OUTPUT->container_end();

echo $OUTPUT->footer();
