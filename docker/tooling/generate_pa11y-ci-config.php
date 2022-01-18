#!/usr/bin/env php
<?php

declare(strict_types = 1);

$urls = [];
foreach ([[360, 640, true], [1280, 720, true], [1280, 720, false], [2560, 1080, false]] as $viewport) {
    [$x, $y, $is_mobile] = $viewport;
    $gen = function (string $url, string $actions = "") use ($x, $y, $is_mobile) {
        $path = "/screenshots/new/{$x}x{$y}" . ($is_mobile ? '-mobile' : '') . '-' . ($url === '' ? 'root' : str_replace('/', '-', $url)) . ".png";
        $is_mobile = $is_mobile ? 'true' : 'false';
        return <<<EOU
        {
            "url": "https://nginx/{$url}",
            "screenCapture": "{$path}",
            "viewport": {
                "width": {$x},
                "height": {$y},
                "isMobile": {$is_mobile}
            }{$actions}
        }
EOU;
    };

    foreach ([
        '', 'feed/public',
        'doc/faq', 'doc/tos', 'doc/privacy', 'doc/source', 'doc/version',
        'main/login', 'main/register',
    ] as $url) {
        $urls[] = $gen($url);
    }

    $urls[] = $gen('main/login', <<<EOA
,
            "actions": [
                "navigate to https://nginx/main/login",
                "set field #inputNicknameOrEmail to taken_user",
                "set field #inputPassword to foobar",
                "click element #signIn",
                "wait for path to not be /login"
            ]
EOA);

    foreach (['feed/public', 'feed/home', '@taken_user/circles',
              'feed/network', 'feed/clique', 'feed/federated', 'feed/notifications',
              '@taken_user/collections', '@taken_user/favourites', '@taken_user/reverse_favourites',
              'directory/people', 'directory/groups', 'settings', 'main/logout'
    ] as $url) {
        $urls[] = $gen($url);
    }
}

$urls = implode(",\n", $urls);
$config = <<<EOF
{
    "defaults": {
        "chromeLaunchConfig": {
            "ignoreHTTPSErrors": true
        },
        "standard": "WCAG2AAA",
        "timeout": 10000
    },
    "concurrency": 4,
    "urls": [
{$urls}
    ]
}
EOF;

file_put_contents('/pa11y/config.json', $config);
