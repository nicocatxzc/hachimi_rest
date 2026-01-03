<?php

add_filter('admin_bar_menu', function ($bar) {
    if (!is_user_logged_in()) return;

    $user = wp_get_current_user();

    // 生成一次性 code
    $code = hachimi_random_token(32);
    $key  = 'hachimi_sso_' . hachimi_hash($code);

    $node = $bar->get_node('view-site');
    set_transient($key, [
        'user_id' => $user->ID,
        'issued_at' => time(),
    ], 180); // 3 分钟

    if ($node) {
        $node->href = 'https://'.hachimi_get_setting('frontend_domain').'/sso?code=' . rawurlencode($code);
        $bar->add_node($node);
    } else {
        $bar->add_node([
            'id'    => 'hachimi-frontend',
            'title' => '登录到前台',
            'href'  => 'https://'.hachimi_get_setting('frontend_domain').'/sso?code=' . rawurlencode($code),
        ]);
    }
}, 100);
