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
 * MCP web service entry point.
 *
 * This is the main entry point for the MCP (Model Context Protocol) web service.
 * Authentication is performed via tokens, supporting both Bearer tokens in the
 * Authorization header and token parameters in the URL.
 *
 * @package     local_placecom_mcp
 * @author      MohammadReza PourMohammad <onbirdev@gmail.com>
 * @copyright   2025 MohammadReza PourMohammad
 * @copyright   2026 AlmaBay Networks Pvt. Ltd. (Placecom) - moved from webservice/mcp
 *              to local/placecom_mcp as part of the 3-plugin merge; protocol-enable
 *              check replaced with a plugin setting (see below)
 * @link        https://onbir.dev
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// Disable Moodle-specific debug messages and error output.
define('NO_DEBUG_DISPLAY', true);

// Mark this as a web service server script.
define('WS_SERVER', true);

require('../../config.php');

// Check if the MCP server is enabled via this plugin's own setting.
//
// This used to check webservice_protocol_is_enabled('mcp') - Moodle's registry
// of enabled webservice PROTOCOLS, which only exists for plugins of type
// "webservice". That mechanism doesn't exist for this plugin anymore now that
// it's type "local" (part of the merge from webservice_mcp + local_oauth2 +
// local_mcpbridge into one plugin) - "mcp" is no longer a protocol Moodle
// knows about at all, so the old check would have silently made this endpoint
// permanently return 403 to everyone, forever, regardless of any setting.
// Replaced with a normal plugin setting instead (Site Admin > Local plugins >
// Placecom MCP Connector > "Enable MCP server"), defaulting to enabled.
if (!get_config('local_placecom_mcp', 'enable_mcp_server')) {
    header("HTTP/1.0 403 Forbidden");
    debugging(
        'The server died because the MCP server is disabled in local_placecom_mcp settings',
        DEBUG_DEVELOPER
    );
    die;
}

// Instantiate and run the MCP server.
$server = new \local_placecom_mcp\local\server(WEBSERVICE_AUTHMETHOD_PERMANENT_TOKEN);
$server->run();
die;
