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
 * @package    block_course_rating
 * @copyright  2022 Yuriy Yurinskiy
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once(__DIR__ . '/lib.php');

class block_course_rating extends block_base
{

    function init()
    {
        $this->title = get_string('pluginname', 'block_course_rating');
    }

    function get_content()
    {
        global $USER, $COURSE, $PAGE;

        if ($this->content !== null) {
            return $this->content;
        }

        $this->content = new stdClass;

        $this->content->text = \block_course_rating\renderer::text_for_block($COURSE);
        $this->content->footer = \block_course_rating\renderer::footer_for_block($USER, $COURSE, $PAGE->url);

        return $this->content;
    }

    // my moodle can only have SITEID and it's redundant here, so take it away
    public function applicable_formats()
    {
        return array('all' => false,
            'site' => false,
            'site-index' => false,
            'course-view' => true,
            'course-view-social' => false,
            'mod' => false,
            'mod-quiz' => false);
    }

    public function instance_allow_multiple()
    {
        return false;
    }

    function has_config()
    {
        return true;
    }

    public function cron()
    {
        return false;
    }

    function instance_create()
    {
        global $DB;

        $field = $DB->get_record('customfield_field', ['shortname' => 'course_rating']);

        if ($field == null) {
            return parent::instance_create();
        }

        list($context, $course, $cm) = get_context_info_array($this->instance->parentcontextid);

        $record = $DB->get_record('customfield_data', [
            'instanceid' => $course->id,
            'fieldid' => $field->id
        ]);

        $title = get_string('pluginname', 'block_course_rating');

        if ($record != null) {

            $record->value = <<<HTML
<img src="/blocks/course_rating/view.php?courseid={$course->id}" alt="$title" width="80" />
HTML;

            $DB->update_record('customfield_data', $record);

            return parent::instance_create();
        }

        $record = new stdClass();
        $record->fieldid = $field->id;
        $record->instanceid = $course->id;
        $record->contextid = $context->id;
        $record->value = <<<HTML
<img src="/blocks/course_rating/view.php?courseid={$course->id}" alt="$title" width="80" />
HTML;
        $record->valueformat = 1;
        $record->timecreated = time();
        $record->timemodified = time();

        $record->id = $DB->insert_record('customfield_data', $record);

        return parent::instance_create();
    }

    function instance_delete()
    {
        global $DB;

        $field = $DB->get_record('customfield_field', ['shortname' => 'course_rating']);

        if ($field == null) {
            return parent::instance_delete();
        }

        list($context, $course, $cm) = get_context_info_array($this->instance->parentcontextid);

        $record = $DB->get_record('customfield_data', [
            'instanceid' => $course->id,
            'fieldid' => $field->id
        ]);

        if ($record == null) {
            return parent::instance_delete();
        }

        $DB->delete_records('customfield_data', [
            'instanceid' => $course->id,
            'fieldid' => $field->id
        ]);

        return parent::instance_delete();
    }
}
