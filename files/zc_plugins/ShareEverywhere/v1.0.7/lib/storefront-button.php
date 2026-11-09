<?php
if (!defined('IS_ADMIN_FLAG') || IS_ADMIN_FLAG !== false) {
    exit('Illegal access');
}

$label = $h($button['label']);
$content = ShareEverywhere::storefrontIcon($button['icon'], $button['kind'] === 'follow' ? 'follow' : 'link')
    . '<span class="se-label">' . $label . '</span>';
$attributes = ' class="se-button se-action" aria-label="' . $label . '" title="' . $label
    . '" data-se-pinned="' . (int)$button['pinned'] . '"';
if ($button['href'] === '') {
    echo '<button type="button"' . $attributes . ' data-se-action="' . $h($button['key']) . '" hidden>'
        . $content . '</button>';
} else {
    $newTab = str_starts_with($button['href'], 'https://') || str_starts_with($button['href'], 'http://');
    echo '<a' . $attributes . ' href="' . $h($button['href']) . '"'
        . ($newTab ? ' target="_blank" rel="noopener noreferrer"' : '') . '>' . $content . '</a>';
}
