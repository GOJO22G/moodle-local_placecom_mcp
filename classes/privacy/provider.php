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

namespace local_placecom_mcp\privacy;

use context;
use context_system;
use core_privacy\local\metadata\collection;
use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\approved_userlist;
use core_privacy\local\request\contextlist;
use core_privacy\local\request\transform;
use core_privacy\local\request\userlist;
use core_privacy\local\request\writer;

/**
 * Privacy Subsystem for local_placecom_mcp implements metadata provider.
 *
 * A user's data is held in the user's own context. The token scope table has no user
 * column: a row belongs to a user through the web service token it describes
 * (external_tokens.token and external_tokens.userid), so it is always reached by joining
 * to external_tokens.
 *
 * @package local_placecom_mcp
 * @author Lai Wei <lai.wei@enovation.ie>
 * @author Dorel Manolescu <dorel.manolescu@enovation.ie>
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @copyright (C) 2025 Enovation Solutions
 * @copyright 2026 AlmaBay Networks Pvt. Ltd. (Placecom) - namespace/table renames, token scope coverage,
 *            user context handling, declaration of data released to AI assistants
 */
class provider implements
    \core_privacy\local\metadata\provider,
    \core_privacy\local\request\core_userlist_provider,
    \core_privacy\local\request\plugin\provider {
    /** @var string[] plugin tables that contain a user_id column. */
    const TABLES = [
        'local_placecom_mcp_user_auth_scope',
        'local_placecom_mcp_access_token',
        'local_placecom_mcp_authorization_code',
        'local_placecom_mcp_refresh_token',
    ];

    /**
     *  Provides metadata that is stored about a user in the local_placecom_mcp plugin.
     *
     * @param collection $collection A collection of metadata items to be added to.
     * @return  collection Returns the collection of metadata.
     */
    public static function get_metadata(collection $collection): collection {
        // Add metadata for the local_placecom_mcp_user_auth_scope table.
        $collection->add_database_table(
            'local_placecom_mcp_user_auth_scope',
            [
                'user_id' => 'privacy:metadata:local_placecom_mcp_user_auth_scope:user_id',
                'client_id' => 'privacy:metadata:local_placecom_mcp_user_auth_scope:client_id',
                'scope' => 'privacy:metadata:local_placecom_mcp_user_auth_scope:scope',
            ],
            'privacy:metadata:local_placecom_mcp_user_auth_scope'
        );

        // Add metadata for the local_placecom_mcp_access_token table.
        $collection->add_database_table(
            'local_placecom_mcp_access_token',
            [
                'user_id' => 'privacy:metadata:local_placecom_mcp_access_token:user_id',
                'client_id' => 'privacy:metadata:local_placecom_mcp_access_token:client_id',
                'scope' => 'privacy:metadata:local_placecom_mcp_access_token:scope',
                'access_token' => 'privacy:metadata:local_placecom_mcp_access_token:access_token',
                'expires' => 'privacy:metadata:local_placecom_mcp_access_token:expires',
            ],
            'privacy:metadata:local_placecom_mcp_access_token'
        );

        // Add metadata for the local_placecom_mcp_authorization_code table.
        $collection->add_database_table(
            'local_placecom_mcp_authorization_code',
            [
                'user_id' => 'privacy:metadata:local_placecom_mcp_authorization_code:user_id',
                'authorization_code' => 'privacy:metadata:local_placecom_mcp_authorization_code:authorization_code',
                'client_id' => 'privacy:metadata:local_placecom_mcp_authorization_code:client_id',
                'redirect_uri' => 'privacy:metadata:local_placecom_mcp_authorization_code:redirect_uri',
                'expires' => 'privacy:metadata:local_placecom_mcp_authorization_code:expires',
                'scope' => 'privacy:metadata:local_placecom_mcp_authorization_code:scope',
                'id_token' => 'privacy:metadata:local_placecom_mcp_authorization_code:id_token',
            ],
            'privacy:metadata:local_placecom_mcp_authorization_code'
        );

        // Add metadata for the local_placecom_mcp_refresh_token table.
        $collection->add_database_table(
            'local_placecom_mcp_refresh_token',
            [
                'user_id' => 'privacy:metadata:local_placecom_mcp_refresh_token:user_id',
                'refresh_token' => 'privacy:metadata:local_placecom_mcp_refresh_token:refresh_token',
                'client_id' => 'privacy:metadata:local_placecom_mcp_refresh_token:client_id',
                'expires' => 'privacy:metadata:local_placecom_mcp_refresh_token:expires',
                'scope' => 'privacy:metadata:local_placecom_mcp_refresh_token:scope',
            ],
            'privacy:metadata:local_placecom_mcp_refresh_token'
        );

        // Add metadata for the local_placecom_mcp_token_scope table. It is linked to a user
        // through the web service token, not through a user column.
        $collection->add_database_table(
            'local_placecom_mcp_token_scope',
            [
                'token' => 'privacy:metadata:local_placecom_mcp_token_scope:token',
                'scope' => 'privacy:metadata:local_placecom_mcp_token_scope:scope',
                'timecreated' => 'privacy:metadata:local_placecom_mcp_token_scope:timecreated',
            ],
            'privacy:metadata:local_placecom_mcp_token_scope'
        );

        // A web service token is created for each authorised user and stored by core_external.
        $collection->add_subsystem_link('core_external', [], 'privacy:metadata:core_external');

        // Data that is released to a connected AI assistant (an MCP client).
        $collection->add_external_location_link(
            'mcp_assistant',
            [
                'identity' => 'privacy:metadata:mcp_assistant:identity',
                'moodledata' => 'privacy:metadata:mcp_assistant:moodledata',
            ],
            'privacy:metadata:mcp_assistant'
        );

        return $collection;
    }

    /**
     * Returns the contexts that are relevant to the user.
     *
     * @param int $userid The user ID.
     * @return contextlist A list of contexts relevant to the user.
     */
    public static function get_contexts_for_userid(int $userid): contextlist {
        $contextlist = new contextlist();

        foreach (self::TABLES as $table) {
            $sql = "SELECT ctx.id
                     FROM {{$table}} t
                     JOIN {context} ctx ON t.user_id = ctx.instanceid AND ctx.contextlevel = :contextlevel
                    WHERE t.user_id = :userid";

            $params = [
                'contextlevel' => CONTEXT_USER,
                'userid' => $userid,
            ];

            $contextlist->add_from_sql($sql, $params);
        }

        // Token scope rows belong to the user who owns the web service token.
        $sql = "SELECT ctx.id
                  FROM {local_placecom_mcp_token_scope} ts
                  JOIN {external_tokens} et ON et.token = ts.token
                  JOIN {context} ctx ON ctx.instanceid = et.userid AND ctx.contextlevel = :contextlevel
                 WHERE et.userid = :userid";
        $contextlist->add_from_sql($sql, ['contextlevel' => CONTEXT_USER, 'userid' => $userid]);

        return $contextlist;
    }

    /**
     * Returns the users in the context.
     *
     * @param userlist $userlist The user list to be populated.
     * @return void
     */
    public static function get_users_in_context(userlist $userlist) {
        $context = $userlist->get_context();

        if ($context instanceof \context_user) {
            // A user context holds the data of the one user it belongs to.
            if (self::user_has_data((int) $context->instanceid)) {
                $userlist->add_user((int) $context->instanceid);
            }
            return;
        }

        if (!$context instanceof context_system) {
            return;
        }

        foreach (self::TABLES as $table) {
            $userlist->add_from_sql('user_id', "SELECT DISTINCT user_id FROM {{$table}}", []);
        }

        $userlist->add_from_sql(
            'userid',
            "SELECT DISTINCT et.userid
               FROM {local_placecom_mcp_token_scope} ts
               JOIN {external_tokens} et ON et.token = ts.token",
            []
        );
    }

    /**
     * Exports user data for the specified user in the specified contexts.
     *
     * @param approved_contextlist $contextlist The approved contexts to export information for.
     * @return void
     */
    public static function export_user_data(\core_privacy\local\request\approved_contextlist $contextlist) {
        if (empty($contextlist)) {
            return;
        }

        foreach ($contextlist as $context) {
            if ($context->contextlevel == CONTEXT_USER) {
                // Export user data for the specified user in the specified contexts.
                self::export_local_placecom_mcp_userdata($context);
            }
        }
    }

    /**
     * Deletes user data for the specified user in the specified contexts.
     *
     * @param approved_userlist $userlist The approved user list to delete information for.
     * @return void
     */
    public static function delete_data_for_users(approved_userlist $userlist) {
        $context = $userlist->get_context();

        if ($context->contextlevel == CONTEXT_SYSTEM || $context->contextlevel == CONTEXT_USER) {
            self::delete_user_data($userlist->get_userids());
        }
    }

    /**
     * Deletes all data for all users in the specified context.
     *
     * @param context $context
     * @return void
     */
    public static function delete_data_for_all_users_in_context(context $context) {
        global $DB;

        if ($context->contextlevel == CONTEXT_SYSTEM) {
            foreach (self::TABLES as $table) {
                $DB->delete_records($table);
            }
            $DB->delete_records('local_placecom_mcp_token_scope');
        } else if ($context->contextlevel == CONTEXT_USER) {
            self::delete_user_data([(int) $context->instanceid]);
        }
    }

    /**
     * Deletes all data for the specified user in the specified contexts.
     *
     * @param approved_contextlist $contextlist The approved contexts to delete information for.
     * @return void
     */
    public static function delete_data_for_user(approved_contextlist $contextlist) {
        $user = $contextlist->get_user();

        foreach ($contextlist->get_contexts() as $context) {
            if ($context->contextlevel == CONTEXT_USER && $context->instanceid == $user->id) {
                self::delete_user_data([(int) $user->id]);
                return;
            }
        }
    }

    /**
     * Exports user data for the specified user in the specified contexts.
     *
     * @param context $context The context to export information for.
     * @return void
     */
    public static function export_local_placecom_mcp_userdata(\context $context) {
        global $DB;
        if (!$context instanceof \context_user) {
            return;
        }

        // The context belongs to the user whose data is being exported. This is not necessarily
        // the logged in user: a data request is usually processed by an administrator or cron.
        $userid = (int) $context->instanceid;

        $subcontext[] = get_string('pluginname', 'local_placecom_mcp');

        $data = [];
        $usertokendata = [];
        $usercodedata = [];
        $userrefreshtokendata = [];
        $usertokenscopedata = [];
        $notexportedstr = get_string('privacy:request:notexportedsecurity', 'core_external');

        foreach (self::TABLES as $table) {
            $sql = "SELECT t.*
                      FROM {{$table}} t
                     WHERE t.user_id = :userid";
            $params = [
                'userid' => $userid,
            ];
            $records = $DB->get_records_sql($sql, $params);

            foreach ($records as $record) {
                $record->client_id = $notexportedstr;
                if ($table == 'local_placecom_mcp_access_token') {
                    $record->access_token = $notexportedstr;
                    $record->expires = transform::datetime($record->expires);
                    $usertokendata[] = $record;
                } else if ($table == 'local_placecom_mcp_authorization_code') {
                    $record->authorization_code = $notexportedstr;
                    $record->expires = transform::datetime($record->expires);
                    $usercodedata[] = $record;
                } else if ($table == 'local_placecom_mcp_refresh_token') {
                    $record->refresh_token = $notexportedstr;
                    $record->expires = transform::datetime($record->expires);
                    $userrefreshtokendata[] = $record;
                } else {
                    $data[] = $record;
                }
            }
        }

        // The token itself is a credential, so only the scope and time are exported.
        $sql = "SELECT ts.id, ts.scope, ts.timecreated
                  FROM {local_placecom_mcp_token_scope} ts
                  JOIN {external_tokens} et ON et.token = ts.token
                 WHERE et.userid = :userid";
        foreach ($DB->get_records_sql($sql, ['userid' => $userid]) as $record) {
            $usertokenscopedata[] = (object) [
                'scope' => $record->scope,
                'timecreated' => transform::datetime($record->timecreated),
            ];
        }

        if (
            !empty($data) || !empty($usertokendata) || !empty($usercodedata) ||
            !empty($userrefreshtokendata) || !empty($usertokenscopedata)
        ) {
            writer::with_context($context)
                ->export_data($subcontext, (object)[
                    'userauthscope' => $data,
                    'userauthtoken' => $usertokendata,
                    'userauthcode' => $usercodedata,
                    'userrefreshtoken' => $userrefreshtokendata,
                    'usertokenscope' => $usertokenscopedata,
                ]);
        }
    }

    /**
     * Whether the user has any data held by this plugin.
     *
     * @param int $userid The user ID.
     * @return bool
     */
    protected static function user_has_data(int $userid): bool {
        global $DB;

        foreach (self::TABLES as $table) {
            if ($DB->record_exists($table, ['user_id' => $userid])) {
                return true;
            }
        }

        return $DB->record_exists_sql(
            "SELECT 1
               FROM {local_placecom_mcp_token_scope} ts
               JOIN {external_tokens} et ON et.token = ts.token
              WHERE et.userid = :userid",
            ['userid' => $userid]
        );
    }

    /**
     * Deletes all of the plugin's data for the given users.
     *
     * @param int[] $userids The user IDs.
     * @return void
     */
    protected static function delete_user_data(array $userids): void {
        global $DB;

        if (empty($userids)) {
            return;
        }

        [$usersql, $userparams] = $DB->get_in_or_equal($userids, SQL_PARAMS_NAMED);

        foreach (self::TABLES as $table) {
            $DB->delete_records_select($table, "user_id $usersql", $userparams);
        }

        // Token scope rows have no user column, so find them through the users' web service tokens.
        $DB->delete_records_select(
            'local_placecom_mcp_token_scope',
            "token IN (SELECT et.token FROM {external_tokens} et WHERE et.userid $usersql)",
            $userparams
        );

        // Moodle's core_external provider also deletes web service tokens, and the order in which
        // providers run is not defined. If it ran first, the rows above can no longer be matched to
        // a user. A row whose token no longer exists cannot be linked to anyone and is of no use,
        // so remove such rows now instead of waiting for the scheduled cleanup task.
        $DB->delete_records_select(
            'local_placecom_mcp_token_scope',
            "token NOT IN (SELECT et.token FROM {external_tokens} et)"
        );
    }
}
