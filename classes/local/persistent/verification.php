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

namespace assignfeedback_verified\local\persistent;

use assignfeedback_verified\local\verification_status;
use core\persistent;

class verification extends persistent {

    /** @var string Table for assignment attempt verifications. */
    public const TABLE = 'assignfeedback_verified_v';

    protected static function define_properties() {
        return [
            'assignid' => [
                'type' => PARAM_INT,
                'default' => 0
            ],
            'gradeid' => [
                'type' => PARAM_INT,
                'default' => 0
            ],
            'status' => [
                'type' => PARAM_RAW,
                'default' => verification_status::DEFAULT
            ],
            'verifierid' => [
                'type' => PARAM_INT,
                'default' => 0
            ],
            'verifiedby' => [
                'type' => PARAM_INT,
                'default' => 0
            ],
            'customtext' => [
                'type' => PARAM_RAW,
                'null' => NULL_ALLOWED,
                'default' => null,
                'optional' => true
            ],
            'commenttext' => [
                'type' => PARAM_RAW,
                'default' => ''
            ],
            'commentformat' => [
                'type' => PARAM_INT,
                'default' => FORMAT_HTML
            ],
            'component' => [
                'type' => PARAM_COMPONENT,
                'default' => 'assignfeedback_verified'
            ],
        ];
    }

}
