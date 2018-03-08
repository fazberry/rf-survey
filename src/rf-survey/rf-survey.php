<?php

    /*
        Plugin Name: RF Survey
        Plugin URI: 
        Description: Survey Tool
        Version: 0.1
        Author: Rod Farry
    */

	require_once plugin_dir_path( __FILE__ ) . 'acf_fields.php';

    // Call Twig

    function findTwig() {
        global $twig;
        if(!isset($twig)) {
            require_once plugin_dir_path( __FILE__ ) . 'vendor/Twig/Autoloader.php';
            Twig_Autoloader::register();

            $loader = new Twig_Loader_Filesystem(plugin_dir_path( __FILE__ ) . 'templates');
            $twig = new Twig_Environment($loader, array(
                'debug' => true,
                'cache' => get_template_directory() . '/templates/cache',
                'auto_reload' => $debug
            ));

            // End call Twig

            $get_image = new Twig_SimpleFunction('getImage', function($id, $size) {
                $image = wp_get_attachment_image_src($id, $size);
                return $image[0];
            });
            $twig->addFunction($get_image);
        }
    }
    add_action( 'after_setup_theme', 'findTwig' );

    // Add Scripts and CSS

    wp_enqueue_style('survey-css', plugin_dir_url( __FILE__ ) . 'css/survey.css');
    wp_enqueue_script('survey-js', plugin_dir_url( __FILE__ ) . 'js/survey.js', array(), '0.1', true);


	// End add Styles and CSS



    function create_post_type() {
  		register_post_type( 'survey',
	    	array(
		      		'labels' => array(
		        	'name' => __( 'Surveys' ),
		        	'singular_name' => __( 'Survey' )
		      	),
		      	'public' => true,
		      	'has_archive' => true,
	    	)
	  	);
	}
	add_action( 'init', 'create_post_type' );


    // Add Custom post type to front page

    add_filter( 'get_pages', function ( $pages, $args ){
        if ( !is_admin() )
            return $pages;

        global $pagenow;
        if ( 'options-reading.php' !== $pagenow )
            return $pages;

        remove_filter( current_filter(), __FUNCTION__ );

        static $counter = 0;

        if ( 2 <= $counter )
            return $pages;

        $counter++;

        $args = [
            'post_type'      => 'survey',
            'posts_per_page' => -1
        ];

        $new_pages = get_posts( $args );    

        $pages = $new_pages;

        return $pages;
    }, 10, 2 );

    add_action( 'pre_get_posts', function ( $q ) {
        if (    !is_admin() 
             && $q->is_main_query()
             && 'page' === get_option( 'show_on_front' )
        ) {
            $q->set( 'post_type', 'survey' );
        }
    });


	// Create Survey Table

	function setupSurvey() {
		global $wpdb;

		$table_name = $wpdb->prefix . 'rf_survey_results';
		
		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE $table_name (
			`survey_id` int(11) NOT NULL,
			`question_id` int(11) NOT NULL,
			`result_id` int(11) NOT NULL,
			`answer` text COLLATE utf8_bin NOT NULL,
			`time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`survey_id`,`question_id`,`result_id`)
		) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_bin;";

		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
		dbDelta( $sql );

	}

	register_activation_hook( __FILE__, 'setupSurvey' );


	// End create table

	// Create Short Code for Themed custom loop

	add_shortcode('survey', 'shortcode_query');

	function shortcode_query($atts, $content){

		global $twig;

	  	$survey_id = $atts['id'];

        $survey = getSurvey($survey_id);
	  	
	  	return $twig->render('survey.html', array('survey'=> $survey, 'shortcode'=>'shortcode'));
	}

    function getSurvey($survey_id) {

        global $twig;

        $twig->getLoader()->addPath(plugin_dir_path( __FILE__ ) . 'templates');

        setup_postdata($survey_id);

        $questions = get_field('survey', $survey_id);

        $completed = false;

        if (isset($_COOKIE['sequel-survey-' . $survey_id]) && get_field('submissions', $survey_id)) {
            $completed = true;
        }

        $response = array(
            'questions' => $questions,
            'completed' => $completed,
            'id'        => $survey_id
        );

        $response['title'] = get_the_title();
        $response['content'] = apply_filters('the_content', get_the_content($survey_id));

        $start_date = get_field('start_date', $survey_id);
        $end_date = get_field('end_date', $survey_id);

        if (!empty($end_date) && strtotime($end_date) < time()) {
            $response['message'] = 'This survey has finished';
        } else if (!empty($start_date) && strtotime($start_date) >= time()) {
            $response['message'] = 'This survey hasn\'t started yet';
        }

        return $response;
    }

	add_filter('single_template', 'rfSurveyTempate');

	function rfSurveyTempate($single) {
	    global $wp_query, $post;

      //  echo get_template_directory() . '/single-survey.php';

	    if($post->post_type == 'survey') {
            if(file_exists( get_template_directory() . '/single-survey.php')) {
                return get_template_directory() . '/single-survey.php';
            } else if(file_exists( plugin_dir_path( __FILE__ ) . '/single-survey.php')) {
	            return plugin_dir_path( __FILE__ ) . '/single-survey.php';
	        }
	    }

	    return $single;
	}

	add_action( 'wp_ajax_survey_submit', 'submitSurveyAjax' );
	add_action( 'wp_ajax_nopriv_survey_submit', 'submitSurveyAjax' );

	function insertSurveyResults($surveyId, $questionId, $resultId, $answer) {
        global $wpdb;

        $table_name = 'wp_rf_survey_results';
        $wpdb->insert( $table_name, array(
            'survey_id'       => $surveyId,
            'question_id'   => $questionId,
            'result_id'     => $resultId,
            'answer'        => $answer
        ));
    }


    function submitSurveyAjax() {

        $surveyId = $_POST['survey_id'];
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
            $submissions = get_field('submissions',  $surveyId);

            if($submissions) {
                setcookie('sequel-survey-' . $surveyId, $resultId, time()+31556926 ,'/');
            }
        }

        wp_die();
    }



    function rfSurveyResultsMenu() {
        add_submenu_page('edit.php?post_type=survey', 'Survey Results', 'Survey Results', 'manage_options', 'survey-results', 'rfSurveyResults');
    }
    add_action( 'admin_menu', 'rfSurveyResultsMenu' );


    function rfSurveyResults() {

        global $twig;

        $twig->getLoader()->addPath(plugin_dir_path( __FILE__ ) . 'templates');

        if(isset($_GET['surveyId'])) {
            include 'rf_results.php';
        } else {
            include 'rf_results_list.php';
        }
    }
