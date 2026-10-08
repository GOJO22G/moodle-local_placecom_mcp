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

defined('MOODLE_INTERNAL') || die();

/**
 * Tests that requested scopes are restricted to the scopes registered for the client.
 *
 * @package     local_placecom_mcp
 * @copyright   2026 AlmaBay Networks Pvt. Ltd.
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers      \local_placecom_mcp\utils::restrict_scope_to_client
 */
final class restrict_scope_test extends \advanced_testcase {
    /** @var string The scopes an assistant such as Claude asks for. */
    private const ASKED = 'openid profile email moodle_mcp_read moodle_mcp_write';

    /**
     * Register a test client.
     *
     * @param string $clientid The client id.
     * @param string|null $scope The registered scopes, or null for none.
     */
    private function add_client(string $clientid, ?string $scope): void {
        global $DB;

        $client = new \stdClass();
        $client->client_id = $clientid;
        $client->client_secret = 'unused';
        $client->redirect_uri = 'https://example.com/callback';
        $client->scope = $scope;
        $client->require_pkce = 0;
        $DB->insert_record('local_placecom_mcp_client', $client);
    }

    /**
     * A scope the client is not registered for is dropped, and the rest is kept.
     */
    public function test_unregistered_scope_is_dropped(): void {
        $this->resetAfterTest(true);
        $this->add_client('readonly', 'openid profile email moodle_mcp_read');

        $this->assertSame(
            'openid profile email moodle_mcp_read',
            utils::restrict_scope_to_client('readonly', self::ASKED)
        );
    }

    /**
     * A client registered for everything gets the original string back unchanged.
     */
    public function test_fully_registered_client_is_unchanged(): void {
        $this->resetAfterTest(true);
        $this->add_client('full', self::ASKED);

        $this->assertSame(self::ASKED, utils::restrict_scope_to_client('full', self::ASKED));
    }

    /**
     * A request with no registered scope is returned unchanged so the library still rejects it.
     */
    public function test_only_unknown_scopes_are_unchanged(): void {
        $this->resetAfterTest(true);
        $this->add_client('readonly', 'openid moodle_mcp_read');

        $this->assertSame('address phone', utils::restrict_scope_to_client('readonly', 'address phone'));
    }

    /**
     * Nothing requested returns the original value.
     */
    public function test_no_scope_requested_is_unchanged(): void {
        $this->resetAfterTest(true);
        $this->add_client('readonly', 'openid moodle_mcp_read');

        $this->assertFalse(utils::restrict_scope_to_client('readonly', false));
    }

    /**
     * A client with no registered scopes returns the original value.
     */
    public function test_client_without_scopes_is_unchanged(): void {
        $this->resetAfterTest(true);
        $this->add_client('noscopes', null);

        $this->assertSame(self::ASKED, utils::restrict_scope_to_client('noscopes', self::ASKED));
    }
}
