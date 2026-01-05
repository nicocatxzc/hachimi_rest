<?php
defined('ABSPATH') || exit;

add_action('rest_api_init', function () {
    register_rest_route(
        'hachimi/v1',
        '/option',
        [
            [
                'methods'  => WP_REST_Server::READABLE,
                'callback' => 'hachimi_get_option',
                'permission_callback' => function () {
                    return current_user_can('manage_options');
                },
            ],
            [
                'methods'  => WP_REST_Server::EDITABLE,
                'callback' => 'hachimi_put_option',
                'permission_callback' => function () {
                    return current_user_can('manage_options');
                },
            ],
        ]
    );
});

function hachimi_get_option($request)
{
    $name = $request->get_param('key');
    if (!$name) {
        return new WP_Error('missing_param', '必须提供 option 键值', ['status' => 400]);
    }

    $option_name = 'hachimi_option_' . sanitize_key($name);
    $value = get_option($option_name, null);

    return rest_ensure_response([
        'key'  => $option_name,
        'value' => $value,
    ]);
}

function hachimi_put_option($request)
{
    $params = $request->get_json_params();

    if (empty($params['key'])) {
        return new WP_Error('missing_name', '必须提供 option 键值', ['status' => 400]);
    }

    $option_name = 'hachimi_option_' . sanitize_key($params['key']);
    $value = $params['value'] ?? null;

    update_option($option_name, $value);

    return rest_ensure_response([
        'success' => true,
        'message' => "配置 {$option_name} 已更新",
        'key'    => $option_name,
        'value'   => $value,
    ]);
}
