=== Paksa Cart Recovery ===
Contributors: paksaitsolutions
Tags: abandoned-cart, woocommerce, cart-recovery, phone-number, pakistan
Requires at least: 6.0
Tested up to: 7.0
Requires PHP: 8.0
Stable tag: 1.4.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Phone-number-based abandoned cart recovery for WooCommerce. Built for markets where customers use mobile numbers and Cash on Delivery.

== Description ==

Paksa Cart Recovery is a powerful WooCommerce abandoned cart recovery plugin specifically designed for markets like Pakistan where customers primarily use **mobile phone numbers** and **Cash on Delivery (COD)** for online purchases.

Unlike traditional abandoned cart solutions that depend on email marketing, this plugin makes phone numbers the primary recovery channel.

= Key Features =

* **Phone Number Based Recovery** — Track customers by mobile number, not email
* **Abandoned Cart Tracking** — Real-time monitoring for guest and registered users
* **One-Click Cart Recovery** — Secure recovery links that restore carts instantly
* **WhatsApp Recovery** — One-click WhatsApp message with pre-filled recovery text
* **Recovery Coupons** — Auto-generate discount coupons to incentivize purchase
* **Exit Intent Popup** — Capture phone numbers before customers leave
* **Browser Push Notifications** — Send recovery alerts without email or phone
* **Cart Sharing** — Customers share carts via WhatsApp/link for someone else to pay
* **Personalized Landing Page** — Beautiful recovery page showing cart items
* **IP Geolocation** — Track abandonment by city/region
* **Email Recovery Campaigns** — 1-hour, 24-hour, and 72-hour automated emails
* **Webhook Integration** — Connect to Zapier, n8n, Make.com
* **Admin Alerts** — Get notified of high-value abandoned carts
* **Dashboard Widget** — Quick stats on WordPress admin home
* **Analytics & Reports** — Daily, weekly, monthly recovery reports
* **CSV Export** — Export all abandoned cart data

= Why Paksa Cart Recovery? =

In Pakistan and many developing eCommerce markets:

* Customers prefer phone numbers over email addresses
* Cash on Delivery is the dominant payment method
* Guest checkout is widely used
* Many customers never provide valid email addresses

This plugin solves the challenge by making mobile phone numbers the primary recovery channel.

= Perfect For =

* WooCommerce Stores
* Cash on Delivery Businesses
* Pakistani eCommerce Stores
* Fashion & Electronics Stores
* Any phone-first eCommerce market

== Installation ==

1. Upload the `paksa-cart-recovery` folder to `/wp-content/plugins/`
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Navigate to WooCommerce → Paksa Cart Recovery
4. Configure your settings and start recovering abandoned carts

== Frequently Asked Questions ==

= Does this plugin require any external service? =

No. It runs entirely within WordPress and WooCommerce without any cloud services, SaaS subscriptions, or third-party APIs.

= Does it work with guest checkout? =

Yes. It tracks guest users using session cookies and captures their phone number as soon as they enter it on the checkout page.

= What about WooCommerce Blocks checkout? =

Yes, fully compatible with both classic and block-based WooCommerce checkout.

= How does the WhatsApp recovery work? =

When you click the WhatsApp button next to an abandoned cart, it opens WhatsApp with a pre-filled message containing the customer's name, cart total, and a recovery link. You send it manually.

= Does it support HPOS (High-Performance Order Storage)? =

Yes, fully compatible with WooCommerce HPOS.

= How are recovery coupons generated? =

Unique single-use WooCommerce coupons are auto-generated with configurable discount type, amount, and expiry. They're included in WhatsApp messages and recovery emails automatically.

== Screenshots ==

1. Dashboard with recovery stats and analytics
2. Abandoned carts list with WhatsApp and recovery actions
3. Settings page with all configuration options
4. Recovery email received by customer
5. Exit intent popup capturing phone number
6. Personalized recovery landing page

== Changelog ==

= 1.4.0 =
* Added Browser Push Notifications
* Added Personalized Recovery Landing Page
* Added Cart Sharing (WhatsApp/Link)
* Added [paksa_share_cart] shortcode
* Auto-release workflow for GitHub

= 1.3.0 =
* Added WordPress Dashboard Widget
* Added Admin Email Alerts for high-value carts
* Added Webhook Integration (Zapier/n8n/Make)

= 1.2.0 =
* Added Recovery Coupon auto-generation
* Added Exit Intent Popup
* Added WooCommerce Order Notes on recovery
* Added IP Geolocation (city/region tracking)

= 1.1.0 =
* Added WhatsApp one-click recovery
* Added phone number normalization (Pakistan formats)
* Added WooCommerce Blocks checkout support
* Added stock validation on cart recovery
* Improved admin UI with date filters and bulk actions

= 1.0.0 =
* Initial release
* Phone number based cart tracking
* Abandoned cart management
* Recovery links
* Dashboard analytics
* Email recovery campaigns
* CSV export

== Upgrade Notice ==

= 1.4.0 =
New features: Push notifications, recovery landing page, and cart sharing. Deactivate and reactivate after update for database changes.
