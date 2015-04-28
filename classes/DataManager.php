<?php

/**
 * This class contains a lot of static methods to make it easier to grab
 * info from Moodle. Most importantly, most of these methods used
 * cached info to avoid hitting the database.
 *
 * @package    block_search
 * @copyright  Anthony Kuske <www.anthonykuske.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_search;

use cache;
use cm_info;
use context_module;
use moodle_exception;
use xmldb_field;
use xmldb_table;

class DataManager
{
	private static $cache;

	//Returns the unique instance ID for a resource across all of moodle
	//given an ID which is unique only to that module
	public static function getGlobalInstanceIDFromModuleInstanceID($moduleName, $moduleInstanceID)
	{
		return self::getDBField(
			'course_modules',
			'id',
			array(
				'module' => self::getModuleID($moduleName),
				'instance' => $moduleInstanceID
			)
		);
	}

	//Returns the ID for an installed module (plugin), given the name of the module
	public static function getModuleID($moduleName)
	{
		return self::getDBField('modules', 'id', array('name' => $moduleName));
	}

	//Get a course record
	public static function getCourse($courseID)
	{
		return self::getDBRecord('course', array('id' => $courseID));
	}

	//Returns the fullname for a course
	public static function getCourseName($courseID)
	{
		$course = self::getCourse($courseID);
		return $course->fullname;
	}

	//Returns the row for a section in a course
	public static function getSection($sectionID)
	{
		return self::getDBRecord('course_sections', array('id' => $sectionID));
	}

	//Returns information about a section a resource is in
	public static function getResourceSection($moduleName, $instanceID)
	{
		//Get the module id from the module's name
		$moduleID = self::getModuleID($moduleName);

		if (!$moduleID) {
			return false;
		}

		//Get the sectionID the resource is in
		$sectionID = self::getDBField(
			'course_modules',
			'section',
			array(
				'module' => $moduleID,
				'instance' => $instanceID
			)
		);

		if (!$sectionID) {
			return false;
		}

		return self::getSection($sectionID);
	}

	public static function getResoureSectionFromCourseModuleID($courseModuleID)
	{
		$sectionID = self::getDBField(
			'course_modules',
			'section',
			array(
				'id' => $courseModuleID
			)
		);

		if (!$sectionID) {
			return false;
		}

		return self::getSection($sectionID);
	}



	public static function canUserSeeModule($courseID, $moduleName, $idInModule)
	{
		if (!$courseID || !$idInModule) {
			return false;
		}

		//Current logged in user
		global $USER;

		//Get the overall coursemodule ID, from the module's ID inside the plugin
		$cmid = self::getGlobalInstanceIDFromModuleInstanceID($moduleName, $idInModule);

		if (!$cmid) {
			return false;
		}

/*
		// Create our own cm_info instance for this module
		// because using get_fast_modinfo is horrible inefficient
		// Begin new way...

		global $DB;
		$course = $DB->get_record('course', array('id' => $courseID));
		if (!$course) {
			return false;
		}

		$course_modinfo = new DummyCourseModinfo($courseID);

		$courseModuleRow = $DB->get_record('course_modules', array('id' => $cmid));
		if (!$courseModuleRow) {
			return false;
		}

		$moduleTableRow = $DB->get_record($moduleName, array('id' => $idInModule));
		if (!$moduleTableRow) {
			return false;
		}

		$mod = (object) array_merge((array)$courseModuleRow, (array)$moduleTableRow);
		$mod->id = $idInModule;
		$mod->cm = $cmid;
		$mod->mod = $moduleName;

		$cm_info = new cm_info($course_modinfo, $course, $mod, false);
		$cm_info->obtain_dynamic_data();

		if (!$cm_info->uservisible) {
			return false;
		}

		// End new way.
*/

		// Begin old way...

		//Load the "modinfo" for the course, and see if the module is "uservisible"
		//This is pretty expensive and is likely the source of any slowness,
		//because get_fast_modinfo loads info for all the modules in the course
		//even though we only want the one
		$modinfo = get_fast_modinfo($courseID, $USER->id);

		try {
			$cm = $modinfo->get_cm($cmid);

			if (!$cm->uservisible) {
				return false;
			}

			//Throws a moodle_exception if it's not found
		} catch (moodle_exception $e) {
			return false;
		}

		// End old way.


		//It still might not be right to show it, because some plugins still want to be shown
		//but the user will just see "you don't have permission" when they click it
		//So let's handle each plugin that's awkward and check if the user has whatever capability applies to it
		switch ($moduleName) {

			case 'chat':
				$capability = 'mod/chat:chat';
				break;

			case 'choice':
				$capability = 'mod/choice:readresponses';
				break;

			case 'data':
				$capability = 'mod/data:viewentry';
				break;

			case 'forum':
				$capability = 'mod/forum:viewdiscussion';
				break;

			/*case 'lesson':
				//The view.php only checks for :manage. Maybe there's no view capability for this plugin?
				$capability = 'mod/lesson:manage';
				break;*/

			/*case 'survey': //questionnaire the same plugin?
				$capability = 'mod/questionnaire:view';
				break;*/

			case 'wiki':
				$capability = 'mod/wiki:viewpage';
				break;

			case '	book':
				$capability = 'mod/book:read';
				break;

			case 'label':
				//There's no view capability for labels - everybody can see
				break;
		}

		//If this plugin has a capability we can check
		if (!empty($capability)) {
			//Check if the user has the capability within the module context
			$moduleContext = context_module::instance($cmid);
			if (!has_capability($capability, $moduleContext, $USER->id)) {
				return false;
			}
		}

		//Now they can see it
		return true;
	}


	//Gets a single field from a table in the database (cached)
	private static function getDBField($tableName, $fieldName, $where)
	{
		$hash = md5("field{$tableName}{$fieldName}".http_build_query($where));

		if (false && $res = self::getCache()->get($hash)) {
			return $res;
		}

		global $DB;
		$res = $DB->get_field($tableName, $fieldName, $where);

		self::getCache()->set($hash, $res);

		return $res;
	}

	//Gets a single row from a table in the database (cached)
	private static function getDBRecord($tableName, $where)
	{
		$hash = md5("record{$tableName}".http_build_query($where));

		if (false && $res = self::getCache()->get($hash)) {
			return $res;
		}

		global $DB;
		$res = $DB->get_record($tableName, $where);

		self::getCache()->set($hash, $res);

		return $res;
	}

	//Returns the cache object
	//Creates a new one when called for the first time
	public static function getCache()
	{
		if (!empty(self::$cache)) {
			return self::$cache;
		}

		self::$cache = cache::make('block_search', 'main');

		return self::$cache;
	}

	public static function getTablesPossibleToSearch()
	{
		global $DB;

		$tables = array();

		//Courses table
		$tables['course'] = 'course (fullname, shortname)';

		//Category table
		$tables['course_categories'] = 'course_categories (name, description)';

		//Database manager object
		$dbman = $DB->get_manager();

		//Get all modules (plugins) - we're going to search their tables
		$modules = $DB->get_records('modules', array(), 'name');

		foreach ($modules as $module) {

			$tableName = $module->name;
			$tableFields = array();

			//Create an xmldb object from the name of this table
			$table = new xmldb_table($tableName);

			//Skip this module if it has no table
			//(Only checks if a table with the same name as the module exists)
			if (!$dbman->table_exists($table)) {
				continue;
			}

			//We want to check if these fields exist in the table
			$possibleFields = array('name', 'intro');

			if ($tableName == 'page') {
					$possibleFields[] = 'content';
			}

			//Check if each of these fields (columns) exists in the table
			foreach ($possibleFields as $fieldName) {

				//Create an xmldb object for this field's name
				$field = new xmldb_field($fieldName);

				//If this field exists in the table, we're going to search in it
				if ($dbman->field_exists($table, $field)) {
					$tableFields[] = $fieldName;
				}

			}

			$tables[$tableName] = $tableName . ' (' .implode(', ', $tableFields) . ')';

		} //end foreach module

		return $tables;
	}

	//Returns the current time in microseconds.
	//Used for timing how long things take
	public static function getDebugTime()
	{
		$timer = explode(' ', microtime());
		$timer = $timer[1] + $timer[0];
		return $timer;
	}

	public static function debugTimeTaken($startTime)
	{
		return round((self::getDebugTime() - $startTime), 4);
	}
}
