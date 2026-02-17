<?php
add_action('init', function () {
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

    function hachimi_encode_data($data)
    {
        return esc_attr(wp_json_encode($data, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE));
    }

    #region Sakurairo

    // ghcard
    include_once __DIR__ . "/ghcard.php";
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

    register_block_type('sakurairo/ghcard', [
        'render_callback' => function ($attrs) {
            return hachimi_render_ghcard($attrs['path'] ?? '');
        }
    ]);

    // notice
    function iro_render_notice($type = '', $content = '')
    {
        $data = hachimi_encode_data([
            'type' => $type,
            'content' => $content
        ]);
        return '<div class="hachimi-notice" data-info="' . $data . '></div>';
    }

    add_shortcode('task', fn($a, $c = '') => iro_render_notice('task', $c));
    add_shortcode('warning', fn($a, $c = '') => iro_render_notice('warning', $c));
    add_shortcode('noway', fn($a, $c = '') => iro_render_notice('noway', $c));
    add_shortcode('buy', fn($a, $c = '') => iro_render_notice('buy', $c));

    register_block_type('sakurairo/notice', [
        'render_callback' => 'iro_render_notice_block',
    ]);
    function iro_render_notice_block($attributes, $content)
    {

        $type = $attributes['type'] ?? 'task';
        $text = $attributes['content'] ?? '';

        return iro_render_notice($type, $text);
    }

    // 展示卡片
    function iro_render_showcard($atts, $content = '')
    {
        $data = hachimi_encode_data([
            'img' => esc_url($atts['img'] ?? ''),
            'link' => esc_url($atts['link'] ?? $content ?? ''),
            'color' => esc_attr($atts['color'] ?? ''),
            'icon' => esc_attr($atts['icon'] ?? ''),
            'color' => esc_attr($atts['color'] ?? ''),
            'title' => wp_kses_post($atts['title'] ?? ''),
        ]);
        return '<div class="hachimi-showcard" data-info="' . $data . '"></div>';
    }

    add_shortcode('showcard', function ($attr, $content = '') {
        $atts = shortcode_atts(array("icon" => "", "title" => "", "img" => "", "color" => ""), $attr);
        return iro_render_showcard($atts, $content);
    });

    register_block_type('sakurairo/showcard', [
        'render_callback' => 'iro_render_showcard_block',
    ]);

    function iro_render_showcard_block($attributes, $content)
    {

        $img = $attributes['img'] ?? '';
        $link = $attributes['link'] ?? '';
        $color = $attributes['color'] ?? '';
        $icon = $attributes['icon'] ?? '';
        $title = $attributes['title'] ?? '';

        return iro_render_showcard([
            'img' => $img,
            'link' => $link,
            'color' => $color,
            'icon' => $icon,
            'title' => $title,
        ]);
    }

    // 对话
    function iro_render_conversations($atts, $content)
    {
        // 基本信息
        $username = isset($atts['username']) ? $atts['username'] : '';
        $avatar = isset($atts['avatar']) ? $atts['avatar'] : '';
        $direction = isset($atts['direction']) && in_array($atts['direction'], ['row', 'row-reverse'])
            ? $atts['direction']
            : 'row';

        $data = hachimi_encode_data([
            'username' => $username,
            'avatar' => $avatar,
            'direction' => $direction,
            'content' => $content,
        ]);

        return '<div class="hachimi-conversation" data-info="' . $data . '"></div>';
    }

    add_shortcode('conversations', function ($attr, $content = '') {
        $atts = shortcode_atts(array("avatar" => "", "direction" => "", "username" => ""), $attr);
        return iro_render_conversations($atts, $content);
    });

    register_block_type('sakurairo/conversation', [
        'render_callback' => 'iro_render_conversations_block',
    ]);
    function iro_render_conversations_block($attributes, $content)
    {

        $avatar = $attributes['avatar'] ?? '';
        $direction = $attributes['direction'] ?? '';
        $content = $attributes['content'] ?? '';

        return iro_render_conversations([
            'avatar' => $avatar,
            'direction' => $direction,
        ], $content);
    }

    // bilibili
    function iro_render_bilibili($content)
    {
        preg_match_all('/av([0-9]+)/', $content, $av_matches);
        preg_match_all('/BV([a-zA-Z0-9]+)/', $content, $bv_matches);
        $data = hachimi_encode_data([
            'av' => $av_matches[1],
            'bv' => $bv_matches[1],
        ]);
        return '<div class="hachimi-bvideo" data-info="' . $data . '></div>';
    }

    add_shortcode('vbilibili', function ($atts, $content = null) {
        return iro_render_bilibili($content);
    });

    register_block_type('sakurairo/vbilibili', [
        'render_callback' => 'iro_render_bilibili_block',
    ]);
    function iro_render_bilibili_block($attributes, $content)
    {
        $bid = $attributes['videoId'] ?? '';

        return iro_render_bilibili($bid);
    }
}, 999);
