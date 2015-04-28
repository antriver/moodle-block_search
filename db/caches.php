<?php

/**
 * Cache definitions for search block
 *
 * @package    block_search
 * @copyright  Anthony Kuske <www.anthonykuske.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$definitions = array(
	'main' => array(
	    'mode' => cache_store::MODE_APPLICATION,
	),
	'user_searches' => array(
		'mode' => cache_store::MODE_SESSION,
	)
);
