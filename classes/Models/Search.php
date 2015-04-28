<?php

/**
 * Object to represent an entire search action.
 * Creating a new search runs the search. Use getResults to get the results.
 * Will return an array of block_search\Models\Result objects.
 *
 * @package    block_search
 * @copyright  Anthony Kuske <www.anthonykuske.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_search\Models;

use Exception;
use xmldb_field;
use xmldb_table;
use block_search\DataManager;
use block_search\Utils;

class Search
{
	private $q = false; //The search query
	private $results = null; //Search results
	private $courseID = false; //CourseID to search in
	private $userID = false; //UserID that performed the search
	private $tables = false; //Tabls to search in
	private $refreshCache = false;
	private $textSubstitutions = null;

	public function __construct($q, $courseID = false, $userID = false)
	{
		$this->q = $q;
		$this->courseID = $courseID;

		if (isset($_GET['refresh'])) {
			$this->refreshCache = true;
		}

		$this->results = $this->runSearch();
	}

	public function getResults()
	{
		if ($this->results ===  null) {
			throw new Exception('Trying to get results before the search has been run.');
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
					//Don't show other courses if we're searching within a certain in a course
					if ($this->courseID) {
						continue;
					}
					$fieldsToSearch['course'] = array('fullname', 'shortname');
					break;

				case 'course_categories':
					//Don't show other courses if we're searching in a course
					if ($this->courseID) {
						continue;
					}
					$fieldsToSearch['course_categories'] = array('name', 'description');
					break;

				default:
					//Create an xmldb object from the name of this table
					$table = new xmldb_table($tableName);

					//Skip this module if it has no table
					//(Only checks if a table with the same name as the module exists)
					if (!$dbman->table_exists($table)) {
						continue;
					}

					//We want to check if these fields exist in the table
					$moduleFields = array('name', 'intro');

					if ($tableName == 'page') {
						$moduleFields[] = 'content';
					}

					//Check if each of these fields (columns) exists in the table
					foreach ($moduleFields as $fieldName) {

						//Create an xmldb object for this field's name
						$field = new xmldb_field($fieldName);

						//If this field exists in the table, we're going to search in it
						if ($dbman->field_exists($table, $field)) {
							$fieldsToSearch[$tableName][] = $fieldName;
						}

					}
					break;
			}
		}

		//Search in folder files?
		if (get_config('block_search', 'search_files_in_folders')) {
			$fieldsToSearch['folder_files'] = array();
		}

		//Sort by by table name
		ksort($fieldsToSearch);

		return $fieldsToSearch;
	}

	//Search for rows which match the search query
	//Returns an associative array of the tables that were searched
	private function runSearch()
	{
		if (empty($this->q)) {
			throw new Exception('No query was given.');
		}

		$startTime = DataManager::getDebugTime();

		//Check if cached shared results exist
		$cache_for = get_config('block_search', 'cache_results');
		$cache = $cache_for > 0 ? true : false;

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

		//Set the tables to search in
		$this->tables = $this->getFieldsToSearch();
		if (empty($this->tables)) {
			throw new Exception('Trying to search, but no tables have been specified to search in.');
		}

		//The results array to be returned
		$results = array(
			'tables' => array(), //Number of results from each table, and the index that they start and end
			'results' => array(), //Array of results
			'generated' => time(), //Time the search was made
			'searchTime' => 0, //How long the search took
			'cached' => false, //Are the results cached?
			'total' => 0, //Total number of results
			'filtered' => false, //Have the results been personalised for a user yet?
		);

		//Search each table we're supposed to search in
		foreach ($this->tables as $tableName => $fields) {

			if ($tableName == 'folder_files') {
				$rows = $this->searchFolderFiles();
			} else {
				$rows = $this->searchTable($tableName, $fields);
			}

			if (!empty($rows)) {
				//Add the rows to the results ($results['results']) is a reference)
				$this->convertRowsToResultObjectsAndAddToArray($tableName, $rows, $results['results']);
			}
		}

		$this->addTableInfoToResults($results);

		//Save in the cache
		if ($cache) {
			DataManager::getCache()->set($hash, $results);
		}

		$results['searchTime'] = DataManager::debugTimeTaken($startTime);

		return $results;
	}

	private function searchTable($tableName, $fields)
	{
		global $DB;

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
		$sql = 'SELECT * FROM {' . $tableName . '} WHERE ' . $where;

		//Run the query and return the matched rows
		return $DB->get_records_sql($sql, $queryParameters);
	}

	/**
	 * Create the appropriate Result object, given a row from a table
	 */
	private function convertRowsToResultObjectsAndAddToArray($tableName, $rows, &$results)
	{
		switch ($tableName) {
			case 'course':
				$className = 'CourseResult';
				break;

			case 'course_categories':
				$className = 'CategoryResult';
				break;

			case 'folder_files':
				$className = 'FileInFolderResult';
				break;

			default:
				$className = 'ModuleResult';
				break;
		}
		$className = '\block_search\Models\\' . $className;

		foreach ($rows as $row) {
			$results[] = new $className($tableName, $row);
		}
	}


	/**
	 * Find files in folder modules
	 * This is a bit more complicated than a simple search, hence the separate method
	 * @return [type] [description]
	 */
	private function searchFolderFiles()
	{
		global $DB;

		if (empty($this->q)) {
			throw new Exception('No query was given.');
		}

		$sql = "
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
		WHERE
			files.component = 'mod_folder'
			AND
			files.filearea = 'content'
			AND
			files.filename != '.'
			AND
			(
		";

		$queryParameters = array();
		$sql .= $this->buildWordQuery('files.filename', $this->q, $queryParameters);

		$sql .= "
		)";

		if ($this->courseID) {
			$sql .= ' AND course_modules.course = ?';
			$queryParameters[] = $this->courseID;
		}

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

		$searchWords = array_unique($searchWords);

		foreach ($searchWords as $word) {
			if (empty($word)) {
				continue;
			}

			$queryWords .= "({$columnName} LIKE ?";
			$queryParameters[] = '%' . $word . '%';

			foreach ($this->getTextSubstitutions($word) as $sub) {
				$queryWords .= " OR {$columnName} LIKE ?";
				$queryParameters[] = '%' . $sub . '%';
			}

			$queryWords .= ') AND ';
		}

		//Now stick it together
		$where = '(' . $queryExact . $queryExclude . $queryWords;
		$where = rtrim($where, 'AND ') . ')';

		return $where;
	}

	private function addTableInfoToResults(&$results)
	{
		//Total number of results
		$results['total'] = count($results['results']);
		$results['tables'] = array();
		$currentTable = false;

		$perPage = (int)get_config('block_search', 'results_per_page');
		$i = 0;
		foreach ($results['results'] as $result) {
			if ($currentTable === false || $result->tableName != $currentTable) {
				$results['tables'][$result->tableName] = array(
					'count' => 0,
					'visibleCount' => 0,
					'hiddenCount' => 0,
					'startIndex' => $i,
					'startPage' => floor($i / $perPage),
				);
				$currentTable = $result->tableName;
			}

			++$results['tables'][$result->tableName]['count'];
			if ($result->hidden) {
				++$results['tables'][$result->tableName]['hiddenCount'];
			} else {
				++$results['tables'][$result->tableName]['visibleCount'];
			}
			$results['tables'][$result->tableName]['endIndex'] = $i;
			++$i;
		}
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

		$this->results['filtered'] = time();

		$startTime = DataManager::getDebugTime();

		//Check if each result is visible
		foreach ($this->results['results'] as $i => &$result) {
			$visible = $result->isVisible();
			if ($visible === null) {

				//null means it should never be displayed
				unset($this->results['results'][$i]);

			} elseif ($visible !== true) {
				if ($removeHiddenResults) {
					unset($this->results['results'][$i]);
				} else {
					$result->hiddenReason = $visible;
					$result->hidden = true;
				}
			}
		}

		//Unset hanging references
		unset($result);

		$this->addTableInfoToResults($this->results);

		if (!$removeHiddenResults) {
			// Hidden results are included, but we want them to go to the bottom
			// Sort the results by 'tableName' then by 'hidden'
			$this->results['results'] = Utils::sortMultidimensionalArray($this->results['results'], "tableName ASC, hidden ASC");
		}

		$this->results['filterTime'] = DataManager::debugTimeTaken($startTime);
	}

	private function getTextSubstitutions($word)
	{
		//Load the substitutions if not already loaded
		if (is_null($this->textSubstitutions)) {
			$this->textSubstitutions = array();

			$config = trim(get_config('block_search', 'text_substitutions'));
			if (strlen($config) > 0) {

				//Split into lines
				$config = explode("\n", $config);

				foreach ($config as $line) {
					$line = strtolower($line);
					$line = trim($line, " \r\n");
					list($find, $replace) = explode(' => ', $line);
					$this->textSubstitutions[$find][] = $replace;
				}

			}
		}

		if (isset($this->textSubstitutions[$word])) {
			return $this->textSubstitutions[$word];
		} else {
			return array();
		}
	}
}
