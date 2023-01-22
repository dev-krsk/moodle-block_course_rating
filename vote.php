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
$returnurl = required_param('returnurl', PARAM_URL);


$course = get_course($courseid);
$context = context_course::instance($course->id);

require_login($course);

if (!block_course_rating_is_student()) {
    redirect($returnurl, 'Вы не зачислены как студент на данный курс!', 5, \core\output\notification::NOTIFY_ERROR);
}

$baseurl = new moodle_url('/blocks/course_rating/vote.php');
$PAGE->set_url($baseurl);

$PAGE->set_pagelayout('course');

$title = get_string('vote', 'block_course_rating');
$PAGE->set_heading($title);
$PAGE->set_title($title);

$PAGE->navbar->add($title);

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('vote:heading', 'block_course_rating'), 3);

echo <<<HTML
<style>
  .course-content input[type=radio] {
    display: none;
  }
  
  .course-content label {
    transition: all 0.2s ease;
    fill: #333;
  }
  
  .course-content label.hover {
    fill: #d48a03 !important;
  }
  
  .course-content label.selected {
    fill: orange;
  }
</style>
<script>
$(document).ready(function() {  
  $('.course-content').on('change', 'input[type=radio]', function (e) {
    let context = $(this);
    
    context.closest('fieldset').find('label').removeClass('selected');
    context.closest('fieldset').find('label').each(function() {
      let label = $(this); 
      
      label.addClass('selected');
      
      if (label.find('input').is(":checked")) {
        return false;  
      }
    });
  });
  
  $('.course-content label').hover(function (e) {          
    let context = $(this);
    context.closest('fieldset').find('label').removeClass('hover');
    context.addClass('hover');
    
    context.closest('fieldset').find('label').each(function() {
      let label = $(this); 
      
      label.addClass('hover');
      
      if (context.find('input').val() === label.find('input').val()) {
        return false;  
      }
    });
  }, function (e) {   
    let context = $(this);
    context.closest('fieldset').find('label').removeClass('hover');
  });
});
</script>
HTML;


echo html_writer::start_div('course-content');

$mform = new block_course_rating_vote_form(null, [
    'templateid' => $templateid,
    'courseid' => $courseid,
    'returnurl' => $returnurl,
]);

if ($mform->is_cancelled()) {
    redirect($returnurl);

} else if ($data = $mform->get_data()) {
    $answers = [];

    if (!empty($data->feedback)) {
        $feedback = new stdClass();
        $feedback->courseid = $COURSE->id;
        $feedback->usercreated = $USER->id;
        $feedback->timecreated = time();
        $feedback->feedback = $data->feedback;

        $DB->insert_record('course_rating_feedback', $feedback);
    }

    foreach (block_course_rating_get_questions($templateid) as $key => $question) {
        $answer = new stdClass();
        $answer->course_rating_templates_id = $templateid;
        $answer->courseid = $COURSE->id;
        $answer->usercreated = $USER->id;
        $answer->timecreated = time();
        $answer->order_question = $key;
        $answer->user_answer = $data->{"question$key"};

        $answers[] = $answer;
    }

    $DB->insert_records('course_rating_answers', $answers);

    redirect($returnurl);
}

$mform->display();


echo html_writer::end_div();

echo $OUTPUT->footer();






