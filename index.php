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
 * Search page (displays the search results)
 * @package	   block_search
 * @copyright	 Anthony Kuske <www.anthonykuske.com>
 * @license	   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once '../../config.php';

require_login();

require_capability('block/search:search', context_system::instance());

require_once __DIR__ . '/MoodleSearch/Block.php';
$searchBlock = new MoodleSearch\Block();

$q = optional_param('q', '', PARAM_RAW);
$escapedq = htmlentities($q);
$courseID = optional_param('courseID', 0, PARAM_INT);
$showHiddenResults = optional_param('showHiddenResults', false, PARAM_BOOL);

if (!get_config('block_search', 'allow_no_access')) {
	$showHiddenResults = false;
}

if ($courseID) {
	$PAGE->set_context(context_course::instance($courseID));
} else {
	$PAGE->set_context(context_system::instance());
}

$PAGE->requires->jquery();
$PAGE->set_url('/blocks/search');
$PAGE->requires->css('/blocks/search/assets/css/page.css');

/**
 * Page Title
 */
if (!empty($q)) {
	$PAGE->set_title(get_string('search_results_for', 'block_search', $escapedq));

	//Log the search (if logging is enabled)
	if (get_config('block_search', 'log_searches') == 1) {
		add_to_log($courseID, 'block_search', 'search', '/blocks/search', $q, 0, $USER->id);
	}

} else {
	$PAGE->set_title(get_string('pagetitle', $searchBlock->blockName));
}

$PAGE->set_heading(get_string('pagetitle', $searchBlock->blockName));

echo $OUTPUT->header();
echo html_writer::start_tag('div', array('id' => $searchBlock->blockName));


echo $searchBlock->display->showSearchBox($q, $courseID, $showHiddenResults);

if (!empty($q)) {

	$removeHiddenResults = empty($showHiddenResults) ? true : false;

	//Do the search
	$search = new MoodleSearch\Search($q, $courseID);
	$search->filterResults($removeHiddenResults);
	$results = $search->getResults();

	if (count($results['tables']) < 1) {

		//There were no results
		$icon = html_writer::tag('i', '', array('class' => 'icon-info-sign'));
		echo html_writer::tag(
			'div',
			$icon . ' ' . get_string('no_results', 'block_search'),
			array('class' => 'noResults')
		);

		/*
		 * Advanced Search Options
		 */
		echo $searchBlock->display->showAdvancedOptions();

	} else {

		$icon = html_writer::tag('i', '', array('class' => 'icon-list-ul'));
		echo html_writer::tag('h2', $icon . ' ' . get_string('search_results', 'block_search'));

		/*
		 * Results menu (on the left)
		 */
		echo html_writer::start_tag('div', array('class' => 'col left'));
			echo $searchBlock->display->showResultsNav($results);

			// This is here so the leftcol still has content (and doesn't collapse)
			// when the resultsNav becomes position:fixed when scrolling
			echo '&nbsp;';

		echo html_writer::end_tag('div');

		/*
		 * Show results
		 */
		echo html_writer::start_tag('div', array('id' => 'results', 'class' => 'col right'));
			echo $searchBlock->display->showResults($results['tables']);

			/*
			 * Advanced Search Options
			 */
			echo $searchBlock->display->showAdvancedOptions();

		echo html_writer::end_tag('div');
	}

}
echo html_writer::tag('div', '', array('class' => 'clear'));


/*
 * Search Info
 */
if (!empty($results)) {
	echo '<div class="searchInfo">';
	echo get_string('search_took', 'block_search', $results['searchTime']);
	if (!empty($results['cached'])) {
		echo '<br/>';
		echo get_string('cached_results_generated', 'block_search', date('Y-m-d H:i:s', $results['generated']));
	}
	if (!empty($results['filterTime'])) {
		echo '<br/>';
		echo get_string('filtering_took', 'block_search', $results['filterTime']);
	}
	if (!empty($searchBlock->display->displayTime)) {
		echo '<br/>';
		echo get_string('displaying_took', 'block_search', $searchBlock->display->displayTime);
	}
	echo '</div>';
}

echo html_writer::end_tag('div');

/*
 * Javascript
 */
echo '<script src="' . $searchBlock->getFullURL() . 'assets/js/jquery.scrollTo.min.js"></script>';
echo '<script src="' . $searchBlock->getFullURL() . 'assets/js/jquery.localScroll.min.js"></script>';
echo '<script src="' . $searchBlock->getFullURL() . 'assets/js/block_search.js"></script>';

echo $OUTPUT->footer();
