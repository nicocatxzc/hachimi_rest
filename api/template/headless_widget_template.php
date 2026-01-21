<?php
wp_head();

echo '<!-- hachimi-widget-start --><div id="__wp_headless_widget_root">';

$sidebars = ['hachimi-widgets'];

foreach ($sidebars as $sidebar_id) {
    if (is_active_sidebar($sidebar_id)) {
        echo '<div data-sidebar="' . esc_attr($sidebar_id) . '">';
        dynamic_sidebar($sidebar_id);
        echo '</div>';
    }
}

echo '</div><!-- hachimi-widget-end -->';

wp_footer();
