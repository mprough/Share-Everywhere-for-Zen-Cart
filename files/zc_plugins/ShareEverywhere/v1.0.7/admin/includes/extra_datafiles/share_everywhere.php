<?php
if (!defined('IS_ADMIN_FLAG') || IS_ADMIN_FLAG !== true) {
    exit('Illegal access');
}

if (!defined('FILENAME_SHARE_EVERYWHERE')) {
    define('FILENAME_SHARE_EVERYWHERE', 'share_everywhere');
}

// Without a rule, Zen Cart's admin sanitizer HTML-escapes every posted value, so a saved
// URL's "&" became "&amp;" (and "&amp;amp;" on the next save). The settings page validates
// each field itself and escapes everything on output. Scoped to this page only.
if (class_exists('AdminRequestSanitizer')) {
    AdminRequestSanitizer::getInstance()->addComplexSanitization([
        'settings' => ['sanitizerType' => 'NULL_ACTION', 'method' => 'post', 'pages' => ['share_everywhere']],
    ]);
}
