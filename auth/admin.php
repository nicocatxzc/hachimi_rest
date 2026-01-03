<?php

add_filter('admin_bar_menu', function ($bar) {
    if (!is_user_logged_in()) return;

    $node = $bar->get_node('view-site');

    $href = admin_url('admin-post.php?action=hachimi_sso');

    if ($node) {
        $node->href = $href;
        $bar->add_node($node);
    } else {
        $bar->add_node([
            'id'    => 'hachimi-frontend',
            'title' => '登录到前台',
            'href'  => $href,
        ]);
    }
}, 100);

add_action('admin_post_hachimi_sso', function () {
    if (!is_user_logged_in()) {
        wp_die('Unauthorized', 403);
    }

    $user = wp_get_current_user();

    // 生成一次性 code
    $code = hachimi_random_token(32);
    $key  = 'hachimi_sso_' . hachimi_hash($code);

    set_transient($key, [
        'user_id'   => $user->ID,
        'issued_at' => time(),
    ], 180); // 3 分钟

    $url = 'https://' . hachimi_get_setting('frontend_domain')
        . '/sso?code=' . rawurlencode($code);

    wp_redirect($url, 302);
    exit;
});
