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

class CourseResult extends Result
{
    public function icon() {

        return html_writer::tag('i', '', array('class' => 'fa fa-archive'));
    }

    public function name() {

        return $this->row->fullname;
    }

    public function url() {

        return new moodle_url('/course/view.php', array('id' => $this->row->id));
    }

    public function path() {

        return $this->getCategoryPath($this->row->category);
    }

    public function isVisible() {

        return $this->isCourseVisible($this->row->id);
    }
}
