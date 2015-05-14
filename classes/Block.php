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
 * Main class for search block
 *
 * @package    block_search
 * @copyright  2015 Anthony Kuske <www.anthonykuske.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_search;

use cache;
use moodle_url;
use block_search\Models\Search;

class Block
{
    public $display;
    public $blockname = 'block_search';
    private $path = '/blocks/search/';

    public function __construct() {

        // Classes are now autoloaded

        $this->display = new DisplayManager($this);
    }

    public function get_full_url() {

        return new moodle_url($this->path);
    }

    public function search($q, $courseid = 0, $removehiddenresults = false) {

        if (strlen($q) < 2) {
            return array(
                'error' => get_string('error_query_too_short', 'block_search', 2)
            );
        }

        raise_memory_limit(MEMORY_UNLIMITED);

        // Check if user cached results exist. for this user.
        $usercachevalidfor = (int)get_config('block_search', 'cache_results_per_user');
        $useusercache = $usercachevalidfor > 0;

        if (is_siteadmin()) {
            $useusercache = false;
        }

        if ($useusercache) {

            $cachekey = md5(json_encode(array($q, $courseid, $removehiddenresults)));

            $usercache = cache::make('block_search', 'user_searches');
            if ($results = $usercache->get($cachekey)) {

                if ($results['filtered'] > (time() - (int)$usercachevalidfor)) {
                    $results['userCached'] = true;
                    return $results;
                }
            }

        }

        $search = new Search($q, $courseid);
        $search->filter_results($removehiddenresults);
        $results = $search->get_results();

        if ($useusercache) {
            $usercache->set($cachekey, $results);
        }

        return $results;
    }

    /**
     * Returns the version number of the plugin, from the version.php file
     *
     * As far as I can see there's no variable or constant that contains this already
     * so it includes the version.php file to read the version number from it.
     */
    public function version() {

        if (isset($this->version)) {
            return $this->version;
        }
        $plugin = new \stdClass;
        include(dirname(__DIR__) . '/version.php');
        $this->version = $plugin->version;
        return $this->version;
    }
}
