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

namespace local_placecom_mcp\form;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->libdir . '/formslib.php');

/**
 * Tests the validation rules of the OAuth client form.
 *
 * @package     local_placecom_mcp
 * @copyright   2026 AlmaBay Networks Pvt. Ltd.
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers      \local_placecom_mcp\form\oauth_client_form::validation
 */
final class oauth_client_form_test extends \advanced_testcase {
    /**
     * Run the form validation on a set of values.
     *
     * @param array $overrides Values that replace the valid defaults.
     * @return array The validation errors, keyed by field name.
     */
    private function validate(array $overrides): array {
        $this->resetAfterTest(true);
        $_POST['action'] = 'add';
        $_GET['action'] = 'add';

        $data = $overrides + [
            'action' => 'add',
            'client_id' => 'form-test-client',
            'redirect_uri' => 'https://example.com/callback',
            'scope' => 'openid moodle_mcp_read',
        ];

        $form = new oauth_client_form();
        return $form->validation($data, []);
    }

    /**
     * A blank redirect URI is refused.
     */
    public function test_blank_redirect_is_refused(): void {
        $errors = $this->validate(['redirect_uri' => '']);
        $this->assertArrayHasKey('redirect_uri', $errors);
    }

    /**
     * A blank scope is refused.
     */
    public function test_blank_scope_is_refused(): void {
        $errors = $this->validate(['scope' => '']);
        $this->assertArrayHasKey('scope', $errors);
    }

    /**
     * A scope that does not include moodle_mcp_read is refused.
     */
    public function test_scope_without_read_is_refused(): void {
        $errors = $this->validate(['scope' => 'openid profile moodle_mcp_write']);
        $this->assertArrayHasKey('scope', $errors);
    }

    /**
     * A valid scope set is accepted.
     */
    public function test_valid_scope_set_is_accepted(): void {
        $errors = $this->validate(['scope' => 'openid profile email offline_access moodle_mcp_read']);
        $this->assertArrayNotHasKey('scope', $errors);
        $this->assertArrayNotHasKey('redirect_uri', $errors);
    }

    /**
     * Read plus write is accepted.
     */
    public function test_read_plus_write_is_accepted(): void {
        $errors = $this->validate(['scope' => 'openid moodle_mcp_read moodle_mcp_write']);
        $this->assertArrayNotHasKey('scope', $errors);
    }

    /**
     * A plain http redirect is refused, but http://localhost is allowed.
     */
    public function test_redirect_scheme_rules(): void {
        $this->assertArrayHasKey('redirect_uri', $this->validate(['redirect_uri' => 'http://example.com/callback']));
        $this->assertArrayNotHasKey('redirect_uri', $this->validate(['redirect_uri' => 'http://localhost:8080/callback']));
    }
}
