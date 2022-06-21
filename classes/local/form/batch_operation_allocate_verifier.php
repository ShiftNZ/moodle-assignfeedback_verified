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

namespace assignfeedback_verified\local\form;

class batch_operation_allocate_verifier extends \moodleform {

    public function definition() {
        $mform = $this->_form;
        $customdata = $this->_customdata;

        $mform->addElement('header', 'warning', get_string('batchoperationconfirmallocateverifier', 'assignfeedback_verified'));
        $mform->addElement('static', 'userlist', get_string('selectedusers', 'assignfeedback_verified'), $customdata['userlist']);

        $mform->addElement('text', 'customtext', get_string('customtext', 'assignfeedback_verified'));
        $mform->setType('customtext', PARAM_TEXT);
        $mform->addHelpButton('customtext', 'customtext', 'assignfeedback_verified');
        $mform->addRule('customtext', get_string('maximumchars', '', 100), 'maxlength', 100, 'client');

        $attributes = [
            'multiple' => false,
            'ajax' => 'assignfeedback_verified/form_verifier_selector'
        ];
        $mform->addElement('autocomplete', 'verifierid', get_string('verifier', 'assignfeedback_verified'), [], $attributes);
        $mform->addRule('verifierid', null, 'required', null, 'client');

        $mform->addElement('hidden', 'id', $customdata['cmid']);
        $mform->setType('id', PARAM_INT);
        $mform->addElement('hidden', 'assignid', $customdata['assignid']);
        $mform->setType('assignid', PARAM_INT);
        $mform->setDefault('assignid', $customdata['assignid']);

        $mform->addElement('hidden', 'operation', 'plugingradingbatchoperation_verified_allocateverifier');
        $mform->setType('operation', PARAM_ALPHAEXT);
        $mform->addElement('hidden', 'action', 'viewpluginpage');
        $mform->setType('action', PARAM_ALPHA);
        $mform->addElement('hidden', 'pluginaction', 'allocateverifier');
        $mform->setType('pluginaction', PARAM_ALPHA);
        $mform->addElement('hidden', 'plugin', 'verified');
        $mform->setType('plugin', PARAM_PLUGIN);
        $mform->addElement('hidden', 'pluginsubtype', 'assignfeedback');
        $mform->setType('pluginsubtype', PARAM_PLUGIN);
        $mform->addElement('hidden', 'selectedusers', implode(',', $customdata['users']));
        $mform->setType('selectedusers', PARAM_SEQUENCE);
        $this->add_action_buttons(true, get_string('batchoperationallocateverifier', 'assignfeedback_verified'));
    }

}
