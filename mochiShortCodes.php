<?php
/* 
 * This class contains shortcodes and their related functions
 */
class mochiShortCodes
{
	private $parent;
	public function mochiShortCodes(&$parent)
	{
		$this->parent = $parent;
		add_shortcode('mochigame', array(&$this, 'mochiShortcode'));
	}
	public function mochiShortcode($atts, $content = null, $tags = null)
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
		return $output;
	}
}
?>
