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

class CategoryResult extends Result
{
    public function icon() {

        if (function_exists('\course_get_category_icon')) {
            $categoryicon = \course_get_category_icon($this->row->id);
            return html_writer::tag('i', '', array('class' => 'fa fa-' . $categoryicon));
        } else {
            return html_writer::tag('i', '', array('class' => 'fa fa-folder-open'));
        }
    }

    public function url() {

        return new moodle_url('/course/index.php', array('categoryid' => $this->row->id));
    }

    public function path() {

        if ($this->row->depth <= 1) {
            return array();
        } else {
            // Get the names of parent categories.
            return $this->get_category_path($this->row->id, $this->row->path, true);
        }
    }

    public function is_visible() {

        return true;
    }
}
