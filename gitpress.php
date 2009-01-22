<?php
/*
Plugin Name: GitPress
Description: This plugin provides a widget that lists a user's GitHub repositories.
Author: Enrico Bianco
Version: 0.1
Author URI: http://liveandcode.com/
*/

function gitpress_widget_init() {
    
    if ( !function_exists('register_sidebar_widget') || !function_exists('register_widget_control') )
		return;
	
	function gitpress_widget($args) {
	    
	    extract($args);
	    
	    // TODO: Add some more options (maybe some text to precede the repos list?)
	    $options = get_option('gitpress_widget_options');
	    $title = $options['title'];
	    $username = $options['username'];
	    
	    // Grab the user info using the GitHub API
	    $curl_session = curl_init();
	    curl_setopt($curl_session, CURLOPT_URL, 'http://github.com/api/v1/json/' . $username);
	    curl_setopt($curl_session, CURLOPT_RETURNTRANSFER, 1);
	    $result = json_decode(curl_exec($curl_session));
	    curl_close($curl_session);
	    
	    $userinfo = $result->{"user"};
	    $repos = $userinfo->{"repositories"};
	    
	    
	    echo $before_widget . $before_title . $title . $after_title;
	    echo "<ul id='gitpress-repo-list'>";
	    foreach ($repos as $repo) {
	        $repo_name = $repo->{"name"};
	        echo "<li class='gitpress-repo'><a href='http://github.com/" . $username . "/" . $repo_name . "'>" . $repo_name . "</a>";
	    }
	    echo "</ul>";
	    echo $after_widget;
	}
	
	function gitpress_widget_control() {
	    
	    $options = get_option('gitpress_widget_options');
	    if (!is_array($options)) {
	        $options = array('title'=>'', 'username'=>'');
	    }
	    
	    if ( $_POST['gitpress-submit'] ) {
			// Remember to sanitize and format use input appropriately.
			$options['title'] = strip_tags(stripslashes($_POST['gitpress-title']));
			$options['username'] = strip_tags(stripslashes($_POST['gitpress-username']));
			update_option('gitpress_widget_options', $options);
		}
		
		$title = htmlspecialchars($options['title'], ENT_QUOTES);
		$username = htmlspecialchars($options['username'], ENT_QUOTES);
		
		echo '<p style="text-align:right;"><label for="gitpress-title">' . __('Title:') . ' <input style="width: 200px;" id="gitpress-title" name="gitpress-title" type="text" value="'.$title.'" /></label></p>';
		echo '<p style="text-align:right;"><label for="gitpress-username">' . __('User:') . ' <input style="width: 200px;" id="gitpress-username" name="gitpress-username" type="text" value="'.$username.'" /></label></p>';
		echo '<input type="hidden" id="gitpress-submit" name="gitpress-submit" value="1" />';
	}
	
	register_sidebar_widget('GitPress', 'gitpress_widget');
	register_widget_control('GitPress', 'gitpress_widget_control');
}

add_action('widgets_init', 'gitpress_widget_init');
?>