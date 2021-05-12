# Meta-course group synchronization [![Build Status](https://github.com/paulholden/moodle-local_metagroups/workflows/moodle-plugin-ci/badge.svg)](https://github.com/paulholden/moodle-local_metagroups/actions)

## Requirements

- Moodle 3.5.3 or later.
- Meta-course enrolment plugin.

## Installation

Copy the metagroups folder into your Moodle /local directory and visit your admin notification page to complete the installation.

## Usage

After installation, or when creating new meta-course enrolment instances, you may need to synchronize existing groups. To do this
run the cli/sync.php script (use the --help switch for further instructions on usage).

Any future amendments to groups (add, update and delete) and their membership (add or remove users) in 'child' courses will be automatically
reflected in 'parent' courses that use groups.

## Author

Paul Holden (paulh@moodle.com)

- Updates: https://moodle.org/plugins/view.php?plugin=local_metagroups
- Latest code: https://github.com/paulholden/moodle-local_metagroups
