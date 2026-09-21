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
 * Plugin version information.
 *
 * A single "local" plugin combining OAuth2 authorization server, OAuth-to-webservice
 * token bridging, and MCP (Model Context Protocol) server functionality for Moodle.
 * Replaces the previously separate local_oauth2, local_mcpbridge, and webservice_mcp
 * plugins with one unified codebase.
 *
 * @package    local_placecom_mcp
 * @copyright  2026 AlmaBay Networks Pvt. Ltd. (Placecom)
 * @copyright  based on work by 2025-2026 Enovation Solutions (local_oauth2)
 * @copyright  based on work by 2025 MohammadReza PourMohammad (webservice_mcp)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$plugin->component = 'local_placecom_mcp';
$plugin->version    = 2026092100;
$plugin->requires   = 2024100700; // Moodle 4.5+.
$plugin->maturity   = MATURITY_ALPHA; // Bump as it stabilises through phases 2-7.
$plugin->release    = '0.1.0';
