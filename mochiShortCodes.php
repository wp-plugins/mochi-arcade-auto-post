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
		if(isset($atts['game_tag']))
		{
			global $wpdb;
			$game = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$this->parent->mochiDB['table_name']} WHERE game_tag = %s", $atts['game_tag']), ARRAY_A);
			if(!array_key_exists('description', $atts))
					$atts['description'] = '';
			if(!array_key_exists('instructions', $atts))
					$atts['instructions'] = '';
			if(array_key_exists('width', $atts))
			{
				$width = $atts['width'];
				if(!array_key_exists('height', $atts))
				{
					$aspectW = $game['height']/$game['width'];
					$height = $aspectW * $width;
				}
			}
			else
				if(!array_key_exists('height', $atts))
					$width = $game['width'];

			if(array_key_exists('height', $atts))
			{
				$height = $atts['height'];

				if(!array_key_exists('width', $atts))
				{
					$aspectH = $game['width']/$game['height'];
					$width = $aspectH * $height;
				}
			}
			else
				$height = $game['height'];
			if(is_single())
			{
				//TODO: Create a button that pops up instructions
				if($this->parent->mochiAutoPostOptions->options['autoPostSWF'] == 'page')
				{
					if($game['posted'])
					{
						$output='
						<object type="application/x-shockwave-flash" data="'.wp_get_attachment_url($game['swf_attach_id']).'" width="'.$width.'" height="'.$height.'">
							<param name="movie" value="'.wp_get_attachment_url($game['swf_attach_id']).'" />
						</object>';
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
					$output = '<a href='.wp_get_attachment_url($game['swf_attach_id']).'>Click here to play!</a>';
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
				return $output;
			}
			else
			{
				$output = '';
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
				return $output;
			}
		}
	}
}
?>
