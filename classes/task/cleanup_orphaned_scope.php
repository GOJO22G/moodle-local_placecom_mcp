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
 * Scheduled task to remove orphaned local_placecom_mcp_token_scope rows.
 *
 * A row becomes orphaned when its corresponding external_tokens row is
 * gone - either because Moodle core deleted it on catching an expired
 * token in use (see webservice/lib.php), or because it never existed
 * for that token in the first place. Core has no knowledge of this
 * plugin's own scope table, so nothing else ever cleans it up.
 *
 * @package    local_placecom_mcp
 * @copyright  2026 AlmaBay Networks Pvt. Ltd.
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_placecom_mcp\task;

use core\task\scheduled_task;

defined('MOODLE_INTERNAL') || die();

/**
 * Cleanup task for local_placecom_mcp_token_scope.
 */
class cleanup_orphaned_scope extends scheduled_task {

    /**
     * Get a descriptive name for this task.
     *
     * @return string
     */
    public function get_name() {
        return get_string('task_cleanup_orphaned_scope', 'local_placecom_mcp');
    }

    /**
     * Execute the task.
     *
     * @return void
     */
    public function execute() {
        global $DB;

        mtrace('local_placecom_mcp: removing orphaned token_scope rows...');

        $sql = "DELETE FROM {local_placecom_mcp_token_scope}
                 WHERE token NOT IN (SELECT token FROM {external_tokens})";
        $DB->execute($sql);

        mtrace('local_placecom_mcp: orphaned token_scope cleanup complete.');
    }
}
