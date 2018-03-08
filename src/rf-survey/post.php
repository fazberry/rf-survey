<?php
    function insertSurveyResults($surveyId, $questionId, $resultId, $answer) {
        global $wpdb;

        $table_name = 'rf_survey_results';
        $wpdb->insert( $table_name, array(
            'survey_id'       => $surveyId,
            'question_id'   => $questionId,
            'result_id'     => $resultId,
            'answer'        => $answer
        ));
    }


    if ($_SERVER['REQUEST_METHOD'] == 'POST') {

        $surveyId = 19;
        $resultId = rand(1, 99999);
        $questions = $_POST;

    	foreach ($questions as $key=>$value) {
            $questionId = $key;
            if(substr($questionId, 0, 1) == 'q'){
                $questionId = substr($questionId, 1);
            } else {
                continue;
            }
            $answer = $value;
            if(is_array($answer)){
                $answer = implode(',', $answer);
            }

            insertSurveyResults($surveyId, $questionId, $resultId, $answer); 
        }
   
    }