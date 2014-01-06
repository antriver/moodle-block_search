<?php

/*
* Class with methods to abstract away getting certain pieces of data so we can apply lots of caching
*/

namespace MoodleSearch;

class Data
{
	private static $cache;	
	
	//Returns the unique instance ID for a resource across all of moodle, from an ID which is unique only to that module
	public static function getGlobalInstanceIDFromModuleInstanceID($moduleName, $moduleInstanceID)
	{
		return self::get_field('course_modules', 'id', array('module' => self::getModuleID($moduleName), 'instance' => $moduleInstanceID));
	}
	
	//Returns the ID for an installed module, given the name of the module
	public static function getModuleID($moduleName)
	{
		return self::get_field('modules', 'id', array('name' => $moduleName));
	}
	
	public static function getCourse($courseID)
	{
		return self::get_record('course', array('id' => $courseID));
	}
	
	public static function getCourseName($courseID)
	{
		$course = self::getCourse($courseID);
		return $course->fullname;
	}
	
	public static function getSection($sectionID)
	{
		return self::get_record('course_sections', array('id' => $sectionID));
	}
	
	public static function getResourceSection($moduleName, $instanceID)
	{
		
		//Get the id of the plugin for the module module
		$moduleID = self::getModuleID($moduleName);
				
		//Get the sectionID the resource is in
		$sectionID = self::get_field('course_modules', 'section', array('module' => $moduleID, 'instance' => $instanceID));
						
		return self::getSection($sectionID);
	}
	
	
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
	
	public static function getCache()
	{
		if (!empty(self::$cache)) {
			return self::$cache;
		}
		
		self::$cache = \cache::make_from_params(\cache_store::MODE_APPLICATION, 'block_search', 'cache');
		
		return self::$cache;
	}

}