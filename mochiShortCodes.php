<?php
/* 
 * This class contains shortcodes and their related functions
 */
class mochiShortCodes
{
	private $parent;
	public $screenshotSize;
	public $excerpt;
	public $count;
	public function mochiShortCodes(&$parent)
	{
		$this->parent = $parent;
		$this->screenshotSize['width'] = $this->parent->mochiAutoPostOptions->options['screenThumbWidth'];
		$this->screenshotSize['height'] = $this->parent->mochiAutoPostOptions->options['screenThumbHeight'];
		$this->excerpt = '';
		$this->count = 0; //will be incremented as it is used
		add_shortcode('mochigame', array(&$this, 'mochiShortcode'));
		if(!is_admin())
		{
			//don't filter on administration panel pages
			//many plugins use this filter to run do_shortcode in excerpts, but for compatibility purposes I don't
			//if a user is expecting other shortcodes to not be run in the excerpt for instance
			//so I'm going to write functions that will process ONLY [mochigame]
			add_filter('get_the_excerpt', array(&$this, 'doTheExcerpt'));
		}
	}
	public function doTheExcerpt($excerpt)
	{
		global $wpdb;
		$output = '';
		$count = 0;
		$excerpt = str_replace('m-DONT CHANGE:', '', $excerpt, $count);


		if($count >= 1)
		{
			$game = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$this->parent->mochiDB['table_name']} WHERE game_tag = %s", $excerpt), ARRAY_A);
			$post = $wpdb->get_row($wpdb->prepare('SELECT * FROM '.$wpdb->posts.' WHERE ID = %d', $game['post_ID']), ARRAY_A);

	        $pattern = get_shortcode_regex();
	        preg_replace_callback( "/$pattern/s", array(&$this, 'processMochiOnly'), $post['post_content'] );
			$output = $this->excerpt;
		}
		else
			$output = $excerpt;
		return $output;
	}
	public function processMochiOnly($m)
	{
		global $shortcode_tags;
		$tag = $m[2];
		// allow [[foo]] syntax for escaping a tag
	        if ( $m[1] == '[' && $m[6] == ']' )
			{
				//This shortcode is escaped, so letting it be handled normally is just fine.
				return $m[0];
	        }
			$attr = shortcode_parse_atts( $m[3] );

			if ( isset( $m[5] ) )
			{
			// enclosing tag - extra parameter
				//Only process mochigame tags
				if($tag == 'mochigame')
				{
					$this->excerpt = $m[1] . call_user_func( $shortcode_tags[$tag], $attr, $m[5], $tag ) . $m[6];
					return $this->excerpt;
				}
				else
					return $m[0]; //not mochigame, don't process
			}
			else
			{
			// self-closing tag
				if($tag == 'mochigame')
				{
					$this->excerpt = $m[1] . call_user_func( $shortcode_tags[$tag], $attr, $m[5], $tag ) . $m[6];
					return $this->excerpt;
				}
				else
					return $m[0]; //not mochigame, don't process


			}
	}
	public function mochiShortcode($atts, $content = null, $tag = null)
	{

		$output = '';
		if(isset($atts['game_tag']))
		{
			global $wpdb;
			$game = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$this->parent->mochiDB['table_name']} WHERE game_tag = %s", $atts['game_tag']), ARRAY_A);
			if(!array_key_exists('description', $atts))
					$atts['description'] = 'false';
			if(!array_key_exists('instructions', $atts))
					$atts['instructions'] = 'false';
			if(!array_key_exists('overridewidth', $atts))
			{
				$atts['overridewidth'] = '';
			}
			if(!array_key_exists('noad', $atts))
			{
				$atts['noad'] = 'false';
			}
			if(!array_key_exists('width', $atts))
			{
				$atts['width'] = 0;
			}
			else
				$atts['width'] = (int)$atts['width'];
			if(!array_key_exists('height', $atts))
			{
				$atts['height'] = 0;
			}
			else
				$atts['height'] = (int)$atts['height'];
			

			

			$widthAltered = false;
			if($atts['width'] != 0)
			{
				$width = $atts['width'];
				$widthAltered = true;
				if($atts['height'] == 0)
				{
					$aspectW = $game['height']/$game['width'];
					$height = $aspectW * $width;
				}
			}
			else
			{
				if($atts['height'] == 0)
					$width = $game['width'];
			}

			if($atts['height'] != 0)
			{
				$height = $atts['height'];

				if($atts['width'] == 0)
				{
					$aspectH = $game['width']/$game['height'];
					$width = $aspectH * $height;
					$widthAltered = true;
				}
			}
			else
				$height = $game['height'];

			if($atts['width'] == 0 && $atts['height'] == 0 && $atts['overridewidth'] != 'true')
			{
				$widthAltered = false;
				if($this->parent->mochiAutoPostOptions->options['minWidth'] != 0)
				{
					if($game['width'] < $this->parent->mochiAutoPostOptions->options['minWidth'])
					{
						$width = $this->parent->mochiAutoPostOptions->options['minWidth'];
						$widthAltered = true;
					}
				}
				if($this->parent->mochiAutoPostOptions->options['maxWidth'] != 0)
				{
					if($game['width'] > $this->parent->mochiAutoPostOptions->options['maxWidth'])
					{
						$width = $this->parent->mochiAutoPostOptions->options['maxWidth'];
						$widthAltered = true;
					}
				}
				if($widthAltered)
				{
					$aspectW = $game['height']/$game['width'];
					$height = $aspectW * $width;
				}
			}

			if(is_single())
			{
				//TODO: Create a button that pops up instructions
				if($this->parent->mochiAutoPostOptions->options['autoPostSWF'] == 'page')
				{
					if($game['posted'])
					{
						$output.='
						<style type="text/css">
						</style>
						<style type="text/css">
						.mAAPAds
						{
							position: relative;
							margin-top: 150px;
						}
						</style>
						<div id="mochi_box">
						<div id="mochi_game">
						<object type="application/x-shockwave-flash" data="'.wp_get_attachment_url($game['swf_attach_id']).'" width="'.$width.'" height="'.$height.'">
							<param name="movie" value="'.wp_get_attachment_url($game['swf_attach_id']).'" />
						</object>
						</div>';
						//Add bridge script
						$output.='<div id="leaderboard_bridge"></div>
							<script src="http://xs.mochiads.com/static/pub/swf/leaderboard.js" type="text/javascript"></script>
							<script type="text/javascript">var options = {partnerID: "'.$this->parent->mochiAutoPostOptions->options['publisher_id'].'", id: "leaderboard_bridge"};
							Mochi.addLeaderboardIntegration(options);
							</script>';
					}
					else
					{
						$output='
						<object type="application/x-shockwave-flash" data="'.$game['swf_url'].'" width="'.$width.'" height="'.$height.'">
							<param name="movie" value="'.$game['swf_url'].'" />
						</object>';
						//Add bridge script
						$output.='<div id="leaderboard_bridge"></div>
							<script src="http://xs.mochiads.com/static/pub/swf/leaderboard.js" type="text/javascript"></script>
							<script type="text/javascript">var options = {partnerID: "'.$this->parent->mochiAutoPostOptions->options['publisher_id'].'", id: "leaderboard_bridge"};
							Mochi.addLeaderboardIntegration(options);
							</script>';
					}
				}
				else if($this->parent->mochiAutoPostOptions->options['autoPostSWF'] == 'link')
				{
					$output = '<a href='.wp_get_attachment_url($game['swf_attach_id']).'>Click here to play '.$game['name'].'!</a>';
				}
				if($atts['noad'] == 'false' && $this->parent->mochiAutoPostOptions->options['adCode'] != '')
				{
					$output .= '';
					$output .= '<div id="mochiAdCode" class="mAAPAds">';
					$output .= $this->parent->mochiAutoPostOptions->options['adCode'];
					$output .= '</div></div>';
				}
				if($atts['description'] == 'true')
				{
					$output .= '<p>';
					$output .= $game['description'];
					$output .= '</p>';
				}
				if($atts['instructions'] == 'true')
				{
					$output .= '<p>';
					$output .= $game['instructions'];
					$output .= '</p>';
				}
			}
			else
			{
			$args = array(
			'post_parent' => $game['post_ID'],
			'post_type' => 'attachment',
			'post_mime_type' => 'image'
					);
			$attachments = get_children($args, ARRAY_A);
			$thumb = get_post_thumbnail_id($game['post_ID']);
			if($this->parent->mochiAutoPostOptions->options['postPics'] == 'yes')
			{
				$output .= '<a href="'.get_permalink($game['post_ID']).'">';
				$output .= wp_get_attachment_image( $thumb, 'full');
				$output .= '</a></br>';
			}
			if($this->parent->mochiAutoPostOptions->options['postScreens'] == 'yes')
			foreach ($attachments as $attID => $att)
			{
				if($attID != $thumb)
				{
					$imageSRC = wp_get_attachment_image_src( $attID, 'thumbnail', true);
					$output .= '<a href='.get_attachment_link($attID).'>';
					$output .= '<img src="'.$imageSRC[0].'"  alt="Screenshot of '.(string)$game['name'].'" title="Screenshot of '.(string)$game['name'].'" style="height:'.$this->screenshotSize['height'].
							'px;width:'.$this->screenshotSize['width'].'px;"/>';
					$output .= '</a>';
				}
			}




				if($atts['description'] == 'true')
				{
					$output .= '<p>';
					$output .= $game['description'];
					$output .= '</p>';
				}
				if($atts['instructions'] == 'true')
				{
					$output .= '<p>';
					$output .= $game['instructions'];
					$output .= '</p>';
				}
				
			}
			$appendAuthor = '<a href='.$game['author_link'].'>'.$game['author'].'</a>';
			if(array_key_exists('authorlink', $atts))
			{
				if($atts['authorlink'] == 'false')
					$appendAuthor = $game['author'];
			}
			if(array_key_exists('author', $atts))
			{
				if($atts['author'] == 'false')
				{
					$appendAuthor = '';
				}
			}
			$appendAuthor = '<br />'.$appendAuthor;
			$output .= $appendAuthor;

			//Add disclaimer if the game's width is altered
			if($widthAltered)
			{
				if(array_key_exists('author', $atts) && $atts['author'] == 'false')
					$output .= '<p>This game\'s default size has been altered, as such it may not appear as the author intended.</p>';
				else
					$output .= '<p>This game\'s default size has been altered, as such it may not appear as '.$game['author'].' intended.</p>';
			}
		}
		$output .= $content;
		return $output;
	}
}
?>
