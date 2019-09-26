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

use core_privacy\local\request\userlist;
use core_privacy\local\request\writer;
use core_privacy\tests\provider_testcase;
use local_metagroups\privacy\provider;

/**
 * Unit tests for Privacy API
 *
 * @package     local_metagroups
 * @group       local_metagroups
 * @covers      \local_metagroups\privacy\provider
 * @copyright   2018 Paul Holden (pholden@greenhead.ac.uk)
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class local_metagroups_privacy_testcase extends provider_testcase {

    /** @var stdClass $course1 */
    protected $course1;

    /** @var stdClass $course2 */
    protected $course2;

    /** @var stdClass $group */
    protected $group;

    /** @var stdClass $user */
    protected $user;

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
        $meta = enrol_get_plugin('meta');
        $meta->add_instance($this->course2, ['customint1' => $this->course1->id]);

        // Create a group in parent course.
        $this->group = $this->getDataGenerator()->create_group(['courseid' => $this->course1->id]);

        // Create user, add them to parent course/group.
        $this->user = $this->getDataGenerator()->create_and_enrol($this->course1, 'student');
        $this->getDataGenerator()->create_group_member(['groupid' => $this->group->id, 'userid' => $this->user->id]);
    }

    /**
     * Tests provider get_contexts_for_userid method
     *
     * @return void
     */
    public function test_get_contexts_for_userid() {
        $contextlist = provider::get_contexts_for_userid($this->user->id);

        // Filter out any contexts that are not related to course context.
        $contexts = array_filter($contextlist->get_contexts(), function($context) {
            return $context instanceof context_course;
        });

        $this->assertCount(1, $contexts);

        $expected = context_course::instance($this->course2->id, MUST_EXIST);
        $this->assertSame($expected, reset($contexts));
    }

    /**
     * Tests provider get_contexts_for_userid method when user has no group membership
     *
     * @return void
     */
    public function test_get_contexts_for_userid_no_group_membership() {
        $user = $this->getDataGenerator()->create_user();

        $contextlist = provider::get_contexts_for_userid($user->id);

        // Filter out any contexts that are not related to course context.
        $contexts = array_filter($contextlist->get_contexts(), function($context) {
            return $context instanceof context_course;
        });

        $this->assertEmpty($contexts);
    }

    /**
     * Tests provider get_users_in_context method
     *
     * @return void
     */
    public function test_get_users_in_context() {
        $context = context_course::instance($this->course2->id, MUST_EXIST);

        $userlist = new userlist($context, 'local_metagroups');
        provider::get_users_in_context($userlist);

        $this->assertCount(1, $userlist);
        $this->assertEquals([$this->user->id], $userlist->get_userids());
    }

    /**
     * Test provider export_user_data method
     *
     * @return void
     */
    public function test_export_user_data() {
        $this->setUser($this->user);

        $context = context_course::instance($this->course2->id, MUST_EXIST);
        $this->export_context_data_for_user($this->user->id, $context, 'local_metagroups');

        $contextpath = [get_string('pluginname', 'local_metagroups'), get_string('groups', 'core_group')];

        $writer = writer::with_context($context);
        $data = $writer->get_data($contextpath);
        $this->assertTrue($writer->has_any_data());

        $this->assertCount(1, $data->groups);
        $this->assertSame($this->group->name, reset($data->groups)->name);
    }
}