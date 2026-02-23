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

//skip cf7 spam check if the form has meta courseType av-prezencna-listina
add_filter( 'wpcf7_skip_spam_check', 'skip_spam_check_for_feedback_form', 11, 1 );
function skip_spam_check_for_feedback_form( $spam ) {
    $submission = WPCF7_Submission::get_instance();
    $form_id = $submission->get_contact_form()->id;
    if ( $form_id == INNOVATION_FEEDBACK_FORM ) {
        return true;
    }
    return $spam; // Default behavior for other forms
}


require_once plugin_dir_path( __FILE__ ) . 'self_study.php';
require_once plugin_dir_path( __FILE__ ) . 'backend_management.php';
require_once plugin_dir_path( __FILE__ ) . 'feedbacks.php';
