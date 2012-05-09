<?php
/*
Plugin Name: Mochi Arcade Auto Post
Plugin URI: http://www.bionicsquirrels.com/mochi-arcade-auto-post/
Description: This plugin is for Mochi publishers, it allows you to use the "post game to your site" button with wordpress.
Version: 1.0.2
Author: Daniel Billings
Author URI: http://www.bionicsquirrels.com
License: GPLv2
 *
 *
Copyright 2012  Daniel Billings Jr  (email : felps@bionicsquirrels.com)

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License, version 2, as
published by the Free Software Foundation.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

//mAAOptions.php contains the class that creates the options page
//it will also provide a simple, efficient way to access the values of those options
require_once (dirname(__FILE__).'/mAAPOptions.php');
require_once (dirname(__FILE__).'/nested_implode_R2.php');
require_once (dirname(__FILE__).'/mochiAdminMenu.php');
require_once (dirname(__FILE__).'/mochiShortCodes.php');
new mochiArcadeAutoPost;
/*
 * This class will contain the main functions of the Mochi Arcade Auto Post
 * plugin.
 */
class mochiArcadeAutoPost
{
	public $mochiAutoPostOptions;	//variable to store options class
	public $pluginName;			//the name of this plugin
	public $mochiDB;		//stores current database information
	public $implode_funcs;
	/*
	 * mochiArcadeAutoPost constructor
	 * detects how the plugin was accessed,
	 * sets up wordpress if available.
	 * requests a wordpress aware page if not.
	 * Throws 403 if accessed without wordpress AND without game_tag
	 */
    public function mochiArcadeAutoPost() 
	{
		if(function_exists('add_action'))
		{
			global $wpdb;
			//set plugin name
			$this->pluginName = 'mochiArcadeAutoPost';
			//set database version
			$this->mochiDB = get_option('mochiDB');

			$this->implode_funcs = new nested_implode_R2();
			//register activation and deactivation hooks to create and remove database entries
			register_activation_hook(__FILE__,array(&$this, 'databaseInstall'));
			//create class instance of options class
			$this->mochiAutoPostOptions = new mAAPOptions($this->pluginName);
			//filter to add game_tag to accepted query vars
			add_filter('query_vars',array(&$this, 'initQuery'));
			//action to add a game to the queue
			new mochiShortCodes($this);
			if(!is_admin())
				add_action('pre_get_posts', array(&$this, 'runPlugin'), 0);
			else
				new mochiAdminMenu($this);


			//load_plugin_textdomain($this->pluginName, false, basename( dirname( __FILE__ ) ) . '/languages' );
		}
		else
		{
			//if plugin was accessed directly with a game_tag, it was probably mochi
			//but that's not too secure, so lets find out if it was mochi
			if(isset($_REQUEST['game_tag']))
			{
				//get the host name
				//prefer SERVER_NAME over HOST_NAME
				//HOST_NAME is always set by client
				//SERVER_NAME is only set by client under special circumstances IE: more secure
				//Not sure what they could possibly do with this script, but better safe than sorry
				if(isset ($GLOBALS['HTTP_SERVER_VARS']['SERVER_NAME']))
					$hostname = $GLOBALS['HTTP_SERVER_VARS']['SERVER_NAME'];
				else
					$hostname = $GLOBALS['HTTP_SERVER_VARS']['HOST_NAME'];

				//This is basically a hack to make mochi and wordpress play nice
				$uriRedirect = $GLOBALS['HTTP_SERVER_VARS']['REQUEST_URI']; //get plugin url
				$uriRedirect = dirname($uriRedirect); //drop /mochiArcadeAutoPost.php
				$uriRedirect = dirname($uriRedirect); //drop /mochiArcadeAutoPost
				$uriRedirect = dirname($uriRedirect); //drop /plugins
				$uriRedirect = dirname($uriRedirect); //drop /wp-content
				//what we're left with is the base URI of your wordpress installation
				//If you've modified the directory structure of your wordpress plugins
				//you hopefully can figure out how to modify this too. :P
				//add /?game_tag=theGameTag to the end of that URI
				$uriRedirect .= '/?game_tag=' . $_REQUEST['game_tag'];
				$uriRedirect .= '&maappw=' . $_REQUEST['maappw'];

				//access the completed URI
				if($GLOBALS['HTTP_SERVER_VARS']['https'])
					echo file_get_contents('https://'.$hostname.$uriRedirect);
				else
					echo file_get_contents('http://'.$hostname.$uriRedirect);
				//mochi thinks it's accessing the plugin page directly
				//I still have access to wordpress functions
				//mochi variables don't get stripped by wordpress
				//everyone is happy.
				exit();
			}
			else
			{
				//if plugin was accessed without a game tag
				header('Status: 403 Forbidden');
				header('HTTP/1.1 403 Forbidden');
				exit();
			}
		}
	}
	/*
	 * Get a visitor's IP address
	 *
	 * @return string containing the IP
	 */
	public function VisitorIP()
    {
		if(isset($GLOBALS['HTTP_SERVER_VARS']['HTTP_X_FORWARDED_FOR']))
			$TheIp=$GLOBALS['HTTP_SERVER_VARS']['HTTP_X_FORWARDED_FOR'];
		else
			$TheIp=$GLOBALS['HTTP_SERVER_VARS']['REMOTE_ADDR'];

		return trim($TheIp);
	}
	/*
	 * Add game_tag to list of wordpress recognized query variables
	 *
	 * @param $queryVars - an array containing wordpress' valid queryVars
	 * @return An array containing wordpress' valid queryVars, plus 'game_tag'
	 */
	public function initQuery($queryVars)
	{
		$queryVars[] = 'game_tag'; //do action on game tag
		$queryVars[] = 'mochi_action'; //do this action
		$queryVars[] = 'mochi_list';  //used by listGames() to determine which games to list
		$queryVars[] = 'mochi_nonce'; //used on admin page for nonce value
		$queryVars[] = 'maappw';
		return $queryVars;
	}
	/*
	 * What I consider the plugin's main function, requests game info from mochi
	 * and adds games to the wordpress database in a new table (created by this plugin)
	 */
	public function runPlugin()
	{
		//gets the game tag from the query
		$gameTag = get_query_var('game_tag');
		$maappw = get_query_var('maappw');
		//checks if game_tag was set
		if($gameTag != '' && $maappw == $this->mochiAutoPostOptions->options['maappw'])
		{
			$urlrequest = 'http://www.mochiads.com/feeds/games/'.$this->mochiAutoPostOptions->options['publisher_id'].'/'.$gameTag.'/?format=json';
			$gamearr = file_get_contents($urlrequest);
			//$game['gamejson'] = $gamearr;
			$gamearr = json_decode($gamearr, true);
			$game = $gamearr['games'][0];
			$game['generated'] = $gamearr['generated'];
			$game['posted'] = 0;

			//the following are sent as arrays, and need to be imploded into strings
			$game['tags'] = $this->implode_funcs->implode_assoc_r2("=","\n",0,$game['tags']);
			$game['controls'] = $this->implode_funcs->implode_assoc_r2("=","\n",0,$game['controls']);
			$game['languages'] = $this->implode_funcs->implode_assoc_r2("=","\n",0,$game['languages']);
			$game['categories'] = $this->implode_funcs->implode_assoc_r2("=","\n",0,$game['categories']);

			$this->addToDB($game);
			//TODO: Add mochi publisher secret key to options page
			//TODO: Store HIGHSCORES in user metadata
			//TODO: Use mochi API to create a system that suggests games to upload based on tags
			//TODO: Create an arcade tag to add to pages/posts that will act as a games browser
			//TODO: Create a cron that checks for game updates.
			//TODO: Create a button that appears when a game needs to be updated, to redownload that game, as well as its data
			//TODO: Create a system to rate games (thumbs up/thumbs down)
			//TODO: Add an option to make games only belong to one category (as is games may belong to multiple categories, always with the parent category flash-games)
			exit();
		}
	}
	/*
	 * This is called to add the game to the database, it checks if the game
	 * was already there, if it was checks to see if the new data was updated
	 * if the data is newer, set updateavailable flag
	 */
	public function addToDB($game)
	{
		global $wpdb;
		//parse $game so it matches database structure
		if(isset($game))
		{
			foreach ($game as &$key)
			{
				if (!isset($key))
				{
					$key = '';
				}
			}
			$currentGame = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$this->mochiDB['table_name']} WHERE game_tag = %s", $game[game_tag]));
			//check if game exists in database, also check if mochi returned a game
			if($currentGame['game_tag'] == NULL && $game['game_tag'] != '')
				$wpdb->insert($this->mochiDB['table_name'], $game);
			else if($currentGame->updated < $game['updated'] && $game['game_tag'] != '')
			{
				$game['updateAvailable'] = 1;
				$wpdb->update($this->mochiDB['table_name'], array ( 'update available' => 1), array('game_tag' => $game['game_tag']));
			}
		}
		$keywd = "";
		$count = count($keywords);
		$i = 0;
		if ($count)
		{
			foreach($keywords as $value)
			{
				if($count-1 == $i)
				{
					$keywd .= $value;
				}
				else
				{
					$keywd .= $value.", ";
				}
				$i++;
			}
		}
	}
	public function databaseInstall()
	{
		global $wpdb;
		global $wp_roles;

		$wp_roles->add_cap('administrator', 'manage_games');
		require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
		add_option('mochiDB', array('DB_version' => '1.0', 'table_name' => $wpdb->prefix.$this->pluginName));
		$this->mochiDB = get_option('mochiDB');


		$sql = "CREATE TABLE IF NOT EXISTS {$this->mochiDB['table_name']} (
				id BIGINT(20) NOT NULL AUTO_INCREMENT,
				generated DATETIME DEFAULT '0000-00-00 00:00:00',
				updateAvailable TINYINT,
				game_tag VARCHAR(16),
				uuid TINYTEXT NOT NULL,
				posted TINYINT NOT NULL,
				post_ID BIGINT(20),
				rating TINYTEXT,
				screen2_thumb TEXT,
				popularity TINYTEXT,
				video_url TEXT,
				key_mappings TEXT,
				screen1_thumb TINYTEXT,
				metascore TINYINT UNSIGNED,
				height SMALLINT UNSIGNED,
				screen3_url TEXT,
				recommendation TEXT,
				alternate_url TEXT,
				category TINYTEXT,
				screen4_thumb TINYTEXT,
				author TINYTEXT,
				coins_revshare_enabled TINYINT,
				thumbnail_large_url TEXT,
				tags TEXT,
				controls TEXT,
				languages TEXT,
				width SMALLINT UNSIGNED,
				recommended TINYINT,
				achievements_enabled TINYINT,
				zip_url TEXT,
				screen1_url TEXT,
				updated DATETIME DEFAULT '0000-00-00 00:00:00',
				description TEXT,
				author_link TEXT,
				SWF_FILE_SIZE INT UNSIGNED,
				game_url TEXT,
				screen2_url TEXT,
				slug TINYTEXT,
				categories TEXT,
				instructions TEXT,
				swf_url TEXT,
				name TEXT,
				screen3_thumb TEXT,
				control_scheme TEXT,
				created DATETIME DEFAULT '0000-00-00 00:00:00',
				feed_approval_created DATETIME DEFAULT '0000-00-00 00:00:00',
				coins_enabled TINYINT,
				thumbnail_url TEXT,
				screen4_url TEXT,
				leaderboard_enabled TINYINT,
				resolution TINYTEXT,
				swf_attach_id BIGINT,
				PRIMARY KEY id (id),
				UNIQUE KEY game_tag(game_tag)
				);";

		dbDelta($sql);
	}
	public function databaseUninstall()
	{

	}
	
	
}

?>
