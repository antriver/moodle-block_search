<?php

require_once '../../config.php';
require_once __DIR__ . '/lib/moodle_search_block.php';

$q = optional_param('q', '', PARAM_RAW);

$PAGE->set_url('/blocks/search');
$PAGE->set_title(get_string('pagetitle', BLOCK_NAME));
$PAGE->set_heading(get_string('pagetitle', BLOCK_NAME));

echo $OUTPUT->header();

	echo MoodleSearchBlock::showSearchBox();
	
	if ($q) {
	
		require_once __DIR__ . '/lib/moodle_search.php';
		$search = new MoodleSearch(true);
	
		echo '<pre>';
					
		$results = $search->search($_GET['q']);
		
		print_object($results);
		
		echo '</pre>';
	
	}	

echo $OUTPUT->footer();
