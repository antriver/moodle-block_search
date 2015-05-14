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

class FileInFolderResult extends Result
{
    public function icon() {

        global $OUTPUT;
        return $OUTPUT->pix_icon(file_mimetype_icon($this->row->mimetype), '');
    }

    public function name() {

        return $this->row->filename;
    }

    public function url() {

        return new moodle_url('/mod/folder/view.php', array('id' => $this->row->folderid));
    }

    public function path() {

        //Get all info for the course this resource is in
        $course = DataManager::getCourse($this->row->courseid);

        $path = $this->getCategoryPath($course->category);

        if (function_exists('\course_get_icon')) {
            $courseIcon = \course_get_icon($course->id);
        } else {
            $courseIcon = false;
        }
        $path[] = array(
            'title' => 'Course',
            'name' => $course->fullname,
            'url' => new moodle_url('/course/view.php', array('id' => $course->id)),
            'icon' => !empty($courseIcon) ? 'fa fa-'.$courseIcon : 'fa fa-archive'
        );

        //Get all info for the course section this resource is in
        $section = DataManager::getResoureSectionFromCourseModuleID($this->row->moduleid);
        if ($section->name) {
            $path[] = array(
                'title' => 'Section',
                'name' => $section->name,
                //TODO: Is sectionid used by vanilla Moodle, or was that an SSIS tweak? I forgot
                'url' => new moodle_url('/course/view.php', array('id' => $course->id, 'sectionid' => $section->id)),
                'icon' => 'fa fa-th'
            );
        }

        $path[] = array(
            'title' => 'Folder',
            'name' => $this->row->foldername,
            'url' => new moodle_url('/mod/folder/view.php', array('id' => $this->row->folderid)),
            'icon' => 'fa fa-folder'
        );

        foreach (explode('/', $this->row->filepath) as $folder) {
            if ($folder) {
                $path[] = array(
                    'title' => 'Folder',
                    'name' => $folder,
                    'url' => $this->url(),
                    'icon' => 'fa fa-folder'
                );
            }
        }

        return $path;
    }

    public function isVisible() {

        // Is it set as visible?
        if (!$this->row->modulevisible) {
            return false;
        }

        // Is user enroled in the course the folder is in?
        if (($error = $this->isCourseVisible($this->row->courseid)) !== true) {
            return $error;
        }

        return true;

        //This was far too slow...
        /*$modulecontext = \context_module::instance($this->row->moduleid);
        if (has_capability('mod/folder:view', $modulecontext)) {
            return true;
        } else {
            return 'notvisible';
        }*/
    }
}
