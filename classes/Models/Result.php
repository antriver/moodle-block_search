<?php

/**
 * Represents a single search result from a table
 *
 * @package    block_search
 * @copyright  Anthony Kuske <www.anthonykuske.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_search\Models;

use context_course;
use moodle_url;

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

	// Returns the URL to take the user to when clicked
	abstract public function url();

	// Returns an array with the path to this row
	// e.g. Teaching & Learning > English > English (7) > Activity Name
	abstract public function path();

	// Returns the HTML to display an icon for a result
	abstract public function icon();

	// Checks if the current logged in user has access to this item
	// Should return true if it is visible
	// The name of a language string containing an error message if not
	// Or null if it should never be displayed to anybody (if it's broken - because the course
	// it's in is missing for example)
	abstract public function isVisible();

	// Gives a human readable name for a result row
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

			// This is really an SSIS tweak, but if your Moodle has this function to find
			// and icon to use for a category it should return the name of a fontawesome 4 icon here
			if (function_exists('\course_get_category_icon')) {
				$categoryIcon = \course_get_category_icon($categoryID);
			} else {
				$categoryIcon = false;
			}
			$path[] = array(
				'title' => 'Category',
				'name' => $DB->get_field('course_categories', 'name', array('id' => $categoryID)),
				'url' => new moodle_url('/course/index.php', array('categoryid' => $categoryID)),
				'icon' => !empty($categoryIcon) ? 'fa fa-'.$categoryIcon : 'fa fa-folder-open'
			);
		}

		return $path;
	}

	public function getRow()
	{
		return $this->row;
	}

	/**
	 * Returns true if the current user is enrolled in the given courseID
	 * An error string if not
	 * null if the course doesn't exist
	 */
	protected function isCourseVisible($courseID)
	{
		if (!$courseID) {
			error_log(
				"Found a result while searching that has no course ID" .
				" Table: " . $this->tableName .
				" ID in table: " . $this->row->id .
				" CourseID: " . $courseID
			);
			return null;
		}

		global $USER;

		$coursecontext = context_course::instance($courseID, IGNORE_MISSING);
		if (!$coursecontext) {
			//If the course ID is set, but doesn't exist
			error_log(
				"Found a result while searching, but its course doesn't exist!" .
				" Table: " . $this->tableName .
				" ID in table: " . $this->row->id .
				" CourseID: " . $courseID
			);
			return null;
		}

		if (is_enrolled($coursecontext, $USER)) {
			return true;
		} else {
			return 'notenrolled';
		}
	}
}
