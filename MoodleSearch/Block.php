<?php

/**
* Class with helper methods for displaying information within the 'search' block
*/

namespace MoodleSearch;

class Block
{
	public $display;
	public $blockName = 'block_search';
	private $path = '/blocks/search/';

	public function __construct()
	{
		//Autoloader would be nice here
		require_once __DIR__ . '/Model/Result.php';
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
}
