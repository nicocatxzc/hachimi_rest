<?php

function hachimi_random_token($len = 64)
{
    return bin2hex(random_bytes($len / 2));
}

function hachimi_hash($token)
{
    return hash('sha256', $token);
}

function hachimi_issue_token($user_id, $ttl = 7 * DAY_IN_SECONDS)
{
    global $wpdb;

    $raw = hachimi_random_token();
    $wpdb->insert("{$wpdb->prefix}hachimi_tokens", [
        'token_hash' => hachimi_hash($raw),
        'user_id' => $user_id,
        'expires_at' => time() + $ttl,
        'created_at' => time(),
    ]);

    return [
        "token" => $raw,
        "expire" => time() + $ttl,
    ];
}

function hachimi_verify_token($raw)
{
    global $wpdb;

    $row = $wpdb->get_row(
        $wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}hachimi_tokens WHERE token_hash = %s",
            hachimi_hash($raw)
        )
    );

    if (!$row || $row->expires_at < time()) {
        return null;
    }

    return [
        "user" => get_user_by('id', $row->user_id),
        "expire" => $row->expires_at
    ];
}

function hachimi_revoke_token($raw)
{
    global $wpdb;
    $wpdb->delete("{$wpdb->prefix}hachimi_tokens", [
        'token_hash' => hachimi_hash($raw)
    ]);
}

function hachimi_revoke_user_tokens($user_id)
{
    global $wpdb;

    $count = $wpdb->get_var(
        $wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}hachimi_tokens WHERE user_id = %d",
            $user_id
        )
    );

    $wpdb->query(
        $wpdb->prepare(
            "DELETE FROM {$wpdb->prefix}hachimi_tokens WHERE user_id = %d",
            $user_id
        )
    );

    return $count;
}


function hachimi_revoke_all_tokens()
{
    global $wpdb;

    $count = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}hachimi_tokens");

    $wpdb->query("DELETE FROM {$wpdb->prefix}hachimi_tokens");

    return $count;
}
