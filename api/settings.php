<?php
defined('ABSPATH') || exit;

add_action('rest_api_init', function() {
    register_rest_route('hachimi/v1', '/settings', array(
        'methods' => 'GET',
        'callback' => 'hachimi_get_settings',
        'permission_callback' => '__return_true',
    ));
});
// 返回站点配置
function hachimi_get_settings() {
    return array(
        'site_name' => get_bloginfo('name'),
        'site_description' => get_bloginfo('description'),
        'posts_per_page' => (int)get_option('posts_per_page', 10),
        'comments_per_page' => (int)get_option('comments_per_page', 50),
        'sticky_post_ids' => get_option('sticky_posts', array()),
    );
}