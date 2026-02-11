<?php

function moveFromCfdb7ToFeedbacks($insert_id) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'db7_forms';
    $query = $wpdb->prepare("SELECT form_post_id, form_value FROM {$table_name} WHERE form_id = %d", $insert_id);
    $result = $wpdb->get_row($query, ARRAY_A);
    if ($result && $result['form_post_id'] == INNOVATION_FEEDBACK_FORM) {
        $group_name = unserialize($result['form_value'])['group_name'];
        $group_query = $wpdb->prepare("SELECT id FROM {$wpdb->prefix}innovation_groups WHERE group_name = %s", $group_name);
        $group = $wpdb->get_row($group_query);
        if ($group) {
            $group_id = $group->id;
        } else {
            // Insert new group if it doesn't exist
            $wpdb->insert(
                "{$wpdb->prefix}innovation_groups",
                ['group_name' => $group_name],
                ['%s']
            );
            $group_id = $wpdb->insert_id;
        }
        // Insert feedback into innovation_feedbacks table
        $wpdb->insert(
            "{$wpdb->prefix}innovation_feedbacks",
            [
                'form_post_id' => $result['form_post_id'],
                'form_value' => $result['form_value'],
                'form_date' => current_time('Y-m-d H:i:s'),
                'innovation_group' => $group_id
            ],
            [
                '%d',
                '%s',
                '%s',
                '%d'
            ]
        );

        // Optionally, you can delete the entry from db7_forms after moving
        $wpdb->delete($table_name, ['form_id' => $insert_id], ['%d']);
    }
}

add_filter( 'cfdb7_after_save_data', 'moveFromCfdb7ToFeedbacks', 12, 1 );

function show_innovation_feedback_form() {
	$form = do_shortcode('[contact-form-7 id="'.INNOVATION_FEEDBACK_FORM.'" title="Contact form 1"]');
    $form = str_replace('{{group_name}}', $_GET['group'], $form);
    return $form;
}
add_shortcode('show_innovation_feedback_form', 'show_innovation_feedback_form');

function getInnovationGroups() {
    global $wpdb;
    $table_name = $wpdb->prefix . "innovation_groups";
    $query = "SELECT group_name FROM {$table_name} ORDER BY group_name";
    $results = $wpdb->get_results($query, ARRAY_A);
    wp_send_json_success($results, 200);
}


function loadFeedbacks() {
    global $wpdb;
    $selectedInnovationGroup = $_POST['selectedInnovationGroup'];
    $table_name = $wpdb->prefix . "innovation_groups";
    $query = $wpdb->prepare("SELECT id FROM {$table_name} WHERE group_name = %s", $selectedInnovationGroup);
    $group = $wpdb->get_row($query);
    if ($group) {
        $table_name = $wpdb->prefix . "innovation_feedbacks";
        $query = $wpdb->prepare("SELECT form_value FROM {$table_name} WHERE innovation_group = %d", $group->id);
        $results = $wpdb->get_results($query, ARRAY_A);
        $data_ready_for_charts = prepareDataForCharts($results);
        echo json_encode($data_ready_for_charts, JSON_FORCE_OBJECT);
    } else {
        wp_send_json_error('Skupina neexistuje', 404);
    }
        
}


function prepareDataForCharts($submittedFeedbacks) {
    $accumulatedData = array();

    $questions_to_titles = array(
        'obsah-1' => ["type"=> "radio", "title" => "1. Ako hodnotíte obsah vzdelávania s ohľadom na obsah jednotlivých tém vzdelávania?"],
        'ciele-2' => ["type"=> "radio", "title" => "2. Ako hodnotíte dosiahnutie stanovených cieľov vzdelávania?"],
        'vedomosti-3' => ["type"=> "radio", "title" => "3. Ako hodnotíte rozsah a úroveň odborných vedomostí lektorky/lektora?"],
        'pristup-4' => ["type"=> "radio", "title" => "4. Ste spokojná/ý s prístupom lektorky/lektora k účastníkom vzdelávania?"],
        'komunikacia-5' => ["type"=> "radio", "title" => "5. Ako hodnotíte úroveň komunikácie lektorky/lektora s účastníkmi vzdelávania?"],
        'formy-6' => ["type"=> "radio", "title" => "6. Ako hodnotíte použité formy vzdelávania?"],
        'metody-7' => ["type"=> "radio", "title" => "7. Ako hodnotíte použité metódy vzdelávania?"],
        'dostupnost-8' => ["type"=> "radio", "title" => "8. Ako hodnotíte dostupnosť a ochotu lektorky/lektora - konzultácie v prípade potreby, elektronická komunikácia a pod.?"],
        'casova-dotacia-9' => ["type"=> "radio", "title" => "9. Ako hodnotíte celkovú časovú dotáciu vzdelávania vzhľadom na obsah vzdelávania?"],
        'miesto-10' => ["type"=> "radio", "title" => "10. Ako hodnotíte dostupnosť miesta vzdelávania?"],
        'harmonogram-11' => ["type"=> "radio", "title" => "11. Ako hodnotíte časový harmonogram vzdelávania? Vyhovovalo Vám rozvrhnutie vzdelávania?"],
        'frekvencia-12' => ["type"=> "radio", "title" => "12. Vyhovovala Vám frekvencia vzdelávacích aktivít?"],
        'vybavenie-13' => ["type"=> "radio", "title" => "13. Ako hodnotíte materiálno-technické a priestorové vybavenie miesta vzdelávania?"],
        'technologie-14' => ["type"=> "radio", "title" => "14. Ako hodnotíte využívanie digitálnych technológií pri vzdelávaní?"],
        'materialy-15' => ["type"=> "radio", "title" => "15. Ste spokojná/ý s poskytnutými vzdelávacími materiálmi?"],
        'komunikacia-pred-16' => ["type"=> "radio", "title" => "16. Ako hodnotíte komunikáciu organizátorov s účastníkmi vzdelávania pred vzdelávaním?"],
        'komunikacia-pocas-17' => ["type"=> "radio", "title" => "17. Ako hodnotíte komunikáciu organizátorov s účastníkmi vzdelávania počas vzdelávania?"],
        'komunikacia-po-18' => ["type"=> "radio", "title" => "18. Ako hodnotíte komunikáciu organizátorov s účastníkmi vzdelávania po ukončení vzdelávania?"],
        'atmosfera-19' => ["type"=> "radio", "title" => "19. Hodnotíte atmosféru vzdelávania ako pozitívnu a tvorivú?"],
        'dovody-atmosfera-20' => ["type"=> "written", "title" => "20. Ak ste na predchádzajúcu otázku odpovedali nie, môžete uviesť dôvody Vášho hodnotenia:"],
        'expert-21' => ["type"=> "radio", "title" => "21. Považujete lektorku/lektora za experta v danom obsahu vzdelávania?"],
        'dovody-expert-22' => ["type"=> "written", "title" => "22. Ak ste na predchádzajúcu otázku odpovedali nie, môžete uviesť dôvody Vášho hodnotenia:"],
        'priestor-23' => ["type"=> "radio", "title" => "23. Mali ste dostatočný priestor na prezentovanie vlastných poznatkov, skúseností, príp. zručností?"],
        'vyuzitie-praca-24' => ["type"=> "radio", "title" => "24. Považujete nadobudnuté poznatky, skúsenosti a zručnosti za využiteľné vo Vašej pracovnej činnosti?"],
        'dovody-praca-25' => ["type"=> "written", "title" => "25. Ak ste na predchádzajúcu otázku odpovedali nie, môžete uviesť dôvody Vášho hodnotenia:"],
        'vyuzitie-profesijny-26' => ["type"=> "radio", "title" => "26. Považujete nadobudnuté poznatky, skúsenosti a zručnosti za využiteľné vo Vašom ďalšom profesijnom rozvoji?"],
        'dovody-profesijny-27' => ["type"=> "written", "title" => "27. Ak ste na predchádzajúcu otázku odpovedali nie, môžete uviesť dôvody Vášho hodnotenia:"],
        'odporucenie-28' => ["type"=> "radio", "title" => "28. Odporučili by ste absolvované vzdelávanie svojim kolegom?"],
        'dovody-odporucenie-29' => ["type"=> "written", "title" => "29. Ak ste na predchádzajúcu otázku odpovedali nie, môžete uviesť dôvody Vášho hodnotenia:"],
        'temy-30' => ["type"=> "written", "title" => "30. Akú/é tému/y by ste doplnili do obsahu vzdelávacej aktivity?"],
        'materialy-forma-31' => ["type"=> "radio", "title" => "31. Boli Vám poskytnuté učebné materiály? V digitálnej alebo tlačenej forme?"],
        'metody-32' => ["type"=> "checkbox", "title" => "32. Vyberte príp. doplňte metódy, ktoré boli využité počas vzdelávacej aktivity"],
        'odkaz-33' => ["type"=> "written", "title" => "33. Môžete nám zanechať akýkoľvek odkaz"],
    );

    // Define a class for question data
    class QuestionData {
        public $question;
        public $selectedAnswers;
        public $writtenAnswers;
        public $answersType;

        public function __construct($cf7_question) {
            $this->question = $cf7_question;
            $this->selectedAnswers = [];
            $this->writtenAnswers = [];
        }

        public function addAnswer($answer) {
            if (is_array($answer)) {
                $key = (string)$answer[0];
                if (!isset($this->selectedAnswers[$key])) {
                    $this->selectedAnswers[(string)$key] = 1;
                } else {
                    $this->selectedAnswers[(string)$key] += 1;
                }
            } else {
                $this->writtenAnswers[] = $answer;
            }
        }
    }

    // loop through result rows and prepare data for charts and add key the title from $questions_to_titles, group values
    foreach ($submittedFeedbacks as $key => $singleFeedback) {
        $form = unserialize($singleFeedback['form_value']);
        //remove cfdb7_status and group_name from $form
        unset($form['cfdb7_status']);
        unset($form['group_name']);
        foreach ($form as $cf7_question => $answer) {
            $title = $questions_to_titles[$cf7_question]['title'];

            if (!isset($accumulatedData[$cf7_question])) {
                $accumulatedData[$cf7_question] = new QuestionData($title);
            }
            $accumulatedData[$cf7_question]->answersType = $questions_to_titles[$cf7_question]['type'];
            $accumulatedData[$cf7_question]->addAnswer($answer);
        }
    }

    // sort selectedAnswers for each question data
    foreach ($accumulatedData as $cf7_question => $questionData) {
        if ($questionData->answersType === 'radio') {
            arsort($questionData->selectedAnswers);
        }
    }

    return array_values($accumulatedData);
}