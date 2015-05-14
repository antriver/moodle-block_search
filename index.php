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
 * Search page (displays the search results)
 *
 * @package    block_search
 * @copyright  2015 Anthony Kuske <www.anthonykuske.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(dirname(dirname(__DIR__)) . '/config.php');

require_login();

require_capability('block/search:search', context_system::instance());

require_once(__DIR__ . '/classes/Block.php');
$searchblock = new block_search\Block();

$q = optional_param('q', '', PARAM_RAW);
$escapedq = htmlentities($q);
$courseid = optional_param('courseid', 0, PARAM_INT);
$searchincourse = optional_param('searchincourse', false, PARAM_BOOL);
$pagenum = optional_param('page', 0, PARAM_INT);
$showhiddenresults = optional_param('showhiddenresults', false, PARAM_BOOL);
$refreshcache = optional_param('refreshcache', false, PARAM_BOOL);

$perpage = (int)get_config('block_search', 'results_per_page');

if (!get_config('block_search', 'allow_no_access')) {
    $showhiddenresults = false;
}

if ($courseid) {
    $PAGE->set_context(context_course::instance($courseid));
} else {
    $PAGE->set_context(context_system::instance());
}

$PAGE->requires->jquery();
$PAGE->set_url('/blocks/search', array(
    'q' => $q,
    'courseid' => $courseid,
    'searchincourse' => $searchincourse,
    'page' => $pagenum,
    'showhiddenresults' => $showhiddenresults
));

$PAGE->requires->css('/blocks/search/assets/font-awesome-4.0.3/css/font-awesome.min.css');
$PAGE->requires->css('/blocks/search/assets/css/page.css?v=' . $searchblock->version());

/**
 * Page Title
 */
if (!empty($q)) {
    $PAGE->set_title(get_string('search_results_for', 'block_search', $escapedq));

    // Log the search (if logging is enabled).
    if (get_config('block_search', 'log_searches') == 1) {
        // FIXME: add_to_log() has been deprecated, please rewrite your code to the new events API
        add_to_log($courseid, 'block_search', 'search', '/blocks/search', $q, 0, $USER->id);
    }

} else {
    $PAGE->set_title(get_string('pagetitle', $searchblock->blockname));
}

$PAGE->set_heading(get_string('pagetitle', $searchblock->blockname));

echo $OUTPUT->header();
echo html_writer::start_tag('div', array('id' => $searchblock->blockname));

echo $searchblock->display->show_search_box($q, $courseid, $searchincourse, $showhiddenresults);

if (!empty($q)) {

    $removehiddenresults = empty($showhiddenresults) ? true : false;

    // Do the search.
    $results = $searchblock->search($q, ($searchincourse ? $courseid : false), $removehiddenresults, $refreshcache);

    if (!empty($results['error'])) {

        echo $OUTPUT->error_text($results['error']);

    } else if ($results['total'] < 1) {

        // There were no results.
        $icon = html_writer::tag('i', '', array('class' => 'fa fa-info-cirlce'));
        echo html_writer::tag(
            'div',
            $icon . ' ' . get_string('no_results', 'block_search'),
            array('class' => 'noResults')
        );

        if ($courseid) {
            // If searching in a course and there are no results,
            // suggest trying a full site search.

            $fullsearchurl = clone $PAGE->url;
            $fullsearchurl->remove_params(array('searchincourse'));

            $icon = html_writer::tag('i', '', array('class' => 'fa fa-hand-o-right'));
            $a = html_writer::tag(
                'a',
                $icon . ' ' . get_string('try_full_search', 'block_search'),
                array(
                    'href' => $fullsearchurl
                )
            );
            echo html_writer::tag(
                'div',
                $a,
                array('class' => 'noResults')
            );
        }

        /**
         * Advanced Search Options
         */
        echo $searchblock->display->show_advanced_options();

    } else {

        $icon = html_writer::tag('i', '', array('class' => 'fa fa-list-ul'));

        $offset = $pagenum * $perpage;
        echo '<p id="showing">';
        echo get_string(
            'showing',
            'block_search',
            array(
                'start' => number_format($offset + 1),
                'end' => number_format(min(($offset + $perpage), $results['total'])),
                'total' => number_format($results['total'])
            )
        );
        echo '</p>';

        echo html_writer::tag('h2', $icon . ' ' . get_string('search_results', 'block_search'));

        /**
         * Results menu (on the left)
         */
        echo html_writer::start_tag('div', array('class' => 'col left'));
            echo $searchblock->display->show_results_nav($results, $pagenum);

            // This is here so the leftcol still has content (and doesn't collapse)
            // when the resultsNav becomes position:fixed when scrolling.
            echo '&nbsp;';

        echo html_writer::end_tag('div');

        /**
         * Show results
         */
        echo html_writer::start_tag('div', array('id' => 'results', 'class' => 'col right'));

            /**
             * Results pagination
             */
            $pagingbar = new paging_bar($results['total'], $pagenum, $perpage, $PAGE->url);

            echo '<div class="text-center">';
            echo $OUTPUT->render($pagingbar);
            echo '</div>';

            echo $searchblock->display->show_results($results['results'], $pagenum);

            echo '<div class="text-center">';
            echo $OUTPUT->render($pagingbar);
            echo '</div>';

            /**
             * Advanced Search Options
             */
            echo $searchblock->display->show_advanced_options();

        echo html_writer::end_tag('div');
    }

    echo html_writer::tag('div', '', array('class' => 'clear'));

} else {

    /**
     * Advanced Search Options
     */
    echo $searchblock->display->show_advanced_options();

}


/**
 * Search Info
 */
if (!empty($results)) {
    echo '<div class="searchInfo">';

    if (!empty($results['userCached'])) {
        echo '<br/>';
        echo get_string('user_cached_results_generated', 'block_search', date('Y-m-d H:i:s', $results['generated']));

    } else {

        if (isset($results['searchTime'])) {
            echo get_string('search_took', 'block_search', $results['searchTime']);
        }

        if (!empty($results['cached'])) {
            echo '<br/>';
            echo get_string('cached_results_generated', 'block_search', date('Y-m-d H:i:s', $results['generated']));
        }

        if (isset($results['filterTime'])) {
            echo '<br/>';
            echo get_string('filtering_took', 'block_search', $results['filterTime']);
        }

    }

    if (!empty($searchblock->display->displayTime)) {
        echo '<br/>';
        echo get_string('displaying_took', 'block_search', $searchblock->display->displayTime);
    }

    echo '<br/>';
    echo 'Memory used: <strong>' . number_format(memory_get_peak_usage() / 1024 / 1024) . 'MB</strong>';

    echo '</div>';
}

echo html_writer::end_tag('div');

/**
 * Javascript
 */
echo '<script src="' . $searchblock->get_full_url() . 'assets/js/jquery.scrollTo.min.js?v=' . $searchblock->version() . '"></script>';
echo '<script src="' . $searchblock->get_full_url() . 'assets/js/jquery.localScroll.min.js?v=' . $searchblock->version() . '"></script>';
echo '<script src="' . $searchblock->get_full_url() . 'assets/js/block_search.js?v=' . $searchblock->version() . '"></script>';

echo $OUTPUT->footer();
