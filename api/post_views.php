<?php
if (!defined('ABSPATH')) {
    exit;
}

add_action('rest_api_init', function () {

    register_rest_route('hachimi/v1', '/views', [
        [
            'methods'  => 'GET',
            'callback' => 'hachimi_get_post_views',
            'permission_callback' => '__return_true',
        ],
        [
            'methods'  => 'POST',
            'callback' => 'hachimi_increment_post_views',
            'permission_callback' => '__return_true',
        ],
    ]);
});

/**
 * 获取文章阅读量
 */
function hachimi_get_post_views(WP_REST_Request $request)
{
    $post_id = intval($request->get_param('post_id'));

    if ($post_id <= 0) {
        return 0;
    }

    $post = get_post($post_id);
    if (!$post) {
        return 0;
    }

    $views = get_post_meta($post_id, 'views', true);

    return intval($views ?: 0);
}

/**
 * 阅读量 +1
 */
function hachimi_increment_post_views(WP_REST_Request $request)
{
    $post_id = intval($request->get_param('post_id'));

    if ($post_id <= 0) {
        return 0;
    }

    $post = get_post($post_id);
    if (!$post) {
        return 0;
    }

    $views = intval(get_post_meta($post_id, 'views', true));

    $views++;

    update_post_meta($post_id, 'views', $views);

    return $views;
}

/**
 * 注册到wpgraphql自定义字段
 */
add_action('graphql_register_types', function () {

    $config = [
        'type'        => 'Int',
        'description' => 'Post views count',
        'resolve'     => function ($post) {
            if (empty($post->ID)) {
                return 0;
            }

            $views = get_post_meta($post->ID, 'views', true);

            return intval($views ?: 0);
        },
    ];

    register_graphql_field('Post', 'views', $config);
    register_graphql_field('Page', 'views', $config);

});
