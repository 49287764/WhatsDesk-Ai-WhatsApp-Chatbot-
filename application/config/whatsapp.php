<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/*
|--------------------------------------------------------------------------
| WhatsApp Cloud API Configuration
|--------------------------------------------------------------------------
| These are FALLBACK values. Once you save real values in the admin panel
| (Settings page) they are stored in the `settings` table and take priority.
|
| To get these values, follow the Meta setup steps in README.md:
|   wa_token           -> System user access token (permanent)
|   wa_phone_number_id -> From WhatsApp > API Setup in your Meta app
|   wa_app_secret      -> From Meta app > App settings > Basic
|   wa_verify_token    -> Any secret string you choose (webhook verification)
|--------------------------------------------------------------------------
*/
$config['wa_token']             = '';
$config['wa_phone_number_id']   = '';
$config['wa_app_secret']        = '';
$config['wa_verify_token']      = '';
$config['wa_graph_version']     = 'v25.0';

/*
| Graph API base URL. Leave empty to use https://graph.facebook.com.
| Only override this for proxies / regional gateways / local testing.
*/
$config['wa_graph_url']         = '';

/*
| Currency symbol shown in front of prices (menu, cart, orders, reports).
| Change it in the admin panel (Settings -> Business details) for your
| local currency, e.g. 'Rs.' for Pakistani rupees.
*/
$config['currency_symbol']      = '$';

/*
| Owner's WhatsApp number (with country code, no +, e.g. 15551234567).
| New-order and human-handoff notifications are sent here. The owner must
| message the bot number once first so WhatsApp opens a 24h window.
*/
$config['wa_owner_wa_id']       = '';

/*
| Auto-notify the customer when the owner changes an order's status
| (0 = off, 1 = on). Uses wa_status_message inside the 24h window and
| wa_order_template outside it. Overridable from Settings > Bot behavior.
*/
$config['wa_notify_status']      = '0';
$config['wa_status_message']     = 'Your order #{order_id} is now {status}.';
