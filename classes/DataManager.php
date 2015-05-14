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
 * This class contains a lot of static methods to make it easier to grab
 * info from Moodle. Most importantly, most of these methods used
 * cached info to avoid hitting the database.
 *
 * @package    block_search
 * @copyright  2015 Anthony Kuske <www.anthonykuske.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_search;

use cache;
use cm_info;
use context_module;
use moodle_exception;
use xmldb_field;
use xmldb_table;

class DataManager
{
    private static $cache;

    /**
     * Returns the unique instance ID for a resource across all of Moodle
     * given an ID which is unique only to that module
     */
    public static function get_global_instance_id_from_module_instance_id($modulename, $moduleinstanceid) {

        return self::get_db_field(
            'course_modules',
            'id',
            array(
                'module' => self::get_module_id($modulename),
                'instance' => $moduleinstanceid
            )
        );
    }

    /**
     * Returns the ID for an installed module (plugin), given the name of the module
     */
    public static function get_module_id($modulename) {

        return self::get_db_field('modules', 'id', array('name' => $modulename));
    }

    /**
     * Get a course record
     */
    public static function get_course($courseid) {

        return self::get_db_record('course', array('id' => $courseid));
    }

    /**
     * Returns the fullname for a course
     */
    public static function get_course_name($courseid) {

        $course = self::get_course($courseid);
        return $course->fullname;
    }

    /**
     * Returns the row for a section in a course
     */
    public static function get_section($sectionid) {

        return self::get_db_record('course_sections', array('id' => $sectionid));
    }

    /**
     * Returns information about a section a resource is in
     */
    public static function get_resource_section($modulename, $instanceid) {

        // Get the module id from the module's name.
        $moduleid = self::get_module_id($modulename);

        if (!$moduleid) {
            return false;
        }

        // Get the sectionID the resource is in.
        $sectionid = self::get_db_field(
            'course_modules',
            'section',
            array(
                'module' => $moduleid,
                'instance' => $instanceid
            )
        );

        if (!$sectionid) {
            return false;
        }

        return self::get_section($sectionid);
    }

    public static function get_resource_section_from_course_module_id($coursemoduleid) {

        $sectionid = self::get_db_field(
            'course_modules',
            'section',
            array(
                'id' => $coursemoduleid
            )
        );

        if (!$sectionid) {
            return false;
        }

        return self::get_section($sectionid);
    }



    public static function can_user_see_module($courseid, $modulename, $idinmodule) {

        if (!$courseid || !$idinmodule) {
            return false;
        }

        global $USER;

        // Get the overall coursemodule ID, from the module's ID inside the plugin.
        $cmid = self::get_global_instance_id_from_module_instance_id($modulename, $idinmodule);

        if (!$cmid) {
            return false;
        }

        // Load the "modinfo" for the course, and see if the module is "uservisible"
        // This is pretty expensive and is likely the source of any slowness,
        // because get_fast_modinfo loads info for all the modules in the course
        // even though we only want the one.
        // 2015-05-14: Does this still suck even in Moodle 2.9?
        $modinfo = get_fast_modinfo($courseid, $USER->id);

        try {
            $cm = $modinfo->get_cm($cmid);

            if (!$cm->uservisible) {
                return false;
            }

            // get_cm throws a moodle_exception if it's not found.
        } catch (moodle_exception $e) {
            return false;
        }

        // It still might not be right to show it, because some plugins still want to be shown
        // but the user will just see "you don't have permission" when they click it
        // So let's handle each plugin that's awkward and check if the user has whatever capability applies to it
        switch ($modulename) {

            case 'chat':
                $capability = 'mod/chat:chat';
                break;

            case 'choice':
                $capability = 'mod/choice:readresponses';
                break;

            case 'data':
                $capability = 'mod/data:viewentry';
                break;

            case 'forum':
                $capability = 'mod/forum:viewdiscussion';
                break;

            /*case 'lesson':
                //The view.php only checks for :manage. Maybe there's no view capability for this plugin?
                $capability = 'mod/lesson:manage';
                break;*/

            /*case 'survey': //questionnaire the same plugin?
                $capability = 'mod/questionnaire:view';
                break;*/

            case 'wiki':
                $capability = 'mod/wiki:viewpage';
                break;

            case '  book':
                $capability = 'mod/book:read';
                break;

            case 'label':
                // There's no view capability for labels - everybody can see.
                break;
        }

        // If this plugin has a capability we can check.
        if (!empty($capability)) {
            // Check if the user has the capability within the module context.
            $modulecontext = context_module::instance($cmid);
            if (!has_capability($capability, $modulecontext, $USER->id)) {
                return false;
            }
        }

        // Now they can see it.
        return true;
    }


    /**
     * Gets a single field from a table in the database (cached)
     * TODO: Use MUC
     */
    private static function get_db_field($tablename, $fieldname, $where) {

        $hash = md5("field{$tablename}{$fieldname}".http_build_query($where));

        if (false && $res = self::get_cache()->get($hash)) {
            return $res;
        }

        global $DB;
        $res = $DB->get_field($tablename, $fieldname, $where);

        self::get_cache()->set($hash, $res);

        return $res;
    }

    /**
     * Gets a single row from a table in the database (cached)
     * TODO: Use MUC
     */
    private static function get_db_record($tablename, $where) {

        $hash = md5("record{$tablename}".http_build_query($where));

        if (false && $res = self::get_cache()->get($hash)) {
            return $res;
        }

        global $DB;
        $res = $DB->get_record($tablename, $where);

        self::get_cache()->set($hash, $res);

        return $res;
    }

    /**
     * Returns the cache object.
     * Creates a new one when called for the first time.
     */
    public static function get_cache() {

        if (!empty(self::$cache)) {
            return self::$cache;
        }

        self::$cache = cache::make('block_search', 'main');

        return self::$cache;
    }

    public static function get_tables_possible_to_search() {

        global $DB;

        $tables = array();

        // Courses table.
        $tables['course'] = 'course (fullname, shortname)';

        // Category table.
        $tables['course_categories'] = 'course_categories (name, description)';

        // Database manager object.
        $dbman = $DB->get_manager();

        // Get all modules (plugins) - we're going to search their tables.
        $modules = $DB->get_records('modules', array(), 'name');

        foreach ($modules as $module) {

            $tablename = $module->name;
            $tablefields = array();

            // Create an xmldb object from the name of this table.
            $table = new xmldb_table($tablename);

            // Skip this module if it has no table.
            // (Only checks if a table with the same name as the module exists)
            if (!$dbman->table_exists($table)) {
                continue;
            }

            // We want to check if these fields exist in the table.
            $possiblefields = array('name', 'intro');

            if ($tablename == 'page') {
                    $possiblefields[] = 'content';
            }

            // Check if each of these fields (columns) exists in the table.
            foreach ($possiblefields as $fieldname) {

                // Create an xmldb object for this field's name.
                $field = new xmldb_field($fieldname);

                // If this field exists in the table, we're going to search in it.
                if ($dbman->field_exists($table, $field)) {
                    $tablefields[] = $fieldname;
                }

            }

            $tables[$tablename] = $tablename . ' (' .implode(', ', $tablefields) . ')';

        } // End foreach module.

        return $tables;
    }

    /**
     * Returns the current time in microseconds.
     * Used for timing how long things take.
     */
    public static function get_debug_time() {

        $timer = explode(' ', microtime());
        $timer = $timer[1] + $timer[0];
        return $timer;
    }

    public static function get_debug_time_taken($starttime) {

        return round((self::get_debug_time() - $starttime), 4);
    }
}
