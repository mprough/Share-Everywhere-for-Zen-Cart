<?php
declare(strict_types=1);

namespace Zencart\PluginSupport {
    // Matches the members every Zen Cart release from 1.5.8 to 3.0.0 gives a scripted installer.
    class ScriptedInstaller
    {
        protected $dbConn;
        public function __construct($dbConn) { $this->dbConn = $dbConn; }
        public function install(): bool { return $this->executeInstall(); }
        public function upgrade(...$args): bool { return $this->executeUpgrade(...$args); }
        public function uninstall(): bool { return $this->executeUninstall(); }
        protected function executeInstallerSql($sql)
        {
            $this->dbConn->Execute($sql);
            return true;
        }
    }
}

namespace {
    require __DIR__ . '/polyfills.php';
    define('IS_ADMIN_FLAG', true);
    define('TABLE_ADMIN_PAGES', 'admin_pages');
    define('TABLE_CONFIGURATION', 'configuration');
    define('TABLE_PLUGIN_CONTROL', 'plugin_control');
    $pages = ['unrelated' => ['something']];
    $config = ['OTHER_KEY' => 'keep'];
    $installedVersion = '';

    $db = new class {
        public function prepare_input(string $value): string { return addslashes($value); }
        public function Execute(string $sql, $limit = null): object
        {
            global $pages, $config, $installedVersion;
            $sql = trim($sql);
            if ($sql === "SELECT main_page FROM admin_pages WHERE page_key = 'catalogShareEverywhere'") {
                $page = $pages['catalogShareEverywhere'] ?? null;
                return (object)['EOF' => $page === null, 'fields' => ['main_page' => $page[1] ?? null]];
            }
            if (preg_match("/^SELECT configuration_value FROM configuration WHERE configuration_key = '(\\w+)'$/", $sql, $m)) {
                return (object)['EOF' => !isset($config[$m[1]]), 'fields' => ['configuration_value' => $config[$m[1]] ?? null]];
            }
            if (preg_match("/^SELECT version FROM plugin_control WHERE unique_key = 'ShareEverywhere' AND status = 1$/", $sql)) {
                return (object)['EOF' => $installedVersion === '', 'fields' => ['version' => $installedVersion]];
            }
            if (preg_match("/^INSERT INTO configuration \\(.*\\) VALUES \\('[^']*', '(\\w+)', '((?:[^'\\\\]|\\\\.)*)', .*now\\(\\)\\)$/s", $sql, $m)) {
                check(!isset($config[$m[1]]), 'Install inserted a duplicate configuration row.');
                $config[$m[1]] = stripslashes($m[2]);
                return (object)['EOF' => true];
            }
            if (preg_match("/^UPDATE configuration SET configuration_value = '((?:[^'\\\\]|\\\\.)*)', last_modified = now\\(\\) WHERE configuration_key = '(\\w+)'$/s", $sql, $m)) {
                $config[$m[2]] = stripslashes($m[1]);
                return (object)['EOF' => true];
            }
            if (preg_match("/^DELETE FROM configuration WHERE configuration_key = '(\\w+)'$/", $sql, $m)) {
                unset($config[$m[1]]);
                return (object)['EOF' => true];
            }
            throw new RuntimeException('Unexpected SQL: ' . $sql);
        }
    };
    function zen_db_perform(string $table, array $fields, string $type, string $where): void
    {
        check($table === TABLE_ADMIN_PAGES && $type === 'UPDATE'
            && $where === "page_key = 'catalogShareEverywhere' AND main_page = 'share_everywhere'",
            'Menu repair must target only the legacy row.');
        $GLOBALS['pages']['catalogShareEverywhere'][1] = $fields['main_page'];
    }
    function zen_register_admin_page(string $key, ...$details): void
    {
        $GLOBALS['pages'][$key] = $details;
    }
    function zen_deregister_admin_pages(array $keys): void
    {
        foreach ($keys as $key) unset($GLOBALS['pages'][$key]);
    }
    function check(bool $passed, string $message): void
    {
        if (!$passed) throw new RuntimeException($message);
    }

    $root = dirname(__DIR__) . '/files/zc_plugins/ShareEverywhere/v1.0.7';
    // A request can load the installed version's helpers before the new installer.
    require dirname($root) . '/v1.0.0/admin/includes/functions/extra_functions/share_everywhere_menu.php';
    require $root . '/Installer/ScriptedInstaller.php';
    $source = file_get_contents($root . '/Installer/ScriptedInstaller.php');
    foreach (['addConfigurationKey', 'getConfigurationKeyDetails', 'deleteConfigurationKeys', 'executeInstallerSelectQuery'] as $helper) {
        check(strpos($source, $helper) === false, $helper . '() does not exist on Zen Cart 1.5.8.');
    }
    $adminPage = file_get_contents($root . '/admin/share_everywhere.php');
    check(strpos($adminPage, "require DIR_WS_INCLUDES . 'admin_html_head.php';") < strpos($adminPage, "'header.php';")
        && str_contains($adminPage, "require DIR_WS_INCLUDES . 'admin_html_head.php';")
        && !str_contains($adminPage, 'includes/stylesheet.css'), 'Use the supported Zen Cart admin head before the header.');
    $installer = new ScriptedInstaller($db);
    check($installer->install(), 'Install failed.');
    check($pages['catalogShareEverywhere'] === ['BOX_CATALOG_SHARE_EVERYWHERE', 'FILENAME_SHARE_EVERYWHERE', '', 'catalog', 'Y'],
        'Catalog menu registration is incorrect.');
    $settings = json_decode($config[ShareEverywhere::KEY], true);
    check(is_array($settings) && !$settings['enabled'] && count($settings['buttons']) > 90, 'Install defaults are incorrect.');
    check($installer->install() && count($config) === 2, 'A repeated install must keep the existing row.');
    $config[ShareEverywhere::KEY] = '{"enabled":true}';
    check(defined($pages['catalogShareEverywhere'][1]) && constant($pages['catalogShareEverywhere'][1]) === 'share_everywhere',
        'Zen Cart would skip a menu target that is not a defined filename constant.');
    $pages['catalogShareEverywhere'][1] = 'share_everywhere';
    $pages['catalogShareEverywhere'][4] = 'N';
    check($installer->upgrade('v1.0.7') && $config[ShareEverywhere::KEY] === '{"enabled":true}', 'Upgrade replaced settings.');
    check($pages['catalogShareEverywhere'][1] === 'FILENAME_SHARE_EVERYWHERE', 'Upgrade did not repair the 1.0.0 menu.');
    check($pages['catalogShareEverywhere'][4] === 'N', 'Upgrade replaced the existing menu registration.');

    // Settings saved by 1.0.6 and earlier carry the admin sanitizer's escaping, once per save.
    $escaped = [
        'enabled' => true, 'label' => 'Share &amp;amp; save',
        'buttons' => ['follow-youtube' => ['enabled' => true, 'order' => 5, 'pinned' => false,
            'url' => 'https://www.youtube.com/@shop?a=1&amp;amp;b=2']],
        'custom' => [['label' => 'Tom &amp; Jerry\'s', 'kind' => 'share', 'icon' => 'link', 'enabled' => true,
            'order' => 1, 'pinned' => false, 'url' => 'https://example.com/s?url={url}&amp;amp;amp;title={title}']],
    ];
    $config[ShareEverywhere::KEY] = json_encode($escaped);
    check($installer->upgrade('v1.0.6'), 'Upgrade from 1.0.6 failed.');
    $repaired = json_decode($config[ShareEverywhere::KEY], true);
    check($repaired['label'] === 'Share & save'
        && $repaired['buttons']['follow-youtube']['url'] === 'https://www.youtube.com/@shop?a=1&b=2'
        && $repaired['buttons']['follow-youtube']['enabled'] === true
        && $repaired['custom'][0]['url'] === 'https://example.com/s?url={url}&title={title}'
        && $repaired['custom'][0]['label'] === "Tom & Jerry's", 'Upgrade did not repair escaped 1.0.6 settings.');
    // 1.5.8 and 2.0.x pass no version; the installer reads it from plugin_control.
    $config[ShareEverywhere::KEY] = json_encode($escaped);
    $installedVersion = 'v1.0.5';
    check($installer->upgrade() && json_decode($config[ShareEverywhere::KEY], true)['label'] === 'Share & save',
        'Upgrade without a version argument did not repair 1.0.5 settings.');
    // From 1.0.7 on, values are stored as typed: a literal "&amp;" must survive.
    $literal = ['label' => 'Use &amp; for an ampersand'] + ShareEverywhere::defaults();
    $config[ShareEverywhere::KEY] = json_encode($literal);
    $installedVersion = 'v1.0.7';
    check($installer->upgrade() && $installer->upgrade('v1.0.7')
        && json_decode($config[ShareEverywhere::KEY], true)['label'] === 'Use &amp; for an ampersand',
        'Upgrade from 1.0.7 altered saved text.');

    unset($pages['catalogShareEverywhere']);
    // Admin bootstrap has already included the helper before loading settings.
    require_once $root . '/admin/includes/functions/extra_functions/share_everywhere_menu.php';
    check(!isset($pages['catalogShareEverywhere']), 'Bootstrap registered a menu before settings loaded.');
    define(ShareEverywhere::KEY, $config[ShareEverywhere::KEY]);
    class base {}
    require $root . '/admin/includes/classes/observers/auto.share_everywhere_admin.php';
    new zcObserverShareEverywhereAdmin();
    check(isset($pages['catalogShareEverywhere']), 'Runtime menu repair did not run.');
    check($installer->uninstall() && $pages === ['unrelated' => ['something']], 'Uninstall affected an unrelated registration.');
    check($config === ['OTHER_KEY' => 'keep'], 'Uninstall did not remove only the plugin settings row.');
    echo "Installer defaults, upgrade preservation and repair, menu repair, and uninstall checks passed.\n";
}
