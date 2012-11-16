<?php
/*
Plugin Name: Mochi Arcade Auto Post
Plugin URI: http://www.bionicsquirrels.com/mochi-arcade-auto-post/
Description: This plugin is for Mochi publishers, it allows you to use the "post game to your site" button with wordpress.
Version: 1.1.45
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
	public $theMochiAdminMenu;
	public $pluginName;			//the name of this plugin
	public $mochiDB;		//stores current database information
	public $implode_funcs;
    public $mochiGameTag;
	public $divAdded;		//bool that stores wether or not a div was already added
	public $currentPost;
	public $maappw;
	/*
	 * mochiArcadeAutoPost constructor
	 * detects how the plugin was accessed,
	 * sets up wordpress if available.
	 * requests a wordpress aware page if not.
	 * Throws 403 if accessed without wordpress AND without game_tag
	 */
    public function mochiArcadeAutoPost() 
	{
		if(isset($_REQUEST['game_tag']))
			$this->mochiGameTag = $_REQUEST['game_tag'];
		else
			$this->mochiGameTag = '';
		if(isset($_REQUEST['maappw']))
			$this->maappw = $_REQUEST['maappw'];
		else
			$this->maappw = '';
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
			$this->mochiAutoPostOptions = new mAAPOptions($this->pluginName, $this);
			//filter to add game_tag to accepted query vars
			add_filter('query_vars',array(&$this, 'initQuery'));
			//action to add a game to the queue
			new mochiShortCodes($this);
			if(!is_admin())
			{
				//add_action('init', array(&$this, 'fetchData'), 0);
				add_action('pre_get_posts', array(&$this, 'runPlugin'), 0);
			}
			else
			{
				add_action('wp_loaded', array(&$this, 'runUpdate'), 0);
			}
			$this->theMochiAdminMenu = new mochiAdminMenu($this);

			if($this->mochiAutoPostOptions->options['gamesOnHomePage'] == 'no')
					add_filter('pre_get_posts', array(&$this, 'hideGames'));
			add_filter('the_title', array(&$this, 'addDivTitle'));
			add_action('template_redirect', array(&$this, 'retrievePostID'));

			//load_plugin_textdomain($this->pluginName, false, basename( dirname( __FILE__ ) ) . '/languages' );
		}
		else
		{
			//Left in for compatibility with old settings
			//This will run if the plugin page was accessed directly
			//New method works on any wordpress page
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
	public function retrievePostID()
	{
		global $wp_query;
		$this->currentPost = $wp_query->get_queried_object_id();
	}
	public function addDivTitle($title, $post_id = NULL)
	{
		$title1 = $title;
			
		if(is_single())
		{
			if($post_id === NULL)
			{
				$post_id = $GLOBALS['post']->ID;
			}
			if (in_the_loop() && is_main_query() && !$this->divAdded)
			{
				if($post_id === NULL)
				{
					$post_id = get_the_ID();
				}
				if ($post_id === $this->currentPost)
				if($this->mochiAutoPostOptions->options['thumbnailTitle'] != 'off')
				{
					$title1 = '<div id="mochiTitle" style="width:';
					//reserve some space on the dom
					if($this->mochiAutoPostOptions->options['thumbnailTitle'] == 'large')
						$title1 .= '200px;height:200px;"';
					else
						$title1 .= '100px;height:100px;"';
					$title1 .= '><!-- image div --></div>'.$title;
					$this->divAdded = true;
				}
			}
		}
		return $title1;
	}
	public function fetchData()
	{
		if(isset($_REQUEST['game_tag']))
			$this->mochiGameTag = $_REQUEST['game_tag'];
		else
			$this->mochiGameTag = '';
		if(isset($_REQUEST['maappw']))
			$this->maappw = $_REQUEST['maappw'];
		else
			$this->maappw = '';
	}
	public function updateDB()
	{
		global $wpdb;
		if(strcmp($this->mochiDB['DB_version'], "1.2.0") < 0 && current_user_can('manage_games'))
		{
			$sql1 = "ALTER TABLE {$this->mochiDB['table_name']} ADD stage3d TINYINT;";
			$sql2 = "ALTER TABLE {$this->mochiDB['table_name']} ADD additional_data TEXT;";
			update_option('mochiDB', array('DB_version' => '1.2.0', 'table_name' => $wpdb->prefix.$this->pluginName));
			try
			{
			//Yes, one query would be more efficient, but some users have likely already worked around this bug
			//and as a courtesy to them I'm making TWO sql statements, if sql1 throws an exception because the 
			//table already exists, sql2 will still go through, and properly update the DB to the latest version
			//Apparently wpdb handles those sql exceptions, so the try/catch is pretty useless... Gonna leave it in anyway just in case
				$wpdb->query($sql2);
				$wpdb->query($sql1);
			}
			catch(Exception $e)
			{
				//Add exception to mochi log
				$event = $e->getMessage();
				$info = $e->__toString();
				$cause = 'Most likely you added the "stage3d" column already so that the plugin worked, if that\'s the case you can safely ignore this error';
				$this->theMochiAdminMenu->addLogItem($event, $cause, $info);
			}
		}
	}
	public function runUpdate()
	{
		
		
		if(strcmp($this->mochiDB['DB_version'], "1.1.0") < 0 && current_user_can('manage_games'))
		{
			global $wpdb;
			global $wp_query;
			//get all game posts
			//modify game posts to fit the new format
			$args = array('slug' => 'maapbs');
			$tag = get_tags($args);

			if(!empty($tag))
				$posts = $wpdb->get_results($wpdb->prepare('SELECT * FROM '.$wpdb->posts.' INNER JOIN '.
					$wpdb->term_relationships.' ON '.$wpdb->posts.'.ID='.$wpdb->term_relationships.'.object_id
					WHERE '.$wpdb->term_relationships.'.term_taxonomy_id = %d;', $tag[0]->term_taxonomy_id), ARRAY_A);
			foreach ($posts as $post)
			{
				$temp = $post['post_content'];
				$post['post_content'] = strip_tags($temp, '<p><br><strong><em><i><b>');
				$post['post_content'] .= '[/mochigame]';
				$temp2 = str_replace('game_tag=', '', $temp);
				$pos = strpos($temp, 'game_tag=');
				$temp2 = substr($temp, $pos+9, 16);
				$post['post_excerpt'] = 'm-DONT CHANGE:'.$temp2;
				wp_update_post($post);
			}
			update_option('mochiDB', array('DB_version' => '1.1.0', 'table_name' => $wpdb->prefix.$this->pluginName));
		}
		$this->updateDB();
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
		$queryVars[] = 'thumbnailSize';
		return $queryVars;
	}
	/*
	 * Adds a parameter to wordpress' home posts query, removing game posts from the home page
	 * preventing clutter (may be turned on and off in options)
	 */
	public function hideGames($query)
	{
		$tag = get_term_by('slug', 'maapbs', 'post_tag');
		//Note: Games will still show in recent posts/comments widgets, there are more advanced widgets available in the plugin repository that could be set to filter out the mAAPBS tag, or flash games category.  The mAAPBS tag is added to every post created by the plugin, so it's a tad more "targeted" than the flash games category, you know, should you happen to write an article on a flash game...
		if($query->is_home && 
			($this->mochiAutoPostOptions->options['hideGamesOnHomeWidgets'] == 'on' || $query->is_main_query()))
			array_push($query->query_vars['tag__not_in'], $tag->term_id);
		//wp_reset_query();
//		if (is_front_page())
//			query_posts('tag__not_in=mAAPBS');
	}
	//This function will fetch known games from the database
	//and also add unknown games to the database.
	//This is a much smoother way to degrade when a game_tag that isn't
	//in the database is found, particularly if it is a valid game_tag.
	//@params $game_tag a valid game_tag
	//@return an associative array of a game's database entry.
	public function getGame($game_tag, $type = mochiAdminMenu::autoAdded)
	{
		global $wpdb;
		$game = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$this->mochiDB['table_name']} WHERE game_tag = %s", $game_tag), ARRAY_A);

		if($game['game_tag'] != '')
		{
			return $game;
		}
		else
		{
			
			$urlrequest = 'http://www.mochiads.com/feeds/games/'.$this->mochiAutoPostOptions->options['publisher_id'].'/'.$game_tag.'/?format=json';
			$gamearr = file_get_contents($urlrequest);
			//$game['gamejson'] = $gamearr;
			$gamearr = json_decode($gamearr, true);
			if(!empty($gamearr['games']))
			{
				$game = $gamearr['games'][0];
				$game['generated'] = current_time('mysql');

				//the following are sent as arrays, and need to be imploded into strings
				$game['tags'] = $this->implode_funcs->implode_assoc_r2("=","\n",0,$game['tags']);
				$game['controls'] = $this->implode_funcs->implode_assoc_r2("=","\n",0,$game['controls']);
				$game['languages'] = $this->implode_funcs->implode_assoc_r2("=","\n",0,$game['languages']);
				$game['categories'] = $this->implode_funcs->implode_assoc_r2("=","\n",0,$game['categories']);
				$game['posted'] = $this->theMochiAdminMenu->stringToPosted($type);
				$this->addToDB($game, $type);
			}
			else
			{
				$event = 'Empty games array returned by mochi';
				if(strlen($game_tag) != 16)
				{
					$cause = 'The plugin encountered an <strong>invalid game tag</strong>';
					$info = 'game_tag:'.htmlspecialchars($game_tag, ENT_QUOTES);
				}
				else
				{
					$urlrequest = 'http://www.mochimedia.com/feeds/games/'.$this->mochiAutoPostOptions->options['publisher_id'].'?format=json&limit=1';
					$gamearr = file_get_contents($urlrequest);
					$gamearr = json_decode($gamearr);
					if(empty($gamearr->games))
					{
						$cause = '<strong>Invalid publisher ID</strong> on options page';
						$info = 'publisher ID:'.htmlspecialchars($this->mochiAutoPostOptions->options['publisher_id'], ENT_QUOTES);
					}
					else
					{
						$cause = 'The plugin encountered an <strong>invalid game tag</strong>';
						$info = 'game_tag:'.htmlspecialchars($game_tag, ENT_QUOTES);
						
					}
				}
				$game = null;
				$this->theMochiAdminMenu->addLogItem($event,$cause,$info);
			}
		}
		return $game;
	}
	/*
	 * What I consider the plugin's main function, requests game info from mochi
	 * and adds games to the wordpress database in a new table (created by this plugin)
	 */
	public function runPlugin($query)
	{
		//gets the game tag from the query
		$game_tag = $this->mochiGameTag;
		
		$maappw = $this->maappw;
		//checks if game_tag was set
		if($game_tag != '' && $maappw == $this->theMochiAdminMenu->_sanitize_title(rawurlencode($this->mochiAutoPostOptions->options['maappw'])))
		{
			$this->getGame($game_tag, mochiAdminMenu::unposted);
			//TODO: Add mochi publisher secret key to options page
			//TODO: Store HIGHSCORES in user metadata
			//TODO: Use mochi API to create a system that suggests games to upload based on tags
			//TODO: Create an arcade tag to add to pages/posts that will act as a games browser
			//TODO: Create a cron that checks for game updates.
			//TODO: Create a button that appears when a game needs to be updated, to redownload that game, as well as its data
			//TODO: Create a system to rate games (thumbs up/thumbs down)
			exit();
		}
		else
		{
			if($maappw != $this->mochiAutoPostOptions->options['maappw'] && $game_tag != '')
			{
				$event = 'Attempt to add a game with an invalid password';
				$cause = 'maappw set incorrectly on the auto post url in your mochi publisher settings (it should be the password in your mochi arcade auto post options page) <br/><strong>OR</strong><br/> someone unauthorized is attempting to post games to your site';
				$info = 'password given:'.htmlspecialchars($maappw, ENT_QUOTES);;
				$this->theMochiAdminMenu->addLogItem($event,$cause,$info);
				header('Status: 403 Forbidden');
				header('HTTP/1.1 403 Forbidden');
				exit();
			}
			if($game_tag == '')
			{

			}
		}
		return $query;
	}
	/*
	 * This is called to add the game to the database, it checks if the game
	 * was already there, if it was checks to see if the new data was updated
	 * if the data is newer, set updateavailable flag
	 */
	public function addToDB($game, $type = mochiAdminMenu::autoAdded)
	{
		global $wpdb;
		$temp = array();
		$currentGame = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$this->mochiDB['table_name']} WHERE game_tag = %s", $game['game_tag']));
		$columnNames = $wpdb->get_col("select column_name from information_schema.columns where table_name='{$this->mochiDB['table_name']}';");
		$columnNames = array_fill_keys($columnNames, NULL);
		//check that all columns, and add a column if necessary
		foreach($game as $columnName => $column)
		{
			//check for unknown keys, or if mochi suddenly add a key called 'additional_data' check for that too
			if(!array_key_exists(strtolower($columnName), array_change_key_case($columnNames)) || $columnName == 'additional_data')
			{
				//split off unknown keys
				$temp[$columnName] = $game[$columnName];
				unset($game[$columnName]);
			}
			if(count($temp) > 0)
			{
				//add unknown keys to known key "additional data"
				$game['additional_data'] = json_encode($temp);
			}
		}
		//check if game exists in database, also check if mochi returned a game
		if($currentGame['game_tag'] == NULL)
		{
			if($game['game_tag'] != '')
			{
				$wpdb->insert($this->mochiDB['table_name'], $game);
			}
		}
		else
		{
			if($currentGame->updated < $game['updated'] && $game['game_tag'] != '')
			{
				$game['updateAvailable'] = 1;
				$wpdb->update($this->mochiDB['table_name'], array ( 'update available' => 1), array('game_tag' => $game['game_tag']));
			}
		}
		return $game;
	}
	public function databaseInstall()
	{
		global $wpdb;
		global $wp_roles;

		$wp_roles->add_cap('administrator', 'manage_games');
		require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
		add_option('mochiDB', array('DB_version' => '1.1.0', 'table_name' => $wpdb->prefix.$this->pluginName));
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
