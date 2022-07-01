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
 * This file contains the definition for the library class for verified feedback sub-plugin.
 *
 * @package     assignfeedback_verified
 * @copyright   2022 Skills Consulting Group
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use assignfeedback_verified\local\exception\invalid_argument_exception;
use assignfeedback_verified\local\form\batch_operation_allocate_verifier as batch_operation_allocate_verifier_form;
use assignfeedback_verified\local\form\batch_operation_remove_verifiers as batch_operation_remove_verifiers_form;
use assignfeedback_verified\local\persistent\allocated_user;
use assignfeedback_verified\local\persistent\verification;
use assignfeedback_verified\local\verification_status;

defined('MOODLE_INTERNAL') || die();

/**
 * Library class for verified feedback sub-plugin containing required sub-plugin hooks.
 *
 * @package     assignfeedback_verified
 * @copyright   2022 Skills Consulting Group
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class assign_feedback_verified extends assign_feedback_plugin {

    /**
     * Factory type method used for checking and building verification slots for an
     * assignment grade based on allocated users aka verifiers.
     *
     * @param stdClass $grade
     * @return array|verification[]|stdClass[]
     * @throws \core\invalid_persistent_exception
     * @throws coding_exception
     * @throws invalid_argument_exception
     */
    public function check_and_build_verification_slots_for_grade(stdClass $grade): array {
        $slots = [];
        if (!$grade->assignment || !$grade->userid) {
            throw new invalid_argument_exception('stdClass $grade is invalid');
        }
        if ($allocatedusers = $this->get_allocated_users($grade, false)) {
            $slots = $this->get_verifications($grade, false);
            if (empty($slots)) {
                foreach ($allocatedusers as $allocateduser) {
                    $verification = static::create_verification_slot(
                        $grade->assignment, $grade->id, $allocateduser->verifierid, $allocateduser->customtext
                    );
                    $slots[] = $verification;
                }
            } else {
                foreach ($allocatedusers as $allocateduser) {
                    $found = array_filter($slots,
                        function($slot) use($allocateduser) {
                            return $slot->verifierid == $allocateduser->verifierid;
                        }
                    );
                    if (!$found) {
                        $verification = static::create_verification_slot(
                            $grade->assignment, $grade->id, $allocateduser->verifierid, $allocateduser->customtext
                        );
                        $slots[] = $verification;
                    }
                }
            }
        }
        return $slots;
    }

    /**
     * Factory type method creates a new verification slot using the persistent class.
     *
     * @param int $assignid
     * @param int $gradeid
     * @param int $verifierid
     * @param string|null $customtext
     * @return stdClass
     * @throws \core\invalid_persistent_exception
     * @throws coding_exception
     */
    public static function create_verification_slot(int $assignid, int $gradeid, int $verifierid, ?string $customtext = ''): stdClass {
        $verificationslot = new verification();
        $verificationslot->set('assignid', $assignid);
        $verificationslot->set('gradeid', $gradeid);
        $verificationslot->set('verifierid', $verifierid);
        if (trim($customtext) !== '') {
            $verificationslot->set('customtext', $customtext);
        }
        return $verificationslot->create()->to_record();
    }

    /**
     * Delete instance handler.
     *
     * @return bool
     * @throws dml_exception
     */
    public function delete_instance() {
        global $DB;

        $DB->delete_records(
            verification::TABLE,
            ['assignid' => $this->assignment->get_instance()->id]
        );
        $DB->delete_records(
            allocated_user::TABLE,
            ['assignid' => $this->assignment->get_instance()->id]
        );
        return true;
    }

    /**
     * Form editor element supported configuration options.
     *
     * @return array Editor element options.
     */
    public function get_editor_options() {
        global $COURSE;
        return [
            'context' => $this->assignment->get_context(),
            'subdirs' => 1,
            'maxbytes' => $COURSE->maxbytes,
            'maxfiles' => EDITOR_UNLIMITED_FILES,
            'noclean' => false,
            'trusttext' => false,
            'autosave' => false,
            'removeorphaneddrafts' => true,
            'accepted_types' => '*'
        ];
    }

    /**
     * Batch operations supported by this plugin.
     *
     * @return array - An array of actions and description strings passed to grading_batch_operation.
     * @throws coding_exception
     */
    public function get_grading_batch_operations(): array {
        return [
            'allocateverifier' => get_string('batchoperationallocateverifier', 'assignfeedback_verified'),
            'removeallocatedverifiers' => get_string('batchoperationremoveallocatedverifiers', 'assignfeedback_verified')
        ];
    }

    /**
     * Build form(s) for our supported plugin batch operations.
     *
     * @param $action
     * @param $users
     * @return string
     * @throws \core\invalid_persistent_exception
     * @throws coding_exception
     * @throws dml_exception
     * @throws moodle_exception
     * @throws required_capability_exception
     */
    public function grading_batch_operation($action, $users): string {
        switch ($action) {
            case 'allocateverifier':
                return $this->view_page_allocate_verifier($users);
            case 'removeallocatedverifiers':
                return $this->view_page_remove_allocated_verifiers($users);
            default:
                throw new coding_exception("Action {$action} not supported");
        }
    }

    /**
     * Human friendly name used in configuration and summary view areas.
     *
     * @return lang_string|string
     * @throws coding_exception
     */
    public function get_name() {
        return get_string('verification', 'assignfeedback_verified');
    }

    /**
     * Helper method to get allocated users aka verifiers associated with a grade.
     *
     * @param stdClass $grade
     * @param bool $returnpersistent
     * @return allocated_user[]|stdClass[]
     * @throws invalid_argument_exception
     */
    public static function get_allocated_users(stdClass $grade, bool $returnpersistent = true): array {
        if (!$grade->assignment || !$grade->userid) {
            throw new invalid_argument_exception('stdClass $grade is invalid');
        }
        $allocatedusers = allocated_user::get_records(['assignid' => $grade->assignment, 'userid' => $grade->userid]);
        if (!$returnpersistent) {
            return array_map(function($allocateduser) {
                return $allocateduser->to_record();
            }, $allocatedusers);
        }
        return $allocatedusers;
    }

    /**
     * Helper method to get verifications associated with a grade.
     *
     * @param stdClass $grade
     * @param bool $returnpersistent
     * @return verification[]|stdClass[]
     */
    public function get_verifications(stdClass $grade, bool $returnpersistent = true): array {
        $verifications = verification::get_records(['gradeid' => $grade->id]);
        if (!$returnpersistent) {
            return array_map(function($verification) {
                return $verification->to_record();
            }, $verifications);
        }
        return $verifications;
    }

    /**
     * Check all verifications for a student's assignment grade have been completed.
     *
     * @param stdClass $grade
     * @return bool
     */
    public function is_verified(stdClass $grade): bool {
        if (!$verifications = $this->get_verifications($grade, false)) {
            return false;
        }
        $verifiedcount = 0;
        foreach ($verifications as $verification) {
            if ($verification->status == verification_status::VERIFIED) {
                $verifiedcount++;
            }
        }
        return $verifiedcount == count($verifications);
    }

    /**
     * Has the plugin form element been modified in the current submission?
     *
     * @param stdClass $grade
     * @param stdClass $data
     * @return bool
     */
    public function is_feedback_modified(stdClass $grade, stdClass $data): bool {
        if (!$verifications = $this->get_verifications($grade, false)) {
            return false;
        }
        foreach ($verifications as $verification) {
            $id = $verification->id;
            $status = $data->{'assignfeedbackverifiedstatus_' . $id};
            if ($verification->status != $status) {
                return true;
            }
            $editor = 'assignfeedbackverifiedcomment_' . $id . '_editor';
            $commenttext = $data->{$editor}['text'];
            // Need to convert the form text to use @@PLUGINFILE@@ so we can compare it with what is stored in the DB.
            if (isset($data->{$editor}['itemid'])) {
                $commenttext = file_rewrite_urls_to_pluginfile($commenttext, $data->{$editor}['itemid']);
            }
            if ($verification->commenttext != $commenttext) {
                return true;
            }
        }
        return false;
    }

    /**
     * Returns true if there are no verification records for the given grade.
     *
     * @param stdClass $grade
     * @return bool
     */
    public function is_empty(stdClass $grade): bool {
        return verification::count_records(['gradeid' => $grade->id]) <= 0;
    }

    /**
     * Build form for grading panel.
     *
     * @param $grade
     * @param MoodleQuickForm $mform
     * @param stdClass $data
     * @param $userid
     * @return bool
     * @throws \core\invalid_persistent_exception
     * @throws coding_exception
     * @throws invalid_argument_exception
     */
    public function get_form_elements_for_user($grade, MoodleQuickForm $mform, stdClass $data, $userid): bool {
        global $USER;

        $verifications = static::check_and_build_verification_slots_for_grade($grade);
        if (!$verifications) {
            $mform->addElement('static', 'notice', get_string('noverifiersallocated', 'assignfeedback_verified'));
            return false;
        }
        foreach ($verifications as $verification) {
            $canverify = $verification->verifierid == $USER->id || has_capability('assignfeedback/verified:anybyproxy', $this->assignment->get_context());
            if ($canverify) {
                $radiogroup = [];
                $radiofield  = 'assignfeedbackverifiedstatus_' . $verification->id;
                $radiolabel = $verification->customtext ?? get_string('verification',  'assignfeedback_verified');
                $radiogroup[] = $mform->createElement('radio', $radiofield, '', get_string('requestchanges',  'assignfeedback_verified'), verification_status::CHANGES_REQUESTED);
                $radiogroup[] = $mform->createElement('radio', $radiofield, '', get_string('verified',  'assignfeedback_verified'), verification_status::VERIFIED);
                $mform->addGroup($radiogroup, 'statusradiogroup', $radiolabel, '', false);
                $mform->setDefault($radiofield, $verification->status);
                $field = 'assignfeedbackverifiedcomment_' . $verification->id;
                $editor = $field . '_editor';
                $data->{$field} = $verification->commenttext;
                $data->{$field . 'format'} = $verification->commentformat;
                file_prepare_standard_editor(
                    $data,
                    $field,
                    $this->get_editor_options(),
                    $this->assignment->get_context(),
                    'assignfeedback_verified',
                    'comment',
                    $verification->id
                );
                $editorlabel = get_string('leavefeedback', 'assignfeedback_verified');
                $mform->addElement('editor', $editor, $editorlabel, null, $this->get_editor_options());
            }
        }
        return true;
    }

    /**
     * Save verification data associated with a learners grade record.
     *
     * @param stdClass $grade
     * @param stdClass $data
     * @return bool
     * @throws \core\invalid_persistent_exception
     * @throws coding_exception
     */
    public function save(stdClass $grade, stdClass $data) {
        global $USER;

        if (!$verifications = $this->get_verifications($grade)) {
            return false;
        }
        foreach ($verifications as $verification) {
            $id = $verification->get('id');
            $status = $data->{'assignfeedbackverifiedstatus_' . $id};
            if ($status) {
                $verification->set('status', $status);
                if ($status == verification_status::VERIFIED) {
                    $verification->set('verifiedby', $USER->id);
                }
            }
            $field = 'assignfeedbackverifiedcomment_' . $id;
            $editor = $field . '_editor';
            $data = file_postupdate_standard_editor(
                $data,
                $field,
                $this->get_editor_options(),
                $this->assignment->get_context(),
                'assignfeedback_verified',
                'comment',
                $id
            );
            $commenttext = $data->{$field};
            $commentformat = $data->{$field . 'format'};
            $verification->set('commenttext', $commenttext);
            $verification->set('commentformat', $commentformat);
            $verification->update();
        }
        return true;
    }

    public function save_settings(stdClass $data) {
        return true;
    }

    /**
     * Display the information in the feedback table.
     *
     *
     * @todo Implement.
     * @param stdClass $grade The grade object.
     * @return string The feedback to display.
     */
    public function view(stdClass $grade): string {
        if (!$verifications = $this->get_verifications($grade, false)) {
            return '';
        }
        $output = '';
        return $output;
    }

    /**
     * Direct to plugin operation page based on action.
     *
     * @param $action
     * @return string
     * @throws \core\invalid_persistent_exception
     * @throws coding_exception
     * @throws dml_exception
     * @throws moodle_exception
     * @throws required_capability_exception
     */
    public function view_page($action) {
        $users = explode(',', required_param('selectedusers', PARAM_SEQUENCE));
        switch ($action) {
            case 'allocateverifier':
                return $this->view_page_allocate_verifier($users);
            case 'removeallocatedverifiers':
                return $this->view_page_remove_allocated_verifiers($users);
            default:
                throw new coding_exception("Action {$action} not supported");
        }
    }

    /**
     * Callback method used to provide functionality for allocating a verifier to a list of
     * learners in a particular assignment.
     *
     * @param array $users
     * @return string
     * @throws \core\invalid_persistent_exception
     * @throws coding_exception
     * @throws dml_exception
     * @throws moodle_exception
     * @throws required_capability_exception
     */
    public function view_page_allocate_verifier(array $users): string {
        global $DB;

        require_capability('mod/assign:grade', $this->assignment->get_context());

        $returnurl = new moodle_url(
            'view.php',
            [
                'id' => $this->assignment->get_course_module()->id,
                'action'=>'grading'
            ]
        );

        $userlist = '';
        foreach($DB->get_records_list('user', 'id', $users) as $user) {
            $usersummary = new assign_user_summary(
                $user,
                $this->assignment->get_course()->id,
                has_capability('moodle/site:viewfullnames', $this->assignment->get_course_context()),
                $this->assignment->is_blind_marking(),
                $this->assignment->get_uniqueid_for_user($user->id),
                get_extra_user_fields($this->assignment->get_context()));
            $userlist .= $this->assignment->get_renderer()->render($usersummary);
        }
        $customdata = [
            'cmid' => $this->assignment->get_course_module()->id,
            'assignid' => $this->assignment->get_instance()->id,
            'context' => $this->assignment->get_context(),
            'users' => $users,
            'userlist' => $userlist
        ];
        $mform = new batch_operation_allocate_verifier_form(null, $customdata);
        if ($mform->is_cancelled()) {
            redirect($returnurl);
        }
        if ($data = $mform->get_data()) {
            foreach (explode(',', $data->selectedusers) as $userid) {
                $params = ['assignid' => $data->assignid, 'userid' => $userid, 'verifierid' => $data->verifierid];
                if (!allocated_user::count_records($params)) {
                    $params['customtext'] = empty($data->customtext) ? null : $data->customtext;
                    $allocatedverifier = new allocated_user(0, (object) $params);
                    $allocatedverifier->create();
                }
            }
            redirect($returnurl);
        }
        $header = new assign_header(
            $this->assignment->get_instance(),
            $this->assignment->get_context(),
            false,
            $this->assignment->get_course_module()->id,
            get_string('batchoperationallocateverifier', 'assignfeedback_verified')
        );
        $output = '';
        $output .= $this->assignment->get_renderer()->render($header);
        $output .= $this->assignment->get_renderer()->render(new assign_form('batchoperationallocateverifier', $mform));
        $output .= $this->assignment->get_renderer()->render_footer();
        return $output;
    }

    /**
     * Callback method used to provide functionality for removing verifiers from a list of
     * learners in a particular assignment.
     *
     * @param array $users
     * @return string
     * @throws coding_exception
     * @throws dml_exception
     * @throws moodle_exception
     * @throws required_capability_exception
     */
    public function view_page_remove_allocated_verifiers(array $users): string {
        global $DB;

        require_capability('mod/assign:grade', $this->assignment->get_context());

        $returnurl = new moodle_url(
            'view.php',
            [
                'id' => $this->assignment->get_course_module()->id,
                'action'=>'grading'
            ]
        );

        $userlist = '';
        foreach($DB->get_records_list('user', 'id', $users) as $user) {
            $usersummary = new assign_user_summary(
                $user,
                $this->assignment->get_course()->id,
                has_capability('moodle/site:viewfullnames', $this->assignment->get_course_context()),
                $this->assignment->is_blind_marking(),
                $this->assignment->get_uniqueid_for_user($user->id),
                get_extra_user_fields($this->assignment->get_context()));
            $userlist .= $this->assignment->get_renderer()->render($usersummary);
        }
        $customdata = [
            'cmid' => $this->assignment->get_course_module()->id,
            'assignid' => $this->assignment->get_instance()->id,
            'context' => $this->assignment->get_context(),
            'users' => $users,
            'userlist' => $userlist
        ];
        $mform = new batch_operation_remove_verifiers_form(null, $customdata);
        if ($mform->is_cancelled()) {
            redirect($returnurl);
        }
        if ($data = $mform->get_data()) {
            $params['assignid'] = $data->assignid;
            $selecteduserids = explode(',', $data->selectedusers);
            list($insql, $inparams) = $DB->get_in_or_equal($selecteduserids, SQL_PARAMS_NAMED);
            $DB->delete_records_select(
                allocated_user::TABLE,
                "assignid = :assignid AND userid $insql",
                array_merge($params, $inparams)
            );
            redirect($returnurl);
        }
        $header = new assign_header(
            $this->assignment->get_instance(),
            $this->assignment->get_context(),
            false,
            $this->assignment->get_course_module()->id,
            get_string('batchoperationremoveallocatedverifiers', 'assignfeedback_verified')
        );
        $output = '';
        $output .= $this->assignment->get_renderer()->render($header);
        $output .= $this->assignment->get_renderer()->render(new assign_form('batchoperationremoveallocatedverifiers', $mform));
        $output .= $this->assignment->get_renderer()->render_footer();
        return $output;
    }

    /**
     * Display the information feedback summary.
     *
     * @param stdClass $grade The grade object.
     * @param bool $showviewlink Set to true to show a link to view the full feedback.
     * @return string The feedback to display.
     * @throws coding_exception
     * @throws dml_exception
     * @throws moodle_exception
     */
    public function view_summary(stdClass $grade, &$showviewlink): string {
        global $PAGE;

        $context = [];
        $render =  $PAGE->get_renderer('assignfeedback_verified');
        if (!$verifications = $this->get_verifications($grade, false)) {
            $context['summarytitle'] = get_string('noverifiersallocated', 'assignfeedback_verified');
        } else {
            if ($pendingcount = static::pending_verifications_count($verifications)) {
                $context['summarytitle'] = get_string('verificationspending', 'assignfeedback_verified', $pendingcount);
            } else {
                $context['summarytitle'] = get_string('verificationcomplete', 'assignfeedback_verified');
                $context['verified'] = true;
            }
            $context['verifications'] = [];
            foreach ($verifications as $verification) {
                $data = new stdClass();
                $data->verificationstautus = $verification->status;
                if ($verification->status == verification_status::VERIFIED) {
                    $data->verified = true;
                }
                if (trim($verification->customtext) !== '') {
                    $data->title = s($verification->customtext);
                } else {
                    $data->title = get_string('verification', 'assignfeedback_verified');
                }
                if ($verification->verifiedby) {
                    $data->verifiedbyfullname = fullname(\core_user::get_user($verification->verifiedby));
                }
                $data->comment = format_text(
                    $this->rewrite_comment_text_urls($verification->commenttext, $verification->id),
                    $verification->commentformat,
                    ['context' => $this->assignment->get_context()->id]
                );
                $context['verifications'][] = $data;
            }
        }
        return $render->render_from_template("assignfeedback_verified/verification_summary", $context);
    }

    /**
     * Iterate verifications and return count of unverified.
     *
     * @param array $verifications
     * @return int
     */
    public static function pending_verifications_count(array $verifications): int {
        $pending = array_filter($verifications,
            function ($verification) {
                return $verification->status != verification_status::VERIFIED;
            }
        );
        return count($pending);
    }

    /**
     * Convert encoded URLs in $text from the @@PLUGINFILE@@/... form to an actual URL.
     *
     * @param string $text The text to check.
     * @param int $itemid  The verification id.
     * @return string
     */
    private function rewrite_comment_text_urls(string $text, int $itemid): string {
        return file_rewrite_pluginfile_urls(
            $text,
            'pluginfile.php',
            $this->assignment->get_context()->id,
            'assignfeedback_verified',
            'comment',
            $itemid
        );
    }

}
