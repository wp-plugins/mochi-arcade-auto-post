<?php
/* 
 * mochiAutoPost uninstall
 */
//require_once ((__DIR__).'mAAPOptions.php');
if(function_exists('add_action') && defined('WP_UNINSTALL_PLUGIN') && defined('ABSPATH'))
{
	global $wpdb;
	global $wp_roles;
	$mochiDB = get_option('mochiDB');
	//$plugin_options = new mAAPOptions();
	$sql = "DROP TABLE IF EXISTS $mochiDB[table_name]";
	$wpdb->query($wpdb->prepare($sql));
	delete_option('mochiDB');
	delete_option('mochiArcadeAutoPostOptions');
	foreach (array_keys($wp_roles->roles) as $role)
	{
		$wp_roles->remove_cap($role, 'manage_games');
	}
	//TODO: Also delete posts and attachments created by the plugin (may not be possible in the uninstall script, might be a button on games queue page)
}
else
{
	header('Status: 403 Forbidden');
	header('HTTP/1.1 403 Forbidden');
	exit();
}
?>