<?php
add_filter(
    'graphql_request_data',
    function ($request) {

        if (!empty($request['token'])) {
            $user = hachimi_verify_token($request['token']); // 查询token是否有对应用户

            if ($user) {
                // 暂存到 request extensions，供后续使用
                $request['extensions']['verified_user'] = $user;
            }
        }

        return $request;
    }
);

// 设置用户身份
add_action(
    'graphql_before_resolve_field',
    function ($source, $args, $context, $info) {
        if ($info->fieldName !== 'createComment') {
            return;
        }

        $user = $context->request->extensions['verified_user']['user'] ?? null;

        if ($user && $user->ID) {
            wp_set_current_user($user->ID);
        }
    },
    10,
    4
);

add_filter(
    'preprocess_comment',
    function ($commentdata) {

        if (is_user_logged_in()) {
            $user = wp_get_current_user();

            $commentdata['user_id'] = $user->ID;
            $commentdata['comment_author'] = $user->display_name;
            $commentdata['comment_author_email'] = $user->user_email;
        }

        return $commentdata;
    }
);
