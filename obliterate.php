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

    const PREFIX = 'obliterate_';

    public $lists = array (
        'obliterate_disable_user_from_sitemap' => 'XML サイトマップからユーザーを除外',
        'obliterate_disable_author_archive' => '著者アーカイブページを無効化',
        'obliterate_disable_xml_rpc' => 'XML-RPC を無効化',
        'obliterate_disable_comment_form' => 'コメントフォームを無効化',
        'obliterate_disable_comment_feed' => 'コメントフィードを無効化',
        'obliterate_disable_comment_header' => 'コメントフィードのヘッダ出力を無効化',
        'obliterate_disable_rest_api' => 'REST APIを無効化',
        'obliterate_disable_emoji' => 'emoji を無効化',
        'obliterate_disable_wlwmanifest' => 'ヘッダから wlwmanifest を削除',
        'obliterate_disable_rsd' => 'ヘッダから rsd を削除',
        'obliterate_disable_shortlink' => 'ヘッダからショートリンクを削除',
    );

    function __construct() {
        # オプションページ
        add_action( 'admin_menu', array( $this, 'obliterate_settings_menu' ) );
        # オプションページの項目
        add_action( 'admin_init', array( $this, 'obliterate_settings_api_init') );

        # 著者アーカイブ無効化
        if( get_option( 'obliterate_disable_author_archive' ) ) {
            add_filter( 'query_vars', array( $this, 'disable_author_archive' ) );
            add_action( 'template_redirect', array( $this,'redirect_author_to_home' ) );
        }

        # XML-RPC無効化
        if( get_option( 'obliterate_disable_xml_rpc' ) ) {
            add_action( 'init', array( $this, 'disable_xml_rpc' ) );
        }

        # コメントフォーム無効化
        if( get_option( 'obliterate_disable_comment_form' ) ) {
            add_action( 'template_redirect', array( $this,'disable_all_comment_form' ) );
        }

        # コメントフィード機能の無効化
        if( get_option( 'obliterate_disable_comment_feed' ) ) {
            add_action( 'do_feed_rss2', array( $this, 'remove_comment_feeds' ), 9, 1 );
            add_action( 'do_feed_atom', array( $this, 'remove_comment_feeds' ), 9, 1 );
        }

        # コメントフィードのヘッダ出力無効化
        if( get_option( 'obliterate_disable_comment_header' ) ) {
            remove_action( 'wp_head', 'feed_links_extra', 3 );
            remove_action( 'wp_head', 'feed_links', 2 );
        }
 
        # XML サイトマップからユーザーページを削除する
        if( get_option( 'obliterate_disable_user_from_sitemap' ) ) {
            add_filter( 'wp_sitemaps_add_provider', array( $this, 'disable_all_user_from_sitemap' ), 99, 2 );
        }

        # REST API無効化
        if( get_option( 'obliterate_disable_rest_api' ) ) {
            remove_action( 'template_redirect', 'rest_output_link_header', 11 );
            remove_action( 'wp_head', 'rest_output_link_wp_head', 10 );
            add_filter( 'rest_authentication_errors', array( $this, 'disable_rest_api') );
        }

       # リビジョンの無効化
#        add_action( 'wp_print_scripts', array( $this, 'remove_revision_script') );
#        add_filter( 'wp_revisions_to_keep', array( $this, 'disable_revision_count' ), 999, 2 );

        # ブロックエディタの自動保存長期遅延
#        add_filter( 'block_editor_settings', 'custom_autosave_interval' );

        # emoji関連の排除
        if( get_option( 'obliterate_disable_emoji' ) ) {
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
        }

        # ヘッダから wlwmanifest を削除
        if( get_option( 'obliterate_disable_wlwmanifest' ) ) {
            remove_action( 'wp_head', 'wlwmanifest_link' );
        }

        # ヘッダから rsd を削除
        if( get_option( 'obliterate_disable_rsd' ) ) {
            remove_action( 'wp_head', 'rsd_link' );
        }

        # ヘッダからショートリンクを削除
        if( get_option( 'obliterate_disable_shortlink' ) ) {
            remove_action( 'wp_head', 'wp_shortlink_wp_head' );
        }
    }

    public function disable_author_archive( $vars ) {
        if( ( $key = array_search( 'author', $vars ) ) !== false) {
            unset( $vars[$key] );
    }
        return $vars;
    }

    public function redirect_author_to_home() {
        if ( is_author() ) :
            wp_redirect( home_url() );
            exit;
        endif;
    }

    public function disable_all_user_from_sitemap ( $provider, $name ) {
        if ( 'users' === $name ) {
            return null;
        }
        return $provider;
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


    /**
     * オプションページ
     */
    public function obliterate_settings_menu() {
        add_options_page(
            'Obliterate', // ページのタイトル
            'Obliterate', // メニューのタイトル
            'manage_options', // このページを操作する権限
            'obliterate_settings', // ページ名
            array( $this, 'obliterate_settings_plugin_options' ), // コールバック関数。この関数の実行結果が出力される
        );
    }

    public function obliterate_settings_plugin_options() { ?>
        <div class="wrap">
           <form action="options.php" method="post">
            <?php settings_fields('obliterate_settings-group'); // グループ名 ?>
            <?php do_settings_sections( 'obliterate_settings'); // ページ名 ?>
            <?php submit_button(); ?>
        </form>
    </div> <?php
    }

    /**
     * オプションメニュー
     * @see https://wpdocs.osdn.jp/Settings_API
     */
    public function obliterate_settings_api_init() {
        add_settings_section(
            'obliterate_setting_section',
            'Obliterate Settings',
            array( $this, 'obliterate_section_callback' ),
            'obliterate_settings',
        );
        

    foreach( $this->lists as $list => $value ) :
        # XMLサイトマップからユーザーを除外
        add_settings_field(
            $list,
            $value,
            array ( $this, 'callback' ),
            'obliterate_settings',
            'obliterate_setting_section',
            array( $list )
        );
 
        register_setting(
            'obliterate_settings-group',
            $list,
        );
    endforeach;

    }

    public function obliterate_section_callback() {
        echo '<h3>Obliterate 設定</h3>';
    } 
 
    public function callback( $args ) {
echo get_option($args[0]);
        $output = '<p><input class="code" name="' . $args[0] . '" id="' . $args[0] . '" type="checkbox" value="' . $args[0] . '"' . checked( $args[0], get_option( $args[0] ), false ) . ' /></p>';
        echo $output;
    }



} // class end
