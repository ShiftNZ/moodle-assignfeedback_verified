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
 *  * This file contains the backup class for the verified feedback sub-plugin.
 *
 * @package     assignfeedback_verified
 * @copyright   2022 Skills Consulting Group
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use assignfeedback_verified\local\persistent\verification;

defined('MOODLE_INTERNAL') || die();

class backup_assignfeedback_verified_subplugin extends backup_subplugin {

    /**
     * Returns the sub-plugin information to attach to submission element.
     *
     * Currently, no method to back up allocated user data that is referenced
     * at the activity/course_module level. Otherwise it will repeat for every
     * grade item.
     *
     * @return backup_subplugin_element
     * @throws base_element_struct_exception
     */
    protected function define_grade_subplugin_structure() {

        // Create XML elements.
        $subplugin = $this->get_subplugin_element();

        // Lives at /activity/assign/grades/grade/subplugin_assignfeedback_verified_grade path.
        $grades = new backup_nested_element($this->get_recommended_name());
        $verifications = new backup_nested_element('verifications');
        $verification = new backup_nested_element(
            'verification',
            ['id'],
            [
                'assignid', 'gradeid', 'status', 'verifierid', 'verifiedby', 'customtext', 'commenttext', 'commentformat',
                'usermodified', 'timecreated', 'timemodified'
            ]
        );

        // Connect XML elements into the tree.
        $subplugin->add_child($grades);
        $grades->add_child($verifications);
        $verifications->add_child($verification);

        // Define id annotations.
        $verification->annotate_ids('user', 'verifierid');
        $verification->annotate_ids('user', 'verifiedby');
        $verification->annotate_ids('user', 'usermodified');

        // Set source(s) to populate the data.
        $verification->set_source_table(verification::TABLE, ['gradeid' => backup::VAR_PARENTID]);

        $verification->annotate_files(
            'assignfeedback_verified',
            'feedback',
            'id'
        );

        return $subplugin;
    }

}
