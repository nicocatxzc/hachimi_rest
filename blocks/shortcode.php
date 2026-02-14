<?php
add_shortcode('friend_link', function () {
    return '<div id="hachimi-friend-link"></div>';
});

add_shortcode('bangumi', function () {
    return '<div id="hachimi-bangumi"></div>';
});

add_shortcode('favlist', function () {
    return '<div id="hachimi-favlist"></div>';
});

add_shortcode('archive', function () {
    return '<div id="hachimi-archive"></div>';
});

include_once __DIR__."/ghcard.php";

add_shortcode('ghcard', function ($atts) {
    // 默认值
    $atts = shortcode_atts(
        array(
            'path' => '',
        ),
        $atts,
        'ghcard'
    );

    $path = trim($atts['path']);
    if (empty($path)) {
        return ''; // 无路径时静默返回空
    }

    return hachimi_render_ghcard($path);
});

register_block_type('hachimi/ghcard', [
    'render_callback' => function($attrs) {
        return hachimi_render_ghcard($attrs['path'] ?? '');
    }
]);
