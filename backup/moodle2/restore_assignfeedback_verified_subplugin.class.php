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
 * This file contains the restore class for the verified feedback sub-plugin.
 *
 * @package     assignfeedback_verified
 * @copyright   2022 Skills Consulting Group
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use assignfeedback_verified\local\persistent\allocated_user;
use assignfeedback_verified\local\persistent\verification;

defined('MOODLE_INTERNAL') || die();

class restore_assignfeedback_verified_subplugin extends restore_subplugin {

    /**
     * Returns the paths to be handled by the sub-plugin at assignment level.
     *
     * @return array
     */
    protected function define_grade_subplugin_structure() {
        return [new restore_path_element('verification', $this->get_pathfor('/verifications/verification'))];
    }

    /**
     * Create a verification feedback record.
     *
     * @param $data
     * @return void
     * @throws dml_exception
     */
    public function process_verification($data) {
        global $DB;

        $data = (object) $data;
        $data->assignid = $this->get_new_parentid('assign');
        $oldid = $data->id;

        // The mapping is set in the restore for the core assign activity when a grade node is processed.
        $data->gradeid = $this->get_mappingid('grade', $data->gradeid);

        $DB->insert_record(verification::TABLE, $data);

        $this->add_related_files(
            'assignfeedback_verified',
            'feedback',
            'id',
            null,
            $oldid
        );

    }

    /**
     * Work around to build allocated verifiers based data in verification table.
     *
     * @return void
     * @throws \core\invalid_persistent_exception
     * @throws coding_exception
     * @throws dml_exception
     */
    protected function after_execute_grade() {
        global $DB;

        $sql = "SELECT DISTINCT afv.assignid, ag.userid, afv.verifierid, afv.customtext 
                  FROM {assign_grades} ag
                  JOIN {" . verification::TABLE . "} afv ON afv.gradeid = ag.id
                 WHERE afv.assignid = :assignid";

        $rs = $DB->get_recordset_sql($sql, ['assignid' => $this->get_new_parentid('assign')]);
        foreach ($rs as $record) {
            $allocatedverifier = new allocated_user();
            $allocatedverifier->set('assignid', $record->assignid);
            $allocatedverifier->set('userid', $record->userid);
            $allocatedverifier->set('verifierid', $record->verifierid);
            if (trim($record->customtext) !== '') {
                $allocatedverifier->set('customtext', $record->customtext);
            }
            $allocatedverifier->create();
        }
        $rs->close();
    }

}
