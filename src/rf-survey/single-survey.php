<?php 
    global $twig;
    global $wpdb;

	wp_head();

	$survey = getSurvey($post->ID);

	echo $twig->render('survey.html', array('survey'=>$survey));

	wp_footer();