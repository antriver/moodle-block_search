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
 * @package    block_search
 * @copyright  2015 Anthony Kuske <www.anthonykuske.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_search\Models;

use html_writer;
use moodle_url;
use block_search\DataManager;

class ModuleResult extends Result
{
    public function icon() {

        global $OUTPUT;
        return trim($OUTPUT->pix_icon('icon', '', $this->tablename, array('class' => 'icon')));
    }

    public function url() {

        $resourceid = DataManager::get_global_instance_id_from_module_instance_id($this->tablename, $this->row->id);
        return new moodle_url('/mod/' . $this->tablename . '/view.php', array('id' => $resourceid));
    }

    public function path() {

        // Get all info for the course this resource is in.
        $course = DataManager::get_course($this->row->course);

        $path = $this->get_category_path($course->category);

        if (function_exists('\course_get_icon')) {
            $courseicon = \course_get_icon($course->id);
        } else {
            $courseicon = false;
        }
        $path[] = array(
            'title' => 'Course',
            'name' => $course->fullname,
            'url' => new moodle_url('/course/view.php', array('id' => $course->id)),
            'icon' => !empty($courseicon) ? 'fa fa-'.$courseicon : 'fa fa-archive'
        );

        // Get all info for the course section this resource is in.
        $section = DataManager::get_resource_section($this->tablename, $this->row->id);
        if ($section->name) {
            $path[] = array(
                'title' => 'Section',
                'name' => $section->name,
                'url' => new moodle_url('/course/view.php', array('id' => $course->id, 'sectionid' => $section->id)),
                'icon' => 'fa fa-th'
            );
        }
        return $path;
    }

    public function is_visible() {

        // Is user enroled in the course the folder is in?
        if (($error = $this->is_course_visible($this->row->course)) !== true) {
            return $error;
        }

        if (DataManager::can_user_see_module($this->row->course, $this->tablename, $this->row->id)) {
            return true;
        } else {
            return 'notvisible';
        }
    }
}
