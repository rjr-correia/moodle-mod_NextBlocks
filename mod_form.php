<?php
// This file is part of Moodle - https://moodle.org/
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
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

/**
 * The main mod_nextblocks configuration form.
 * @package     mod_nextblocks
 * @copyright   2025 Rui Correia<rjr.correia@campus.fct.unl.pt>
 * @copyright   based on work by 2024 Duarte Pereira<dg.pereira@campus.fct.unl.pt>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;

require_once($CFG->dirroot . '/course/moodleform_mod.php');

/**
 * Module instance settings form.
 *
 * @package     mod_nextblocks
 * @copyright   2023 Duarte Pereira<dg.pereira@campus.fct.unl.pt>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_nextblocks_mod_form extends moodleform_mod {

    /**
     * Defines forms elements
     *
     * @throws coding_exception
     */
    public function definition() {
        global $CFG;

        $mform = $this->_form;

        // Adding the "general" fieldset, where all the common settings are shown.
        $mform->addElement('header', 'general', get_string('general', 'form'));

        // Adding the standard "name" field.
        $mform->addElement('text', 'name', get_string('nextblocksname', 'mod_nextblocks'), ['size' => '64']);

        if (!empty($CFG->formatstringstriptags)) {
            $mform->setType('name', PARAM_TEXT);
        } else {
            $mform->setType('name', PARAM_CLEANHTML);
        }

        $mform->addRule('name', null, 'required', null, 'client');
        $mform->addRule('name', get_string('maximumchars', '', 255), 'maxlength', 255, 'client');
        $mform->addHelpButton('name', 'nextblocksname', 'mod_nextblocks');

        // Adding the standard "intro" and "introformat" fields.
        if ($CFG->branch >= 29) {
            $this->standard_intro_elements();

        } else {
            $this->add_intro_editor();
        }

        // Adding the rest of mod_nextblocks settings, spreading all them into this fieldset
        // ... or adding more fieldsets ('header' elements) if needed for better logic.

        // ...<<------------------------------------------ Tests tab ------------------------------------------>>//

        $mform->addElement('header', 'tests', get_string('nextblockscreatetests', 'mod_nextblocks'));

        $mform->addElement(
            'filemanager',
            'attachments',
            get_string('testsfilesubmit', 'mod_nextblocks'),
            null,
            [
                'subdirs' => 0,
                'areamaxbytes' => 10485760,
                'maxfiles' => 1,
                'accepted_types' => ['txt'],
                'return_types' => FILE_INTERNAL | FILE_EXTERNAL,
            ]
        );
        $mform->addHelpButton('attachments', 'testsfile', 'mod_nextblocks');
        $mform->setType('testsfile', PARAM_FILE);

        // ...<<------------------------------------------ Custom Blocks tab ------------------------------------------>>//

        /**
         * Renders the custom blocks
         * 
         * @param $mform object
         * @return void
         */
        function addcustomblockinputs($mform) {
            $mform->addElement('textarea', 'blockdefinition', get_string("blockdefinition", "mod_nextblocks"),
                'wrap="virtual" rows="8" cols="80"');
            $mform->addHelpButton('blockdefinition', 'blockdefinition', 'mod_nextblocks');
            $mform->setType('blockdefinition', PARAM_TEXT);
            $mform->addElement('textarea', 'blockgenerator', get_string("blockgenerator", "mod_nextblocks"),
                'wrap="virtual" rows="8" cols="80"');
            $mform->addHelpButton('blockgenerator', 'blockgenerator', 'mod_nextblocks');
            $mform->setType('blockgenerator', PARAM_TEXT);
            $mform->addElement('textarea', 'blockpythongenerator', get_string("blockgeneratorPython", "mod_nextblocks"),
                'wrap="virtual" rows="8" cols="80"');
            $mform->addHelpButton('blockpythongenerator', 'blockgeneratorPython', 'mod_nextblocks');
            $mform->setType('blockpythongenerator', PARAM_TEXT);
        }

        $mform->addElement('header', 'customblocks', get_string('nextblockscreatecustomblocks', 'mod_nextblocks'));
        $mform->addElement('html', get_string('customblockstext', 'mod_nextblocks'));

        $repeatarray = [
            $mform->createElement('textarea', 'definition', get_string('blockdefinition', 'mod_nextblocks'), 
                'wrap="virtual" rows="8" cols="80"'),
            $mform->createElement('textarea', 'generator', get_string('blockgenerator', 'mod_nextblocks'), 
                'wrap="virtual" rows="8" cols="80"'),
            $mform->createElement('textarea', 'pythongenerator', get_string('blockpythongenerator', 'mod_nextblocks'), 
                'wrap="virtual" rows="8" cols="80"'),
            $mform->createElement('hidden', 'optionid', 0),
            $mform->createElement('submit', 'delete', get_string('deletestr', 'mod_nextblocks'), [], false),
        ];

        $repeatoptions = [
            'definition' => [
                'type' => PARAM_TEXT,

            ],
            'generator' => [
                'type' => PARAM_TEXT,
            ],
            'optionid' => [
                'type' => PARAM_INT,
            ],
        ];

        $this->repeat_elements(
            $repeatarray,
            1,
            $repeatoptions,
            'option_repeats',
            'option_add_fields',
            1,
            null,
            true,
            'delete',
        );

        // ...<<------------------------------------------ Block limits tab ------------------------------------------>>//

        global $DB;

        $mform->addElement('header', 'hdr_blocklimits', 'Block Limits');

        $builtincategories = [
            'Logic' => [
                'controls_if'      => 'If / Else',
                'logic_compare'    => 'Number Compare',
                'logic_negate'     => 'Not',
                'logic_operation'  => 'AND/OR',
                'logic_boolean'    => 'True/False',
                'logic_null'       => 'Null',
                'logic_ternary'    => 'If {1} return {2} else return {3}',
            ],
            'Loops' => [
                'controls_repeat_ext'    => 'Repeat {1} Times',
                'controls_whileUntil'    => 'While / Until',
                'controls_for'           => 'Count from {1} to {2} step {3}',
                'controls_forEach'       => 'For Each',
                'controls_flow_statements' => 'Continue / Break',
            ],
            'Math' => [
                'math_number'        => 'Number',
                'math_arithmetic'    => '2 argument arithmetic operations',
                'math_single'        => '1 argument arithmetic operations',
                'math_trig'          => 'Trigonometric functions',
                'math_constant'      => 'Constants',
                'math_number_property' => 'Number Properties',
                'math_round'         => 'Round',
                'math_on_list'       => 'Math on List',
                'math_modulo'        => 'Remainder',
                'math_constrain'     => 'Constrain',
                'math_random_int'    => 'Random Integer',
                'math_random_float'  => 'Random Fractional Number',
                'math_atan2'         => 'Arctangent of point',
                'text_to_number'     => 'Convert text to number',

            ],
            'Text' => [
                'text'               => 'Text',
                'text_multiline'     => 'Multiline Text',
                'text_join'          => 'Join Text',
                'text_append'        => 'Append Text',
                'text_length'        => 'Length',
                'text_isEmpty'       => 'Is Empty',
                'text_indexOf'       => 'Index Of',
                'text_charAt'        => 'Char At',
                'text_getSubstring'  => 'Get Substring',
                'text_changeCase'    => 'Change Case',
                'text_trim'          => 'Trim',
                'text_count'         => 'Count',
                'text_replace'       => 'Replace',
                'text_reverse'       => 'Reverse',
                'text_print'         => 'Print',
                'text_ask'           => 'Input',
            ],
            'Lists' => [
                'lists_create_with'    => 'Create List from enumeration',
                'lists_repeat'         => 'Create List by repetition',
                'lists_length'         => 'Length of List',
                'lists_isEmpty'        => 'Is List Empty',
                'lists_indexOf'        => 'Index Of Item',
                'lists_getIndex'       => 'Get Item at Index',
                'lists_setIndex'       => 'Set Item at Index',
                'lists_getSublist'     => 'Get Sublist',
                'lists_split'          => 'Split Text',
                'lists_sort'           => 'Sort List',
                'lists_reverse'        => 'Reverse List',

            ],
            'Variables' => [
                'variables_get'    => 'Get Variable',
                'variables_set'    => 'Set Variable',
            ],
            'Functions' => [
                'procedures_defnoreturn' => 'Define Function',
                'procedures_defreturn'   => 'Define Function w/ Return',
                'procedures_callnoreturn' => 'Call Function',
                'procedures_callreturn'  => 'Call Function w/ Return',
                'procedures_ifreturn'    => 'If Return',
            ],
        ];

        foreach ($builtincategories as $catname => $blocks) {
            $mform->addElement('html',
                '<details style="margin-top:1em;"><summary><strong>'
                . $catname .
                '</strong></summary>'
            );

            foreach ($blocks as $type => $label) {
                $field = 'limit_' . $type;
                $mform->addElement('text', $field, $label, ['size' => 4]);
                $mform->setType($field, PARAM_INT);
                $mform->setDefault($field, 10);
            }
            $mform->addElement('html', '</details>');
        }

        $customrecs = $DB->get_records('nextblocks_customblocks',
            ['nextblocksid' => $this->current->instance]
        );
        if ($customrecs) {
            $mform->addElement('html',
                '<details style="margin-top:1em;"><summary><strong>'
                . 'Custom Blocks' .
                '</strong></summary>'
            );
            foreach ($customrecs as $rec) {
                $def = json_decode($rec->blockdefinition, true);
                if (!empty($def['type'])) {
                    $label = !empty($def['message0']) ? $def['message0'] : $def['type'];
                    $field = 'limit_' . $def['type'];
                    $mform->addElement('text', $field, $label, ['size' => 4]);
                    $mform->setType($field, PARAM_INT);
                    $mform->setDefault($field, 0);
                }
            }
            $mform->addElement('html', '</details>');
        }

        // ...<<------------------------------------------ Submissions tab ------------------------------------------>>//

        $mform->addElement(
            'header', 'submissions', get_string('nextblockscreatesubmissions', 'mod_nextblocks')
        );

        $mform->addElement(
            'advcheckbox', 'multiplesubmissions', get_string('multiplesubmissions', 'mod_nextblocks'),
        );
        $mform->addElement('text', 'maxsubmissions', get_string('howmanysubmissions', 'mod_nextblocks'));
        $mform->setType('maxsubmissions', PARAM_INT);
        $mform->hideIf('maxsubmissions', 'multiplesubmissions', 'neq', 1);

        // ...<<------------------------------------------ Grading tab ------------------------------------------>>//

        $this->standard_grading_coursemodule_elements();

        $this->standard_coursemodule_elements();
        $this->apply_admin_defaults();

        // Add standard buttons.
        $this->add_action_buttons();
    }

    /**
     * Checks whether file structure is correct
     *
     * @param $data
     * @param $files
     * @return array errors
     */
    public function validation($data, $files): array {
        global $USER;
        $errors = parent::validation($data, $files);
        $usercontext = context_user::instance($USER->id);
        $fs = get_file_storage();
        try {
            $files = $fs->get_area_files($usercontext->id, 'user', 'draft', $data['attachments'], 'id', false);
        } catch (coding_exception $e) {
            // If no files just continue.
        }
        if (count($files) === 1) {
            $file = reset($files);
            $filestring = $file->get_content();
            if (file_structure_is_valid($filestring)) {
                $errors['attachments'] = get_string('invalidfilestructure', 'mod_nextblocks');
            }
        }

        return $errors;
    }
}
