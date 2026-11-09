<?php
use Zencart\PluginSupport\ScriptedInstaller as ScriptedInstallBase;

if (!defined('IS_ADMIN_FLAG') || IS_ADMIN_FLAG !== true) {
    exit('Illegal access');
}

require_once dirname(__DIR__) . '/lib/ShareEverywhere.php';

// Uses executeInstallerSql() and the installer's database connection only. Zen Cart 1.5.8
// has neither the configuration helpers (2.0.0+) nor their 2.1.0 signatures.
class ScriptedInstaller extends ScriptedInstallBase
{
    protected function executeInstall(): bool
    {
        return $this->writeSettings(false) && $this->syncMenu();
    }

    // 1.5.8 and 2.0.x call this with no argument; 2.1.0 and later pass the old version.
    protected function executeUpgrade(...$args): bool
    {
        $old = isset($args[0]) && is_string($args[0]) ? $args[0] : $this->installedVersion();
        $repair = $old !== '' && version_compare(ltrim($old, 'vV'), '1.0.7', '<');
        return $this->writeSettings($repair) && $this->syncMenu();
    }

    protected function executeUninstall(): bool
    {
        zen_deregister_admin_pages(['catalogShareEverywhere']);
        return $this->executeInstallerSql(
            'DELETE FROM ' . TABLE_CONFIGURATION . " WHERE configuration_key = '" . ShareEverywhere::KEY . "'"
        ) && $this->readSettings() === null;
    }

    private function readSettings(): ?string
    {
        $row = $this->dbConn->Execute(
            'SELECT configuration_value FROM ' . TABLE_CONFIGURATION
            . " WHERE configuration_key = '" . ShareEverywhere::KEY . "'", 1
        );
        return $row->EOF ? null : (string)$row->fields['configuration_value'];
    }

    private function installedVersion(): string
    {
        $row = $this->dbConn->Execute(
            'SELECT version FROM ' . TABLE_PLUGIN_CONTROL . " WHERE unique_key = 'ShareEverywhere' AND status = 1", 1
        );
        return $row->EOF ? '' : (string)$row->fields['version'];
    }

    private function writeSettings(bool $repair): bool
    {
        $current = $this->readSettings();
        if ($current === null) {
            $json = json_encode(ShareEverywhere::defaults(), JSON_THROW_ON_ERROR);
            $this->executeInstallerSql(
                'INSERT INTO ' . TABLE_CONFIGURATION
                . ' (configuration_title, configuration_key, configuration_value, configuration_description,'
                . ' configuration_group_id, sort_order, date_added) VALUES'
                . " ('Share Everywhere settings', '" . ShareEverywhere::KEY . "', '" . $this->dbConn->prepare_input($json)
                . "', 'Managed through Catalog > Share Everywhere.', 0, 0, now())"
            );
            return $this->readSettings() !== null;
        }
        $saved = json_decode($current, true);
        if (!$repair || !is_array($saved)) {
            return true;
        }
        // Before 1.0.7, Zen Cart's admin sanitizer HTML-escaped every saved label and URL,
        // once per save. Undo exactly that escaping so existing links work again.
        $errors = [];
        $json = json_encode(ShareEverywhere::normalize(ShareEverywhere::unescapeLegacy($saved), $errors), JSON_INVALID_UTF8_SUBSTITUTE);
        if ($json === false || $json === $current) {
            return true;
        }
        return $this->executeInstallerSql(
            'UPDATE ' . TABLE_CONFIGURATION . " SET configuration_value = '" . $this->dbConn->prepare_input($json)
            . "', last_modified = now() WHERE configuration_key = '" . ShareEverywhere::KEY . "'"
        );
    }

    private function syncMenu(): bool
    {
        require_once dirname(__DIR__) . '/admin/includes/functions/extra_functions/share_everywhere_menu.php';
        share_everywhere_sync_catalog_menu();
        return true;
    }
}
