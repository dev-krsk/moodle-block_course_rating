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
require_once(__DIR__ . '/classes/renderer.php');

$courseid = required_param('courseid', PARAM_INT);
try {
    $course = get_course($courseid);
    $context = context_course::instance($course->id);

    require_login($course);

    $svg = \block_course_rating\renderer::text_for_course($course);
} catch (Exception $e) {
    $svg = <<<HTML
<svg viewBox="0 0 80 20" version="1.1"></svg>
HTML;
}

header('Content-Type: image/svg+xml');
echo $svg;

