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
 * Search block functions
 * @package	   block_search
 * @copyright	 Anthony Kuske <www.anthonykuske.com>
 * @license	   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

class block_search extends block_base
{

	public function init()
	{
		$this->title = get_string('pluginname', 'block_search');
	}

	// Set the content of the block when displayed as a block on a page.
	public function get_content()
	{
		global $CFG, $OUTPUT, $PAGE, $SITE;

		// Include the CSS for the block.
		$PAGE->requires->css('/blocks/search/assets/font-awesome-4.0.3/css/font-awesome.min.css');
		$PAGE->requires->css('/blocks/search/assets/css/block.css');

		require_once(dirname(__FILE__) . '/MoodleSearch/Block.php');
		$searchBlock = new \MoodleSearch\Block();

		$q = isset($_GET['q']) ? $_GET['q'] : '';

		$courseID = (is_object($this->page->course) && $this->page->course->id > 1) ? $this->page->course->id : false;
		$courseName = $courseID ? $this->page->course->fullname : $SITE->shortname;

		$this->content = new stdClass;
		$this->content->text = $searchBlock->display->showSearchBox(
			$q,
			$courseID,
			false,
			false,
			false,
			get_string(($courseID ? 'search_in_course' : 'search_all_of_site'), 'block_search', $courseName)
		);

		return $this->content;
	}

	public function applicable_formats()
	{
		return array(
			'all' => false,
			'site' => true,
			'site-index' => true,
			'course-view' => true,
			'course-view-social' => false,
			'mod' => true,
			'mod-quiz' => false
		);
	}

	/**
	 * Can multiple instance of this block be added to the same page?
	 * @return bool
	 */
	public function instance_allow_multiple()
	{
		return true;
	}

	/**
	 * Do we have a settings.php file? (Global admin settings for the block)
	 * @return bool
	 */
	public function has_config()
	{
		return true;
	}
}
