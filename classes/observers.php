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
 * Event observer that mirrors local_placecom_mcp access tokens into
 * external_tokens, so an OAuth token becomes a valid wstoken.
 *
 * @package    local_placecom_mcp
 * @copyright  2026 AlmaBay Networks Pvt. Ltd.
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_placecom_mcp;

defined('MOODLE_INTERNAL') || die();

class observers {

    /**
     * Resolve which external service this plugin should bridge tokens into.
     *
     * Prefers an explicit admin-configured serviceid (backward compatible
     * with existing installs), and falls back to the service auto-created
     * on install (see db/install.php) when no override is set.
     */
    private static function resolve_service_id() {
        global $DB;

        $configured = get_config('local_placecom_mcp', 'serviceid');
        if (!empty($configured) && $DB->record_exists('external_services', ['id' => $configured])) {
            return $configured;
        }

        $service = $DB->get_record('external_services', ['shortname' => 'placecom_mcp_service']);
        return $service ? $service->id : null;
    }

    /**
     * Fires when local_placecom_mcp creates or updates an access token for a user.
     * We mirror that same token string into external_tokens, scoped to
     * whichever web service this plugin is configured to bridge (see
     * settings.php) - so the OAuth token becomes a valid wstoken for that
     * service, without needing a separate admin-generated token per user.
     */
    public static function handle_access_token_created_or_updated($event) {
        global $DB;

        // Note: the original local_mcpbridge version of this method called a
        // defensive lib.php helper here to re-seed the moodle_mcp_read/write
        // scope names, in case they didn't exist yet (install order between
        // SEPARATE plugins wasn't guaranteed in the old 3-plugin setup). That
        // helper was deliberately not ported (see db/install.php's docblock) -
        // this plugin's own install.php now seeds all 8 scopes, including
        // these two, unconditionally at install time, so by the time this
        // plugin can even be handling a login event, its own install has
        // already run and its own scope table is already fully seeded. No
        // re-seeding call is needed here anymore.

        $data = $event->get_data();
        $userid     = $data['userid'];
        $token      = $data['other']['accesstoken'];
        $validuntil = $data['other']['expires'];
        $scope      = $data['other']['scope'] ?? '';

        $externalserviceid = self::resolve_service_id();
        if (empty($externalserviceid)) {
            debugging('local_placecom_mcp: no serviceid configured or auto-created, skipping token bridge', DEBUG_DEVELOPER);
            return;
        }

        $existing = $DB->get_record('external_tokens', [
            'token' => $token,
            'externalserviceid' => $externalserviceid,
        ]);

        if ($existing) {
            $existing->token = $token;
            $existing->validuntil = $validuntil;
            $existing->timecreated = time();
            $DB->update_record('external_tokens', $existing);
        } else {
            $record = new \stdClass();
            $record->token = $token;
            $record->tokentype = EXTERNAL_TOKEN_PERMANENT;
            $record->userid = $userid;
            $record->externalserviceid = $externalserviceid;
            $record->contextid = \context_system::instance()->id;
            $record->creatorid = $userid;
            $record->validuntil = $validuntil;
            $record->timecreated = time();
            $record->iprestriction = '';
            $DB->insert_record('external_tokens', $record);
        }

        // Record the OAuth scope granted for this bridged token, so
        // local_placecom_mcp can enforce read/write permissions later without
        // needing to match against local_placecom_mcp's own token table (which
        // uses a different token string entirely).
        $scoperow = $DB->get_record('local_placecom_mcp_token_scope', ['token' => $token]);

        if ($scoperow) {
            $scoperow->scope = $scope;
            $scoperow->timecreated = time();
            $DB->update_record('local_placecom_mcp_token_scope', $scoperow);
        } else {
            $scoperecord = new \stdClass();
            $scoperecord->token = $token;
            $scoperecord->scope = $scope;
            $scoperecord->timecreated = time();
            $DB->insert_record('local_placecom_mcp_token_scope', $scoperecord);
        }
    }

    /**
     * Fires when an admin manually revokes an OAuth access token via
     * local_placecom_mcp's manage_tokens.php page. Immediately deletes the
     * matching bridged token(s) and their scope rows, rather than leaving
     * them to be cleaned up later by cleanup_orphaned_scope's scheduled
     * task or core's own lazy on-use expiry check - a deliberately revoked
     * session should stop working right away, not just eventually.
     *
     * Deliberately does NOT match by token string. local_placecom_mcp stores
     * access_token as a SHA-256 hash (see moodle_oauth_storage.php), never
     * the plaintext - so there is no plaintext value available here to
     * match against external_tokens/local_placecom_mcp_token_scope, which
     * both store the plaintext bridged token. Instead, revokes by user +
     * service: every currently bridged token this user has for OUR
     * service gets deleted. Deliberately broader than "just this one
     * session" - erring toward revoking too much rather than too little,
     * same fail-closed principle used in enforce_scope().
     */
    public static function handle_access_token_revoked($event) {
        global $DB;

        $userid = $event->relateduserid;

        if (empty($userid)) {
            debugging('local_placecom_mcp: access_token_revoked event received with no relateduserid, skipping', DEBUG_DEVELOPER);
            return;
        }

        $externalserviceid = self::resolve_service_id();
        if (empty($externalserviceid)) {
            return;
        }

        $bridgedtokens = $DB->get_records('external_tokens', [
            'userid' => $userid,
            'externalserviceid' => $externalserviceid,
        ]);

        foreach ($bridgedtokens as $bridgedtoken) {
            $DB->delete_records('local_placecom_mcp_token_scope', ['token' => $bridgedtoken->token]);
        }

        $DB->delete_records('external_tokens', [
            'userid' => $userid,
            'externalserviceid' => $externalserviceid,
        ]);
    }
}