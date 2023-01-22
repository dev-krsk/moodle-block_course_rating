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
 * Edit form for grade scales
 *
 * @package   core_grades
 * @copyright 2007 Petr Skoda
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.');    ///  It must be included from a Moodle page
}

require_once $CFG->libdir . '/formslib.php';
require_once __DIR__ . '/lib.php';

class block_course_rating_form extends moodleform
{
    function definition()
    {
        global $CFG;
        $mform =& $this->_form;

        // visible elements
        $mform->addElement('header', 'general', get_string('scale'));

        $mform->addElement('text', 'name', get_string('name'));
        $mform->addRule('name', get_string('required'), 'required');
        $mform->setType('name', PARAM_TEXT);

        $mform->addElement('static', 'used', get_string('used'), $this->is_used() ? get_string('yes') : get_string('no'));

        $params = array('cols' => 100, 'rows' => 5);

        if ($this->is_used()) {
            $params['disabled'] = 'disabled';
        }

        $mform->addElement('textarea', 'data', get_string('manage:template:data', 'block_course_rating'), $params);
        $mform->setType('data', PARAM_TEXT);

        if (!$this->is_used()) {
            $mform->addRule('data', get_string('required'), 'required');
        }

        // hidden params
        $mform->addElement('hidden', 'id', 0);
        $mform->setType('id', PARAM_INT);

//-------------------------------------------------------------------------------
        // buttons
        $this->add_action_buttons();
    }

    function is_used()
    {
        return $this->_customdata;
    }
}

class block_course_rating_vote_form extends moodleform
{
    protected $template;

    function definition()
    {
        $this->template = $this->_customdata;

        $mform = $this->_form;

        $star = \block_course_rating\renderer::get_star();

        foreach (block_course_rating_get_questions($this->template['templateid']) as $key => $question) {
            $radioarray = array();
            $radioarray[] = $mform->createElement('radio', 'question' . $key, '', $star, 1, []);
            $radioarray[] = $mform->createElement('radio', 'question' . $key, '', $star, 2, []);
            $radioarray[] = $mform->createElement('radio', 'question' . $key, '', $star, 3, []);
            $radioarray[] = $mform->createElement('radio', 'question' . $key, '', $star, 4, []);
            $radioarray[] = $mform->createElement('radio', 'question' . $key, '', $star, 5, []);
            $mform->addGroup($radioarray, 'question' . $key, $question, array(' '), false);
            $mform->addRule('question' . $key, get_string('required'), 'required');
        }

        $mform->addElement('textarea', 'feedback', get_string('vote:feedback', 'block_course_rating'),  array('cols' => 100, 'rows' => 5));
        $mform->setType('feedback', PARAM_TEXT);

        $mform->addElement('hidden', 'templateid', $this->template['templateid']);
        $mform->setType('templateid', PARAM_INT);

        $mform->addElement('hidden', 'courseid', $this->template['courseid']);
        $mform->setType('courseid', PARAM_INT);

        $mform->addElement('hidden', 'returnurl', $this->template['returnurl']);
        $mform->setType('returnurl', PARAM_URL);

//-------------------------------------------------------------------------------
        // buttons
        $this->add_action_buttons();
    }
}


