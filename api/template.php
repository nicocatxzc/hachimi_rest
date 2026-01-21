<?php
add_filter('template_include', function ($template) {
    if (!isset($_GET['headless'])) {
        return $template;
    }

    return __DIR__ . '/template/headless_template.php';
}, PHP_INT_MAX);
