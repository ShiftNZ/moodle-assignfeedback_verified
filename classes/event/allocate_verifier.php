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

namespace assignfeedback_verified\event;

class allocate_verifier extends \core\event\base {

    /**
     * Init method.
     *
     * @return void
     */
    protected function init() {
        $this->data['crud'] = 'c';
        $this->data['edulevel'] = self::LEVEL_OTHER;
        $this->data['objecttable'] = 'assignfeedback_verified_au';
    }

    /**
    * Returns description of what happened.
    *
    * @return string
    */
    public function get_description() {
        return "The user with id '$this->userid' has allocated verifier with id '{$this->other['verifierid']}' to " .
            "'$this->relateduserid' for the assignment with course module id '$this->contextinstanceid'.";
    }

    /**
     * Return localised event name.
     * *
     * @return \lang_string|string
     * @throws \coding_exception
     */
    public static function get_name() {
        return get_string('eventallocateverifier', 'assignfeedback_verified');
    }

    public static function get_objectid_mapping() {
        return ['db' => 'assignfeedback_verified_au', 'restore' => 'assignfeedback_verified_au'];
    }

    /**
     * Custom validation.
     *
     * @throws \coding_exception
     * @return void
     */
    protected function validate_data() {
        parent::validate_data();

        if (!isset($this->relateduserid)) {
            throw new \coding_exception('The \'relateduserid\' must be set.');
        }

        if (!isset($this->other['assignid'])) {
            throw new \coding_exception('The \'forumid\' value must be set in other.');
        }

        if (!isset($this->other['verifierid'])) {
            throw new \coding_exception('The \'verifierid\' value must be set in other.');
        }

        if ($this->contextlevel != CONTEXT_MODULE) {
            throw new \coding_exception('Context level must be CONTEXT_MODULE.');
        }
    }

    public static function get_other_mapping() {
        $othermapped = [];
        $othermapped['assignid'] = ['db' => 'assign', 'restore' => 'assign'];
        $othermapped['verifierid'] = ['db' => 'user', 'restore' => 'user'];
        return $othermapped;
    }

}
