<?php

/**
 * Generates HTML for displaying search forms and search results
 *
 * @package    block_search
 * @copyright  Anthony Kuske <www.anthonykuske.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_search;

use html_writer;

class DisplayManager
{
	private $block;
	public $displayTime = 0;

	public function __construct(Block $block)
	{
		$this->block = $block;
	}

	public function showSearchBox(
		$q = false,
		$courseID = false,
		$searchInCourse = false,
		$showAllResults = false,
		$showOptions = true,
		$placeholderText = null
	) {
		global $SITE;

		//Begin form
		$r = html_writer::start_tag(
			'form',
			array(
				'action' => $this->block->getFullURL(),
				'method' => 'get',
				'class' => 'searchBlockForm'
			)
		);

		if ($placeholderText === null) {
			$placeholderText = $this->str('search_input_text_page');
		}

		//Input box
		$r .= html_writer::empty_tag(
			'input',
			array(
				'type' => 'text',
				'placeholder' => $placeholderText,
				'value' => $q,
				'class' => 'searchBlockInput',
				'name' => 'q'
			)
		);

		//Search Button
		$icon = html_writer::tag('i', '', array('class' => 'fa fa-search'));
		$r .= html_writer::tag(
			'button',
			$icon . ' ' . $this->str('search'),
			array('class' => 'searchButton')
		);

		if ($showOptions) {

			$r .= html_writer::start_tag('div', array('class' => 'options'));

			$allowNoAccess = get_config('block_search', 'allow_no_access');
			$showOptionsTitle = $allowNoAccess || !empty($courseID);

			if ($showOptionsTitle) {
				$icon = html_writer::tag('i', '', array('class' => 'fa fa-cogs'));
				$r .= '<strong>' . $icon . ' ' . $this->str('search_options') . '</strong>';
			}

			//If courseID is in the URL, show options to search this course or everywhere
			if ($courseID) {

				// hidden courseID field
				$r .= html_writer::empty_tag('input', array(
					'type' => 'hidden',
					'name' => 'courseID',
					'value' => $courseID
				));


				$inputParams = array(
						'type' => 'radio',
						'name' => 'searchInCourse',
						'value' => 0,
				);
				if (!$searchInCourse) {
					$inputParams['checked'] = 'checked';
				}

				$r .= html_writer::tag(
					'label',
					html_writer::empty_tag('input', $inputParams) . $this->str('search_all_of_site', $SITE->shortname)
				);

				$inputParams = array(
						'type' => 'radio',
						'name' => 'searchInCourse',
						'value' => 1
				);
				if ($searchInCourse) {
					$inputParams['checked'] = 'checked';
				}

				$courseName = DataManager::getCourseName($courseID);
				$r .= html_writer::tag(
					'label',
					html_writer::empty_tag('input', $inputParams) . $this->str('search_in_course', $courseName)
				);

			}

			if ($allowNoAccess) {
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

				$checkbox = html_writer::empty_tag('input', $checkboxAttributes);

				$r .= html_writer::tag(
					'label',
					$checkbox . $this->str('include_hidden_results')
				);
			}

			$r .= html_writer::end_tag('div');

		} elseif ($courseID) {

			//If we're not showing the options, but have a courseID we still need to add that to the form
			$r .= html_writer::empty_tag(
				'input',
				array(
					'type' => 'hidden',
					'name' => 'courseID',
					'value' => $courseID
				)
			);

		}

		$r .= html_writer::end_tag('form');

		return $r;
	}


	//Shows the 'quick jump' box on the left of the results page
	public function showResultsNav($results, $currentPage)
	{
		$r = html_writer::start_tag('div', array('id' => 'resultsNav', 'class' => 'block'));

		$r .= html_writer::start_tag('div', array('class' => 'header'));
			$r .= html_writer::start_tag('div', array('class' => 'title'));
				$r .= html_writer::tag('h2', $this->str('items_found', number_format($results['total'])));
			$r .= html_writer::end_tag('div');
		$r .= html_writer::end_tag('div');

		$r .= html_writer::start_tag('div', array('class' => 'content'));
		$r .= html_writer::start_tag('ul');

		foreach ($results['tables'] as $tableName => $tableInfo) {
			if ($tableInfo['count'] < 1) {
				continue;
			}
			$sectionDetails = $this->tableName($tableName);

			if ($tableInfo['hiddenCount'] > 0) {
				$countLabel = html_writer::tag(
					'span',
					$tableInfo['visibleCount'] . ' + ' . $tableInfo['count'] . ' hidden'
				);
			} else {
				$countLabel = html_writer::tag(
					'span',
					$tableInfo['visibleCount']
				);
			}

			if ($tableInfo['startPage'] == $currentPage) {
				$href = "#searchresults-{$tableName}";
			} else {
				global $PAGE;
				$url = clone ($PAGE->url);
				$url->param('page', $tableInfo['startPage']);
				$href = $url->out(false) . "#searchresults-{$tableName}";
			}

			$a = html_writer::tag(
				'a',
				$countLabel . $sectionDetails['icon'] . $sectionDetails['title'],
				array('href' => $href)
			);

			$r .= html_writer::tag('li', $a);
		}

		$r .= html_writer::end_tag('ul');
		$r .= html_writer::end_tag('div');
		$r .= html_writer::end_tag('div');

		return $r;
	}

	public function sliceResultsForPage($results, $pageNum)
	{
		$perPage = (int)get_config('block_search', 'results_per_page');
		$offset = $pageNum * $perPage;
		return array_splice($results, $offset, $perPage);
	}

	//Takes the result set from a search and makes HTML to show it nicely
	public function showResults($results, $pageNum = 0, $currentTable = false)
	{
		$startTime = DataManager::getDebugTime();

		$results = $this->sliceResultsForPage($results, $pageNum);

		$r = '';

		foreach ($results as $result) {

			if ($currentTable === false || $result->tableName != $currentTable) {

				//Start of a new section in results

				if ($currentTable !== false) {
					//Close the previous section
					$r .= html_writer::end_tag('ul');
				}

				//Section header in results
				$sectionDetails = $this->tableName($result->tableName);
				$r .= html_writer::tag(
					'h3',
					$sectionDetails['icon'] . ' ' . $sectionDetails['title'],
					array('id' => 'searchresults-' . $result->tableName)
				);

				//Show results from this table
				$r .= html_writer::start_tag('ul', array('class' => 'results'));

				$currentTable = $result->tableName;
			}

			$r .= $this->showResult($result->tableName, $result, $sectionDetails['icon']);
		}

		$r .= html_writer::end_tag('ul');

		$this->displayTime = DataManager::debugTimeTaken($startTime);

		return $r;
	}

	//Takes the name of a table and returns a nice human readable name
	//Mostly this replaces a module name (which is also a table name) with the title of that module
	private function tableName($tableName)
	{
		global $OUTPUT;

		switch ($tableName) {
			case 'course_categories':
				return array(
					'title' => get_string('categories', 'moodle'),
					'icon' => html_writer::tag('i', '', array('class' => 'fa fa-folder-open'))
				);
				break;
			case 'course':
				return array(
					'title' => get_string('courses', 'moodle'),
					'icon' => html_writer::tag('i', '', array('class' => 'fa fa-archive'))
				);
				break;
			case 'folder_files':
				return array(
					'title' => $this->str('folder_contents'),
					'icon' => html_writer::tag('i', '', array('class' => 'fa fa-folder'))
				);
				break;
			default:
				if ($pluginName = get_string('pluginname', "mod_{$tableName}")) {
					return array(
						'title' => $pluginName,
						'icon' => trim($OUTPUT->pix_icon('icon', '', $tableName, array('class' => 'icon')))
					);
				} else {
					return array(
						'title' =>$tableName, //oops no localization
						'icon' => html_writer::tag('i', '', array('class' => 'fa fa-certificate'))
					);
				}
		}
	}

	//Gets all the information needed to show a row nicely in the search results
	// e.g. gets the "path" to an activity, the URL, the icon etc.
	private function showResult($tableName, $result, $defaultSectionIcon = false)
	{
		$liClasses = '';

		if ($result->hidden) {
			$liClasses .= ' hideresult';
		}

		$r = html_writer::start_tag('li', array('class' => $liClasses));

		//Show the path
		$r .= html_writer::tag('ul', $this->showPath($result->path()), array('class' => 'path'));

		if (!empty($result->hiddenReason)) {
			if ($result->hiddenReason == 'notenrolled') {
				$niceHiddenReason = $this->str('hidden_not_enrolled');
			} elseif ($result->hiddenReason == 'notvisible') {
				$niceHiddenReason = $this->str('hidden_not_available');
			}

			$hiddenIcon = html_writer::tag('i', '', array('class' => 'fa fa-times'));
			$r .= html_writer::tag(
				'h5',
				$hiddenIcon . ' ' .$niceHiddenReason,
				array('class' => 'hiddenReason')
			);
		}

		$icon = $result->icon();
		if (!$icon) {
			$icon = $defaultSectionIcon;
		}

		$r .= html_writer::tag(
			'a',
			$icon . $result->name(),
			array(
				'class' => 'resultLink',
				'href' => $result->url()
			)
		);

		if ($d = $result->description()) {
			$d = strip_tags($d);
			$d = $this->wordTruncate($d, 350);
			$r .= html_writer::tag('p', $d);
		}

		$r .= html_writer::end_tag('li');

		return $r;
	}

	private function showPath($path)
	{
		$r = '';
		foreach ($path as $item) {
			$icon = html_writer::tag('i', '', array('class' => $item['icon']));
			$a = html_writer::tag(
				'a',
				$icon . ' ' . $item['name'],
				array(
					'href' => $item['url'],
					'title' => $item['title']
				)
			);
			$r .= html_writer::tag('li', $a);
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

	/**
	 * Returns the HTML for displaying the advanced search options to the user
	 */
	public function showAdvancedOptions()
	{
		$r = '<div class="advancedOptions">'
			. '<h4><i class="fa fa-crosshairs"></i> '. $this->str('advanced_search_title') . '</h4>'
			. '<p>' . $this->str('advanced_search_desc') . '</p>'
			. '<div class="col">'
				. '<p><em>-'. $this->str('advanced_search_exclude_example') . '</em> ' . $this->str('advanced_search_exclude_desc') .'</p>'
				. '<p><em>&quot;' . $this->str('advanced_search_exact_example') . '&quot;</em> '
				. $this->str('advanced_search_exact_desc') . '</p>'
			. '</div>'
			. '<div class="col">'
				. '<p><em>'. $this->str('advanced_search_wildcard_example') . '</em> ' . $this->str('advanced_search_wildcard_desc') . '</p>'
			. '</div>'
			. '<div class="clear"></div>'
		. '</div>';
		return $r;
	}

	/**
	 * Shortcut to get_string from the block.
	 * (Only supports one parameter though)
	 */
	private function str($name, $params = false)
	{
		return get_string($name, 'block_search', $params);
	}
}
