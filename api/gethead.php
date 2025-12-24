<?php
add_action('wp_head', function () {
    if (!isset($_GET['onlyhead'])) {
        return;
    }

    // 终止wp_head输出
    exit;
}, PHP_INT_MAX);
