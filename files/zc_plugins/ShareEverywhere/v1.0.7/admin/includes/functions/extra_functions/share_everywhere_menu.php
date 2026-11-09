<?php
if (!defined('IS_ADMIN_FLAG') || IS_ADMIN_FLAG !== true) {
    exit('Illegal access');
}

require_once dirname(__DIR__, 2) . '/extra_datafiles/share_everywhere.php';

if (!defined('BOX_CATALOG_SHARE_EVERYWHERE')) {
    define('BOX_CATALOG_SHARE_EVERYWHERE', 'Share Everywhere');
}

if (!function_exists('share_everywhere_sync_catalog_menu')) {
    function share_everywhere_sync_catalog_menu(): void
    {
        global $db;

        $where = "page_key = 'catalogShareEverywhere'";
        $page = $db->Execute('SELECT main_page FROM ' . TABLE_ADMIN_PAGES . ' WHERE ' . $where, 1);
        if ($page->EOF) {
            zen_register_admin_page(
                'catalogShareEverywhere', 'BOX_CATALOG_SHARE_EVERYWHERE', 'FILENAME_SHARE_EVERYWHERE',
                '', 'catalog', 'Y'
            );
        } elseif ($page->fields['main_page'] === 'share_everywhere') {
            // Repair the original 1.0.0 row in place so profile permissions are preserved.
            zen_db_perform(TABLE_ADMIN_PAGES, ['main_page' => 'FILENAME_SHARE_EVERYWHERE'], 'UPDATE',
                $where . " AND main_page = 'share_everywhere'");
        }
    }
}
