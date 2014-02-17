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
 * Main class for search block
 * @package	   block_search
 * @copyright	 Anthony Kuske <www.anthonykuske.com>
 * @license	   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace MoodleSearch;

class Block
{
	public $display;
	public $blockName = 'block_search';
	private $path = '/blocks/search/';

	public function __construct()
	{
		error_reporting(E_ALL);
		ini_set('display_errors', 1);

		//TODO: Autoloader would be nice here
		require_once __DIR__ . '/Model/Result.php';
		require_once __DIR__ . '/Model/Results/CourseResult.php';
		require_once __DIR__ . '/Model/Results/CategoryResult.php';
		require_once __DIR__ . '/Model/Results/FileInFolderResult.php';
		require_once __DIR__ . '/Model/Results/ModuleResult.php';
		require_once __DIR__ . '/Model/Search.php';
		require_once __DIR__ . '/DataManager.php';
		require_once __DIR__ . '/DisplayManager.php';
		$this->display = new DisplayManager($this);
	}

	public function getFullPath()
	{
		global $CFG;
		return $CFG->dirroot . $this->path;
	}

	public function getFullURL()
	{
		global $CFG;
		return $CFG->wwwroot . $this->path;
	}

	public function search($q, $courseID = 0, $removeHiddenResults = false)
	{
		//Check if user cached results exist
		$userCacheValidFor = (int)get_config('block_search', 'cache_results_per_user');
		$useUserCache = $userCacheValidFor > 0;

		if (is_siteadmin()) {
			$useUserCache = false;
		}

		if ($useUserCache) {

			$cacheKey = md5(json_encode(array($q, $courseID, $removeHiddenResults)));

			$userCache = \cache::make('block_search', 'user_searches');
			if ($results = $userCache->get($cacheKey)) {

				if ($results['filtered'] > (time() - (int)$userCacheValidFor)) {
					$results['userCached'] = true;
					return $results;
				}
			}

		}

		$search = new Search($q, $courseID);
		$search->filterResults($removeHiddenResults);
		$results = $search->getResults();

		if ($useUserCache) {
			$userCache->set($cacheKey, $results);
		}

		return $results;
	}
}
