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
 * Generates HTML for displaying search forms and search results
 * @package	   block_search
 * @copyright	 Anthony Kuske <www.anthonykuske.com>
 * @license	   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
 
namespace MoodleSearch;

class DisplayManager
{
	private $block;
	
	public function __construct(\MoodleSearch\Block $block)
	{
		$this->block = $block;
	}

	public function showSearchBox($q = false, $courseID = false, $showAllResults = false, $showOptions = true, $placeholderText = null)
	{
		global $SITE;
	
		//Begin form	
		$r = \html_writer::start_tag(
			'form',
			array(
				'action' => $this->block->getFullURL(),
				'method' => 'get',
				'class' => 'searchBlockForm'
			)
		);
		
			//Input box
			$r .= \html_writer::empty_tag(
				'input',
				array(
					'type' => 'text',
					'placeholder' => $placeholderText !== null ? $placeholderText : get_string('search_input_text_page', 'block_search'),
					'value' => $q,
					'class' => 'searchBlockInput',
					'name' => 'q'
				)
			);
			
			//Search Button
			$r .= \html_writer::tag(
				'button',
				\html_writer::tag('i', '', array('class' => 'icon-search')) . ' Search',
				array('class' => 'searchButton')
			);
			
			if ($showOptions) {
			
				$r .= '<strong>' . \html_writer::tag('i', '', array('class' => 'icon-cogs')) . ' Search Options:</strong>';
				
				//If courseID is in the URL, show options to search this course or everywhere
				if ($courseID) {
				
					$r .= \html_writer::tag(
						'label', 			
						\html_writer::empty_tag('input', array(
							'type' => 'radio',
							'name' => 'courseID',
							'value' => 0,
						)) . 'Search all of '. $SITE->shortname
					);
	
					$r .= \html_writer::tag(
						'label', 			
						\html_writer::empty_tag('input', array(
							'type' => 'radio',
							'name' => 'courseID',
							'value' => $courseID,
							'checked' => 'checked'
						)) . 'Search in ' . \MoodleSearch\DataManager::getCourseName($courseID)
					);				
					
				}
				
				//"Show hidden results" button
				
				//We need to make this an array so 'checked' can only be added if necessary
				$checkboxAttributes = array(
					'type' => 'checkbox',
					'name' => 'showHiddenResults',
					'value' => 1,
				);
				if ($showAllResults) {
					$checkboxAttributes['checked'] = 'checked';
				}
				
				$r .= \html_writer::tag(
						'label', 			
						\html_writer::empty_tag('input', $checkboxAttributes) . get_string('include_hidden_results', 'block_search')
					);
					
			} else if ($courseID) {
				
				//If we're not showing the options, but have a courseID we still need to add that to the form
				$r .= \html_writer::empty_tag('input', array('type' => 'hidden', 'name' => 'courseID', 'value' => $courseID));
			
			}
		
		$r .= \html_writer::end_tag('form');

		return $r;
	}
	
	
	//Shows the 'quick jump' box on the left of the results page
	public function showResultsNav($results)
	{
		$r = \html_writer::start_tag('div', array('id' => 'resultsNav', 'class' => 'block'));
		
		$r .= \html_writer::start_tag('div', array('class' => 'header'));
			$r .= \html_writer::tag('h2', 'Items Found');
		$r .= \html_writer::end_tag('div');
		
		$r .= \html_writer::start_tag('div', array('class' => 'content'));
		$r .= \html_writer::start_tag('ul');
		
		foreach ($results as $tableName => $tableResults) {
			if (count($tableResults) < 1) {
				continue;
			}
			$sectionDetails = $this->tableName($tableName);
			
			$countLabel = \html_writer::tag('span', count($tableResults));
			
			$a = \html_writer::tag(
				'a',
				$countLabel . $sectionDetails['icon'] . ' ' . $sectionDetails['title'],
				array('href' => "#searchresults-{$tableName}")
			);
			
			$r .= \html_writer::tag('li', $a);
		}
		
		$r .= \html_writer::end_tag('ul');
		$r .= \html_writer::end_tag('div');		
		$r .= \html_writer::end_tag('div');
		
		return $r;
	}
	
	//Takes the result set from a search and makes HTML to show it nicely
	public function showResults($results)
	{
		$r = '';
		
		foreach ($results as $tableName => $tableResults) {
			if (count($tableResults) < 1) {
				continue;
			}
			$sectionDetails = $this->tableName($tableName);

			//Section header in results
			$r .= \html_writer::tag(
				'h3',
				$sectionDetails['icon'] . ' ' . $sectionDetails['title'],
				array('id' => 'searchresults-' . $tableName)
			);
			
			//Show results from this table
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
	private function tableName($tableName) {
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
		$liClasses = '';
		
		if ($result->hidden) {
			$liClasses .= ' hidden';
		}
		
		$r = \html_writer::start_tag('li', array('class' => $liClasses));
		
			//Show the path
			$r .= \html_writer::tag('ul', $this->showPath($result->path()), array('class' => 'path'));
			
			if (!empty($result->hiddenReason)) {
				if ($result->hiddenReason == 'notenrolled') {
					$niceHiddenReason = 'You are not enroled in this course.';
				} else if ($result->hiddenReason == 'notvisible') {
					$niceHiddenReason = 'This resource hasn\'t been made available to you.';
				}
				$r .= \html_writer::tag(
					'h5', 
					 \html_writer::tag('i', '', array('class' => 'icon icon-remove')) . ' ' .$niceHiddenReason,
					array('class' => 'hiddenReason')
				);
			}
			
			$r .= \html_writer::tag('a', $sectionIcon . $result->name(), array('class' => 'resultLink', 'href' => $result->url()));
			
			if ($d = $result->description()) {
				$d = strip_tags($d);
				$d = $this->wordTruncate($d, 350);
				$r .= \html_writer::tag('p', $d);
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
	
	/**
	 * Truncate string to a certain length, but cut at the nearest word instead of cutting words in half
	 * @param    string $string        Text to truncate
	 * @param    int    $limit         Maximum length
	 * @param    string $cutter        Sting to append to text if it gets truncated
	 * @return string    Truncated text
	 */
	private function wordTruncate($string, $limit, $cutter = '...')
	{
		if (strlen($string) <= $limit) {
			return $string;
		}
	
		$limit -= strlen($cutter);
	
		$string = substr($string, 0, $limit);
		$string = trim($string, " ,.");
	
		$breakpoint = strrpos($string, ' '); //Find last space in truncated string
	
		if ($breakpoint === false) {
			return $string.$cutter;
		} else {
			$string = substr($string, 0, $breakpoint);
			$string = trim($string, " ,.");
			return $string.$cutter;
		}
	}


}