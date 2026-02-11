<?php
/*
Plugin Name: Edusteps Innovation Courses
Description: Functionality for managing innovation courses in Edusteps.
Version: 1.0.0
Author: Edusteps
*/
define('INNOVATION_FEEDBACK_FORM', 82319); // Replace 123 with your actual form ID

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

//requires edusteps_courses_automation plugin to be loaded
register_activation_hook( __FILE__, 'this_plugin_activation' );
function this_plugin_activation(){
    // Require parent plugin
    if ( ! is_plugin_active( 'edusteps_courses_automation/functions.php' ) and current_user_can( 'activate_plugins' ) ) {
        // Stop activation redirect and show error
        wp_die('Sorry, but this plugin requires the Edusteps Courses Automation to be installed and active. <br><a href="' . admin_url( 'plugins.php' ) . '">&laquo; Return to Plugins</a>');
    }
}


require_once plugin_dir_path( __FILE__ ) . 'self_study.php';
require_once plugin_dir_path( __FILE__ ) . 'backend_management.php';
require_once plugin_dir_path( __FILE__ ) . 'feedbacks.php';
