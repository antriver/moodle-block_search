<?php

/**
* Class for performing a search in all courses, categories an modules
*/

class MoodleSearch
{

	private $debug = false;
	private $tables = false;

	public function __construct($debug = false)
	{
		$this->debug = $debug;
		$this->tables = $this->getFieldsToSearch();
	}

	//Perform a search for the given string
	public function search($q)
	{
		return $this->getResults($q);
	}
	
	//Builds an associative array of which fields in which tables to search in
	private function getFieldsToSearch()
	{
		global $DB;
		
		//Courses and categories
		$tables = array(
			'course_categories' => array('name'),
			'course' => array('fullname', 'shortname'),
		);

		//Database manager object
		$dbman = $DB->get_manager();
		
		//Get all modules (activities) - we're going to search their tables
		$modules = $DB->get_records('modules', array(), 'name');
		
		foreach ($modules as $module) {
		
			//Create an xmldb object from the name of this table
			$table = new xmldb_table($module->name);
		
			//Skip this module if it has no table
			//(Only checks if a table with the same name as the module exists)
			if (!$dbman->table_exists($table)) {
				continue;
			}
		
			//We want to check if these fields exist in the table
			$moduleFields = array('name', 'intro');
		
			//Check if each of these fields (columns) exists in the table
			foreach ($moduleFields as $fieldName) {
	
				//Create an xmldb object for this field's name
				$field = new xmldb_field($fieldName);
				
				//If this field exists in the table, we're going to search in it
				if ($dbman->field_exists($table, $field)) {
					$tables[$module->name][] = $fieldName;
				}
				
			}
				
		} //end foreach module
		
		return $tables;
	}
	
	//Search for rows which match the search query
	//Returns an associative array of the tables that were searched
	function getResults($q)
	{
		global $DB;
		
		if (empty($this->tables)) {
			throw new Exception('Trying to search, but no tables have been specified to search in.');
		}
		
		$results = array();
		$q = strtolower($q);
		$q = "%{$q}%";
		
		//Search each table we're supposed to search in
		foreach ($this->tables as $table => $fields) {
		
			//Array of query values
			$values = array();
			
			//Build the SQL query
			$where = '';
			foreach ($fields as $fieldName) {
				$where .= ' OR LOWER(' . $fieldName . ') SIMILAR TO ?';
				$values[] = $q;
			}
			$where = ltrim($where, ' OR ');
		
			//Full query
			$sql = 'SELECT * FROM {' . $table . '} WHERE ' . $where;

			if ($this->debug) {
				echo "\n" . $sql;
			}
		
			//Run the query and return the matched rows
			$results[$table] = $DB->get_records_sql($sql, $values);
		}
		
		return $results;
	}
	

}