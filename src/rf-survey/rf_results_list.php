    <div class="wrap">
        <h2>Results</h2>
        <ul>
            <?php

                $posts = get_posts(array(   
                    'posts_per_page'   => 10,
                    'orderby'          => 'date',
                    'order'            => 'DESC',
                    'post_type' => 'survey'
                )); 

                foreach($posts as $post): 
                    setup_postdata($post);
                    $link = '?post_type=survey&page=survey-results&surveyId=' . $post->ID;
            ?>
                <li><a href="<?=$link;?>"><?= get_the_title($post->ID); ?></a></li>
            <?php endforeach; ?>
        </ul>
    </div>

