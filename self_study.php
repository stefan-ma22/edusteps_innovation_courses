<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

add_filter( 'cfdb7_after_save_data', 'send_intro_email', 12, 1 );
function send_intro_email( $insert_id ) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'db7_forms';
    $form_data = $wpdb->get_row( $wpdb->prepare( "SELECT form_post_id, form_value FROM $table_name WHERE form_id = %d", $insert_id ), ARRAY_A );

    if ( $form_data ) {
        $deserialized_form = unserialize($form_data['form_value']);
        $form_meta = get_post_meta($form_data['form_post_id']);
        if ($form_meta['courseType'][0] == 'inovacny') {
            send_intro($deserialized_form, $form_meta['innovationTopic'][0]);
        } else {
            return;
        }
    }
}

function send_intro($form_data, $innovation_topic) {
    global $wpdb;
    // Implement the logic to send intro email and materials for self-study
    $to = array("email" => $form_data['email-ucastnika'], "attachments" => array());

    if ($form_data['sposob-vzdelavania'][0] == 'Samoštúdium') {
        $form = "formou samoštúdia";
        $learning_mode = 0;
    } else {
        $form = "s lektormi";
        $learning_mode = 1;
    }
                    

    $table_topic_name = $wpdb->prefix . 'iv_topics';
    $topic_name = $wpdb->get_var($wpdb->prepare("SELECT topic_name FROM $table_topic_name WHERE ID = %d" , $innovation_topic));

    $subject = "Úvodné informácie - inovačné vzdelávanie " . $form . " - " . $topic_name;

    $table_email_body = $wpdb->prefix . 'iv_topics_material_emails';
    $message = $wpdb->get_var($wpdb->prepare("SELECT email FROM $table_email_body WHERE iv_topic_id = %d AND `order` = 0 AND learning_mode = %d" , $innovation_topic, $learning_mode));

    if (!$message || $message == "") {
        return;
    }

    $result = sendEmail($to, $subject, $message);

    if ($result) {
        $body = "Boli odoslané úvodné informácie na IV ". $form ." - ".$topic_name." - pre " . $form_data['email-ucastnika'] . ".<br><br><b><u>Znenie emailu:</u></b><br><br>" . $message . "<br>";
        $subject = "Boli odoslané úvodné informácie na IV " . $form . " - ".$topic_name." - pre " . $form_data['email-ucastnika'];
        $recipients_with_attachments = ["email" => adminEmail, "attachments" => []];
        sendEmail($recipients_with_attachments, $subject, $body);
    }
}


/// SEND Materials if self-study
add_action('edusteps_invoice_paid_webinars', 'send_self_study_materials', 10, 1);
function send_self_study_materials($invoice_ids, $selectedMaterials = array()) {
    global $wpdb;
    $log_filename = "/var/www/html/log";
    $log_file_data = $log_filename.'/innovation_send_materials_' . date('d-M-Y') . '.log';
    if (!file_exists($log_filename))
    {
        // create directory/folder uploads.
        mkdir($log_filename, 0777, true);
    }
    try {
        $form_ids = $wpdb->get_col("SELECT form_id FROM uirgkqdb7_forms WHERE invoice_id IN ($invoice_ids)");
        file_put_contents($log_file_data, date('d-M-Y H:i:s') . " - Invoice IDs: " . $invoice_ids . " - Form IDs: " . implode(", ", $form_ids) . "\n", FILE_APPEND);
        $table_topic_name = $wpdb->prefix . 'iv_topics';

        foreach ($form_ids as $form_id) {
            $selectedMaterials_query = "";
            $form_data = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}db7_forms WHERE form_id = %d", $form_id), ARRAY_A);
            file_put_contents($log_file_data, date('d-M-Y H:i:s') . " - Processing Form ID: " . $form_id . "\n", FILE_APPEND);
            if ($form_data) {
                $form_value = unserialize($form_data['form_value']);

                if ($form_value['sposob-vzdelavania'][0] != 'Samoštúdium') {
                    file_put_contents($log_file_data, date('d-M-Y H:i:s') . " - Skipping Form ID: " . $form_id . " as the learning mode is not self-study.\n", FILE_APPEND);
                    continue;
                }

                $learning_mode = 0;

                $form_meta = get_post_meta($form_data['form_post_id']);
                if ($form_meta == null){
                    file_put_contents($log_file_data, date('d-M-Y H:i:s') . " - ERROR: No form meta found for Form ID: " . $form_id . "\n", FILE_APPEND);
                }

                $topic_name = $wpdb->get_var($wpdb->prepare("SELECT topic_name FROM $table_topic_name WHERE ID = %d" , $form_meta['innovationTopic'][0]));

                $to = array("email" => $form_value['email-ucastnika'], "attachments" => array());
                
                $table_email_body = $wpdb->prefix . 'iv_topics_material_emails';

                if (count($selectedMaterials) > 0 && !in_array(99, $selectedMaterials)) {
                    $selectedMaterials_query = " AND `order` IN (" . implode(",", array_map('intval', $selectedMaterials)) . ") ";    
                } else {
                    $selectedMaterials_query = "";
                }

                $query = $wpdb->prepare("SELECT email, `order` FROM $table_email_body WHERE iv_topic_id = %d AND `order` > 0 AND learning_mode = %d " . $selectedMaterials_query . " AND is_visible = 1 ORDER BY `order` ASC" , $form_meta['innovationTopic'][0], $learning_mode);
                $material_emails = $wpdb->get_results($query, ARRAY_A);

                file_put_contents($log_file_data, date('d-M-Y H:i:s') . " - Found " . count($material_emails) . " material emails for Form ID: " . $form_id . "\n", FILE_APPEND);

                $admin_email_body = "";

                foreach ($material_emails as $single_mail_with_materials) {
                    if ($single_mail_with_materials['order'] < 5) {
                        $subject = "Materiály k inovačnému vzdelávaniu formou samoštúdia - " . $topic_name . " - " . $single_mail_with_materials['order'] . ". časť";
                    } else {
                        $subject = "Informácie k záverečnej prezentácii a obhajobe - " . $topic_name;
                    }

                    $message = $single_mail_with_materials['email'];
                    
                    sendEmail($to, $subject, $message);

                    $admin_email_body .= "<b><u>Znenie emailu č. " . $single_mail_with_materials['order'] . ":</u></b><br><br>" . $message . "<br><br>";
                }
                
                $recipients_with_attachments = ["email" => adminEmail, "attachments" => []];
                if (count($material_emails) == 0) {
                    $body = "Po registrácii na IV na tému - ".$topic_name." neboli nájdené žiadne materiály na samoštúdium, preto nič nebolo odoslané účastníkovi " . $form_value['email-ucastnika'] . ".<br><br>";
                    $subject = "Neboli nájdené žiadne materiály na samoštúdium na tému - ".$topic_name." pre " . $form_value['email-ucastnika'];
                } else {
                    $body = "Boli odoslané materiály na samoštúdium - ".$topic_name." - pre " . $form_value['email-ucastnika'] . ".<br><br>" . $admin_email_body;
                    $subject = "Boli odoslané materiály na samoštúdium - ".$topic_name." - pre " . $form_value['email-ucastnika'];

                    try {
                        $sql = $wpdb->prepare("UPDATE {$wpdb->prefix}db7_forms SET innovation_materials_sent = 1 WHERE form_id = %d", $form_id);
                        $wpdb->query($sql);
                    } catch (Exception $e) {
                    }
                }
                sendEmail($recipients_with_attachments, $subject, $body);
            } else {
                file_put_contents($log_file_data, date('d-M-Y H:i:s') . " - ERROR: No form data found for Form ID: " . $form_id . "\n", FILE_APPEND);
            }
        }
    } catch (Exception $e) {
        file_put_contents($log_file_data, date('d-M-Y H:i:s') . " - " . $e->getMessage() . "\n", FILE_APPEND);
    }
}


function sendIvMaterialsFromAdminPage(){
    $attendeeId = isset($_POST['attendeeId']) ? intval($_POST['attendeeId']) : 0;
    $selectedMaterials = isset($_POST['selectedMaterials']) ? json_decode(stripslashes($_POST['selectedMaterials'])) : array();

    if ($attendeeId == 0) {
        return;
    }

    global $wpdb;
    $form_data = $wpdb->get_row($wpdb->prepare("SELECT invoice_id FROM {$wpdb->prefix}db7_forms WHERE form_id = %d", $attendeeId), ARRAY_A);
    if (!$form_data || $form_data['invoice_id'] == "" || $form_data['invoice_id'] == 0) {
        wp_send_json_error("Účastník nemá pridelenú faktúru", 400);
        return;
    }
    $invoice_id = $form_data['invoice_id'];
    send_self_study_materials($invoice_id, $selectedMaterials);

    //update paid column with the word paid
    $wpdb->update(
        "{$wpdb->prefix}db7_forms",
        array('paid' => 'paid'),
        array('form_id' => $attendeeId)
    );

    wp_send_json_success("Materiály boli úspešne odoslané.", 200);
}


add_filter( 'cfdb7_after_save_data', 'assign_person_to_innovation_self_study_group', 11, 1 );
function assign_person_to_innovation_self_study_group( $insert_id ) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'db7_forms';
    $registration_details = $wpdb->get_results("SELECT form_post_id, form_value FROM {$table_name} WHERE form_id = {$insert_id}");
    $form_post_id = $registration_details[0]->form_post_id;
    $form_value = unserialize($registration_details[0]->form_value);
    $main_form_meta = get_post_meta( $form_post_id);

    if ($main_form_meta['courseType'][0] == 'inovacny') {

        if ($form_value['sposob-vzdelavania'][0] != 'Samoštúdium') {
            return;
        }

        $selfStudyGroupName = new InnovationGroupSelfStudy();
        $selfStudyGroupName->fillDetails(date("Y"), date("m"), $main_form_meta['innovationProgramShortcut'][0]);

        $lastUserNumberInTheGroup = get_last_added_user_to_innovation_group($selfStudyGroupName->generateGroupName());
        $selfStudyGroupName->userNumber = $lastUserNumberInTheGroup == 0 || $lastUserNumberInTheGroup == "" ? 1 : intval($lastUserNumberInTheGroup) + 1;

        try {
            $userAssignedGroup = $selfStudyGroupName->generateEvidenceNumber();
            $wpdb->update(
                $table_name,
                array('innovation_group' => $userAssignedGroup),
                array('form_id' => $insert_id)
            );   
        } catch (Exception $e) {
            echo $e->getMessage();
        } 
    }
}

