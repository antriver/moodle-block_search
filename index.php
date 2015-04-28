<?php

/**
 * Search page (displays the search results)
 *
 * @package    block_search
 * @copyright  Anthony Kuske <www.anthonykuske.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once '../../config.php';

require_login();

require_capability('block/search:search', context_system::instance());

require_once __DIR__ . '/classes/Block.php';
$searchBlock = new block_search\Block();

$q = optional_param('q', '', PARAM_RAW);
$escapedq = htmlentities($q);
$courseID = optional_param('courseID', 0, PARAM_INT);
$searchInCourse = optional_param('searchInCourse', false, PARAM_BOOL);
$pageNum = optional_param('page', 0, PARAM_INT);
$showHiddenResults = optional_param('showHiddenResults', false, PARAM_BOOL);

$perPage = (int)get_config('block_search', 'results_per_page');

if (!get_config('block_search', 'allow_no_access')) {
	$showHiddenResults = false;
}

if ($courseID) {
	$PAGE->set_context(context_course::instance($courseID));
} else {
	$PAGE->set_context(context_system::instance());
}

$PAGE->requires->jquery();
$PAGE->set_url('/blocks/search', array(
	'q' => $q,
	'courseID' => $courseID,
	'searchInCourse' => $searchInCourse,
	'page' => $pageNum,
	'showHiddenResults' => $showHiddenResults
));

$PAGE->requires->css('/blocks/search/assets/font-awesome-4.0.3/css/font-awesome.min.css');
$PAGE->requires->css('/blocks/search/assets/css/page.css?v=' . $searchBlock->version());

/**
 * Page Title
 */
if (!empty($q)) {
	$PAGE->set_title(get_string('search_results_for', 'block_search', $escapedq));

	//Log the search (if logging is enabled)
	if (get_config('block_search', 'log_searches') == 1) {
        // FIXME: add_to_log() has been deprecated, please rewrite your code to the new events API
		add_to_log($courseID, 'block_search', 'search', '/blocks/search', $q, 0, $USER->id);
	}

} else {
	$PAGE->set_title(get_string('pagetitle', $searchBlock->blockName));
}

$PAGE->set_heading(get_string('pagetitle', $searchBlock->blockName));

echo $OUTPUT->header();
echo html_writer::start_tag('div', array('id' => $searchBlock->blockName));

echo $searchBlock->display->showSearchBox($q, $courseID, $searchInCourse, $showHiddenResults);

if (!empty($q)) {

	$removeHiddenResults = empty($showHiddenResults) ? true : false;

	//Do the search
	$results = $searchBlock->search($q, ($searchInCourse ? $courseID : false), $removeHiddenResults);

	if (!empty($results['error'])) {

		//Show an error
		echo $OUTPUT->error_text($results['error']);

	} elseif ($results['total'] < 1) {

		//There were no results
		$icon = html_writer::tag('i', '', array('class' => 'fa fa-info-cirlce'));
		echo html_writer::tag(
			'div',
			$icon . ' ' . get_string('no_results', 'block_search'),
			array('class' => 'noResults')
		);

		if ($courseID) {
			//If searching in a course and there are no results,
			//suggest trying a full site search.

			$fullSearchURL = clone $PAGE->url;
			$fullSearchURL->remove_params(array('searchInCourse'));

			$icon = html_writer::tag('i', '', array('class' => 'fa fa-hand-o-right'));
			$a = html_writer::tag(
				'a',
				$icon . ' ' . get_string('try_full_search', 'block_search'),
				array(
					'href' => $fullSearchURL
				)
			);
			echo html_writer::tag(
				'div',
				$a,
				array('class' => 'noResults')
			);
		}

		/*
		 * Advanced Search Options
		 */
		echo $searchBlock->display->showAdvancedOptions();

	} else {

		$icon = html_writer::tag('i', '', array('class' => 'fa fa-list-ul'));

		$offset = $pageNum * $perPage;
		echo '<p id="showing">';
		echo get_string(
			'showing',
			'block_search',
			array(
				'start' => number_format($offset + 1),
				'end' => number_format(min(($offset + $perPage), $results['total'])),
				'total' => number_format($results['total'])
			)
		);
		echo '</p>';

		echo html_writer::tag('h2', $icon . ' ' . get_string('search_results', 'block_search'));

		/*
		 * Results menu (on the left)
		 */
		echo html_writer::start_tag('div', array('class' => 'col left'));
			echo $searchBlock->display->showResultsNav($results, $pageNum);

			// This is here so the leftcol still has content (and doesn't collapse)
			// when the resultsNav becomes position:fixed when scrolling
			echo '&nbsp;';

		echo html_writer::end_tag('div');

		/*
		 * Show results
		 */
		echo html_writer::start_tag('div', array('id' => 'results', 'class' => 'col right'));

			/*
			 * Results pagination
			 */
			$pagingbar = new paging_bar($results['total'], $pageNum, $perPage, $PAGE->url);

			echo $OUTPUT->render($pagingbar);

			echo $searchBlock->display->showResults($results['results'], $pageNum);

			echo $OUTPUT->render($pagingbar);

			/*
			 * Advanced Search Options
			 */
			echo $searchBlock->display->showAdvancedOptions();

		echo html_writer::end_tag('div');
	}

	echo html_writer::tag('div', '', array('class' => 'clear'));

} else {

	/*
	 * Advanced Search Options
	 */
	echo $searchBlock->display->showAdvancedOptions();

}


/*
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

	if (!empty($searchBlock->display->displayTime)) {
		echo '<br/>';
		echo get_string('displaying_took', 'block_search', $searchBlock->display->displayTime);
	}

	echo '<br/>';
	echo 'Memory used: <strong>' . number_format(memory_get_peak_usage() / 1024 / 1024) . 'MB</strong>';

	echo '</div>';
}

echo html_writer::end_tag('div');

/*
 * Javascript
 */
echo '<script src="' . $searchBlock->getFullURL() . 'assets/js/jquery.scrollTo.min.js?v=' . $searchBlock->version() . '"></script>';
echo '<script src="' . $searchBlock->getFullURL() . 'assets/js/jquery.localScroll.min.js?v=' . $searchBlock->version() . '"></script>';
echo '<script src="' . $searchBlock->getFullURL() . 'assets/js/block_search.js?v=' . $searchBlock->version() . '"></script>';

echo $OUTPUT->footer();
