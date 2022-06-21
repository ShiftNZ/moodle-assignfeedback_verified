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

namespace assignfeedback_verified\external;

use context_module;
use core_user;
use external_api;
use external_description;
use external_function_parameters;
use external_multiple_structure;
use external_single_structure;
use external_value;
use user_picture;

class search_verifier extends external_api {

    /**
     * Describes the external function parameters.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters(
            [
                'query' => new external_value(PARAM_RAW, 'The search query', VALUE_REQUIRED),
                'assignid' => new external_value(PARAM_INT, 'Assignment ID'),
            ]
        );
    }

    /**
     * Find users can be allocated as verifiers to a learners matching the given query.
     *
     * @param string $query
     * @param int $assignid
     * @return array
     * @throws \coding_exception
     * @throws \dml_exception
     * @throws \invalid_parameter_exception
     * @throws \restricted_context_exception
     */
    public static function execute(string $query, int $assignid): array {
        global $CFG, $DB, $USER;

        $params = external_api::validate_parameters(
            self::execute_parameters(),
            ['query' => $query, 'assignid' => $assignid]
        );
        $query = $params['query'];
        $assignid = $params['assignid'];

        $cm = get_coursemodule_from_instance(
            'assign',
            $assignid,
            0,
            false,
            MUST_EXIST
        );
        $context = context_module::instance($cm->id);
        self::validate_context($context);

        $params = [];
        $wheres = [];
        $wheresql  = '';
        $extrasearchfields = [];
        if (!empty($CFG->showuseridentity) && has_capability('moodle/site:viewuseridentity', $context)) {
            $extrasearchfields = explode(',', $CFG->showuseridentity);
        }
        $fields = user_picture::fields('u', $extrasearchfields);

        list($esql, $eparams) = get_enrolled_sql($context, 'mod/assign:grade', null, true);
        $params = array_merge($params, $eparams);

        $basesql = "FROM {user} u
                    JOIN ($esql) je ON je.id = u.id";

        // The current user doesn't need to be in list.
        $wheres[] = "u.id != :userid";
        $params['userid'] = $USER->id;

        $fullname = $DB->sql_concat('u.firstname', "' '", 'u.lastname');
        if (!empty($query)) {
            $wheres[] = $DB->sql_like($fullname, ':search1', false, false);
            $params['search1'] = "%$query%";
        }

        if ($wheres) {
            $wheresql = " WHERE " . implode(" AND ", $wheres);
        }

        $countsql = "SELECT COUNT(1) " . $basesql . $wheresql;
        $selectsql = "SELECT $fields " . $basesql . $wheresql . " ORDER BY $fullname ASC";

        $count = $DB->count_records_sql($countsql, $params);
        $rs = $DB->get_recordset_sql($selectsql, $params, 0, $CFG->maxusersperpage);

        $list = [];
        foreach ($rs as $record) {
            $newuser = (object) [
                'id' => $record->id,
                'fullname' => fullname($record),
                'extrafields' => []
            ];
            foreach ($extrasearchfields as $extrafield) {
                // Sanitize the extra fields to prevent potential XSS exploit.
                $newuser->extrafields[] = (object) [
                    'name' => $extrafield,
                    'value' => s($record->$extrafield)
                ];
            }
            $list[$record->id] = $newuser;
        }
        $rs->close();

        return [
            'list' => $list,
            'maxusersperpage' => $CFG->maxusersperpage,
            'overflow' => ($count > $CFG->maxusersperpage),
        ];
    }

    /**
     * Describes the external structure returned.
     *
     * @return external_description
     * @throws \coding_exception
     */
    public static function execute_returns(): external_description {
        return new external_single_structure([
            'list' => new external_multiple_structure(
                new external_single_structure([
                    'id' => new external_value(core_user::get_property_type('id'), 'ID of the user'),
                    'fullname' => new external_value(PARAM_RAW, 'The full name of the user'),
                    'extrafields' => new external_multiple_structure(
                        new external_single_structure([
                            'name' => new external_value(PARAM_TEXT, 'Name of the extra field.'),
                            'value' => new external_value(PARAM_TEXT, 'Value of the extra field.'),
                        ]), 'List of extra fields', VALUE_OPTIONAL)
                ])
            ),
            'maxusersperpage' => new external_value(PARAM_INT, 'Configured maximum users per page.'),
            'overflow' => new external_value(PARAM_BOOL, 'Were there more records than maxusersperpage found?'),
        ]);
    }

}
