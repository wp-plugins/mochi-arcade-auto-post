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
	/*
	 * Manages the admin menu
	 *
	 * @param mochiArcadeAutoPost object
	 */
	public function mochiAdminMenu(&$parent)
	{
		$this->parent = $parent;
		if(!isset($_REQUEST['mochi_list']))
			$_REQUEST['mochi_list'] = 'unposted';
		add_action('admin_init', array(&$this, 'init'));
		add_action('admin_menu', array(&$this, 'menus'));
	}
	public function init()
	{
		global $WP_roles;
		
//		global $wp_roles;
//		$all_roles = $wp_roles->roles;
//		$editable_roles = apply_filters('editable_roles', $all_roles);
//		foreach($editable_roles as $key => $value)
//		{
//			//wee mis-spelling to get unique variables
//			$roll = get_role($key);
//			$roll->add_cap('manage_games');
//		}
	}
	public function menus()
	{
		add_posts_page('Manage Mochi Games', 'Mochi Games Queue', 'manage_games', 'mochiGamesQueue',array(&$this, 'mochiGamesQueue'));
	}
	//generate the mochiGamesQueue page
	public function mochiGamesQueue()
	{
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
			if($_REQUEST['mochi_action'] == 'post and publish')
			{
				if(check_admin_referer('mochimanage'))
					$this->postGame($_REQUEST['game_tag'], true);
				else die('Invalid NONCE');
			}
			if($_REQUEST['mochi_action'] == 'post')
			{
				if(check_admin_referer('mochimanage'))
					$this->postGame($_REQUEST['game_tag'], false);
				else die('Invalid NONCE');
			}
			if($_REQUEST['mochi_action'] == 'repost')
			{
				if(check_admin_referer('mochimanage'))
					$this->postGame($_REQUEST['game_tag'], false);
				else die('Invalid NONCE');
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
			}

			if($_REQUEST['mochi_action'] == 'I changed my mind')
			{
				$this->listGames();
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
			//create post
			$keywd .= ',mAAPBS';
			$postContent = '<p>[mochigame game_tag='.$game_tag.']</p>';
			$postContent .= '<p>'.$game[0]['description'].'</p>';
			if($game[0]['description'] != $game[0]['instructions'])
				$postContent .= '<p>'.$game[0]['instructions'].'</p>';
			//setup post array
			if($publish)
			{
				$post = array(
				'post_author' => get_current_user_id(),
				'post_category' => $cats,
				'post_content' => $postContent,
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
				'post_content' => $postContent,
				'post_status' => 'pending',
				'post_title' => $name,
				'post_type' => 'post',
				'tags_input' => $keywd
							);
			}
			//insert post
			$post_id = wp_insert_post($post);

			//download thumbnail
			$url = $game[0]['thumbnail_url'];
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
	//removes % characters before sending to sanitize_title
	public function _sanitize_title($string)
	{
		$string = str_replace('%20','-', $string);
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
		define('numGames', 100);
		if(isset($_REQUEST['mochi_list']))
		{
			$requested = $_REQUEST['mochi_list'];
			switch($requested)
			{
				case 'all': $requested = 'all';
					break;
				case 'posted': $requested = '1';
					break;
				case 'unposted': $requested = '0';
					break;
				default: $requested = '0';
					break;
			}
		}
		else $requested = '0';
		?>
		<table class="wp-list-table widefat fixed posts" cellspacing="0">
		<tr><th>main image</th><th>name</th><th>game tag</th><th>screen1</th><th>screen2</th><th>screen3</th><th>screen4</th><th>video URL</th><th>author</th><th></th><th></th><th></th></tr>
		<?php

		//initialize data from the database
		$games = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$this->parent->mochiDB['table_name']} ORDER BY generated DESC LIMIT %d OFFSET 0;", numGames), ARRAY_A);


		//initial rowOffset is 0
		$rowOffset = 0;
		//while the SELECT statement found games (100 per while loop)
		while(!empty($games))
		{
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

				//This line might be confusing, there were some problems setting $requested to 0 up above, so I set it to something else
				//I just fix it here.
				$requested = $requested==2?0:$requested;
				if($game['posted'] == $requested || $requested == 'all')
				{
					//populate table data
					?>
					<tr>
					<form name="<?php echo $game['uuid'];?>" action="edit.php?page=mochiGamesQueue" method="post">
					<?php
					wp_nonce_field( 'mochimanage', '_wpnonce', true, true );
					?>
					<input type="hidden" name="game_tag" value="<?php echo $game['game_tag'];?>" />
					<input type="hidden" name="mochi_list" value="<?php echo $_REQUEST['mochi_list'];?>"/>

					<td><img src="<?php echo $game['thumbnail_url'];?>" alt="splash for <?php echo $game['name'];?>" /></td>
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
							Image missing
							<?php
						}
						?>
					</td>
					<td>
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
							Image missing
							<?php
						}
						?>
					</td>
					<td>
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
							Image missing
							<?php
						}
						?>
					</td>
					<td>
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
							Image missing
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
					<td><a href="<?php echo $game['author_link'];?>"> <?php echo $game['author'];?></a></td>
					<td><a href="<?php echo $game['swf_url']?>">play</a></td>
					<td><input type="submit" name="mochi_action" value="delete" />
						<?php
						if($game['posted'])
						{
							?>
							<input type="submit" name="mochi_action" value="repost" />
							<?php
						}
						?>
					</td>
					<?php
					if($game['posted'])
					{
						global $wpdb;
						$postID = $wpdb->get_var($wpdb->prepare("SELECT post_ID from {$this->parent->mochiDB['table_name']} WHERE game_tag = %s", $game['game_tag']));

						?>
					</form>
						<form action="post.php?post=<?php echo $postID;?>&action=edit" method="post">
						<?php
						wp_nonce_field( 'edit', '_wpnonce', true, true );
						?>
							<td><input type="submit" name="mochi_action" value="<?php echo 'edit';?>"/>
								</td>
						<?php
					}
					else
					{
						?>
						<td><input type="submit" name="mochi_action" value="post and publish" />
						<input type="submit" name="mochi_action" value="post" /></td>
						<?php
					}
					?>
					
					</form>
					</tr>
					<?php
				}
				//increment rowCount and get next row
				$rowCount++;
				if(array_key_exists($rowCount, $games))
					$game = $games[$rowCount];
				else $game = NULL;
			}
			//assume 100 rows were processed
			$rowOffset += numGames;
			//fetch next result set
			$games = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$this->parent->mochiDB['table_name']} ORDER BY id DESC LIMIT %d OFFSET %d;", numGames, $rowOffset), ARRAY_A);
			//The purpose of doing it this way is to support a virtually limitless number of games, if I fetched them all at once, typical shared hosting would
			//eventually shut the script down for over-stepping its memory allocation.
			//Even though the intended usage of this plugin should never see more than a few games in the queue, it's better safe than sorry.
		}
		?>
				</table>
		<div>
			Show which games?
		<form action="edit.php?page=mochiGamesQueue" method="post">
			<input type="submit" name="mochi_list" value="posted">
			<input type="submit" name="mochi_list" value="unposted">
			<input type="submit" name="mochi_list" value="all">
		</form>
		</div>
		<?php
	}
}
?>
