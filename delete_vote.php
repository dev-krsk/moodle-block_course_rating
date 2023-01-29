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

$courseid = required_param('courseid', PARAM_INT);
$templateid = required_param('templateid', PARAM_INT);
$timecreated = required_param('timecreated', PARAM_INT);
$usercreated = required_param('usercreated', PARAM_INT);

$returnurl = optional_param('returnurl', new moodle_url('/blocks/course_rating/manage.php'), PARAM_URL);

require_login();

require_admin();

$DB->delete_records('course_rating_feedback', [
    'courseid' => $courseid,
    'timecreated' => $timecreated,
    'usercreated' => $usercreated,
]);

$DB->delete_records('course_rating_answers', [
    'courseid' => $courseid,
    'course_rating_templates_id' => $templateid,
    'timecreated' => $timecreated,
    'usercreated' => $usercreated,
]);

redirect($returnurl);
