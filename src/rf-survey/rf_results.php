<?php
    global $twig;
    global $wpdb;

    wp_enqueue_style('rf-results-css', plugin_dir_url( __FILE__ ) . '/css/rf-results.css');

    $surveyId = $_GET['surveyId'];
    
    $survey = get_field('survey', $surveyId);

    $results = $wpdb->get_results( "SELECT * FROM wp_rf_survey_results WHERE survey_id = '". $surveyId . "'" );


    $userId = $wpdb->get_results( "SELECT `result_id` FROM `wp_rf_survey_results` WHERE `survey_id` = '". $surveyId . "' group by `result_id`");
    $totalUsers = count($userId);

    if(isset($_GET['view'])) {
        $questionId = $_GET['view'];
        $getTextResponse = $wpdb->get_results("SELECT * FROM  `wp_rf_survey_results`  WHERE  `survey_id` = '". $surveyId . "' AND  `question_id` = '". $questionId ."' ");
        
       // echo '<div class="wrap">' . $twig->render('rf-text-response.html', array('responses'=>$getTextResponse, 'questionId'=>$questionId)) . '</div>';
        //die;
    }

    // if(isset($_GET['download'])) {
    //     $fp = fopen('file.csv', 'w');

    //     foreach ($results as $response) {
    //         $temp = array();
    //         array_push($temp, $response->question_id);
    //         array_push($temp, $response->result_id);
    //         array_push($temp, $response->answer);
    //         fputcsv($fp, $temp);
    //     }

    //     fclose($fp);
    // }

    if(isset($_GET['download'])) {
        $fp = fopen(basename(get_permalink($surveyId)) . '.csv', 'w');

        $questions = array();
        $default = array();
        array_push($questions, 'User ID');
        foreach($survey as $question) {
            array_push($questions, strip_tags($question['question']));
            array_push($default, ' ');
        }
        fputcsv($fp, $questions);

        $answers = array();
        foreach ($results as $response) {
            if(!isset($answers[$response->result_id])){
                $answers[$response->result_id] = $default;

                $answers[$response->result_id][0] = $response->result_id;
            }

            $answer = $response->answer;
            $answer = str_replace(array("\n", "\r"), ' ', $answer);
            $answers[$response->result_id][$response->question_id] = $answer;
        }
        // print_r($answers);

        foreach ($answers as $answer) {
            fputcsv($fp, $answer);
        }

        //die;

        fclose($fp);
    }


    foreach ($results as $result) {
        $questionId = $result->question_id;
        $question = &$survey[$questionId - 1];

        if(!isset($question['results'])) {
            $question['results'] = array();
        }
        if($question['type'] == 'emoji' || $question['type'] == 'score' || ($question['type'] == 'multiple' && $question['allowed_choices'] == 1)) {
           $question['results'][$result->answer - 1]++;
        } else if(($question['type'] == 'multiple' && $question['allowed_choices'] > 1)) {
            
            $answers = explode(',',$result->answer);

            foreach ($answers as $answer) {
                $question['results'][$answer - 1]++;
            }
        } else if($question['type'] == 'group') {
            $answers = explode(',',$result->answer);
            $q = 0;
            foreach ($answers as $answer) {
                if(!isset($question['results'][$q])) {
                    $question['results'][$q] = array();
                }
                $question['results'][$q][$answer - 1]++;
                $q++;
            }
        }
    }

    foreach ($survey as &$question) {
        if($question['type'] == 'emoji' || $question['type'] == 'score' || $question['type'] == 'multiple') {
            $total = array_sum($question['results']);

            $question['percentages'] = array();

            foreach ($question['results'] as $key => $answer) {
                $question['percentages'][$key] = 100 - ($answer / $total * 100);
            }
        } else if($question['type'] == 'group') {

            foreach ($question['results'] as $id=>$q) {
                $total = array_sum($q);

                if(!isset($question['percentages'])) {
                    $question['percentages'] = array();
                }
                foreach ($q as $key => $answer) {
                    $question['percentages'][$id][$key] = 100 - ($answer / $total * 100);
                }
            }
        }
    }

    $siteUrl = get_site_url();

?>

    <div class="wrap">
        <h2>Results</h2>

        <?php 

            if(isset($_GET['view'])) {
                echo $twig->render('rf-text-response.html', array('responses'=>$getTextResponse, 'questionId'=>$questionId));
            } else {
                echo $twig->render('rf-results.html', array('questions'=>$survey, 'totalUsers'=> $totalUsers, 'surveyId'=>$surveyId, 'siteUrl'=>$siteUrl, 'surveySlug' => basename(get_permalink($surveyId)))); 
            }
        ?>
    </div>