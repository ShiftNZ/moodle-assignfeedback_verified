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

namespace assignfeedback_verified\local\event;

class observer {

    public static function allocate_verifier(\assignfeedback_verified\event\allocate_verifier $event) {

        if ($record = $event->get_record_snapshot($event->objecttable, $event->objectid)) {
            list($course, $coursemodule) = get_course_and_cm_from_instance($record->assignid, 'assign');
            $context = \context_module::instance($coursemodule->id);
            $assign = new \assign($context, $coursemodule, $course);
            $grade = $assign->get_user_grade($record->userid, true);
            /** @var \assign_feedback_verified $feedbackplugin */
            $feedbackplugin = $assign->get_feedback_plugin_by_type('verified');
            if ($feedbackplugin->get_config('enabled')) {
                $feedbackplugin->check_and_build_verification_slots_for_grade($grade);
            }
        }

    }

    /**
     * Listen to events and queue the submission for processing.
     *
     * @param \mod_assign\event\submission_created $event
     */
    public static function submission_created(\mod_assign\event\submission_created $event) {
        $submissionid = $event->other['submissionid'];
        static::build_verification_slots($submissionid);
    }

    /**
     * Listen to events and queue the submission for processing.
     *
     * @param \mod_assign\event\submission_updated $event
     */
    public static function submission_updated(\mod_assign\event\submission_updated $event) {
        $submissionid = $event->other['submissionid'];
        static::build_verification_slots($submissionid);
    }

    public static function build_verification_slots(int $submissionid) {
        global $DB;
        if ($submission = $DB->get_record('assign_submission', ['id' => $submissionid])) {
            list($course, $coursemodule) = get_course_and_cm_from_instance($submission->assignment, 'assign');
            $context = \context_module::instance($coursemodule->id);
            $assign = new \assign($context, $coursemodule, $course);
            $grade = $assign->get_user_grade($submission->userid, true, $submission->attemptnumber);
            /** @var \assign_feedback_verified $feedbackplugin */
            $feedbackplugin = $assign->get_feedback_plugin_by_type('verified');
            if ($feedbackplugin->get_config('enabled')) {
                $feedbackplugin->check_and_build_verification_slots_for_grade($grade);
            }
        }
    }

}
