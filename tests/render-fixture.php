<?php
declare(strict_types=1);

require __DIR__ . '/polyfills.php';
define('IS_ADMIN_FLAG', false);
$root = dirname(__DIR__) . '/files/zc_plugins/ShareEverywhere/v1.0.7';
require $root . '/lib/ShareEverywhere.php';

$settings = ShareEverywhere::defaults();
$settings['trigger'] = 'hover';
$settings['label'] = 'Share this duck';
$settings['size'] = $argv[1] ?? 'medium';
$settings['color_mode'] = $argv[2] ?? 'mono';
$settings['show_title'] = ($argv[3] ?? '1') === '1';
$settings['icon_color'] = '#a1357c';
$settings['buttons']['qr']['enabled'] = true;
$settings['buttons']['follow-instagram']['enabled'] = true;
$settings['buttons']['follow-instagram']['url'] = 'https://www.instagram.com/example/';
$product = ['url' => 'https://shop.example/duck', 'title' => 'Example duck', 'image' => 'https://shop.example/duck.png'];
$buttons = ShareEverywhere::buttons($settings, $product);
$assets = ShareEverywhere::assets('/');
?>
<!doctype html>
<html lang="en">
<head><meta charset="UTF-8"><title>Product fixture</title></head>
<body>
<main id="productGeneral"><h1>Example duck</h1><div id="cartAdd"><button>Add to Cart</button></div></main>
<footer>Store footer</footer>
<?php require $root . '/lib/storefront.php'; ?>
</body>
</html>
