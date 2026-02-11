<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

add_action('admin_menu', 'edusteps_innovation_menu');
function edusteps_innovation_menu() {
    add_submenu_page("wpcf7",'Témy IV', 'Témy IV', 'manage_options', 'iv-topics-edit', 'iv_topics_edit' );

}

function register_iv_topics_scripts() {
    wp_enqueue_script('iv_topics_script', plugin_dir_url(__FILE__) . 'js/iv_topics_script.js?'.time(), array('jquery'), '1.0', true);
}

function iv_topics_edit(){
    register_backend_scripts(); //from edusteps_courses_automation plugin
    register_iv_topics_scripts();
    include dirname( __FILE__ ) . '/../edusteps_courses_automation/templates/alertsAndLoadings.php';
    include dirname( __FILE__ ) . '/templates/iv_topics_edit.php';
}

add_action( 'admin_bar_menu', 'add_iv_topics_admin_menu', 500 );
function add_iv_topics_admin_menu ( WP_Admin_Bar $admin_bar ) {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

    $admin_bar->add_menu(
        array(
            'id'    => 'inovacne-temy',
            'parent' => 'inovacne',
            'title' => 'Témy IV', //you can use img tag with image link. it will show the image icon Instead of the title.
            'href'  => '/wp-admin/admin.php?page=iv-topics-edit',
            'meta' => [
                'title' => 'Témy IV', //This title will show on hover
            ]
        ) 
    );
}

function addNewIvTopic(){
    $current_user = $GLOBALS['current_user'];
    $post_params = $current_user->get_params();
    $topic_name = $post_params["topic_name"];

    global $wpdb;
    $table_name = $wpdb->prefix . "iv_topics";
    $saved = $wpdb->insert( 
        $table_name, 
        array( 
            'topic_name' => $topic_name, 
        ) 
    );
    if (!$saved){
        $error = $wpdb->last_error;
        if (str_contains($error, "Duplicate entry")){
            wp_send_json_error( 'Téma s rovnakým názvom už existuje.', 200);
            return;
        }
        wp_send_json_error( 'Tému sa nepodarilo pridať. Kontaktujte admina.', 500);
        return;
    }
    
    wp_send_json_success( 'Téma bola pridaná', 200);
    
}

function getSingleIvTopic($id = 0){
    if ($id == 0){
        $current_user = $GLOBALS['current_user'];
        $post_params = $current_user->get_params();
        $iv_topic_id = $post_params["iv_topic_id"];
    } else {
        $iv_topic_id = $id;
    }

    global $wpdb;
    $table_name = $wpdb->prefix . "iv_topics";
    $topic = $wpdb->get_row("SELECT * FROM " . $table_name . " WHERE id = " . $iv_topic_id, OBJECT);

    if (!$topic){
        wp_send_json_error( 'Tému sa nepodarilo načítať. Kontaktujte admina.', 500);
        return;
    } else {
        //remove backslashes from content
        if ($id == 0) {
            $topic->content = stripslashes($topic->content);
            wp_send_json_success( $topic, 200);
        } else {
            return $topic;
        }
    }
}

function updateIvTopic(){
    $current_user = $GLOBALS['current_user'];
    $post_params = $current_user->get_params();
    $iv_topic_id = $post_params["iv_topic_id"];
    $topic_name = $post_params["topic_name"];

    global $wpdb;
    $table_name = $wpdb->prefix . "iv_topics";
    $updated = $wpdb->update( 
        $table_name, 
        array( 
            'topic_name' => $topic_name, 
        ), 
        array( 'id' => $iv_topic_id ), 
        array( 
            '%s'
        ), 
        array( '%d' ) 
    );

    if (is_bool($updated) && !$updated){
        wp_send_json_error( 'Tému sa nepodarilo upraviť. Kontaktujte admina.', 500);
        return;
    }else{
        wp_send_json_success( 'Téma bola upravená', 200);
    }
}

function deleteIvTopic(){
    $current_user = $GLOBALS['current_user'];
    $post_params = $current_user->get_params();
    $iv_topic_id = $post_params["iv_topic_id"];

    global $wpdb;
    //update is_visible = 0
    $table_name = $wpdb->prefix . "iv_topics";
    $updated = $wpdb->update( 
        $table_name, 
        array( 
            'is_visible' => 0, 
        ), 
        array( 'id' => $iv_topic_id ), 
        array( 
            '%d'
        ), 
        array( '%d' ) 
    );

    if (is_bool($updated) && !$updated){
        wp_send_json_error( 'Tému sa nepodarilo vymazať. Kontaktujte admina.', 500);
        return;
    }else{
        wp_send_json_success( 'Téma bola vymazaná', 200);
    }
}

function fetchSingleIvMaterialEmail(){
    $current_user = $GLOBALS['current_user'];
    $post_params = $current_user->get_params();
    $iv_material_email_id = $post_params["id"];

    global $wpdb;
    $table_name = $wpdb->prefix . "iv_topics_material_emails";
    $email = $wpdb->get_row("SELECT * FROM " . $table_name . " WHERE id = " . $iv_material_email_id, OBJECT);

    if (!$email){
        wp_send_json_error( 'Email sa nepodarilo načítať. Kontaktujte admina.', 500);
        return;
    } else {
        wp_send_json_success( $email, 200);
    }
}

function addNewIvMaterialEmail(){
    $current_user = $GLOBALS['current_user'];
    $post_params = $current_user->get_params();
    $topic_id = $post_params["topic_id"];
    $materialEmail_order = $post_params["materialEmail_order"];
    $materialEmail_body = $post_params["materialEmail_body"];
    $learning_mode = $post_params["learning_mode"];

    global $wpdb;
    $table_name = $wpdb->prefix . "iv_topics_material_emails";
    $saved = $wpdb->insert( 
        $table_name, 
        array( 
            'iv_topic_id' => $topic_id, 
            'order' => $materialEmail_order,
            'email' => $materialEmail_body,
            'learning_mode' => $learning_mode
        ), 
        array(
            '%d',
            '%d',
            '%s',
        )
    );
    if (!$saved){
        wp_send_json_error( 'Email sa nepodarilo pridať. Kontaktujte admina.', 500);
        return;
    }
    
    wp_send_json_success( 'Email bol pridaný', 200);   
}

function updateIvMaterialEmail() {
    $current_user = $GLOBALS['current_user'];
    $post_params = $current_user->get_params();
    $iv_material_email_id = $post_params["iv_materialEmail_id"];
    $materialEmail_order = $post_params["materialEmail_order"];
    $materialEmail_body = $post_params["materialEmail_body"];
    $learning_mode = $post_params["learning_mode"];

    global $wpdb;
    $table_name = $wpdb->prefix . "iv_topics_material_emails";
    $updated = $wpdb->update( 
        $table_name, 
        array( 
            'order' => $materialEmail_order,
            'email' => $materialEmail_body,
            'learning_mode' => $learning_mode
        ), 
        array( 'ID' => $iv_material_email_id ), 
        array( 
            '%d',
            '%s',
        ), 
        array( '%d' ) 
    );

    if (is_bool($updated) && !$updated){
        wp_send_json_error( 'Email sa nepodarilo upraviť. Kontaktujte admina.', 500);
        return;
    }else{
        wp_send_json_success( 'Email bol upravený', 200);
    }
}

function deleteIvMaterialEmail() {
    $current_user = $GLOBALS['current_user'];
    $post_params = $current_user->get_params();
    $iv_material_email_id = $post_params["iv_materialEmail_id"];

    global $wpdb;
    //update is_visible = 0
    $table_name = $wpdb->prefix . "iv_topics_material_emails";
    $updated = $wpdb->update( 
        $table_name, 
        array( 
            'is_visible' => 0, 
        ), 
        array( 'ID' => $iv_material_email_id ), 
        array( 
            '%d'
        ), 
        array( '%d' ) 
    );

    if (is_bool($updated) && !$updated){
        wp_send_json_error( 'Email sa nepodarilo vymazať. Kontaktujte admina.', 500);
        return;
    }else{
        wp_send_json_success( 'Email bol vymazaný', 200);
    }
}