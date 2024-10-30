=== Micropayments Paywall ===
Contributors: ronantrelis
Tags: paywall, membership, restricted-content, micropayments, credit card, debit card, stripe
Requires at least: 6.1
Tested up to: 6.3
Stable tag: 4.0.2
Requires PHP: 7.4
License: GPL-3.0
License URI: http://www.gnu.org/licenses/gpl-3.0.txt

Paywall your posts with a micropayments paywall.

== Description ==

Micropayments Paywall allows you to monetize your content by setting up a paywall. Users pay to access individual posts or pay for a lifetime membership (Premium Plugin only), allowing you to generate revenue directly from your website.

== Getting Started ==

1. Install the Micropayments Paywall plugin.
1. Activate the plugin.
1. Go to your WordPress dashboard and click on 'Paywall' on the left hand menu.
1. Configure the Stripe payment gateway.
1. From "Posts" on the left hand menu, create or edit a post. Enable the paywall by checking the "Enable Paywall" box.
1. Set the price for the post in the "Product Price" field.
1. Publish or Save the post.
1. That's it! Your post will now be paywalled.

== Configuring Stripe Payment Gateway ==

If you have a Stripe account, you can allow customers to pay you via card by purchasing the [Premium plugin](https://buy.stripe.com/5kA9Bm5tPdmb4ow6oy).

Stripe fees typically include a flat fee (often 30c) plus a fixed percentage over 2.5%. In certain cases, you can request micropayment fee rates which allow for a lower fixed fee.

!!! Stripe does not allow for payments below 0.5 Euro. For this reason, if you enable the Stripe payment gateway, the minimum you can charge for a post is $0.75.

To configure Stripe within Micropayments Paywall Settings (accessible via "Paywall" on your WordPress dashboard):

1. Copy the Stripe Webhook URL.
1. Navigate to [Stripe.com](Stripe.com) and log into your account. This account will receive payments.
1. Search for "webhooks" in the developer section of the site and create a new webhook for the Stripe Webhook URL you copied over from WordPress. You can set the webhook to receive all events.
1. Again, on your Stripe dashboard, find your api keys in the developer setting. Set up a new key and get the public key and the secret.
1. Enter the public key (which is the API key) and the secret (API secret) into Micropayments Paywall Settings (acessible via "Paywall" on your WordPress admin dashboard).
1. Press "Save Settings" on Wordpress to confirm changes.

== Configuring Life-time Access (Premium Plug-in Only)==
1. Navigate to WordPress -> Settings -> Micropayments Paywall Settings (see 'Paywall' on the left menu)
1. Scroll down and tick the box to enable Lifetime access to posts
1. Set a price
1. Save Settings
1. Purchase the premium plugin [here](https://buy.stripe.com/5kA9Bm5tPdmb4ow6oy)

== Frequently Asked Questions ==

= How do users pay for the content? =

Users can pay for the content by clicking the "Purchase this post" button that appears when the content is paywalled. If a user is not logged in, they will be prompted to log in before they can pay. They can pay using their credit or debit card (Stripe).

= Can I set different prices for different posts? =

Yes, you can set different prices for each post by editing the "Product Price" field in the paywall meta box. If you enable Stripe, you cannot set the price below 75c.

= What payment methods are supported? =

Card payments via Stripe.

= What is the pricing? =

This plugin does not charge for payments made via credit/debit card. Fees are managed by Stripe. Upgrade to the premium plugin to be able to charge a flat fee for lifetime access [buy here](https://buy.stripe.com/5kA9Bm5tPdmb4ow6oy).

== Screenshots ==

1. Adding api keys.
2. Enabling the paywall and setting the price for a post.
3. Paywall displayed on a post.
4. Paying for access.
5. Configuring lifetime access (Premium Plugin).

== Changelog ==

= Open issues =

None.

= 4.0.2 =
Fix paywall payment button alignment

= 4.0.0 =

Enable Stripe onthe free plug-in.
Remove Trelis payment gateway (USDC), as it has been discontinued.

= 3.0.0 =

Allow for lifetime access to all posts via credit/debit card in the Premium plugin.

= 2.5 =

Cosmetic fixes

= 2.4.1 =

Release on WordPress.org

= 2.2.2 =

Fix bug on payment link generation

= 2.2.0 =

File clean-up

= 2.1.0 =

Micropayments Paywall Settings now accessible via "Paywall" on the WordPress admin dashboard

= 2.0.0 =

Add support for Stripe

= 1.0.0 =

Initial release.

== Upgrade Notice ==

There are no active upgrade notices.