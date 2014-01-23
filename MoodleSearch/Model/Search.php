<?php

/**
* Class for performing a search in all courses, categories an modules
*/

namespace MoodleSearch;

class Search
{
	private $q = false;
	private $results = null;
	private $courseID = false;	
	private $tables = false;
	private $info = array();

	public function __construct($q, $courseID = false)
	{
		$this->q = $q;
		$this->courseID = $courseID;
		$this->tables = $this->getFieldsToSearch();
		$this->results = $this->runSearch();
	}
	
	public function getResults()
	{
		if ($this->results ===  null) {
			throw new \Exception('Trying to get results before the search has been run.');
		}	
		return $this->results;
	}

	//Builds an associative array of which fields in which tables to search in
	private function getFieldsToSearch()
	{
		global $DB;
		
		if (!$this->courseID) {
			//Courses and categories
			$tables = array(
				//'course_categories' => array('name'),
				'course' => array('fullname', 'shortname'),
			);
		}

		//Database manager object
		$dbman = $DB->get_manager();
		
		//Get all modules (activities) - we're going to search their tables
		$modules = $DB->get_records('modules', array(), 'name');
		
		foreach ($modules as $module) {
		
			//Create an xmldb object from the name of this table
			$table = new \xmldb_table($module->name);
		
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
				$field = new \xmldb_field($fieldName);
				
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
	public function runSearch()
	{
		if (empty($this->q)) {
			throw new \Exception('No query was given.');
		}
	
		if (empty($this->tables)) {
			throw new \Exception('Trying to search, but no tables have been specified to search in.');
		}
		
		$startTime = DataManager::getDebugTime();
		
		$hash = md5('search'.$this->q.'courseid'.$this->courseID);
		
		//Check if cached results exists
		$results = DataManager::getCache()->get($hash);
		if (is_array($results)) {
			$results['searchTime'] = DataManager::debugTimeTaken($startTime);
			$results['cached'] = true;
			return $results;
		}

		global $DB;
		
		$results = array(
			'tables' => array(),
			'generated' => date('Y-m-d H:i:s'),
			'searchTime' => 0,
			'cached' => false,
		);
		$q = strtolower($this->q);
		$q = "%{$q}%";
		
		//Search each table we're supposed to search in
		foreach ($this->tables as $table => $fields) {
		
			$where = '';
		
			//Array of query values
			$values = array();
			
			//Build the SQL query
			foreach ($fields as $fieldName) {
				$where .= ' OR LOWER(' . $fieldName . ') SIMILAR TO ?';
				$values[] = $q;
			}
			$where = ltrim($where, ' OR ');
			
			if ($this->courseID) {
				$where = "({$where})";
				$where .= ' AND course = ?';
				$values[] = $this->courseID; 
			}
		
			//Full query
			$sql = 'SELECT * FROM {' . $table . '} WHERE ' . $where;
			
			//Run the query and return the matched rows
			if ($tableResults = $DB->get_records_sql($sql, $values)) {
				$results['tables'][$table] = $tableResults;
			}
		}
		
		if (count($results['tables']) < 1) {
			DataManager::getCache()->set($hash, $results);
			$results['searchTime'] = DataManager::debugTimeTaken($startTime);
			return $results;
		}
		
		require_once __DIR__ . '/Result.php';
		
		//Convert the rows from the database into Result objects
		foreach ($results['tables'] as $tableName => &$tableResults) {
			foreach ($tableResults as &$row) {
				$row = new Result($tableName, $row);
			}
		}
		
		DataManager::getCache()->set($hash, $results);
		
		$results['searchTime'] = DataManager::debugTimeTaken($startTime);
		
		return $results;
	}
	
	

	/**
	* Go through the array of results and remove those the user doesn't have permission to see
	*/
	function filterResults($removeHiddenResults = true)
	{
		global $USER;
		
		$startTime = DataManager::getDebugTime();
		
		foreach ($this->results['tables'] as $tableName => &$tableResults) {
			foreach ($tableResults as $i => &$result) {
			
				switch ($tableName) {
				
					//Remove unenroled courses
					case 'course':
						$this->removeResultIfUserNotEnroledInCourse($result->getRow()->id, $i, $tableResults, $removeHiddenResults);
						break;
						
					//Remove resources from unenroled courses
					default:
						if (!$this->removeResultIfUserNotEnroledInCourse($result->getRow()->course, $i, $tableResults, $removeHiddenResults)) {
							$this->removeResourceIfHidden($result, $i, $tableResults, $removeHiddenResults);
						}
						break;
				}			
			
			}
		}
		
		if ($removeHiddenResults) {
		
			//Now remove tables that have no results left
			foreach ($this->results['tables'] as $tableName => $tableResults) {
				if (count($tableResults) < 1) {
					unset($this->results['tables'][$tableName]);
				}
			}
			
		} else {
		
			//Hidden results are included, but we want them to go to the bottom
			//Sort each table's results by 'hidden'
			foreach ($this->results['tables'] as $tableName => &$tableResults) {
				usort($tableResults, function($a, $b) {
					return $a->hidden - $b->hidden;
				});
			}
			
		}
		
		$this->results['filterTime'] = DataManager::debugTimeTaken($startTime);
	}

	
	/*
	*	Checks if a user is enroled in the given courseid.
	*	If not...
	*		If $remove == true
	*			Item number $i is removed from the array (reference) $tableResults
	*			Then returns true
	*		if $remove == false
	*			Item number $i is in the the array (reference) $tableResults has its 'hidden' property set to true
	*			Then returns true
	*/
	private function removeResultIfUserNotEnroledInCourse($courseid, $i, &$tableResults, $remove = true)
	{
		global $USER;
		
		$coursecontext = \context_course::instance($courseid);
		
		if (is_enrolled($coursecontext, $USER)) {
			//They're enroled in the course. We don't have to do anyhting
			return false;
		} else {
			if ($remove) {
				unset($tableResults[$i]);
			} else {
				$tableResults[$i]->hiddenReason = 'notenrolled';
				$tableResults[$i]->hidden = true;
			}
			return true;
		}
	}
	
	
	
	/*
		Checks if a user is can view a resource even if they're enroled in the course
		If not...
			If $remove == true
				Item number $i is removed from the array (reference) $tableResults
			if $remove == false
				Item number $i is in the the array (reference) $tableResults has its 'hidden' property set to true
	*/
	private function removeResourceIfHidden($result, $i, &$tableResults, $remove = true)
	{
		
		$visible = DataManager::canUserSeeModule($result->getRow()->course, $result->tableName, $result->getRow()->id);
		//var_dump($result->name());
		//var_dump($visible);
		//echo '<br/>';
		if ($visible) {
			//They can see it. Yay
		} else if ($remove) {
			unset($tableResults[$i]);
		} else {
			$tableResults[$i]->hiddenReason = 'notvisible';
			$tableResults[$i]->hidden = true;
		}
	}
	
}
