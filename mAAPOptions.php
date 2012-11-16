<?php
/*
 * This class will create, and operate the options forms for the Mochi
 * Arcade Auto Post plugin. As well as provide a variable to access them.
 */
class mAAPOptions
{
	public $options;
	private $pluginName;
	public $parent;
	public function mAAPOptions($pluginName, $theParent = null) //mAAPOptions constructor
	{
		//initializes the plugin by adding actions and filters
		//get current options.
		$this->parent = $theParent;
		$this->pluginName = $pluginName;
		$this->options = get_option($this->pluginName.'Options');
		//Make it a valid array for below functions even if get_option returned nothing
		$this->options['initialized'] = true;

		//set defaults
		//I realize this is an odd way to do this, rather than setting the options in the install script
		//It could be more efficient too, but the performance impact is negligible.  I wrote it when I didn't
		//quite fully understand the options api yet, and I'm lazy to change it.
		if(!array_key_exists('autoPostSWF', $this->options))
			$this->options['autoPostSWF'] = 'page';
		if(!array_key_exists('publisher_id', $this->options))
			$this->options['publisher_id'] = 'paste your mochi publisher id here';
		if(!array_key_exists('maappw', $this->options))
			$this->options['maappw'] = 'password';
		if(!array_key_exists('gamesOnHomePage', $this->options))
			$this->options['gamesOnHomePage'] = 'yes';
		if(!array_key_exists('primCat', $this->options))
			$this->options['primCat'] = 'no';
		if(!array_key_exists('minWidth', $this->options))
			$this->options['minWidth'] = '';
		if(!array_key_exists('maxWidth', $this->options))
			$this->options['maxWidth'] = '';
		if(!array_key_exists('adCode', $this->options))
			$this->options['adCode'] = '';
		if(!array_key_exists('postPics', $this->options))
			$this->options['postPics'] = 'no';
		if(!array_key_exists('postScreens', $this->options))
			$this->options['postScreens'] = 'yes';
		if(!array_key_exists('thumbSize', $this->options))
			$this->options['thumbSize'] = 'large';
		if(!array_key_exists('screenThumbWidth', $this->options))
			$this->options['screenThumbWidth'] = '64';
		if(!array_key_exists('screenThumbHeight', $this->options))
			$this->options['screenThumbHeight'] = '64';
		if(!array_key_exists('noScript', $this->options))
			$this->options['noScript'] = '';
		if(!array_key_exists('hideGamesOnHomeWidgets', $this->options))
			$this->options['hideGamesOnHomeWidgets'] = 'off';
		if(!array_key_exists('thumbnailTitle', $this->options))
			$this->options['thumbnailTitle'] = 'off';
		add_action('admin_menu', array(&$this, 'createSettingsPage'));
		add_action('admin_init', array(&$this, 'createSettingsFields'));

	}
	public function createSettingsPage() //registers the settings page with wordpress
	{
		add_options_page( 'Mochi AP Options',					//Name of page on admin screen
						  'Mochi Arcade Auto Post',				//page title
						  'manage_options',						//required permissions to access page
						  $this->pluginName.'OptionsPage',						//unique page id
						  array(&$this, 'createSettingsForm')); //function to fire when clicked
	}
	public function createSettingsForm() //creates the form, and submit button, and asks wordpress about everything else
	{
		?>
		<div>
			<h2>Mochi Arcade Auto Post</h2>
			Options page.
			<form action="options.php" method="post">
				<?php settings_fields($this->pluginName.'Options'); //get settings fields ?>
				<?php do_settings_sections($this->pluginName.'OptionsPage')//apply settings sections ?>

				<input name="Submit" type="submit" value="<?php esc_attr_e('Save Changes'); ?>" />
			</form>
		</div>
	<?php
	}
	public function createSettingsFields() //registers various settings with wordpress
	{
		register_setting( $this->pluginName.'Options',				//settings fields group
						  $this->pluginName.'Options',				//name of the options
						  array(&$this, 'mochiAPValidate'));		//function to validate

		add_settings_section( 'mochiAPPublisherData',				//unique id for section
							  'Publisher Data',						//title of the section
							  array(&$this, 'publisherDataText'),	//Displays in section
							 $this->pluginName.'OptionsPage');		//settings page id

		add_settings_section( 'mochiAPGeneral',						//unique id for section
							  'General',							//title of the section
							  array(&$this, 'generalText'),			//Displays in section
							 $this->pluginName.'OptionsPage');		//settings page id

		add_settings_section( 'pictures',						//unique id for section
							  'Screenshots and thumbnails',							//title of the section
							  array(&$this, 'piccieOps'),			//Displays in section
							 $this->pluginName.'OptionsPage');		//settings page id
		
		add_settings_section( 'mochiGamesVisibility',						//unique id for section
							  'Game Visibility',							//title of the section
							  array(&$this, 'visText'),			//Displays in section
							 $this->pluginName.'OptionsPage');		//settings page id

		add_settings_section( 'postOpts',						//unique id for section
							  'Post Options',							//title of the section
							  array(&$this, 'postOps'),			//Displays in section
							 $this->pluginName.'OptionsPage');		//settings page id

//JPL-849 in-development feature, uncomment to enable at your own risk (currently also in the wrong section of settings)
/*		add_settings_field('titlePics',							//field ID
						   'Post game thumbnail before title?',	//field title
						   array(&$this, 'thumbTitle'),			//callback to display input box
						   $this->pluginName.'OptionsPage',		//Page ID
						   'postOpts');			*/				//Section ID

		add_settings_field('mochiPublisherID',				//field ID
						   'Mochi Publisher ID',			//field title
						   array(&$this, 'publisherIDBox'),	//callback to display input box
						   $this->pluginName.'OptionsPage',	//Page ID
						   'mochiAPPublisherData');			//Section ID

		add_settings_field('autoPostSWF',						//field ID
						   'How should your SWFs be accessed',	//field title
						   array(&$this, 'postSwfIn'),			//callback to display input box
						   $this->pluginName.'OptionsPage',		//Page ID
						   'mochiAPGeneral');				//Section ID

		add_settings_field('maappw',						//field ID
						   '<strong>password</strong>',		//field title
							array(&$this, 'setMAAPPW'),		//callback to display form elements
							$this->pluginName.'OptionsPage',//Page ID
						   'mochiAPPublisherData');				//Section ID

		add_settings_field('primCat',						//field ID
						   'Game categories',				//field title
							array(&$this, 'primaCats'),		//callback to display form elements
							$this->pluginName.'OptionsPage',//Page ID
						   'postOpts');				//Section ID

		add_settings_field('maxWidth',						//field ID
						   'Game Width',				//field title
							array(&$this, 'widthSet'),		//callback to display form elements
							$this->pluginName.'OptionsPage',//Page ID
						   'mochiAPGeneral');				//Section ID

		add_settings_field('gamesOnHomePage',				//field ID
							'Show game posts on home page?',	//field title
							array(&$this, 'hideHome'),		//callback to display form elements
							$this->pluginName.'OptionsPage',//page ID
							'mochiGamesVisibility'			//Section ID
							);

		add_settings_field('postPics',				//field ID
							'add featured image to post',	//field title
							array(&$this, 'postPicture'),		//callback to display form elements
							$this->pluginName.'OptionsPage',//page ID
							'pictures'			//Section ID
							);

		add_settings_field('postScreens',				//field ID
							'add screenshots to post',	//field title
							array(&$this, 'postScreenies'),		//callback to display form elements
							$this->pluginName.'OptionsPage',//page ID
							'pictures'			//Section ID
							);

		add_settings_field('screenThumbSize',				//field ID
							'Screenshot thumbnail size',	//field title
							array(&$this, 'screenshotThumbSize'),		//callback to display form elements
							$this->pluginName.'OptionsPage',//page ID
							'pictures'			//Section ID
							);

		add_settings_field('thumbSize',				//field ID
							'What size thumbnails to prefer?',	//field title
							array(&$this, 'thumbnailSize'),		//callback to display form elements
							$this->pluginName.'OptionsPage',//page ID
							'pictures'			//Section ID
							);

		add_settings_field('adCode(s)',						//field ID
						   'Ad code',				//field title
							array(&$this, 'adCodes'),		//callback to display form elements
							$this->pluginName.'OptionsPage',//Page ID
						   'mochiAPGeneral');
	}
	public function thumbTitle()
	{
		?>
		<p>
			<p>
			<input type="radio" name="<?php echo $this->pluginName; ?>Options[thumbnailTitle]" value="large"<?php if($this->options['thumbnailTitle']=='large') echo ' checked'; ?>/> Large (200x200 px)
		</p>
		<p>
			<input type="radio" name="<?php echo $this->pluginName; ?>Options[thumbnailTitle]" value="small"<?php if($this->options['thumbnailTitle']=='small') echo ' checked'; ?>/> Small (100x100 px)
		</p>
		<p>
			<input type="radio" name="<?php echo $this->pluginName; ?>Options[thumbnailTitle]" value="off"<?php if($this->options['thumbnailTitle']=='off') echo ' checked'; ?>/> Off
		</p>
		<p>
			This will place the game's thumbnail next to the title, currently it may not work properly in all themes, as it doesn't yet know how to differentiate between posts.  (eg. navigation links may get the image instead of the current post's title)<br/>
			The next patch will include a better targeted image, for now it's recommended that you set this to off. (may reserve space for the image in multiple places, although only one image will exist)
		</p>
		</p>
		<?php
	}
	public function screenshotThumbSize()
	{
		?>
		<p>
			<input type="text" id="screenThumbWidth" name="<?php echo $this->pluginName.'Options[screenThumbWidth]';?>" value="<?php echo $this->options['screenThumbWidth'];?>" /> Thumbnail width
		</p>
		<p>
			<input type="text" id="screenThumbHeight" name="<?php echo $this->pluginName.'Options[screenThumbHeight]';?>" value="<?php echo $this->options['screenThumbHeight'];?>" /> Thumbnail height
		</p>
		<br/>The plugin uses the thumbnail size image created by Wordpress, and then scales it to whatever values you add here via HTML,
		so there may be pixelation if you set it larger than the thumbnail size.
		<?php
	}
	public function thumbnailSize()
	{
		?>
		<p>
			<input type="radio" name="<?php echo $this->pluginName; ?>Options[thumbSize]" value="large"<?php if($this->options['thumbSize']=='large') echo ' checked'; ?>/> Large (200x200 px)
		</p>
		<p>
			<input type="radio" name="<?php echo $this->pluginName; ?>Options[thumbSize]" value="small"<?php if($this->options['thumbSize']=='small') echo ' checked'; ?>/> Small (100x100 px)
		</p>
		<p>
			Sets the default size thumbnails to use, all thumbnails will be stretched or shrunk to fit this size.<br/>
			Large is usually suggested.
		</p>
		<?php
	}
	public function postScreenies()
	{
		?>
		<p>
			<input type="radio" name="<?php echo $this->pluginName; ?>Options[postScreens]" value="yes"<?php if($this->options['postScreens']=='yes') echo ' checked'; ?>/> Yes
		</p>
		<p>
			<input type="radio" name="<?php echo $this->pluginName; ?>Options[postScreens]" value="no"<?php if($this->options['postScreens']=='no') echo ' checked'; ?>/> No
		</p>
		<p>
			Setting this to yes will cause screenshots to be shown with the game (as links to the game's gallery).
		</p>
		<?php
	}
	public function postPicture()
	{
		?>
		<p>
			<input type="radio" name="<?php echo $this->pluginName; ?>Options[postPics]" value="yes"<?php if($this->options['postPics']=='yes') echo ' checked'; ?>/> Yes
		</p>
		<p>
			<input type="radio" name="<?php echo $this->pluginName; ?>Options[postPics]" value="no"<?php if($this->options['postPics']=='no') echo ' checked'; ?>/> No
		</p>
		<p>
			Setting this to yes will cause the game's thumbnail to be shown (as a link to the game's post)
		</p>
		<?php
	}
	public function adCodes()
	{
		?>
		<p>
			<textarea rows="10" cols="70" name="<?php echo $this->pluginName.'Options[adCode]';?>"><?php echo $this->options['adCode'];?></textarea>
			<br/>This will be placed 150px below your games<br/><br/>
			<strong>IMPORTANT:</strong> Google adsense recommends placing ads no less than 150px away from flash games, if you get a lot of
			what they determine to be accidental clicks they will suspend your account regardless of whether you followed that recommendation
			or not.  The above box will be inserted 150px below your flash game on the page as recommended, but you will be responsible for
			ensuring that your users are not accidentally clicking on these ads.  If a particular game seems prone to this sort of thing, add noad=true
			to its shortcode.
		</p>
		<?php
	}
	public function visText()
	{
		echo '<p>Game Visibility</p>';
	}
	public function widthSet()
	{
		?>
			
		<p>
			<input type="text" id="maxWidth" name="<?php echo $this->pluginName.'Options[maxWidth]';?>" value="<?php echo $this->options['maxWidth'];?>" /> Maximum game width
		</p>
		<p>
			<input type="text" id="minWidth" name="<?php echo $this->pluginName.'Options[minWidth]';?>" value="<?php echo $this->options['minWidth'];?>" /> Minimum game width
		</p>
		<p>
			The default size of the games varies, this can be trouble for some
			(most) themes, leaving both blank (or setting to 0) will always use
			the default width, and the default width will always be preferred.
			Setting both to the same value allows you to specify that all games
			should be that size on your site.  The aspect ratio of the games
			will be maintained (by proportionately altering the height as well).
			<br />
			<strong>NOTE: As with all game embed size altering functions, some games are
			hard coded to a specific size, and will experience issues (such as
			unused game elements appearing slightly off screen, or game elements
			clipping off the edges (not just UI, but even some of the action).</strong>
			Fear not though, you can override these min/max width settings by either
			specifying a width in the game post's shortcode or specifying overridewidth=true in the shortcode (to use default)
		</p>
		<?php
	}
	public function postOps()
	{
		echo '<p>These options affect games as they are posted only (not retroactively)</p>';
	}
	public function piccieOps()
	{
		
	}
	public function primaCats()
	{
		?>
		<p>
			<input type="radio" name="<?php echo $this->pluginName; ?>Options[primCat]" value="yes"<?php if($this->options['primCat']=='yes') echo ' checked'; ?>/> Classify games under a single category
		</p>
		<p>
			<input type="radio" name="<?php echo $this->pluginName; ?>Options[primCat]" value="no"<?php if($this->options['primCat']=='no') echo ' checked'; ?>/> Use multiple categories
		</p>
		<p>
			Games, especially flash games, can be considered to fall into multiple genres, this section allows you to choose whether you want to acknowledge a game's hybrid status, or keep a clean category structure.
			<br /><i>(Changes to this setting will only affect games posted AFTER the setting is saved.)</i>
		</p>
		<?php
	}
	public function hideHome()
	{
		?>
		<p>
			<input type="radio" name="<?php echo $this->pluginName; ?>Options[gamesOnHomePage]" value="yes"<?php if($this->options['gamesOnHomePage']=='yes') echo ' checked'; ?>/> Show on home page
		</p>
		<p>
			<input type="radio" name="<?php echo $this->pluginName; ?>Options[gamesOnHomePage]" value="no"<?php if($this->options['gamesOnHomePage']=='no') echo ' checked'; ?>/> Hide on home page <br/>
			<input type="checkbox" name="<?php echo $this->pluginName; ?>Options[hideGamesOnHomeWidgets]"<?php if($this->options['hideGamesOnHomeWidgets']=='on') echo ' checked'; ?>/> Also prevent widgets from showing games on the home page
		</p>
		<p>
			Games can still be accessed through categories, with the category stub "flash-games" containing all of them as sub categories.<br/>
		</p>
		<?php
	}
	public function setMAAPPW()
	{
		$blogUrl = get_bloginfo('url', 'raw');
		?>
		
		<p>
		<script type="text/javascript">
		function updateMochiLink(updateTo)
		{
			var updateThis = document.getElementById('mochiLink');
			var newVal = escape(updateTo.value);
			newVal = sanitize(newVal);
			updateThis.value = '<?php echo $blogUrl.'/?maappw=';?>' + newVal;
		}
		function selectMochiLink(updateTo)
		{
			var updateThis = document.getElementById('mochiLink');
			var newVal = escape(updateTo.value);
			newVal = sanitize(newVal);
			updateThis.value = '<?php echo $blogUrl.'/?maappw=';?>' + newVal;
			updateThis.select();
		}
		function sanitize(someText)
		{
			someText = someText.replace(/%20/g,'-');
			someText = someText.replace(/\*/g,'2A');
			someText = someText.replace(/@/g,'40');
			someText = someText.replace(/\//g,'2F');
			someText = someText.replace(/\+/g,'2B');
			someText = someText.replace(/%/g, '');
			return someText;
		}
		</script>
			<input type="text" id="maappw" name="<?php echo $this->pluginName.'Options[maappw]';?>" value="<?php echo $this->options['maappw'];?>" onblur="selectMochiLink(this);" onkeyup="updateMochiLink(this);"/>
		</p>
		<p>This is a unique password to prevent unauthorized users from adding mochi games to your games queue.</p>

		<?php
		$uri = $blogUrl.'/?maappw=';
		$uri .= $this->parent->theMochiAdminMenu->_sanitize_title(rawurlencode($this->options['maappw']));
		?>
		<p>Copy and paste <br/><textarea rows="2" cols="100" name="Options[mochiLink]" id="mochiLink" readonly="readonly">
<?php echo $uri;?></textarea><br/> to your <a href="https://www.mochimedia.com/pub/settings" target="_blank">Mochimedia publisher settings</a> page auto post url textbox, and change Auto Post Method to `Custom built script`. <br/>(Don't forget to click save changes at the bottom of this page)</p>
		<p>
			If Mochimedia tells you there is a problem, or the games don't appear, check any spam blocking plugin, such as Bad Behavior for blocked access attempts from mochimedia.net.  Mochimedia uses a python library to make the request that Bad Behavior, among other plugins have blacklisted to help prevent hackers from accessing your site.  If you look through Bad Behavior's log, you should find the attempt from mochi, copy the IP address onto your Bad Behavior white list.  Mochi uses more than one of these IP addresses, so you may need to do this more than once.<br/>
			Two known mochimedia IPs are: 38.102.129.100, and 38.102.129.101<br/>
			It will be less secure, but if you don't want to bother with all of these IPs, you can add Python-urllib/2.7 to Bad Behavior's user-agent whitelist, that should unblock all mochi's servers (as well as anyone else using that library).  The security risk of doing this is small, known "bad" IP addresses are blacklisted as well, and the plugin checks for suspicious behavior, but it should be noted anyway, that the most secure method is just to whitelist those two IPs.
		</p>		
<?php
	}
	public function generalText()
	{
		echo '<p>Mochi Auto Post General Settings</p>';
	}
	public function postSwfIn()
	{
		?>
		<p>
			<input type="radio" name="<?php echo $this->pluginName; ?>Options[autoPostSWF]" value="page"<?php if($this->options['autoPostSWF']=='page') echo ' checked'; ?>/> Embed on post page
		</p>
		<p>
			<input type="radio" name="<?php echo $this->pluginName; ?>Options[autoPostSWF]" value="link"<?php if($this->options['autoPostSWF']=='link') echo ' checked'; ?>/> Link to SWF file on post page
		</p>
		<p>
			We recommend embedding the SWF on the page, as this allows the javascript for mochi bridge to be embeded as well, giving you access to high scores and other data/options through the mochi website.<br />
			Future versions of this plugin will also include options to save high scores for your visitors locally, which will require the bridge.  Right now it doesn't do much though.
		</p>
		<?php
	}
	public function publisherDataText()
	{
		echo '<p>Your Mochi Publisher details</p>';
	}
	public function publisherIDBox()
	{
		//create text box
		echo '<p><input id=\'mochiPublisherID\' name=\''.$this->pluginName.'Options[publisher_id]\' size=\'40\' type=\'text\' value=\''.$this->options['publisher_id'].'\' />';
		echo 'Your publisher ID from <a href="https://www.mochimedia.com/pub/settings" target="_blank">Mochimedia</a>';
		echo '<p>To allow communication of high scores and other data, you should also create a file in your website\'s root called crossdomain.xml and paste the following into it <br/>
		</p><p>
		<code>
		&lt;?xml version="1.0"?&gt;<br/>
	    &lt;!DOCTYPE cross-domain-policy SYSTEM "http://www.macromedia.com/xml/dtds/cross-domain-policy.dtd"&gt;<br/>
	    &lt;cross-domain-policy&gt;<br/>
	    &lt;allow-access-from domain="x.mochiads.com" /&gt;<br/>
		&lt;allow-access-from domain="www.mochiads.com" /&gt;<br/>
		&lt;allow-access-from domain="www.mochimedia.com" /&gt;<br/>
	    &lt;/cross-domain-policy&gt;
		</code>
		</p>';
		echo '<p>Or if you already have a crossdomain.xml file, modify it accordingly.  This will allow mochi bridge to function, but is not strictly necessary for the games themselves.</p>';
	}
	/*public function min_privs()
	{
		global $wp_roles;
		$all_roles = $wp_roles->roles;
		$editable_roles = apply_filters('editable_roles', $all_roles);
		$index=0;
		foreach($editable_roles as $key => $value)
		{


			?>
			<input type="checkbox" name="<?php echo $this->pluginName.'Options[min_privs]['.$key.']'; ?>" value="<?php echo $key; ?>"
				   checked=<?php echo $this->options['min_privs'][$key]; ?> "/>
				   <?php echo $key; ?>
			<?php
			$index++;
		}

	}*/
	public function deleteOptions()
	{
		delete_option($this->pluginName.'Options');
	}
	public function mochiAPValidate($input)
	{
		$output = $input;
		$output['minWidth'] = (int)$output['minWidth'];
		$output['maxWidth'] = (int)$output['maxWidth'];
		$output['screenThumbWidth'] = (int)$output['screenThumbWidth'];
		$output['screenThumbHeight'] = (int)$output['screenThumbHeight'];

		if($output['screenThumbWidth'] == 0)
			$output['screenThumbWidth'] = 32;
		if($output['screenThumbHeight'] == 0)
			$output['screenThumbHeight'] = 32;

		if($output['minWidth'] > $output['maxWidth'])
		{
			$output['minWidth'] = $output['maxWidth'];
		}
		return $output;
	}
}
?>
