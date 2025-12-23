<?php
defined('ABSPATH') || exit;

add_action('rest_api_init', function () {
    register_rest_route('hachimi/v1', '/route', [
        'methods' => 'GET',
        'callback' => 'hachimi_route_resolver',
        'args' => [
            'path' => [
                'required' => true
            ]
        ]
    ]);
    register_rest_route(
        'hachimi/v1',
        '/config',
        [
            [
                'methods'  => WP_REST_Server::READABLE,
                'callback' => 'hachimi_get_config',
                'permission_callback' => function () {
                    return current_user_can('manage_options');
                },
            ],
            [
                'methods'  => WP_REST_Server::EDITABLE,
                'callback' => 'hachimi_put_config',
                'permission_callback' => function () {
                    return current_user_can('manage_options');
                },
            ],
        ]
    );
});

function hachimi_get_config()
{
    $config = get_option('hachimi_config', array());
    return rest_ensure_response($config);
}

function hachimi_put_config($request)
{
    $new_config = $request->get_json_params();

    if (!is_array($new_config)) {
        return new WP_Error('invalid_data', '配置必须是数组', array('status' => 400));
    }

    update_option('hachimi_config', $new_config);

    return rest_ensure_response(array(
        'success' => true,
        'message' => '配置已更新',
        'config' => $new_config
    ));
}

function hachimi_route_resolver($req)
{
    global $wp;
    $path = $req['path'];

    $home = hachimi_match_home($path);
    if ($home !== null) {
        return $home;
    }

    $path = trim($req['path'], '/');

    // 复制当前 WP 实例，而不是创建新的实例
    $old = $wp->query_vars;

    // 临时修改请求
    $_SERVER['REQUEST_URI'] = '/' . $path;

    // 触发一次正常的 parse_request，但不会重复加载环境
    $wp->query_vars = [];
    $wp->parse_request();
    $wp->query_posts();

    // 获取解析出来的对象
    $query = $wp->query_vars;

    // 恢复上下文
    $wp->query_vars = $old;

    return hachimi_match_query($query);
}

function hachimi_match_home($path)
{
    $type_home = ['type' => 'home'];

    if ($path == '/') return $type_home;

    // 有路径不是首页
    if ($path !== '') return null;

    $show_on_front = get_option('show_on_front');

    // 首页显示静态页面
    if ($show_on_front === 'page') {
        $front_id = (int) get_option('page_on_front');

        if ($front_id > 0) {
            return [
                'type' => 'page',
                'id'   => $front_id
            ];
        }
    }

    // 默认首页
    return $type_home;
}

function hachimi_match_query($vars)
{
    $q = new WP_Query($vars);

    // 首页？
    if ($q->is_home()) {
        return [
            'type' => 'home',
            'canonical' => '/',
        ];
    }

    // 搜索页
    if ($q->is_search()) {
        return [
            'type' => 'search',
            'query' => get_query_var('s'),
        ];
    }

    // 作者
    if ($q->is_author()) {
        $author = get_queried_object();

        $author_id = $author->ID;
        return [
            'type' => 'author',
            'author_id' => $author_id,
            'name' => $author->display_name,
            'description' => get_the_author_meta('description', $author_id),
            'post_count' => count_user_posts($author_id),
            'slug' => $author->user_nicename,
            'avatar' => [
                'url_96' => get_avatar_url($author_id, ['size' => 96]),
                'url_150' => get_avatar_url($author_id, ['size' => 150]),
                'url_300' => get_avatar_url($author_id, ['size' => 300]),
            ],
            'canonical' => wp_parse_url(
                get_author_posts_url($author->ID),
                PHP_URL_PATH
            ),
        ];
    }

    // 日期归档
    // if ($q->is_date()) {
    //     return [
    //         'type' => 'date',
    //         'year'  => get_query_var('year'),
    //         'month' => get_query_var('monthnum'),
    //         'day'   => get_query_var('day')
    //     ];
    // }

    // 分类
    if ($q->is_category()) {
        $category = get_queried_object();
        return [
            'type' => 'category',
            'id' => get_queried_object_id(),
            'title' => $category->name,
            'description' => $category->description,
            'canonical' => wp_parse_url(get_term_link($category), PHP_URL_PATH),
        ];
    }

    // 标签
    if ($q->is_tag()) {
        $tag = get_queried_object();
        return [
            'type' => 'tag',
            'id' => get_queried_object_id(),
            'title' => $tag->name,
            'description' => $tag->description,
            'canonical' => wp_parse_url(get_term_link($tag), PHP_URL_PATH),
        ];
    }

    // 自定义分类？
    if ($q->is_tax()) {
        $obj = get_queried_object();
        return [
            'type' => 'taxonomy',
            'taxonomy' => $obj->taxonomy,
            'slug' => $obj->slug,
            'canonical' => wp_parse_url(get_term_link($obj), PHP_URL_PATH),
        ];
    }

    // 文章
    if ($q->is_single()) {

        if (empty($q->post)) {
            return ['type' => '404'];
        }

        return [
            'type' => 'single',
            'id'   => $q->post->ID,
            'post_type' => $q->post->post_type,
            'canonical' => wp_parse_url(get_permalink($q->post), PHP_URL_PATH),
        ];
    }

    // 页面
    if ($q->is_page()) {

        if (empty($q->post)) {
            return [
                'type' => '404'
            ];
        }

        return [
            'type' => 'page',
            'id'   => $q->post->ID,
            'canonical' => wp_parse_url(get_permalink($q->post), PHP_URL_PATH),
        ];
    }

    // 自定义文章类型
    // if ($q->is_post_type_archive()) {
    //     return [
    //         'type' => 'post_type_archive',
    //         'post_type' => get_query_var('post_type'),
    //         'canonical' =>$canonical_path,
    //     ];
    // }

    // 附件
    // if ($q->is_attachment()) {
    //     return [
    //         'type' => 'attachment',
    //         'id'   => $q->post->ID,
    //         'canonical' =>$canonical_path,
    //     ];
    // }

    return ['type' => '404'];
}
