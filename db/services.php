<?php
// This file is part of Moodle - http://moodle.org/
//
// local_placecom_mcp - external service definition.
// On plugin install/upgrade, Moodle reads this file automatically and
// creates (or updates) the declared service with its function list
// already attached - no manual admin UI step required.
//
// Ported from local_mcpbridge's db/services.php, renamed from
// "MCP Bridge Service" / mcpbridge_service to reflect that this is no
// longer a separate bridge plugin - it's this plugin's own service.

defined('MOODLE_INTERNAL') || die();

// The function list is read directly from
// local_placecom_mcp\local\approved_functions::LIST - the single canonical
// list also used by tool_provider's tools/list filter and enforce_scope()'s
// execution gate. Keeping one array in one place means there is no second,
// separately-maintained copy that could drift out of sync with what the
// MCP server actually allows.
//
// To add/remove a function: edit approved_functions::LIST in
// classes/local/approved_functions.php - NOT here.
//
// IMPORTANT: after that list changes, bump $plugin->version in version.php.
// Moodle only re-syncs a plugin's declared service when it detects a version
// change and runs the upgrade step - the list changing alone, with no
// version bump, has no effect on an already-installed site.
$approvedfunctionnames = \local_placecom_mcp\local\approved_functions::LIST;

$services = [
    'Placecom MCP Service' => [
        'functions'       => $approvedfunctionnames,
        'restrictedusers' => 0,   // any authorised user's bridged token works - no per-user manual authorisation step
        'enabled'         => 1,   // on immediately after install, no manual toggle
        'shortname'       => 'placecom_mcp_service',
        'downloadfiles'   => 1,   // needed for resource extraction
        'uploadfiles'     => 0,
    ],
];
