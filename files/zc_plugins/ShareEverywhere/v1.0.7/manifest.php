<?php
if (!defined('IS_ADMIN_FLAG')) {
    exit('Illegal access');
}

return [
    'pluginVersion' => 'v1.0.7',
    'pluginName' => 'Share Everywhere for Zen Cart',
    'pluginDescription' => 'Product sharing, messaging, bookmarks, and store profiles with configurable desktop and mobile layouts.',
    'pluginAuthor' => 'Melanie Prough, PRO-Webs, Inc.',
    'pluginId' => 0, // No Zen Cart Plugins Library ID assigned yet.
    'zcVersions' => ['v158', 'v200', 'v210', 'v220', 'v300'],
    'pluginGroups' => [],
    'github_repo' => 'https://github.com/mprough/Share-Everywhere-for-Zen-Cart',
    'changelog' => 'https://github.com/mprough/Share-Everywhere-for-Zen-Cart/blob/main/CHANGELOG.md',
];
