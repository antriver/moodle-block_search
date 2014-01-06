<?php

require_once '../../config.php';

echo '<pre>';

$q = $_GET['q'];

$search = array(
	'course_categories' => array('name'),
	'course' => array('fullname', 'shortname'),
);

$dbman = $DB->get_manager();

//Get all modules and we're going to search their tables
$modules = $DB->get_records('modules', array(), 'name');
foreach ($modules as $module) {

	$table = new xmldb_table($module->name);

	$fields = array('name', 'intro');

	if ($dbman->table_exists($table)) {

		foreach ($fields as $fieldname) {

			$field = new xmldb_field($fieldname);

			if ($dbman->field_exists($table, $field)) {

				$search[$module->name][] = $fieldname;
			}
		}
	}
}

print_r($search);

$results = array();
$q = strtolower($q);
$q = "%{$q}%";

foreach ($search as $table => $cols) {

	$values = array();
	$where = '';
	foreach ($cols as $col) {
		$where .= ' OR LOWER(' . $col . ') SIMILAR TO ?';
		$values[] = $q;
	}
	$where = ltrim($where, ' OR ');

	$sql = 'SELECT * FROM {' . $table . '} WHERE ' . $where;

	echo "\n" . $sql;

	//Do the search
	$results[$table] = $DB->get_records_sql($sql, $values);
}

print_r($results);
