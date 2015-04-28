<?php

/**
 * @package    block_search
 * @copyright  Anthony Kuske <www.anthonykuske.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_search\Models;

use html_writer;
use moodle_url;

class CourseResult extends Result
{
	public function icon()
	{
		return html_writer::tag('i', '', array('class' => 'fa fa-archive'));
	}

	public function name()
	{
		return $this->row->fullname;
	}

	public function url()
	{
		return new moodle_url('/course/view.php', array('id' => $this->row->id));
	}

	public function path()
	{
		return $this->getCategoryPath($this->row->category);
	}

	public function isVisible()
	{
		return $this->isCourseVisible($this->row->id);
	}
}
