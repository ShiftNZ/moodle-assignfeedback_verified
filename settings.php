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
 * This file defines the admin settings for this the verified assignment feedback plugin.
 *
 * @package     assignfeedback_verified
 * @copyright   2022 Skills Consulting Group
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$setting = new admin_setting_configcheckbox(
    'assignfeedback_verified/default',
    get_string('default', 'assignfeedback_verified'),
    get_string('default_help', 'assignfeedback_verified'), 0
);
$settings->add($setting);

$setting = new admin_setting_configcheckbox(
    'assignfeedback_verified/allowmanualallocation',
    get_string('allowmanualallocation', 'assignfeedback_verified'),
    get_string('allowmanualallocation::help', 'assignfeedback_verified'), 0
);
$settings->add($setting);
