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
	private $refreshCache = false;

	public function __construct($q, $courseID = false)
	{
		$this->q = $q;
		$this->courseID = $courseID;
		$this->results = $this->runSearch();

		if (isset($_GET['refresh'])) {
			$this->refreshCache = true;
		}
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

		$this->tables = $this->getFieldsToSearch();

		if (empty($this->tables)) {
			throw new \Exception('Trying to search, but no tables have been specified to search in.');
		}

		$startTime = DataManager::getDebugTime();

		$cache_for = get_config('block_search', 'cache_results');
		$cache = $cache_for > 0 ? true : false;
$cache = false;
		if ($cache) {

			$hash = md5('search' . strtolower($this->q) . 'courseid' . $this->courseID);

			if (!$this->refreshCache) {
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

		}

		global $DB;

		$results = array(
			'tables' => array(),
			'generated' => time(),
			'searchTime' => 0,
			'cached' => false,
			'total' => 0
		);

		//Search each table we're supposed to search in
		foreach ($this->tables as $table => $fields) {
			$where = '';

			//Array of query values
			$queryParameters = array();

			//Build the SQL query
			foreach ($fields as $fieldName) {
				$where .= $this->buildWordQuery($fieldName, $this->q, $queryParameters) . ' OR ';
			}
			$where = rtrim($where, 'OR ');

			if ($this->courseID) {
				$where = "({$where})";
				$where .= ' AND course = ?';
				$queryParameters[] = $this->courseID;
			}

			//Full query
			$sql = 'SELECT * FROM {' . $table . '} WHERE ' . $where;

			//Run the query and return the matched rows
			if ($tableResults = $DB->get_records_sql($sql, $queryParameters)) {
				$results['tables'][$table] = $tableResults;
			}
		}

		//Also search files in folders
		//TODO: This isn't ready yet
		//$results['tables']['filesInFolders'] = $this->searchFilesInFolders();

		if (count($results['tables']) < 1) {
			DataManager::getCache()->set($hash, $results);
			$results['searchTime'] = DataManager::debugTimeTaken($startTime);
			return $results;
		}

		//Convert the rows from the database into Result objects
		foreach ($results['tables'] as $tableName => &$tableResults) {
			switch ($tableName) {
				case 'course':
					$className = 'CourseResult';
					break;

				case 'course_categories':
					$className = 'CategoryResult';
					break;

				case 'filesInFolders':
					$className = 'FileInFolderResult';
					break;

				default:
					$className = 'ModuleResult';
					break;
			}
			$className = '\MoodleSearch\\' . $className;

			foreach ($tableResults as &$row) {
				$row = new $className($tableName, $row);
				++$results['total'];
			}
		}

		if ($cache) {
			DataManager::getCache()->set($hash, $results);
		}

		$results['searchTime'] = DataManager::debugTimeTaken($startTime);

		return $results;
	}


	/**
	 * Find files in folder modules
	 * This is a bit more complicated than a simple search, hence the separate method
	 * @return [type] [description]
	 */
	private function searchFilesInFolders()
	{
		global $DB;

		if (empty($this->q)) {
			throw new \Exception('No query was given.');
		}

		$sql = '
SELECT
	files.id,
	files.filepath,
	files.filename,
	files.mimetype,
	context.instanceid as folderid,
	context.id as contextid,
	course_modules.id as moduleid,
	course_modules.visible as modulevisible,
	folder.name as foldername,
	folder.course as courseid
FROM
	{files} files
JOIN
	{context} context ON files.contextid = context.id
JOIN
	{course_modules} course_modules ON course_modules.id = context.instanceid
JOIN
	{folder} folder ON folder.id = course_modules.instance
WHERE ';

		$queryParameters = array();
		$sql .= $this->buildWordQuery('files.filename', $this->q, $queryParameters);

		$fileResults = $DB->get_records_sql($sql, $queryParameters);

		return $fileResults;
	}



	/**
	 * Splits the query string into words and phrases as appropriate and returns
	 * a portion of to match the given column name against.
	 *
	 * e.g. 'Two Words'
	 * returns
	 * ( columnName LIKE %two%' AND columnName LIKE %words% )
	 */
	private function buildWordQuery($columnName, $searchTerms, &$queryParameters = array())
	{
		$searchTerms = strtolower($searchTerms);

		$columnName = "LOWER({$columnName})";

		//Replace character for wildcards
		$searchTerms = str_replace('*', '%', $searchTerms);

		// "Words in quotes" to search exact phrases
		$queryExact = '';
		if (preg_match_all('/"[\w|\s|\']+"/i', $searchTerms, $matches)) {
			foreach ($matches[0] as $match) {
				$queryExact .= "{$columnName} LIKE ? AND ";

				//Remove the match from the search terms because we're done with it
				$searchTerms = str_replace($match, '', $searchTerms);

				//Remove quotes from the match
				$match = trim($match, '"');
				$queryParameters[] = '%' . $match . '%';
			}
		}
		// -Word to exclude words
		$queryExclude = '';
		if (preg_match_all('/\-\w+/i', $searchTerms, $matches)) {
			foreach ($matches[0] as $match) {

				$queryExclude .= "{$columnName} NOT LIKE ? AND ";

				//Remove the match from the search terms because we're done with it
				$searchTerms = str_replace($match, '', $searchTerms);

				//Remove - from the match
				$match = ltrim($match, '-');
				$queryParameters[] = '%' . $match . '%';
			}
		}

		//Now the advanced parameters have been dealt with and removed from $searchTerms
		//We're just left with words we want to look for
		$queryWords = '';
		$searchTerms = trim($searchTerms);
		$searchWords = explode(' ', trim($searchTerms));
		foreach ($searchWords as $word) {
			if (empty($word)) {
				continue;
			}

			$queryWords .= "{$columnName} LIKE ? AND ";
			$queryParameters[] = '%' . $word . '%';
		}

		//Now stick it together
		$where = '(' . $queryExact . $queryExclude . $queryWords;
		$where = rtrim($where, 'AND ') . ')';

		return $where;
	}



	/**
	* Go through the array of results and remove those the user doesn't have permission to see
	*/
	public function filterResults($removeHiddenResults = true)
	{
		//Site admin can see everything so don't bother filtering
		if (is_siteadmin()) {
			return;
		}

		$startTime = DataManager::getDebugTime();

		//Check if each result is visible
		foreach ($this->results['tables'] as $tableName => &$tableResults) {
			foreach ($tableResults as $i => &$result) {

				$visible = $result->isVisible();
				if ($visible !== true) {
					if ($removeHiddenResults) {
						unset($tableResults[$i]);
					} else {
						$tableResults[$i]->hiddenReason = $visible;
						$tableResults[$i]->hidden = true;
					}
				}
			}
		}

		unset($result);
		unset($tableResults);

		if ($removeHiddenResults) {

			$this->results['total'] = 0;

			//Now remove tables that have no results left
			foreach ($this->results['tables'] as $tableName => $tableResults) {
				$c = count($tableResults);
				if ($c < 1) {
					unset($this->results['tables'][$tableName]);
				} else {
					$this->results['total'] += $c;
				}
			}

		} else {

			//Hidden results are included, but we want them to go to the bottom
			//Sort each table's results by 'hidden'
			foreach ($this->results['tables'] as $tableName => &$tableResults) {
				usort($tableResults, function ($a, $b) {
					return $a->hidden - $b->hidden;
				});
			}

		}

		$this->results['filterTime'] = DataManager::debugTimeTaken($startTime);
	}
}
