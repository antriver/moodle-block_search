<?php

/*
* Handy methods for getting data from Moodle with added caching etc.
*/

namespace MoodleSearch;

class DataManager
{
	private static $cache;	
	
	//Returns the unique instance ID for a resource across all of moodle, given an ID which is unique only to that module
	public static function getGlobalInstanceIDFromModuleInstanceID($moduleName, $moduleInstanceID)
	{
		return self::get_field('course_modules', 'id', array('module' => self::getModuleID($moduleName), 'instance' => $moduleInstanceID));
	}
	
	//Returns the ID for an installed module (plugin), given the name of the module
	public static function getModuleID($moduleName)
	{
		return self::get_field('modules', 'id', array('name' => $moduleName));
	}
	
	//Get a course record
	public static function getCourse($courseID)
	{
		return self::get_record('course', array('id' => $courseID));
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
		return self::get_record('course_sections', array('id' => $sectionID));
	}
	
	//Returns information about a section a resource is in
	public static function getResourceSection($moduleName, $instanceID)
	{
		//Get the id of the plugin for the module module
		$moduleID = self::getModuleID($moduleName);
				
		//Get the sectionID the resource is in
		$sectionID = self::get_field('course_modules', 'section', array('module' => $moduleID, 'instance' => $instanceID));
						
		return self::getSection($sectionID);
	}
	
	
	
	public static function canUserSeeModule($courseID, $module, $idInModule)
	{
		//Current logged in user
		global $USER;
		
		//Get the overall coursemodule ID, from the module's ID inside the plugin
		$cmid = self::getGlobalInstanceIDFromModuleInstanceID($module, $idInModule);
		
		//Load the "modinfo" for the course, and see if the module is "uservisible"
		//This is pretty expensive and is likely the source of any slowness,
		//because get_fast_modinfo loads info for all the modules in the course
		//even though we only want the one
		$modinfo = get_fast_modinfo($courseID, $USER->id);
		
		try {
			$mod = $modinfo->get_cm($cmid);
			//Throws a moodle_exception if it's not found
		} catch (\moodle_exception $e) {
			return false;
		}
		
		//If the module says it's not visible, don't show it
		if (!$mod->uservisible) {
			return false;
		}
		
		//It still might not be right to show it, because some plugins still want to be shown
		//but the user will just see "you don't have permission" when they click it
		//So let's handle each plugin that's awkward and check if the user has whatever capability applies to it
		switch ($module) {
		
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
				$capability = 'mod/lesson:manage'; //view.php only checks for :manage. Maybe there's no view capability for this plugin?
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
			$moduleContext = \context_module::instance($cmid);
			if (!has_capability($capability, $moduleContext, $USER->id)) {
				return false;
			}
		}
		
		//Now they can see it
		return true;
	}
	

	//Gets a single field from a table in the database (cached)
	private static function get_field($tableName, $fieldName, $where)
	{
		$hash = md5("field{$tableName}{$fieldName}".http_build_query($where));
		
		if ($res = self::getCache()->get($hash)) {
			return $res;
		}
		
		global $DB;
		$res =$DB->get_field($tableName, $fieldName, $where);
		
		self::getCache()->set($hash, $res);
		
		return $res;
	}
	
	//Gets a single row from a table in the database (cached)
	private static function get_record($tableName, $where)
	{
		$hash = md5("record{$tableName}".http_build_query($where));
		
		if ($res = self::getCache()->get($hash)) {
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
		
		self::$cache = \cache::make_from_params(\cache_store::MODE_APPLICATION, 'block_search', 'cache');
		
		return self::$cache;
	}
	
	//Returns the current time in microseconds.
	//Used for timing how long things take
	public static function getDebugTime() 
	{ 
		$timer = explode( ' ', microtime()); 
		$timer = $timer[1] + $timer[0]; 
		return $timer; 
	}
	
	public static function debugTimeTaken($startTime)
	{
		return round((self::getDebugTime() - $startTime), 4);
	}

}