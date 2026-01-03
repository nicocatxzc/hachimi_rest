<?php

if (! defined('ABSPATH')) {
    exit;
}

/**
 * 注册设置页菜单
 */
add_action('admin_menu', function () {
    add_menu_page(
        'Hachimi 设置',
        'Hachimi 设置',
        'manage_options',
        'hachimi_headless_wp',
        'hachimi_headless_wp_page',
        'dashicons-admin-generic',
        3
    );
});

/**
 * 获取单个设置项
 */
function hachimi_get_setting($key, $default = '')
{
    $options = get_option('hachimi_headless_wp', array());
    return isset($options[$key]) ? $options[$key] : $default;
}

/**
 * 设置页回调
 */
function hachimi_headless_wp_page()
{

    // 处理表单提交
    if (isset($_POST['hachimi_headless_wp_submit'])) {
        if (!current_user_can('manage_options')) {
            return;
        }
        check_admin_referer('hachimi_headless_wp_nonce');

        $fields = $_POST;
        unset($fields['hachimi_headless_wp_submit']);
        unset($fields['_wpnonce']);
        unset($fields['_wp_http_referer']);

        // 清理数据
        $sanitized = array_map('sanitize_text_field', $fields);

        // 覆盖写入 option
        update_option('hachimi_headless_wp', $sanitized);

        echo '<div class="updated notice is-dismissible"><p>设置已保存</p></div>';
    }

    if (!empty($_POST['hachimi_revoke_user_submit'])) {
        check_admin_referer('hachimi_headless_wp_nonce');

        $user_id = intval($_POST['revoke_user_id'] ?? 0);
        if ($user_id > 0) {
            $count = hachimi_revoke_user_tokens($user_id);
            echo '<div class="updated notice is-dismissible"><p>已吊销该用户 ' . $count . ' 个前台登录状态。</p></div>';
        }
    }

    if (!empty($_POST['hachimi_revoke_all_submit'])) {
        check_admin_referer('hachimi_headless_wp_nonce');

        $count = hachimi_revoke_all_tokens();
        echo '<div class="updated notice is-dismissible"><p>已吊销所有用户 ' . $count . ' 个前台登录状态。</p></div>';
    }


    // 获取当前配置
    $options = get_option('hachimi_headless_wp', array());
    $users = get_users();
?>
    <div class="wrap">
        <h1>Headless WP 设置</h1>
        <form method="post" action="">
            <?php wp_nonce_field('hachimi_headless_wp_nonce'); ?>
            <table class="form-table">
                <tr valign="top">
                    <th scope="row"><label for="frontend_domain">前台域名</label></th>
                    <td>
                        <input type="text" id="frontend_domain" name="frontend_domain" value="<?php echo esc_attr($options['frontend_domain'] ?? ''); ?>" class="regular-text" />
                        <p class="description">
                            仅本地使用，前端连接权限需要在个人主页创建应用程序密码并导出为前端程序的运行时变量。<br>
                            <a href="<?php echo esc_url(admin_url('profile.php#application-passwords-section')); ?>" target="_blank">前往创建应用程序密码</a>
                        </p>
                    </td>
                </tr>
            </table>
            <?php submit_button('保存设置', 'primary', 'hachimi_headless_wp_submit'); ?>
        </form>
        <form method="post" style="margin-bottom:20px;">
            <?php wp_nonce_field('hachimi_headless_wp_nonce'); ?>
            <table class="form-table">
                <tr valign="top">
                    <th scope="row">吊销指定用户前台登录状态</th>
                    <td>
                        <select name="revoke_user_id">
                            <option value="">选择用户</option>
                            <?php foreach ($users as $user): ?>
                                <option value="<?php echo $user->ID; ?>"><?php echo esc_html($user->display_name . ' (' . $user->ID . ')'); ?></option>
                            <?php endforeach; ?>
                        </select>
                        <?php submit_button('吊销该用户', 'secondary', 'hachimi_revoke_user_submit', false); ?>
                        <p class="description">将删除该用户所有现有前台 token。</p>
                    </td>
                </tr>
            </table>
        </form>

        <form method="post">
            <?php wp_nonce_field('hachimi_headless_wp_nonce'); ?>
            <?php submit_button('吊销所有用户前台登录状态', 'secondary', 'hachimi_revoke_all_submit'); ?>
            <p class="description">将删除所有用户的现有前台 token。</p>
        </form>
    </div>
<?php
}
