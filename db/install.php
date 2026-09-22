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
 * Ported from local_oauth2's own db/install.php (seeds default OpenID Connect
 * scopes, plus the moodle_mcp_read/write scopes originally added for MCP
 * integration; generates a default RSA key pair for OIDC ID token signing).
 * Does NOT include a capability grant: that used to be done here too (for
 * local/placecom_mcp:use) but crashed install, since a plugin's own
 * capabilities aren't registered until after its install.php runs. That grant
 * now lives in db/access.php's 'archetypes' instead, which Moodle applies at
 * the correct point in the sequence. See git history for that fix.
 *
 * Also deliberately does NOT port local_mcpbridge's on-login defensive scope
 * re-seeding (lib.php's local_mcpbridge_seed_oauth_scopes(), called from
 * classes/observers.php on every access_token_created/updated event). That
 * existed only to work around install-order not being guaranteed *between
 * separate plugins* in the old 3-plugin setup - moodle_mcp_read/write could
 * be needed before local_oauth2's own install.php had run yet. With OAuth2
 * and the MCP scopes now seeded by the same plugin's own install.php, that
 * ordering problem no longer exists, so the defensive fallback is dropped as
 * unneeded rather than ported.
 *
 * @package    local_placecom_mcp
 * @copyright  2026 AlmaBay Networks Pvt. Ltd. (Placecom)
 * @copyright  based on work by 2026 Enovation Solutions (local_oauth2)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Post installation hook: seeds default OAuth2/OIDC scopes and generates the
 * default RSA key pair used to sign OpenID Connect ID tokens.
 *
 * @return bool
 */
function xmldb_local_placecom_mcp_install() {
    global $DB;

    // Default OpenID Connect scopes, plus the two MCP-specific scopes that
    // must exist at install time (not only after a first login) so a client
    // can request MCP read/write permission on its very first OAuth
    // authorization on a genuinely fresh site.
    $defaultscopes = [
        ['scope' => 'openid', 'is_default' => 1],
        ['scope' => 'profile', 'is_default' => 0],
        ['scope' => 'email', 'is_default' => 0],
        ['scope' => 'offline_access', 'is_default' => 0],
        ['scope' => 'address', 'is_default' => 0],
        ['scope' => 'phone', 'is_default' => 0],
        ['scope' => 'moodle_mcp_read', 'is_default' => 0],
        ['scope' => 'moodle_mcp_write', 'is_default' => 0],
    ];

    foreach ($defaultscopes as $scopedata) {
        if (!$DB->record_exists('local_placecom_mcp_scope', ['scope' => $scopedata['scope']])) {
            $record = (object) $scopedata;
            $DB->insert_record('local_placecom_mcp_scope', $record);
        }
    }

    // Generate RSA key pair for OpenID Connect ID token signing.
    // Empty client_id represents default keys for all clients.
    if (!$DB->record_exists('local_placecom_mcp_public_key', ['client_id' => ''])) {
        $config = [
            'private_key_bits' => 2048,
            'private_key_type' => OPENSSL_KEYTYPE_RSA,
        ];

        $res = openssl_pkey_new($config);
        if ($res === false) {
            debugging('local_placecom_mcp install: failed to generate RSA key pair: '
                . openssl_error_string(), DEBUG_DEVELOPER);
            return true;
        }

        openssl_pkey_export($res, $privatekey);

        $publickey = openssl_pkey_get_details($res);
        $publickey = $publickey['key'];

        $record = new stdClass();
        $record->client_id = '';
        $record->public_key = $publickey;
        $record->private_key = $privatekey;
        $record->encryption_algorithm = 'RS256';

        $DB->insert_record('local_placecom_mcp_public_key', $record);
    }

    return true;
}