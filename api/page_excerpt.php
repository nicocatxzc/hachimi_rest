<?php
// 为页面启用摘要功能
add_action('init', function () {
    add_post_type_support('page', 'excerpt');
});
