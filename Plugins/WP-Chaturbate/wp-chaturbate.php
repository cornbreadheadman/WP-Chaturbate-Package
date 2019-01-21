<?php
/*
Plugin Name: WP Chaturbate
Plugin URI: https://github.com/cornbreadheadman/WP-Chaturbate-Package/Plugins/WP-Chaturbate
Description: Plugin to show chaturbate.com webcams on your Wordpress page
Version: 0.0.1
Author: CamUnderGround.Com
Author URI: http://camunderground.com/
License: GPL3
Text Domain: wp-chaturbate
*/

/**
 * wpchaturbatewidget widget class
 *
 * @since 2.8.0
 */
class WP_Widget_wpchaturbatewidget extends WP_Widget {

  function __construct() {
    $widget_ops = array('classname' => 'widget_wpchaturbatewidget', 'description' => __( "Chaturbate cams") );
    parent::__construct('WP_Widget_wpchaturbatewidget', __('Chaturbate cams'), $widget_ops);
    $this->alt_option_name = 'widget_wpchaturbatewidget';
  }

  function widget($args, $instance) {
    global $wpdb;

    extract($args);
    
    $output = "";
    $chaturbate_options = get_option("chaturbate_options");
    $chaturbate_options = unserialize($chaturbate_options);
    $r = rand(1,10);
    if ($r>1) $r=0;
    $affid = $chaturbate_options['affid'][$r];
    $track = $chaturbate_options['track'][$r];
    $program = $chaturbate_options['program'];
    if (!$program) $program = "revshare";
    
    if (!$affid) {
      $output = "<div class=\"chaturbateerror\">".__("Error: You haven't set your chaturbate.com affiliate ID. Go to admininstration panel and then Settings -> WP Chaturbate to set your preferences!", 'wp-chaturbate')."</div>";
    }
    else {
      $title = apply_filters('widget_title', $instance['title'], $instance, $this->id_base);
      if (empty($instance['number']) || !$number = absint($instance['number'])) $number = 5;
      $gender = $instance['gender'];
      if (empty($gender)) $gender = "a";
      $onlinecamsjson = get_chaturbate_json($affid);
      if (is_array($onlinecamsjson)) {
	$cams = get_online_cams($onlinecamsjson, $track, $number, "no", $affid, $program, "out", null, null);
	$output .= $cams[$gender];
      }
      else $content = __('Error getting data from chaturbate. Try again later.', 'wp-chaturbate');
    }
    
    echo $before_widget;
    if ($title) echo $before_title.$title.$after_title;
    echo $output;
    echo $after_widget;
  }

  function update( $new_instance, $old_instance ) {
    $instance = $old_instance;
    $instance['title'] = strip_tags($new_instance['title']);
    $instance['number'] = (int) $new_instance['number'];
    $instance['gender'] = strip_tags($new_instance['gender']);
    return $instance;
  }

  function form( $instance ) {
    $title = isset($instance['title']) ? esc_attr($instance['title']) : '';
    $number = isset($instance['number']) ? absint($instance['number']) : 5;
    $gender = isset($instance['gender']) ? esc_attr($instance['gender']) : 'a';
    ?>
    <p><label for="<?php echo $this->get_field_id('title'); ?>"><?php _e('Title:'); ?></label>
    <input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo $title; ?>" /></p>

    <p><label for="<?php echo $this->get_field_id('number'); ?>"><?php _e('Number of cams to show', 'wp-chaturbate'); ?>:</label>
    <input id="<?php echo $this->get_field_id('number'); ?>" name="<?php echo $this->get_field_name('number'); ?>" type="text" value="<?php echo $number; ?>" size="3" /></p>
    
    <p><label for="<?php echo $this->get_field_id('gender'); ?>"><?php _e('Show', 'wp-chaturbate'); ?> </label>
    <select id="<?php echo $this->get_field_id('gender'); ?>" name="<?php echo $this->get_field_name('gender'); ?>">
      <option value="a"<?php if ($gender=="a") echo " selected"; ?>><?php _e('All', 'wp-chaturbate'); ?></option>
      <option value="f"<?php if ($gender=="f") echo " selected"; ?>><?php _e('Female', 'wp-chaturbate'); ?></option>
      <option value="m"<?php if ($gender=="m") echo " selected"; ?>><?php _e('Male', 'wp-chaturbate'); ?></option>
      <option value="c"<?php if ($gender=="c") echo " selected"; ?>><?php _e('Couple', 'wp-chaturbate'); ?></option>
      <option value="s"<?php if ($gender=="s") echo " selected"; ?>><?php _e('Shemale', 'wp-chaturbate'); ?></option>
    </select> <?php _e('cams', 'wp-chaturbate'); ?>
    <?php
  }
}



function action_chaturbate_menu() {
  add_options_page('WP Chaturbate Options', 'WP Chaturbate', 'manage_options', 'wp_chaturbate_options', 'chaturbate_options');
}

// Function to creat admin page
function chaturbate_options() {
  if (!current_user_can('manage_options'))  {
    wp_die(__('You do not have sufficient permissions to access this page.', 'wp-chaturbate'));
  }
    // Read in existing option value from database
    $chaturbate_options = get_option("chaturbate_options");
    if(isset($_POST['chaturbate_affid'])) {
        $prev_chaturbate_options = $chaturbate_options;
        if ($prev_chaturbate_options) {
	  $prev_chaturbate_options = unserialize($prev_chaturbate_options);
	  $new_chaturbate_options = $prev_chaturbate_options;
	  $new_chaturbate_options['affid'][0] = $_POST['chaturbate_affid'];
	  $new_chaturbate_options['track'][0] = $_POST['chaturbate_track'];
	  $new_chaturbate_options['chatlocation'] = $_POST['chaturbate_chat_location'];
	  $new_chaturbate_options['signup_text'] = $_POST['chaturbate_text'];
	  $new_chaturbate_options['maxcams'] = $_POST['chaturbate_maxcams'];
	  $new_chaturbate_options['pager'] = $_POST['chaturbate_pager'];
	  $new_chaturbate_options['program'] = $_POST['chaturbate_program'];
	  $new_chaturbate_options['pretty_urls'] = $_POST['chaturbate_pretty_urls'];
	  $new_chaturbate_options['pretty_urls_front'] = $_POST['chaturbate_pretty_urls_front'];
	  $new_chaturbate_options['url_cam'] = $_POST['chaturbate_url_cam'];
	  $new_chaturbate_options['url_campage'] = $_POST['chaturbate_url_campage'];
	  $new_chaturbate_options['interval'] = $_POST['chaturbate_interval'];
	  $new_chaturbate_options['custom_css'] = $_POST['chaturbate_custom_css'];
	  $new_chaturbate_options['text_above'] = $_POST['chaturbate_text_above'];
	  $new_chaturbate_options['text_below'] = $_POST['chaturbate_text_below'];
	  $new_chaturbate_options['use_curl'] = $_POST['chaturbate_use_curl'];
        }
        else {
	  $new_chaturbate_options['affid'] = array($_POST['chaturbate_affid'], 'yX0Ue');
	  $new_chaturbate_options['track'] = array($_POST['chaturbate_track'], site_url());
	  $new_chaturbate_options['chatlocation'] = $_POST['chaturbate_chat_location'];
	  $new_chaturbate_options['signup_text'] = $_POST['chaturbate_text'];
	  $new_chaturbate_options['maxcams'] = $_POST['chaturbate_maxcams'];
	  $new_chaturbate_options['pager'] = $_POST['chaturbate_pager'];
	  $new_chaturbate_options['program'] = $_POST['chaturbate_program'];
	  $new_chaturbate_options['pretty_urls'] = $_POST['chaturbate_pretty_urls'];
	  $new_chaturbate_options['pretty_urls_front'] = $_POST['chaturbate_pretty_urls_front'];
	  $new_chaturbate_options['url_cam'] = $_POST['chaturbate_url_cam'];
	  $new_chaturbate_options['url_campage'] = $_POST['chaturbate_url_campage'];
	  $new_chaturbate_options['interval'] = $_POST['chaturbate_interval'];
	  $new_chaturbate_options['custom_css'] = $_POST['chaturbate_custom_css'];
	  $new_chaturbate_options['text_above'] = $_POST['chaturbate_text_above'];
	  $new_chaturbate_options['text_below']  =$_POST['chaturbate_text_below'];
	  $new_chaturbate_options['use_curl'] = $_POST['chaturbate_use_curl'];
	}
	// Save the posted value in the database
	$new_chaturbate_options=serialize($new_chaturbate_options);
        update_option('chaturbate_options', $new_chaturbate_options);
        $chaturbate_options=$new_chaturbate_options;

        // Put a settings saved message on the screen
?>
<div class="updated"><p><strong><?php _e('Settings saved', 'wp-chaturbate'); ?></strong></p></div>
<?php

    }
    $chaturbate_options=unserialize($chaturbate_options);
    
    echo '<div class="wrap">';
    echo "<h2>WP Chaturbate setting</h2>";
    
    if (!ini_get('allow_url_fopen')) {
      ?>
      <div class="error"><p><strong><?php _e('Error: allow_url_fopen is not enabled! You need to have allow_url_fopen enabled to use this plugin. Contact your web hosting service provider.', 'wp-chaturbate'); ?></strong></p></div>
      <?php
    }
    
    // Settings form
    ?>
<form name="form1" method="post" action="">

<p><b><?php _e('Affiliate ID', 'wp-chaturbate'); ?>:</b><br>
<input type="text" name="chaturbate_affid" value="<?php echo $chaturbate_options[affid][0]; ?>" size="20"><br>
<?php
$reg_url = 'http://chaturbate.com/affiliates/in/9O7D/yX0Ue/?track='.site_url();
printf(
  __(
    'Click <a href="%s" target="_blank">here</a> to register as chaturbate.com affiliate and get your affiliate ID. If you already have registered you can use your existing ID. If you do not know your affiliate ID, log in to your chaturbate.com account and go to Affiliate Program -> Linking Codes. The linking codes are something like this - http://chaturbate.com/affiliates/in/ZmU7/XXXXX/?track=default - where XXXXX is your affiliate ID.',
    'wp-chaturbate'
  ),
  $reg_url
);
?><br>
<br><b><?php _e('Tracking ID', 'wp-chaturbate'); ?>:</b><br>
<input type="text" name="chaturbate_track" value="<?php echo $chaturbate_options[track][0]; ?>" size="20"><br>
<?php _e('This is optional. If you leave it blank, "default" is used as tracking ID.', 'wp-chaturbate'); ?><br><br>
<b><?php _e('How to display the embedded chatroom?', 'wp-chaturbate'); ?></b><br>
<input type="radio" name="chaturbate_chat_location" id="chaturbate_chat_location1" value="inside"<?php if ($chaturbate_options[chatlocation]=="inside") echo " checked"; ?>><label for="chaturbate_chat_location1"><?php _e('Inside Wordpress page', 'wp-chaturbate'); ?></label><br>
<input type="radio" name="chaturbate_chat_location" id="chaturbate_chat_location2" value="overlay"<?php if ($chaturbate_options[chatlocation]!="inside") echo " checked"; ?>><label for="chaturbate_chat_location2"><?php _e('Overlay', 'wp-chaturbate'); ?></label><br>
<?php _e("For most Wordpress themes it's recommended to use the overlay method, because usually there isn't enough room to display the chatroom.", 'wp-chaturbate'); ?><br><br>
<b><?php _e('Chaturbate json download interval', 'wp-chaturbate'); ?>:</b><br>
<select name="chaturbate_interval">
<option value="none"<?php if ($chaturbate_options[interval]=="none") echo " selected"; ?>><?php _e('Do not cache the json file', 'wp-chaturbate'); ?></option>
<option value="everyminute"<?php if ($chaturbate_options[interval]=="everyminute") echo " selected"; ?>><?php _e('Every Minute (1min)', 'wp-chaturbate'); ?></option>
<option value="everythreeminutes"<?php if ($chaturbate_options[interval]=="everythreeminutes") echo " selected"; ?>><?php _e('Every Three Minutes (3min)', 'wp-chaturbate'); ?></option>
<option value="everyfiveminutes"<?php if ($chaturbate_options[interval]=="everyfiveminutes" || !$chaturbate_options[interval]) echo " selected"; ?>><?php _e('Every Five Minutes (5min)', 'wp-chaturbate'); ?></option>
<option value="everytenminutes"<?php if ($chaturbate_options[interval]=="everytenminutes") echo " selected"; ?>><?php _e('Every Ten Minutes (10min)', 'wp-chaturbate'); ?></option>
<option value="twicehourly"<?php if ($chaturbate_options[interval]=="twicehourly") echo " selected"; ?>><?php _e('Twice Hourly (30min)', 'wp-chaturbate'); ?></option>
<option value="hourly"<?php if ($chaturbate_options[interval]=="hourly") echo " selected"; ?>><?php _e('Once Hourly (60min)', 'wp-chaturbate'); ?></option>
</select>
<br><br>
<b><?php _e('Max number of cams to show on page', 'wp-chaturbate'); ?>:</b><br>
<input type="text" size="5" name="chaturbate_maxcams" value="<?php if($chaturbate_options[maxcams]) echo stripslashes($chaturbate_options[maxcams]); else echo "0"; ?>"><br>
<?php _e('Enter 0 to show all cams.', 'wp-chaturbate'); ?>
<br><br>
<b><?php _e('Display pager?', 'wp-chaturbate'); ?></b><br>
<input type="radio" name="chaturbate_pager" id="chaturbate_pager1" value="yes"<?php if ($chaturbate_options[pager]!="no") echo " checked"; ?>><label for="chaturbate_pager1"><?php _e('Yes', 'wp-chaturbate'); ?></label><br>
<input type="radio" name="chaturbate_pager" id="chaturbate_pager2" value="no"<?php if ($chaturbate_options[pager]=="no") echo " checked"; ?>><label for="chaturbate_pager2"><?php _e('No', 'wp-chaturbate'); ?></label><br>
<?php _e('If you have entered the max number of cams to show on page, you can select to show a numbered pager below the cams.', 'wp-chaturbate'); ?>
<br><br>
<b><?php _e('Program', 'wp-chaturbate'); ?></b><br>
<input type="radio" name="chaturbate_program" id="chaturbate_program1" value="pps"<?php if ($chaturbate_options[program]=="pps") echo " checked"; ?>><label for="chaturbate_program1"><?php _e('Pay per sign-up', 'wp-chaturbate'); ?></label><br>
<input type="radio" name="chaturbate_program" id="chaturbate_program2" value="revshare"<?php if ($chaturbate_options[program]!=="pps") echo " checked"; ?>><label for="chaturbate_program2"><?php _e('Revshare', 'wp-chaturbate'); ?></label>
<br><br>
<b><?php _e('Use pretty urls?', 'wp-chaturbate'); ?></b><br>
<input type="radio" name="chaturbate_pretty_urls" id="chaturbate_pretty_urls1" value="yes"<?php if ($chaturbate_options[pretty_urls]=="yes") echo " checked"; ?>><label for="chaturbate_pretty_urls1"><?php _e('Yes', 'wp-chaturbate'); ?></label><br>
<input type="radio" name="chaturbate_pretty_urls" id="chaturbate_pretty_urls2" value="no"<?php if ($chaturbate_options[pretty_urls]!="yes") echo " checked"; ?>><label for="chaturbate_pretty_urls2"><?php _e('No', 'wp-chaturbate'); ?></label><br>
<?php
printf(
  __(
    'If you select "yes", your urls will be like %1$s and %2$s',
    'wp-chaturbate'
  ),
  get_site_url().'/yourpage/cam/somename/',
  get_site_url().'/yourpage/campage/2/'
);
?>
<br>
<?php
printf(
  __(
    'If you select "no", your urls will be like %1$s and %2$s',
    'wp-chaturbate'
  ),
  get_site_url().'/yourpage/?cam=somename',
  get_site_url().'/yourpage/?campage=2'
);
?>
<br>
<?php _e('NB! For the pretty urls to work, you need to make sure you have set the correct permalink structure under Settings->Permalinks to Post name.', 'wp-chaturbate'); ?>
<br><br>
<input type="checkbox" name="chaturbate_pretty_urls_front" id="chaturbate_pretty_urls_front" value="yes"<?php if ($chaturbate_options['pretty_urls_front']=="yes") echo " checked"; ?>> <label for="chaturbate_pretty_urls_front"><?php _e('Use pretty urls also on front page', 'wp-chaturbate'); ?></label><br>
<?php _e('Check this only if your Chaturbate cams page is set to be front page and you want to use pretty urls there.', 'wp-chaturbate'); ?>
<br><br>
<b><?php _e('Change the structure of pretty urls', 'wp-chaturbate'); ?>:</b><br>
<?php _e('For single cam', 'wp-chaturbate'); ?>: <span style="font-style: italic;"><?php echo get_site_url(); ?>/yourpage/<input type="text"  size="10" name="chaturbate_url_cam" value="<?php if($chaturbate_options['url_cam']) echo stripslashes($chaturbate_options['url_cam']); else echo "cam"; ?>">/<?php _e('[name_of_performer]', 'wp-chaturbate'); ?>/</span><br>
<?php _e('For page numbers', 'wp-chaturbate'); ?>: <span style="font-style: italic;"><?php echo get_site_url(); ?>/yourpage/<input type="text"  size="10" name="chaturbate_url_campage" value="<?php if($chaturbate_options['url_campage']) echo stripslashes($chaturbate_options['url_campage']); else echo "campage"; ?>">/<?php _e('[page_number]', 'wp-chaturbate'); ?>/</span><br>
<br><br>
<b><?php _e('Dowloading the list of online cams', 'wp-chaturbate'); ?></b><br>
<input type="checkbox" name="chaturbate_use_curl" id="chaturbate_use_curl" value="yes"<?php if ($chaturbate_options['use_curl']=="yes") echo " checked"; ?>> <label for="chaturbate_use_curl"><?php _e('Use curl to download Chaturbate JSON', 'wp-chaturbate'); ?></label><br>
<?php _e('If you get the error "Error getting data from chaturbate. Try again later." then try turning this option on. Then the plugin uses curl instead of file_get_contents to download the list of online cams.', 'wp-chaturbate'); ?>
<br><br>
<b><?php _e("Link's text to show above chat application", 'wp-chaturbate'); ?>:</b><br>
<input type="text"  size="100" name="chaturbate_text" value="<?php if($chaturbate_options['signup_text']) echo stripslashes($chaturbate_options['signup_text']); else echo __("If you haven't registered yet, click here to start chatting. It's free and no e-mail required.", 'wp-chaturbate'); ?>"><br>
<?php _e('Optional. You can enter some text here to encourage users to sign up with chaturbate.com. If you’re using revshare, it links directly to Chaturbate’s join page and if you’re using pay per sign-up, then it links to Chaturbate’s home page (there is no affiliate link directly to join page for pay per sign-up program).', 'wp-chaturbate'); ?>
<br><br>
<b><?php _e('Custom CSS', 'wp-chaturbate'); ?>:</b><br>
<textarea rows="10" cols="30" name="chaturbate_custom_css"><?php if($chaturbate_options['affid']) echo stripslashes($chaturbate_options['custom_css']); else echo ""; ?></textarea><br>
<?php _e("Optional. If you want to change the appearance of the cam page, you can enter custom css here. This way your changes won't get lost if you update the plugin", 'wp-chaturbate'); ?>.
<br><br>
<b><?php _e('Custom text above chat application', 'wp-chaturbate'); ?>:</b><br>
<textarea rows="10" cols="30" name="chaturbate_text_above"><?php if($chaturbate_options['affid']) echo stripslashes($chaturbate_options['text_above']); else echo ""; ?></textarea><br>
<?php _e('Optional. You can enter some text to show above the chat application. You can use HTML here. And you can use the fields from Chaturbate JSON.', 'wp-chaturbate'); ?><br>
<?php _e('Fields you can use', 'wp-chaturbate'); ?>:<br>
* [username]<br>
* [display_name]<br>
* [room_subject]<br>
* [location]<br>
* [spoken_languages]<br>
* [birthday]<br>
* [age]<br>
* [is_hd] - "true" <?php _e('or', 'wp-chaturbate'); ?> "false"<br>
* [is_new] - "true" <?php _e('or', 'wp-chaturbate'); ?> "false"<br>
* [tags]<br>
* [seconds_online]<br>
* [gender]<br>
* [recorded] - "true" <?php _e('or', 'wp-chaturbate'); ?> "false"<br>
* [current_show] - "public", "private", "group", <?php _e('or', 'wp-chaturbate'); ?> "away"<br>
* [chat_room_url] -<?php _e(' A url with your affiliate code in it.', 'wp-chaturbate'); ?><br>
* [image_url]<br>
* [image_url_360x270]<br>
* [num_users] - <?php _e('Number of users currently viewing the room.', 'wp-chaturbate'); ?><br>
* [num_followers] - <?php _e('Number of users following the room.', 'wp-chaturbate'); ?><br>
<br>
<?php _e('For example you can enter', 'wp-chaturbate'); ?>:<br>
<?php
printf(
  __(
    '%s\'s bio',
    'wp-chaturbate'
  ),
  '[username]'
);
?>:<br>
<?php _e('Real name', 'wp-chaturbate'); ?>: [display_name]<br>
<?php _e('Location', 'wp-chaturbate'); ?>: [location]<br>
<?php _e('Age', 'wp-chaturbate'); ?>: [age]<br>
<br><br>
<b><?php _e('Custom text below chat application', 'wp-chaturbate'); ?>:</b><br>
<textarea rows="10" cols="30" name="chaturbate_text_below"><?php if($chaturbate_options['affid']) echo stripslashes($chaturbate_options['text_below']); else echo ""; ?></textarea><br>
<?php _e('Optional. You can enter some text to show below the chat application. You can use HTML and fields from Chaturbate JSON, same way like for the text above chat application.', 'wp-chaturbate'); ?><br>
</p><hr />

<p class="submit">
<input type="submit" name="Submit" class="button-primary" value="<?php esc_attr_e('Save Changes', 'wp-chaturbate') ?>" />
</p>

</form>

<?php _e('If you have saved you preferences, enter the following codes to your pages or posts where you want to display chaturbate.com cams', 'wp-chaturbate'); ?>:<br>
[chaturbate] - <?php _e('for all online cams', 'wp-chaturbate'); ?><br>
[chaturbate gender=f] - <?php _e('for female cams', 'wp-chaturbate'); ?><br>
[chaturbate gender=m] - <?php _e('for male cams', 'wp-chaturbate'); ?><br>
[chaturbate gender=c] - <?php _e('for couple cams', 'wp-chaturbate'); ?><br>
[chaturbate gender=s] - <?php _e('for shemale cams', 'wp-chaturbate'); ?><br>
<br>
<?php _e('You can also specify the number of cams to be shown. For example', 'wp-chaturbate'); ?>:<br>
[chaturbate gender=f maxcams=9] <?php _e('or', 'wp-chaturbate'); ?> [chaturbate maxcams=3]<br>
<?php _e('This will override the max number of cams option and disable the pager.', 'wp-chaturbate'); ?>
<br><br>
<?php _e('Or you can use php codes in your wordpress theme files to show the cams. The code to use is', 'wp-chaturbate'); ?>:<br>
&#60;?php wp-chaturbatecams("x",#); ?&#62;<br>
<?php _e('where x is a (for all cams), f (for female cams), m (for male cams), c (for couple cams) or s (for shemale cams). And # is the number of cams to show (Insert 0 to use number of cams set in the settings page).', 'wp-chaturbate'); ?>
<br><br>
<?php
printf(
  __(
    'You can read the full instructions and download the latest version of the plugin <a href="%s" target="_blank">here</a>.',
    'wp-chaturbate'
  ),
  'http://sexplugins.com/plugins/wp-chaturbate-plugin/'
);
?><br>


</div>

<?php
 
}

// Function to add css
function add_wpchaturbate_stylesheet() {
  $chaturbate_options = get_option("chaturbate_options");
  $chaturbate_options = unserialize($chaturbate_options);

  $myStyleUrl = plugins_url('style.css', __FILE__);
  $myStyleFile = WP_PLUGIN_DIR . '/wp-chaturbate/style.css';
  if ( file_exists($myStyleFile) ) {
    wp_register_style('WpChaturbateStyleSheets', $myStyleUrl);
    wp_enqueue_style('WpChaturbateStyleSheets');
  }
  
  if (array_key_exists('custom_css', $chaturbate_options)) {
    $customcss = wp_strip_all_tags($chaturbate_options['custom_css']);
    if ($customcss) wp_add_inline_style('WpChaturbateStyleSheets', $customcss);
  }
}

// Function to display embedded chat and cam
function view_cam($username, $affid, $track, $chatlocation, $text, $program, $text_above, $text_below) {
  if ($text_above) $text_above = '<br>'.nl2br(stripslashes($text_above)).'<br>';
  else $text_above = '';
  if ($text_below) $text_below = '<br>'.nl2br(stripslashes($text_below)).'<br>';
  else $text_below = '';
  if (($text_above && (strpos($text_above, '[') !== false || strpos($text_above, ']') !== false)) || ($text_below && (strpos($text_below, '[') !== false || strpos($text_below, ']') !== false))) {
    $onlinecamsjson = get_chaturbate_json($affid);
    if (is_array($onlinecamsjson)) {
      $model_info = chaturbate_model_info($onlinecamsjson, $username);
      if (is_object($model_info)) {
	foreach($model_info as $key => $value) {
	  $text_above = str_replace("[$key]", $value, $text_above);
	  $text_below = str_replace("[$key]", $value, $text_below);
	}
      }
    }
    $text_above = preg_replace('/(\[.+?\])/', '-', $text_above);
    $text_below = preg_replace('/(\[.+?\])/', '-', $text_below);
  }
  $b="";
  $program_code["pps"]["embed"]="Jrvi";
  $program_code["pps"]["link"]="ZQAI";
  $program_code["pps"]["signup"]="g4pe";
  $program_code["revshare"]["embed"]="9oGW";
  $program_code["revshare"]["link"]="dT8X";
  $program_code["revshare"]["signup"]="3Mc9";
  $signup_link = "http://chaturbate.com/affiliates/in/".$program_code[$program]["signup"]."/$affid/?track=$track";
  $text = "<strong><a href=\"$signup_link\" target=\"_blank\">$text</a></strong>";
  $chat_room_url="http://chaturbate.com/affiliates/in/".$program_code[$program]["link"]."/$affid/?track=$track&room=$username";
  $embedded_cam="<iframe src='//chaturbate.com/affiliates/in/".$program_code[$program]["embed"]."/$affid/?track=$track&room=$username&bgcolor=white' style='border: none; height: 535px; width: 100%;'></iframe>";
  $usernames_profile = sprintf(
    __(
      '%s\'s profile on chaturbate.com',
      'wp-chaturbate'
    ),
    $username
  );
  if ($chatlocation=="inside") $b='<a href="'.get_permalink().'">'.__('back', 'wp-chaturbate').'</a><h3>'.$username.'</h3><a href="'.$chat_room_url.'" target="_blank">'.$usernames_profile.'</a><br>'.$text.$text_above.$embedded_cam.$text_below;
  else $b='<div class="chaturbateshadow"></div><div class="chaturbatepopup"><div class="chaturbateclose"><a href="'.get_permalink().'">['.__('close', 'wp-chaturbate').']</a></div><h3>'.$username.'</h3><a href="'.$chat_room_url.'" target="_blank">'.$usernames_profile.'</a><br>'.$text.$text_above.$embedded_cam.$text_below.'</div>';
  if ($b=="") $b="<h3>".$username." ".__('is offline', 'wp-chaturbate')."</h3><a href=\"".get_permalink()."\">".__('Go back', 'wp-chaturbate')."!</a>";
  return $b;
}

$chaturbate_options = get_option("chaturbate_options");
$chaturbate_options = unserialize($chaturbate_options);
$use_pretty_urls = FALSE;
if ($chaturbate_options['pretty_urls'] == "yes") $use_pretty_urls = TRUE;
$use_pretty_urls_front = FALSE;
if ($chaturbate_options['pretty_urls_front'] == "yes") $use_pretty_urls_front = TRUE;
add_action( 'wp_loaded','chaturbate_flush_rules' );
if ($use_pretty_urls) {
  if ($use_pretty_urls_front) {
    add_filter('redirect_canonical', 'chaturbate_redirect_canonical', 10, 2);
    add_filter( 'rewrite_rules_array','chaturbate_insert_rewrite_rules_front' );
  }
  else add_filter( 'rewrite_rules_array','chaturbate_insert_rewrite_rules' );
}

function chaturbate_redirect_canonical($redirect_url, $requested_url) {
  if(is_front_page()) return $requested_url;
  else return $redirect_url;
}

function chaturbate_flush_rules() {
  $rules = get_option( 'rewrite_rules' );
  $singlecam_var = chaturbate_get_query_var_var("cam");
  $campage_var = chaturbate_get_query_var_var("campage");
  if ( ! isset( $rules['(.+)/'.$singlecam_var.'/(.+)$'] ) || ! isset( $rules['(.+)/'.$campage_var.'/(.+)$'] ) || ! isset( $rules[$singlecam_var.'/(.+)$'] ) || ! isset( $rules[$campage_var.'/(.+)$'] ) ) {
    global $wp_rewrite;
    $wp_rewrite->flush_rules();
  }
}

function chaturbate_insert_rewrite_rules( $rules ) {
  $singlecam_var = chaturbate_get_query_var_var("cam");
  $campage_var = chaturbate_get_query_var_var("campage");
  $newrules = array();
  $newrules['(.+)/'.$singlecam_var.'/(.+)$'] = 'index.php?pagename=$matches[1]&'.$singlecam_var.'=$matches[2]';
  $newrules['(.+)/'.$campage_var.'/(.+)$'] = 'index.php?pagename=$matches[1]&'.$campage_var.'=$matches[2]';
  return $newrules + $rules;
}

function chaturbate_insert_rewrite_rules_front( $rules ) {
  $singlecam_var = chaturbate_get_query_var_var("cam");
  $campage_var = chaturbate_get_query_var_var("campage");
  $newrules = array();
  $newrules['(.+)/'.$singlecam_var.'/(.+)$'] = 'index.php?pagename=$matches[1]&'.$singlecam_var.'=$matches[2]';
  $newrules['(.+)/'.$campage_var.'/(.+)$'] = 'index.php?pagename=$matches[1]&'.$campage_var.'=$matches[2]'; 
  $newrules[$singlecam_var.'/(.+)$'] = 'index.php?'.$singlecam_var.'=$matches[1]';
  $newrules[$campage_var.'/(.+)$'] = 'index.php?'.$campage_var.'=$matches[1]';
  return $newrules + $rules;
}

function chaturbate_add_query_arg($var, $value, $permalink) {
  $chaturbate_options = get_option("chaturbate_options");
  $chaturbate_options = unserialize($chaturbate_options);
  $use_pretty_urls = FALSE;
  if ($chaturbate_options['pretty_urls'] == "yes") $use_pretty_urls = TRUE;
  $use_pretty_urls_front = FALSE;
  if ($chaturbate_options['pretty_urls_front'] == "yes") $use_pretty_urls_front = TRUE;
  if (($use_pretty_urls && !is_front_page()) || ($use_pretty_urls && $use_pretty_urls_front && is_front_page())) {
    if ($chaturbate_options['url_'.$var]) $var = $chaturbate_options['url_'.$var];
    $url = $permalink.$var."/".$value."/";
  }
  else $url = add_query_arg($var, $value, $permalink);
  return $url;
}

function chaturbate_selfURL() { 
  $s = empty($_SERVER["HTTPS"]) ? '' : ($_SERVER["HTTPS"] == "on") ? "s" : ""; 
  $protocol = chaturbate_strleft(strtolower($_SERVER["SERVER_PROTOCOL"]), "/").$s; 
  $port = ($_SERVER["SERVER_PORT"] == "80") ? "" : (":".$_SERVER["SERVER_PORT"]); 
  return $protocol."://".$_SERVER['SERVER_NAME'].$port.$_SERVER['REQUEST_URI']; 
} 

function chaturbate_strleft($s1, $s2) {
  return substr($s1, 0, strpos($s1, $s2));
}

function chaturbate_get_query_var($var) {
  $value = &$_GET[$var];
  if (!$value) {
    $fullurl = chaturbate_selfURL();
    if (stripos($fullurl, $var)) {
      $permalink = get_permalink();
      $query = str_replace($permalink, "", $fullurl);
      $query = trim($query, "/");
      $query = explode("/", $query);
      $query = array_reverse($query);
      $get = array();
      foreach ($query as $k => $q) {
	if (($k % 2 == 0) && array_key_exists(($k + 1), $query) && $query[$k + 1]) $get[$query[$k + 1]] = $q;
      }
      $value = $get[$var];
    }
  }
  return $value;
}

function chaturbate_get_query_var_var($var) {
  $chaturbate_options = get_option("chaturbate_options");
  $chaturbate_options = unserialize($chaturbate_options);
  $use_pretty_urls = FALSE;
  if ($chaturbate_options['pretty_urls'] == "yes") $use_pretty_urls = TRUE;
  $use_pretty_urls_front = FALSE;
  if ($chaturbate_options['pretty_urls_front'] == "yes") $use_pretty_urls_front = TRUE;
  if ((($use_pretty_urls && !is_front_page()) || ($use_pretty_urls && $use_pretty_urls_front && is_front_page())) && $chaturbate_options['url_'.$var]) $var_var = $chaturbate_options['url_'.$var];
  else $var_var = $var;
  return $var_var;
}


// Function to show online cams with thumbnails
function get_online_cams($onlinecams, $track, $maxcams, $pager, $affid, $program, $linkto, $listofcams, $page_slug) {
  $b=array();
  $count['a'] = 0;
  $count['f'] = 0;
  $count['m'] = 0;
  $count['c'] = 0;
  $count['s'] = 0;
  $campage_var = chaturbate_get_query_var_var("campage");
  $campage = chaturbate_get_query_var($campage_var);
  if ($campage) $campage = intval($campage);
  else $campage = 1;
  $min = $campage * $maxcams - $maxcams + 1;
  $max = $min + $maxcams - 1;
  $linkstyle1 = ' style="font-size: 13px; color: #0A5A83; font: Arial; text-decoration: none; padding: 0; margin: 0; display: inline; line-height: 1.35; background: none;"';
  $linkstyle2 = ' style="color: white; font-size: 14px; font-weight: bold; text-shadow: 0.1em 0.1em 0.2em black; text-decoration: none; padding: 0; margin: 0; display: inline; line-height: 1.35; background: none;"';
  foreach($onlinecams as $cam) {
    $username=$cam->username;
    if ($listofcams && !in_array($username, $listofcams)) continue;
    if ($linkto == "in") {
      if ($page_slug) {
	$page_object = get_page_by_path(sanitize_text_field($page_slug));
	if ($page_object) $url = chaturbate_add_query_arg('cam', "$username", get_permalink($page_object->ID));
	else $url = chaturbate_add_query_arg('cam', "$username", get_permalink());
      }
      else $url = chaturbate_add_query_arg('cam', "$username", get_permalink());
      $target = "";
    }
    else {
      $program_code["pps"]["link"] = "ZQAI";
      $program_code["revshare"]["link"] = "dT8X";
      $url = "http://chaturbate.com/affiliates/in/".$program_code[$program]["link"]."/$affid/?track=$track&room=$username";
      $target = " target=\"_blank\"";
    }
    $image = $cam->image_url;
    $gender = $cam->gender;
    $viewers = $cam->num_users;
    $current_show = $cam->current_show;
    $recorded = $cam->recorded;
    if ($recorded == "True") $live = __("RECORDED", 'wp-chaturbate');
    else $live = __("LIVE", 'wp-chaturbate');
    $count['a']++;
    if ($maxcams == 0 || ($count['a'] >= $min && $count['a'] <= $max)) $b['a'] .= '<div class="chaturbatecamitem"><a'.$linkstyle1.' href="'.$url.'"'.$target.'><img src="'.$image.'"></a><div class="chaturbatecamlive"><a'.$linkstyle2.' href="'.$url.'"'.$target.'>'.$live.'</a></div><br><a'.$linkstyle1.' href="'.$url.'"'.$target.'>'.$username.'</a><br>'.__('Show:', 'wp-chaturbate').' '.$current_show.' | '.__('Viewers:', 'wp-chaturbate').' '.$viewers.'</div>';
    $count["$gender"]++;
    if ($maxcams==0 || ($count["$gender"]>=$min && $count["$gender"]<=$max)) $b["$gender"].='<div class="chaturbatecamitem"><a'.$linkstyle1.' href="'.$url.'"'.$target.'><img src="'.$image.'"></a><div class="chaturbatecamlive"><a'.$linkstyle2.' href="'.$url.'"'.$target.'>'.$live.'</a></div><br><a'.$linkstyle1.' href="'.$url.'"'.$target.'>'.$username.'</a><br>'.__('Show:', 'wp-chaturbate').' '.$current_show.' | '.__('Viewers:', 'wp-chaturbate').' '.$viewers.'</div>';
  }
  foreach($b as $key => $value) {
    $b[$key] = '<div style="clear: both;">'.$value.'</div><div style="clear: both;"></div>';
    if ($pager == "yes" && $maxcams > 0) {
      $numpages = ceil($count[$key] / $maxcams);
      $b[$key] .= '<div class="chaturbate_pager">'.__('Go to page:', 'wp-chaturbate').' ';
      for ($i = 1; $i <= $numpages; $i++) {
	if ($i == $campage) $b[$key] .= "<span class=\"chaturbate_pager_item_current\">$i</span> ";
	else {
	  $pagerurl = chaturbate_add_query_arg('campage', $i, get_permalink());
	  $b[$key] .= "<a href=\"$pagerurl\" class=\"chaturbate_pager_item\">$i</a> ";
	}
      }
      $b[$key].="</div>";
    }
  }
  return $b;
}

function chaturbate_model_info($onlinecams, $name) {
  $return = false;
  foreach($onlinecams as $cam) {
    $username = $cam->username;
    if ($username == $name) {
      $return = $cam;
      break;
    }
  }
  return $return;
}

// replace old shortcodes
function filter_wpchaturbate_old_shortcodes($content) {
  if (strpos($content, "[chaturbate")!==false) {
    $pattern = '/\[chaturbate[-]?([fmcs]?)[-]?([0-9]*)\]/i';
    preg_match_all($pattern, $content, $matches, PREG_SET_ORDER);
    foreach($matches as $match) {
      $maxcams = "";
      $gender = "";
      if (is_numeric($match[2])) {
	$maxcams=" maxcams=\"".$match[2]."\"";
      }
      if ($match[1]) $gender=" gender=\"".$match[1]."\"";
      $content = preg_replace($pattern, "[chaturbate$gender$maxcams]", $content, 1);
    }
  }
  return $content;
}

// replace [chaturbate*] with chaturbate content
function filter_chaturbate_cams($atts, $content = null) {
  $chaturbate_options = get_option("chaturbate_options");
  $chaturbate_options = unserialize($chaturbate_options);
  $r = rand(1,10);
  if ($r > 1) $r = 0;
  $affid = @$chaturbate_options['affid'][$r];
  $track = @$chaturbate_options['track'][$r];
  $maxcams = @$chaturbate_options['maxcams'];
  if (!$maxcams) $maxcams = 0;
  $pager = @$chaturbate_options['pager'];
  $program = @$chaturbate_options['program'];
  $text_above = @$chaturbate_options['text_above'];
  $text_below = @$chaturbate_options['text_below'];
  if (is_array($atts) && isset($atts['pagename'])) $page_slug = $atts['pagename'];
  else $page_slug = NULL;
  if (!$program) $program = "revshare";
  // If affiliate ID is not set
  if (!$affid) {
    $error = "<div class=\"chaturbateerror\">".__("Error: You haven't set your chaturbate.com affiliate ID. Go to admininstration panel and then Settings -> WP Chaturbate to set your preferences!", 'wp-chaturbate')."</div>";
    $contentreturn = $error;
  }
  else {
    if (!$track) $track = "default";
    // Single cam
    $singlecam_var = chaturbate_get_query_var_var("cam");
    $singlecam = chaturbate_get_query_var($singlecam_var);
    if ($singlecam) {
      $chatlocation = @$chaturbate_options['chatlocation'];
      if (@$chaturbate_options['signup_text']) $text = stripslashes(@$chaturbate_options['signup_text']);
      else $text = __("If you haven't registered yet, click here to sign up and start chatting. It's free and no e-mail required.", 'wp-chaturbate');
      $username = htmlentities(strip_tags(stripslashes($singlecam)), ENT_COMPAT, "UTF-8");
      $contentreturn = view_cam($username, $affid, $track, $chatlocation, $text, $program, $text_above, $text_below);
    }
    // List of cams
    else {
      $onlinecamsjson = get_chaturbate_json($affid);
      if (is_array($onlinecamsjson)) {
	if (is_array($atts) && isset($atts['maxcams'])) {
	  $maxcams = $atts['maxcams'];
	  $pager = "no";
	}
	if (is_array($atts) && isset($atts['gender'])) $gender = $atts['gender'];
	else $gender = "a";
	$listofcams = null;
	if ($content) {
	  $listofcams = trim(preg_replace('/<(\/)?(br|p)(\s+)?\/?>/i', "", $content));
	  $listofcams = preg_split('/\r\n|\r|\n/', $listofcams);
	  if (count($listofcams) == 1) $contentreturn = view_cam($listofcams[0], $affid, $track, $chatlocation, $text, $program, $text_above, $text_below);
	  else {
	    $cams = get_online_cams($onlinecamsjson, $track, $maxcams, $pager, $affid, $program, "in", $listofcams, $page_slug);
	    $contentreturn = $cams[$gender];
	  }
	}
	else {
	  $cams = get_online_cams($onlinecamsjson, $track, $maxcams, $pager, $affid, $program, "in", $listofcams, $page_slug);
	  $contentreturn = $cams[$gender];
	}
      }
      else $contentreturn = __("Error getting data from chaturbate. Try again later.", 'wp-chaturbate');
      
    }
  }
  return $contentreturn;  
}


function filter_wpchaturbate($content) {
  $singlecam_var = chaturbate_get_query_var_var("cam");
  $singlecam = chaturbate_get_query_var("cam");
  if ($singlecam) {
    if (strpos($content, "[chaturbate") !== false) {
      $chaturbate_options = get_option("chaturbate_options");
      $chaturbate_options = unserialize($chaturbate_options);
      $r = rand(1,10);
      if ($r>1) $r = 0;
      $affid = @$chaturbate_options['affid'][$r];
      $track = @$chaturbate_options['track'][$r];
      $maxcams = @$chaturbate_options['maxcams'];
      if (!$maxcams) $maxcams = 0;
      $pager = @$chaturbate_options['pager'];
      $program = @$chaturbate_options['program'];
      $text_above = @$chaturbate_options['text_above'];
      $text_below = @$chaturbate_options['text_below'];
      if (!$program) $program = "revshare";
      // If affiliate ID is not set
      if (!$affid) {
	$error = "<div class=\"chaturbateerror\">".__("Error: You haven't set your chaturbate.com affiliate ID. Go to admininstration panel and then Settings -> WP Chaturbate to set your preferences!", 'wp-chaturbate')."</div>";
	$pattern = '/\[chaturbate[-]?([fmcs]?)[-]?([1-9]*)\]/i';
	$content = preg_replace($pattern, $error, $content);
      }
      else {
	if (!$track) $track = "default";
	// Single cam
	$chatlocation = @$chaturbate_options['chatlocation'];
	if (@$chaturbate_options['signup_text']) $text = stripslashes(@$chaturbate_options['signup_text']);
	else $text = __("If you haven't registered yet, click here to sign up and start chatting. It's free and no e-mail required.", 'wp-chaturbate');
	$username = htmlentities(strip_tags(stripslashes($singlecam)), ENT_COMPAT, "UTF-8");
	$content = view_cam($username, $affid, $track, $chatlocation, $text, $program, $text_above, $text_below);

      }
    }
  }
  else add_shortcode('chaturbate', 'filter_chaturbate_cams');
  return $content;
}

function wpchaturbatecams($gender, $maxcams) {
  $atts = array();
  if ($maxcams && $maxcams != 0) $atts['maxcams'] = $maxcams;
  if ($gender) $atts['gender'] = $gender;
  echo filter_chaturbate_cams($atts);
}

function get_chaturbate_json($affid) {
    $myCBjsonfile = ABSPATH . '/wp-content/plugins/wp-chaturbate/chaturbatecams.json';
    $chaturbate_options=get_option("chaturbate_options");
    $chaturbate_options=unserialize($chaturbate_options);
    $interval=@$chaturbate_options['interval'];
    if (!$interval) $interval = "everyfiveminutes";
    $intervalseconds = array(
        'everytenseconds' => 10,
        'everythirtyseconds' => 30,
        'everyminute' => 60,
        'everythreeminutes' => 3 * 60,
        'everyfiveminutes' => 5 * 60,
        'everytenminutes' => 10 * 60,
        'twicehourly' => 30 * 60,
        'hourly' => 60 * 60
    );
    
    
    $json = FALSE;
    
    $fresh = TRUE;
    if ($interval == "none") $fresh = FALSE;
    else if (!file_exists($myCBjsonfile)) $fresh = FALSE;
    else if (filemtime($myCBjsonfile) < (time() - $intervalseconds[$interval] - 10)) $fresh = FALSE;
    if ($fresh) {
	$json = @file_get_contents($myCBjsonfile);
    }
    else {
      if ($interval != "none") {
	wp_chaturbate_do_download_json();
	if (file_exists($myCBjsonfile)) $json = @file_get_contents($myCBjsonfile);
	else $json = wp_chaturbate_get_contents('https://chaturbate.com/affiliates/api/onlinerooms/?format=json&wm='.$affid);
      }
      else $json = wp_chaturbate_get_contents('https://chaturbate.com/affiliates/api/onlinerooms/?format=json&wm='.$affid);
    }
    
    if ($json) return json_decode($json);
    else return FALSE;
}

// Add new cron schedule
add_filter( 'cron_schedules', 'wp_chaturbate_schedule' ); 
function wp_chaturbate_schedule( $schedules ) {
  $schedules['everyminute'] = array(
    'interval' => 60,
    'display' => __('Every Minute', 'wp-chaturbate')
  );
  $schedules['everythreeminutes'] = array(
    'interval' => 3 * 60,
    'display' => __('Every Three Minutes', 'wp-chaturbate')
  );
  $schedules['everyfiveminutes'] = array(
    'interval' => 5 * 60,
    'display' => __('Every Five Minutes', 'wp-chaturbate')
  );
  $schedules['everytenminutes'] = array(
    'interval' => 10 * 60,
    'display' => __('Every Ten Minutes', 'wp-chaturbate')
  );
  $schedules['twicehourly'] = array(
    'interval' => 30 * 60,
    'display' => __('Twice Hourly', 'wp-chaturbate')
  );
  return $schedules;
}

// Schedule new hook
add_action( 'wp', 'wp_chaturbate_download_json' );
// Check if the hook is scheduled - if not, schedule it.
function wp_chaturbate_download_json() {
  $chaturbate_options = get_option("chaturbate_options");
  $chaturbate_options = unserialize($chaturbate_options);
  $interval = $chaturbate_options['interval'];
  if (!$interval) $interval = "everyfiveminutes";
  $schedule = wp_get_schedule( 'wp_chaturbate_download_json' );
  if ($schedule && $interval == "none") wp_clear_scheduled_hook( 'wp_chaturbate_download_json' );
  else if ( $schedule != $interval ) {
    if ($schedule) wp_clear_scheduled_hook( 'wp_chaturbate_download_json' );
    wp_schedule_event( time(), $interval, 'wp_chaturbate_download_json');
  }
}

// Removed the scheduled hook upon deactivation of the plugin
register_deactivation_hook(__FILE__, 'wp_chaturbate_remove_schedule');
function wp_chaturbate_remove_schedule(){
  wp_clear_scheduled_hook('wp_chaturbate_download_json');
}

function wp_chaturbate_get_contents($url) {

  $chaturbate_options = get_option("chaturbate_options");
  $chaturbate_options = unserialize($chaturbate_options);
  $use_curl = FALSE;
  if (isset($chaturbate_options['use_curl']) && $chaturbate_options['use_curl'] == "yes") $use_curl = TRUE;

  if ($use_curl) {
  
    $ch = curl_init();

    curl_setopt($ch, CURLOPT_HEADER, 0);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
    curl_setopt($ch, CURLOPT_URL, $url);

    $data = curl_exec($ch);
    curl_close($ch);
  }
  else {
    $data = @file_get_contents($url);
  }

  return $data;
}

add_action( 'wp_chaturbate_download_json', 'wp_chaturbate_do_download_json' );
function wp_chaturbate_do_download_json() {
  $chaturbate_options = get_option("chaturbate_options");
  $chaturbate_options = unserialize($chaturbate_options);
  $affid = $chaturbate_options['affid'][0];
  $myCBjsonfile = ABSPATH . '/wp-content/plugins/wp-chaturbate/chaturbatecams.json';
  $myCBjsontmpfile = ABSPATH . '/wp-content/plugins/wp-chaturbate/chaturbatecams.tmp.json';
  set_time_limit(0);
  $url = 'https://chaturbate.com/affiliates/api/onlinerooms/?format=json&wm='.$affid;
  
  file_put_contents($myCBjsontmpfile, wp_chaturbate_get_contents($url));
  
  $json = @file_get_contents($myCBjsontmpfile);
  if ($json && (substr($json, 0, 1) == "[" || substr($json, 0, 1) == "{")) {
    rename($myCBjsontmpfile, $myCBjsonfile);
  }
  else {
    touch($myCBjsonfile);
    unlink($myCBjsontmpfile);
  }
}


// Function to change the title
add_filter('wpseo_title', 'filter_wpchaturbate_title', 999);
function filter_wpchaturbate_title($title) {
  $singlecam_var = chaturbate_get_query_var_var("cam");
  $singlecam = chaturbate_get_query_var($singlecam_var);
  if ($singlecam && (strpos($title, $singlecam) === false)) {
    $title = htmlentities(strip_tags(stripslashes($singlecam)), ENT_COMPAT, "UTF-8")." - ".$title;
  }
  return $title;
}

add_filter('document_title_parts', 'filter_wpchaturbate_title_parts', 10);
function filter_wpchaturbate_title_parts($title){
  $singlecam_var = chaturbate_get_query_var_var("cam");
  $singlecam = chaturbate_get_query_var($singlecam_var);
  if ($singlecam && (strpos($title['title'], $singlecam) === false)) {
    $title['title'] = htmlentities(strip_tags(stripslashes($singlecam)), ENT_COMPAT, "UTF-8")." - ".$title['title'];
  }
    
  return $title; 
}


global $wp_query;

function chaturbate_force_404() {
    global $singlecam;
    global $wp_query;
    global $post;
    if (strpos($post->post_content, "[chaturbate")!==false) {
	$chaturbate_options = get_option("chaturbate_options");
	$chaturbate_options = unserialize($chaturbate_options);
	$r = rand(1,10);
	if ($r>1) $r=0;
	$affid = $chaturbate_options['affid'][$r];
	$online = false;
	$onlinecamsjson = get_chaturbate_json($affid);
	if (is_array($onlinecamsjson)) {
	  $model_info = chaturbate_model_info($onlinecamsjson, $singlecam);
	  if (is_object($model_info)) {
	    $online = true;
	  }
	}
	if (!$online) {
	  $wp_query->set_404();
	  status_header( 404 );
	  nocache_headers();
	  include( get_query_template( '404' ) );
	  die();
	}
    }
}

$singlecam_var = chaturbate_get_query_var_var("cam");
$singlecam = chaturbate_get_query_var($singlecam_var);
if ($singlecam) {
  add_action('wp', 'chaturbate_force_404');
  remove_action('wp_head', 'rel_canonical');
  remove_action('template_redirect', 'wp_shortlink_header', 11);
  remove_action('wp_head', 'wp_shortlink_wp_head', 10, 0);
}

add_action('wp_enqueue_scripts', 'add_wpchaturbate_stylesheet');
add_filter('the_content', 'filter_wpchaturbate_old_shortcodes');
add_filter('the_content', 'filter_wpchaturbate');
// add_filter('wp_title', 'filter_wpchaturbate_title');
add_action('admin_menu', 'action_chaturbate_menu');

add_action('widgets_init', 'widget_wpchaturbatewidget_init');
function widget_wpchaturbatewidget_init() {
  register_widget('WP_Widget_wpchaturbatewidget');
}

// Localization
add_action('plugins_loaded', 'wpchaturbate_localization');
function wpchaturbate_localization() {
  load_plugin_textdomain('wp-chaturbate', false, dirname( plugin_basename( __FILE__ ) ) . '/' );
}

?>
