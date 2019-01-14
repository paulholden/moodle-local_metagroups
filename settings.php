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
 * local_metagroups plugin settings page
 *
 * @package    local_metagroups
 * @copyright  2016 Vadim Dvorovenko (vadimon@mail.ru)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

if ($hassiteconfig) {
    $settings = new admin_settingpage('local_metagroups', get_string('pluginname', 'local_metagroups'));
    $ADMIN->add('localplugins', $settings);
    $settings->add(new admin_setting_configcheckbox(
            'local_metagroups/syncall',
            get_string('syncall', 'local_metagroups'),
            get_string('syncall_desc', 'local_metagroups'),
            0));
    $settings->add(new admin_setting_configcheckbox(
            'local_metagroups/syncgroupings',
            get_string('syncgroupings', 'local_metagroups'),
            get_string('syncgroupings_desc', 'local_metagroups'),
            1));
}
