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

use local_placecom_mcp\local\approved_functions;
use local_placecom_mcp\local\tool_provider;

global $CFG;
require_once($CFG->dirroot . '/webservice/tests/helpers.php');

/**
 * Tests that tools/list hides write tools from tokens without the moodle_mcp_write scope.
 *
 * @package     local_placecom_mcp
 * @copyright   2026 AlmaBay Networks Pvt. Ltd.
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers      \local_placecom_mcp\local\tool_provider
 */
final class tool_provider_scope_test extends \externallib_advanced_testcase {
    /** @var string[] The two write functions on the allowlist. */
    private const WRITE_FUNCTIONS = [
        'mod_forum_add_discussion',
        'mod_forum_add_discussion_post',
    ];

    /**
     * Create an external service holding every approved function.
     *
     * @return int The service id.
     */
    private function create_service(): int {
        global $DB;

        $service = new \stdClass();
        $service->name = 'Test MCP Scope Service';
        $service->enabled = 1;
        $service->restrictedusers = 0;
        $service->component = null;
        $service->timecreated = time();
        $service->timemodified = time();
        $service->shortname = 'test_mcp_scope_' . bin2hex(random_bytes(4));
        $service->downloadfiles = 0;
        $service->uploadfiles = 0;
        $serviceid = $DB->insert_record('external_services', $service);

        foreach (approved_functions::LIST as $functionname) {
            $function = new \stdClass();
            $function->externalserviceid = $serviceid;
            $function->functionname = $functionname;
            $DB->insert_record('external_services_functions', $function);
        }

        return $serviceid;
    }

    /**
     * Create a token for a service, optionally with a recorded OAuth scope.
     *
     * @param int $serviceid The service id.
     * @param string|null $scope Space-separated scope, or null for no scope record.
     * @return string The token string.
     */
    private function create_token(int $serviceid, ?string $scope): string {
        global $DB, $USER;

        $token = new \stdClass();
        $token->token = bin2hex(random_bytes(32));
        $token->userid = $USER->id;
        $token->tokentype = EXTERNAL_TOKEN_PERMANENT;
        $token->contextid = \context_system::instance()->id;
        $token->creatorid = $USER->id;
        $token->timecreated = time();
        $token->externalserviceid = $serviceid;
        $DB->insert_record('external_tokens', $token);

        if ($scope !== null) {
            $scoperecord = new \stdClass();
            $scoperecord->token = $token->token;
            $scoperecord->scope = $scope;
            $scoperecord->timecreated = time();
            $DB->insert_record('local_placecom_mcp_token_scope', $scoperecord);
        }

        return $token->token;
    }

    /**
     * A token whose scope lacks moodle_mcp_write sees the 22 read tools only.
     */
    public function test_read_only_scope_hides_write_tools(): void {
        $this->resetAfterTest(true);
        $this->setAdminUser();

        $token = $this->create_token($this->create_service(), 'openid profile email');
        $names = array_column(tool_provider::get_tools($token), 'name');

        $this->assertCount(22, $names);
        foreach (self::WRITE_FUNCTIONS as $writefunction) {
            $this->assertNotContains($writefunction, $names);
        }
    }

    /**
     * A token with moodle_mcp_write sees all 24 tools, including both write tools.
     */
    public function test_write_scope_shows_all_tools(): void {
        $this->resetAfterTest(true);
        $this->setAdminUser();

        $token = $this->create_token($this->create_service(), 'openid profile moodle_mcp_write');
        $names = array_column(tool_provider::get_tools($token), 'name');

        $this->assertCount(24, $names);
        foreach (self::WRITE_FUNCTIONS as $writefunction) {
            $this->assertContains($writefunction, $names);
        }
    }

    /**
     * A token with no scope record fails closed: write tools are hidden.
     */
    public function test_no_scope_record_hides_write_tools(): void {
        $this->resetAfterTest(true);
        $this->setAdminUser();

        $token = $this->create_token($this->create_service(), null);
        $names = array_column(tool_provider::get_tools($token), 'name');

        $this->assertCount(22, $names);
        foreach (self::WRITE_FUNCTIONS as $writefunction) {
            $this->assertNotContains($writefunction, $names);
        }
    }
}