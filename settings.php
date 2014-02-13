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

require_once $CFG->dirroot . '/blocks/search/MoodleSearch/DataManager.php';

//Tables
$possibleTables = \MoodleSearch\DataManager::getTablesPossibleToSearch();
$settings->add(
	new admin_setting_configmulticheckbox(
		'block_search/search_tables',
		get_string('settings_search_tables_name', 'block_search'),
		get_string('settings_search_tables_desc', 'block_search') .
		' <a href="#" onclick="Y.all(\'#admin-search_tables input[type=checkbox]\').set(\'checked\', true); return false;">' . get_string('selectall', 'block_search') . '</a>',
		'',
		$possibleTables
	)
);

//Cache
$settings->add(
	new admin_setting_configtext(
		'block_search/cache_results',
		get_string('settings_cache_results_name', 'block_search'),
		get_string('settings_cache_results_desc', 'block_search'),
		86400,
		PARAM_INT
	)
);

//Logging
$settings->add(
	new admin_setting_configcheckbox(
		'block_search/log_searches',
		get_string('settings_log_searches_name', 'block_search'),
		get_string('settings_log_searches_desc', 'block_search'),
		1
	)
);

//Options
$settings->add(
	new admin_setting_configcheckbox(
		'block_search/allow_no_access',
		get_string('settings_allow_no_access_name', 'block_search'),
		get_string('settings_allow_no_access_desc', 'block_search'),
		1
	)
);
