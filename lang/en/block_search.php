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
 * English language strings for search block
 * @package	   block_search
 * @copyright	 Anthony Kuske <www.anthonykuske.com>
 * @license	   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

//General blockyness
$string['pluginname'] = 'Search';
$string['pagetitle'] = 'Search';
$string['search'] = 'Search';

//Placeholder text for the search box when shown in a block on a page
$string['search_input_text_block'] = 'Search this course';

//Placeholder text for the search box when shown on the full search page
$string['search_input_text_page'] = 'Find courses, activities, or documents';

//Search form
$string['search_options'] = 'Search Options:';
$string['search_all_of_site'] = 'Search all of {$a}';
$string['search_in_course'] = 'Search in {$a}';
$string['include_hidden_results'] = 'Include results I don\'t have access to';

//Search results
$string['search_results_for'] = 'Search Results for \'{$a}\'';
$string['search_results'] = 'Search Results';
$string['items_found'] = '{$a} Items Found';
$string['no_results'] = 'Sorry, there were no results for your search.';
$string['hidden_not_enrolled'] = 'You are not enrolled in this course.';
$string['hidden_not_available'] = 'This resource has not been made available to you.';
$string['folder_contents'] = 'Files Inside Folders';

//Search stats
$string['search_took'] = 'Search took <strong>{$a}</strong> seconds.';
$string['cached_results_generated'] = 'Cached results from <strong>{$a}</strong>.';
$string['filtering_took'] = 'Filtering results took <strong>{$a}</strong> seconds.';
$string['displaying_took'] = 'Displaying results took <strong>{$a}</strong> seconds.';

//Admin settings
$string['settings_search_tables_name'] = 'Search Tables';
$string['settings_search_tables_desc'] = 'Which tables in the database will be searched.';
$string['selectall'] = 'Select All';
$string['settings_cache_results_name'] = 'Cache Results For';
$string['settings_cache_results_desc'] = 'How long (in seconds) to cache search results for. 0 mean no caching. Default is 1 day.';
$string['settings_log_searches_name'] = 'Log Searches';
$string['settings_log_searches_desc'] = 'Should searches made be logged in the Moodle logs?';
$string['settings_allow_no_access_name'] = 'Show Hidden Results';
$string['settings_allow_no_access_desc'] = 'Allow users to tick "'. $string['include_hidden_results'] .'" to see results that aren\'t available to them. (This does not allow them to access the actual content that is found. But the user can see that it exists.)';


//Capabilities
$string['search:search'] = 'Perform a search';
