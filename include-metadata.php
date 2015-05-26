<?php

    $subtitle = get_post_meta( get_the_ID(), 'newsml_meta_subtitle', true );
    if ( ! empty( $subtitle ) ) {
        echo '<h2>' . $subtitle . '</h2>';
    }

    echo '<div>' . the_time( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ) ) . '</div>';

    $copyrightholder = get_post_meta( get_the_ID(), 'newsml_meta_copyrightholder', true );

    if ( ! empty( $copyrightholder ) ) {
        echo '<strong>' . __( 'Author:', 'newsml-import' ) . ' </strong>' . $copyrightholder . '<br>';
    }

    $terms = get_the_terms( get_the_ID(), 'newsml_mediatopic' );
    if ( $terms && ! is_wp_error( $terms ) ) :

        $tax_links = array();

        foreach ( $terms as $term ) {
            $term_link = get_term_link( $term );
            if ( is_wp_error( $term_link ) ) {
                continue;
            }

            $tax_links[] = '<a href="' . esc_url( $term_link ) . '">' . $term->name . '</a></li>';
        }

        $tax = implode( " / ", $tax_links );
        echo '<strong>' . __( 'Categories:', 'newsml-import' ) . ' </strong>' . $tax;
    endif;


    $locations = get_post_meta( get_the_ID(), 'newsml_meta_location', true );
    if ( ! empty( $locations ) ) {
        echo '<div><strong>' . __( 'Locations:', 'newsml-import' ) . ' </strong>' . $locations . '</div><br>';
    }

    /**
     * Load the children = images from the database
     */
    $images = get_children( array(
        'post_parent' => get_the_ID(),
        'post_type' => 'attachment',
        'post_mime_type' => 'image'
    ) );

    if ( empty( $images ) ) {
    } else {
        foreach ( $images as $attachment_id => $attachment ) {
            echo wp_get_attachment_link( $attachment_id, 'thumbnail' );
        }
    }