<?php
if (!defined('IS_ADMIN_FLAG') || IS_ADMIN_FLAG !== false) {
    exit('Illegal access');
}

class zcObserverShareEverywhere extends base
{
    private bool $rendered = false;

    public function __construct()
    {
        if (defined('SHARE_EVERYWHERE_SETTINGS') && str_ends_with((string)($GLOBALS['current_page_base'] ?? ''), '_info')) {
            $this->attach($this, ['NOTIFY_FOOTER_END']);
        }
    }

    protected function notify_footer_end(&$callingClass, string $event): void
    {
        global $canonicalLink, $current_page_base;

        if ($this->rendered) {
            return;
        }
        $this->rendered = true;
        $id = filter_var($_GET['products_id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        if ($id === false) {
            return;
        }
        $root = dirname(__DIR__, 4);
        require_once $root . '/lib/ShareEverywhere.php';
        $settings = ShareEverywhere::settings();
        if (!$settings['enabled']) {
            return;
        }
        $product = ShareEverywhere::product($id, (string)($canonicalLink ?? ''));
        if ($product === null || $product['page'] !== $current_page_base) {
            return;
        }
        $buttons = ShareEverywhere::buttons($settings, $product);
        if (!$buttons) {
            return;
        }
        $assets = ShareEverywhere::assets(DIR_WS_CATALOG);
        require $root . '/lib/storefront.php';
    }
}
