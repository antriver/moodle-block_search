<?php

namespace MoodleSearch;

class CategoryResult extends Result
{
	public function icon()
	{
		if (function_exists('\course_get_category_icon')) {
			$categoryIcon = \course_get_category_icon($this->row->id);
			return \html_writer::tag('i', '', array('class' => 'fa fa-' . $categoryIcon));
		} else {
			return \html_writer::tag('i', '', array('class' => 'fa fa-folder-open'));
		}
	}

	public function url()
	{
		return new \moodle_url('/course/index.php', array('categoryid' => $this->row->id));
	}

	public function path()
	{
		if ($this->row->depth <= 1) {
			return array();
		} else {
			//Get the names of parent categories
			return $this->getCategoryPath($this->row->id, $this->row->path, true);
		}
	}

	public function isVisible()
	{
		return true;
	}
}
