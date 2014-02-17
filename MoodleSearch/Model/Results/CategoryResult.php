<?php

namespace MoodleSearch;

class CategoryResult extends Result
{
	public function icon()
	{
		if (function_exists('\course_get_category_icon')) {
			$categoryIcon = \course_get_category_icon($this->row->id);
			return \html_writer::tag('i', '', array('class' => 'icon-' . $categoryIcon));
		} else {
			return \html_writer::tag('i', '', array('class' => 'icon-folder-open'));
		}
	}

	public function url()
	{
		return '/course/index.php?categoryid=' . $this->row->id;
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

}
