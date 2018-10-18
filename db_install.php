<?php
$SSI_INSTALL = false;
if (file_exists(dirname(__FILE__) . '/SSI.php') && !defined('SMF'))
{
	$SSI_INSTALL = true;
	require_once(dirname(__FILE__) . '/SSI.php');
}
elseif (!defined('SMF')) // If we are outside SMF and can't find SSI.php, then throw an error
	die('<b>Error:</b> Cannot install - please verify you put this file in the same place as SMF\'s SSI.php.');
require($sourcedir.'/Subs-Admin.php');
db_extend('packages');

// Build the Hide Topic table:
$columns = array(
	array(
		'name' => 'id',
		'type' => 'int',
		'size' => 8,
		'unsigned' => false,
		'auto' => true,
	),
	array(
		'name' => 'id_member',
		'type' => 'int',
		'size' => 8,
		'unsigned' => false,
	),
	array(
		'name' => 'id_topic',
		'type' => 'int',
		'size' => 8,
		'unsigned' => false,
	),
	array(
		'name' => 'id_board',
		'type' => 'int',
		'size' => 8,
		'unsigned' => false,
	),
);
$indexes = array(
	array(
		'type' => 'primary',
		'columns' => array('id')
	),
);
$smcFunc['db_create_table']('{db_prefix}hide_topics', $columns, $indexes, array(), 'update_remove');

// Echo that we are done if necessary:
if ($SSI_INSTALL)
	echo 'DB Changes should be made now...';
?>