<?php

/**
 * @package    block_search
 * @copyright  Anthony Kuske <www.anthonykuske.com>
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
    * @param mixed $order_by
    * @return array
    */
    public static function sortMultidimensionalArray(array $array, $order_by)
    {
        /*if(!is_array($array[0]))
           throw new Exception('$array must be a multidimensional array!',E_USER_ERROR);*/

        $columns = explode(',', $order_by);
        foreach ($columns as $col_dir) {
            if (preg_match('/(.*)([\s]+)(ASC|DESC)/is', $col_dir, $matches)) {
                if (!array_key_exists(trim($matches[1]), $array[0])) {
                    trigger_error('Unknown Column <b>' . trim($matches[1]) . '</b>', E_USER_NOTICE);
                } else {
                    if (isset($sorts[trim($matches[1])])) {
                        trigger_error('Redundand specified column name : <b>' . trim($matches[1] . '</b>'));
                    }

                    $sorts[trim($matches[1])] = 'SORT_'.strtoupper(trim($matches[3]));
                }
            } else {
                throw new Exception("Incorrect syntax near : '{$col_dir}'", E_USER_ERROR);
            }
        }

        //TODO -c optimization -o tufanbarisyildirim : use array_* functions.
        $colarr = array();
        foreach ($sorts as $col => $order) {
            $colarr[$col] = array();
            foreach ($array as $k => $row) {
                #$colarr[$col]['_'.$k] = strtolower($row[$col]);
                $colarr[$col]['_'.$k] = strtolower($row->{$col});
            }
        }

        $multi_params = array();
        foreach ($sorts as $col => $order) {
            $multi_params[] = '$colarr[\'' . $col .'\']';
            $multi_params[] = $order;
        }

        $rum_params = implode(',', $multi_params);
        eval("array_multisort({$rum_params});");

        $sorted_array = array();
        foreach ($colarr as $col => $arr) {
            foreach ($arr as $k => $v) {
                $k = substr($k, 1);
                if (!isset($sorted_array[$k])) {
                    $sorted_array[$k] = $array[$k];
                }
                #$sorted_array[$k][$col] = $array[$k][$col];
                $sorted_array[$k]->{$col} = $array[$k]->{$col};
            }
        }

        return array_values($sorted_array);
    }
}
