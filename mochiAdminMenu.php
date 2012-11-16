<?php
/* 
 * Creates and manages the mochi admin menu(s)
 * contructor takes an object as a parameter, object should be of type
 * mochiArcadeAutoPost
 */
class mochiAdminMenu
{
	private $mochiDB;
	private $parent;
	private $last_id;
	const unposted = '0';
	const posted = '1';
	const autoAdded = '2';
	const all =  '50';
	const updated = '150';
	const unknown = '100';
	/*
	 * Manages the admin menu
	 *
	 * @param mochiArcadeAutoPost object
	 */
	public function mochiAdminMenu(&$parent)
	{
		$this->parent = $parent;
		if(is_admin())
		{
			if(!isset($_REQUEST['mochi_list']))
				$_REQUEST['mochi_list'] = 'queued';
			add_action('admin_init', array(&$this, 'init'));
			add_action('admin_menu', array(&$this, 'menus'));
		}
	}
	public function init()
	{

	}
	public function menus()
	{
		add_posts_page('Manage Mochi Games', 'Mochi Games Queue', 'manage_games', 'mochiGamesQueue',array(&$this, 'mochiGamesQueue'));
		add_management_page( 'Mochi Log', 'Mochi Log', 'manage_options', 'mochiArcadeAutoPostLog', array(&$this,'mochiLog') );
	}
	public function mochiLog()
	{
		//generate mochi log here
		$fileName = WP_PLUGIN_DIR.'/mochi-arcade-auto-post/mochiLog.log';
		if(array_key_exists('mochi_action', $_REQUEST))
		{
			if($_REQUEST['mochi_action'] == 'Clear')
			{
				if(check_admin_referer('mochimanage'))
					$this->clearLog ();
				?>
<script type="text/javascript">location.href = window.location.pathname + '?page=mochiArcadeAutoPostLog';</script>
				<?php
			}
		}
		if(is_file($fileName))
		{
			$theLog = file_get_contents($fileName);
		}
		else
		$theLog = '';
		?>
		<script type="text/javascript" src="<?php echo plugin_dir_url(__FILE__);?>js/sorttable.js"></script>
		<link href="<?php echo plugin_dir_url(__FILE__);?>css/tables.css" title="compact" rel="stylesheet" type="text/css">
		<h1>Mochi Arcade Auto Post Error Log</h1><br/>

		<table class="sortable" id="mochiLog" style="text-align:center;">
		<thead>
		<th>Occurred</th>
		<th>Logged event</th>
		<th>Probable cause</th>
		<th>Other information</th>
		</thead>
		<tbody>
		<?php
		echo $theLog;
		?>
		</tbody>
		<tfoot></tfoot>
		</table>
		<form action="tools.php?page=mochiArcadeAutoPostLog" method="post">
		<?php
		wp_nonce_field( 'mochimanage', '_wpnonce', true, true );
		?>
		<input type="submit" name="mochi_action" value="Clear"/>
		</form>
		
		<?php
	}
	public function addLogItem($event,$cause = '',$info = '')
	{
		$output = '';
		$output .= '<tr><td class="mochiNoWrap">';
		$output .= current_time('mysql');
		$output .= '</td><td>';
		$output .= $event;
		$output .= '</td><td>';
		$output .= $cause;
		$output .= '</td><td>';
		$output .= $info;
		$output .= '</td></tr>';
		file_put_contents(WP_PLUGIN_DIR.'/mochi-arcade-auto-post/mochiLog.log',$output,FILE_APPEND);
	}
	public function clearLog()
	{
		file_put_contents(WP_PLUGIN_DIR.'/mochi-arcade-auto-post/mochiLog.log','');
	}
	//generate the mochiGamesQueue page
	public function mochiGamesQueue()
	{
		?>
		<script type="text/javascript" src="<?php echo plugin_dir_url(__FILE__);?>js/postIt.js"></script>
		<?php
		if(!current_user_can('manage_games'))
		{
			//Throw errors if user tries to access who doesn't have the required permissions.
			header('Status: 403 Forbidden');
			header('HTTP/1.1 403 Forbidden');
			exit();
		}
		if(!isset($_REQUEST['game_tag']))
		{
			$this->listGames();
		}
		if(isset($_REQUEST['game_tag']))
		{
			$backToList = false;
			if($_REQUEST['mochi_action'] == 'post and publish')
			{
				if(check_admin_referer('mochimanage'))
					$this->postGame($_REQUEST['game_tag'], true);
				else die('Invalid NONCE');
				$backToList = true;
			}
			if($_REQUEST['mochi_action'] == 'post')
			{
				if(check_admin_referer('mochimanage'))
					$this->postGame($_REQUEST['game_tag'], false);
				else die('Invalid NONCE');
				$backToList = true;
			}
			if($_REQUEST['mochi_action'] == 'refetch/repost')
			{
				if(check_admin_referer('mochimanage'))
				{
					$this->deleteGame($_REQUEST['game_tag']);
					$game = $this->parent->getGame($_REQUEST['game_tag']);
					$this->postGame($_REQUEST['game_tag'], false);
				}
				else die('Invalid NONCE');
				$backToList = true;
			}
			if($_REQUEST['mochi_action'] == 'edit')
			{
				/*global $wpdb;
				$postID = $wpdb->get_var($wpdb->prepare("SELECT post_ID from {$this->parent->mochiDB['table_name']} WHERE game_tag LIKE %s", $this->parent->mochiDB['table_name'], $_REQUEST['game_tag']));
				if(check_admin_referer('mochimanage'))
					echo '<meta http-equiv="refresh" content="0; url=post.php?post='.$postID.'&action=edit&_wpnonce='.wp_create_nonce('edit_post').'/>';
				else die('Invalid NONCE');*/
			}
			if($_REQUEST['mochi_action'] == 'delete')
			{
				if(check_admin_referer('mochimanage'))
					$this->confirmRemove($_REQUEST['game_tag']);
				else die('Invalid NONCE');
			}
			if($_REQUEST['mochi_action'] == 'really-delete')
			{
				if(check_admin_referer('mochimanage'))
					$this->deleteGame($_REQUEST['game_tag']);
				else die('Invalid NONCE');
				$backToList = true;
			}

			if($_REQUEST['mochi_action'] == 'I changed my mind')
			{
				$this->listGames();
			}
			if($backToList)
			{
				
				echo '
						<script type="text/javascript">
							postToHere({mochi_list:\''.$_REQUEST['mochi_list'].'\'});
						</script>';
			}
		}
	}
	public function confirmRemove($game_tag)
	{
		global $wpdb;
		?>
		<h1>REALLY!? <strong>PERMANENTLY</strong> delete <?php echo $wpdb->get_var($wpdb->prepare("SELECT name FROM {$this->parent->mochiDB['table_name']} WHERE game_tag = %s;", $game_tag), 0, 0); ?></h1>
		<div>	
			<form action="edit.php?page=mochiGamesQueue" method="post">
				<?php
				wp_nonce_field( 'mochimanage', '_wpnonce', true, true );
				?>
				<input type="submit" name="mochi_action" value="really-delete"/>
				<input type="submit" name="mochi_action" value="I changed my mind"/>
				<input type="hidden" name="game_tag" value="<?php echo $game_tag;?>"/>
				<input type="hidden" name="mochi_list" value="<?php echo $_REQUEST['mochi_list'];?>"/>
			</form>
		</div>
		<?php
	}
	public function deleteGame($game_tag)
	{
		global $wpdb;
		$postID = $wpdb->get_var($wpdb->prepare("SELECT post_ID from {$this->parent->mochiDB['table_name']} WHERE game_tag = %s;", $game_tag));
		$children = get_children(array('post_parent' => $postID), ARRAY_A);

		if($postID != NULL)
		{
			foreach($children as $id => $child)
			{
				wp_delete_attachment($id, true);
			}
			wp_delete_post($postID, true);
		}
		$wpdb->query($wpdb->prepare("DELETE FROM {$this->parent->mochiDB['table_name']} WHERE game_tag = %s;",  $game_tag));
		$this->listGames();
	}
	public function postGame($game_tag, $publish)
	{
		global $wpdb;

		//fetch game from database
		$game = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$this->parent->mochiDB['table_name']} WHERE game_tag = %s;", $game_tag), ARRAY_A);

		if(isset($game[0]))
		{
			//explode categories string into an array
			$categories = $this->parent->implode_funcs->explode_assoc_r2("=", "\n", 0, $game[0]['categories']);
			$addToTags = $categories;
			if($this->parent->mochiAutoPostOptions->options['primCat'] == 'yes')
			{
				if($game[0]['category'] != '')
				{
					$categories[0] = $game[0]['category'];
				}
				else
				{
					//In case a primary category is not set, use the first category in the array
					//This is not ideal, but a necessary precaution as the data included with games is inconsistent
					$categories = $this->parent->implode_funcs->explode_assoc_r2("=", "\n", 0, $game[0]['categories']);
					$category = $categories[0];
					unset($categories);
					$categories[0] = $category;
				}
			}

			$name = mysql_escape_string($game[0]['name']);
			//explode tags into an array
            $keywords = $this->parent->implode_funcs->explode_assoc_r2("=", "\n", 0, $game[0]['tags']);

            $cats = array();

			//create categories, and push category IDs into an array
            for($x=0;$x<count($categories);$x++)
            {
                $categories[$x] = sanitize_title($categories[$x]);
                $myCategoryID = get_category_by_slug($categories[$x]);
                if($myCategoryID == false)
                {
                    $flashCatID = get_category_by_slug('flash-games');
                    if($flashCatID == false)
                        $flashCatID = wp_create_category('Flash Games');
                    $flashCatID = wp_create_category($categories[$x], $flashCatID->term_id);
                    array_push($cats,$flashCatID);
                }
                else
                    array_push($cats,get_category_by_slug($categories[$x])->term_id);
            }

            $keywd = "";
            $count = count($keywords);
            $i = 0;
			//get tags as a comma delimited list
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
                        $keywd .= $value.",";
                    }
                    $i++;
                }
            }
			//This will add the game "categories" (genres) to the tags
			foreach($addToTags as $arg)
			{
				if(strlen($keywd) == 0)
					$keywd = $arg;
				else
					$keywd .= ','.$arg;
			}
			//create post
			$keywd .= ',mAAPBS';
			$postContent = '<p>[mochigame game_tag='.$game_tag.' noad=false flashscreen=keep]</p>';
			$postContent .= '<p>'.$game[0]['description'].'</p>';
			if($game[0]['description'] != $game[0]['instructions'])
				$postContent .= '<p>'.$game[0]['instructions'].'</p>';
			$tempPostCont = $postContent;
			
			//download thumbnail
			if(isset($_REQUEST['thumbnailSize']))
				$thumbnailSize = $_REQUEST['thumbnailSize'];
			else
				$thumbnailSize = $this->parent->mochiAutoPostOptions->options['thumbSize'];

			if($thumbnailSize == 'large')
			{
				if($game[0]['thumbnail_large_url'] != '')
				{
					$url = $game[0]['thumbnail_large_url'];
				}
				else
				{
					if($game[0]['thumbnail_url'] != '')
						$url = $game[0]['thumbnail_url'];
					//TODO: Set default image if no thumbnail exists
				}
			}
			if($thumbnailSize == 'small')
			{
				if($game[0]['thumbnail_url'] != '')
				{
					$url = $game[0]['thumbnail_url'];
				}
				else
				{
					$url = $game[0]['thumbail_large_url'];
				}
			}
			//setup post array
			if($publish)
			{
				$post = array(
				'post_author' => get_current_user_id(),
				'post_category' => $cats,
				'post_content' => '',
				'post_status' => 'publish',
				'post_title' => $name,
				'post_type' => 'post',
				'tags_input' => $keywd
							);
			}
			else
			{
				$post = array(
				'post_author' => get_current_user_id(),
				'post_category' => $cats,
				'post_content' => '',
				'post_status' => 'pending',
				'post_title' => $name,
				'post_type' => 'post',
				'tags_input' => $keywd
							);
			}
			//insert post
			$post_id = wp_insert_post($post);
			add_action('add_attachment',array(&$this, 'get_attach_id'));
			

			

            $tmp = download_url( $url );
            $file_array = array(
                'name' => $this->_sanitize_title( $game[0]['game_tag'].'_'.basename( $url )),
                'tmp_name' => $tmp
            );

            // Check for download errors
            if ( is_wp_error( $tmp ) )
            {
                @unlink( $file_array[ 'tmp_name' ] );
                return $tmp;
            }
			add_action('add_attachment',array(&$this, 'new_attachment'));
            $id = media_handle_sideload( $file_array, $post_id );
			remove_action('add_attachment',array(&$this, 'new_attachment'));
            // Check for handle sideload errors.
            if ( is_wp_error( $id ) )
            {
                @unlink( $file_array['tmp_name'] );
                return $id;
            }

			//edit post with changes
			//init post id
			$post['ID'] = $post_id;
			//add screenshots to post
			$gameCount = 0;
			while($gameCount < 4)
			{
				$gameCount++;
				if($game[0]['screen'.$gameCount.'_url'] != '')
				{
					$theScreenie = $game[0]['screen'.$gameCount.'_url'];
					$tmp = download_url( $theScreenie );
					$file_array = array(
										'name' => $this->_sanitize_title( $game[0]['game_tag'].'_'.basename( $theScreenie )),
										'tmp_name' => $tmp
										);

					// Check for download errors
					if ( is_wp_error( $tmp ) )
					{
						@unlink( $file_array[ 'tmp_name' ] );
						return $tmp;
					}
					$id = media_handle_sideload( $file_array, $post_id, 'screenshot of '.$game[0]['name']);
				}
			}

			$post['post_content'] = $postContent.'[/mochigame]';
			$post['post_excerpt'] = 'm-DONT CHANGE:'.$game[0]['game_tag'];
			$post_id = wp_insert_post($post);

			$url = $game[0]['swf_url'];
            $tmp = download_url( $url );
            $file_array = array(
                'name' => $this->_sanitize_title( $game[0]['game_tag'].'_'.basename( $url )),
                'tmp_name' => $tmp
            );

            // Check for download errors
            if ( is_wp_error( $tmp ) )
            {
                @unlink( $file_array[ 'tmp_name' ] );
                return $tmp;
            }

            $id = media_handle_sideload( $file_array, $post_id );
            // Check for handle sideload errors.
            if ( is_wp_error( $id ) )
            {
                @unlink( $file_array['tmp_name'] );
                return $id;
            }
			$wpdb->update($this->parent->mochiDB['table_name'], array ( 'swf_attach_id' => $id, 'posted' => 1, 'post_ID' => $post_id), array('game_tag' => $game[0]['game_tag']));
			echo '<h2> Game posted successfully!</h2>';
			$this->listGames();
			return 1;
		}
		echo '<h2>Game tag not found!</h2>';
		$this->listGames();
		return 0;
	}
	public function get_attach_id($att_id)
	{
		$this->last_id = $att_id;
	}
	//removes % characters before sending to sanitize_title
	public function _sanitize_title($string)
	{
		$string = str_replace('%20','-', $string);
		$string = str_replace('*','2A', $string);
		$string = str_replace('@','40', $string);
		$string = str_replace('%', '', $string);
		return $string;
	}
	public function new_attachment($att_id)
	{
		//get post id
		$p = get_post($att_id);
		//set featured image to newly uploaded thumbnail
		update_post_meta($p->post_parent,'_thumbnail_id',$att_id);
	}
	public function listGames()
	{
		global $wpdb;
		$sql = "SELECT * FROM {$this->parent->mochiDB['table_name']}";
		$sqlEnd = " ORDER BY generated DESC LIMIT %d OFFSET %d;";
		$numGames = 100;
		if(isset($_REQUEST['thumbnailSize']))
			$thumbnailSize = $_REQUEST['thumbnailSize'];
		else
			$thumbnailSize = $this->parent->mochiAutoPostOptions->options['thumbSize'];
		?>
		<style type="text/css">

		</style>
		<?php
		$check = $wpdb->get_results($wpdb->prepare("SELECT posted, updateAvailable FROM {$this->parent->mochiDB['table_name']}"), ARRAY_A);
		$checkUnposted = false;
		$checkPosted = false;
		$checkAutoAdded = false;
		$checkUpdated = false;
		for($i = 0;$i < count($check);$i++)
		{
			switch($check[$i]['posted'])
			{
				case self::unposted:
					$checkUnposted = true;
					if(!is_null($check[$i]['updateAvailable']))
					{
						echo 'unposted';
						$checkUpdated = true;
					}
					
					break;
				case self::posted:
					$checkPosted = true;
					if(!is_null($check[$i]['updateAvailable']))
					{
						echo 'posted';
						$checkUpdated = true;
					}
					break;
				case self::autoAdded:
					$checkAutoAdded = true;
					if(!is_null($check[$i]['updateAvailable']))
					{
						echo 'autoAdded';
						$checkUpdated = true;
					}
				break;
			}
		}
		if(isset($_REQUEST['mochi_list']))
		{
			$requested = $_REQUEST['mochi_list'];
		}
		else
		{
			$requested = 'queued';
		}
		$originalRequest = $requested;
		$done = false;
		do
		{
			switch($requested)
			{
				//in all cases, I'm checking to see if games exist, and if not
				//falling back to other options, displaying an empty table should be avoided.
				case 'all':
					$done = true;
					break;
				case 'posted':
					if($checkPosted)
					{
						$sql .= " WHERE posted = ".self::posted;
						$done = true;
					}
					else
					{
						$requested = 'all';
					}
					break;
				case 'queued':
					if($checkUnposted)
					{
						$sql .= " WHERE posted = ".self::unposted;
						$done = true;
					}
					else
					{
						$requested = 'posted';
					}
					break;
				case 'autoAdded':
					if($checkAutoAdded)
					{
						$sql .= " WHERE posted = ".self::autoAdded;
						$done = true;
					}
					else
					{
						$requested = 'posted';
					}

					break;
				case 'updated':
					if($checkUpdated)
					{
						$sql .= " WHERE updateAvailable = 1";
						$done = true;
					}
					else
					{
						$requested = 'all';
					}
					break;
				default: $requested = 'queued';
					break;
			}
		}while(!$done);
		$sql .= $sqlEnd;

		//Check if not showing what user requested
		//The nested functions that seem to cancel each other out are actually
		//ensuring that names of sections match the UI, as various iterations have
		//called things by slightly different names
		if($originalRequest != $requested)
			echo '<strong>No games found in '.$this->postedToString ($this->stringToPosted ($originalRequest)).' games - showing '
									.$this->postedToString ($this->stringToPosted ($requested)).' games instead.</strong>';
		//initialize data from the database

		?>
		<link href="<?php echo plugin_dir_url(__FILE__);?>css/tables.css" title="compact" rel="stylesheet" type="text/css">
		<script type="text/javascript" src="<?php echo plugin_dir_url(__FILE__);?>js/sorttable.js"></script>
		<script type="text/javascript">
		function mochiThumbSize(theSize)
		{
			var elems = document.getElementsByName('thumbnailSize');
			for(var i=0;i<elems.length;i++)
				if(elems[i].type == 'hidden')
					elems[i].value=theSize;
		}
		</script>
		<form name="theForm" action="edit.php?page=mochiGamesQueue" method="post" id="theForm">
			<input type="radio" name="thumbnailSize" value="small"<?php if($thumbnailSize == 'small') echo ' checked';?> onclick="mochiThumbSize('small');"/> Use small thumbnail
		<input type="radio" name="thumbnailSize" value="large"<?php if($thumbnailSize == 'large') echo ' checked';?> onclick="mochiThumbSize('large');"/> Use large thumbnail
		<noscript>
			<br/>
			<input type="submit" value="Change set thumb"/>
		</noscript>
		</form>
		<table class="sortable" id="gameList" cellspacing="0" border="0" >
		<input type="hidden" name="mochi_list" value="<?php echo $_REQUEST['mochi_list'];?>"/>
		<tr>
		<th style="width:10em;">Added</th>
		<th>small thumbnail
		</th><th>large thumbnail
		</th><th>name</th><th>game tag</th><th>screens</th><th>video URL</th><th>author</th><th>status</th><th></th><th></th>
		
		<?php


		//initial rowOffset is 0
		$rowOffset = 0;
		//while the SELECT statement found games (100 per while loop)
		do
		{
			//read data from the database
			$games = $wpdb->get_results($wpdb->prepare($sql, $numGames, $rowOffset), ARRAY_A);
			//initial count of rows processed in this iteration of while($games != NULL) loop
			$rowCount = 0;
			if(array_key_exists($rowCount, $games))
				$game = $games[$rowCount];
			else
				$game = NULL;
			while($game != NULL)
			{

				$game = $games[$rowCount];
				$gameRes = $game['height']/$game['width'];
				$imageHeight = $gameRes * 100;

				//populate table data
				?>
				<tr>


				<td><?php echo $game['generated'];?></td>
				<td>
					<?php
					if($game['thumbnail_url'] != '')
					{
					?>
					<img src="<?php echo $game['thumbnail_url'];?>" alt="splash for <?php echo $game['name'];?>" />
					<?php
					}
					else
					{
						echo 'Small thumbnail missing';
					}
					?>
				</td>
				<td>
					<?php
					if($game['thumbnail_large_url'] != '')
					{
					?>
					<img src="<?php echo $game['thumbnail_large_url'];?>" alt="splash for <?php echo $game['name'];?>" width="100" height="100" /><br/>
					*will render 2x as large
					<?php
					}
					else
					{
						echo 'Large thumbnail<br/> missing';
					}
					?>
				</td>
				<td><?php echo $game['name'];?></td>
				<td><?php echo $game['game_tag'];?></td>
				<td>
					<?php
				if($game['screen1_url'] != NULL)
					{
						?>
						<a href="<?php echo $game['screen1_url']?>">
						<img src="<?php echo $game['screen1_url'];?>" alt="image of <?php echo $game['name'];?>" height="<?php echo $imageHeight;?>" width="100" />
						</a>
						<?php
					}
					else
					{
						?>
						Screen1 missing
						<?php
					}
					?>
					<?php
				if($game['screen2_url'] != NULL)
					{
						?>
						<a href="<?php echo $game['screen2_url']?>">
						<img src="<?php echo $game['screen2_url'];?>" alt="image of <?php echo $game['name'];?>" height="<?php echo $imageHeight;?>" width="100" />
						</a>
						<?php
					}
					else
					{
						?>
						Screen2 missing
						<?php
					}
					?>

						<br/>
				<!--</td>
				<td>-->
				<?php
				if($game['screen3_url'] != NULL)
					{
						?>
						<a href="<?php echo $game['screen3_url']?>">
						<img src="<?php echo $game['screen3_url'];?>" alt="image of <?php echo $game['name'];?>" height="<?php echo $imageHeight;?>" width="100" />
						</a>
						<?php
					}
					else
					{
						?>
						Screen3 missing
						<?php
					}
					?>
						<?php
				if($game['screen4_url'] != NULL)
					{
						?>
						<a href="<?php echo $game['screen4_url']?>">
						<img src="<?php echo $game['screen4_url'];?>" alt="image of <?php echo $game['name'];?>" height="<?php echo $imageHeight;?>" width="100" />
						</a>
						<?php
					}
					else
					{
						?>
						Screen4 missing
						<?php
					}
					?>
				</td>

				<td><?php
				if($game['video_url'] != NULL)
					{
						?>
						<a href="<?php echo $game['video_url']?>">video of <?php echo $game['name']?></a>
						<?php
					}
					else
					{
						?>
						No video available
						<?php
					}
					?>
				</td>
				<td class="mochiNoWrap"><a href="<?php echo $game['author_link'];?>"> <?php echo $game['author'];?></a></td>
				<td class="mochiNoWrap">
				<p<?php echo ' style="'.$this->postedColor($game['posted']).'"'; //set color?>>
				<?php echo $this->postedToString($game['posted']); //print posted status?></p></td>
				<td class="mochiNoWrap"><a href="<?php echo $game['swf_url']?>">play</a></td>
				<!--Action buttons-->
				<td><form name="_<?php echo $game['game_tag'];?>" action="edit.php?page=mochiGamesQueue" method="post">
				<?php
				wp_nonce_field( 'mochimanage', '_wpnonce', true, true );
				?>
				<div id="_<?php echo $game['game_tag'];?>"></div>
				<input type="hidden" name="game_tag" value="<?php echo $game['game_tag'];?>" />
				<input type="hidden" name="mochi_list" value="<?php echo $requested;?>"/>
				<input type="hidden" name="thumbnailSize" value="<?php echo $thumbnailSize?>"/>
				<input type="submit" name="mochi_action" value="delete" /><br/>
					<?php
					if($game['posted'])
					{
						?>
						<input type="submit" name="mochi_action" value="refetch/repost" />
						<?php
						if($game['updateAvailable'])
						{
							?>
							<input type="submit" name="mochi_action" value="update" />
							<?php
						}
					}
					?>
						<br/><br/>
						<?php
				if($game['posted'])
				{
					global $wpdb;
					$postID = $wpdb->get_var($wpdb->prepare("SELECT post_ID from {$this->parent->mochiDB['table_name']} WHERE game_tag = %s", $game['game_tag']));

					?>
				</form>
					<form name="moo" action="post.php?post=<?php echo $postID;?>&action=edit" method="post"><br/>
					<?php
					wp_nonce_field( 'edit', '_wpnonce', true, true );
					?>
						<input type="submit" name="mochi_action" value="<?php echo 'edit';?>"/><br/>
					<?php
				}
				else
				{
					?>
					<input type="submit" name="mochi_action" value="post and publish" /><br/>
					<input type="submit" name="mochi_action" value="post" /><br/>
					<?php
				}
				?>
				</form></td>
				</tr>



				<?php
				//increment rowCount and get next row
				$rowCount++;
				if(array_key_exists($rowCount, $games))
					$game = $games[$rowCount];
				else $game = NULL;
			}
			//assume 100 rows were processed
			$rowOffset += $numGames;
			//fetch next result set
			//The purpose of doing it this way is to support a virtually limitless number of games, if I fetched them all at once, typical shared hosting would
			//eventually shut the script down for over-stepping its memory allocation.  Assuming there was thousands of games.
		}while(!empty($games))
		//TODO: Search games.
		//TODO: Paginate results.
		?>
				</table>

		<div>
			<!--navigation-->
			Show which games?
		<form action="edit.php?page=mochiGamesQueue" method="post" name="navigation">
			<select name="mochi_list" onchange="document.forms['navigation'].submit();">
				<option value="all" <?php if($requested == 'all')echo 'selected';?>>All Games</option>
				<option value="queued" <?php if($requested == 'queued')echo 'selected';?>>Queued Games</option>
				<option value="posted" <?php if($requested == 'posted')echo 'selected';?>>Posted Games</option>
				<option value="autoAdded" <?php if($requested == 'autoAdded')echo 'selected';?>>Suggested/found</option>
				<option value="updated" <?php if($requested == 'updated')echo 'selected';?>>Waiting updates</option>
			</select>
			<noscript>
			<input type="submit" value="Go!">
			</noscript>
		</form>
		</div>
		<?php
	}
	//takes a posted status of a game, outputs 'color:<a color>' (with no angle brackets around the color name) for use in styles
	public function postedColor($input)
	{
		switch($input)
		{
			case self::unposted:
			case 'unposted':
			case 'queued':
				$output = 'color:Crimson';
				break;
			case self::posted:
			case 'posted':
				$output = 'color:Chartreuse';
				break;
			case self::autoAdded:
			case 'suggested':
			case 'autoAdded':
				$output = 'color:GoldenRod';
				break;
			default:
				$output = 'color:Turquoise';
				break;
		}
		return $output;
	}
	public function postedToString($input)
	{
		switch($input)
		{
			case self::unposted:
				$output = 'queued';
				break;
			case self::posted:
				$output = 'posted';
				break;
			case self::autoAdded:
				$output = 'suggested';
				break;
			case self::all:
				$output = 'all';
				break;
			case self::updated:
				$output = 'updated';
				break;
			default:
				$output = 'unknown';
				break;
		}
		return $output;
	}
	public function stringToPosted($input)
	{
		switch((string)$input)
		{
			case 'unposted':
			case 'queued':
			case self::unposted:
				$output = self::unposted;
				break;
			case 'suggested':
			case 'autoAdded':
			case self::autoAdded:
				$output = self::autoAdded;
				break;
			case 'posted':
			case self::posted:
				$output = self::posted;
				break;
			//case of the unkown posted type, this should never happen, but what if it does?
			case 'updated':
			case self::updated:
				$output = self::updated;
				break;
			case 'all':
			case self::all;
				$output = self::all;
				break;
			default:
				$output = self::unknown;
				break;
		}
		return $output;
	}
}
?>
