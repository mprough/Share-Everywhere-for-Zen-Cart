<?php
declare(strict_types=1);

if (!defined('IS_ADMIN_FLAG')) {
    exit('Illegal access');
}

final class ShareEverywhere
{
    public const VERSION = '1.0.7';
    public const KEY = 'SHARE_EVERYWHERE_SETTINGS';
    public const MAX_CUSTOM = 20;
    public const COLOR_MODES = ['mono' => 'Monochrome', 'brand' => 'Platform colors', 'custom' => 'Custom color'];
    public const CUSTOM_ICONS = ['link' => 'Link', 'share' => 'Share', 'follow' => 'Follow', 'email' => 'Email', 'sms' => 'Message'];
    public const LAYOUTS = [
        'single' => 'Single share button',
        'compact' => 'Compact row with More',
        'row' => 'Full icon row',
        'grid' => 'Icon grid',
        'stack' => 'Vertical stack',
    ];

    public static function destinations(): array
    {
        return require __DIR__ . '/destinations.php';
    }

    public static function defaults(): array
    {
        $buttons = [];
        foreach (self::destinations() as $group) {
            foreach ($group as $key => $item) {
                $buttons[$key] = [
                    'enabled' => in_array($key, ['facebook', 'pinterest', 'email', 'copy', 'share'], true),
                    'order' => (count($buttons) + 1) * 10,
                    'pinned' => in_array($key, ['facebook', 'pinterest', 'copy'], true),
                    'url' => '',
                ];
            }
        }
        return [
            'enabled' => false, 'desktop' => 'compact', 'mobile' => 'single', 'trigger' => 'click',
            'alignment' => 'start', 'size' => 'medium', 'labels' => false, 'visible' => 4,
            'label' => 'Share this product', 'show_title' => true, 'color_mode' => 'mono', 'icon_color' => '#20262d',
            'buttons' => $buttons, 'custom' => [],
        ];
    }

    public static function settings(): array
    {
        $saved = defined(self::KEY) ? json_decode((string)constant(self::KEY), true) : null;
        $errors = [];
        return is_array($saved) ? self::normalize($saved, $errors) : self::defaults();
    }

    public static function normalize(array $input, array &$errors): array
    {
        $settings = self::defaults();
        foreach (['desktop', 'mobile', 'trigger', 'alignment', 'size', 'color_mode'] as $key) {
            $choices = [
                'desktop' => array_keys(self::LAYOUTS), 'mobile' => array_keys(self::LAYOUTS),
                'trigger' => ['click', 'hover'], 'alignment' => ['start', 'center', 'end'],
                'size' => ['small', 'medium', 'large'], 'color_mode' => array_keys(self::COLOR_MODES),
            ][$key];
            if (in_array($input[$key] ?? null, $choices, true)) {
                $settings[$key] = $input[$key];
            }
        }
        $settings['enabled'] = !empty($input['enabled']);
        $settings['labels'] = !empty($input['labels']);
        if (array_key_exists('show_title', $input)) {
            $settings['show_title'] = !empty($input['show_title']);
        }
        $color = $input['icon_color'] ?? '';
        if (is_string($color) && preg_match('/^#[0-9a-f]{6}$/iD', $color)) {
            $settings['icon_color'] = strtolower($color);
        }
        $settings['visible'] = self::number($input['visible'] ?? null, 0, 20, 4);
        $settings['label'] = self::text($input['label'] ?? '', 100) ?: $settings['label'];

        foreach (self::destinations() as $group) {
            foreach ($group as $key => [$name]) {
                $row = $input['buttons'][$key] ?? null;
                if (!is_array($row)) {
                    continue;
                }
                $follow = str_starts_with($key, 'follow-');
                $url = $follow ? self::text($row['url'] ?? '', 600) : '';
                $enabled = !empty($row['enabled']);
                if ($follow && (($url !== '' && !self::webUrl($url)) || ($enabled && $url === ''))) {
                    $errors[] = $name . ' requires a valid profile or channel URL.';
                    $enabled = false;
                }
                $settings['buttons'][$key] = [
                    'enabled' => $enabled, 'order' => self::number($row['order'] ?? null, 0, 9999, $settings['buttons'][$key]['order']),
                    'pinned' => !empty($row['pinned']), 'url' => $url,
                ];
            }
        }

        $custom = $input['custom'] ?? [];
        if (!is_array($custom)) {
            return $settings;
        }
        foreach (array_slice($custom, 0, self::MAX_CUSTOM) as $row) {
            if (!is_array($row)) {
                continue;
            }
            $label = self::text($row['label'] ?? '', 100);
            $url = self::text($row['url'] ?? '', 600);
            if ($label === '' && $url === '') {
                continue;
            }
            $kind = ($row['kind'] ?? '') === 'follow' ? 'follow' : 'share';
            $icon = $row['icon'] ?? '';
            $valid = $label !== '' && ($kind === 'share' ? self::customShareUrl($url) : self::webUrl($url));
            if (!$valid) {
                $errors[] = 'Custom ' . $kind . ' link ' . (count($settings['custom']) + 1)
                    . ' needs a label and a valid ' . ($kind === 'share' ? 'HTTPS template containing {url} or {text}.' : 'profile URL.');
            }
            $settings['custom'][] = [
                'label' => $label, 'kind' => $kind, 'url' => $url,
                'icon' => is_string($icon) && isset(self::CUSTOM_ICONS[$icon]) ? $icon : 'link',
                'enabled' => !empty($row['enabled']),
                'order' => self::number($row['order'] ?? null, 0, 9999, 900),
                'pinned' => !empty($row['pinned']),
            ];
        }
        return $settings;
    }

    /**
     * Reverses the HTML escaping Zen Cart's admin sanitizer applied to saved text before 1.0.7
     * (htmlspecialchars with ENT_COMPAT, once per save). Touches only free-text fields.
     */
    public static function unescapeLegacy(array $saved): array
    {
        $fix = static function ($value) {
            for ($i = 0; $i < 20 && is_string($value) && preg_match('/&(?:amp|quot|lt|gt);/', $value); $i++) {
                $value = htmlspecialchars_decode($value, ENT_COMPAT);
            }
            return $value;
        };
        if (isset($saved['label'])) {
            $saved['label'] = $fix($saved['label']);
        }
        foreach (is_array($saved['buttons'] ?? null) ? $saved['buttons'] : [] as $key => $row) {
            if (is_array($row) && isset($row['url'])) {
                $saved['buttons'][$key]['url'] = $fix($row['url']);
            }
        }
        foreach (is_array($saved['custom'] ?? null) ? $saved['custom'] : [] as $i => $row) {
            if (is_array($row)) {
                foreach (['label', 'url'] as $field) {
                    if (isset($row[$field])) {
                        $saved['custom'][$i][$field] = $fix($row[$field]);
                    }
                }
            }
        }
        return $saved;
    }

    public static function h(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    private static function text($value, int $max): string
    {
        return is_string($value) ? trim(substr($value, 0, $max)) : '';
    }

    private static function number($value, int $min, int $max, int $default): int
    {
        return (is_string($value) || is_int($value)) && ctype_digit((string)$value)
            ? min($max, max($min, (int)$value)) : $default;
    }

    public static function webUrl(string $url, bool $httpsOnly = false): bool
    {
        if ($url === '' || preg_match('/[[:space:]\\\\{}]/', $url) || !filter_var($url, FILTER_VALIDATE_URL)) {
            return false;
        }
        $parts = parse_url($url);
        return is_array($parts) && isset($parts['host']) && !isset($parts['user']) && !isset($parts['pass'])
            && in_array(strtolower($parts['scheme'] ?? ''), $httpsOnly ? ['https'] : ['https', 'http'], true);
    }

    public static function customShareUrl(string $template): bool
    {
        if (!preg_match('~^https://([^/?#]+)[/?#]~i', $template, $authority)
            || strpbrk($authority[1], '{}') !== false
            || (!str_contains($template, '{url}') && !str_contains($template, '{text}'))) {
            return false;
        }
        return self::webUrl(str_replace(['{url}', '{title}', '{image}', '{text}'], 'test', $template), true);
    }

    public static function expand(string $template, array $product): string
    {
        return strtr($template, [
            '{url}' => rawurlencode($product['url']),
            '{title}' => rawurlencode($product['title']),
            '{image}' => rawurlencode($product['image']),
            '{text}' => rawurlencode($product['title'] . ' ' . $product['url']),
        ]);
    }

    public static function assets(string $catalogBase): string
    {
        return rtrim($catalogBase, '/') . '/zc_plugins/ShareEverywhere/v' . self::VERSION . '/assets/';
    }

    public static function product(int $id, string $canonical = ''): ?array
    {
        global $db;
        if ($id < 1) {
            return null;
        }
        $language = (int)($_SESSION['languages_id'] ?? 1);
        $result = $db->Execute(
            'SELECT pd.products_name, p.products_image, pt.type_handler FROM ' . TABLE_PRODUCTS . ' p JOIN '
            . TABLE_PRODUCTS_DESCRIPTION . ' pd ON pd.products_id = p.products_id AND pd.language_id = '
            . $language . ' LEFT JOIN ' . TABLE_PRODUCT_TYPES . ' pt ON pt.type_id = p.products_type'
            . ' WHERE p.products_status = 1 AND p.products_id = ' . $id,
            1
        );
        if ($result->EOF || trim((string)$result->fields['products_name']) === '') {
            return null;
        }
        $type = (string)$result->fields['type_handler'];
        $page = preg_match('/^[a-z][a-z0-9_]*$/i', $type) ? $type . '_info' : 'product_info';
        $canonical = html_entity_decode($canonical, ENT_QUOTES, 'UTF-8');
        if (self::webUrl($canonical) && !preg_match('/[?&]zenid=/i', $canonical)) {
            $url = $canonical;
        } else {
            $parameters = 'products_id=' . $id;
            $link = IS_ADMIN_FLAG ? zen_catalog_href_link($page, $parameters, 'SSL')
                : zen_href_link($page, $parameters, 'SSL', false);
            $url = html_entity_decode($link, ENT_QUOTES, 'UTF-8');
        }
        if (!self::webUrl($url)) {
            return null;
        }
        $imagePath = trim((string)$result->fields['products_image']);
        $catalog = IS_ADMIN_FLAG ? HTTP_CATALOG_SERVER : HTTP_SERVER;
        if (!IS_ADMIN_FLAG && parse_url($url, PHP_URL_SCHEME) === 'https' && defined('HTTPS_SERVER') && HTTPS_SERVER !== '') {
            $catalog = HTTPS_SERVER;
        }
        $imageDirectory = defined('DIR_WS_IMAGES') ? DIR_WS_IMAGES : 'images/';
        $image = $imagePath === '' ? '' : rtrim($catalog, '/') . '/' . ltrim(DIR_WS_CATALOG, '/')
            . ltrim($imageDirectory, '/') . implode('/', array_map('rawurlencode', explode('/', ltrim($imagePath, '/'))));
        return [
            'url' => $url, 'page' => $page,
            'title' => html_entity_decode((string)$result->fields['products_name'], ENT_QUOTES, 'UTF-8'),
            'image' => $image,
        ];
    }

    public static function buttons(array $settings, array $product): array
    {
        $buttons = [];
        foreach (self::destinations() as $group) {
            foreach ($group as $key => $item) {
                $row = $settings['buttons'][$key];
                if (!$row['enabled'] || ($key === 'pinterest' && $product['image'] === '')) {
                    continue;
                }
                $follow = str_starts_with($key, 'follow-');
                $url = $follow ? $row['url'] : self::expand($item[2], $product);
                if ($follow && !self::webUrl($url)) {
                    continue;
                }
                $buttons[] = $row + [
                    'key' => $key, 'label' => $follow ? 'Follow on ' . $item[0] : $item[0],
                    'icon' => $follow ? substr($key, 7) : $key, 'kind' => $follow ? 'follow' : 'share', 'href' => $url,
                ];
            }
        }
        foreach ($settings['custom'] as $row) {
            $follow = $row['kind'] === 'follow';
            if ($row['enabled'] && $row['label'] !== ''
                && ($follow ? self::webUrl($row['url']) : self::customShareUrl($row['url']))) {
                $buttons[] = $row + ['key' => 'custom', 'href' => $follow ? $row['url'] : self::expand($row['url'], $product)];
            }
        }
        usort($buttons, static fn(array $a, array $b): int => $a['order'] <=> $b['order']);
        return $buttons;
    }

    private static function iconName(string $name, string $fallback): string
    {
        return preg_match('/^[a-z0-9-]+$/D', $name) && is_file(dirname(__DIR__) . '/assets/icons/' . $name . '.svg')
            ? $name : $fallback;
    }

    public static function icon(string $name, string $assets): string
    {
        $name = self::iconName($name, 'link');
        return '<img src="' . self::h($assets . 'icons/' . $name . '.svg') . '" width="20" height="20" alt="" loading="lazy">';
    }

    public static function storefrontIcon(string $name, string $fallback = 'link'): string
    {
        // Only render SVGs shipped with the plugin. No uploaded or remote SVGs.
        $name = self::iconName($name, $fallback);
        $svg = file_get_contents(dirname(__DIR__) . '/assets/icons/' . $name . '.svg');
        if ($svg === false) {
            return '';
        }
        return str_replace(
            ['<svg ', 'stroke="#20262d"'],
            ['<svg class="se-icon" data-se-icon="' . self::h($name) . '" aria-hidden="true" focusable="false" ', 'stroke="currentColor"'],
            $svg
        );
    }
}
