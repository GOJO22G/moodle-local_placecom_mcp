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
 * Plugin capabilities.
 *
 * Merges capabilities previously split across local_oauth2
 * (local/oauth2:manage_oauth_clients) and webservice_mcp (webservice/mcp:use).
 *
 * @package    local_placecom_mcp
 * @copyright  2026 AlmaBay Networks Pvt. Ltd. (Placecom)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$capabilities = [
    // Formerly webservice/mcp:use. Granted to Authenticated user on install (see db/install.php)
    // so any logged-in user's bridged token can reach the MCP endpoint; Moodle's normal
    // per-function capability checks still apply downstream.
    'local/placecom_mcp:use' => [
        'captype' => 'read',
        'contextlevel' => CONTEXT_SYSTEM,
        'archetypes' => [],
    ],

    // Formerly local/oauth2:manage_oauth_clients. Admin-only: register/edit OAuth clients.
    'local/placecom_mcp:manage_oauth_clients' => [
        'riskbitmask' => RISK_SPAM | RISK_PERSONAL | RISK_XSS,
        'captype' => 'write',
        'contextlevel' => CONTEXT_SYSTEM,
        'archetypes' => [
            'manager' => CAP_ALLOW,
        ],
    ],
];
