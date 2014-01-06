<?php

/*
* A model for a search result
*/

namespace MoodleSearch;

class Result
{
	private $tableName;
	private $row;

	public function __construct($tableName, $row)
	{
		$this->tableName = $tableName;
		$this->row = $row;
	}

	//Gives a human readable name for a result row
	public function name()	
	{
		if ($this->tableName == 'course') {
			return $this->row->fullname;
		} else {
			return $this->row->name;
		}
	}
	
	public function icon()
	{
	
	}
	
	public function description()
	{
		$d = $this->row->intro;
		$d = str_replace('<p></p>', '', $d);
		return trim($d);
	}
	
	public function url()
	{
		switch ($this->tableName) {
			case 'course_categories':
				return '/course/index.php?categoryid=' . $this->row->id;
			
			case 'course':
				return '/course/view.php?id=' . $this->row->id;
				
			default:
				$resourceID = $this->getGlobalInstanceIDFromModuleInstanceID($this->tableName, $this->row->id);
				return '/mod/' . $this->tableName . '/view.php?id=' . $resourceID;
		}
	}

	//Returns the unique instance ID for a resource across all of moodle, from the given ID which is unique to that module	
	private function getGlobalInstanceIDFromModuleInstanceID($moduleName, $moduleInstanceID)
	{
		global $DB;
		return $DB->get_field('course_modules', 'id', array('module' => $this->getModuleID($moduleName), 'instance' => $moduleInstanceID));
	}
	
	private function getModuleID($moduleName)
	{
		global $DB;
		return $DB->get_field('modules', 'id', array('name' => $moduleName));
	}
	
	//Returns an array with the path to this row
	// e.g. Teaching & Learning > English > English (7) > Activity Name
	public function path()
	{
		global $DB;
		
		switch ($this->tableName) {
			//Categories
			case 'course_categories':
				if ($this->row->depth <= 1) {
					return array();
				} else {
					//Get the names of parent categories
					return $this->getCategoryPath($this->row->id, $this->row->path, true);
				}
				break;	
		
			//Courses
			case 'course':
				return $this->getCategoryPath($this->row->category);
				break;
				
			//Modules
			default:
			
				//Get all info for the course this resource is in
				$course = $DB->get_record('course', array('id' => $this->row->course));
				
				//Get the id of the resource's module
				$pluginID = $this->getModuleID($this->tableName);
				
				//Get the sectionID the resource is in
				$sectionID = $DB->get_field('course_modules', 'section', array('module' => $pluginID, 'instance' => $this->row->id));
				
				//Get the name of the section
				$sectionName = $DB->get_field('course_sections', 'name', array('id' => $sectionID));
				
				$path = $this->getCategoryPath($course->category);
				$courseIcon = course_get_icon($course->id);
				$path[] = array(
					'title' => 'Course',
					'name' => $course->fullname,
					'url' => '/course/view.php?id=' . $course->id,
					'icon' => !empty($courseIcon) ? 'icon-'.$courseIcon : 'icon-archive'
				);
				$path[] = array(
					'title' => 'Section',
					'name' => $sectionName,
					'url' => '/course/view.php?id=' . $course->id . '&sectionid=' . $sectionID,
					'icon' => 'icon-th'
				);
				return $path;
		}
	}
	
	private function getCategoryPath($categoryID, $pathString = null, $removeLastCategory = false)
	{
		global $DB;
		
		if (is_null($pathString)) {
			$pathString = $DB->get_field('course_categories', 'path', array('id' => $categoryID));
		}
		
		$categoryIDs = explode('/', $pathString);
		array_shift($categoryIDs); //Remove the first item (will be empty because the path string starts with /)
		if ($removeLastCategory) {
			array_pop($categoryIDs); //Remove the last item (would cause the name to be duplicated if shown for a category result)
		}
		
		if (count($categoryIDs) < 1) {
			return array();
		}
		
		$path = array();
		foreach ($categoryIDs as $categoryID) {
			$categoryIcon = course_get_category_icon($categoryID);
			$path[] = array(
				'title' => 'Category',
				'name' => $DB->get_field('course_categories', 'name', array('id' => $categoryID)),
				'url' => '/course/index.php?categoryid=' . $categoryID,
				'icon' => !empty($categoryIcon) ? 'icon-'.$categoryIcon : 'icon-folder-open'
			);
		}
		
		return $path;
	}
}
