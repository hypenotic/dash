<?php

/**
* README: Wordpress Custom Page Templates
* Within this ../cpt folder, place all the necessary CPTs if you want to
* break the templates up into separate pieces.
*/

/*
* Post Type: Page (Default)
*
* Dependencies:
* - List your
* - dependencies here
*
* Details:
* This files constructs the fields for a default WP 'page'.
*
*/

add_filter( 'rwmb_meta_boxes', 'pc_register_default' );
function pc_register_default( $meta_boxes ) {
    $prefix = '_page_';
    // ALL PAGES
    $meta_boxes[] = array(
        'title'      => __( 'Site Information', 'textdomain' ),
        'post_types' => array( 'page'),
        'fields' => array(
            array(
                'name'    => 'Site Title',
                'id'      => $prefix . 'site_title',
                'type'    => 'text',
            ),
            array(
                'name'    => 'Site Description',
                'id'      => $prefix . 'site_description',
                'type'    => 'text',
            )
        ),
    );
    $meta_boxes[] = array(
        'title'      => __( 'Repeating Sections', 'textdomain' ),
        'post_types' => array( 'page'),
        'fields' => array(
            array(
                'name'    => 'Body Text',
                'id'      => $prefix . 'body_text',
                'type'    => 'wysiwyg',

                // Set the 'raw' parameter to TRUE to prevent data being passed through wpautop() on save
                'raw'     => false,

                // Editor settings, see https://codex.wordpress.org/Function_Reference/wp_editor
                'options' => array(
                    'textarea_rows' => 4,
                    'teeny'         => true,
                ),
            )
        ),
    );
    return $meta_boxes;
}

?>