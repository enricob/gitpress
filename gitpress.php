<?php
/*
Plugin Name: GitPress
Description: This plugin provides a widget that lists a user's GitHub repositories.
Author: Enrico Bianco
Version: 1.0
Author URI: http://liveandcode.com/
*/

function gitpress_widget_init() {
    
    if ( !function_exists('register_sidebar_widget') || !function_exists('register_widget_control') )
		return;
	
}

add_action('widgets_init', 'gitpress_widget_init');
?>