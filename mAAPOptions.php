<?php
/*
 * This class will create, and operate the options forms for the Mochi
 * Arcade Auto Post plugin. As well as provide a variable to access them.
 */
class mAAPOptions
{
	public $options;
	private $pluginName;
	public function mAAPOptions($pluginName) //mAAPOptions constructor
	{
		//initializes the plugin by adding actions and filters
		//get current options.
		$this->pluginName = $pluginName;
		$this->options = get_option($this->pluginName.'Options');
		$this->options['initialized'] = true;
		if(!array_key_exists('autoPostSWF', $this->options))
			$this->options['autoPostSWF'] = 'page';
		if(!array_key_exists('publisher_id', $this->options))
			$this->options['publisher_id'] = 'paste your mochi publisher id here';
		if(!array_key_exists('maappw', $this->options))
			$this->options['maappw'] = 'password';
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

		add_settings_field('maappw',
						   '<strong>password</strong>',
							array(&$this, 'setMAAPPW'),
							$this->pluginName.'OptionsPage',
						   'mochiAPGeneral');

//		add_settings_field('min_privs',				//field ID
//						   'Who can manage games?',			//field title
//						   array(&$this, 'min_privs'),	//callback to display input box
//						   $this->pluginName.'OptionsPage',	//Page ID
//						   'mochiAPPublisherData');			//Section ID
	}
	public function setMAAPPW()
	{
		?>
		<p>
			<input type="text" id="maappw" name="<?php echo $this->pluginName.'Options[maappw]';?>" value="<?php echo $this->options['maappw'];?>" />
		</p>
		<p>This is a unique password to prevent unauthorized users from adding mochi games to your games queue.</p>

		<?php
		$uri = plugins_url('mochiArcadeAutoPost.php', dirname(__FILE__));
		$uri .= '/mochi-arcade-auto-post/mochiArcadeAutoPost.php?maappw=';
		$uri .= $this->options['maappw'];
		?>
		<p>Copy and paste <code><?php echo $uri;?></code> to your <a href="https://www.mochimedia.com/pub/settings">Mochimedia publisher settings</a> page auto post url textbox, and change Auto Post Method to `Custom built script`.</p>
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
		echo 'Your publisher ID from <a href="https://www.mochimedia.com/pub/settings">Mochimedia</a>';
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
		//currently does nothing
		//will eventually check for valid publisher ID
		return $input;
	}
}
?>
