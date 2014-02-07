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

class observers {

    /**
     * Group created
     *
     * @param \core\event\group_created $event
     * @return void
     */
    public static function group_created(\core\event\group_created $event) {
        $group = $event->get_record_snapshot('groups', $event->objectid);

    }

    /**
     * Group updated
     *
     * @param \core\event\group_updated $event
     * @return void
     */
    public static function group_updated(\core\event\group_updated $event) {
        $group = $event->get_record_snapshot('groups', $event->objectid);

    }

    /**
     * Group deleted
     *
     * @param \core\event\group_deleted $event
     * @return void
     */
    public static function group_deleted(\core\event\group_deleted $event) {
        $group = $event->get_record_snapshot('groups', $event->objectid);

    }
}
