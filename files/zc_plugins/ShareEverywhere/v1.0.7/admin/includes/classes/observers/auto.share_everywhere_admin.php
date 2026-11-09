<?php
if (!defined('IS_ADMIN_FLAG') || IS_ADMIN_FLAG !== true) {
    exit('Illegal access');
}

class zcObserverShareEverywhereAdmin extends base
{
    public function __construct()
    {
        // Extra functions can be included before configuration and admin functions load.
        require_once dirname(__DIR__, 2) . '/functions/extra_functions/share_everywhere_menu.php';
        if (defined('SHARE_EVERYWHERE_SETTINGS') && function_exists('zen_register_admin_page')) {
            share_everywhere_sync_catalog_menu();
        }
    }
}
