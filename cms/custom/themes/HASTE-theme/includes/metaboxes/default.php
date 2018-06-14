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
    
    // For projects
    
    $prefix = 'projects_';
    
    $meta_boxes[] = array(

        'title'      => __( 'Client', 'textdomain' ),
        'post_types' => array( 'project'),
        'fields' => array(
            array(
                'name'    => 'Client',
                'id'      => $prefix . 'site_title',
                'type'    => 'taxonomy_advanced',

                // Taxonomy slug.
                'taxonomy'   => 'category',

                // How to show taxonomy.
                'field_type' => 'select_advanced',
            ),
        ),
    );
    
    return $meta_boxes;
}

?>