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

use core\persistent;

class allocated_user extends persistent {

    /** @var string Table for users allocated to learners as verifiers. */
    public const TABLE = 'assignfeedback_verified_au';

    protected static function define_properties() {
        return [
            'assignid' => [
                'type' => PARAM_INT,
                'default' => 0
            ],
            'userid' => [
                'type' => PARAM_INT,
                'default' => 0
            ],
            'verifierid' => [
                'type' => PARAM_INT,
                'default' => 0
            ],
            'customtext' => [
                'type' => PARAM_RAW,
                'null' => NULL_ALLOWED,
                'default' => null,
                'optional' => true
            ],
        ];
    }

}
