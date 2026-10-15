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

namespace local_placecom_mcp;

/**
 * Tests that deleting an OAuth client also removes the credentials issued to it.
 *
 * @package     local_placecom_mcp
 * @copyright   2026 AlmaBay Networks Pvt. Ltd.
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers      \local_placecom_mcp\utils::delete_client
 * @covers      \local_placecom_mcp\observers::revoke_bridged_tokens_for_user
 */
final class delete_client_test extends \advanced_testcase {
    /** @var string[] Tables keyed by client_id that this test fills with rows. */
    private const CLIENT_TABLES = [
        'local_placecom_mcp_access_token',
        'local_placecom_mcp_refresh_token',
        'local_placecom_mcp_user_auth_scope',
    ];

    /**
     * Register a test client.
     *
     * @param string $clientid The client id.
     * @return int The id of the new client row.
     */
    private function add_client(string $clientid): int {
        global $DB;

        $client = new \stdClass();
        $client->client_id = $clientid;
        $client->client_secret = 'unused';
        $client->redirect_uri = 'https://example.com/callback';
        $client->scope = 'openid moodle_mcp_read';
        $client->require_pkce = 0;
        return $DB->insert_record('local_placecom_mcp_client', $client);
    }

    /**
     * Give a client an access token, a refresh token and a stored consent for a user.
     *
     * @param string $clientid The client id.
     * @param int $userid The user the credentials belong to.
     */
    private function add_credentials(string $clientid, int $userid): void {
        global $DB;

        $DB->insert_record('local_placecom_mcp_access_token', (object) [
            'access_token' => hash('sha256', 'access-' . $clientid),
            'client_id' => $clientid,
            'user_id' => $userid,
            'expires' => time() + HOURSECS,
            'scope' => 'openid moodle_mcp_read',
        ]);
        $DB->insert_record('local_placecom_mcp_refresh_token', (object) [
            'refresh_token' => hash('sha256', 'refresh-' . $clientid),
            'client_id' => $clientid,
            'user_id' => $userid,
            'expires' => time() + WEEKSECS,
            'scope' => 'openid moodle_mcp_read offline_access',
        ]);
        $DB->insert_record('local_placecom_mcp_user_auth_scope', (object) [
            'client_id' => $clientid,
            'user_id' => $userid,
            'scope' => 'openid moodle_mcp_read',
        ]);
    }

    /**
     * Create the web service the plugin bridges tokens to, and point the plugin at it.
     *
     * @return int The web service id.
     */
    private function create_service(): int {
        global $DB;

        $serviceid = $DB->insert_record('external_services', (object) [
            'name' => 'Delete client test service',
            'enabled' => 1,
            'requiredcapability' => '',
            'restrictedusers' => 0,
            'component' => 'local_placecom_mcp',
            'timecreated' => time(),
            'timemodified' => time(),
            'shortname' => 'delete_client_test_service',
            'downloadfiles' => 0,
            'uploadfiles' => 0,
        ]);
        set_config('serviceid', $serviceid, 'local_placecom_mcp');
        return $serviceid;
    }

    /**
     * Give a user a bridged web service token and its scope row.
     *
     * @param int $userid The user.
     * @param int $serviceid The web service id.
     * @param string $token The plaintext bridged token.
     */
    private function add_bridged_token(int $userid, int $serviceid, string $token): void {
        global $DB;

        $DB->insert_record('external_tokens', (object) [
            'token' => $token,
            'tokentype' => 0,
            'userid' => $userid,
            'externalserviceid' => $serviceid,
            'contextid' => \context_system::instance()->id,
            'creatorid' => $userid,
            'timecreated' => time(),
        ]);
        $DB->insert_record('local_placecom_mcp_token_scope', (object) [
            'token' => $token,
            'scope' => 'moodle_mcp_read',
            'timecreated' => time(),
        ]);
    }

    /**
     * Deleting a client removes the client row and every credential keyed by its client id.
     */
    public function test_delete_removes_client_and_its_credentials(): void {
        global $DB;

        $this->resetAfterTest(true);
        $user = $this->getDataGenerator()->create_user();
        $id = $this->add_client('doomed');
        $this->add_credentials('doomed', $user->id);

        $this->assertTrue(utils::delete_client($id));

        $this->assertFalse($DB->record_exists('local_placecom_mcp_client', ['id' => $id]));
        foreach (self::CLIENT_TABLES as $table) {
            $this->assertSame(0, $DB->count_records($table, ['client_id' => 'doomed']), $table);
        }
    }

    /**
     * Deleting a client revokes the bridged web service tokens of the users it served.
     */
    public function test_delete_revokes_bridged_tokens(): void {
        global $DB;

        $this->resetAfterTest(true);
        $serviceid = $this->create_service();
        $user = $this->getDataGenerator()->create_user();
        $id = $this->add_client('doomed');
        $this->add_credentials('doomed', $user->id);
        $this->add_bridged_token($user->id, $serviceid, 'bridged-doomed');

        $this->assertTrue(utils::delete_client($id));

        $this->assertSame(0, $DB->count_records('external_tokens', [
            'userid' => $user->id,
            'externalserviceid' => $serviceid,
        ]));
        $this->assertSame(0, $DB->count_records('local_placecom_mcp_token_scope', ['token' => 'bridged-doomed']));
    }

    /**
     * Deleting one client leaves the credentials of another client, used by other users, alone.
     */
    public function test_delete_leaves_other_clients_untouched(): void {
        global $DB;

        $this->resetAfterTest(true);
        $serviceid = $this->create_service();
        $gone = $this->getDataGenerator()->create_user();
        $kept = $this->getDataGenerator()->create_user();
        $doomedid = $this->add_client('doomed');
        $this->add_client('survivor');
        $this->add_credentials('doomed', $gone->id);
        $this->add_credentials('survivor', $kept->id);
        $this->add_bridged_token($gone->id, $serviceid, 'bridged-doomed');
        $this->add_bridged_token($kept->id, $serviceid, 'bridged-survivor');

        $this->assertTrue(utils::delete_client($doomedid));

        $this->assertTrue($DB->record_exists('local_placecom_mcp_client', ['client_id' => 'survivor']));
        foreach (self::CLIENT_TABLES as $table) {
            $this->assertSame(1, $DB->count_records($table, ['client_id' => 'survivor']), $table);
        }
        $this->assertTrue($DB->record_exists('external_tokens', ['token' => 'bridged-survivor']));
        $this->assertTrue($DB->record_exists('local_placecom_mcp_token_scope', ['token' => 'bridged-survivor']));
    }

    /**
     * Deleting a client that does not exist reports failure and changes nothing.
     */
    public function test_delete_missing_client_returns_false(): void {
        $this->resetAfterTest(true);

        $this->assertFalse(utils::delete_client(999999));
    }
}
