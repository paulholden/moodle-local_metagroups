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
 * @copyright  2014 Paul Holden (pholden@greenhead.ac.uk)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_metagroups;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/local/metagroups/locallib.php');

class observers {

    /**
     * Enrol instance created
     *
     * @param \core\event\enrol_instance_created $event
     * @return void
     */
    public static function enrol_instance_created(\core\event\enrol_instance_created $event) {
        $instance = $event->get_record_snapshot('enrol', $event->objectid);

        if (strcasecmp($instance->enrol, 'meta') == 0) {
            $course = get_course($instance->courseid);

            $syncall = get_config('local_metagroups', 'syncall');

            // Return early if course doesn't use groups.
            if (!$syncall && groups_get_course_groupmode($course) == NOGROUPS) {
                return;
            }

            // Immediate synchronization could be expensive, defer to adhoc task.
            $task = new \local_metagroups\task\adhoc();
            $task->set_custom_data(['courseid' => $course->id]);

            \core\task\manager::queue_adhoc_task($task);
        }
    }

    /**
     * Enrol instance deleted
     *
     * @param \core\event\enrol_instance_deleted $event
     * @return void
     */
    public static function enrol_instance_deleted(\core\event\enrol_instance_deleted $event) {
        global $DB;

        $instance = $event->get_record_snapshot('enrol', $event->objectid);

        if (strcasecmp($instance->enrol, 'meta') == 0) {
            $course = get_course($instance->courseid);

            // Get groups from linked course, and delete them from current course.
            $groups = groups_get_all_groups($instance->customint1);
            foreach ($groups as $group) {
                if ($metagroup = $DB->get_record('groups', array('courseid' => $course->id, 'idnumber' => $group->id))) {
                    groups_delete_group($metagroup);
                }
            }
        }
    }

    /**
     * Group created
     *
     * @param \core\event\group_created $event
     * @return void
     */
    public static function group_created(\core\event\group_created $event) {
        global $DB;

        $syncall = get_config('local_metagroups', 'syncall');

        $group = $event->get_record_snapshot('groups', $event->objectid);

        $courseids = local_metagroups_parent_courses($group->courseid);
        foreach ($courseids as $courseid) {
            $course = get_course($courseid);

            // If parent course doesn't use groups and syncall disabled, we can skip synchronization.
            if (!$syncall && groups_get_course_groupmode($course) == NOGROUPS) {
                continue;
            }

            if (! $DB->record_exists('groups', array('courseid' => $course->id, 'idnumber' => $group->id))) {
                $metagroup = new \stdClass();
                $metagroup->courseid = $course->id;
                $metagroup->idnumber = $group->id;
                $metagroup->name = $group->name;
                $metagroup->description = $group->description;
                $metagroup->descriptionformat = $group->descriptionformat;
                $metagroup->picture = $group->picture;
                $metagroup->hidepicture = $group->hidepicture;
                // No need to sync enrolmentkey, user should be able to enrol only on source course.
                $metagroup->enrolmentkey = null;

                groups_create_group($metagroup, false, false);
            }
        }
    }

    /**
     * Group updated
     *
     * @param \core\event\group_updated $event
     * @return void
     */
    public static function group_updated(\core\event\group_updated $event) {
        global $DB;

        $group = $event->get_record_snapshot('groups', $event->objectid);

        $courseids = local_metagroups_parent_courses($group->courseid);
        foreach ($courseids as $courseid) {
            $course = get_course($courseid);

            if ($metagroup = $DB->get_record('groups', array('courseid' => $course->id, 'idnumber' => $group->id))) {
                $metagroup->name = $group->name;
                $metagroup->description = $group->description;
                $metagroup->descriptionformat = $group->descriptionformat;
                $metagroup->picture = $group->picture;
                $metagroup->hidepicture = $group->hidepicture;
                // No need to sync enrolmentkey, user should be able to enrol only on source course.
                $metagroup->enrolmentkey = null;

                groups_update_group($metagroup, false, false);
            }
        }
    }

    /**
     * Group deleted
     *
     * @param \core\event\group_deleted $event
     * @return void
     */
    public static function group_deleted(\core\event\group_deleted $event) {
        global $DB;

        $group = $event->get_record_snapshot('groups', $event->objectid);

        $courseids = local_metagroups_parent_courses($group->courseid);
        foreach ($courseids as $courseid) {
            $course = get_course($courseid);

            if ($metagroup = $DB->get_record('groups', array('courseid' => $course->id, 'idnumber' => $group->id))) {
                groups_delete_group($metagroup);
            }
        }
    }

    /**
     * Group member added
     *
     * @param \core\event\group_member_added $event
     * @return void
     */
    public static function group_member_added(\core\event\group_member_added $event) {
        global $DB;

        $group = $event->get_record_snapshot('groups', $event->objectid);
        $user = \core_user::get_user($event->relateduserid, '*', MUST_EXIST);

        $courseids = local_metagroups_parent_courses($group->courseid);
        foreach ($courseids as $courseid) {
            $course = get_course($courseid);

            if ($metagroup = $DB->get_record('groups', array('courseid' => $course->id, 'idnumber' => $group->id))) {
                groups_add_member($metagroup, $user, 'local_metagroups', $group->id);
            }
        }
    }

    /**
     * Group member removed
     *
     * @param \core\event\group_member_removed $event
     * @return void
     */
    public static function group_member_removed(\core\event\group_member_removed $event) {
        global $DB;

        $group = $event->get_record_snapshot('groups', $event->objectid);
        $user = \core_user::get_user($event->relateduserid, '*', MUST_EXIST);

        $courseids = local_metagroups_parent_courses($group->courseid);
        foreach ($courseids as $courseid) {
            $course = get_course($courseid);

            if ($metagroup = $DB->get_record('groups', array('courseid' => $course->id, 'idnumber' => $group->id))) {
                groups_remove_member($metagroup, $user);
            }
        }
    }

    /**
     * Grouping created
     *
     * @param \core\event\grouping_created $event
     * @return void
     */
    public static function grouping_created(\core\event\grouping_created $event) {
        global $DB;

        $syncgroupings = get_config('local_metagroups', 'syncgroupings');
        if (!$syncgroupings) {
            return;
        }

        $grouping = $event->get_record_snapshot('groupings', $event->objectid);

        $courseids = local_metagroups_parent_courses($grouping->courseid);
        foreach ($courseids as $courseid) {
            $course = $DB->get_record('course', array('id' => $courseid), '*', MUST_EXIST);

            // If parent course doesn't use groups and syncall disabled, we can skip synchronization.
            if (!$syncall && groups_get_course_groupmode($course) == NOGROUPS) {
                continue;
            }

            if (!$DB->record_exists('groupings', array('courseid' => $course->id, 'idnumber' => $grouping->id))) {
                $metagrouping = new \stdClass();
                $metagrouping->courseid = $course->id;
                $metagrouping->idnumber = $grouping->id;
                $metagrouping->name = $grouping->name;
                groups_create_grouping($metagrouping);
            }
        }
    }

    /**
     * Grouping deleted
     *
     * @param \core\event\grouping_deleted $event
     * @return void
     */
    public static function grouping_deleted(\core\event\grouping_deleted $event) {
        global $DB;

        $syncgroupings = get_config('local_metagroups', 'syncgroupings');
        if (!$syncgroupings) {
            return;
        }

        $grouping = $event->get_record_snapshot('groupings', $event->objectid);

        $courseids = local_metagroups_parent_courses($grouping->courseid);

        foreach ($courseids as $courseid) {
            $course = $DB->get_record('course', array('id' => $courseid), '*', MUST_EXIST);

            if ($metagrouping = $DB->get_record('groupings', array('courseid' => $course->id, 'idnumber' => $grouping->id))) {
                groups_delete_grouping($metagrouping);
            }
        }
    }

    /**
     * Grouping updated
     *
     * @param \core\event\grouping_updated $event
     * @return void
     */
    public static function grouping_updated(\core\event\grouping_updated $event) {
        global $DB;

        $syncgroupings = get_config('local_metagroups', 'syncgroupings');
        if (!$syncgroupings) {
            return;
        }

        $grouping = $event->get_record_snapshot('groupings', $event->objectid);

        $courseids = local_metagroups_parent_courses($grouping->courseid);
        foreach ($courseids as $courseid) {
            $course = $DB->get_record('course', array('id' => $courseid), '*', MUST_EXIST);

            if ($metagrouping = $DB->get_record('groupings', array('courseid' => $course->id, 'idnumber' => $grouping->id))) {
                $metagrouping->name = $grouping->name;
                groups_update_grouping($metagrouping);
            }
        }
    }

    /**
     * Course updated
     * We need to listen for course_updated event to check situation, when group mode changed from NOGROUPS
     *
     * @param \core\event\course_updated $event
     * @return void
     */
    public static function course_updated(\core\event\course_updated $event) {
        global $DB;

        $syncall = get_config('local_metagroups', 'syncall');
        if ($syncall) {// If syncall is on, then course is alredy synced.
            return;
        }

        // Immediate synchronization could be expensive, defer to adhoc task.
        $task = new \local_metagroups\task\adhoc();
        $task->set_custom_data(['courseid' => $event->objectid]);

        \core\task\manager::queue_adhoc_task($task);
    }

    /**
     * Group assigned to grouping
     *
     * @param \core\event\grouping_group_assigned $event
     * @return void
     */
    public static function grouping_group_assigned(\core\event\grouping_group_assigned $event) {
        global $DB;

        $syncgroupings = get_config('local_metagroups', 'syncgroupings');
        if (!$syncgroupings) {
            return;
        }

        $grouping = $event->get_record_snapshot('groupings', $event->objectid);
        $groupid = $event->other['groupid'];

        $parents = local_metagroups_parent_courses($grouping->courseid);
        foreach($parents as $parentid) {
            if ($metagrouping = $DB->get_record('groupings', array('courseid' => $parentid, 'idnumber' => $grouping->id))) {
                $targetgroups = local_metagroups_group_match($groupid, $parentid);
                foreach ($targetgroups as $targetgroup) {
                    groups_assign_grouping($metagrouping->id, $targetgroup->id);
                }
            }
        }
    }

    /**
     * Group unassigned from grouping
     *
     * @param \core\event\grouping_group_assigned $event
     * @return void
     */
    public static function grouping_group_unassigned(\core\event\grouping_group_unassigned $event) {
        global $DB;

        $syncgroupings = get_config('local_metagroups', 'syncgroupings');
        if (!$syncgroupings) {
            return;
        }

        $grouping = $event->get_record_snapshot('groupings', $event->objectid);
        $groupid = $event->other['groupid'];

        $parents = local_metagroups_parent_courses($grouping->courseid);
        foreach($parents as $parentid) {
            if ($metagrouping = $DB->get_record('groupings', array('courseid' => $parentid, 'idnumber' => $grouping->id))) {
                $targetgroups = local_metagroups_group_match($groupid, $parentid);
                foreach ($targetgroups as $targetgroup) {
                    groups_unassign_grouping($metagrouping->id, $targetgroup->id);
                }
            }
        }
    }
}
