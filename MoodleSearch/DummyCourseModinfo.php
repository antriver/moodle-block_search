<?php

namespace MoodleSearch;

use course_modinfo;

/**
 * The cm_info class which is used to check if a module is available
 * need an instance of course_modinfo passed to it.
 * But that class is super bloated. So we sent it one of these instead...
 */
class DummyCourseModinfo extends course_modinfo
{
    function __construct($courseid)
    {
        $this->courseid = $courseid;
        global $USER;
        $this->userid = $USER->id;
    }

    function get_section_info($sectionnumber, $strictness = IGNORE_MISSING)
    {
        global $DB;
        $row =$DB->get_record('course_sections', array('id' => $sectionnumber), '*', $strictness);
        return new \section_info($row, $row->section, $this->courseid, 0, $this, $this->userid);
    }
}
