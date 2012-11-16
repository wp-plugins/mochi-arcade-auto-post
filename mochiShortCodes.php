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
		global $post;
		$output = '';
		$count = 0;
		$excerpt = str_replace('m-DONT CHANGE:', '', $excerpt, $count);


		if($count >= 1)
		{
			//$game = $this->parent->getGame($excerpt);
	        $pattern = get_shortcode_regex();
	        preg_replace_callback( "/$pattern/s", array(&$this, 'processMochiOnly'), $post->post_content );
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
			$game = $this->parent->getGame($atts['game_tag']);
			if(isset($game))
			{
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
			if(!array_key_exists('flashscreen', $atts))
				$atts['flashscreen'] = 'keep';

			

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
				if($game['posted'] == mochiAdminMenu::posted)
				{
					$gameURL = wp_get_attachment_url($game['swf_attach_id']);
					
				}
				else
				{
					$gameURL = $game['swf_url'];
					
				}
				if($this->parent->mochiAutoPostOptions->options['autoPostSWF'] == 'page')
				{
					?>
					<style type="text/css">
					.mAAPAds
					{
						position: relative;
						margin-top: 150px;
					}
					.flashManip
					{
						position:relative;
						padding:0;
						z-index: 9999;
					}
					</style>

					
					<script type="text/javascript" src="<?php echo plugin_dir_url(__FILE__);?>js/FlashScreen.js"></script>
					<?php
					//mochi_box contains all mochi shortcode output
					//mochi_game contains the game and the fullscreen link
					$output.='
						<div id="mochi_box">
						<div id="mochi_game" class="mochiFlash" style="height:'.$height.'px;width:'.$width.'px;position:relative;z-index:9998;background-color:LightGray;">
						<object type="application/x-shockwave-flash" id="mochi_object" data="'.$gameURL.'" width="100%" height="100%" style="z-index:9998;">
							<param name="movie" value="'.$gameURL.'" />
							<param name="allowFullScreen" value="true" />
							<param name="quality" value="high" />';
					if($atts['flashscreen'] == 'stage3d' || $atts['flashscreen'] == 'false' || $atts['flashscreen'] == 'stagescreen')
						$output.= '<param name="wmode" value="direct" />';
					else
						$output.= '<param name="wmode" value="transparent" />';
					if($atts['flashscreen'] == 'deform')
					$output.='<param name="scale" value="exactfit" />
						</object>';
					else
					$output.='<param name="scale" value="showall" />
						</object>';
					if($atts['flashscreen'] != 'false' && $atts['flashscreen'] != 'stage3d')
					{
						$output.='</div>
							<div id="fullscreen_link" class="flashManip" style="top:0px;left:'.(($width/2)-60).'px;z-index:9999;">
							<form>
							<!--FlashScreen(requires javascript enabled)-->
							<script type="text/javascript">
							document.write(\'<input type="button" id="fullscreen_link2" value="FlashScreen" onclick="mochiArcadeAutoPostJS.toggleFull();" class="flashManip"/>\');
							</script>
							<noscript>
							'.$this->parent->mochiAutoPostOptions->options['noScript'].'
							</noscript>
							</form>
							</div><br/>';
							//place button for fullscreen via javascript, since it will only function if javascript is on anyway
					}
					else
					{
						//FlashScreen as I've dubbed it is a faux-fullscreen mode accomplished with javascript
						//It'll cause the flash to fill the browser window (without distorting it)
						//But certain ways of programming a flash game will make this mostly not work.
						//So for them, we have a shortcode option, flashscreen=false, and the shortcode grows out of control!
						$output.='';
					}
					//$output.='
						//</div>
						//';
						//Add bridge script
						$output.='<div id="leaderboard_bridge"></div>
							<script src="http://xs.mochiads.com/static/pub/swf/leaderboard.js" type="text/javascript"></script>
							<script type="text/javascript">var options = {partnerID: "'.$this->parent->mochiAutoPostOptions->options['publisher_id'].'", id: "leaderboard_bridge"};
							Mochi.addLeaderboardIntegration(options);
							</script>';
				}
				else if($this->parent->mochiAutoPostOptions->options['autoPostSWF'] == 'link')
				{
					$output = '<a href='.$gameURL.'>Click here to play '.$game['name'].'!</a>';
				}
				if($atts['noad'] == 'false' && $this->parent->mochiAutoPostOptions->options['adCode'] != '')
				{
					$output .= '';
					$output .= '<div id="mochiAdCode" class="mAAPAds">';
					$output .= $this->parent->mochiAutoPostOptions->options['adCode'];
					$output .= '</div>';
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
				$output .= '</div>'; //close <div id="mochibox">
			}
			else //!is_single()
			{
				$thumb = '';
				$screenshots = '';
				//if($game['posted'] == mochiAdminMenu::posted)
				{
					$args = array(
					'post_parent' => $game['post_ID'],
					'post_type' => 'attachment',
					'post_mime_type' => 'image'
							);
					if($game['post_ID'] != NULL && $game['post_ID'] != 0)
						$attachments = get_children($args, ARRAY_A);
					$thumbID = get_post_thumbnail_id($game['post_ID']);
					$count = 0;
					if($this->parent->mochiAutoPostOptions->options['postPics'] == 'yes')
					{
						if($thumbID != NULL)
						{
							$thumb .= '<a href="'.get_permalink().'">';
							if($this->parent->mochiAutoPostOptions->options['thumbSize'] == 'large')
							{
								$thumb .= '<img src="'.wp_get_attachment_url($thumbID).'"  alt="Splash of '.(string)$game['name'].'" title="Splash of '.(string)$game['name'].'" style="height:200px;width:200px;"/>';
							}
							else
							{
								$thumb .= '<img src="'.wp_get_attachment_url($thumbID).'"  alt="Splash of '.(string)$game['name'].'" title="Splash of '.(string)$game['name'].'" style="height:100px;width:100px;"/>';
							}
							$thumb .= '</a></br>';
						}
						else
						{
							$thumbLarge = $game['thumbnail_large_url'];
							$thumbSmall = $game['thumbnail_url'];
							$thumb .= '<a href="'.get_permalink().'">';
							if($this->parent->mochiAutoPostOptions->options['thumbSize'] == 'large')
							{

								if($thumbLarge != '')
								{

									$thumb .= '<img src="'.$thumbLarge.'"  alt="Splash of '.(string)$game['name'].'" title="Splash of '.(string)$game['name'].'" style="height:200px;width:200px;"/>';

								}
								else
								{
									$thumb .= '<img src="'.$thumbSmall.'"  alt="Splash of '.(string)$game['name'].'" title="Splash of '.(string)$game['name'].'" style="height:200px;width:200px;"/>';
								}

							}
							else
							{
								if($thumbSmall != '')
								{

									$thumb .= '<img src="'.$thumbSmall.'"  alt="Splash of '.(string)$game['name'].'" title="Splash of '.(string)$game['name'].'" style="
										height:100px;
										width:100px;"/>';

								}
								else
								{
									$thumb .= '<img src="'.$thumbLarge.'"  alt="Splash of '.(string)$game['name'].'" title="Splash of '.(string)$game['name'].'" style="
										height:100px;
										width:100px;"/>';
								}
							}
							$thumb .= '</a></br>';
						}
					}
					if($this->parent->mochiAutoPostOptions->options['postScreens'] == 'yes')
					{
						$count = 1;
						if(isset($attachments))
						foreach ($attachments as $attID => $att)
						{
							if($attID != $thumbID)
							{
								$imageSRC = wp_get_attachment_image_src( $attID, 'thumbnail', true);
								$screenshots .= '<a href='.get_attachment_link($attID).'>';
								$screenshots .= '<img src="'.$imageSRC[0].'"  alt="Screenshot of '.(string)$game['name'].'" title="Screenshot of '.(string)$game['name'].'" style="height:'.$this->screenshotSize['height'].
										'px;width:'.$this->screenshotSize['width'].'px;"/>';
								$screenshots .= '</a>';
							}
							$count++;
						}
						if($count < 5)
						{
							if($game['screen'.$count.'_thumb'] != '')
							{
								//some games have screenshots available, but not in thumbnail form... Unfortunately they still register a url for the thumbnail
								if ($this->urlOK($game['screen'.$count.'_thumb']))
								{
									//valid

									$imageSRC = $game['screen'.$count.'_thumb'];
									$screenshots .= '<a href='.$imageSRC.'>';
									$screenshots .= '<img src="'.$imageSRC.'"  alt="Screenshot of '.(string)$game['name'].'" title="Screenshot of '.(string)$game['name'].'" style="height:'.$this->screenshotSize['height'].
											'px;width:'.$this->screenshotSize['width'].'px;"/>';
									$screenshots .= '</a>';
								}
								else
								{
									if($game['screen'.$count.'_url'] != '')
									{
									$imageSRC = $game['screen'.$count.'_url'];
									$screenshots .= '<a href='.$imageSRC.'>';
									$screenshots .= '<img src="'.$imageSRC.'"  alt="Screenshot of '.(string)$game['name'].'" title="Screenshot of '.(string)$game['name'].'" style="height:'.$this->screenshotSize['height'].
											'px;width:'.$this->screenshotSize['width'].'px;"/>';
									$screenshots .= '</a>';
									}
								}
							}
						}
					}
				}



	//			if($this->parent->mochiAutoPostOptions->options['postPics'] == 'yes')
	//			{
	//				$output .= '<a href="'.get_permalink($game['post_ID']).'">';
	//				$output .= wp_get_attachment_image( $thumb, 'full');
	//				$output .= '</a></br>';
	//			}
	//			if($this->parent->mochiAutoPostOptions->options['postScreens'] == 'yes')
	//			foreach ($attachments as $attID => $att)
	//			{
	//				if($attID != $thumb)
	//				{
	//					$imageSRC = wp_get_attachment_image_src( $attID, 'thumbnail', true);
	//					$output .= '<a href='.get_attachment_link($attID).'>';
	//					$output .= '<img src="'.$imageSRC[0].'"  alt="Screenshot of '.(string)$game['name'].'" title="Screenshot of '.(string)$game['name'].'" style="height:'.$this->screenshotSize['height'].
	//							'px;width:'.$this->screenshotSize['width'].'px;"/>';
	//					$output .= '</a>';
	//				}
	//			}
					$output .= $thumb;
					$output .= $screenshots;



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
			if(is_single() && $this->parent->mochiAutoPostOptions->options['thumbnailTitle'] != 'off')
			{
				$src = '';
				$args = array(
					'post_parent' => $game['post_ID'],
					'post_type' => 'attachment',
					'post_mime_type' => 'image'
							);
				if($game['post_ID'] != NULL && $game['post_ID'] != 0)
					$attachments = get_children($args, ARRAY_A);
				$thumbID = get_post_thumbnail_id($game['post_ID']);
				$count = 0;
				if($thumbID != NULL)
				{
					$src .= '<a href="'.get_permalink().'">';
					if($this->parent->mochiAutoPostOptions->options['thumbnailTitle'] == 'large')
					{
						$src .= '<img src="'.wp_get_attachment_url($thumbID).'"  alt="Splash of '.(string)$game['name'].'" title="Splash of '.(string)$game['name'].'" style="height:200px;width:200px;"/>';
					}
					else
					{
						$src .= '<img src="'.wp_get_attachment_url($thumbID).'"  alt="Splash of '.(string)$game['name'].'" title="Splash of '.(string)$game['name'].'" style="height:100px;width:100px;"/>';
					}
					$src .= '</a></br>';
				}
				else
				{
					$thumbLarge = $game['thumbnail_large_url'];
					$thumbSmall = $game['thumbnail_url'];
					$src .= '<a href="'.get_permalink().'">';
					if($this->parent->mochiAutoPostOptions->options['thumbnailTitle'] == 'large')
					{

						if($thumbLarge != '')
						{

							$src .= '<img src="'.$thumbLarge.'"  alt="Splash of '.(string)$game['name'].'" title="Splash of '.(string)$game['name'].'" style="height:200px;width:200px;"/>';

						}
						else
						{
							$src .= '<img src="'.$thumbSmall.'"  alt="Splash of '.(string)$game['name'].'" title="Splash of '.(string)$game['name'].'" style="height:200px;width:200px;"/>';
						}

					}
					else
					{
						if($thumbSmall != '')
						{

							$src .= '<img src="'.$thumbSmall.'"  alt="Splash of '.(string)$game['name'].'" title="Splash of '.(string)$game['name'].'" style="
								height:100px;
								width:100px;"/>';

						}
						else
						{
							$src .= '<img src="'.$thumbLarge.'"  alt="Splash of '.(string)$game['name'].'" title="Splash of '.(string)$game['name'].'" style="
								height:100px;
								width:100px;"/>';
						}
					}
					$src .= '</a></br>';
				}
				if($this->parent->mochiAutoPostOptions->options['thumbnailTitle'] != 'off')
				$output .= '
					<script type="text/javascript">
					var theDiv = document.getElementById("mochiTitle");
					theDiv.innerHTML =\''.$src.'\';
					</script>';
			}
			return $output;
		}
	}
	public function urlOK($url)
	{

		$url_data = parse_url ($url);
		if (!$url_data) return FALSE;

	   $errno="";
	   $errstr="";
	   $fp=0;

	   $fp=fsockopen($url_data['host'],80,$errno,$errstr,30);

	   if($fp===0) return FALSE;
	   $path ='';
	   if  (isset( $url_data['path'])) $path .=  $url_data['path'];
	   if  (isset( $url_data['query'])) $path .=  '?' .$url_data['query'];

	   $out="GET /$path HTTP/1.1\r\n";
	   $out.="Host: {$url_data['host']}\r\n";
	   $out.="Connection: Close\r\n\r\n";

	   fwrite($fp,$out);
	   $content=fgets($fp);
	   $code=trim(substr($content,9,4)); //get http code
	   fclose($fp);
	   // if http code is 2xx or 3xx url should work
	   return  ($code[0] == 2 || $code[0] == 3) ? TRUE : FALSE;
	}
}
?>
