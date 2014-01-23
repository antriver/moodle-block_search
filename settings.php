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
 * Admin settings page for search block
 * @package	   block_search
 * @copyright	 Anthony Kuske <www.anthonykuske.com>
 * @license	   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

if (!function_exists('block_search_get_searchable_tables')) {
	function block_search_get_searchable_tables()
	{
		global $DB;
		
		$tables = array();
		
		//Courses table
		$tables['course'] = 'course (fullname, shortname)';
		
		//Database manager object
		$dbman = $DB->get_manager();
		
		//Get all modules (plugins) - we're going to search their tables
		$modules = $DB->get_records('modules', array(), 'name');
		
		foreach ($modules as $module) {
		
			$tableName = $module->name;
			$tableFields = array();
			
			//Create an xmldb object from the name of this table
			$table = new \xmldb_table($tableName);
		
			//Skip this module if it has no table
			//(Only checks if a table with the same name as the module exists)
			if (!$dbman->table_exists($table)) {
				continue;
			}
			
			//We want to check if these fields exist in the table
			$possibleFields = array('name', 'intro');
		
			//Check if each of these fields (columns) exists in the table
			foreach ($possibleFields as $fieldName) {
	
				//Create an xmldb object for this field's name
				$field = new \xmldb_field($fieldName);
				
				//If this field exists in the table, we're going to search in it
				if ($dbman->field_exists($table, $field)) {
					$tableFields[] = $fieldName;
				}
				
			}

			$tables[$tableName] = $tableName . ' (' .implode(', ',$tableFields) . ')';
				
		} //end foreach module
		
		return $tables;
	}
}

/*$settings->add(
	new admin_setting_heading(
		'sampleheader',
		'Section Title',
		'Section description goes here.'
	)
);*/

//Tables

//($name, $visiblename, $description, $defaultsetting, $choices)
$settings->add(
	new admin_setting_configmulticheckbox(
		'block_search/search_tables',
		get_string('settings_search_tables_name', 'block_search'),
		get_string('settings_search_tables_desc', 'block_search') . ' <a href="#" onclick="Y.all(\'#admin-search_tables input[type=checkbox]\').set(\'checked\', true); return false;">[' . get_string('selectall', 'block_search') . ']</a>',
		implode(',', array_keys(block_search_get_searchable_tables())),
		block_search_get_searchable_tables()
	)
);

//Cache

//($name, $visiblename, $description, $defaultsetting, $paramtype=PARAM_RAW, $size=null)
$settings->add(
	new admin_setting_configtext(
		'block_search/cache_results',
		get_string('settings_cache_results_name', 'block_search'),
		get_string('settings_cache_results_desc', 'block_search'),
		86400,
		PARAM_INT
	)
);
