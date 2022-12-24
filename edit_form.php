<?php

if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.');    ///  It must be included from a Moodle page
}

class block_course_rating_edit_form extends block_edit_form
{
    protected function specific_definition($mform)
    {
        // Start block specific section in config form.
        $mform->addElement('header', 'configheader', get_string('blocksettings', 'block'));

        $options = [];

        foreach (block_course_rating_all_templates() as $template) {
            $options[$template->id] = $template->name;
        }

        $mform->addElement('select', 'config_template', 'Выберите шаблон', $options);

    }
}