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
 * Lists active OAuth access tokens and allows an admin to revoke one
 * immediately, rather than waiting for it to naturally expire.
 *
 * @package local_placecom_mcp
 * @copyright 2026 AlmaBay Networks Pvt. Ltd.
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use local_placecom_mcp\event\access_token_revoked;

require_once(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/adminlib.php');

require_login();
require_capability('local/placecom_mcp:manage_oauth_clients', context_system::instance());

admin_externalpage_setup('local_placecom_mcp_manage_tokens');

$action = optional_param('action', '', PARAM_ALPHANUMEXT);
$tokenid = optional_param('id', 0, PARAM_INT);

$pageurl = new moodle_url('/local/placecom_mcp/manage_tokens.php');
$PAGE->set_url($pageurl);
$PAGE->set_title(get_string('manage_tokens', 'local_placecom_mcp'));
$PAGE->set_heading(get_string('manage_tokens', 'local_placecom_mcp'));

if ($action === 'revoke' && $tokenid) {
    require_sesskey();

    $tokenrecord = $DB->get_record('local_placecom_mcp_access_token', ['id' => $tokenid]);

    if ($tokenrecord) {
        $token = $tokenrecord->access_token;
        $clientid = $tokenrecord->client_id;
        $revokeduserid = $tokenrecord->user_id;

        $DB->delete_records('local_placecom_mcp_access_token', ['id' => $tokenid]);

        $DB->delete_records('local_placecom_mcp_refresh_token', [
            'user_id' => $revokeduserid,
            'client_id' => $clientid,
        ]);

        $event = access_token_revoked::create([
            'objectid' => $tokenid,
            'relateduserid' => $revokeduserid,
            'other' => [
                'clientid' => $clientid,
            ],
        ]);
        $event->trigger();

        redirect($pageurl, get_string('token_revoked', 'local_placecom_mcp'), null, \core\output\notification::NOTIFY_SUCCESS);
    } else {
        redirect($pageurl, get_string('token_not_found', 'local_placecom_mcp'), null, \core\output\notification::NOTIFY_ERROR);
    }
}

echo $OUTPUT->header();

$tokens = $DB->get_records_sql(
    "SELECT t.id, t.client_id, t.user_id, t.expires, u.username
       FROM {local_placecom_mcp_access_token} t
       JOIN {user} u ON u.id = t.user_id
   ORDER BY t.expires DESC"
);

if (empty($tokens)) {
    echo $OUTPUT->notification(get_string('no_active_tokens', 'local_placecom_mcp'), 'info');
} else {
    $table = new html_table();
    $table->head = [
        get_string('token_user', 'local_placecom_mcp'),
        get_string('token_client', 'local_placecom_mcp'),
        get_string('token_expires', 'local_placecom_mcp'),
        '',
    ];

    foreach ($tokens as $t) {
        $expired = $t->expires < time();
        $expiresstr = userdate($t->expires) . ($expired ? ' (' . get_string('expired', 'local_placecom_mcp') . ')' : '');

        $revokeurl = new moodle_url('/local/placecom_mcp/manage_tokens.php', [
            'action' => 'revoke',
            'id' => $t->id,
            'sesskey' => sesskey(),
        ]);
        $revokelink = html_writer::link(
            $revokeurl,
            get_string('revoke', 'local_placecom_mcp'),
            ['onclick' => 'return confirm(' . json_encode(get_string('revoke_confirm', 'local_placecom_mcp')) . ');']
        );

        $table->data[] = [
            s($t->username),
            s($t->client_id),
            $expiresstr,
            $revokelink,
        ];
    }

    echo html_writer::table($table);
}

echo $OUTPUT->footer();