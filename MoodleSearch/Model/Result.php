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
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.	 See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Represents a single search result from a table
 * @package	   block_search
 * @copyright	 Anthony Kuske <www.anthonykuske.com>
 * @license	   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
 
namespace MoodleSearch;

class Result
{
	private $row;
	public $tableName;
	public $hidden = false;
	public $hiddenReason = '';

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
	
	public function description()
	{
		if (empty($this->row->intro)) {
			return false;
		}
		
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
				$resourceID = DataManager::getGlobalInstanceIDFromModuleInstanceID($this->tableName, $this->row->id);
				return '/mod/' . $this->tableName . '/view.php?id=' . $resourceID;
		}
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
				
			//Resources in courses
			default:
			
				//Get all info for the course this resource is in
				$course = DataManager::getCourse($this->row->course);
				
				//Get all info for the course section this resource is in
				$section = DataManager::getResourceSection($this->tableName, $this->row->id);
				
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
					'name' => $section->name,
					'url' => '/course/view.php?id=' . $course->id . '&sectionid=' . $section->id,
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
		
		//Remove the first item
		//(will be empty because the path string starts with /)
		array_shift($categoryIDs);
		
		if ($removeLastCategory) {
			//Remove the last item
			//(would cause the name to be duplicated if shown for a category result)
			array_pop($categoryIDs);
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
	
	public function getRow()
	{
		return $this->row;
	}
}
