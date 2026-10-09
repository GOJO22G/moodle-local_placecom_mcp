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

use advanced_testcase;
use local_placecom_mcp\local\server;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/webservice/lib.php');

/**
 * Tests that the site info reply is cut down to a short list of fields.
 *
 * @package     local_placecom_mcp
 * @copyright   2026 AlmaBay Networks Pvt. Ltd.
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers      \local_placecom_mcp\local\server::restrict_site_info
 */
final class site_info_filter_test extends advanced_testcase {
    /**
     * The fields an assistant may see are kept, and credentials and site details are removed.
     */
    public function test_only_allowed_fields_are_returned(): void {
        $reply = [
            'sitename' => 'Test site',
            'username' => 'student1',
            'firstname' => 'Stu',
            'lastname' => 'Dent',
            'fullname' => 'Stu Dent',
            'userid' => 111,
            'userprivateaccesskey' => 'secretkey123',
            'functions' => [['name' => 'core_course_get_courses_by_field', 'version' => '2024100700']],
            'userissiteadmin' => false,
            'siteurl' => 'https://example.com/moodle',
            'userpictureurl' => 'https://example.com/pluginfile.php/1/user/icon/f1',
            'release' => '4.5',
        ];

        $filtered = server::restrict_site_info($reply);

        $this->assertEqualsCanonicalizing(
            ['sitename', 'username', 'firstname', 'lastname', 'fullname', 'userid'],
            array_keys($filtered)
        );
        $this->assertArrayNotHasKey('userprivateaccesskey', $filtered);
        $this->assertArrayNotHasKey('functions', $filtered);
        $this->assertSame('student1', $filtered['username']);
        $this->assertSame(111, $filtered['userid']);
    }

    /**
     * A reply with missing fields is filtered without an error.
     */
    public function test_missing_fields_are_tolerated(): void {
        $filtered = server::restrict_site_info(['username' => 'student1', 'userprivateaccesskey' => 'secretkey123']);

        $this->assertSame(['username' => 'student1'], $filtered);
    }

    /**
     * An object reply is accepted as well as an array.
     */
    public function test_object_reply_is_accepted(): void {
        $reply = (object) ['username' => 'student1', 'userprivateaccesskey' => 'secretkey123'];

        $this->assertSame(['username' => 'student1'], server::restrict_site_info($reply));
    }
}
