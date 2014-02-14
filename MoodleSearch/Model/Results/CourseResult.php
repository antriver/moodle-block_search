<?php

namespace MoodleSearch;

class CourseResult extends Result
{
	public function icon()
	{
		return \html_writer::tag('i', '', array('class' => 'icon-archive'));
	}

	public function name()
	{
		return $this->row->fullname;
	}

	public function url()
	{
		return '/course/view.php?id=' . $this->row->id;
	}

	public function path()
	{
		return $this->getCategoryPath($this->row->category);
	}

	public function isVisible()
	{
		global $USER;
		$coursecontext = \context_course::instance($this->row->id);
		if (is_enrolled($coursecontext, $USER)) {
			return true;
		} else {
			return 'notenrolled';
		}
	}

}
