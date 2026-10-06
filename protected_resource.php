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
 * OAuth Protected Resource Metadata endpoint (RFC 9728).
 *
 * @package    local_placecom_mcp
 * @copyright  2026 AlmaBay Networks Pvt. Ltd.
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// This is a public discovery document (RFC 9728) that must be readable before any sign-in. It contains
// only this site's own addresses and no user data, so there is nothing to protect with a login.
// phpcs:ignore moodle.Files.RequireLogin.Missing -- Public discovery document, contains no user data.
require(__DIR__ . '/../../config.php');

header('Content-Type: application/json');
echo json_encode([
    'resource' => $CFG->wwwroot . '/local/placecom_mcp/server.php',
    'authorization_servers' => [$CFG->wwwroot],
]);
