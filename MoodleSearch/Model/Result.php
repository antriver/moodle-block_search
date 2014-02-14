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

abstract class Result
{
	protected $row;
	public $tableName;
	public $hidden = false;
	public $hiddenReason = '';

	public function __construct($tableName, $row)
	{
		$this->tableName = $tableName;
		$this->row = $row;
	}

	//Returns the URL to take the user to when clicked
	public abstract function url();

	//Returns an array with the path to this row
	// e.g. Teaching & Learning > English > English (7) > Activity Name
	public abstract function path();

	//Returns the HTML to display an icon for a result
	public abstract function icon();

	//Checks if the current logged in user has access to this item
	//Subclasses should override this. But the default here is just to make everything visible
	//Should return either true, or the name of a language string containing an error message.
	public function isVisible()
	{
		return true;
	}

	//Gives a human readable name for a result row
	public function name()
	{
		return $this->row->name;
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

	protected function getCategoryPath($categoryID, $pathString = null, $removeLastCategory = false)
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
