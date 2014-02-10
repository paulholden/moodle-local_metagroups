Moodle Meta-course Group Synchronization
=========================================

Requirements
------------
- Moodle 2.6 (version 2013111800 or later)
- Meta-course enrolment (version 2013110500 or later)

Installation
------------
Copy the metagroups folder into your Moodle /local directory and visit your Admin Notification page to complete the installation.

To synchronize existing meta-course enrolments, run the cli/sync.php script. Any future amemdments to groups in 'child' courses will
be reflected in linked meta-courses.

Author
------
Paul Holden (pholden@greenhead.ac.uk)

Changes
-------
Release 1.0 (version 2014021001)
- First release.
