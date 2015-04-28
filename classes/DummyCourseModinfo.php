<?php

/**
 * The cm_info class which is used to check if a module is available
 * need an instance of course_modinfo passed to it.
 * But that class is super bloated. So we sent it one of these instead...
 *
 * FIXME: This is broken in 2.8
 * But maybe 2.8 is improved and this is no longer needed...
 *
 * @package    block_search
 * @copyright  Anthony Kuske <www.anthonykuske.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_search;

use course_modinfo;
use section_info;

class DummyCourseModinfo extends course_modinfo
{
    function __construct($courseid)
    {
        global $USER;
        $this->courseid = $courseid;
        $this->userid = $USER->id;
    }

    function get_section_info($sectionnumber, $strictness = IGNORE_MISSING)
    {
        global $DB;
        $row =$DB->get_record('course_sections', array('id' => $sectionnumber), '*', $strictness);
        return new section_info($row, $row->section, $this->courseid, 0, $this, $this->userid);
    }
}
