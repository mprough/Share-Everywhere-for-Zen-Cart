<?php
if (!defined('IS_ADMIN_FLAG') || IS_ADMIN_FLAG !== true) {
    exit('Illegal access');
}

require 'includes/application_top.php';
require_once dirname(__DIR__) . '/lib/ShareEverywhere.php';

if (!defined(ShareEverywhere::KEY)) {
    zen_redirect(zen_href_link(FILENAME_DEFAULT));
}

$settings = ShareEverywhere::settings();
$errors = [];
$testProduct = null;
$testId = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['securityToken'] ?? '';
    if (!is_string($token) || empty($_SESSION['securityToken']) || !hash_equals($_SESSION['securityToken'], $token)) {
        $errors[] = 'This form has expired. Reload the page and try again.';
    } elseif (!is_array($_POST['settings'] ?? null) || ($_POST['settings_complete'] ?? '') !== '1') {
        $errors[] = 'The settings form was incomplete. Reload the page and try again.';
    } else {
        $settings = ShareEverywhere::normalize($_POST['settings'], $errors);
        $testId = is_string($_POST['test_product'] ?? null) ? $_POST['test_product'] : '';
        if (isset($_POST['preview'])) {
            $id = filter_var($testId, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
            $testProduct = $id === false ? null : ShareEverywhere::product($id);
            if ($testProduct === null) {
                $errors[] = 'Enter the ID of an enabled product with a name in the active language.';
            }
        } elseif (isset($_POST['save']) && !$errors) {
            $json = json_encode($settings, JSON_INVALID_UTF8_SUBSTITUTE);
            if ($json === false || strlen($json) > 60000) {
                $errors[] = 'The settings could not be saved. Shorten the custom links and try again.';
            } else {
                zen_db_perform(TABLE_CONFIGURATION, [
                    'configuration_value' => $json,
                    'last_modified' => 'now()',
                ], 'UPDATE', "configuration_key = '" . ShareEverywhere::KEY . "'");
                $messageStack->add_session('Share Everywhere settings saved.', 'success');
                zen_redirect(zen_href_link(FILENAME_SHARE_EVERYWHERE));
            }
        }
    }
}

$h = [ShareEverywhere::class, 'h'];
$assets = ShareEverywhere::assets(DIR_WS_CATALOG);
$options = static function (array $items, string $selected) use ($h): void {
    foreach ($items as $value => $label) {
        echo '<option value="' . $h($value) . '"' . ($value === $selected ? ' selected' : '') . '>' . $h($label) . '</option>';
    }
};
$customRow = static function (string $index, array $row) use ($h, $options, $testProduct): void {
    $field = 'settings[custom][' . $index . ']';
    $testLink = '';
    if ($testProduct !== null) {
        if ($row['kind'] === 'share' && ShareEverywhere::customShareUrl($row['url'])) {
            $testLink = ShareEverywhere::expand($row['url'], $testProduct);
        } elseif ($row['kind'] === 'follow' && ShareEverywhere::webUrl($row['url'])) {
            $testLink = $row['url'];
        }
    }
    ?>
    <fieldset class="se-admin-custom" data-se-custom>
        <legend>Custom button</legend>
        <label><input type="checkbox" name="<?= $field ?>[enabled]" value="1"<?= $row['enabled'] ? ' checked' : '' ?>> Enabled</label>
        <label>Type <select name="<?= $field ?>[kind]"><?php $options(['share' => 'Share product', 'follow' => 'Follow or join'], $row['kind']); ?></select></label>
        <label>Label <input name="<?= $field ?>[label]" maxlength="100" value="<?= $h($row['label']) ?>"></label>
        <label>Icon <select name="<?= $field ?>[icon]"><?php $options(ShareEverywhere::CUSTOM_ICONS, $row['icon']); ?></select></label>
        <label>Order <input type="number" name="<?= $field ?>[order]" min="0" max="9999" value="<?= (int)$row['order'] ?>"></label>
        <label><input type="checkbox" name="<?= $field ?>[pinned]" value="1"<?= $row['pinned'] ? ' checked' : '' ?>> Keep visible in compact layout</label>
        <label class="se-admin-url">URL or URL template <input name="<?= $field ?>[url]" maxlength="600" value="<?= $h($row['url']) ?>"></label>
        <?php if ($testLink !== '') { ?>
            <a href="<?= $h($testLink) ?>" target="_blank" rel="noopener noreferrer">Open test link</a>
        <?php } ?>
        <button type="button" class="btn btn-default" data-se-remove>Remove</button>
    </fieldset>
    <?php
};
$blank = ['label' => '', 'url' => '', 'kind' => 'share', 'icon' => 'link', 'order' => 900, 'pinned' => false, 'enabled' => false];
?>
<!doctype html>
<html <?= HTML_PARAMS ?>>
<head>
<?php require DIR_WS_INCLUDES . 'admin_html_head.php'; ?>
<link rel="stylesheet" href="<?= $h($assets . 'admin.css') ?>">
<script defer src="<?= $h($assets . 'admin.js') ?>"></script>
</head>
<body>
<?php require DIR_WS_INCLUDES . 'header.php'; ?>
<main class="se-admin">
    <h1>Share Everywhere <small><?= ShareEverywhere::VERSION ?></small></h1>
    <p>Choose the buttons shoppers see on product pages. Lower order numbers appear first within each group.</p>
    <?php foreach ($errors as $error) { ?><p role="alert" class="se-admin-error"><?= $h($error) ?></p><?php } ?>
    <?= zen_draw_form('share_everywhere', FILENAME_SHARE_EVERYWHERE, '', 'post') ?>
    <fieldset>
        <legend>Product-page display</legend>
        <div class="se-admin-fields">
            <label><input type="checkbox" name="settings[enabled]" value="1"<?= $settings['enabled'] ? ' checked' : '' ?>> Enable on product pages</label>
            <input type="hidden" name="settings[show_title]" value="0">
            <label><input type="checkbox" name="settings[show_title]" value="1"<?= $settings['show_title'] ? ' checked' : '' ?>> Show share title</label>
            <label>Share title <input name="settings[label]" maxlength="100" value="<?= $h($settings['label']) ?>"></label>
            <label>Desktop layout <select name="settings[desktop]"><?php $options(ShareEverywhere::LAYOUTS, $settings['desktop']); ?></select></label>
            <label>Mobile layout <select name="settings[mobile]"><?php $options(ShareEverywhere::LAYOUTS, $settings['mobile']); ?></select></label>
            <label>Expand on <select name="settings[trigger]"><?php $options(['click' => 'Click or tap', 'hover' => 'Mouseover and click or tap'], $settings['trigger']); ?></select></label>
            <label>Alignment <select name="settings[alignment]"><?php $options(['start' => 'Start', 'center' => 'Center', 'end' => 'End'], $settings['alignment']); ?></select></label>
            <label>Icon size <select name="settings[size]"><?php $options(['small' => 'Small', 'medium' => 'Medium', 'large' => 'Large'], $settings['size']); ?></select></label>
            <label>Icon colors <select name="settings[color_mode]"><?php $options(ShareEverywhere::COLOR_MODES, $settings['color_mode']); ?></select></label>
            <label>Custom icon color <input type="color" name="settings[icon_color]" value="<?= $h($settings['icon_color']) ?>"></label>
            <label>Maximum pinned buttons <input type="number" name="settings[visible]" min="0" max="20" value="<?= (int)$settings['visible'] ?>"></label>
            <label><input type="checkbox" name="settings[labels]" value="1"<?= $settings['labels'] ? ' checked' : '' ?>> Show icon labels</label>
        </div>
    </fieldset>
    <?php foreach (ShareEverywhere::destinations() as $name => $group) { ?>
    <details<?= $name === 'Social sharing' ? ' open' : '' ?>>
        <summary><?= $h($name) ?></summary>
        <div class="se-admin-table">
            <table>
                <thead><tr><th scope="col">Button</th><th scope="col">Enabled</th><th scope="col">Order</th><th scope="col">Pin</th><th scope="col">Details</th></tr></thead>
                <tbody>
                <?php foreach ($group as $key => $item) {
                    [$label, $help] = $item;
                    $row = $settings['buttons'][$key];
                    $field = 'settings[buttons][' . $key . ']';
                    $follow = str_starts_with($key, 'follow-');
                    ?>
                    <tr>
                        <th scope="row"><?= ShareEverywhere::icon($follow ? substr($key, 7) : $key, $assets) ?> <?= $h($label) ?></th>
                        <td><input type="checkbox" name="<?= $field ?>[enabled]" value="1" aria-label="<?= $h($label . ' enabled') ?>"<?= $row['enabled'] ? ' checked' : '' ?>></td>
                        <td><input type="number" name="<?= $field ?>[order]" min="0" max="9999" aria-label="<?= $h($label . ' order') ?>" value="<?= (int)$row['order'] ?>"></td>
                        <td><input type="checkbox" name="<?= $field ?>[pinned]" value="1" aria-label="<?= $h($label . ' pinned') ?>"<?= $row['pinned'] ? ' checked' : '' ?>></td>
                        <td>
                            <?= $h($help) ?>
                            <?php if ($follow) { ?>
                                <input type="url" name="<?= $field ?>[url]" maxlength="600" placeholder="https://" aria-label="<?= $h($label . ' profile URL') ?>" value="<?= $h($row['url']) ?>">
                            <?php } ?>
                        </td>
                    </tr>
                <?php } ?>
                </tbody>
            </table>
        </div>
    </details>
    <?php } ?>
    <details open>
        <summary>Custom links</summary>
        <p>For sharing, enter an HTTPS template with <code>{url}</code> or <code>{text}</code>. You can also use <code>{title}</code> and <code>{image}</code>. For profiles, enter the full profile or invite URL.</p>
        <div id="se-custom-links" data-limit="<?= ShareEverywhere::MAX_CUSTOM ?>">
            <?php foreach ($settings['custom'] as $i => $row) { $customRow((string)$i, $row); } ?>
        </div>
        <template id="se-custom-template"><?php $customRow('__index__', $blank); ?></template>
        <button type="button" class="btn btn-default" id="se-add-custom">Add custom button</button>
        <label>Product ID <input type="number" name="test_product" min="1" value="<?= $h($testId) ?>"></label>
        <button type="submit" name="preview" value="1" class="btn btn-default">Prepare test links</button>
        <?php if ($testProduct !== null) { ?><p>Test links use <?= $h($testProduct['title']) ?>. These settings have not been saved.</p><?php } ?>
    </details>
    <input type="hidden" name="settings_complete" value="1">
    <button type="submit" name="save" value="1" class="btn btn-primary">Save settings</button>
    </form>
</main>
<?php require DIR_WS_INCLUDES . 'application_bottom.php'; ?>
</body>
</html>
