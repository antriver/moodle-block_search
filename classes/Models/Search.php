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
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Object to represent an entire search action.
 * Creating a new search runs the search. Use get_results to get the results.
 * Will return an array of block_search\Models\Result objects.
 *
 * @package    block_search
 * @copyright  2015 Anthony Kuske <www.anthonykuske.com>
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
    private $q = false; // The search query.
    private $results = null; // Search results.
    private $courseid = false; // CourseID to search in.
    private $userid = false; // UserID that performed the search.
    private $tables = false; // Tabels to search in.
    private $refreshcache = false;
    private $textsubstitutions = null;

    public function __construct($q, $courseid = false, $userid = false, $refreshcache = false) {

        $this->q = $q;
        $this->courseid = $courseid;
        $this->refreshcache = $refreshcache;
        $this->results = $this->run_search();
    }

    public function get_results() {

        if ($this->results === null) {
            throw new Exception('Trying to get results before the search has been run.');
        }
        return $this->results;
    }

    /**
     * Builds an associative array of which fields in which tables to search in
     * @return array
     */
    private function get_fields_to_search() {

        global $DB;

        // Load the config setting for tables to search.
        // (A comma seperated list of table names without the prefix)
        $tablestosearch = get_config('block_search', 'search_tables');
        $tablestosearch = explode(',', $tablestosearch);

        // This array will hold the tables => array('fields') we'll actually search.
        $fieldstosearch = array();

        $dbman = $DB->get_manager();

        // Go through each table the admin wants to be searched and set which fields from that table to search.
        // (Makes sure the tables and fields exist)
        foreach ($tablestosearch as $tablename) {

            switch ($tablename) {

                case 'course':
                    // Don't show other courses if we're searching within a certain in a course.
                    if ($this->courseid) {
                        continue;
                    }
                    $fieldstosearch['course'] = array('fullname', 'shortname');
                    break;

                case 'course_categories':
                    // Don't show other courses if we're searching in a course.
                    if ($this->courseid) {
                        continue;
                    }
                    $fieldstosearch['course_categories'] = array('name', 'description');
                    break;

                default:
                    // Create an xmldb object from the name of this table.
                    $table = new xmldb_table($tablename);

                    // Skip this module if it has no table.
                    // (Only checks if a table with the same name as the module exists)
                    if (!$dbman->table_exists($table)) {
                        continue;
                    }

                    // We want to check if these fields exist in the table.
                    $modulefields = array('name', 'intro');

                    if ($tablename == 'page') {
                        $modulefields[] = 'content';
                    }

                    // Check if each of these fields (columns) exists in the table.
                    foreach ($modulefields as $fieldmame) {

                        // Create an xmldb object for this field's name.
                        $field = new xmldb_field($fieldmame);

                        // If this field exists in the table, we're going to search in it.
                        if ($dbman->field_exists($table, $field)) {
                            $fieldstosearch[$tablename][] = $fieldmame;
                        }

                    }
                    break;
            }
        }

        // Search in folder files?
        if (get_config('block_search', 'search_files_in_folders')) {
            $fieldstosearch['folder_files'] = array();
        }

        // Sort by by table name
        ksort($fieldstosearch);

        return $fieldstosearch;
    }

    /**
     * Search for rows which match the search query
     * @return array An associative array of the tables that were searched
     */
    private function run_search() {

        if (empty($this->q)) {
            throw new Exception('No query was given.');
        }

        $starttime = DataManager::get_debug_time();

        // Check if cached shared results exist.
        $cachefor = get_config('block_search', 'cache_results');
        $cache = $cachefor > 0 ? true : false;

        if ($cache) {

            $hash = md5('search' . strtolower($this->q) . 'courseid' . $this->courseid);

            if (!$this->refreshcache) {
                // Check if cached results exists.
                $results = DataManager::get_cache()->get($hash);

                if (is_array($results)) {

                    // If the cached results are newer than than the cache_results setting we'll use them.
                    if ($results['generated'] > (time() - (int)$cachefor)) {
                        $results['searchTime'] = DataManager::get_debug_time_taken($starttime);
                        $results['cached'] = true;
                        return $results;
                    }
                }
            }
        }

        // Set the tables to search in.
        $this->tables = $this->get_fields_to_search();
        if (empty($this->tables)) {
            throw new Exception('Trying to search, but no tables have been specified to search in.');
        }

        // The results array to be returned.
        $results = array(
            'tables' => array(), // Number of results from each table, and the index that they start and end.
            'results' => array(), // Array of results.
            'generated' => time(), // Time the search was made.
            'searchTime' => 0, // How long the search took.
            'cached' => false, // Are the results cached?
            'total' => 0, // Total number of results.
            'filtered' => false, // Have the results been personalised for a user yet?
        );

        // Search each table we're supposed to search in.
        foreach ($this->tables as $tablename => $fields) {

            if ($tablename == 'folder_files') {
                $rows = $this->search_folder_files();
            } else {
                $rows = $this->search_table($tablename, $fields);
            }

            if (!empty($rows)) {
                // Add the rows to the results ($results['results']) is a reference)
                $this->convert_rows_to_result_objects_and_add_to_array($tablename, $rows, $results['results']);
            }
        }

        $this->add_table_info_to_results($results);

        // Save in the cache.
        if ($cache) {
            DataManager::get_cache()->set($hash, $results);
        }

        $results['searchTime'] = DataManager::get_debug_time_taken($starttime);

        return $results;
    }

    private function search_table($tablename, $fields) {

        global $DB;

        $where = '';

        // Array of query values.
        $queryparameters = array();

        // Build the SQL query.
        foreach ($fields as $fieldmame) {
            $where .= $this->build_word_query($fieldmame, $this->q, $queryparameters) . ' OR ';
        }
        $where = rtrim($where, 'OR ');

        if ($this->courseid) {
            $where = "({$where})";
            $where .= ' AND course = ?';
            $queryparameters[] = $this->courseid;
        }

        // Full query.
        $sql = 'SELECT * FROM {' . $tablename . '} WHERE ' . $where;

        // Run the query and return the matched rows.
        return $DB->get_records_sql($sql, $queryparameters);
    }

    /**
     * Create the appropriate Result object, given a row from a table
     */
    private function convert_rows_to_result_objects_and_add_to_array($tablename, $rows, &$results) {

        switch ($tablename) {
            case 'course':
                $classname = 'CourseResult';
                break;

            case 'course_categories':
                $classname = 'CategoryResult';
                break;

            case 'folder_files':
                $classname = 'FileInFolderResult';
                break;

            default:
                $classname = 'ModuleResult';
                break;
        }
        $classname = '\block_search\Models\\' . $classname;

        foreach ($rows as $row) {
            $results[] = new $classname($tablename, $row);
        }
    }


    /**
     * Find files in folder modules
     * This is a bit more complicated than a simple search, hence the separate method
     * @return [type] [description]
     */
    private function search_folder_files() {

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

        $queryparameters = array();
        $sql .= $this->build_word_query('files.filename', $this->q, $queryparameters);

        $sql .= "
        )";

        if ($this->courseid) {
            $sql .= ' AND course_modules.course = ?';
            $queryparameters[] = $this->courseid;
        }

        return $DB->get_records_sql($sql, $queryparameters);
    }

    /**
     * Splits the query string into words and phrases as appropriate and returns
     * a portion of to match the given column name against.
     *
     * e.g. 'Two Words'
     * returns
     * ( columnName LIKE %two%' AND columnName LIKE %words% )
     */
    private function build_word_query($columnname, $searchterms, &$queryparameters = array()) {

        $searchterms = strtolower($searchterms);

        $columnname = "LOWER({$columnname})";

        // Replace character for wildcards.
        $searchterms = str_replace('*', '%', $searchterms);

        // "Words in quotes" to search exact phrases.
        $queryexact = '';
        if (preg_match_all('/"[\w|\s|\']+"/i', $searchterms, $matches)) {
            foreach ($matches[0] as $match) {
                $queryexact .= "{$columnname} LIKE ? AND ";

                // Remove the match from the search terms because we're done with it.
                $searchterms = str_replace($match, '', $searchterms);

                // Remove quotes from the match.
                $match = trim($match, '"');
                $queryparameters[] = '%' . $match . '%';
            }
        }
        // -Word to exclude words.
        $queryexclude = '';
        if (preg_match_all('/\-\w+/i', $searchterms, $matches)) {
            foreach ($matches[0] as $match) {

                $queryexclude .= "{$columnname} NOT LIKE ? AND ";

                // Remove the match from the search terms because we're done with it.
                $searchterms = str_replace($match, '', $searchterms);

                // Remove - from the match.
                $match = ltrim($match, '-');
                $queryparameters[] = '%' . $match . '%';
            }
        }

        // Now the advanced parameters have been dealt with and removed from $searchterms
        // we're just left with words we want to look for.
        $querywords = '';
        $searchterms = trim($searchterms);
        $searchwords = explode(' ', trim($searchterms));

        $searchwords = array_unique($searchwords);

        foreach ($searchwords as $word) {
            if (empty($word)) {
                continue;
            }

            $querywords .= "({$columnname} LIKE ?";
            $queryparameters[] = '%' . $word . '%';

            foreach ($this->get_text_substitutions($word) as $sub) {
                $querywords .= " OR {$columnname} LIKE ?";
                $queryparameters[] = '%' . $sub . '%';
            }

            $querywords .= ') AND ';
        }

        // Now stick it together.
        $where = '(' . $queryexact . $queryexclude . $querywords;
        $where = rtrim($where, 'AND ') . ')';

        return $where;
    }

    private function add_table_info_to_results(&$results) {

        // Total number of results.
        $results['total'] = count($results['results']);
        $results['tables'] = array();
        $currenttable = false;

        $perpage = (int)get_config('block_search', 'results_per_page');
        $i = 0;
        foreach ($results['results'] as $result) {
            if ($currenttable === false || $result->tablename != $currenttable) {
                $results['tables'][$result->tablename] = array(
                    'count' => 0,
                    'visibleCount' => 0,
                    'hiddenCount' => 0,
                    'startIndex' => $i,
                    'startPage' => floor($i / $perpage),
                );
                $currenttable = $result->tablename;
            }

            ++$results['tables'][$result->tablename]['count'];
            if ($result->hidden) {
                ++$results['tables'][$result->tablename]['hiddenCount'];
            } else {
                ++$results['tables'][$result->tablename]['visibleCount'];
            }
            $results['tables'][$result->tablename]['endIndex'] = $i;
            ++$i;
        }
    }

    /**
     * Go through the array of results and remove those the user doesn't have permission to see
     */
    public function filter_results($removehiddenresults = true) {

        // Site admin can see everything so don't bother filtering.
        if (is_siteadmin()) {
            return;
        }

        $this->results['filtered'] = time();

        $starttime = DataManager::get_debug_time();

        // Check if each result is visible.
        foreach ($this->results['results'] as $i => &$result) {
            $visible = $result->is_visible();
            if ($visible === null) {

                // Null means it should never be displayed.
                unset($this->results['results'][$i]);

            } else if ($visible !== true) {
                if ($removehiddenresults) {
                    unset($this->results['results'][$i]);
                } else {
                    $result->hiddenreason = $visible;
                    $result->hidden = true;
                }
            }
        }

        // Unset hanging references.
        unset($result);

        $this->add_table_info_to_results($this->results);

        if (!$removehiddenresults) {
            // Hidden results are included, but we want them to go to the bottom
            // Sort the results by 'tableName' then by 'hidden'
            $this->results['results'] = Utils::sort_multidimensional_array($this->results['results'], "tableName ASC, hidden ASC");
        }

        $this->results['filterTime'] = DataManager::get_debug_time_taken($starttime);
    }

    private function get_text_substitutions($word) {

        // Load the substitutions if not already loaded.
        if (is_null($this->textsubstitutions)) {
            $this->textsubstitutions = array();

            $config = trim(get_config('block_search', 'text_substitutions'));
            if (strlen($config) > 0) {

                // Split into lines.
                $config = explode("\n", $config);

                foreach ($config as $line) {
                    $line = strtolower($line);
                    $line = trim($line, " \r\n");
                    list($find, $replace) = explode(' => ', $line);
                    $this->textsubstitutions[$find][] = $replace;
                }

            }
        }

        if (isset($this->textsubstitutions[$word])) {
            return $this->textsubstitutions[$word];
        } else {
            return array();
        }
    }
}
