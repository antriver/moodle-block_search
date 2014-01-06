<?php

require_once '../../config.php';
require_once __DIR__ . '/MoodleSearch/Block.php';
$searchBlock = new MoodleSearch\Block();

$q = optional_param('q', '', PARAM_RAW);

$PAGE->set_url('/blocks/search');
$PAGE->set_title(get_string('pagetitle', $searchBlock->blockName));
$PAGE->set_heading(get_string('pagetitle', $searchBlock->blockName));

echo $OUTPUT->header();
echo html_writer::start_tag('div', array('id' => $searchBlock->blockName));

//Add the CSS
//TODO: Is there a nicer way to do this than just echoing here?
echo '<link rel="stylesheet" type="text/css" href="' . $searchBlock->getFullURL() . 'assets/style.css" />';
echo '<script src="' . $searchBlock->getFullURL() . 'assets/js/jquery.scrollTo.min.js"></script>';
echo '<script src="' . $searchBlock->getFullURL() . 'assets/js/jquery.localScroll.min.js"></script>';
echo '<script src="' . $searchBlock->getFullURL() . 'assets/js/block_search.js"></script>';
echo "<script>
$(function(){
	$.localScroll({
		duration: 200,
		hash: true,
		offset: -35
	});
});
</script>";

echo $searchBlock->getSearchBox($q);
	
if (!empty($q)) {

	$icon = html_writer::tag('i', '', array('class' => 'icon-list-ul'));
	echo html_writer::tag('h2', "$icon Search Results");

	require_once __DIR__ . '/MoodleSearch/Search.php';
	$search = new MoodleSearch\Search($q);

	$results = $search->getResults();
	
	echo html_writer::start_tag('div', array('class' => 'col left'));
		echo $searchBlock->resultsNav($results);
		echo '&nbsp;'; //This is here so the leftcol still has content (and doesn't collapse) when the resultsNav becomes position:fixed when scrolling
	echo html_writer::end_tag('div');
	
	echo html_writer::start_tag('div', array('id' => 'results', 'class' => 'col right'));
		echo $searchBlock->formatResults($results);
	echo html_writer::end_tag('div');
		
}

echo html_writer::end_tag('div');
echo $OUTPUT->footer();
