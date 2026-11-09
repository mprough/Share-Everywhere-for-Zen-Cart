<?php
declare(strict_types=1);

require __DIR__ . '/polyfills.php';
define('IS_ADMIN_FLAG', false);
define('TABLE_PRODUCTS', 'products');
define('TABLE_PRODUCTS_DESCRIPTION', 'products_description');
define('TABLE_PRODUCT_TYPES', 'product_types');
define('HTTP_SERVER', 'http://shop.example');
define('HTTPS_SERVER', 'https://shop.example');
define('DIR_WS_CATALOG', '/store/');
define('DIR_WS_IMAGES', 'images/');

function zen_href_link($page, $query, $connection, $addSession): string
{
    if ($addSession || $connection !== 'SSL') {
        throw new RuntimeException('The share URL must omit session IDs.');
    }
    return 'https://shop.example/store/index.php?main_page=' . $page . '&amp;' . $query;
}

function check(bool $passed, string $message): void
{
    if (!$passed) {
        throw new RuntimeException($message);
    }
}

$root = dirname(__DIR__) . '/files/zc_plugins/ShareEverywhere/v1.0.7';
require $root . '/lib/ShareEverywhere.php';
$manifest = require $root . '/manifest.php';
check($manifest['pluginVersion'] === 'v' . ShareEverywhere::VERSION, 'Version mismatch.');
check($manifest['pluginId'] === 0, 'Unlisted plugins still need a Plugin Manager ID field.');
$settings = ShareEverywhere::defaults();
check(!$settings['enabled'], 'Plugin should start disabled.');
$appearanceErrors = [];
check(ShareEverywhere::normalize([], $appearanceErrors)['show_title'], 'Existing settings should keep the title visible.');
$appearance = ShareEverywhere::normalize(['show_title' => '0', 'color_mode' => 'custom', 'icon_color' => '#A1357C'], $appearanceErrors);
check(!$appearance['show_title'] && $appearance['icon_color'] === '#a1357c', 'Appearance options were not saved.');
check(ShareEverywhere::normalize(['icon_color' => 'red; color:black'], $appearanceErrors)['icon_color'] === '#20262d',
    'Invalid icon color was accepted.');

$db = new class {
    public function Execute(string $sql, int $limit): object
    {
        check(str_contains($sql, 'p.products_status = 1 AND p.products_id = 42'), 'Wrong product query.');
        check($limit === 1, 'Wrong result limit.');
        return (object)['EOF' => false, 'fields' => [
            'products_name' => 'Ducks &amp; Jeeps',
            'products_image' => 'ducks/blue duck.webp',
            'type_handler' => 'product',
        ]];
    }
};
$product = ShareEverywhere::product(42, 'https://shop.example/store/blue-duck.html');
check($product === [
    'url' => 'https://shop.example/store/blue-duck.html', 'page' => 'product_info',
    'title' => 'Ducks & Jeeps', 'image' => 'https://shop.example/store/images/ducks/blue%20duck.webp',
], 'Incorrect canonical URL or product data.');
check(ShareEverywhere::product(42, 'https://shop.example/p?zenid=secret')['url']
    === 'https://shop.example/store/index.php?main_page=product_info&products_id=42', 'Session URL was not discarded.');

$valid = 'https://example.org/share?url={url}&title={title}&image={image}';
check(ShareEverywhere::customShareUrl($valid), 'Valid template rejected.');
foreach ([
    'javascript:alert({url})', 'http://example.org/?x={url}', '//example.org/?x={url}',
    'https://{url}/share', 'https://user@example.org/?x={url}', 'https://example.org/?x={title}',
    'https://example.org/?x={url}&y={unknown}', 'https://example.org\\bad/?x={url}',
] as $invalid) {
    check(!ShareEverywhere::customShareUrl($invalid), 'Unsafe template accepted: ' . $invalid);
}
parse_str((string)parse_url(ShareEverywhere::expand($valid, $product), PHP_URL_QUERY), $parameters);
check($parameters === ['url' => $product['url'], 'title' => $product['title'], 'image' => $product['image']], 'Placeholder encoding failed.');

$settings['buttons']['follow-instagram'] = ['enabled' => true, 'url' => 'javascript:alert(1)', 'order' => 0];
$settings['buttons']['follow-tiktok'] = ['enabled' => true, 'url' => 'https://www.tiktok.com/@shop', 'order' => 1];
$settings['custom'] = [['enabled' => true, 'label' => '<Share>', 'url' => $valid, 'kind' => 'share', 'order' => 2]];
$errors = [];
$settings = ShareEverywhere::normalize($settings, $errors);
check(count($errors) === 1, 'Invalid profile did not get a validation error.');
$buttons = ShareEverywhere::buttons($settings, $product);
check(array_column(array_slice($buttons, 0, 2), 'key') === ['follow-tiktok', 'custom'], 'Incorrect button order.');
check(!in_array('follow-instagram', array_column($buttons, 'key'), true), 'Unsafe profile rendered.');
check(!in_array('pinterest', array_column(ShareEverywhere::buttons($settings, ['image' => ''] + $product), 'key'), true),
    'Pinterest should be hidden for products without an image.');
check(str_contains(ShareEverywhere::storefrontIcon('douban'), 'data-se-icon="douban"'),
    'Douban should use its dedicated local icon.');
$assets = ShareEverywhere::assets(DIR_WS_CATALOG);
ob_start();
require $root . '/lib/storefront.php';
$html = ob_get_clean();
check(str_contains($html, '&lt;Share&gt;'), 'Custom label was not escaped.');
check(str_contains($html, '/store/zc_plugins/ShareEverywhere/v1.0.7/assets/storefront.js'), 'Asset path is incorrect.');
check(!str_contains($html, 'qrcode.js'), 'QR code library loaded while the QR button was disabled.');
echo "Product data, URL validation, settings, and storefront rendering checks passed.\n";
