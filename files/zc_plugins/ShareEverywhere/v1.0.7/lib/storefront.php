<?php
if (!defined('IS_ADMIN_FLAG') || IS_ADMIN_FLAG !== false) {
    exit('Illegal access');
}

$h = [ShareEverywhere::class, 'h'];
?>
<link rel="stylesheet" href="<?= $h($assets . 'storefront.css') ?>">
<?php if (in_array('qr', array_column($buttons, 'key'), true)) { ?>
<script defer src="<?= $h($assets . 'qrcode.js') ?>"></script>
<?php } ?>
<script defer src="<?= $h($assets . 'storefront.js') ?>"></script>
<section id="share-everywhere" class="se se-align-<?= $h($settings['alignment']) ?> se-size-<?= $h($settings['size']) ?> se-color-<?= $h($settings['color_mode']) ?><?= $settings['labels'] ? ' se-labels' : '' ?>"<?= $settings['color_mode'] === 'custom' ? ' style="--se-custom-color:' . $h($settings['icon_color']) . '"' : '' ?>
         aria-label="<?= $h($settings['label']) ?>" data-se-desktop="<?= $h($settings['desktop']) ?>"
         data-se-mobile="<?= $h($settings['mobile']) ?>" data-se-trigger="<?= $h($settings['trigger']) ?>"
         data-se-visible="<?= (int)$settings['visible'] ?>" data-se-url="<?= $h($product['url']) ?>"
         data-se-title="<?= $h($product['title']) ?>" data-se-show-title="<?= (int)$settings['show_title'] ?>">
    <p class="se-title"<?= $settings['show_title'] ? '' : ' hidden' ?>><?= $h($settings['label']) ?></p>
    <div class="se-bar">
        <button type="button" class="se-button se-toggle" aria-expanded="false" aria-controls="se-panel" hidden>
            <?= ShareEverywhere::storefrontIcon('share') ?>
            <span class="se-toggle-label"><?= $h($settings['label']) ?></span>
        </button>
    </div>
    <div id="se-panel" class="se-panel">
        <div class="se-group" data-se-group="share">
            <span class="se-group-label">Share</span>
            <div class="se-items">
            <?php foreach ($buttons as $button) {
                if ($button['kind'] !== 'share') {
                    continue;
                }
                require __DIR__ . '/storefront-button.php';
            } ?>
            </div>
        </div>
        <div class="se-group" data-se-group="follow">
            <span class="se-group-label">Follow our store</span>
            <div class="se-items">
            <?php foreach ($buttons as $button) {
                if ($button['kind'] !== 'follow') {
                    continue;
                }
                require __DIR__ . '/storefront-button.php';
            } ?>
            </div>
        </div>
    </div>
    <p class="se-status" role="status" hidden></p>
    <div class="se-qr" hidden>
        <p>Scan to open this product</p>
        <canvas role="img" aria-label="QR code for this product"></canvas>
        <button type="button" class="se-close-qr">Close QR code</button>
    </div>
</section>
