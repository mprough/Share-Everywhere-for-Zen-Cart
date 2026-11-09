# Share Everywhere for Zen Cart

**Download the install ZIP:** [Share-Everywhere-for-Zen-Cart-1.0.6.zip](https://github.com/mprough/Share-Everywhere-for-Zen-Cart/releases/download/v1.0.6/Share-Everywhere-for-Zen-Cart-1.0.6.zip). Extract it on your computer and follow the installation instructions below.


Share Everywhere is a free plugin for Zen Cart ecommerce software. It adds configurable product sharing, messaging, bookmarks, and store profile buttons to product pages.

## Compatibility

| Zen Cart | PHP |
| --- | --- |
| 1.5.8 | 7.4 through 8.0 |
| 2.0.x and 2.1.x | 8.0 through 8.3 |
| 2.2.x | 8.2 through 8.5 |
| 3.0.0 | 8.3 through 8.5 |

Use [Zen Cart's server requirements](https://docs.zen-cart.com/user/first_steps/server_requirements/) for your installed release. The package uses Plugin Manager and supports the standard product-page hooks in Bootstrap and Responsive Classic.

## Install and configure

1. Copy the contents of `files/zc_plugins/` into the store's `zc_plugins/` directory.
2. In Zen Cart admin, open **Modules > Plugin Manager** and install **Share Everywhere for Zen Cart 1.0.7**.
3. Open **Catalog > Share Everywhere**, choose a layout, and enable the destinations you want.
4. Select **Enable on product pages**, save, and open an enabled product page.

The widget is disabled at installation. It uses the standard footer notifier in Bootstrap and Responsive Classic, then moves below the add-to-cart area in the browser. For a customized template, keep the `NOTIFY_FOOTER_END` notifier and a standard add-to-cart or product container. If placement targets are missing, the widget stays where the notifier rendered it.

## Buttons and layouts

- Choose a single button, compact icon row, full row, grid, or vertical stack independently for desktop and mobile.
- Choose click or hover-and-click expansion. On a touch device or keyboard, use the same disclosure button.
- Configure order, compact-row pinning, alignment, button size, and labels. Choose whether to show the share title and use monochrome, platform, or custom icon colors.
- Share buttons build links from the product title, canonical URL, and main image where supported. Pinterest is hidden for products without a main image. Profile and invite buttons require the store's public URL.
- Native Share is available on supported browsers. Copy link, print, and a locally generated product QR code are also available.
- Optional destinations grouped under **Additional services via AddToAny** send the shopper to AddToAny after a click. The product page loads all icons and scripts from the store. Third-party destinations may require a login or app, and SMS support varies by device.

## Custom links

Enter a label, icon, and URL. For a custom profile, use a full HTTP or HTTPS URL. For a custom share button, use a fixed HTTPS destination and place `{url}` or `{text}` in its path, query, or fragment. You can also include `{title}` and `{image}`. The plugin URL-encodes the replacement values.

Example: `https://example.com/share?url={url}&title={title}`

Enter a product ID and choose **Prepare test links** to test the custom destinations before saving. Preview does not save the form.

## Upgrade and uninstall

Upload the new version and use **Modules > Plugin Manager** to upgrade the existing installation in place. Settings are stored in one plugin-owned configuration row and preserved by the upgrade handler. Upgrading from 1.0.6 or earlier also repairs labels and links that earlier versions saved with `&amp;` in place of `&`. Uninstall in Plugin Manager removes that row and the Catalog menu registration. The plugin creates no tables and requires no collation changes.

## Troubleshooting

- If the widget is missing, confirm the plugin is installed, **Enable on product pages** is selected, and at least one applicable button is enabled.
- If the admin menu is missing for a limited admin profile, have a superuser check its access to **Catalog > Share Everywhere**.
- If the widget appears near the footer on a custom template, check that the page contains a standard add-to-cart or product container.
- Follow buttons require a public profile, channel, or invite URL. Some external destinations also require a shopper account or compatible app.

## Support and license

Maintained by Melanie Prough, [PRO-Webs, Inc.](https://pro-webs.net/). Report reproducible issues and security concerns through the [PRO-Webs helpdesk](https://prowebsinc.zohodesk.com/portal/en/newticket). Source and change history are available in the [Share Everywhere repository](https://github.com/mprough/Share-Everywhere-for-Zen-Cart).

The plugin is available as a free download under [GPL v2](LICENSE) and is distributed without warranty. Installation Extra. Configuration and customization are available separately.

Brand artwork is from [Simple Icons](https://github.com/simple-icons/simple-icons), revision `1bb2f6e`. Product QR codes use [QR Code Generator](https://github.com/kazuhikoarase/qrcode-generator), revision `83b7e8f`. The Simple Icons CC0 project notice and QR Code Generator MIT notice are included with the assets. Platform names and marks identify their destinations and belong to their respective owners. See the [Simple Icons disclaimer](https://github.com/simple-icons/simple-icons/blob/1bb2f6e6ea50f920de8d2f3d273ae48564a5eaee/DISCLAIMER.md) for individual brand terms.
