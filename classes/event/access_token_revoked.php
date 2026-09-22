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
 * local_placecom_mcp access token revoked event.
 *
 * Deliberately carries the actual token string in 'other', unlike
 * access_token_deleted (used by the expiry-cleanup scheduled task), which
 * only carries the already-deleted row's id - not enough for
 * classes/observers.php's handle_access_token_revoked() to identify which
 * of its own bridged records (external_tokens, local_placecom_mcp_token_scope)
 * correspond to the same login session.
 *
 * @package local_placecom_mcp
 * @copyright 2026 AlmaBay Networks Pvt. Ltd.
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_placecom_mcp\event;

use context_system;
use core\event\base;

/**
 * The access_token_revoked event class.
 */
class access_token_revoked extends base {
    /**
     * Init method.
     *
     * @return void
     */
    protected function init() {
        $this->context = context_system::instance();
        $this->data['crud'] = 'd';
        $this->data['edulevel'] = self::LEVEL_OTHER;
        $this->data['objecttable'] = 'local_placecom_mcp_access_token';
    }

    /**
     * Return localised event name.
     *
     * @return string
     */
    public static function get_name() {
        return get_string('event_access_token_revoked', 'local_placecom_mcp');
    }

    /**
     * Return description of what happened.
     *
     * @return string
     */
    public function get_description() {
        $clientid = $this->data['other']['clientid'] ?? '';
        return "Access token for user id " . $this->relateduserid . " (client " . $clientid . ") was manually revoked by user id " . $this->userid . ".";
    }
}