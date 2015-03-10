<?php
load_plugin_textdomain('fl3r-feelbox', NULL, dirname(plugin_basename(__FILE__)) . "/languages");

define('feelbox_CSS_DEFAULT', feelbox_PLUGIN_DIR . '/css/style-custom.css');
define('feelbox_CSS_FILE', WP_CONTENT_DIR . '/uploads/feelbox-custom.css');
define('feelbox_CSS_URI', WP_CONTENT_URL . '/uploads/feelbox-custom.css');


// duration: can be a string like "1 DAY", "30 DAY", "7 DAY", etc. 
// If blank string or null then remove the interval from the SQL statement completely.
function feelbox_get_most_clicked_sql( $duration, $limit ) {
	global $wpdb;

	$intervalstring = ($duration) ? " AND (day + INTERVAL " . $duration . ") >= NOW()" : "";

	return "SELECT post_ID, (SUM(emotion_1)) as emotion_1,  (SUM(emotion_2)) as emotion_2, (SUM(emotion_3)) as emotion_3, (SUM(emotion_4)) as emotion_4, (sum(emotion_5)) as emotion_5, (sum(emotion_6)) as emotion_6, ((SUM(emotion_1)) + (SUM(emotion_2)) + (SUM(emotion_3)) + (SUM(emotion_4)) + (SUM(emotion_5)) + (SUM(emotion_6))) AS total FROM ( SELECT post_ID, SUM(votes) as emotion_1, 0 as emotion_2, 0 as emotion_3, 0 as emotion_4, 0 as emotion_5, 0 as emotion_6 FROM {$wpdb->prefix}lydl_poststimestamp WHERE emotion=1" . $intervalstring . " group by post_ID UNION SELECT post_ID, 0, SUM(votes) as emotion_2, 0, 0, 0, 0 from {$wpdb->prefix}lydl_poststimestamp WHERE emotion=2" . $intervalstring . " group by post_ID UNION SELECT post_ID, 0, 0, SUM(votes) as emotion_3, 0, 0, 0 from {$wpdb->prefix}lydl_poststimestamp WHERE emotion=3" . $intervalstring . " group by post_ID UNION SELECT post_ID, 0, 0, 0, SUM(votes) as emotion_4, 0, 0 from {$wpdb->prefix}lydl_poststimestamp WHERE emotion=4" . $intervalstring . " group by post_ID UNION SELECT post_ID, 0, 0, 0, 0, SUM(votes) as emotion_5, 0 from {$wpdb->prefix}lydl_poststimestamp WHERE emotion=5" . $intervalstring . " group by post_ID UNION SELECT post_ID, 0, 0, 0, 0, 0, SUM(votes) as emotion_6 from {$wpdb->prefix}lydl_poststimestamp WHERE emotion=6" . $intervalstring . " group by post_ID) as test group by post_ID ORDER BY total desc LIMIT " . $limit;
}

function feelbox_get_moods_sql( $i, $limit ) {

	global $wpdb;

	return "SELECT ID, emotion_" . $i . " as emoted, 
		(emotion_1 + emotion_2 + emotion_3 + emotion_4 + emotion_5 + emotion_6
		) AS total, (
		  emotion_" . $i . " / (
		  SELECT CASE WHEN total=0
		  THEN 0.1
		  ELSE total
		  END 
		) *100
		) AS ranking, (
		  emotion_" . $i . " / (
		  SELECT CASE WHEN total=0
		  THEN 0.1
		  ELSE total
		  END 
		) * (emotion_" . $i . ")
		) AS weighted
		FROM {$wpdb->prefix}lydl_posts WHERE (emotion_" . $i . ">0 AND emotion_1 + emotion_2 + emotion_3 + emotion_4 + emotion_5 + emotion_6>1) 
		ORDER BY weighted DESC , ranking DESC , total DESC LIMIT " . $limit;

}

class feelbox_Widget extends WP_Widget {
	function feelbox_Widget() {
		// widget actual processes
		$widget_ops = array('classname' => 'feelbox_widget', 'description' => 'The most emotional posts from your FeelBox. It helps you and your users to know the posts that the users more involved.' );
		$this->WP_Widget('feelbox_widget', 'FL3R FeelBox Widget', $widget_ops);		
	}

	function form($instance) {
	
		global $moods;
		
		// outputs the options form on admin
		$title = ($instance['title'] == '') ? 'Most emotional posts' : esc_attr($instance['title']);
		$duration = ($instance['duration'] == '') ? '7 DAY' : esc_attr($instance['duration']);
		
		if ( !$number = (int) $instance['number'] )
                $number = 4;
            elseif ( $number < 1 )
                $number = 1;
            elseif ( $number > 10 )
                $number = 10;		
		?>

<p>
  <label for="<?php echo $this->get_field_id('title'); ?>">
    <?php _e('Title:', 'fl3r-feelbox'); ?>
    <input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo attribute_escape($title); ?>" />
  </label>
</p>
<p>
  <label for="<?php echo $this->get_field_id('duration'); ?>">
    <?php _e('Show:', 'fl3r-feelbox'); ?>
    <select id="<?php echo $this->get_field_id('duration'); ?>" name="<?php echo $this->get_field_name('duration'); ?>">
      <option value="<?php _e('365 DAYS', 'fl3r-feelbox'); ?>"<?php echo ($duration == '365 DAY') ? ' selected="selected"' : ''; ?>>
      <?php _e('The past year', 'fl3r-feelbox'); ?>
      </option>
      <option value="<?php _e('30 DAY', 'fl3r-feelbox'); ?>"<?php echo ($duration == '30 DAY') ? ' selected="selected"' : ''; ?>>
      <?php _e('The past 30 days', 'fl3r-feelbox'); ?>
      </option>
      <option value="<?php _e('7 DAY', 'fl3r-feelbox'); ?>"<?php echo ($duration == '7 DAY') ? ' selected="selected"' : ''; ?>>
      <?php _e('The past 7 days', 'fl3r-feelbox'); ?>
      </option>
      <option value="<?php _e('1 DAY', 'fl3r-feelbox'); ?>"<?php echo ($duration == '1 DAY') ? ' selected="selected"' : ''; ?>>
      <?php _e('The past day', 'fl3r-feelbox'); ?>
      </option>
      <?php for ($i=1; $i<=6; $i=$i+1) { ?>
      <option value="MOOD-<?php echo $i; ?>"<?php echo ($duration == 'MOOD-' . $i ) ? ' selected="selected"' : ''; ?>>
      <?php _e('Post most', 'fl3r-feelbox'); ?>
      "<?php echo $moods[$i]; ?>"</option>
      <?php } ?>
    </select>
  </label>
</p>
<p>
  <label for="<?php echo $this->get_field_id('number'); ?>">
    <?php _e('Number of posts to display:', 'fl3r-feelbox'); ?>
  </label>
  <input id="<?php echo $this->get_field_id('number'); ?>" name="<?php echo $this->get_field_name('number'); ?>" type="text" value="<?php echo $number; ?>" size="3" />
  <br />
  <small>
  <?php _e('(at most 10)', 'fl3r-feelbox'); ?>
  </small> </p>
<p>
<fieldset style="width:214px; padding:5px;"  class="widefat">
  <legend>
  <?php _e('Thumbnail settings', 'fl3r-feelbox'); ?>
  </legend>
  <input type="checkbox" class="checkbox" name="<?php echo $this->get_field_name( 'thumbnail-active' ); ?>" <?php echo ($instance['thumbnail']['active']) ? 'checked="checked"' : ''; ?> id="<?php echo $this->get_field_id('thumbnail-active'); ?>">
  <label for="<?php echo $this->get_field_id('thumbnail-active'); ?>">
    <?php _e('Display post thumbnail. Check and save to change the size.', 'fl3r-feelbox'); ?>
  </label>
  <br>
  <?php if($instance['thumbnail']['active']) : ?>
  <label for="<?php echo $this->get_field_id( 'thumbnail-width' ); ?>">
    <?php _e('Width:', 'fl3r-feelbox'); ?>
  </label>
  <input id="<?php echo $this->get_field_id( 'thumbnail-width' ); ?>" name="<?php echo $this->get_field_name( 'thumbnail-width' ); ?>" value="<?php echo  $instance['thumbnail']['width']; ?>"  class="widefat" style="width:30px!important" />
  px <br />
  <label for="<?php echo $this->get_field_id( 'thumbnail-height' ); ?>">
    <?php _e('Height:', 'fl3r-feelbox'); ?>
  </label>
  <input id="<?php echo $this->get_field_id( 'thumbnail-height' ); ?>" name="<?php echo $this->get_field_name( 'thumbnail-height' ); ?>" value="<?php echo  $instance['thumbnail']['height']; ?>"  class="widefat" style="width:30px!important" />
  px<br />
  <?php endif; ?>
</fieldset>
</p>
<?php
	}

    function update( $new_instance, $old_instance ) {
    
        $instance = $old_instance;
        $instance['title'] = strip_tags($new_instance['title']);
        $instance['number'] = (int) $new_instance['number'];
        $instance['duration'] = $new_instance['duration'];
		$instance['thumbnail']['active'] = $new_instance['thumbnail-active'];				
		$instance['thumbnail']['width'] = is_numeric($new_instance['thumbnail-width']) ? $new_instance['thumbnail-width'] : 15;
		$instance['thumbnail']['height'] = is_numeric($new_instance['thumbnail-height']) ? $new_instance['thumbnail-height'] : 15;


        return $instance;
    }

	function widget($args, $instance) {
		// outputs the content of the widget
		
		global $wpdb; 
		global $nothumb;
		
		extract($args);
		$title = empty($instance['title']) ? ' ' : apply_filters('widget_title', $instance['title']);
		$duration = ($instance['duration'] == '') ? '7 DAY' : esc_attr($instance['duration']);
		if ( !$number = (int) $instance['number'] )
                $number = 4;
            elseif ( $number < 1 )
                $number = 1;
            elseif ( $number > 10 )
                $number = 10;	
		
		if ( substr($instance['duration'], 0, 5) == 'MOOD-' ) {
			$i = substr($instance['duration'], -1);
			$objs = $wpdb->get_results( feelbox_get_moods_sql( $i, $number ) );
		} else {
			$duration = ($instance['duration'] == '') ? '7 DAY' : esc_attr($instance['duration']);
			$objs = $wpdb->get_results( feelbox_get_most_clicked_sql( $duration, $number ) );
		}
		
		echo $before_widget;

		if (sizeof($objs) > 0) {
			if ( !empty( $title ) ) { 
				echo $before_title . $title . $after_title; 
			}
			echo '<ul>';
			foreach ($objs as $obj) {

			
				// echo '<div class="panel">';
				// if (function_exists( 'get_the_image' )) { get_the_image( $arraydefaults ); }
				
				// get thumbnail
				if ($instance['thumbnail']['active']) {
					$tbWidth = $instance['thumbnail']['width'];
					$tbHeight = $instance['thumbnail']['height'];
					
					
					if (!function_exists('get_the_post_thumbnail')) { // if the Featured Image is not active, show default thumbnail

						$thumb = "<a href=\"".get_permalink($obj->post_ID)."\" class=\"mdt-nothumb\" title=\"". $title_attr ."\"><img src=\"". $nothumb . "\" alt=\"".$title_attr."\" border=\"0\" class=\"wpp-thumbnail\" width=\"".$tbWidth."\" height=\"".$tbHeight."\" "."/></a>";
					} else {
					
						if (has_post_thumbnail( $obj->post_ID )) { // if the post has a thumbnail, get it
							$thumb = "<a href=\"".get_permalink( $obj->post_ID )."\" title=\"". $title_attr ."\">" . get_the_post_thumbnail($obj->post_ID, array($tbWidth, $tbHeight), array('class' => 'mdt-thumbnail', 'alt' => $title_attr, 'title' => $title_attr) ) . "</a>";
						} else { // try to generate a post thumbnail from first image attached to post. If it fails, use default thumbnail
							$thumb = "<a href=\"".get_permalink($obj->post_ID)."\" title=\"". $title_attr ."\">" . generate_post_thumbnail($obj->post_ID, array($tbWidth, $tbHeight), array('class' => 'mdt-thumbnail', 'alt' => $title_attr, 'title' => $title_attr) ) ."</a>";
						}

					}
				}				
				

								
				echo '<li>' . $thumb . '<a href="' . get_permalink($obj->post_ID) . '">' . get_the_title($obj->post_ID) . '</li></a>';
			}
			echo '</ul>';
		} else {
		}		
		
		echo $after_widget;
	}
}

// Generates a featured image from the first image attached to a post if found.
// Otherwise, returns default thumbnail
function generate_post_thumbnail($id, $dimensions, $atts) {
	global $nothumb;

	// get post attachments
	$attachments = get_children(array('post_parent' => $id, 'post_type' => 'attachment', 'post_mime_type' => 'image', 'orderby' => 'menu_order'));
	
	// no images have been attached to the post, return default thumbnail
	if ( !$attachments ) return "<img src=\"". $nothumb . "\" alt=\"". $atts['alt'] ."\" border=\"0\" class=\"". $atts['class'] ."\" width=\"". $dimensions[0] ."\" height=\"". $dimensions[1] ."\" "."/>";
	
	$count = count($attachments);
	$first_attachment = array_shift($attachments);			
	$img = wp_get_attachment_image($first_attachment->ID);
				
	if (!empty($img)) { // found an image, use it as Featured Image
		update_post_meta( $id, '_thumbnail_id', $first_attachment->ID );
		return get_the_post_thumbnail($id, $dimensions, $atts);
	} else { // no images have been found, return default thumbnail
		return "<img src=\"". $nothumb . "\" alt=\"". $atts['alt'] ."\" border=\"0\" class=\"". $atts['class'] ."\" width=\"". $dimensions[0] ."\" height=\"". $dimensions[1] ."\" "."/>";
	}
	
}

function feelbox_wp_menu() {

	add_dashboard_page('FL3R FeelBox Dashboard', 'FL3R FeelBox', 'manage_options', 'feelbox', 'feelbox_dashboard_page', plugin_dir_url( __FILE__ ) . 'images/logo-admin.png');
	//fl3r: menu with icon
	//add_menu_page( 'FL3R FeelBox Dashboard', 'FL3R FeelBox X', 'manage_options', 'feelbox', 'feelbox_dashboard_page', plugin_dir_url( __FILE__ ) . 'images/logo-admin.png' );
	//fl3r.
	add_options_page('FL3R FeelBox Options', 'FL3R FeelBox', 'manage_options', 'feelbox', 'feelbox_settings_page');
}

// Add settings link on plugin page
function feelbox_settings_link($links) { 
  $settings_link = '<a href="options-general.php?page=feelbox">Settings</a>';
  array_unshift($links, $settings_link); 
  return $links; 
}

function feelbox_settings_page() {
	global $wpdb;
	global $moods;
	global $customcss;
	
	$hidden_field_name = 'feelbox_submit_hidden';
	$options = get_option('feelbox_wp_options');

	// http://codex.wordpress.org/Adding_Administration_Menus
	
	if (!current_user_can('manage_options'))  {
		wp_die( __('You do not have sufficient permissions to access this page.') );
	} ?>
<style>
	.selected { font-weight:bold; color:#000; text-decoration: none; }
	.content { display:none; }
	#settings { display:block; }
	</style>
<script type="text/javascript">

	jQuery(document).ready(function(){
	
	
		if ( window.location.hash ) {
			var str = window.location.hash.split('#')[1];
			// console.log(str);

			jQuery('a[rel="' + str + '"]').addClass("nav-tab-active").siblings().removeClass("nav-tab-active");
			jQuery(".content:visible").css("display","none");
			jQuery("#"+str).css("display", "block");
		}
	
		jQuery(".nav-tab").click(function(){
			
			var activeTab = jQuery(this).attr("rel");
			if ( typeof(activeTab) != 'undefined' ) {
				jQuery(this).addClass("nav-tab-active").siblings().removeClass("nav-tab-active");
				jQuery(".content:visible").fadeOut("fast", function(){
					jQuery("#"+activeTab).slideDown("fast");
				});
				
				return false;
			}

		});	

		if (jQuery('input[name="bypasscss"]').is(':checked')) { jQuery('#csswrapper').hide(); } 
		
		jQuery('input[name="bypasscss"]').click(function(){
			// jQuery('#cssbox').prop("disabled", this.checked);
			jQuery('#csswrapper').css("display", (this.checked) ? "none" : "block");
		});
					
	});	

	</script>
<?php 
	
		if( isset($_POST[ $hidden_field_name ]) && $_POST[ $hidden_field_name ] == 'Y' ) {
		
			if ( trim($_POST[ 'cleardata' ] == 'DELETE') ) {
				feelbox_reset_moods();		
			} else {
		
				$options['showsparkbar'] 			= $_POST[ 'showsparkbar' ];
				$options['sortmoods']				= $_POST[ 'sortmoods' ];
				$options['showinpostsondefault'] 	= $_POST[ 'showinpostsondefault' ];
				$options['bypasscss']				= $_POST[ 'bypasscss' ];				
				$options['showtweetfollowup'] 		= ( $_POST[ 'showtweetfollowup' ] ) ? $_POST[ 'showtweetfollowup' ] : 'off' ;

				@file_put_contents(feelbox_CSS_FILE, stripslashes( $_POST[ 'cssbox' ] ) );
				
				if ($options['showinpostsondefault'] == 'on') {
					add_filter('the_content', 'add_feelbox_widget_to_posts');
				}
				
				update_option('feelbox_wp_options', $options);
				

					echo '<div class="updated"><p><strong>';
					_e("Settings saved. (◕‿◕)","fl3r-feelbox");
					echo '</strong></p></div>';
	
			}
		}

	?>
<div class="wrap">
  <h2 class="nav-tab-wrapper">FL3R FeelBox <a class="nav-tab" href="<?php echo admin_url('index.php?page=feelbox'); ?>">
    <?php _e("Stats","fl3r-feelbox");?>
    </a> <a class="nav-tab nav-tab-active" rel="settings" href="#">
    <?php _e("Settings","fl3r-feelbox");?>
    </a> <a class="nav-tab" rel="customize" href="#">
    <?php _e("Custom CSS","fl3r-feelbox");?>
    </a> <a class="nav-tab" rel="faq" href="#">
    <?php _e("FAQ","fl3r-feelbox");?>
    </a> <a class="nav-tab" rel="credits" href="#">
    <?php _e("Credits","fl3r-feelbox");?>
    </a> <a class="nav-tab" rel="donate" href="#">
    <?php _e("Donate","fl3r-feelbox");?>
    </a></h2>
  <div class="container">
    <div class="content" id="settings">
      <form id="options" method="post">
      <h3 style="clear:both;">
        <?php _e("Plugin settings","fl3r-feelbox");?>
      </h3>
      <table class="form-table">
        <tbody>
          <tr valign="top">
            <th scope="row"><?php _e("Graphical bar","fl3r-feelbox");?></th>
            <td><input type="checkbox" id="showsparkbar" name="showsparkbar" <?php if ( $options["showsparkbar"]=='on' ) { echo 'checked="true"'; } ?>>
              <label for="showsparkbar">
                <?php _e("Show the graphical bar graph above moods.","fl3r-feelbox");?>
              </label></td>
          </tr>
          <tr valign="top">
            <th scope="row"><?php _e("Sorting","fl3r-feelbox");?></th>
            <td><input type="checkbox" id="sortmoods" name="sortmoods" <?php if ( $options["sortmoods"]=='on' ) { echo 'checked="true"'; } ?>>
              <label for="sortmoods">
                <?php _e("Automatically sort the moods by popularity. I suggest you leave this box unchecked.","fl3r-feelbox");?>
              </label></td>
          </tr>
          <tr valign="top">
            <th scope="row"><?php _e("Automatic display","fl3r-feelbox");?></th>
            <td><input type="checkbox" id="showinpostsondefault" name="showinpostsondefault" <?php if ( $options["showinpostsondefault"]=='on' ) { echo 'checked="true"'; } ?>>
              <label for="showinpostsondefault">
                <?php _e("Automatically display the FeelBox at the end of each blog post.","fl3r-feelbox");?>
              </label>
              <p class="description">
                <?php _e("Only if this is unchecked, you can use the print_feelbox_widget() PHP function in your templates or use the [feelbox] shortcode to show the FeelBox where you want.","fl3r-feelbox");?>
              </p></td>
          </tr>
          <tr valign="top">
            <th scope="row"><?php _e("Social media sharing","fl3r-feelbox");?></th>
            <td><input type="checkbox" id="showtweetfollowup" name="showtweetfollowup" <?php if ( $options["showtweetfollowup"]=='on' ) { echo 'checked="true"'; } ?>>
              <label for="showtweetfollowup">
                <?php _e("Allow people to share on Twitter, Facebook and Google+ after voting.","fl3r-feelbox");?>
              </label></td>
          </tr>
        </tbody>
      </table>
      <h3>
        <?php _e("Delete all moods","fl3r-feelbox");?>
      </h3>
      <table class="form-table">
        <tbody>
          <tr valign="top">
            <th scope="row"><?php _e("Clear all data","fl3r-feelbox");?></th>
            <td><p>
                <input type="textbox" id="cleardata" name="cleardata">
              </p>
              <p>
                <?php _e("WARNING!","fl3r-feelbox");?>
              </p>
              <p>
                <?php _e("This option will clear all votes and all data in the Stats page. Normally this is an operation without risk, but to be safe make sure you","fl3r-feelbox");?>
                <a target="_blank" href="http://codex.wordpress.org/Backing_Up_Your_Database">back up</a>
                <?php _e("your database. This operation is irreversible. If you want to proceed, type <strong>DELETE</strong> in the textbox and click on Save changes button.","fl3r-feelbox");?>
              </p></td>
          </tr>
        </tbody>
      </table>
      <input type="hidden" name="<?php echo $hidden_field_name; ?>" value="Y">
      <p class="submit">
        <input type="submit" name="Submit" class="button-primary" value="<?php esc_attr_e('Save changes') ?>" />
      </p>
      <!-- /form -->
      <h3>
        <?php _e("Donate","fl3r-feelbox");?>
      </h3>
      <p>
        <?php _e("If you like this plugin, you consider making a small donation. Thanks.","fl3r-feelbox");?>
      </p>
      <p>
      <form action="https://www.paypal.com/cgi-bin/webscr" method="post" target="_top">
        <input type="hidden" name="cmd" value="_s-xclick">
        <input type="hidden" name="hosted_button_id" value="2PCZCTKZ86ANA">
        <input type="image" src="https://www.paypalobjects.com/en_US/GB/i/btn/btn_donateCC_LG.gif" border="0" name="submit" alt="PayPal – The safer, easier way to pay online.">
        <img alt="" border="0" src="https://www.paypalobjects.com/it_IT/i/scr/pixel.gif" width="1" height="1">
      </form>
      </p>
    </div>
    <div class="content" id="customize">
      <div id="csswrapper">
        <textarea style="width:100%;" rows="10" id="cssbox" name="cssbox"><?php echo @file_get_contents(feelbox_CSS_FILE); ?></textarea>
        <p class="description">
          <?php _e("Here you can customize your FeelBox Css. The file is stored in the same directory of original FeelBox style.css.","fl3r-feelbox");?>
        </p>
      </div>
      <table class="form-table">
        <tbody>
          <tr valign="top">
            <th scope="row"><?php _e("Bypass plugin CSS","fl3r-feelbox");?></th>
            <td><input type="checkbox" name="bypasscss" <?php if ( $options["bypasscss"]=='on' ) { echo 'checked="true"'; } ?>>
              <p class="description">
                <?php _e("Bypass the default and custom CSS used in the FeelBox plug-in and use CSS from your theme instead. This may be necessary for specific WordPress Multisite installations. Normally this option should be left unchecked.","fl3r-feelbox");?>
              </p></td>
          </tr>
        </tbody>
      </table>
      <p class="submit">
        <input type="submit" name="Submit" class="button-primary" value="<?php esc_attr_e('Save changes') ?>" />
      </p>
      <h3>
        <?php _e("Donate","fl3r-feelbox");?>
      </h3>
      <p>
        <?php _e("If you like this plugin, you consider making a small donation. Thanks.","fl3r-feelbox");?>
      </p>
      <p>
      <form action="https://www.paypal.com/cgi-bin/webscr" method="post" target="_top">
        <input type="hidden" name="cmd" value="_s-xclick">
        <input type="hidden" name="hosted_button_id" value="2PCZCTKZ86ANA">
        <input type="image" src="https://www.paypalobjects.com/en_US/GB/i/btn/btn_donateCC_LG.gif" border="0" name="submit" alt="PayPal – The safer, easier way to pay online.">
        <img alt="" border="0" src="https://www.paypalobjects.com/it_IT/i/scr/pixel.gif" width="1" height="1">
      </form>
      </p>
    </div>
    <div class="content" id="faq">
      <h3>
        <?php _e("FAQ","fl3r-feelbox");?>
      </h3>
      <h3>
        <?php _e("There is a FeelBox widget?","fl3r-feelbox");?>
      </h3>
      <p>
        <?php _e("Sure! You can find it in the widget page of your admin panel.","fl3r-feelbox");?>
      </p>
      <h3>
        <?php _e("Can I manually insert the FeelBox?","fl3r-feelbox");?>
      </h3>
      <p>
        <?php _e("If the \"Automatic display option\" is checked, the FeelBox will be automatically display on each  post, right before or after the comments.","fl3r-feelbox");?>
      </p>
      <p>
        <?php _e("But if you want, you can  use the WordPress shortcode","fl3r-feelbox");?>
        <code>[feelbox]</code>
        <?php _e("or add","fl3r-feelbox");?>
        <code>&lt;?php if ( function_exists('print_feelbox_widget') ) { print_feelbox_widget(); } ?&gt;</code>
        <?php _e("in your PHP template, usually on your comment template (comments.php).","fl3r-feelbox");?>
      </p>
      <p>
        <?php _e("Attention! Don't insert manually the FeelBox code if the Automatic display option is cecked: it may cause style errors and graphical glitch.","fl3r-feelbox");?>
      </p>
      <h3>
        <?php _e("Donate","fl3r-feelbox");?>
      </h3>
      <p>
        <?php _e("If you like this plugin, you consider making a small donation. Thanks.","fl3r-feelbox");?>
      </p>
      <p>
      <form action="https://www.paypal.com/cgi-bin/webscr" method="post" target="_top">
        <input type="hidden" name="cmd" value="_s-xclick">
        <input type="hidden" name="hosted_button_id" value="2PCZCTKZ86ANA">
        <input type="image" src="https://www.paypalobjects.com/en_US/GB/i/btn/btn_donateCC_LG.gif" border="0" name="submit" alt="PayPal – The safer, easier way to pay online.">
        <img alt="" border="0" src="https://www.paypalobjects.com/it_IT/i/scr/pixel.gif" width="1" height="1">
      </form>
      </p>
    </div>
    <div class="content" id="credits">
      <h3>
        <?php _e("Credits","fl3r-feelbox");?>
      </h3>
      <p>
        <?php _e("FeelBox is a plugin created by Armando \"FL3R\" Fiore. Dedicated to the beautiful girl who is always by my side, Anna Rita!","fl3r-feelbox");?>
      </p>
      <p>
        <?php _e("Thanks for using FeelBox, you helped a developer to increase his self-esteem!","fl3r-feelbox");?>
      </p>
      <h4>
        <?php _e("Copyright","fl3r-feelbox");?>
      </h4>
      <p>
        <?php _e("Copyright © Armando \"FL3R\" Fiore. All rights reserved. This software is provided as is, without any express or implied warranty. In no event shall the author be liable for any damage arising from the use of this software.","fl3r-feelbox");?>
      </p>
      <p><strong>
        <?php _e("Follow me!","fl3r-feelbox");?>
        </strong></p>
      <p>
        <?php _e("If you want to ask me, you want to send your opinion or you have a question please don't hesitate to contact me.","fl3r-feelbox");?>
      </p>
      <p><a href="https://twitter.com/Armando_Fiore" target="new">Twitter</a> - <a href="https://www.facebook.com/armando.FL3R.fiore" target="new">Facebook</a> - <a href="https://plus.google.com/+ArmandoFiore" target="new">Google+</a> - <a href="http://it.linkedin.com/in/armandofiore" target="new">LinkedIn</a></p>
      <h3>
        <?php _e("Donate","fl3r-feelbox");?>
      </h3>
      <p>
        <?php _e("If you like this plugin, you consider making a small donation. Thanks.","fl3r-feelbox");?>
      </p>
      <p>
      <form action="https://www.paypal.com/cgi-bin/webscr" method="post" target="_top">
        <input type="hidden" name="cmd" value="_s-xclick">
        <input type="hidden" name="hosted_button_id" value="2PCZCTKZ86ANA">
        <input type="image" src="https://www.paypalobjects.com/en_US/GB/i/btn/btn_donateCC_LG.gif" border="0" name="submit" alt="PayPal – The safer, easier way to pay online.">
        <img alt="" border="0" src="https://www.paypalobjects.com/it_IT/i/scr/pixel.gif" width="1" height="1">
      </form>
      </p>
    </div>
    <div class="content" id="donate">
      <h3>
        <?php _e("Donate","fl3r-feelbox");?>
      </h3>
      <p>
        <?php _e("If you like this plugin, you consider making a small donation. Thanks.","fl3r-feelbox");?>
      </p>
      <p>
      <form action="https://www.paypal.com/cgi-bin/webscr" method="post" target="_top">
        <input type="hidden" name="cmd" value="_s-xclick">
        <input type="hidden" name="hosted_button_id" value="2PCZCTKZ86ANA">
        <input type="image" src="https://www.paypalobjects.com/en_US/GB/i/btn/btn_donateCC_LG.gif" border="0" name="submit" alt="PayPal – The safer, easier way to pay online.">
        <img alt="" border="0" src="https://www.paypalobjects.com/it_IT/i/scr/pixel.gif" width="1" height="1">
      </form>
      </p>
    </div>
  </div>
  </form>
</div>
<?php 
}

function feelbox_reset_moods() {
	global $wpdb;
	
	$table_name = $wpdb->prefix.'lydl_posts';
	$table_name2 = $wpdb->prefix.'lydl_poststimestamp';	

	$wpdb->query( "DELETE FROM ".$table_name );
	$wpdb->query( "DELETE FROM ".$table_name2 );
	
						echo '<div class="updated"><p><strong>';
					_e("All moods have been reset. ლ(ಠ益ಠლ) What did you do?","fl3r-feelbox");
					echo '</strong></p></div>';
					
}

function feelbox_dashboard_page() {
	global $wpdb;
	global $moods;
	global $customcss;
	
	$hidden_field_name = 'feelbox_submit_hidden';
	$options = get_option('feelbox_wp_options');

	// http://codex.wordpress.org/Adding_Administration_Menus
	
	if (!current_user_can('manage_options'))  {
		wp_die( __('You do not have sufficient permissions to access this page.') );
	} ?>
<style>
	.selected { font-weight:bold; color:#000; text-decoration: none; }
	.totaltable,.mood_table { display:none; }
	#daily,#mood_1 { display:block; }
	</style>
<script type="text/javascript">
	jQuery(document).ready(function(){
		jQuery("#tabs a").click(function(){
			var activeTab = jQuery(this).attr("rel");
			jQuery(this).addClass("button-primary").removeClass("button-secondary").siblings().addClass("button-secondary").removeClass("button-primary");
			jQuery(".mood_table:visible").fadeOut("fast", function(){
				jQuery("#"+activeTab).slideDown("fast");
			});
			
			return false;
		});
		jQuery("#tabs2 a").click(function(){
			var activeTab = jQuery(this).attr("rel");
			jQuery(this).addClass("button-primary").removeClass("button-secondary").siblings().addClass("button-secondary").removeClass("button-primary");
			jQuery(".totaltable:visible").fadeOut("fast", function(){
				jQuery("#"+activeTab).slideDown("fast");
			});
			
			return false;
		});
				
	});	
	</script>
<?php 
		feelbox_printmoodtables(); 
}

function feelbox_printmoodtables() {
	global $wpdb;
	global $moods;
	global $customcss; ?>
<div class="wrap">
<h2 class="nav-tab-wrapper">FL3R FeelBox <a class="nav-tab nav-tab-active" href="<?php echo admin_url('index.php?page=feelbox'); ?>">
  <?php _e("Stats","fl3r-feelbox");?>
  </a> <a class="nav-tab" rel="settings" href="<?php echo admin_url('options-general.php?page=feelbox#settings'); ?>">
  <?php _e("Settings","fl3r-feelbox");?>
  </a> <a class="nav-tab" rel="customize" href="<?php echo admin_url('options-general.php?page=feelbox#customize'); ?>">
  <?php _e("Custom CSS","fl3r-feelbox");?>
  </a> <a class="nav-tab" rel="faq" href="<?php echo admin_url('options-general.php?page=feelbox#faq'); ?>">
  <?php _e("FAQ","fl3r-feelbox");?>
  </a> <a class="nav-tab" rel="credits" href="<?php echo admin_url('options-general.php?page=feelbox#credits'); ?>">
  <?php _e("Credits","fl3r-feelbox");?>
  </a> <a class="nav-tab" rel="donate" href="<?php echo admin_url('options-general.php?page=feelbox#donate'); ?>">
  <?php _e("Donate","fl3r-feelbox");?>
  </a></h2>
<h3>
  <?php _e("Most voted by mood","fl3r-feelbox");?>
</h3>
<p>
  <?php _e("To get an accurate read of the posts people care about, we're only counting posts that have more than one vote.","fl3r-feelbox");?>
</p>
<div id="tabs">
  <?php for ($i=1; $i<=6; $i=$i+1) { ?>
  <a href="#" class="<?php if ($i==1) echo 'button-primary'; else echo 'button-secondary'; ?>" rel="mood_<?php echo $i; ?>">
  <?php _e("Most","fl3r-feelbox");?>
  <?php echo $moods[$i]; ?></a>
  <?php } ?>
</div>
<?php	
	for ($i=1; $i<=6; $i=$i+1) {
		$objs = $wpdb->get_results( feelbox_get_moods_sql($i, 10) );
		
		echo '<div class="mood_table" id="mood_' . $i . '">';
		echo '<h4><?php _e("Most","fl3r-feelbox");?> ' . $moods[$i] . '</h4><table class="widefat">';
		
		
		
		echo '<thead><tr><th>';
		echo '<th>';
					_e("Post","fl3r-feelbox");
		echo '</th>';
		echo '<th>';
					_e("Votes","fl3r-feelbox");
		echo '</th>';			
		echo '<th>';
					_e("Total","fl3r-feelbox");
		echo '</th>';
		echo '<th>';
					_e("Relative percentage","fl3r-feelbox");
		echo '</th>';
		echo '<th>';
					_e("Weighted score","fl3r-feelbox");
		echo '</th>';
		echo '</tr></thead>';

		
		
		
		if (sizeof($objs) > 0) {
			foreach ($objs as $obj) {
				echo '<tr>';
				echo '<td><a href="' . get_permalink($obj->ID) . '">' . get_the_title($obj->ID) . '</a></td>';
				echo '<td>' . $obj->emoted . '</td>';
				echo '<td>' . $obj->total . '</td>';
				echo '<td>' . $obj->ranking . '%</td>';
				echo '<td>' . $obj->weighted . '</td>';
				echo '</tr>';
			}
		} else {
					echo '<tr><td colspan="3">';
					_e("No one has voted for this. Not yet.","fl3r-feelbox");
		echo '</td></tr>';
				}
		echo '</table>';
		echo '</div>';
	} ?>
<h3>
  <?php _e("Most voted by date","fl3r-feelbox");?>
</h3>
<div id="tabs2"> <a href="#" class="button-secondary" rel="totalvotes">
  <?php _e("Most voted ever","fl3r-feelbox");?>
  </a> <a href="#" class="button-secondary" rel="monthly">
  <?php _e("Most voted in the past month","fl3r-feelbox");?>
  </a> <a href="#" class="button-secondary" rel="weekly">
  <?php _e("Most voted in the past week","fl3r-feelbox");?>
  </a> <a href="#" class="button-primary" rel="daily">
  <?php _e("Most voted in the past day","fl3r-feelbox");?>
  </a> </div>
<?php
	$objs = $wpdb->get_results( feelbox_get_most_clicked_sql( NULL, 10 ) );

	echo '<div class="totaltable" id="totalvotes"><h4>Most voted ever</h4><table class="widefat">';
	echo '<thead><tr><th>Post</th><th>'. $moods[1] .'</th><th>'. $moods[2] .'</th><th>'. $moods[3] .'</th><th>'. $moods[4] .'</th><th>'. $moods[5] .'</th><th>'. $moods[6] .'</th></tr></thead>';
	if (sizeof($objs) > 0) {
		foreach ($objs as $obj) {
			echo '<tr>';
			echo '<td><a href="' . get_permalink($obj->post_ID) . '">' . get_the_title($obj->post_ID) . '</a></td>';
			echo '<td>' . $obj->emotion_1 . '</td>';
			echo '<td>' . $obj->emotion_2 . '</td>';
			echo '<td>' . $obj->emotion_3 . '</td>';
			echo '<td>' . $obj->emotion_4 . '</td>';									
			echo '<td>' . $obj->emotion_5 . '</td>';
			echo '<td>' . $obj->emotion_6 . '</td>';
			echo '</tr>';
		}
	} else {
		echo '<tr><td colspan="3"><?php _e("No one has voted for this. Not yet.","fl3r-feelbox");?></td></tr>';
	}
	echo '</table></div>';

	$objs = $wpdb->get_results( feelbox_get_most_clicked_sql( "30 DAY", 10 ) );

	echo '<div class="totaltable" id="monthly"><h4><?php _e("Most Voted in the past month","fl3r-feelbox");?></h4><table class="widefat">';
	echo '<thead><tr><th>Post</th><th>'. $moods[1] .'</th><th>'. $moods[2] .'</th><th>'. $moods[3] .'</th><th>'. $moods[4] .'</th><th>'. $moods[5] .'</th><th>'. $moods[6] .'</th></tr></thead>';
	if (sizeof($objs) > 0) {
		foreach ($objs as $obj) {
			echo '<tr>';
			echo '<td><a href="' . get_permalink($obj->post_ID) . '">' . get_the_title($obj->post_ID) . '</a></td>';
			echo '<td>' . $obj->emotion_1 . '</td>';
			echo '<td>' . $obj->emotion_2 . '</td>';
			echo '<td>' . $obj->emotion_3 . '</td>';
			echo '<td>' . $obj->emotion_4 . '</td>';									
			echo '<td>' . $obj->emotion_5 . '</td>';
			echo '<td>' . $obj->emotion_6 . '</td>';
			echo '</tr>';
		}
	} else {
		echo '<tr><td colspan="3"><?php _e("No one has voted for this. Not yet.","fl3r-feelbox");?></td></tr>';
	}
	echo '</table></div>';

	$objs = $wpdb->get_results( feelbox_get_most_clicked_sql( "7 DAY", 10 ) );

	echo '<div class="totaltable" id="weekly"><h4><?php _e("Most voted in the past week","fl3r-feelbox");?></h4><table class="widefat">';
	echo '<thead><tr><th>Post</th><th>'. $moods[1] .'</th><th>'. $moods[2] .'</th><th>'. $moods[3] .'</th><th>'. $moods[4] .'</th><th>'. $moods[5] .'</th><th>'. $moods[6] .'</th</tr></thead>';
	if (sizeof($objs) > 0) {
		foreach ($objs as $obj) {
			echo '<tr>';
			echo '<td><a href="' . get_permalink($obj->post_ID) . '">' . get_the_title($obj->post_ID) . '</a></td>';
			echo '<td>' . $obj->emotion_1 . '</td>';
			echo '<td>' . $obj->emotion_2 . '</td>';
			echo '<td>' . $obj->emotion_3 . '</td>';
			echo '<td>' . $obj->emotion_4 . '</td>';									
			echo '<td>' . $obj->emotion_5 . '</td>';
			echo '<td>' . $obj->emotion_6 . '</td>';
			echo '</tr>';
		}
	} else {
		echo '<tr><td colspan="3"><?php _e("No one has voted for this. Not yet.","fl3r-feelbox");?></td></tr>';
	}
	echo '</table></div>';

	$objs = $wpdb->get_results( feelbox_get_most_clicked_sql( "1 DAY", 10 ) );


	echo '<div class="totaltable" id="daily"><h4><?php _e("Most voted in the past day","fl3r-feelbox");?></h4><table class="widefat">';
	echo '<thead><tr><th>Post</th><th>'. $moods[1] .'</th><th>'. $moods[2] .'</th><th>'. $moods[3] .'</th><th>'. $moods[4] .'</th><th>'. $moods[5] .'</th><th>'. $moods[6] .'</th></tr></thead>';
	if (sizeof($objs) > 0) {
		foreach ($objs as $obj) {
			echo '<tr>';
			echo '<td><a href="' . get_permalink($obj->post_ID) . '">' . get_the_title($obj->post_ID) . '</a></td>';
			echo '<td>' . $obj->emotion_1 . '</td>';
			echo '<td>' . $obj->emotion_2 . '</td>';
			echo '<td>' . $obj->emotion_3 . '</td>';
			echo '<td>' . $obj->emotion_4 . '</td>';									
			echo '<td>' . $obj->emotion_5 . '</td>';
			echo '<td>' . $obj->emotion_6 . '</td>';
			echo '</tr>';
		}
	} else {
		echo '<tr><td colspan="3"><?php _e("No one has voted for this. Not yet.","fl3r-feelbox");?></td></tr>';
	}
	echo '</table></div></div>';
}
?>
