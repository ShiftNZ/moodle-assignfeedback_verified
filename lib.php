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
 * Main library for core Moodle hooks.
 *
 * @package     assignfeedback_verified
 * @copyright   2022 Skills Consulting Group
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Serves any assignment verified feedback files.
 *
 * @param $course A course object or id of the course.
 * @param $cm
 * @param context $context
 * @param $filearea
 * @param $args
 * @param $forcedownload
 * @param array $options
 * @return false|void
 * @throws coding_exception
 * @throws dml_exception
 * @throws moodle_exception
 * @throws require_login_exception
 */
function assignfeedback_verified_pluginfile(
    $course,
    $cm,
    context $context,
    $filearea,
    $args,
    $forcedownload,
    array $options = []) {
    global $USER, $DB, $CFG, $PAGE;

    require_once($CFG->dirroot . '/mod/assign/locallib.php');

    if ($context->contextlevel != CONTEXT_MODULE) {
        return false;
    }

    require_login($course, false, $cm);

    $itemid = (int) array_shift($args);
    $verification = new \assignfeedback_verified\local\persistent\verification($itemid);

    $record = $DB->get_record('assign_grades', ['id' => $verification->get('gradeid')], 'userid,assignment', MUST_EXIST);
    $userid = $record->userid;

    $assign = new assign($context, $cm, $course);
    $instance = $assign->get_instance();

    if ($instance->id != $record->assignment) {
        return false;
    }

    if (!$assign->can_view_submission($userid)) {
        return false;
    }

    $relativepath = implode('/', $args);

    $fullpath = "/{$context->id}/assignfeedback_verified/$filearea/$itemid/$relativepath";

    $fs = get_file_storage();
    $file = $fs->get_file_by_hash(sha1($fullpath));
    if (! $file || $file->is_directory()) {
        return false;
    }

    send_stored_file($file, 0, 0, true, $options);
}
