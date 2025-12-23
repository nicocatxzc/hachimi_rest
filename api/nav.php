<?
add_action('after_setup_theme', function () {
    register_nav_menus([
        'hachimi' => __('hachimi主题导航栏', 'hachimi'),
    ]);
});

add_action('rest_api_init', function () {

    register_rest_route('hachimi/v1', '/navigation', [
        'methods'  => WP_REST_Server::READABLE,
        'permission_callback' => '__return_true',
        'callback' => 'hachimi_get_navigation',
    ]);
});

function hachimi_get_menu($location)
{

    // 所有已注册菜单
    $menus = wp_get_nav_menus();

    // 没有任何菜单 → 新建一个
    if (empty($menus)) {

        $menu_id = wp_create_nav_menu(__('导航栏', 'hachimi'));

        // 添加首页链接
        wp_update_nav_menu_item($menu_id, 0, [
            'menu-item-title'  => get_bloginfo('name'),
            'menu-item-url'    => home_url('/'),
            'menu-item-status' => 'publish',
        ]);

        $menus = wp_get_nav_menus();
    }

    // 当前菜单位置绑定情况
    $locations = get_nav_menu_locations();

    // 如果菜单为空则自动分配
    if (empty($locations[$location])) {

        // 自动使用第一个可用菜单
        $menu = reset($menus);

        $locations[$location] = $menu->term_id;
        set_theme_mod('nav_menu_locations', $locations);
    }

    return (int) $locations[$location];
}

function hachimi_get_navigation()
{

    // 获取菜单
    $menu_id = hachimi_get_menu('hachimi');

    if (!$menu_id) {
        return [];
    }

    // 使用wp_rest_menu获取结构
    if (!class_exists('WP_REST_Menus')) {
        return new WP_Error(
            'missing_dependency',
            'WP_API_Menus plugin is required.',
            ['status' => 500]
        );
    }

    $menus_api = new WP_REST_Menus();

    $request = new WP_REST_Request('GET');
    $request->set_param('location', 'hachimi');

    return $menus_api->get_menu_location($request);
}
