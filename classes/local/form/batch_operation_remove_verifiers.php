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

class batch_operation_remove_verifiers extends \moodleform {

    public function definition() {
        $mform = $this->_form;
        $customdata = $this->_customdata;

        $mform->addElement('header', 'warning', get_string('batchoperationconfirmremoveallocatedverifiers', 'assignfeedback_verified'));
        $mform->addElement('static', 'userlist', get_string('selectedusers', 'assignfeedback_verified'), $customdata['userlist']);

        $mform->addElement('hidden', 'id', $customdata['cmid']);
        $mform->setType('id', PARAM_INT);
        $mform->addElement('hidden', 'assignid', $customdata['assignid']);
        $mform->setType('assignid', PARAM_INT);
        $mform->setDefault('assignid', $customdata['assignid']);

        $mform->addElement('hidden', 'operation', 'plugingradingbatchoperation_verified_removeallocatedverifiers');
        $mform->setType('operation', PARAM_ALPHAEXT);
        $mform->addElement('hidden', 'action', 'viewpluginpage');
        $mform->setType('action', PARAM_ALPHA);
        $mform->addElement('hidden', 'pluginaction', 'removeallocatedverifiers');
        $mform->setType('pluginaction', PARAM_ALPHA);
        $mform->addElement('hidden', 'plugin', 'verified');
        $mform->setType('plugin', PARAM_PLUGIN);
        $mform->addElement('hidden', 'pluginsubtype', 'assignfeedback');
        $mform->setType('pluginsubtype', PARAM_PLUGIN);
        $mform->addElement('hidden', 'selectedusers', implode(',', $customdata['users']));
        $mform->setType('selectedusers', PARAM_SEQUENCE);
        $this->add_action_buttons(true, get_string('batchoperationremoveallocatedverifiers', 'assignfeedback_verified'));
    }

}
