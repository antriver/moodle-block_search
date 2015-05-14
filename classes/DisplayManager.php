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
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Generates HTML for displaying search forms and search results
 *
 * @package    block_search
 * @copyright  2015 Anthony Kuske <www.anthonykuske.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_search;

use html_writer;

class DisplayManager
{
    private $block;

    public function __construct(Block $block) {

        $this->block = $block;
    }

    public function show_search_box(
        $q = false,
        $courseid = false,
        $searchincourse = false,
        $showallresults = false,
        $showoptions = true,
        $placeholdertext = null
    ) {
        global $SITE;

        $r = html_writer::start_tag(
            'form',
            array(
                'action' => $this->block->get_full_url(),
                'method' => 'get',
                'class' => 'block-search-form'
            )
        );

        if ($placeholdertext === null) {
            $placeholdertext = $this->str('search_input_text_page');
        }

        // Input box.
        $r .= html_writer::empty_tag(
            'input',
            array(
                'type' => 'text',
                'placeholder' => $placeholdertext,
                'value' => $q,
                'name' => 'q'
            )
        );

        // Search Button.
        $icon = html_writer::tag('i', '', array('class' => 'fa fa-search'));
        $r .= html_writer::tag(
            'button',
            $icon . ' ' . $this->str('search'),
            array('class' => 'searchButton')
        );

        if ($showoptions) {

            $r .= html_writer::start_tag('div', array('class' => 'options'));

            $allownoaccess = get_config('block_search', 'allow_no_access');
            $showoptionstitle = $allownoaccess || !empty($courseid);

            if ($showoptionstitle) {
                $icon = html_writer::tag('i', '', array('class' => 'fa fa-cogs'));
                $r .= '<strong>' . $icon . ' ' . $this->str('search_options') . '</strong>';
            }

            // If courseID is in the URL, show options to search this course or everywhere.
            if ($courseid) {

                // Hidden courseID field.
                $r .= html_writer::empty_tag('input', array(
                    'type' => 'hidden',
                    'name' => 'courseID',
                    'value' => $courseid
                ));

                $inputparams = array(
                        'type' => 'radio',
                        'name' => 'searchInCourse',
                        'value' => 0,
                );
                if (!$searchincourse) {
                    $inputparams['checked'] = 'checked';
                }

                $r .= html_writer::tag(
                    'label',
                    html_writer::empty_tag('input', $inputparams) . $this->str('search_all_of_site', $SITE->shortname)
                );

                $inputparams = array(
                        'type' => 'radio',
                        'name' => 'searchInCourse',
                        'value' => 1
                );
                if ($searchincourse) {
                    $inputparams['checked'] = 'checked';
                }

                $coursename = DataManager::get_course_name($courseid);
                $r .= html_writer::tag(
                    'label',
                    html_writer::empty_tag('input', $inputparams) . $this->str('search_in_course', $coursename)
                );

            }

            if ($allownoaccess) {
                // "Show hidden results" button
                // We need to make this an array so 'checked' can only be added if necessary.
                $checkboxattributes = array(
                    'type' => 'checkbox',
                    'name' => 'showHiddenResults',
                    'value' => 1,
                );

                if ($showallresults) {
                    $checkboxattributes['checked'] = 'checked';
                }

                $checkbox = html_writer::empty_tag('input', $checkboxattributes);

                $r .= html_writer::tag(
                    'label',
                    $checkbox . $this->str('include_hidden_results')
                );
            }

            $r .= html_writer::end_tag('div');

        } else if ($courseid) {

            // If we're not showing the options, but have a courseID we still need to add that to the form.
            $r .= html_writer::empty_tag(
                'input',
                array(
                    'type' => 'hidden',
                    'name' => 'courseID',
                    'value' => $courseid
                )
            );

        }

        $r .= html_writer::end_tag('form');

        return $r;
    }


    /**
     * Returns the HTML for the 'quick jump' box on the left of the results page.
     * @param  array $results
     * @param  int $currentpage
     * @return string
     */
    public function show_results_nav($results, $currentpage) {

        $r = html_writer::start_tag('div', array('id' => 'resultsNav', 'class' => 'block'));

        $r .= html_writer::start_tag('div', array('class' => 'header'));
            $r .= html_writer::start_tag('div', array('class' => 'title'));
                $r .= html_writer::tag('h2', $this->str('items_found', number_format($results['total'])));
            $r .= html_writer::end_tag('div');
        $r .= html_writer::end_tag('div');

        $r .= html_writer::start_tag('div', array('class' => 'content'));
        $r .= html_writer::start_tag('ul');

        foreach ($results['tables'] as $tablename => $tableinfo) {
            if ($tableinfo['count'] < 1) {
                continue;
            }
            $sectiondetails = $this->get_nice_table_name($tablename);

            if ($tableinfo['hiddenCount'] > 0) {
                $countlabel = html_writer::tag(
                    'span',
                    $tableinfo['visibleCount'] . ' + ' . $tableinfo['count'] . ' hidden'
                );
            } else {
                $countlabel = html_writer::tag(
                    'span',
                    $tableinfo['visibleCount']
                );
            }

            if ($tableinfo['startPage'] == $currentpage) {
                $href = "#searchresults-{$tablename}";
            } else {
                global $PAGE;
                $url = clone ($PAGE->url);
                $url->param('page', $tableinfo['startPage']);
                $href = $url->out(false) . "#searchresults-{$tablename}";
            }

            $a = html_writer::tag(
                'a',
                $countlabel . $sectiondetails['icon'] . $sectiondetails['title'],
                array('href' => $href)
            );

            $r .= html_writer::tag('li', $a);
        }

        $r .= html_writer::end_tag('ul');
        $r .= html_writer::end_tag('div');
        $r .= html_writer::end_tag('div');

        return $r;
    }

    public function slice_results_for_page($results, $pagenum) {

        $perpage = (int)get_config('block_search', 'results_per_page');
        $offset = $pagenum * $perpage;
        return array_splice($results, $offset, $perpage);
    }

    /**
     * Takes the result set from a search and makes HTML to show it nicely
     */
    public function show_results($results, $pagenum = 0, $currenttable = false) {

        $starttime = DataManager::get_debug_time();

        $results = $this->slice_results_for_page($results, $pagenum);

        $r = '';

        foreach ($results as $result) {

            if ($currenttable === false || $result->tablename != $currenttable) {

                // Start of a new section in results.

                if ($currenttable !== false) {
                    // Close the previous section.
                    $r .= html_writer::end_tag('ul');
                }

                // Section header in results.
                $sectiondetails = $this->get_nice_table_name($result->tablename);
                $r .= html_writer::tag(
                    'h3',
                    $sectiondetails['icon'] . ' ' . $sectiondetails['title'],
                    array('id' => 'searchresults-' . $result->tablename)
                );

                // Show results from this table.
                $r .= html_writer::start_tag('ul', array('class' => 'results'));

                $currenttable = $result->tablename;
            }

            $r .= $this->show_result($result->tablename, $result, $sectiondetails['icon']);
        }

        $r .= html_writer::end_tag('ul');

        $this->displayTime = DataManager::get_debug_time_taken($starttime);

        return $r;
    }

    /**
     * Takes the name of a table and returns a nice human readable name.
     * @param  string $tablename
     * @return string
     */
    private function get_nice_table_name($tablename) {

        global $OUTPUT;

        switch ($tablename) {
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
                if ($pluginname = get_string('pluginname', "mod_{$tablename}")) {
                    return array(
                        'title' => $pluginname,
                        'icon' => trim($OUTPUT->pix_icon('icon', '', $tablename, array('class' => 'icon')))
                    );
                } else {
                    return array(
                        'title' => $tablename,
                        'icon' => html_writer::tag('i', '', array('class' => 'fa fa-certificate'))
                    );
                }
        }
    }

    /**
     * Gets all the information needed to show a row nicely in the search results
     * e.g. gets the "path" to an activity, the URL, the icon etc.
     */
    private function show_result($tablename, $result, $defaultsectionicon = false) {

        $liclasses = '';

        if ($result->hidden) {
            $liclasses .= ' hideresult';
        }

        $r = html_writer::start_tag('li', array('class' => $liclasses));

        // Show the path.
        $r .= html_writer::tag('ul', $this->show_path($result->path()), array('class' => 'path'));

        if (!empty($result->hiddenreason)) {
            if ($result->hiddenreason == 'notenrolled') {
                $nicehiddenreason = $this->str('hidden_not_enrolled');
            } else if ($result->hiddenreason == 'notvisible') {
                $nicehiddenreason = $this->str('hidden_not_available');
            }

            $hiddenicon = html_writer::tag('i', '', array('class' => 'fa fa-times'));
            $r .= html_writer::tag(
                'h5',
                $hiddenicon . ' ' . $nicehiddenreason,
                array('class' => 'hiddenreason')
            );
        }

        $icon = $result->icon();
        if (!$icon) {
            $icon = $defaultsectionicon;
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
            $d = $this->word_truncate($d, 350);
            $r .= html_writer::tag('p', $d);
        }

        $r .= html_writer::end_tag('li');

        return $r;
    }

    private function show_path($path) {

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
    private function word_truncate($string, $limit, $cutter = '...') {

        if (strlen($string) <= $limit) {
            return $string;
        }

        $limit -= strlen($cutter);

        $string = substr($string, 0, $limit);
        $string = trim($string, " ,.");

        // Find last space in truncated string.
        $breakpoint = strrpos($string, ' ');

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
    public function show_advanced_options() {

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
     */
    private function str($name, $params = false) {

        return get_string($name, 'block_search', $params);
    }
}
