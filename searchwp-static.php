<?php
/**
 * Plugin Name:     Searchwp Static
 * Plugin URI:      https://www.gusmanson.nl
 * Description:     Add non-Wordpress content to SearchWP
 * Author:          Mgussekloo
 * Text Domain:     searchwp-static
 * Domain Path:     /languages
 * Version:         0.1.0
 *
 * @package         Searchwp_Static
 */

// Your code starts here.

namespace SearchWPStatic;

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die;
}

// Requirements

// require __DIR__ . '/vendor/autoload.php';
require __DIR__ . '/src/options.php';

// Add schedule

if ( ! wp_next_scheduled( 'search_wp_static_sync' ) ) {
    wp_schedule_event( time(), 'daily', 'search_wp_static_sync' );
}

// Unschedule on plugin disable

register_deactivation_hook( __FILE__, function() {
    $timestamp = wp_next_scheduled( 'search_wp_static_sync' );
    wp_unschedule_event( $timestamp, 'search_wp_static_sync' );
});

// Init

add_action( 'init', function() {
    $Options = new Options();

    add_action( 'search_wp_static_sync', function() {
        $urls = array_filter(array_map('trim', explode("\n", get_option('search_wp_static_urls'))));

        $new_ids = [];

        foreach ($urls as $url) {
            $relative_url = wp_make_link_relative($url);

            $post_content = apply_filters( 'search_wp_static_post_content', null, $url );
            if (is_null($post_content)) {
                $post_content = file_get_contents($url);
            }

            $post_title = apply_filters( 'search_wp_static_post_title', null, $url );
            if (is_null($post_title)) {
                $post_title = 'Redirect to ' . $relative_url;
            }

            $posts = get_posts([
                'meta_query'        => [
                    [
                        'key'       => 'search_wp_static_redirect',
                        'value'     => $url
                    ]
                ],
                'post_type'         => 'page',
                'posts_per_page'    => '1',
            ]);

            $post_data = [
                'post_title' => $post_title,
                'post_content' => $post_content,
                'post_type' => 'page',
            ];

            if ( ! $posts || is_wp_error( $posts ) ) {
                $post_data['ID'] = 0;
                $post_data['post_status'] = 'publish';
            } else {
                $post_data['ID'] = $posts[0]->ID;
                $post_data['post_status'] = $posts[0]->post_status;
            }

            $new_id = wp_insert_post( $post_data );
            $new_ids[] = $new_id;

            update_post_meta( $new_id, 'search_wp_static_redirect', $url );
            do_action('search_wp_static_post_updated', $new_id, $url);
        }

        update_option( 'search_wp_static_post_ids', $new_ids );
    } );

    add_action( 'template_redirect', function() {
        if (!is_single('page')) {
            return;
        }

        if (is_admin()) {
            return;
        }

        global $post;

        $redirect = get_post_meta( $post->ID , 'search_wp_static_redirect' , true );
        if ($redirect != '') {
            wp_redirect( $redirect );
            exit;
        }
    });
});

// Hide dummy page from all users except admin

add_action('pre_get_posts', function( $wp_query ) {
    global $pagenow;

    if(!is_admin()) {
        return;
    }

    if (current_user_can('administrator')) {
        return;
    }

    if ($pagenow != 'edit.php') {
        return;
    }

    if ($_GET['post_type'] != 'page') {
        return;
    }

    $post_ids = (array)get_option('search_wp_static_post_ids');
    $wp_query->set( 'post__not_in', $post_ids );

    add_filter('views_edit-page', function($views) use ($post_ids, $wp_query) {
        unset($views['mine']);

        $types = array(
            array( 'status' =>  NULL ),
            array( 'status' => 'publish' ),
            array( 'status' => 'draft' ),
            array( 'status' => 'pending' ),
            array( 'status' => 'trash' )
        );
        foreach( $types as $type ) {
            $query = array(
                'post__not_in' => $post_ids,
                'post_type'   => 'post',
                'post_status' => $type['status']
            );
            $result = new \WP_Query($query);
            if( $type['status'] == NULL ):
                $class = ($wp_query->query_vars['post_status'] == NULL) ? ' class="current"' : '';
                $views['all'] = sprintf(
                '<a href="%1$s"%2$s>%4$s <span class="count">(%3$d)</span></a>',
                admin_url('edit.php?post_type=post'),
                $class,
                $result->found_posts,
                __('All')
            );
            elseif( $type['status'] == 'publish' ):
                $class = ($wp_query->query_vars['post_status'] == 'publish') ? ' class="current"' : '';
                $views['publish'] = sprintf(
                '<a href="%1$s"%2$s>%4$s <span class="count">(%3$d)</span></a>',
                admin_url('edit.php?post_type=post'),
                $class,
                $result->found_posts,
                __('Publish')
            );
            elseif( $type['status'] == 'draft' ):
                $class = ($wp_query->query_vars['post_status'] == 'draft') ? ' class="current"' : '';
                $views['draft'] = sprintf(
                '<a href="%1$s"%2$s>%4$s <span class="count">(%3$d)</span></a>',
                admin_url('edit.php?post_type=post'),
                $class,
                $result->found_posts,
                __('Draft')
            );
            elseif( $type['status'] == 'pending' ):
                $class = ($wp_query->query_vars['post_status'] == 'pending') ? ' class="current"' : '';
                $views['pending'] = sprintf(
                '<a href="%1$s"%2$s>%4$s <span class="count">(%3$d)</span></a>',
                admin_url('edit.php?post_type=post'),
                $class,
                $result->found_posts,
                __('Pending')
            );
            elseif( $type['status'] == 'trash' ):
                $class = ($wp_query->query_vars['post_status'] == 'trash') ? ' class="current"' : '';
                $views['trash'] = sprintf(
                '<a href="%1$s"%2$s>%4$s <span class="count">(%3$d)</span></a>',
                admin_url('edit.php?post_type=post'),
                $class,
                $result->found_posts,
                __('Trash')
            );
            endif;
        }
        return $views;
    });
});