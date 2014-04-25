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

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/group/lib.php');

/**
 * Get a list of parent courses for a given course ID
 *
 * @param int|null $courseid or null for all parents
 * @return array of course IDs
 */
function local_metagroups_parent_courses($courseid = null) {
    global $DB;

    $conditions = array('enrol' => 'meta', 'status' => ENROL_INSTANCE_ENABLED);
    if ($courseid !== null) {
        $conditions['customint1'] = $courseid;
    }

    return $DB->get_records_menu('enrol', $conditions, 'sortorder', 'id, courseid');
}

/**
 * Get a list of all child courses for a given course ID
 *
 * @param int $courseid
 * @return array of course IDs
 */
function local_metagroups_child_courses($courseid) {
    global $DB;

    return $DB->get_records_menu('enrol', array('enrol' => 'meta', 'courseid' => $courseid, 'status' => ENROL_INSTANCE_ENABLED), 'sortorder', 'id, customint1');
}

/**
 * Returns the groups in the specified grouping.
 *
 * @param int $groupingid The groupingid to get the groups for
 * @param string $fields The fields to return
 * @param string $sort optional sorting of returned users
 * @return array|bool Returns an array of the groups for the specified
 * group or false if no groups or an error returned.
 */
function local_metagroups_get_grouping_groups($groupingid, $fields='g.*', $sort='name ASC') {
    global $DB;

	return $DB->get_records_sql("SELECT $fields
                                   FROM {groups} g, {groupings_groups} gg
                                  WHERE g.id = gg.groupid AND gg.groupingid = ?
                               ORDER BY $sort", array($groupingid));
}

/**
 * Returns the group id of a group in a parent course matching the child course.
 *
 * @param int $groupid The groupid to get the group for
 * @param int $courseid The course "parent" id
 * @return array|bool Returns an array of the id for the specified
 * course or false if no group or an error returned.
 */
function local_metagroups_group_match($groupid, $courseid) {
    global $DB;
	
	return $DB->get_records_sql("SELECT g.id
                                   FROM {groups} g
                                  WHERE g.idnumber = $groupid AND g.courseid=$courseid");
}



/**
 * Run synchronization process
 *
 * @param progress_trace $trace
 * @return void
 */
function local_metagroups_sync(progress_trace $trace) {
    global $DB;

    $courseids = local_metagroups_parent_courses(); // Get of all parent courses in the platform
    foreach (array_unique($courseids) as $courseid) { // browse parent courses ID
        $parent = $DB->get_record('course', array('id' => $courseid), '*', MUST_EXIST);
        $trace->output($parent->fullname, 1);

        $children = local_metagroups_child_courses($parent->id); // Get of all child courses ID for current parretn course
        foreach ($children as $childid) { // browse child courses ID
            $child = $DB->get_record('course', array('id' => $childid), '*', MUST_EXIST);
            $trace->output($child->fullname, 2);

            $childgroups = groups_get_all_groups($child->id); // Get child course groups
            foreach ($childgroups as $group) { // Browse child group course and create in parent course if necessary
                if (! $metagroup = $DB->get_record('groups', array('courseid' => $parent->id, 'idnumber' => $group->id))) {
                    $metagroup = new stdClass();
                    $metagroup->courseid = $parent->id;
                    $metagroup->idnumber = $group->id;
                    $metagroup->name = $group->name;

                    $metagroup->id = groups_create_group($metagroup, false, false);
                }

                $trace->output($metagroup->name, 3);

                $users = groups_get_members($group->id); // Get members from child course group
                foreach ($users as $user) { // Browse for adding in parent course group
                    groups_add_member($metagroup, $user->id, 'local_metagroups', $group->id);
                }
            }
			
			$parentgroups = groups_get_all_groups($parent->id); // Get members from parent course groups
			$groupings = groups_get_all_groupings($child->id); // Get groupings from child course
			
            foreach ($groupings as $grouping) { // Browse child course groupings and create in parent course if necessary
				if (! $metagrouping = $DB->get_record('groupings', array('courseid' => $parent->id, 'name' => $grouping->name))) {
                    $metagrouping = new stdClass();
                    $metagrouping->courseid = $parent->id;
                    $metagrouping->idnumber = $grouping->id;
                    $metagrouping->name = $grouping->name;
                    $metagrouping->id = groups_create_grouping($metagrouping);
                }

                $trace->output($metagrouping->name, 3);
				
				$groupinggroupsChild = local_metagroups_get_grouping_groups($grouping->id); // Get groups of current grouping (child course)
				$groupinggroupsParent = local_metagroups_get_grouping_groups($metagrouping->id); // Get groups of current grouping (parent course)

				foreach ($groupinggroupsChild as $groupinggroup) {
					$targetgroupsid=local_metagroups_group_match($groupinggroup->id,$parent->id); // Should be one group
					foreach ($targetgroupsid as $targetgroupid) {
						groups_assign_grouping($metagrouping->id, $targetgroupid->id);
						unset($groupinggroupsParent[$targetgroupid->id]); // Unset from group for unassign afterward
					}
                }
				
				foreach ($groupinggroupsParent as $groupinggroup) { // Unassign in parent course 
					groups_unassign_grouping($metagrouping->id, $groupinggroup->id);
                }
				
            }
			
        }
    }
}
