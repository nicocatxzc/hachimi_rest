<?php
defined('ABSPATH') || exit;

add_action('rest_api_init', function () {
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