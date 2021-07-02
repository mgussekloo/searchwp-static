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

add_filter('cron_schedules',function($schedules){
    if(!isset($schedules['30min'])){
        $schedules['30min'] = [
            'interval' => 30 * 60,
            'display' => __('Once every 30 minutes')
        ];
    }
    return $schedules;
});

if ( ! wp_next_scheduled( 'search_wp_static_sync' ) ) {
    wp_schedule_event( time(), '30min', 'search_wp_static_sync' );
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

        foreach ($urls as $url) {
            $relative_url = wp_make_link_relative($url);

            $post_content = apply_filters( 'search_wp_static_post_content', null, $url );
            if (is_null($post_content)) {
                $post_content = file_get_contents($url);
            }

            $post_title = apply_filters( 'search_wp_static_post_title', null, $url );
            if (is_null($post_title)) {
                // $res = preg_match("/<title>(.*)<\/title>/siU", $post_content, $title_matches);
                // if ($res !== false) {
                //     $post_title = preg_replace('/\s+/', ' ', $title_matches[1]);
                //     $post_title = trim($post_title);
                // }

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

            update_post_meta( $new_id, 'search_wp_static_redirect', $url );
            do_action('search_wp_static_post_updated', $new_id, $url);
        }
    } );

    add_action( 'template_redirect', function() {
        global $post;

        $redirect = get_post_meta( $post->ID , 'search_wp_static_redirect' , true );
        if ($redirect != '') {
            wp_redirect( $redirect );
            exit;
        }
    });
});