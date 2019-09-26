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

defined('MOODLE_INTERNAL') || die();

use local_metagroups\task\synchronize;

/**
 * Unit tests for event observers
 *
 * @package     local_metagroups
 * @group       local_metagroups
 * @covers      \local_metagroups\observers
 * @copyright   2018 Paul Holden (pholden@greenhead.ac.uk)
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class local_metagroups_observers_testcase extends advanced_testcase {

    /** @var stdClass $course1 */
    protected $course1;

    /** @var stdClass $course2 */
    protected $course2;

    /** @var stdClass $group */
    protected $group;

    /**
     * Test setup
     *
     * @return void
     */
    protected function setUp() {
        $this->resetAfterTest(true);

        // Create test courses.
        $this->course1 = $this->getDataGenerator()->create_course(['groupmode' => VISIBLEGROUPS]);
        $this->course2 = $this->getDataGenerator()->create_course(['groupmode' => VISIBLEGROUPS]);

        // Enable metacourse enrolment plugin.
        $enabled = enrol_get_plugins(true);
        $enabled['meta'] = true;
        $enabled = array_keys($enabled);
        set_config('enrol_plugins_enabled', implode(',', $enabled));

        // Create metacourse enrolment instance.
        enrol_get_plugin('meta')->add_instance($this->course2, ['customint1' => $this->course1->id]);

        // Create a group in parent course.
        $this->group = $this->getDataGenerator()->create_group(['courseid' => $this->course1->id]);
    }

    /**
     * Tests enrol_instance_created event observer
     *
     * @return void
     */
    public function test_enrol_instance_created() {
        global $DB;

        $course = $this->getDataGenerator()->create_course(['groupmode' => VISIBLEGROUPS]);
        enrol_get_plugin('meta')->add_instance($course, ['customint1' => $this->course1->id]);

        // Execute queued plugin adhoc tasks.
        ob_start();
        $this->runAdhocTasks(synchronize::class);
        ob_end_clean();

        // The group from the parent should have been created in linked course.
        $linkedgroup = $DB->get_record('groups', ['courseid' => $course->id, 'idnumber' => $this->group->id], '*', MUST_EXIST);
        $this->assertSame($this->group->name, $linkedgroup->name);
    }

    /**
     * Tests enrol_instance_deleted event observer
     *
     * @return void
     */
    public function test_enrol_instance_deleted() {
        global $DB;

        $instance = $DB->get_record('enrol', ['courseid' => $this->course2->id, 'enrol' => 'meta'], '*', MUST_EXIST);
        enrol_get_plugin('meta')->delete_instance($instance);

        // The group should also have been deleted in linked course.
        $exists = $DB->record_exists('groups', ['courseid' => $this->course2->id, 'idnumber' => $this->group->id]);
        $this->assertFalse($exists);
    }

    /**
     * Tests group_created event observer
     *
     * @return void
     */
    public function test_group_created() {
        global $DB;

        // The group should also have been created in linked course.
        $linkedgroupname = $DB->get_field('groups', 'name', ['courseid' => $this->course2->id, 'idnumber' => $this->group->id]);
        $this->assertSame($this->group->name, $linkedgroupname);
    }

    /**
     * Tests group_updated event observer
     *
     * @return void
     */
    public function test_group_updated() {
        global $DB;

        $this->group->name = core_text::strrev($this->group->name);
        groups_update_group($this->group);

        // The group should also have been updated in linked course.
        $linkedgroupname = $DB->get_field('groups', 'name', ['courseid' => $this->course2->id, 'idnumber' => $this->group->id]);
        $this->assertSame($this->group->name, $linkedgroupname);
    }

    /**
     * Tests group_deleted event observer
     *
     * @return void
     */
    public function test_group_deleted() {
        global $DB;

        groups_delete_group($this->group);

        // The group should also have been deleted in linked course.
        $exists = $DB->record_exists('groups', ['courseid' => $this->course2->id, 'idnumber' => $this->group->id]);
        $this->assertFalse($exists);
    }

    /**
     * Tests group_member_added event observer
     *
     * @return void
     */
    public function test_group_member_added() {
        global $DB;

        $user = $this->getDataGenerator()->create_and_enrol($this->course1, 'student');
        $this->getDataGenerator()->create_group_member(['groupid' => $this->group->id, 'userid' => $user->id]);

        // User should also be a member of group in linked course.
        $linkedgroupid = $DB->get_field('groups', 'id', ['courseid' => $this->course2->id, 'idnumber' => $this->group->id]);

        $exists = $DB->record_exists('groups_members',
            ['groupid' => $linkedgroupid, 'userid' => $user->id, 'component' => 'local_metagroups', 'itemid' => $this->group->id]);
        $this->assertTrue($exists);
    }

    /**
     * Tests group_member_added event observer
     *
     * @return void
     */
    public function test_group_member_removed() {
        global $DB;

        $user = $this->getDataGenerator()->create_and_enrol($this->course1, 'student');
        $this->getDataGenerator()->create_group_member(['groupid' => $this->group->id, 'userid' => $user->id]);

        groups_remove_member($this->group, $user);

        // User should no longer be a member of group in linked course.
        $linkedgroupid = $DB->get_field('groups', 'id', ['courseid' => $this->course2->id, 'idnumber' => $this->group->id]);

        $exists = $DB->record_exists('groups_members',
            ['groupid' => $linkedgroupid, 'userid' => $user->id, 'component' => 'local_metagroups', 'itemid' => $this->group->id]);
        $this->assertFalse($exists);
    }
}