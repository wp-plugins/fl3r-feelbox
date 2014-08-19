<?php

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
  <label for="<?php echo $this->get_field_id('title'); ?>">Title:
    <input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo attribute_escape($title); ?>" />
  </label>
</p>
<p>
  <label for="<?php echo $this->get_field_id('duration'); ?>">Show:
    <select id="<?php echo $this->get_field_id('duration'); ?>" name="<?php echo $this->get_field_name('duration'); ?>">
      <option value="365 DAY"<?php echo ($duration == '365 DAY') ? ' selected="selected"' : ''; ?>>The past year</option>
      <option value="30 DAY"<?php echo ($duration == '30 DAY') ? ' selected="selected"' : ''; ?>>The past 30 days</option>
      <option value="7 DAY"<?php echo ($duration == '7 DAY') ? ' selected="selected"' : ''; ?>>The past 7 days</option>
      <option value="1 DAY"<?php echo ($duration == '1 DAY') ? ' selected="selected"' : ''; ?>>The past day</option>
      <?php for ($i=1; $i<=6; $i=$i+1) { ?>
      <option value="MOOD-<?php echo $i; ?>"<?php echo ($duration == 'MOOD-' . $i ) ? ' selected="selected"' : ''; ?>>Posts most "<?php echo $moods[$i]; ?>"</option>
      <?php } ?>
    </select>
  </label>
</p>
<p>
  <label for="<?php echo $this->get_field_id('number'); ?>">Number of posts to display:</label>
  <input id="<?php echo $this->get_field_id('number'); ?>" name="<?php echo $this->get_field_name('number'); ?>" type="text" value="<?php echo $number; ?>" size="3" />
  <br />
  <small>(at most 10)</small> </p>
<p>
<fieldset style="width:214px; padding:5px;"  class="widefat">
  <legend>Thumbnail settings</legend>
  <input type="checkbox" class="checkbox" name="<?php echo $this->get_field_name( 'thumbnail-active' ); ?>" <?php echo ($instance['thumbnail']['active']) ? 'checked="checked"' : ''; ?> id="<?php echo $this->get_field_id('thumbnail-active'); ?>">
  <label for="<?php echo $this->get_field_id('thumbnail-active'); ?>">Display post thumbnail. Check ad save to change the size.</label>
  <br>
  <?php if($instance['thumbnail']['active']) : ?>
  <label for="<?php echo $this->get_field_id( 'thumbnail-width' ); ?>">Width:</label>
  <input id="<?php echo $this->get_field_id( 'thumbnail-width' ); ?>" name="<?php echo $this->get_field_name( 'thumbnail-width' ); ?>" value="<?php echo  $instance['thumbnail']['width']; ?>"  class="widefat" style="width:30px!important" />
  px <br />
  <label for="<?php echo $this->get_field_id( 'thumbnail-height' ); ?>">Height:</label>
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
			// echo '<tr><td colspan="3">No one has voted for this yet. Check back soon.</td></tr>';
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
    // wp_register_script( 'jquery', 'http://ajax.googleapis.com/ajax/libs/jquery/1.4/jquery.min.js');

	add_dashboard_page('FL3R FeelBox Dashboard', 'FL3R FeelBox Stats', 'manage_options', 'feelbox', 'feelbox_dashboard_page');
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
	global $feelbox_server;
	global $use_centralized_site;
	
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
	#apikey, #apiname { padding: 5px; width: 200px; }
	</style>
<script type="text/javascript">

	<?php if ( $use_centralized_site ) : ?>
	jQuery(document).ready(function(){
	
		if ( jQuery('#validkey').val()=='1' ) {
			jQuery('#apiverifyresult').html('Your Website ID and API key have been verified. Your widget will now appear on your blog posts!');
		} else {
			jQuery('#apiverifyresult').html('<span style="color:red;">Your Website ID and/or API key don\'t match our records. Your widget will not appear until it does. Try copying & pasting them again.</span>');
		}
	
		function verify( submit_form_afterward ) {
			// http://stackoverflow.com/questions/1388018/jquery-attaching-an-event-to-multiple-elements-at-one-go
			var jApikey = jQuery("#apikey");
			var jApiname = jQuery("#apiname");
			var jObj = jApikey.add(jApiname);

			var n = jApiname.val();
			n = jQuery.trim(n);
			var k = jApikey.val();
			k = jQuery.trim(k);
			// console.log(n,k);
			jQuery.ajax({
				url: '<?php echo $feelbox_server; ?>/api/websites/verify/' + n + '/' + k,
				dataType: 'jsonp',
				type: 'get',
				success: function(j) {
					if (j.stat == 'ok') {
						jQuery('#apiverifyresult').html('Your Website ID and API key have been verified. Your widget will appear on your blog posts, but don\'t forget to save your changes!');
						jQuery('#validkey').val('1');
					} else {
						jQuery('#apiverifyresult').html('<span style="color:red;">Your Website ID and/or API key don\'t match our records. Your widget will not appear until it does. Try copying & pasting them again.</span>');
						jQuery('#validkey').val('');
					}
					
					if (submit_form_afterward) jQuery('form#options').submit();
					
				}
			});
		}
	
		var jApikey = jQuery("#apikey");
		var jApiname = jQuery("#apiname");
		var jObj = jApikey.add(jApiname);
					
		jQuery('form#options p.submit input.button-primary').click( function(){ verify(true); } );
		jObj.blur( function(){ verify(); } );
		
	});	
	<?php endif; ?>

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
							
				$options['apikey'] 					= $_POST[ 'apikey' ];
				$options['apiname'] 				= $_POST[ 'apiname' ];
				$options['validkey'] 				= $_POST[ 'validkey' ];

				@file_put_contents(feelbox_CSS_FILE, stripslashes( $_POST[ 'cssbox' ] ) );
				
				if ($options['showinpostsondefault'] == 'on') {
					add_filter('the_content', 'add_feelbox_widget_to_posts');
				}
				
				update_option('feelbox_wp_options', $options);
				echo '<div class="updated"><p><strong>Settings Saved.</strong></p></div>';

			}
		}

	?>
<div class="wrap">
  <h2 class="nav-tab-wrapper">FL3R FeelBox <a class="nav-tab" href="<?php echo admin_url('index.php?page=feelbox'); ?>">Stats</a> <a class="nav-tab nav-tab-active" rel="settings" href="#">Settings</a> <a class="nav-tab" rel="customize" href="#">Custom CSS</a> <a class="nav-tab" rel="faq" href="#">FAQ</a> <a class="nav-tab" rel="credits" href="#">Credits</a></h2>
  <div class="container">
  <div class="content" id="settings">
  <form id="options" method="post">
    <h3 style="clear:both;">Plugin settings</h3>
    <table class="form-table">
      <tbody>
        <tr valign="top">
          <th scope="row">Graphical bar</th>
          <td><input type="checkbox" id="showsparkbar" name="showsparkbar" <?php if ( $options["showsparkbar"]=='on' ) { echo 'checked="true"'; } ?>>
            <label for="showsparkbar">Show the graphical bar graph above moods.</label></td>
        </tr>
        <tr valign="top">
          <th scope="row">Sorting</th>
          <td><input type="checkbox" id="sortmoods" name="sortmoods" <?php if ( $options["sortmoods"]=='on' ) { echo 'checked="true"'; } ?>>
            <label for="sortmoods">Automatically sort the moods by popularity. I suggest you leave this box unchecked.</label></td>
        </tr>
        <tr valign="top">
          <th scope="row">Automatic display</th>
          <td><input type="checkbox" id="showinpostsondefault" name="showinpostsondefault" <?php if ( $options["showinpostsondefault"]=='on' ) { echo 'checked="true"'; } ?>>
            <label for="showinpostsondefault">Automatically display the feelbox widget at the end of each blog post.</label>
            <p class="description">Only if this is unchecked, you can use the print_feelbox_widget() PHP function in your templates or use the [feelbox] shortcode to show the FeelBox where you want.</p></td>
        </tr>
        <tr valign="top">
          <th scope="row">Social media sharing</th>
          <td><input type="checkbox" id="showtweetfollowup" name="showtweetfollowup" <?php if ( $options["showtweetfollowup"]=='on' ) { echo 'checked="true"'; } ?>>
            <label for="showtweetfollowup">Allow people to tweet on Twitter and sharing on Facebook after voting.</label></td>
        </tr>
      </tbody>
    </table>
    <h3>Delete all moods</h3>
    <table class="form-table">
      <tbody>
        <tr valign="top">
          <th scope="row">Clear all data</th>
          <td><p>
              <input type="textbox" id="cleardata" name="cleardata">
            </p>
            <p>WARNING!</p>
            <p>This option will clear all votes and all data in the Stats page. Normally this is an operation without risk, but to be safe make sure you <a target="_blank" href="http://codex.wordpress.org/Backing_Up_Your_Database">back up</a> your database. This operation is irreversible. If you want to proceed, type <strong>DELETE</strong> in the textbox and click on Save changes button.</p></td>
        </tr>
      </tbody>
    </table>
    <input type="hidden" id="validkey" name="validkey" value="<?php echo $options['validkey']; ?>">
    <input type="hidden" name="<?php echo $hidden_field_name; ?>" value="Y">
    <p class="submit">
      <input type="submit" name="Submit" class="button-primary" value="<?php esc_attr_e('Save Changes') ?>" />
    </p>
    <!-- /form -->
    </div>
    <div class="content" id="customize">
      <div id="csswrapper">
        <textarea style="width:100%;" rows="10" id="cssbox" name="cssbox"><?php echo @file_get_contents(feelbox_CSS_FILE); ?></textarea>
        <p class="description">Here you can customize your FeelBox Css. The file is stored in the same directory of original FeelBox style.css.</p>
      </div>
      <table class="form-table">
        <tbody>
          <tr valign="top">
            <th scope="row">Bypass plugin CSS</th>
            <td><input type="checkbox" name="bypasscss" <?php if ( $options["bypasscss"]=='on' ) { echo 'checked="true"'; } ?>>
              <p class="description">Bypass the default and custom CSS used in the FeelBox plug-in and use CSS from your theme instead. This may be necessary for specific WordPress Multisite installations. Normally this option should be left unchecked.</p></td>
          </tr>
        </tbody>
      </table>
      <p class="submit">
        <input type="submit" name="Submit" class="button-primary" value="<?php esc_attr_e('Save Changes') ?>" />
      </p>
    </div>
    <div class="content" id="faq">
      <h3>FAQ</h3>
      <h3>There is a FeelBox widget?</h3>
      <p>Sure! You can find it in the widget page of your admin panel.</p>
      <h3>Can I manually insert the FeelBox widget?</h3>
      <p>If the Automatic display option is checked, the FeelBox will be automatically display on each  post, right after the comment.</p>
      <p>But if you want, you can  use the WordPress shortcode</p>
      <p><code>[feelbox]</code></p>
      <p>or add</p>
      <p><code>&lt;?php if ( function_exists('print_feelbox_widget') ) { print_feelbox_widget(); } ?&gt;</code></p>
      <p>in your PHP template, usually on your comment template (comments.php).</p>
	  <p>Attention! don't insert manually the FeelBox code if the Automatic display option is cecked: it may cause style errors and graphical glitch.</p>
    </div>
    <div class="content" id="credits">
      <h3>Credits</h3>
      <p>FeelBox is a plugin created by Armando "FL3R" Fiore. Dedicated to the beautiful girl who is always by my side, Anna Rita!</p>
      <p>Thanks for using FeelBox, you helped a developer to increase his self-esteem!</p>
      <h4>Copyright</h4>
      <p>Copyright © 2014 Armando "FL3R" Fiore. All rights reserved. This software is provided as is, without any express or implied warranty. In no event shall the author be liable for any damage arising from the use of this software.</p>
      <p><strong>Follow me!</strong></p>
      <p>If you want to ask me, you want to send your opinion or you have a question please don't hesitate to contact me.</p>
      <p><a href="https://twitter.com/Armando_Fiore" target="new">Twitter</a> - <a href="https://www.facebook.com/armando.FL3R.fiore" target="new">Facebook</a> - <a href="https://plus.google.com/+ArmandoFiore" target="new">Google+</a> - <a href="http://it.linkedin.com/in/armandofiore" target="new">LinkedIn</a></p>
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
	echo '<div class="updated"><p><strong>All Moods have been reset.</strong></p></div>';
}

function feelbox_dashboard_page() {
	global $wpdb;
	global $moods;
	
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
	#apikey, #apiname { padding: 5px; width: 200px; }
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
	global $moods; ?>
<div class="wrap">
<h2 class="nav-tab-wrapper">FL3R FeelBox <a class="nav-tab nav-tab-active" href="<?php echo admin_url('index.php?page=feelbox'); ?>">Stats</a> <a class="nav-tab" rel="settings" href="<?php echo admin_url('options-general.php?page=feelbox#settings'); ?>">Settings</a> <a class="nav-tab" rel="customize" href="<?php echo admin_url('options-general.php?page=feelbox#customize'); ?>">Customization</a> <a class="nav-tab" rel="faq" href="<?php echo admin_url('options-general.php?page=feelbox#faq'); ?>">FAQ</a> <a class="nav-tab" rel="credits" href="<?php echo admin_url('options-general.php?page=feelbox#credits'); ?>">Credits</a></h2>
<h3>Most voted by mood</h3>
<p>To get an accurate read of the posts people care about, we're only counting posts that have more than one vote.</p>
<div id="tabs">
  <?php for ($i=1; $i<=6; $i=$i+1) { ?>
  <a href="#" class="<?php if ($i==1) echo 'button-primary'; else echo 'button-secondary'; ?>" rel="mood_<?php echo $i; ?>">Most <?php echo $moods[$i]; ?></a>
  <?php } ?>
</div>
<?php	
	for ($i=1; $i<=6; $i=$i+1) {
		$objs = $wpdb->get_results( feelbox_get_moods_sql($i, 10) );
		
		echo '<div class="mood_table" id="mood_' . $i . '">';
		echo '<h4>Most ' . $moods[$i] . '</h4><table class="widefat">';
		echo '<thead><tr><th>Post</th><th>Votes</th><th>Total</th><th>Relative percentage</th><th>Weighted score</th></tr></thead>';
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
			echo '<tr><td colspan="3">No one has voted for this. Not yet.</td></tr>';
		}
		echo '</table>';
		echo '</div>';
	} ?>
<h3>Most voted by date</h3>
<div id="tabs2"> <a href="#" class="button-secondary" rel="totalvotes">Most voted ever</a> <a href="#" class="button-secondary" rel="monthly">Most voted in the past month</a> <a href="#" class="button-secondary" rel="weekly">Most voted in the past week</a> <a href="#" class="button-primary" rel="daily">Most voted in the past day</a> </div>
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
		echo '<tr><td colspan="3">No one has voted for this. Not yet.</td></tr>';
	}
	echo '</table></div>';

	$objs = $wpdb->get_results( feelbox_get_most_clicked_sql( "30 DAY", 10 ) );

	echo '<div class="totaltable" id="monthly"><h4>Most Voted in the past month</h4><table class="widefat">';
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
		echo '<tr><td colspan="3">No one has voted for this. Not yet.</td></tr>';
	}
	echo '</table></div>';

	$objs = $wpdb->get_results( feelbox_get_most_clicked_sql( "7 DAY", 10 ) );

	echo '<div class="totaltable" id="weekly"><h4>Most voted in the past week</h4><table class="widefat">';
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
		echo '<tr><td colspan="3">No one has voted for this. Not yet.</td></tr>';
	}
	echo '</table></div>';

	$objs = $wpdb->get_results( feelbox_get_most_clicked_sql( "1 DAY", 10 ) );


	echo '<div class="totaltable" id="daily"><h4>Most voted in the past day</h4><table class="widefat">';
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
		echo '<tr><td colspan="3">No one has voted for this. Not yet.</td></tr>';
	}
	echo '</table></div></div>';
}

?>
