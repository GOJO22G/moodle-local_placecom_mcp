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

declare(strict_types=1);

namespace local_placecom_mcp\local;

/**
 * The single, canonical list of Moodle functions this MCP server will ever
 * expose or execute.
 *
 * Moodle ships roughly 750 external functions in total, and its own admin
 * UI (Site administration > Server > Web services > External services)
 * lets any site admin add ANY of them to a service - including functions
 * this project has specifically reviewed and excluded (e.g.
 * core_user_get_users / core_user_get_users_by_field, which return
 * cross-user profile data unrelated to the calling user's own courses).
 *
 * Without this allowlist, Moodle's own external_services_functions table
 * IS the safety boundary - and that table can be edited by any site admin
 * with no review at all. This class makes our own reviewed list the real
 * boundary instead, enforced in code regardless of what the service table
 * says.
 *
 * This is a curated subset (34 read, 2 write) of an earlier, broader
 * 54-function list, narrowed down to core day-to-day functionality.
 * Every name below was individually verified against this Moodle
 * installation's actual source (db/services.php in the owning module),
 * not assumed from documentation alone.
 *
 * Referenced from two places, both of which must stay in sync with this
 * one array rather than keeping their own copies:
 *   - tool_provider::get_tools() filters what appears in tools/list.
 *   - server::enforce_scope() blocks execution of anything not listed here,
 *     even if called directly by function name rather than via tools/list.
 *
 * local_mcpbridge's db/services.php also reads this same constant when
 * registering functions on its service, rather than keeping a second,
 * separately-maintained copy.
 *
 * @package     local_placecom_mcp
 * @copyright   2026 AlmaBay Networks Pvt. Ltd.
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class approved_functions {
    /**
     * The approved function names. Adding or removing a function means
     * editing ONLY this array - nowhere else.
     *
     * @var string[]
     */
    public const LIST = [
        // Core (lib/db/services.php)
        'core_webservice_get_site_info',
        'core_course_get_courses_by_field',
        'core_course_get_categories',
        'core_course_search_courses',
        'core_course_get_contents',
        'core_course_get_course_module',
        'core_enrol_get_users_courses',
        'core_course_get_enrolled_courses_by_timeline_classification',
        'core_course_get_recent_courses',
        'core_group_get_course_groups',
        'core_group_get_course_user_groups',
        'core_group_get_group_members',
        'core_calendar_get_action_events_by_course',
        'core_calendar_get_action_events_by_timesort',
        'core_message_get_unread_notification_count',
        'core_completion_get_activities_completion_status',
        'core_completion_get_course_completion_status',
        'core_badges_get_user_badges',
        'core_files_get_files',

        // Assignment (mod/assign)
        'mod_assign_get_assignments',
        'mod_assign_get_submission_status',

        // Quiz (mod/quiz)
        'mod_quiz_get_quizzes_by_courses',
        'mod_quiz_get_user_best_grade',
        'mod_quiz_get_attempt_review',
        'mod_quiz_get_attempt_summary',

        // Forum (mod/forum)
        'mod_forum_get_forums_by_courses',
        'mod_forum_get_forum_discussions',
        'mod_forum_get_discussion_posts',
        'mod_forum_add_discussion',       // write
        'mod_forum_add_discussion_post',  // write

        // Lesson (mod/lesson)
        'mod_lesson_get_lessons_by_courses',
        'mod_lesson_get_user_attempt',

        // Glossary (mod/glossary)
        'mod_glossary_get_entries_by_search',

        // Page (mod/page)
        'mod_page_get_pages_by_courses',

        // URL (mod/url)
        'mod_url_get_urls_by_courses',

        // Notifications (message/output/popup)
        'message_popup_get_unread_popup_notification_count',
    ];

    /**
     * Whether a given function name is on the approved list.
     *
     * @param string $functionname
     * @return bool
     */
    public static function is_approved(string $functionname): bool {
        return in_array($functionname, self::LIST, true);
    }
}