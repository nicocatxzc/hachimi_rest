<?php
/**
 * Plugin Name: Hachimi RestAPI
 * Plugin URI: https://nicocat.cc
 * Description: 为hachimi headless wordpress主题提供RESTful api支持
 * Author: nicocat
 * Author URI: https://github.com/nicocatxzc
 * Version: 1.0.0
 * Text Domain: hachimi
 * Requires at least: 6.2
 * Requires PHP: 8.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

include_once __DIR__."/auth/auth_init.php";
include_once __DIR__."/option.php";
include_once __DIR__."/wp-api-menus/wp-api-menus.php";
include_once __DIR__."/api/nav.php";
include_once __DIR__."/api/router.php";
include_once __DIR__."/api/settings.php";
include_once __DIR__."/api/theme_options.php";
include_once __DIR__."/api/comment.php";
include_once __DIR__."/api/gethead.php";
include_once __DIR__."/api/links.php";
include_once __DIR__."/blocks/index.php";
include_once __DIR__."/api/cache.php";
include_once __DIR__."/api/post_views.php";

// 安装时注册令牌管理表
function hachimi_install()
{
    global $wpdb;

    $table = $wpdb->prefix . 'hachimi_tokens';
    $charset = $wpdb->get_charset_collate();

    $sql = "
    CREATE TABLE {$table} (
        id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        token_hash CHAR(64) NOT NULL,
        user_id BIGINT UNSIGNED NOT NULL,
        expires_at BIGINT UNSIGNED NOT NULL,
        created_at BIGINT UNSIGNED NOT NULL,
        PRIMARY KEY  (id),
        KEY token_hash (token_hash),
        KEY user_id (user_id)
    ) {$charset};
    ";

    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    dbDelta($sql);
}
register_activation_hook(__FILE__, 'hachimi_install');

// 每天清理过期令牌
register_activation_hook(__FILE__, function () {
    if (!wp_next_scheduled('hachimi_cleanup_tokens')) {
        wp_schedule_event(time(), 'daily', 'hachimi_cleanup_tokens');
    }
});

register_deactivation_hook(__FILE__, function () {
    wp_clear_scheduled_hook('hachimi_cleanup_tokens');
});

add_action('hachimi_cleanup_tokens', function () {
    global $wpdb;

    $now = time();

    $wpdb->query(
        $wpdb->prepare(
            "DELETE FROM {$wpdb->prefix}hachimi_tokens WHERE expires_at < %d",
            $now
        )
    );
});