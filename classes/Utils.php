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
 * @package    block_search
 * @copyright  2015 Anthony Kuske <www.anthonykuske.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_search;

class Utils
{
    /**
     * @name Mutlidimensional Array Sorter.
     * @author Tufan Barış YILDIRIM
     * @link http://www.tufanbarisyildirim.com
     * @github http://github.com/tufanbarisyildirim
     *
     * This function can be used for sorting a multidimensional array by sql like order by clause
     *
     * Tweaked to work with objects Feb 17th 2014
     * @author Anthony Kuske <www.anthonykuske.com>
     *
     * @param mixed $array
     * @param mixed $orderby
     * @return array
     */
    public static function sort_multidimensional_array(array $array, $orderby) {

        $columns = explode(',', $orderby);
        foreach ($columns as $coldir) {
            if (preg_match('/(.*)([\s]+)(ASC|DESC)/is', $coldir, $matches)) {
                if (!array_key_exists(trim($matches[1]), $array[0])) {
                    trigger_error('Unknown Column <b>' . trim($matches[1]) . '</b>', E_USER_NOTICE);
                } else {
                    if (isset($sorts[trim($matches[1])])) {
                        trigger_error('Redundand specified column name : <b>' . trim($matches[1] . '</b>'));
                    }

                    $sorts[trim($matches[1])] = 'SORT_'.strtoupper(trim($matches[3]));
                }
            } else {
                throw new Exception("Incorrect syntax near : '{$coldir}'", E_USER_ERROR);
            }
        }

        // TODO -c optimization -o tufanbarisyildirim : use array_* functions.
        $colarr = array();
        foreach ($sorts as $col => $order) {
            $colarr[$col] = array();
            foreach ($array as $k => $row) {
                $colarr[$col]['_'.$k] = strtolower($row->{$col});
            }
        }

        $multiparams = array();
        foreach ($sorts as $col => $order) {
            $multiparams[] = '$colarr[\'' . $col .'\']';
            $multiparams[] = $order;
        }

        $rumparams = implode(',', $multiparams);
        eval("array_multisort({$rumparams});");

        $sortedarray = array();
        foreach ($colarr as $col => $arr) {
            foreach ($arr as $k => $v) {
                $k = substr($k, 1);
                if (!isset($sortedarray[$k])) {
                    $sortedarray[$k] = $array[$k];
                }
                $sortedarray[$k]->{$col} = $array[$k]->{$col};
            }
        }

        return array_values($sortedarray);
    }
}
