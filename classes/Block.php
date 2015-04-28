<?php

/**
 * Main class for search block
 *
 * @package    block_search
 * @copyright  Anthony Kuske <www.anthonykuske.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_search;

use cache;
use moodle_url;
use block_search\Models\Search;

class Block
{
	public $display;
	public $blockName = 'block_search';
	private $path = '/blocks/search/';

	public function __construct()
	{
		// Classes are now autoloaded

		$this->display = new DisplayManager($this);
	}

	public function getFullPath()
	{
		global $CFG;
		return $CFG->dirroot . $this->path;
	}

	public function getFullURL()
	{
		return new moodle_url($this->path);
	}

	public function search($q, $courseID = 0, $removeHiddenResults = false)
	{
		if (strlen($q) < 2) {
			return array(
				'error' => get_string('error_query_too_short', 'block_search', 2)
			);
		}

		raise_memory_limit(MEMORY_UNLIMITED);

		//Check if user cached results exist
		$userCacheValidFor = (int)get_config('block_search', 'cache_results_per_user');
		$useUserCache = $userCacheValidFor > 0;

		if (is_siteadmin()) {
			$useUserCache = false;
		}

		if ($useUserCache) {

			$cacheKey = md5(json_encode(array($q, $courseID, $removeHiddenResults)));

			$userCache = cache::make('block_search', 'user_searches');
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

	/**
	 * Returns the version number of the plugin, from the version.php file
	 *
	 * As far as I can see there's no variable or constant that contains this already
	 * so it includes the version.php file to read the version number from it.
	 */
	public function version()
	{
		if (isset($this->version)) {
			return $this->version;
		}
		$plugin = new \stdClass;
		include dirname(__DIR__) . '/version.php';
		$this->version = $plugin->version;
		return $this->version;
	}
}
