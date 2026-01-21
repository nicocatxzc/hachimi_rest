<?php
// 准备存储渲染后的内容
$rendered_posts = [];

if (have_posts()) {
    while (have_posts()) {
        the_post();

        // 使用 apply_filters('the_content', get_the_content()) 触发 do_blocks / render_block / shortcode / autop 等流程，
        // 让古腾堡渲染逻辑把内联 CSS 注册进 $wp_styles。
        $rendered_posts[] = apply_filters('the_content', get_the_content());
    }

    // 重置全局 postdata
    wp_reset_postdata();
}

// 打印带有样式的head
wp_head();

// 输出定位用容器
echo '<!-- hachimi-headless-start --><div id="hachimi-headless">';

// 输出渲染好的内容
foreach ($rendered_posts as $html) {
    echo $html;
}

echo '</div><!-- hachimi-headless-end -->';

// 输出footer
wp_footer();