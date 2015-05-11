<?php

/**
 * Capabilities for search block
 *
 * @package    block_search
 * @copyright  Anthony Kuske <www.anthonykuske.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$capabilities = array(

	//Ability for users to search
	'block/search:search' => array(
		'captype' => 'read',
		'contextlevel' => CONTEXT_SYSTEM,
		'clonepermissionsfrom' => 'moodle/block:view', //copy permissions from this capability
		'archetypes' => array(
			'user' => CAP_ALLOW,
			'student' => CAP_ALLOW,
			'teacher' => CAP_ALLOW,
			'editingteacher' => CAP_ALLOW,
			'manager' => CAP_ALLOW,
		),
	),

	'block/search:addinstance' => array(
		'riskbitmask' => RISK_SPAM | RISK_XSS,
		'captype' => 'write',
		'contextlevel' => CONTEXT_MODULE,
		'clonepermissionsfrom' => 'moodle/site:manageblocks',
		'archetypes' => array(
			'editingteacher' => CAP_ALLOW,
			'manager' => CAP_ALLOW
		),
	),

    'block/search:myaddinstance' => array(
        'riskbitmask' => RISK_SPAM | RISK_XSS,
        'captype' => 'write',
        'contextlevel' => CONTEXT_MODULE,
        'clonepermissionsfrom' => 'moodle/site:manageblocks',
        'archetypes' => array(
            'editingteacher' => CAP_ALLOW,
            'manager' => CAP_ALLOW
        ),
    ),

);
