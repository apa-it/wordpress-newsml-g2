<?php
require( '../../../wp-blog-header.php' );

/**
 * Gets the latest 5 newsml_posts and echoes their permalinks as json.
 */
function get_urls() {
    global $post;

    $result = get_option( 'newsml-import' );

    $args = array(
        'posts_per_page' => $result['kiosk_count'],
        'post_type' => 'newsml_post',
    );

    $the_query = new WP_Query( $args );

    $postdata = array();

    if ( $the_query->have_posts() ) {
        while ( $the_query->have_posts() ) {

            $the_query->the_post();
            $postdata[] = get_the_permalink();
        }
        wp_reset_postdata();

        echo json_encode( $postdata );

    } else {
        echo '<p>' . __( 'Sorry, no posts matched your criteria.' ) . '</p>';
    }
}

/**
 * Echoes the duration in seconds we want to show each entry.
 *
 * @author Bernhard Punz
 */
function get_time_per_page() {
    $result = get_option( 'newsml-import' );

    $time = $result['kiosk_duration'];

    echo $time;
}

/**
 * Echoes the number of posts we want to show.
 *
 * @author Bernhard Punz
 */
function get_post_count() {
    $result = get_option( 'newsml-import' );

    $count = $result['kiosk_count'];

    echo $count;
}

?>