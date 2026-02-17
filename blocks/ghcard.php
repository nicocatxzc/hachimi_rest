<?php
function hachimi_render_ghcard($path) {
    // 解析 owner 和 repo
    $repo_info = parse_github_path($path);
    if (!$repo_info) {
        return '<div>无法被解析的github仓库:' . $path . '</div>';
    }

    $owner = $repo_info['owner'];
    $repo  = $repo_info['repo'];

    $cache_key = 'ghcard_' . md5($owner . '/' . $repo);
    $data = get_transient($cache_key);

    // 无缓存或已过期，调用 GitHub API
    if (!$data) {
        $api_url = "https://api.github.com/repos/{$owner}/{$repo}";
        $response = wp_remote_get($api_url, array(
            'headers' => array(
                'User-Agent' => 'WordPress Theme',
                'Accept'     => 'application/vnd.github.v3+json',
            ),
            'timeout' => 10,
        ));

        if (is_wp_error($response) || wp_remote_retrieve_response_code($response) !== 200) {
            $data = [
                'url' => "https://github.com/{$owner}/{$repo}",
                'name' => $repo,
                'owner' => $owner,
                'description' => "未知的仓库{$path}",
                'stars' => 0,
                'language' => '',
                'forks' => 0,
                'license' => '',
            ];
        } else {
            $body = wp_remote_retrieve_body($response);
            $api_data = json_decode($body, true);

            // 提取需要的字段
            $data = [
                'url'         => $api_data['html_url'] ?? '',
                'name'        => $api_data['name'] ?? '',
                'owner'       => $api_data['owner']['login'] ?? '',
                'description' => $api_data['description'] ?? '',
                'stars'       => $api_data['stargazers_count'] ?? 0,
                'language'    => $api_data['language'] ?? '',
                'forks'       => $api_data['forks_count'] ?? 0,
                'license'     => $api_data['license']['key'] ?? ''
            ];
        }

        // 设置缓存
        set_transient($cache_key, $data, DAY_IN_SECONDS);
    }

    // 保存数据
    $data_info = esc_attr(wp_json_encode($data, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE));

    return '<div class="hachimi-ghcard" data-info="' . $data_info . '"></div>';
}

function parse_github_path($path)
{
    // 匹配完整URL：https://github.com/owner/repo
    if (preg_match('/github\.com\/([^\/]+)\/([^\/\?#]+)/', $path, $matches)) {
        return array('owner' => $matches[1], 'repo' => $matches[2]);
    }
    // 匹配owner/repo
    if (preg_match('/^([a-zA-Z0-9\-]+)\/([a-zA-Z0-9\-\._]+)$/', $path, $matches)) {
        return array('owner' => $matches[1], 'repo' => $matches[2]);
    }
    return false;
}