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
$string['search_in_course'] = 'Search in {$a} only';
$string['include_hidden_results'] = 'Include results I don\'t have access to';

//Search results
$string['error_query_too_short'] = 'Please enter a query at least {$a} characters long.';
$string['search_results_for'] = 'Search Results for \'{$a}\'';
$string['search_results'] = 'Search Results';
$string['items_found'] = 'Items Found';
$string['showing'] = 'Showing {$a->start} to {$a->end} of {$a->total} results';
$string['no_results'] = 'Sorry, there were no results for your search.';
$string['try_full_search'] = 'Did you mean to search all of the site instead of just this course?';
$string['hidden_not_enrolled'] = 'You are not enrolled in this course.';
$string['hidden_not_available'] = 'This resource has not been made available to you.';
$string['folder_contents'] = 'Files Inside Folders';

//Search stats
$string['search_took'] = 'Search took <strong>{$a}</strong> seconds.';
$string['cached_results_generated'] = 'Cached results from <strong>{$a}</strong>.';
$string['filtering_took'] = 'Filtering results took <strong>{$a}</strong> seconds.';
$string['user_cached_results_generated'] = 'Personalised cached results from <strong>{$a}</strong>.';
$string['displaying_took'] = 'Displaying results took <strong>{$a}</strong> seconds.';

//Admin settings
$string['settings_search_tables_name'] = 'Search Tables';
$string['settings_search_tables_desc'] = 'Which tables in the database will be searched.';
$string['selectall'] = 'Select All';
$string['settings_cache_results_name'] = 'Cache Results For';
$string['settings_cache_results_desc'] = 'How long (in seconds) to cache search results for. 0 mean no caching. Default is 1 day. This cache stores the results from the database, before they are personalised for a certain user (before results the user doesn\'t have access to are removed). Meaning this cache can be shared between different users and provides benefit when different users are searching for the same terms. If the content on your site doesn\'t change that often you can set this value higher.';

$string['settings_cache_results_per_user_name'] = 'Cache User-Specific Results For';
$string['settings_cache_results_per_user_desc'] = 'How long (in seconds) to cache filtered results for. 0 means no caching. Default is 15 minutes. This cache stores the results *after* results the user doesn\'t have access to have been removed. Each item in this cache is specific to a single user, so it only provides a benefit when the same person searches for the same thing again (or when they go to a different page in the results). It is reccomended to have this enabled for at least a few minutes, so users can view all the pages of results without the results having to be regenerated on each page. If it is disabled, the entire search must be run again when a user goes to another page of the results. If you think your users will search for the same thing often, consider increasing this value.';

$string['settings_log_searches_name'] = 'Log Searches';
$string['settings_log_searches_desc'] = 'Should searches made be logged in the Moodle logs?';
$string['settings_allow_no_access_name'] = 'Show Hidden Results';
$string['settings_allow_no_access_desc'] = 'Allow users to tick "'. $string['include_hidden_results'] .'" to see results that aren\'t available to them. (This does not allow them to access the actual content that is found. But the user can see that it exists.)';
$string['settings_search_files_in_folders_name'] = 'Search For Files Inside Folder Activities';
$string['settings_search_files_in_folders_desc'] = 'Should searches try to find files within "folder" activities/resources in courses?';
$string['settings_results_per_page_name'] = 'Results Per Page';
$string['settings_results_per_page_desc'] = 'How many search results to show per page';
$string['settings_text_substitutions_name'] = 'Text Substitutions';
$string['settings_text_substitutions_desc'] = 'Text substitutions allow users to search for shortened words/phrases but still get results that contain the full phrase. For example, a user can search for "Docs" and  get results which contain the word "Documents" and/or "Docs".
Specify each replacement on it\'s own line in this format:
<pre>Docs => Documents
App => Application
Some Phrase => Some Much Longer Phrase</pre>';


//Advanced Search Help
$string['advanced_search_title'] = 'Advanced Search Options';
$string['advanced_search_desc'] = 'Add these words to your search to refine the results.';

$string['advanced_search_exclude_example'] = 'word';
$string['advanced_search_exclude_desc'] = 'Find results that <strong>don\'t</strong> include that word.';

$string['advanced_search_exact_example'] = 'words in quotes';
$string['advanced_search_exact_desc'] = 'Find results that contain this <strong>exact phrase</strong>';

$string['advanced_search_wildcard_example'] = 'w*d';
$string['advanced_search_wildcard_desc'] = '* is a <strong>wildcard</strong>. This would match both "word" and "weird".';

//Capabilities
$string['search:search'] = 'Perform a search';
