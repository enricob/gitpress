<?php
/*
Plugin Name: GitPress
Plugin URI: http://enricob.github.com/gitpress
Description: This plugin provides a widget that lists a user's GitHub repositories.
Author: Enrico Bianco
Version: 0.2.1
Author URI: http://enricob.github.com/
*/

/*
               DO WHAT THE FUCK YOU WANT TO PUBLIC LICENSE
                       Version 2, December 2004

    Copyright (C) 2009 Enrico Bianco
     14 rue de Plaisance, 75014 Paris, France
    Everyone is permitted to copy and distribute verbatim or modified
    copies of this license document, and changing it is allowed as long
    as the name is changed.

               DO WHAT THE FUCK YOU WANT TO PUBLIC LICENSE
      TERMS AND CONDITIONS FOR COPYING, DISTRIBUTION AND MODIFICATION

     0. You just DO WHAT THE FUCK YOU WANT TO.
*/
function gitpress_widget_init() {
    
    if ( !function_exists('register_sidebar_widget') || !function_exists('register_widget_control') )
		return;
	
	function gitpress_widget($args, $widget_args = 1) {
	    
	    // Figure out which instance we're rendering
	    extract( $args, EXTR_SKIP );
    	if ( is_numeric($widget_args) )
    		$widget_args = array( 'number' => $widget_args );
    	$widget_args = wp_parse_args( $widget_args, array( 'number' => -1 ) );
    	extract( $widget_args, EXTR_SKIP );

    	// Bail out if we don't have options for this instance
    	$options = get_option('gitpress_widget_options');
    	if ( !isset($options[$number]) )
    		return;
	    
	    // TODO: Add some more options (maybe some text to precede the repos list?)
	    $options = get_option('gitpress_widget_options');
	    $title = $options[$number]['title'];
	    $username = $options[$number]['username'];
	    $use_badge = $options[$number]['use_badge'];
	    
	    // Grab the user info using the GitHub API
	    if (!isset($use_badge)) {
	        $curl_session = curl_init();
    	    curl_setopt($curl_session, CURLOPT_URL, 'http://github.com/api/v1/json/' . $username);
    	    curl_setopt($curl_session, CURLOPT_RETURNTRANSFER, 1);
    	    $result = json_decode(curl_exec($curl_session));
    	    curl_close($curl_session);
	    
    	    $userinfo = $result->{"user"};
    	    $repos = $userinfo->{"repositories"};
	    
	    
    	    echo $before_widget . $before_title . $title . $after_title;
    	    echo "<ul id='gitpress-repo-list-" . $number . "' class='widget gitpress-repo-list'>";
    	    if (isset($repos)) {
    	        foreach ($repos as $repo) {
        	        $repo_name = $repo->{"name"};
        	        $repo_url = $repo->{"url"};
        	        echo "<li class='gitpress-repo'><a href='" . $repo_url . "'>" . $repo_name . "</a></li>";
        	    }
            }
    	    echo "</ul>";
    	    echo $after_widget;
        } else {
	    echo $before_widget . $before_title . $after_title; ?>
	    <li id="github-badge" style="list-style: none"></li>
        <script type="text/javascript" charset="utf-8">
          GITHUB_TITLE="<?php echo $title ?>";
          GITHUB_USERNAME="<?php echo $username ?>";
          GITHUB_HEAD="h2";
        </script>
        <script src="http://drnicjavascript.rubyforge.org/github_badge/dist/github-badge-launcher.js" type="text/javascript"></script><?php
        echo $after_widget;
        }
	}
	
	function gitpress_widget_control($widget_args = 1) {
	    
	    global $wp_registered_widgets;
    	static $updated = false; // Whether or not we have already updated the data after a POST submit

        // Figure out which instance we're working with
    	if ( is_numeric($widget_args) )
    		$widget_args = array( 'number' => $widget_args );
    	$widget_args = wp_parse_args( $widget_args, array( 'number' => -1 ) );
    	extract( $widget_args, EXTR_SKIP );

    	// Create an options array if we don't have one
    	$options = get_option('gitpress_widget_options');
    	if ( !is_array($options) )
    		$options = array();
    		
    	// Do we need to update the data?
    	if ( !$updated && !empty($_POST['sidebar']) ) {
    		// Which sidebar are we getting added to?
    		$sidebar = (string) $_POST['sidebar'];

    		$sidebars_widgets = wp_get_sidebars_widgets();
    		if ( isset($sidebars_widgets[$sidebar]) )
    			$this_sidebar =& $sidebars_widgets[$sidebar];
    		else
    			$this_sidebar = array();

    		foreach ( $this_sidebar as $_widget_id ) {
    			// Remove all widgets of this type from the sidebar.  We'll add the new data in a second.  This makes sure we don't get any duplicate data
    			// since widget ids aren't necessarily persistent across multiple updates
    			if ( 'gitpress_widget' == $wp_registered_widgets[$_widget_id]['callback'] && isset($wp_registered_widgets[$_widget_id]['params'][0]['number']) ) {
    				$widget_number = $wp_registered_widgets[$_widget_id]['params'][0]['number'];
    				if ( !in_array( "gitpress-$widget_number", $_POST['widget-id'] ) ) // the widget has been removed.
    					unset($options[$widget_number]);
    			}
    		}
            
            // Update options if we're processing a POST
    		foreach ( (array) $_POST['gitpress-widget'] as $widget_number => $gitpress_widget_instance ) {
    			if ( (!isset($gitpress_widget_instance['title']) || !isset($gitpress_widget_instance['username']))
    			        && isset($options[$widget_number]) )
    			        // user clicked cancel
    				    continue;
    			$title = strip_tags(stripslashes($gitpress_widget_instance['title']));
    			$username = strip_tags(stripslashes($gitpress_widget_instance['username']));
    			if (isset($gitpress_widget_instance['use_badge'])) {
    			    $options[$widget_number] = array( 'title' => $title, 'username' => $username, 'use_badge' => 'true');
    			} else {
    			    $options[$widget_number] = array( 'title' => $title, 'username' => $username );
			    }
    		}

    		update_option('gitpress_widget_options', $options);

    		$updated = true; // So that we don't go through this more than once
    	}
    	
    	if ( -1 == $number ) {
    		$title = '';
    		$username = '';
    		$number = '%i%';    // temporary number to be replaced by JS
    	} else {
    		$title = attribute_escape($options[$number]['title']);
    		$username = attribute_escape($options[$number]['username']);
    		$use_badge = $options[$number]['use_badge'];
    	}
		
		$title_id = 'gitpress-title-' . $number;
		$title_name = 'gitpress-widget[' . $number . '][title]';
		$username_id = 'gitpress-username-' . $number;
		$username_name = 'gitpress-widget[' . $number . '][username]';
		$use_badge_id = 'gitpress-use_badge-' . $number;
		$use_badge_name = 'gitpress-widget[' . $number . '][use_badge]';
		$submit_id = 'gitpress-submit-' . $number;
		$submit_name = 'gitpress-widget[' . $number . '][submit]';
		
		echo '<p style="text-align:right;"><label for="' . $title_id . '">' . __('Title:') . ' <input style="width: 200px;" id="' . $title_id . '" name="' . $title_name . '" type="text" value="' . $title .'" /></label></p>';
		echo '<p style="text-align:right;"><label for="' . $username_id . '">' . __('User:') . ' <input style="width: 200px;" id="' . $username_id . '" name="' . $username_name . '" type="text" value="' . $username . '" /></label></p>';
		echo '<p style="text-align: right;"><label for="' . $use_badge_id . '">' . __('Use Dr. Nic GitHub Badge?') . ' <input id="' . $use_badge_id . '" name="' . $use_badge_name . '" type="checkbox"';
		if (isset($use_badge)) echo ' checked="true" ';
		echo '/></label></p>';
		echo '<p style="color: grey; font-size: xx-small; text-align: center; width: 250px">' . 
		__('Note: Title is not rendered for badges and only one badge can be rendered at a time. ') . '</p>';
		echo '<input type="hidden" id="' . $submit_id . '" name="' . $submit_name . '" value="1" />';
	}
	
	function gitpress_widget_register() {
	    if ( !$options = get_option('gitpress_widget_options') )
    		$options = array();

    	$widget_ops = array('classname' => 'gitpress_widget', 
    	    'description' => __('Displays repos for a GitHub user'));
    	$control_ops = array('id_base' => 'gitpress');
    	$name = __('GitPress');

    	$registered = false;
    	foreach ( array_keys($options) as $o ) {
    		// Old widgets can have null values for some reason
    		if ( !isset($options[$o]['title']) || !isset($options[$o]['username']) )
    			continue;

    		// $id should look like {$id_base}-{$o}
    		$id = "gitpress-$o"; // Never never never translate an id
    		$registered = true;
    		wp_register_sidebar_widget( $id, $name, 'gitpress_widget', $widget_ops, array( 'number' => $o ) );
    		wp_register_widget_control( $id, $name, 'gitpress_widget_control', $control_ops, array( 'number' => $o ) );
    	}

    	// If there are none, we register the widget's existance with a generic template
    	if ( !$registered ) {
    		wp_register_sidebar_widget( 'gitpress-1', $name, 'gitpress_widget', $widget_ops, array( 'number' => -1 ) );
    		wp_register_widget_control( 'gitpress-1', $name, 'gitpress_widget_control', $control_ops, array( 'number' => -1 ) );
    	}
	}
	
	gitpress_widget_register();
}

add_action('widgets_init', 'gitpress_widget_init');
?>