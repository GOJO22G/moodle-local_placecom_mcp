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
 * Post-install steps.
 *
 * @package    local_placecom_mcp
 * @copyright  2026 AlmaBay Networks Pvt. Ltd. (Placecom)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Auto-grant local/placecom_mcp:use to the Authenticated user role on install,
 * so every logged-in user's bridged token can reach the MCP endpoint with zero
 * manual per-user or per-role setup. Moodle's normal per-function capability
 * checks still apply downstream (see webservice_mcp's function allowlist).
 */
function xmldb_local_placecom_mcp_install() {
    global $DB;

    // Looked up by archetype, not shortname: finds the right role even if an
    // admin has renamed "Authenticated user" on their site. (This matches the
    // already-validated lookup pattern from local_mcpbridge's install.php.)
    $role = $DB->get_record('role', ['archetype' => 'user'], '*', IGNORE_MISSING);

    if (!$role) {
        debugging('local_placecom_mcp install: could not find Authenticated user role, skipping auto-grant', DEBUG_DEVELOPER);
    } else {
        $context = context_system::instance();
        assign_capability('local/placecom_mcp:use', CAP_ALLOW, $role->id, $context->id, true);
        $context->mark_dirty();
    }
}
