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
 * local_placecom_mcp user granted event.
 *
 * @package local_placecom_mcp
 * @author Pau Ferrer Ocaña <pferre22@xtec.cat>
 * @author Lai Wei <lai.wei@enovation.ie>
 * @author Dorel Manolescu <dorel.manolescu@enovation.ie>
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @copyright (C) 2025 Enovation Solutions
 * @copyright 2026 AlmaBay Networks Pvt. Ltd. (Placecom) - namespace/table renames, PKCE param fix
 */

namespace local_placecom_mcp\event;

use coding_exception;
use context_system;
use core\event\base;

/**
 * The user_granted event class.
 */
class user_granted extends base {
    /**
     * Init method.
     *
     * @return void
     */
    protected function init() {
        $this->context = context_system::instance();
        $this->data['crud'] = 'r';
        $this->data['edulevel'] = self::LEVEL_OTHER;
        $this->data['objecttable'] = 'user';
    }

    /**
     * Return localised event name.
     *
     * @return string
     */
    public static function get_name() {
        return get_string('event_user_granted', 'local_placecom_mcp');
    }

    /**
     * Return description of what happened.
     *
     * @return string
     */
    public function get_description() {
        $clientid = $this->data['other']['clientid'];
        $scope = $this->data['other']['scope'];
        return "The user has been granted to access for $clientid to $scope.";
    }

    /**
     * Validate the event data.
     *
     * @throws coding_exception
     */
    protected function validate_data() {
        if (!isset($this->data['other']['clientid'])) {
            throw new coding_exception('The event data must have a clientid');
        }
        if (!isset($this->data['other']['scope'])) {
            throw new coding_exception('The event data must have a scope');
        }
    }
}
