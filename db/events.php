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
 * Plugin event observers are registered here.
 *
 * @package     assignfeedback_verified
 * @category    event
 * @copyright   2022 Skills Consulting Group
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
$observers = [

    [
        'eventname'   => '\mod_assign\event\submission_created',
        'callback'    => '\assignfeedback_verified\local\event\observer::submission_created',
        'priority'    => 0,
        'internal'    => false
    ],

    [
        'eventname'   => '\mod_assign\event\submission_updated',
        'callback'    => '\assignfeedback_verified\local\event\observer::submission_updated',
        'priority'    => 0,
        'internal'    => false
    ],

    [
        'eventname'   => '\assignfeedback_verified\event\allocate_verifier',
        'callback'    => '\assignfeedback_verified\local\event\observer::allocate_verifier',
        'priority'    => 900,
        'internal'    => true
    ],

];
