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
 * @package    local_metagroups
 * @copyright  2018 Paul Holden (pholden@greenhead.ac.uk)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_metagroups\privacy;

defined('MOODLE_INTERNAL') || die();

use \core_privacy\local\metadata\collection,
    \core_privacy\local\request\contextlist,
    \core_privacy\local\request\approved_contextlist,
    \core_privacy\local\request\userlist,
    \core_privacy\local\request\approved_userlist;

class provider implements
    \core_privacy\local\metadata\provider,
    \core_privacy\local\request\core_userlist_provider,
    \core_privacy\local\request\plugin\provider {

    /**
     * Returns meta data about this system.
     *
     * @param collection $collection The initialised collection to add items to.
     * @return collection A listing of user data stored through this system.
     */
    public static function get_metadata(collection $collection) : collection {
        $collection->add_subsystem_link('core_group', [], 'privacy:metadata:core_group');

        return $collection;
    }

    /**
     * Get the list of contexts that contain user information for the specified user.
     *
     * @param int $userid The user to search.
     * @return contextlist $contextlist The contextlist containing the list of contexts used in this plugin.
     */
    public static function get_contexts_for_userid(int $userid) : contextlist {
        return \core_group\privacy\provider::get_contexts_for_group_member($userid, 'local_metagroups');
    }

    /**
     * Get the list of users who have data within a context.
     *
     * @param userlist $userlist The userlist containing the list of users who have data in this context/plugin combination.
     * @return void
     */
    public static function get_users_in_context(userlist $userlist) {
        return \core_group\privacy\provider::get_group_members_in_context($userlist, 'local_metagroups');
    }

    /**
     * Export all user data for the specified user, in the specified contexts.
     *
     * @param approved_contextlist $contextlist The approved contexts to export information for.
     * @return void
     */
    public static function export_user_data(approved_contextlist $contextlist) {
        $contextpath = [get_string('pluginname', 'local_metagroups')];

        foreach ($contextlist as $context) {
            \core_group\privacy\provider::export_groups($context, 'local_metagroups', $contextpath);
        }
    }

    /**
     * Delete all user data in the specified context.
     *
     * @param context $context
     * @return void
     */
    public static function delete_data_for_all_users_in_context(\context $context) {

    }

    /**
     * Delete multiple users within a single context.
     *
     * @param approved_userlist $userlist The approved context and user information to delete information for.
     * @return void
     */
    public static function delete_data_for_users(approved_userlist $userlist) {

    }

    /**
     * Delete all user data for the specified user, in the specified contexts.
     *
     * @param approved_contextlist $contextlist The approved contexts and user information to delete information for.
     * @return void
     */
    public static function delete_data_for_user(approved_contextlist $contextlist) {

    }
}
