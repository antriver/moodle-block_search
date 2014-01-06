<?php

/**
* Class with helper methods for displaying information within the 'search' block
*/

class MoodleSearchBlock
{

	public static function showSearchBox($q = false)
	{
		global $SITE;
		
		$r = '<form action="/block/search/" method="get" id="block_search_searchform">';
		
			$r .= '<input type="text" placeholder="Find courses, activities, and/or documents on ' . $SITE->shortname . '." value=" ' .$q . '" name="q"/>';
			
			$r .= '<button><i class="icon-search"></i> Search</button>';
		
		$r .= '</form>';
		return $r;
	}

}