<?php

/**
* Class with helper methods for displaying information within the 'search' block
*/

namespace MoodleSearch;

class Block
{

	public $blockName = 'block_search';
	private $path = '/blocks/search/';

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

	public function getSearchBox($q = false)
	{
		global $SITE;
		
		$r = \html_writer::start_tag(
			'form',
			array(
				'action' => $this->getFullURL(),
				'method' => 'get',
				'id' => 'searchForm'
			)
		);
		
			$r .= \html_writer::empty_tag(
				'input',
				array(
					'type' => 'text',
					'placeholder' => 'Find courses, activities, or documents on ' . $SITE->shortname,
					'value' => $q,
					'id' => 'searchInput',
					'name' => 'q'
				)
			);
			
			$r .= \html_writer::tag(
				'button',
				\html_writer::tag('i', '', array('class' => 'icon-search')) . ' Search',
				array('class' => 'searchButton')
			);
		
		$r .= \html_writer::end_tag('form');

		return $r;
	}


	//Shows the 'quick jump' box on the left of the results page
	public function resultsNav($results)
	{
		$r = \html_writer::start_tag('div', array('id' => 'resultsNav', 'class' => 'block'));
		
		$r .= \html_writer::start_tag('div', array('class' => 'header'));
			$r .= \html_writer::tag('h2', 'Items Found');
		$r .= \html_writer::end_tag('div');
		
		$r .= \html_writer::start_tag('div', array('class' => 'content'));
		$r .= \html_writer::start_tag('ul');
		
		foreach ($results as $tableName => $tableResults)
		{
			if (count($tableResults) < 1) {
				continue;
			}
			$sectionDetails = $this->formatResultTableName($tableName);
			
			$countLabel = \html_writer::tag('span', count($tableResults));
			
			$a = \html_writer::tag(
				'a',
				$countLabel . $sectionDetails['icon'] . ' ' . $sectionDetails['title'],
				array('href' => "#{$tableName}")
			);
			
			$r .= \html_writer::tag('li', $a);
		}
		
		$r .= \html_writer::end_tag('ul');
		$r .= \html_writer::end_tag('div');		
		$r .= \html_writer::end_tag('div');
		
		return $r;
	}

	//Takes the result set from a search and makes HTML to show it nicely
	public function formatResults($results)
	{
		$r = '';
		
		foreach ($results as $tableName => $tableResults)
		{
			if (count($tableResults) < 1) {
				continue;
			}
			$sectionDetails = $this->formatResultTableName($tableName);

			$r .= \html_writer::tag(
				'h3',
				$sectionDetails['icon'] . ' ' . $sectionDetails['title'],
				array('id' => $tableName)
			);
			
			$r .= \html_writer::start_tag('ul', array('class' => 'results'));
			foreach ($tableResults as $result) {
				$r .= $this->showResult($tableName, $result, $sectionDetails['icon']);
			}
			$r .= \html_writer::end_tag('ul');
		}
		
		return $r;
	}
	
	//Takes the name of a table and returns a nice human readable name
	//Mostly this replaces a module name (which is also a table name) with the title of that module
	private function formatResultTableName($tableName) {
		global $OUTPUT;
		
		switch ($tableName) {
			case 'course_categories':
				return array(
					'title' => 'Categories',
					'icon' => \html_writer::tag('i', '', array('class' => 'icon-folder-open'))
				);
				break;
			case 'course':
				return array(
					'title' => 'Courses',
					'icon' => \html_writer::tag('i', '', array('class' => 'icon-archive'))
				);
				break;		
			default:
				if ($pluginName = get_string('pluginname' , "mod_{$tableName}")) {
					return array(
						'title' => $pluginName,
						'icon' => trim($OUTPUT->pix_icon('icon', '', $tableName, array('class' => 'icon')))
					);
				} else {
					return array(
						'title' =>$tableName,
						'icon' => \html_writer::tag('i', '', array('class' => 'icon-certificate'))
					);
				}
		}
	}
	
	//Gets all the information needed to show a row nicely in the search results
	// e.g. gets the "path" to an activity, the URL, the icon etc.
	private function showResult($tableName, $result, $sectionIcon = false)
	{
		$r = \html_writer::start_tag('li');
		
			//Show the path
			$r .= \html_writer::tag('ul', $this->showPath($result->path()), array('class' => 'path'));
			
			$r .= \html_writer::tag('a', $sectionIcon . $result->name(), array('class' => 'resultLink', 'href' => $result->url()));
			
			if ($d = $result->description()) {
				$r .= $d; //Description looks like it is already wrapped in <p> tags
				//$r .= \html_writer::tag('p', $d);
			}
		
		$r .= \html_writer::end_tag('li');
		
		return $r;
	}
	
	private function showPath($path)
	{
		$r = '';
		foreach ($path as $item) {
			$icon = \html_writer::tag('i', '', array('class' => $item['icon']));
			$a = \html_writer::tag('a', $icon . ' ' .$item['name'], array('href' => $item['url'], 'title' => $item['title']));
			$r .= \html_writer::tag('li', $a);
		}
		return $r;
	}
}
