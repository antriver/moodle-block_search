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
 * Object to represent an entire search action.
 * Creating a new search runs the search. Use getResults to get the results.
 * Will return an array of MoodleSearch\Result objects.
 * @package	   block_search
 * @copyright	 Anthony Kuske <www.anthonykuske.com>
 * @license	   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
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
		
		//Load the config setting for tables to search
		//(A comma seperated list of table names without the prefix)
		$tablesToSearch = get_config('block_search', 'search_tables');
		$tablesToSearch = explode(',', $tablesToSearch);
		
		//This array will hold the tables => array('fields') we'll actually search
		$fieldsToSearch = array();
		
		//Database manager object
		$dbman = $DB->get_manager();
		
		//Go through each table the admin wants to be searched and set which fields from that table to search
		//(Makes sure the tables and fields exist)
		foreach ($tablesToSearch as $tableName) {
		
			switch ($tableName) {
			
				case 'course':
					//Don't show other courses if we're searching in a course
					if ($this->courseID) {
						continue;
					}
					$fieldsToSearch['course'] = array('fullname', 'shortname');
					break;
					
				default:
					//Create an xmldb object from the name of this table
					$table = new \xmldb_table($tableName);
				
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
							$fieldsToSearch[$tableName][] = $fieldName;
						}
						
					}
					break;	
			}
		}
		
		return $fieldsToSearch;
	}
	
	//Search for rows which match the search query
	//Returns an associative array of the tables that were searched
	private function runSearch()
	{
		if (empty($this->q)) {
			throw new \Exception('No query was given.');
		}
	
		if (empty($this->tables)) {
			throw new \Exception('Trying to search, but no tables have been specified to search in.');
		}
		
		$startTime = DataManager::getDebugTime();
		
		$cache_for = get_config('block_search', 'cache_results');
		$cache = $cache_for > 0 ? true : false;
		
		if ($cache) {
		
			$hash = md5('search' . strtolower($this->q) . 'courseid' . $this->courseID);
		
			//Check if cached results exists
			$results = DataManager::getCache()->get($hash);
		
			if (is_array($results)) {
		
				//If the cached results are newer than than the cache_results setting we'll use them
				if ($results['generated'] > (time() - (int)$cache_for)) {
					$results['searchTime'] = DataManager::debugTimeTaken($startTime);
					$results['cached'] = true;
					return $results;		
				}		
			}
			
		}

		global $DB;
		
		$results = array(
			'tables' => array(),
			'generated' => time(),
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
		
		if ($cache) {
			DataManager::getCache()->set($hash, $results);
		}
		
		$results['searchTime'] = DataManager::debugTimeTaken($startTime);
		
		return $results;
	}
	
	

	/**
	* Go through the array of results and remove those the user doesn't have permission to see
	*/
	public function filterResults($removeHiddenResults = true)
	{
		global $USER;
		
		//Site admin can see everything so don't bother filtering
		if (is_siteadmin()) {
			return;
		}
		
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
