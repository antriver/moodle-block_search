<?php

require_once '../../config.php';

require_once __DIR__ . '/MoodleSearch/Block.php';
$searchBlock = new MoodleSearch\Block();

$q = optional_param('q', '', PARAM_RAW);
$courseID = optional_param('courseID', 0, PARAM_INT);
$showHiddenResults = optional_param('showHiddenResults', false, PARAM_BOOL);

if ($courseID) {
	$PAGE->set_context(context_course::instance($courseID));
} else {
	$PAGE->set_context(context_system::instance());
}

$PAGE->set_url('/blocks/search');
$PAGE->set_title(get_string('pagetitle', $searchBlock->blockName));
$PAGE->set_heading(get_string('pagetitle', $searchBlock->blockName));

echo $OUTPUT->header();
echo html_writer::start_tag('div', array('id' => $searchBlock->blockName));

//Add the CSS
//TODO: Is there a nicer way to do this than just echoing here?
echo '<link rel="stylesheet" type="text/css" href="' . $searchBlock->getFullURL() . 'assets/css/style.css" />';

echo $searchBlock->display->showSearchBox($q, $courseID, $showHiddenResults);
	
if (!empty($q)) {

	$icon = html_writer::tag('i', '', array('class' => 'icon-list-ul'));
	echo html_writer::tag('h2', "$icon Search Results");

	$removeHiddenResults = empty($showHiddenResults) ? true : false;
				
	//Do the search
	$search = new MoodleSearch\Search($q, $courseID);
	$search->filterResults($removeHiddenResults);
	$results = $search->getResults();	
	
	#unset($results['tables']);
	#print_object($results);

	echo html_writer::start_tag('div', array('class' => 'col left'));
		echo $searchBlock->display->showResultsNav($results['tables']);
		
		//This is here so the leftcol still has content (and doesn't collapse) when the resultsNav becomes position:fixed when scrolling
		echo '&nbsp;';
		
	echo html_writer::end_tag('div');
	
	echo html_writer::start_tag('div', array('id' => 'results', 'class' => 'col right'));
		echo $searchBlock->display->showResults($results['tables']);
	echo html_writer::end_tag('div');
		
}

echo html_writer::end_tag('div');


//Show some info about the search
if (!empty($results)) {
	echo '<p>Search took <strong>' . $results['searchTime'] . '</strong> seconds.';
	if ($results['cached']) {
		echo '<br/>Cached results generated <strong>' . $results['generated'] . '</strong>';
	}
	
	echo '<br/>Filtering results took <strong>' . $results['filterTime'] .'</strong> seconds.';
}

echo '<script src="' . $searchBlock->getFullURL() . 'assets/42/jquery.scrollTo.min.js"></script>';
echo '<script src="' . $searchBlock->getFullURL() . 'assets/js/jquery.localScroll.min.js"></script>';
echo '<script src="' . $searchBlock->getFullURL() . 'assets/js/block_search.js"></script>';
echo "<script> $(function(){ $.localScroll({duration: 200, hash: true, offset: -35 }); }); </script>";

echo $OUTPUT->footer();
