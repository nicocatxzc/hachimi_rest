<?php
if (! defined('ABSPATH')) exit;

// 启用 link-manager
add_filter('pre_option_link_manager_enabled', '__return_true');

add_action('rest_api_init', function () {
    register_rest_route('hachimi/v1', '/links', [
        'methods' => 'GET',
        'callback' => function () {
            // 所有分类
            $categories = get_terms(['taxonomy' => 'link_category', 'hide_empty' => false]);
            $cats = array_map(function ($cat) {
                return [
                    'id' => intval($cat->term_id),
                    'name' => $cat->name,
                    'description' => $cat->description,
                ];
            }, $categories);

            // 所有链接
            $links = get_bookmarks(['orderby' => 'rating', 'order' => 'DESC']);
            $links_array = array_map(function ($link) {
                $terms = wp_get_object_terms($link->link_id, 'link_category');
                $category_ids = array_map(fn($term) => intval($term->term_id), $terms);

                return [
                    'id' => intval($link->link_id),
                    'name' => $link->link_name,
                    'description' => $link->link_description,
                    'url' => $link->link_url,
                    'image' => $link->link_image,
                    'priority' => intval($link->link_rating),
                    'category_ids' => $category_ids,
                ];
            }, $links);

            return [
                'categories' => $cats,
                'links' => $links_array,
            ];
        },
        'permission_callback' => '__return_true',
    ]);
});
