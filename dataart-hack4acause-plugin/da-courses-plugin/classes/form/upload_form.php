<?php
namespace local_da_courses\form;

defined('MOODLE_INTERNAL') || die();
require_once($CFG->libdir . '/formslib.php');

class upload_form extends \moodleform {
    public function definition() {
        global $CFG;
        $mform = $this->_form;

        $mform->addElement('header', 'hdr', get_string('form:heading', 'local_da_courses'));

        $mform->addElement('text', 'course_name', get_string('form:coursename', 'local_da_courses'));
        $mform->setType('course_name', PARAM_TEXT);
        $mform->addRule('course_name', null, 'required', null, 'client');

        // Accept any file type; no type validation in UI.
        $mform->addElement('filepicker', 'pdffile', get_string('form:file', 'local_da_courses'), null, [
            'maxbytes'       => $CFG->maxbytes,
            'accepted_types' => '*',          // <- allow anything
            'return_types'   => FILE_INTERNAL,
        ]);

        $this->add_action_buttons(true, get_string('form:submit', 'local_da_courses'));
    }

    // Remove the custom validation entirely so it never throws "Please upload a PDF file."
    // public function validation($data, $files) { return parent::validation($data, $files); }
}
