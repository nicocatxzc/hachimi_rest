<?php

add_action('rest_api_init', function () {

    register_rest_route('hachimi/v1', '/auth/login', [
        'methods' => 'POST',
        'callback' => 'hachimi_login',
        'permission_callback' => function () {
            return current_user_can('manage_options');
        }
    ]);

    register_rest_route('hachimi/v1', '/auth/validate', [
        'methods' => 'POST',
        'callback' => 'hachimi_validate',
        'permission_callback' => function () {
            return current_user_can('manage_options');
        }
    ]);

    register_rest_route('hachimi/v1', '/auth/refresh', [
        'methods' => 'POST',
        'callback' => 'hachimi_refresh',
        'permission_callback' => function () {
            return current_user_can('manage_options');
        }
    ]);

    register_rest_route('hachimi/v1', '/auth/sso/exchange', [
        'methods' => 'POST',
        'callback' => 'hachimi_sso_exchange',
        'permission_callback' => function () {
            return current_user_can('manage_options');
        }
    ]);
});

function hachimi_login($req)
{
    $u = $req['username'];
    $p = $req['password'];

    $user = wp_authenticate($u, $p);
    if (is_wp_error($user)) {
        return new WP_Error('invalid', 'Login failed', 401);
    }

    $issued = hachimi_issue_token($user->ID);

    $data = [
        'token' => $issued["token"],
        'user' => [
            'id' => $user->ID,
            'name' => $user->display_name,
            'email' => $user->user_email,
            'roles' => $user->roles,
            'description' => get_the_author_meta('description', $user->ID),
            'slug' => $user->user_nicename,
            'avatar' => [
                'url_96' => get_avatar_url($user->ID, ['size' => 96]),
                'url_150' => get_avatar_url($user->ID, ['size' => 150]),
                'url_300' => get_avatar_url($user->ID, ['size' => 300]),
            ],
            'management' => [],
            'expire' => $issued["expire"],
        ]
    ];

    if (user_can($user, 'edit_posts')) {
        $data['user']['management'] = [
            'admin' => admin_url(),
            'newpost' => admin_url('post-new.php'),
        ];
    }

    return $data;
}

function hachimi_validate($req)
{
    $auth = $req['token'] ?? '';
    if (empty($auth)) {
        return new WP_Error('no_token', 'Missing token', 401);
    }

    $token = $auth;
    $verified = hachimi_verify_token($token);
    if (!$verified) {
        return new WP_Error('invalid', 'Invalid token', 401);
    }
    $user = $verified["user"];

    if (!$user) {
        return new WP_Error('invalid', 'Invalid token', 401);
    }

    $data = [
        'id' => $user->ID,
        'name' => $user->display_name,
        'email' => $user->user_email,
        'roles' => $user->roles,
        'description' => get_the_author_meta('description', $user->ID),
        'slug' => $user->user_nicename,
        'avatar' => [
            'url_96' => get_avatar_url($user->ID, ['size' => 96]),
            'url_150' => get_avatar_url($user->ID, ['size' => 150]),
            'url_300' => get_avatar_url($user->ID, ['size' => 300]),
        ],
        'management' => [],
        'expire' => $verified["expire"],
    ];

    if (user_can($user, 'edit_posts')) {
        $data['management'] = [
            'admin' => admin_url(),
            'newpost' => admin_url('post-new.php'),
        ];
    }

    return $data;
}

function hachimi_refresh($req)
{
    $params = $req->get_json_params();
    $auth = $params['token'] ?? '';

    if (empty($auth)) {
        return new WP_Error('no_token', 'Missing token', 401);
    }
    $old = $auth;

    $verified = hachimi_verify_token($old);
    if (!$verified) {
        return new WP_Error('invalid', 'Invalid token ' . $old, 401);
    }
    $user = $verified["user"];
    if (!$user) {
        return new WP_Error('invalid', 'Invalid user222', 401);
    }

    hachimi_revoke_token($old);
    $new = hachimi_issue_token($user->ID);

    return [
        'token' => $new["token"],
        'expire' => $new["expire"]
    ];
}

function hachimi_sso_exchange($req)
{
    $code = $req['code'] ?? '';
    if (!$code) {
        return new WP_Error('bad_request', 'Missing code', 400);
    }

    $key  = 'hachimi_sso_' . hachimi_hash($code);
    $data = get_transient($key);

    if (!$data) {
        return new WP_Error('invalid', 'SSO expired or used', 401);
    }

    // 阅后即焚
    delete_transient($key);

    $token = hachimi_issue_token($data['user_id']);

    return [
        'token' => $token["token"],
        'expire' => $token["expire"]
    ];
}
