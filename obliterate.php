<?php
/*
Plugin Name: Obliterate
Plugin URI: 
Description: This spell can't be countered. Destroy all artifacts, creatures, and lands. They can't be regenerated.
Version: 6.R.R
Author: zerodaijin
Author URI: add.sh
Text Domain: obliterate
Domain Path: /languages
License: GPL2
*/

new Obliterate();

class Obliterate {
    function __construct() {
        add_filter( 'query_vars', array( $this, 'disable_author_archive' ) );
        add_action( 'init', array( $this, 'disable_xml_rpc' ) );
        add_action( 'template_redirect', array( $this,'disable_all_comment_form' ) );

        # REST API無効化
//        remove_action( 'template_redirect', 'rest_output_link_header', 11 );
//        remove_action( 'wp_head', 'rest_output_link_wp_head', 10 );
//        add_filter( 'rest_authentication_errors', array( $this, 'disable_rest_api') );

        # コメントフィード機能の無効化
        add_action( 'do_feed_rss2', array( $this, 'remove_comment_feeds' ), 9, 1 );
        add_action( 'do_feed_atom', array( $this, 'remove_comment_feeds' ), 9, 1 );

        # コメントフィードのヘッダ出力無効化
        remove_action( 'wp_head', 'feed_links_extra', 3 );
        remove_action( 'wp_head', 'feed_links', 2 );

        # リビジョンの無効化
        add_action( 'wp_print_scripts', array( $this, 'remove_revision_script') );
        add_filter( 'wp_revisions_to_keep', array( $this, 'disable_revision_count' ), 999, 2 );

        # ブロックエディタの自動保存長期遅延
        add_filter( 'block_editor_settings', 'custom_autosave_interval' );

		# emoji関連の排除
		remove_action( 'wp_head', 'wp_generator' );
        remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
        remove_action( 'admin_print_scripts', 'print_emoji_detection_script' );
        remove_action( 'wp_print_styles', 'print_emoji_styles' );
        remove_action( 'admin_print_styles', 'print_emoji_styles' );     
        remove_filter( 'the_content_feed', 'wp_staticize_emoji' );
        remove_filter( 'comment_text_rss', 'wp_staticize_emoji' );  
        remove_filter( 'wp_mail', 'wp_staticize_emoji_for_email' );
        add_filter( 'tiny_mce_plugins', 'disable_emojis_tinymce' );
        add_filter( 'emoji_svg_url', '__return_false' );
        remove_action( 'wp_head', 'wlwmanifest_link' );
        remove_action( 'wp_head', 'rsd_link' );
        remove_action( 'wp_head', 'wp_shortlink_wp_head' );
    }

    public function disable_author_archive( $vars ) {
        if( ( $key = array_search( 'author', $vars ) ) !== false) {
            unset( $vars[$key] );
        }
        return $vars;
	}

	public function disable_all_comment_form() {
		add_filter( 'comments_open', array( $this, '__return_false' ) );
	}

    public function remove_comment_feeds( $for_comments ) {
        if( $for_comments ){
            remove_action( 'do_feed_rss2', 'do_feed_rss2', 10, 1 );
            remove_action( 'do_feed_atom', 'do_feed_atom', 10, 1 );
        }
    }

    public function remove_xmlrpc_pingback_ping( $methods ) {
        unset( $methods['pingback.ping'] );
        return $methods;
	}

    public function disable_xml_rpc() {
        add_filter( 'xmlrpc_methods', array( $this, 'remove_xmlrpc_pingback_ping' ) );
        add_filter( 'xmlrpc_enabled', array( $this, '__return_false' ) );
    }

    public function disable_rest_api( $result ) {
        if ( ! empty( $result ) ) {
            return $result;
        }
        if ( ! is_user_logged_in() ) {
            return new WP_Error( 'rest_not_logged_in', 'You are not currently logged in.', array( 'status' => 401 ) );
        }
        return $result;
    }

    public function remove_revision_script() {
        wp_deregister_script( 'autosave' );
    }

    public function disable_revision_count( $num, $post ) {
        $num = 0;
        return $num;
    }

    public function custom_autosave_interval( $editor_settings ) {
        $editor_settings['autosaveInterval'] = 60 * 60 * 24 * 7;
        return $editor_settings;
    }

} // class end
