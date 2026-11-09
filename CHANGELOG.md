# Changelog

## 1.0.7

- Support Zen Cart 1.5.8 and 3.0.0 and PHP 7.4. Installing on 1.5.8 failed with a fatal error because the installer used configuration helpers that start at Zen Cart 2.0.
- Save labels, profile URLs, and custom link templates exactly as entered. Zen Cart's admin sanitizer stored each `&` as `&amp;` and added another layer on every save, which broke custom share links and profile URLs with more than one query parameter.
- Repair settings saved by earlier versions during the upgrade.

## 1.0.6

- Prepare the free public package with updated compatibility, installation, and support documentation.
- Include a dedicated Douban destination icon drawn for the plugin.

## 1.0.5

- Add a share title toggle and monochrome, platform, or custom icon colors.
- Make the small, medium, and large presets change both the icon and button dimensions.

## 1.0.4

- Display the configured share title above the product-page controls when the layout uses a More button or has no disclosure button.

## 1.0.3

- Use the standard Zen Cart admin HTML head on the settings page to resolve the legacy-header warning.

## 1.0.2

- Repair the Catalog menu after Zen Cart loads its configuration, even when the previous version was loaded earlier in the upgrade request.

## 1.0.1

- Repair the Catalog menu registration on install and upgrade while preserving settings and admin profile assignments.

## 1.0.0

- Add product sharing, messaging, bookmarks, store profiles, and device tools.
- Add configurable desktop and mobile layouts, click and hover expansion, and per-button ordering and pinning.
- Add custom share URL templates and profile links with test links for a selected product.
