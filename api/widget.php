<?php
add_action('after_setup_theme', function () {
    register_sidebar([
        'name'          => 'hachimi主题小工具栏',
        'id'            => 'hachimi-widgets',
        'description'   => '主题右下角小工具栏',
        'before_widget' => '',
        'after_widget'  => '',
        'before_title'  => '',
        'after_title'   => '',
    ]);
    add_theme_support('widgets-block-editor');
});

add_filter('template_include', function ($template) {
    if (!isset($_GET['widget'])) {
        return $template;
    }
    return __DIR__ . '/template/headless_widget_template.php';
}, PHP_INT_MAX);
