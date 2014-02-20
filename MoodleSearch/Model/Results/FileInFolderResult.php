<?php

namespace MoodleSearch;

class FileInFolderResult extends Result
{
	public function icon()
	{
		global $OUTPUT;
		#return '';
		return $OUTPUT->pix_icon(file_mimetype_icon($this->row->mimetype), '');
	}

	public function name()
	{
		return $this->row->filename;
	}

	public function url()
	{
		return '/mod/folder/view.php?id=' . $this->row->folderid;
	}

	public function path()
	{
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
			'url' => '/course/view.php?id=' . $course->id,
			'icon' => !empty($courseIcon) ? 'icon-'.$courseIcon : 'icon-archive'
		);

		//Get all info for the course section this resource is in
		$section = DataManager::getResoureSectionFromCourseModuleID($this->row->moduleid);
		if ($section->name) {
			$path[] = array(
				'title' => 'Section',
				'name' => $section->name,
				'url' => '/course/view.php?id=' . $course->id . '&sectionid=' . $section->id,
				'icon' => 'icon-th'
			);
		}

		$path[] = array(
			'title' => 'Folder',
			'name' => $this->row->foldername,
			'url' => '/mod/folder/view.php?id=' . $this->row->folderid,
			'icon' => 'icon-folder-close'
		);

		foreach (explode('/', $this->row->filepath) as $folder) {
			if ($folder) {
				$path[] = array(
					'title' => 'Folder',
					'name' => $folder,
					'url' => $this->url(),
					'icon' => 'icon-folder-close'
				);
			}
		}

		return $path;
	}

	public function isVisible()
	{
		global $USER;

		if (!$this->row->modulevisible) {
			return false;
		}

		$coursecontext = \context_course::instance($this->row->courseid);
		if (!is_enrolled($coursecontext, $USER)) {
			return 'notenrolled';
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
