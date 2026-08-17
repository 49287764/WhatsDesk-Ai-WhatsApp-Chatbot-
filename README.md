# 🤖 WhatsDesk — AI WhatsApp Business Chatbot (CodeIgniter 3)

> 📘 **Full technical reference** (architecture, database, engine internals, change
> history, known limitations): see [`PROJECT_REFERENCE.md`](PROJECT_REFERENCE.md).
>
> 🚀 **New here? Follow the step-by-step localhost setup guide** (XAMPP → database → ngrok → Meta → go live):
> see [`SETUP_GUIDE.md`](SETUP_GUIDE.md).

A production-ready WhatsApp chatbot for any business built on **Meta's WhatsApp Cloud API** + an
**OpenAI-compatible LLM with tool calling** + an **admin panel**.

```
WhatsApp customer ──> Meta Cloud API ──> your webhook (HTTPS) ──> store message in DB ──> reply 200 (fast)
                                                                        │
                                              cron worker (every minute) ▼
                                                    ┌───────────────────────────┐
                                                    │  conversation engine      │
                                                    │  (order state machine +   │
                                                    │   LLM with tools)         │
                                                    └─────────────┬─────────────┘
                                                                  ▼
                                        reply ──> Meta Cloud API ──> WhatsApp customer
```

**How replies stay fast:** the webhook *stores* the message, then immediately processes it inline
(AI call + reply, ~2–10 s). The cron worker (every minute) stays on as a **safety net**: messages are
locked while processing (no double replies), and anything that fails or times out is retried by
cron. An optional **fast poller** (`cron fast`) checks the queue every second for near-instant
replies on shared hosting — no VPS or queue server needed.

---

## 1. What you get

| Feature | Where |
|---|---|
| **Public marketing website** — landing (hero, real-bot live demo, FAQ), pricing, docs, contact | site root → `Site` controller + `views/site/` |
| **Live demo runs the real bot** in preview (dry-run) mode — no fake sends, no test orders | `Site::demo_chat` → `Bot_engine::preview_reply` |
| Contact form submissions stored → admin **Messages** inbox | `contact_messages` table + `admin/messages` |
| Public uptime endpoint for monitors | `/health` (`Health` controller) |
| **One-click setup checker** — PHP extensions, DB, config, live WhatsApp test, cron heartbeat | `/admin/setup` (`Setup` controller) |
| Answers questions with your business info (hours, address, delivery) | bot tools, admin **Settings** |
| Explains your products/services and prices (never hallucinates — data comes from your DB) | admin **Catalog** |
| Builds carts, computes totals, places orders, confirms with the customer | bot state machine |
| New-order + "human wanted" alerts to your WhatsApp | Settings → owner number |
| Curated FAQ answers (vegetarian, payment, allergies…) | admin **Knowledge** |
| Human takeover — reply manually from the panel, pause/resume the bot | admin **Chats** |
| Order management + status updates + notify customer | admin **Orders** |
| WhatsApp + AI credentials managed from the panel | admin **Settings** |

## 2. Honest costs & limitations (as of 2026)

**Costs — very low:**
- **WhatsApp Cloud API: free** for this use case. Since Nov 1, 2024 *service conversations*
  (customer messages + your bot replies within 24h) are **free and unlimited**. You only pay when
  *you* start a conversation outside the 24h window using an approved template (marketing/utility,
  ~$0.003–$0.025 per conversation depending on country/category).
- **AI**: OpenAI `gpt-4o-mini` ≈ $0.15 / 1M input tokens, $0.60 / 1M output. A small business doing a
  few hundred chats/month typically lands around **$1–10/month**. Cheaper options: DeepSeek
  (`deepseek-chat`, OpenAI-compatible) or Google Gemini (OpenAI-compatible endpoint).
- **Hosting**: any PHP shared host or small VPS with MySQL + cron + HTTPS — **$5–20/month**.
  Domain ~$12/year. Total realistic: **~$10–30/month all-in**.

**Limitations (be realistic):**
- **No native payments** via the Cloud API. Orders are confirmed in chat; you collect payment on
  delivery/pickup, or add a payment link later.
- **24-hour window**: free-form messages can only be sent when the customer messaged within the
  last 24h. Outside it, you need an approved *template* (paid, and approval required).
- **Number & verification**: you need a real phone number to register as the business number
  (new SIM or migrate an existing number). Business verification is needed to raise sending
  limits (unverified starts at ~250 conversations/day — fine for most small businesses).
- **Meta review**: the display name on your business profile must be approved by WhatsApp.
- **AI imperfection**: the bot is constrained by tools (it can only quote real menu data), but it
  is not perfect — that's why the human-handoff and admin takeover exist.

## 3. Is CodeIgniter 3 a good choice?

Honestly: **CI3 is a legacy framework (last release 2021) and I wouldn't start a new greenfield
project on it today.** However, this architecture is deliberately simple — a webhook that writes
to MySQL and a cron that calls an HTTP API — which CI3 handles perfectly well. Since you already
have this project, building on it is the pragmatic choice. The code avoids Composer dependencies
(plain cURL) so it runs on cheap shared hosting. **If you prefer, the same design ports cleanly
to Laravel/CI4 later** — the bot logic is self-contained in `application/libraries/Bot_engine.php`.

> **PHP version note:** this repo has CI **3.1.11**, which targets PHP 5.6–7.x. If your host runs
> PHP 8.x, replace the `system/` folder with **CI 3.1.13** (drop-in from codeigniter.com) to avoid
> deprecation warnings. PHP 7.4 or 8.0/8.1 recommended.

## 4. Requirements

- PHP 7.4+ (with `curl`, `json`, `mbstring`, `mysqli` extensions)
- MySQL 5.7+ / MariaDB
- HTTPS domain (required by Meta for webhooks — Let's Encrypt is free)
- Cron access (or a service like cron-job.org hitting the HTTP endpoint)

## 5. Step 1 — Meta WhatsApp Cloud API setup (~30 min)

1. Go to https://developers.facebook.com → **My Apps → Create App**.
2. Choose **Business** type → use case **"Connect with customers through WhatsApp"**.
3. In **API Setup**: add a phone number for your business (or use the free **test number** while
   developing). Save the **Phone number ID**.
4. Create a **permanent access token**:
   - Business Settings → **System users** → Add → Assign assets (your app + WhatsApp account,
     *Full control*) → **Generate token** with permissions
     `business_management`, `whatsapp_business_messaging`, `whatsapp_business_management`.
   - Copy the token (it starts with `EAAG…`). This is your **Access token**.
5. Meta App → **App settings → Basic**: copy the **App secret**.
6. Choose any random string as your **Webhook verify token** (e.g. `mybusiness-abc123`).
7. **Configure the webhook** (after deploying — Section 8):
   - Callback URL: `https://your-domain.com/whatsapp/webhook`
     (with `.htaccess` enabled the URL is clean; `…/index.php/whatsapp/webhook` works too)
   - Verify token: the string from step 6.
   - Subscribe to the **`messages`** webhook field.
8. Complete your **business profile** (display name, category, description) and request
   **display name approval**.

The four values from steps 3–6 go into the admin panel **Settings** page (Section 7).

## 6. Step 2 — Database

1. Create an empty MySQL database (e.g. `whatsdesk_db`).
2. Import the schema + seed data:

```bash
mysql -u USER -p whatsdesk_db < database/whatsapp_chatbot.sql
```

This is the **only** database file — it creates all tables plus seed data in one go: an unclaimed
   admin seed (no usable default password — you create your own account on first visit), a sample
   catalog and sample FAQ.

3. Edit `application/config/database.php` with your DB credentials.
4. Edit `application/config/config.php`:
   - `base_url` → your real domain (e.g. `https://your-domain.com/`)
   - `encryption_key` → any long random string (I put a placeholder — change it)

## 7. Step 3 — Admin panel & settings

Deploy (Section 8) then open **`https://your-domain.com/admin`** and click **Create your account**
(there are no default credentials — the unclaimed seed is replaced by your own username + password
on first run). Staff accounts can be added later from the **Accounts** page. Login locks for 15
minutes after 5 failed attempts.

Then fill in **Settings**:
- **WhatsApp Cloud API**: access token, phone number ID, app secret, verify token, owner WhatsApp
  number (for order alerts — the owner must message the bot number *once* so WhatsApp opens a
  24h window for notifications).
- **AI**: API key + model. Defaults: OpenAI, `gpt-4o-mini`. For DeepSeek choose provider
  `deepseek`, model `deepseek-chat`, base URL `https://api.deepseek.com/v1`. **Free option:**
  Groq (provider `groq`, model `openai/gpt-oss-20b`, base URL `https://api.groq.com/openai/v1`,
  key from console.groq.com — no card needed).
- **Business information**: name, address, hours, delivery info — the bot answers from these.
- **Security**: change the cron key from `change-me`.

Then populate **Catalog** (real products/services/prices) and extend **Knowledge** with your own FAQs.

> Values saved in Settings override the config files. The files in
> `application/config/whatsapp.php` / `ai.php` are only fallbacks.

## 8. Step 4 — Deploy & connect the webhook

1. Upload the project to your PHP host (all folders: `index.php`, `application/`, `system/`,
   `assets/`). Make sure `application/cache`, `application/logs`, `application/sessions` (if used)
   are writable.
2. Set the app to production: `CI_ENV=production` in your server config (`.htaccess`,
   `SetEnv CI_ENV production`, or in `index.php`). This hides error output.
3. Verify the site is HTTPS (Let's Encrypt on shared hosts/cPanel is usually one click).
4. Test the webhook URL in a browser — you should get `Verification failed` (403) because Meta
   hasn't called it yet. That's expected. (The repo ships a root `.htaccess` that strips
   `index.php` from URLs — clean URLs like `/whatsapp/webhook`; the old-style URLs keep working.
   On nginx, add `try_files $uri $uri/ /index.php?$query_string;` and point `index` at `index.php`.)
5. In the Meta app → **WhatsApp → Configuration**: set the callback URL and verify token from
   Section 5. Meta will call your URL with `hub_mode=subscribe` — your app echoes the challenge
   and shows **"Success"** if the verify token matches. Save.
6. In the same page, under **Webhook fields**, subscribe to **messages**.

## 9. Step 5 — Cron worker

The webhook replies instantly on its own (default), so cron is the **safety net**. Run every minute:

```bash
# CLI (best)
* * * * * php /path/to/project/index.php cron run >> /path/to/project/application/logs/cron.log 2>&1
```

or via HTTP (no server shell needed — use cron-job.org):

```
https://your-domain.com/cron/run?key=YOUR_CRON_KEY
```

**Near-instant replies on shared hosting** — replace `cron run` with the fast poller, which checks
the queue every second for 55 s per minute (single-instance guarded):

```bash
* * * * * php /path/to/project/index.php cron fast >> /path/to/project/application/logs/cron.log 2>&1
```

The key is set in admin **Settings → Security → cron key**. Every run writes a heartbeat
(`last_cron_run`) — the dashboard's green **"Bot worker: live"** pill turns red if the cron stops,
so you always know the bot is actually working. Inline replies can be turned off in
**Settings → Bot behavior → Reply instantly in the webhook** (then replies come only from cron,
up to ~1 min delay).

## 10. Step 6 — Test end to end

1. In the Meta dashboard, use the **test number** and your personal WhatsApp to send a test
   message (the test number lets you chat with yourself while developing).
2. Try these conversations (the test number lets you chat with yourself while developing):
   - "Hi" → greeting
   - "What are your opening hours?" / "Where are you located?"
   - "What do you offer?" / "How much is the chicken biryani?"
   - "I want 2 chicken biryani and 1 soft drink" → cart summary
   - "Yes" → name → address → order placed ✅ (check admin Orders + owner notification)
   - "I want to talk to a human" → staff alert, reply from admin **Chats**
3. Check `application/logs/` (CI error log) if anything misbehaves.

## 11. How the bot works (flows)

- **Open questions** (info, products, prices): LLM calls tools (`get_business_info`, `get_menu`,
  `get_item`, `search_knowledge`) — answers are built from YOUR database, never invented.
- **Ordering**: `cart_add` / `cart_remove` / `cart_clear` build the cart; `request_checkout`
  shows the summary and switches to a **deterministic state machine**:
  `awaiting_confirm` → (YES) → `awaiting_name` → `awaiting_address` → order placed. Confirmation
  is handled in code, not by the AI, so "yes" always means yes.
- **Human handoff**: customer types "human" → bot pauses, owner is notified, admin replies from
  Chats and can re-enable the bot.
- **Messages the bot can't parse** → falls back to the fallback message.

## 12. Security checklist (already built in — verify on deploy)

- ✅ Webhook **signature verification** (HMAC SHA-256 with your app secret) — rejects forged payloads
- ✅ Webhook GET verification token
- ✅ CSRF protection enabled app-wide; webhook URI excluded
- ✅ bcrypt password hashing (seed password auto-upgrades on first login)
- ✅ XSS output escaping everywhere (`html_escape`)
- ✅ SQL via Query Builder (parameterized)
- ✅ Cron endpoint protected by a secret key
- ☐ Change `cron_key` and `encryption_key` immediately (admin accounts already use your own passwords)
- ☐ Use `CI_ENV=production`
- ☐ Keep PHP and MySQL updated

## 13. Troubleshooting

| Symptom | Fix |
|---|---|
| Webhook shows "Verification failed" | Verify token in Meta ≠ value in Settings/config |
| Webhook 403 on POST | App secret wrong; or Meta still verifying |
| Messages arrive but no replies | Check the dashboard pill — "Bot worker: not running" means cron is down; check `application/logs`; is the bot paused in Conversations? |
| Replies say fallback message | AI key missing/invalid, or provider/model wrong in Settings |
| Owner gets no order alerts | Owner must message the bot number once (opens 24h window); check `owner_wa_id` format |
| "Message failed to send" in Orders / test message | 24h window closed or credentials wrong — customer must message first, or use an approved template; use Settings → "Send test message" to verify the API config |
| Blank page / deprecation warnings | PHP 8.x with CI 3.1.11 — upgrade `system/` to CI 3.1.13 |

## 13b. Public website & production extras

- The site root is a **marketing site** (`Site` controller) with: landing (hero with animated WhatsApp chat mock, animated stat counters, features, **live demo that runs the real bot** in dry-run mode, pricing teaser, FAQ, CTA, floating WhatsApp button), plus `/site/pricing` (plans + comparison table), `/site/docs` (full setup guide with sidebar scrollspy) and `/site/contact` (working form → admin Messages inbox). Shared shell: `views/site/header.php`, `views/site/footer.php`, `assets/css/site.css`, `assets/js/site.js`.
- `robots.txt` blocks crawlers from `/admin`, `/whatsapp`, `/cron`; `favicon.svg` is the site icon.
- `/health` returns `{"status":"ok","db":true}` for uptime monitors (UptimeRobot etc.).
- **Pricing is free** — the site advertises a no-subscription model (the software is free; you only
  pay your own AI key and hosting). Adjust `views/site/pricing.php` if that ever changes.
- **`/admin/setup`** runs a one-click setup check (PHP version/extensions, writable dirs, DB
  connection + tables, base URL/encryption key/ENVIRONMENT, admin password hash, cron key,
  WhatsApp credentials with a live Graph API test, AI key, cron heartbeat, owner number).
- **Housekeeping:** the cron worker purges landing-page demo data older than 30 days daily
  (03:00) so the public live demo never grows the database unboundedly.
- **Admin UI** is powered by `assets/css/admin.css` + `assets/js/admin.js`: colorful per-card
  stat accents, toast notifications, copy-to-clipboard buttons, password show/hide toggles,
  reveal-on-scroll, animated counters, revenue + status charts, a first-run
  welcome modal, and **branded error pages** (all respecting `prefers-reduced-motion`).
- **Business info auto-fill:** upload (or paste) one document about your business and the
  panel extracts the business name, hours, address, phone and delivery info from it — no
  more "Your Business / 123 Main Street" demo values, and the bot greets customers with
  your real name.
- **Language-aware offline brain:** the bot detects English, Urdu (Arabic script or Roman
  Urdu) and Hindi (Devanagari) and replies in the customer's language.
- **Local currency support:** a `currency_symbol` setting (e.g. `Rs.`, `€`) is shown before
  every price in the menu, cart, orders, reports and CSV export — set it in Settings →
  Business details. Whole amounts print naturally (`Rs.450`, `€12`) while fractional prices
  keep two decimals (`$29.99`).
- **The bot works even without an AI key**: a built-in deterministic "offline brain"
  answers the menu, prices, hours, address, delivery info, FAQ lookups and even takes a
  **full order** (add to cart → checkout → confirm → name → address → order placed) with
  zero AI configured. It also replies in Urdu/Hindi when customers write in Roman Urdu
  ("assalamualaikum menu dikhao" gets a bilingual answer). When you add your AI key, the
  bot becomes conversational and can handle anything else — and if the AI provider ever
  goes down, it automatically falls back to the offline brain instead of failing.
- **Settings** starts with a **connection status hub** — five live chips (AI provider,
  WhatsApp Cloud API, Webhook, Cron worker, Owner notifications) that turn green as you
  configure each piece — and has three connection tests: **Test AI connection** (sends one
  prompt to the AI provider), **Check WhatsApp connection** (live Graph API check of the
  token + phone number ID — no message sent) and **Send WhatsApp test message** (end-to-end
  delivery to your own number). The AI provider dropdown auto-fills model + base URL for
  OpenAI/DeepSeek/custom.
- **Orders are fully manageable from the list**: colored status pills with icons, a summary
  strip (value / awaiting / delivered), a quick inline status changer per row, and **bulk
  actions** (select rows → move many orders at once). The order detail page adds an
  **animated status stepper** (placed → confirmed → preparing → ready → delivered) plus a
  confetti celebration when you mark an order delivered.
- **Auto-notify customers on status change** (opt-in in Settings → Bot behavior): move an
  order to a new status and the customer is messaged automatically — free-form inside the
  24h window, approved template outside it.
- **Sales report** (`/admin/reports`): pick any date range (Last 7 days / This month /
  This year shortcuts), see orders, revenue and best sellers, then **Print** or **export
  CSV** for Excel/Sheets.

## 14. Sensible next steps

- **Templates (partly done)**: approve an `order_status` template in the Meta dashboard, put its
  name in Settings → `Order status template`, and the order page gets a template button that works
  outside the 24h window. Next: automated template sends on status change.
- Add a **payment link** step (Stripe/PayPal) at checkout.
- Integrate your **POS** — orders already have a clean `orders` table.
- Move to a proper queue (Redis + worker) if you grow past a few thousand messages/day.
- Port the same logic to Laravel/CI4 if you outgrow CI3 — `Bot_engine.php` is framework-light.
