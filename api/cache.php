<?php
add_action('rest_api_init', function () {
    register_rest_route('hachimi/v1', '/cache', [
        'methods'  => ['GET', 'PUT', 'DELETE'],
        'callback' => 'hachimi_cache_handler',
        'permission_callback' => function () {
            return current_user_can('manage_options');
        },
        'args' => [
            'key' => [
                'required' => true,
                'type' => 'string',
            ],
            'value' => [
                'required' => false,
            ],
            'expire' => [
                'required' => false,
                'type' => 'integer',
                'default' => 3600, // 默认缓存1小时
            ],
        ],
    ]);
});

function hachimi_cache_handler($request)
{
    $method = $request->get_method();
    $key = sanitize_text_field($request->get_param('key'));

    if ($method === 'PUT') {
        $value  = $request->get_param('value');
        $expire = (int) ($request->get_param('expire') ?? 3600);

        $payload = [
            '__hachimi_cache__' => 1,   // 魔数，防止误判
            'value' => $value,
        ];

        set_transient("hachimi_" . $key, $payload, $expire);

        return [
            'success' => true,
            'key'     => $key,
            'expire'  => $expire,
        ];
    }


    if ($method === 'GET') {
        $payload = get_transient("hachimi_" . $key);

        if (!is_array($payload) || !isset($payload['__hachimi_cache__']) || $payload === false) {
            return new WP_Error(
                'cache_not_found',
                '缓存不存在或已过期',
                ['status' => 404]
            );
        }

        return [
            'success' => true,
            'key'     => $key,
            'value'   => $payload['value'],
        ];
    }


    if ($method === 'DELETE') {
        delete_transient("hachimi_" . $key);
        return [
            'success' => true,
            'key' => $key,
        ];
    }

    return new WP_Error('Method not allowed', '不支持的请求方法', ['status' => 405]);
}
