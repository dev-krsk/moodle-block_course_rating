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

defined('MOODLE_INTERNAL') || die();

function block_course_rating_templates($page = 0, $perpage = 25)
{
    global $DB;

    $fields = "SELECT t.id, t.name, t.data";
    $fields .= ", (SELECT count(*) FROM {course_rating_answers} a WHERE a.course_rating_templates_id = t.id) is_used";
    $countfields = "SELECT COUNT(*)";
    $sql = " FROM {course_rating_templates} t";

    $total = $DB->count_records_sql($countfields . $sql);

    $order = " ORDER BY t.timecreated";
    $data = $DB->get_records_sql($fields . $sql . $order, [], $page * $perpage, $perpage);

    return array('data' => $data, 'total' => $total);
}

function block_course_rating_all_templates()
{
    return block_course_rating_templates(0, 0)['data'];
}

function block_course_rating_is_used($id)
{
    global $DB;

    $select = "SELECT COUNT(*)";
    $sql = " FROM {course_rating_answers} t";
    $where = " WHERE t.course_rating_templates_id = :id";

    return $DB->count_records_sql($select . $sql . $where, ['id' => $id]);
}

function block_course_rating_is_student()
{
    global $USER, $PAGE;

    list($context, $course, $cm) = get_context_info_array($PAGE->context->id);

    foreach (get_user_roles($context, $USER->id) as $role) {
        if ($role->shortname == 'student') {
            return true;
        }
    }

    return false;
}

function block_course_rating_is_teacher()
{
    global $USER, $PAGE;

    list($context, $course, $cm) = get_context_info_array($PAGE->context->id);

    foreach (get_user_roles($context, $USER->id) as $role) {
        if ($role->shortname == 'student') {
            return true;
        }
    }

    return false;
}

function block_course_rating_get_questions($id)
{
    global $DB;

    $template = $DB->get_record('course_rating_templates', array('id' => $id), '*', MUST_EXIST);

    return preg_split('@#@', $template->data, -1, PREG_SPLIT_NO_EMPTY);
}

function block_course_rating_get_answers($courseid, $templateid)
{

    global $DB;

    $sql = <<<SQL
select min(a.id)                            as                             id,
       a.timecreated                        as                             timecreated,
       a.usercreated                        as                             userid,
       concat(u.lastname, ' ', u.firstname) as                             fio,
       group_concat(a.user_answer order by a.order_question separator '#') answers,
       f.feedback
from {course_rating_templates} t
         join {course_rating_answers} a on a.course_rating_templates_id = t.id
         left join {user} u on u.id = a.usercreated
         left join {course_rating_feedback} f
                   on f.courseid = a.courseid and f.usercreated = a.usercreated
where t.id = :templateid
  and a.courseid = :courseid

group by a.timecreated, a.usercreated, f.feedback
order by 2 desc
SQL;


    $records = $DB->get_records_sql($sql, ['courseid' => $courseid, 'templateid' => $templateid]);


    $data = [];
    foreach ($records as $record) {
        $line = [];
        $line[] = date_format_string($record->timecreated, '%d.%m.%Y');
        $line[] = date_format_string($record->timecreated, '%H:%M');
        $line[] = $record->fio;

        foreach (preg_split('@#@', $record->answers, -1, PREG_SPLIT_NO_EMPTY) as $answer) {
            $line[] = $answer;
        }

        $line[] = $record->feedback;

        $data[] = $line;
    }

    return $data;
}


function block_course_rating_get_answers_paging($courseid, $templateid, $page = 0, $offset = 25)
{

    global $DB;

    $sql = <<<SQL
select min(a.id)                            as                             id,
       a.timecreated                        as                             timecreated,
       a.usercreated                        as                             userid,
       concat(u.lastname, ' ', u.firstname) as                             fio,
       group_concat(a.user_answer order by a.order_question separator '#') answers,
       f.feedback
 from {course_rating_templates} t
         join {course_rating_answers} a on a.course_rating_templates_id = t.id
         left join {user} u on u.id = a.usercreated
         left join {course_rating_feedback} f
                   on f.courseid = a.courseid and f.usercreated = a.usercreated
where t.id = :templateid
  and a.courseid = :courseid

group by a.timecreated, a.usercreated, f.feedback
SQL;

    $sqlCount = <<<SQL
select count(*) from (
    $sql
) z
SQL;


    $orderby = ' order by 2 desc';

    $records = $DB->get_records_sql($sql . $orderby, ['courseid' => $courseid, 'templateid' => $templateid], $page, $offset);

    return array(
        'total' => $DB->get_record_sql($sqlCount, ['courseid' => $courseid, 'templateid' => $templateid]),
        'data' => $records
    );
}

function block_course_rating_get_rating($templateid, $id)
{
    global $DB;

    $sql = <<<SQL
select coalesce(avg(z.avg), 0) as avg, count(*) as cnt
from (
select t.usercreated, sum(t.user_answer) / count(*) as avg
from {course_rating_answers} t
where t.courseid = :id
  and t.course_rating_templates_id = :templateid
group by t.usercreated) z
SQL;

    $record = $DB->get_record_sql($sql, ['id' => $id, 'templateid' => $templateid]);

    $result = new stdClass();
    $result->value = round($record->avg, 2);
    $result->percent = round($record->avg / 5 * 100, 2);
    $result->count = $record->cnt;

    return $result;
}

function block_course_rating_render($courseid, $showText = true)
{
    $instance = block_course_rating_get_instance_block($courseid);
    $template = block_course_rating_get_template_block($instance);

    $rating = block_course_rating_get_rating($template, $courseid);
    $title = get_string('rating', 'block_course_rating', $rating);


    if ($showText) {
        $margin = 15;
    } else {
        $margin = 0;
    }

    $content = <<<HTML
<style>
.showblockicons .block_course_rating.block .header .title h2:before {
    content: '★';
    font-size: 22px;
}
.course-ratings-css {
  unicode-bidi: bidi-override;
  color: #c5c5c5;
  min-height: 32px;
  height: auto;
  width: 100%;
  max-width: 220px;
  margin: {$margin}px auto;
  position: relative;
  text-shadow: 0 1px 0 #a2a2a2;
} 

.course-ratings-css::before { 
  content: '★★★★★';
  opacity: .3;
}

.course-ratings-css::after {
  color: gold;
  content: '★★★★★';
  text-shadow: 0 1px 0 #ab5414;
  position: absolute;
  z-index: 1;
  display: block;
  left: 0;
  top:0;
  overflow: hidden;
  width: {$rating->percent}%;
}

.course-ratings-container {
    width: 100%;
    text-align: center;
}
</style>
<div class="course-ratings-container">
    <div class="course-ratings-css"></div>
HTML;

    if ($showText) {
        $content .= <<<HTML
    <p>$title</p>
HTML;
    }

    $content .= <<<HTML
</div>
<script>
const myObserver = new ResizeObserver(entries => {
 // this will get called whenever div dimension changes
  entries.forEach(entry => {
    console.log('width', entry.contentRect.width);
    console.log('height', entry.contentRect.height);
    console.log(entry);
    entry.target.style.fontSize = entry.contentRect.width / 4.2 + 'px';
    entry.target.style.lineHeight = entry.contentRect.width / 4.2 + 'px';
  });
});

const someEl = document.querySelector('.course-ratings-css');

// start listening to changes
myObserver.observe(someEl);

</script>
HTML;

    return $content;
}

function block_course_rating_render_btn_vote($userid, $courseid, $returnurl)
{
    global $OUTPUT;

    $is_student = block_course_rating_is_student();

    $is_voted = block_course_rating_is_voted($userid, $courseid);

    if (!$is_student || $is_voted) {
        return '';
    }

    $instance = block_course_rating_get_instance_block($courseid);
    $template = block_course_rating_get_template_block($instance);

    return html_writer::tag(
        'div',
        $OUTPUT->single_button(new moodle_url('/blocks/course_rating/vote.php', [
            'templateid' => $template,
            'courseid' => $courseid,
            'returnurl' => $returnurl,
        ]), 'Голосовать'),
        ['style' => 'text-align: center']
    );
}

function block_course_rating_render_btn_download($courseid, $returnurl)
{
    global $OUTPUT;

    $context = context_course::instance($courseid);

    if (!has_capability('block/course_rating:download', $context)) {
        return '';
    }

    $instance = block_course_rating_get_instance_block($courseid);
    $template = block_course_rating_get_template_block($instance);

    return html_writer::tag(
        'div',
        $OUTPUT->single_button(new moodle_url('/blocks/course_rating/export.php', [
            'templateid' => $template,
            'courseid' => $courseid,
            'returnurl' => $returnurl,
        ]), 'Скачать результаты'),
        ['style' => 'text-align: center']
    );
}

function block_course_rating_render_btn_view_votes($courseid)
{
    global $OUTPUT;

    $instance = block_course_rating_get_instance_block($courseid);
    $template = block_course_rating_get_template_block($instance);

    return html_writer::tag(
        'div',
        html_writer::link(new moodle_url('/blocks/course_rating/votes.php', [
            'templateid' => $template,
            'courseid' => $courseid,
        ]), 'Просмотр ответов', ['class' => 'btn btn-secondary']),
        ['style' => 'text-align: center']
    );
}

function block_course_rating_is_voted($userid, $courseid, $template = null)
{
    global $DB;

    if ($template == null) {
        $instance = block_course_rating_get_instance_block($courseid);
        $template = block_course_rating_get_template_block($instance);
    }

    $select = "SELECT COUNT(*)";
    $sql = " FROM {course_rating_answers} t";
    $where = " WHERE t.usercreated = :userid and t.courseid = :courseid and t.course_rating_templates_id = :template";

    return $DB->count_records_sql($select . $sql . $where, ['template' => $template, 'userid' => $userid, 'courseid' => $courseid]);
}

function block_course_rating_get_instance_block($courseid)
{
    global $DB;

    $context = context_course::instance($courseid);

    return $DB->get_record('block_instances', ['parentcontextid' => $context->id, 'blockname' => 'course_rating'], '*', IGNORE_MISSING);
}

function block_course_rating_get_template_block($instance)
{
    try {
        $config = \unserialize(\base64_decode($instance->configdata));
    } catch (Throwable $e) {
        $config = new stdClass();
    }

    return $config->template ?? 1;
}