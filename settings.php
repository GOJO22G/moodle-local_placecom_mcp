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
 * Plugin configuration.
 *
 * @package local_placecom_mcp
 * @author Pau Ferrer OcaÃ±a <pferre22@xtec.cat>
 * @author Lai Wei <lai.wei@enovation.ie>
 * @author Dorel Manolescu <dorel.manolescu@enovation.ie>
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @copyright (C) 2025 Enovation Solutions
 * @copyright 2026 AlmaBay Networks Pvt. Ltd. (Placecom) - namespace/table renames, PKCE param fix
 */

defined('MOODLE_INTERNAL') || die();

if ($hassiteconfig) {
    // Add a section for the plugin configurations in the "Local plugins" section.
    $ADMIN->add('server', new admin_category('local_placecom_mcp', get_string('pluginname', 'local_placecom_mcp')));

    // Add OAuth provider settings to the "Server" section.
    $ADMIN->add(
        'local_placecom_mcp',
        new admin_externalpage(
            'local_placecom_mcp_manage_oauth_clients',
            get_string('manage_oauth_clients', 'local_placecom_mcp'),
            new moodle_url('/local/placecom_mcp/manage_oauth_clients.php'),
            'local/placecom_mcp:manage_oauth_clients'
        )
    );

    // Add token management page.
    $ADMIN->add(
        'local_placecom_mcp',
        new admin_externalpage(
            'local_placecom_mcp_manage_tokens',
            get_string('manage_tokens', 'local_placecom_mcp'),
            new moodle_url('/local/placecom_mcp/manage_tokens.php'),
            'local/placecom_mcp:manage_oauth_clients'
        )
    );

    // Add plugin configuration page.
    $settings = new admin_settingpage('local_placecom_mcp_token_lifetime', get_string('settings_token_settings', 'local_placecom_mcp'));
    $ADMIN->add('local_placecom_mcp', $settings);

    // Master on/off switch for the MCP server endpoint (server.php).
    // Replaces webservice_mcp's old on/off control, which was the
    // Site Admin > Server > Web services > Manage protocols checkbox -
    // that mechanism only exists for "webservice" type plugins and stopped
    // existing once this became a "local" type plugin during the merge.
    $settings->add(
        new admin_setting_configcheckbox(
            'local_placecom_mcp/enable_mcp_server',
            get_string('settings_enable_mcp_server', 'local_placecom_mcp'),
            get_string('settings_enable_mcp_server_desc', 'local_placecom_mcp'),
            1
        )
    );

    // Access token timeout period.
    $settings->add(
        new admin_setting_configduration(
            'local_placecom_mcp/access_token_lifetime',
            get_string('settings_access_token_lifetime', 'local_placecom_mcp'),
            get_string('settings_access_token_lifetime_desc', 'local_placecom_mcp'),
            HOURSECS,
            HOURSECS
        )
    );

    // Refresh token timeout period.
    $settings->add(
        new admin_setting_configduration(
            'local_placecom_mcp/refresh_token_lifetime',
            get_string('settings_refresh_token_lifetime', 'local_placecom_mcp'),
            get_string('settings_refresh_token_lifetime_desc', 'local_placecom_mcp'),
            WEEKSECS,
            WEEKSECS
        )
    );

    // Optional issuer override for reverse proxy or externally published OIDC metadata.
    $settings->add(
        new admin_setting_configtext(
            'local_placecom_mcp/issuer',
            get_string('settings_issuer', 'local_placecom_mcp'),
            get_string('settings_issuer_desc', 'local_placecom_mcp'),
            '',
            PARAM_URL
        )
    );
}
