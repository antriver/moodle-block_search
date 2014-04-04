<?php

namespace MoodleSearch;

class CourseResult extends Result
{
	public function icon()
	{
		return \html_writer::tag('i', '', array('class' => 'fa fa-archive'));
	}

	public function name()
	{
		return $this->row->fullname;
	}

	public function url()
	{
		return new \moodle_url('/course/view.php', array('id' => $this->row->id));
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
