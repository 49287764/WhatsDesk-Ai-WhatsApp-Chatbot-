# 🚀 WhatsDesk — Complete Localhost Setup Guide

**Everything you need to run the app on your own PC, step by step.**
Follow the steps in order. Each step says exactly what to click and what URL to open.

> ⏱️ Total time: about 30–45 minutes (most of it is waiting for Meta to verify things).
> You only do this ONCE per computer.

---

## 📌 What you need before starting

| Item | Where to get it | Free? |
|---|---|---|
| **XAMPP** | https://www.apachefriends.org/download.html (PHP 8.0+ version) | ✅ Free |
| **ngrok** | https://ngrok.com/download | ✅ Free account |
| **Meta (Facebook) account** | https://developers.facebook.com | ✅ Free |
| **AI key (Groq)** | https://console.groq.com → API Keys → Create | ✅ Free tier |
| **A WhatsApp number** | Any real mobile number (yours works for testing) | ✅ |

---

# ✅ STEP 1 — Install XAMPP

1. Go to **https://www.apachefriends.org/download.html**
2. Download the **Windows** version (PHP 8.0 or newer)
3. Double-click the installer → **Next** → **Next** → **Install**
4. ⚠️ **Important:** install it in `C:\xampp_new` (or the default `C:\xampp` is fine too)
5. When the installer asks about starting services, click **Finish**

> 💡 **Where it goes:** XAMPP installs into a folder like `C:\xampp_new`. Your website files go inside `C:\xampp_new\htdocs`.

---

# ✅ STEP 2 — Put the project folder in htdocs

1. Open the folder where you saved the project (the one named **`whatsapp_chatbot`**)
2. **Copy the whole folder** (don't copy the zip, extract it first)
3. Paste it into:
   ```
   C:\xampp_new\htdocs\whatsapp_chatbot
   ```
   (if you used default XAMPP: `C:\xampp\htdocs\whatsapp_chatbot`)

**Your final path must look like this:**
```
C:\xampp_new\htdocs\whatsapp_chatbot\
    ├── index.php
    ├── application\
    ├── assets\
    ├── database\  ← contains whatsapp_chatbot.sql (THE one database file)
    └── README.md
```

> ⚠️ Make sure there is **not** a nested folder like `htdocs\whatsapp_chatbot\whatsapp_chatbot\` — that causes errors.

---

# ✅ STEP 3 — Start Apache and MySQL

1. Open the **XAMPP Control Panel** (search "XAMPP" in the Start menu)
2. Click the **Start** button next to **Apache** (wait until it turns GREEN)
3. Click the **Start** button next to **MySQL** (wait until it turns GREEN)
4. If a Windows Firewall popup appears → click **Allow access** (for both)

**Check it works:**
- Open your browser → go to: **http://localhost** → you should see the XAMPP welcome page

> 🔴 **Problem?** Port 80 busy? In XAMPP Control Panel → Apache → **Config → httpd.conf** → find `Listen 80` → change to `Listen 8080` → save → restart Apache. Then use `http://localhost:8080` everywhere below.

---

# ✅ STEP 4 — Create the database + import (ONE file)

1. Open your browser → go to: **http://localhost/phpmyadmin**
2. Click **New** (left sidebar) → Database name: **`bizbot`** → Charset: `utf8mb4` → **Create**
3. Click the **`bizbot`** database (left sidebar — it selects it)
4. Click the **Import** tab (top menu)
5. Click **Choose File** → go to your project folder → **`database\whatsapp_chatbot.sql`** → select it
6. Scroll down → click **Import** (bottom)
7. Wait for the green message: **"Import has been successfully finished"**

> ✅ **That's it — ONE file, everything is created automatically:** all tables, sample menu, sample FAQs, and the admin account (which you'll claim in Step 8).
>
> 💡 There is **only one database file** now — `database/whatsapp_chatbot.sql`. No updates file needed.

**Verify:** in phpMyAdmin, click `bizbot` → you should see 10 tables: `admin_users`, `settings`, `menu_categories`, `menu_items`, `knowledge`, `customers`, `conversations`, `messages`, `orders`, `contact_messages`.

---

# ✅ STEP 5 — Check the database connection setting

The app already points to `bizbot` with XAMPP defaults — **usually nothing to change**. But if your MySQL has a password, edit this file:

```
C:\xampp_new\htdocs\whatsapp_chatbot\application\config\database.php
```

Find these lines and match your XAMPP:
```php
'hostname' => 'localhost',
'username' => 'root',
'password' => '',        // XAMPP default has NO password
'database' => 'bizbot',
```

---

# ✅ STEP 6 — Open your app for the first time

Open your browser and go to:

```
http://localhost/whatsapp_chatbot/
```

You should see the **WhatsDesk landing page** with the demo chat.
Then open the admin panel:

```
http://localhost/whatsapp_chatbot/admin
```

Click **"Create your account"** → make your **username + password** (this is YOUR admin login — write it down!).

> ✅ After this you're logged in. The app now works locally — menu, orders, dashboard, everything.

---

# ✅ STEP 7 — Install ngrok (so WhatsApp can reach your PC)

WhatsApp (Meta) needs a **public HTTPS URL** to send messages to your app. ngrok creates that tunnel to your localhost.

1. Create a free account at: **https://ngrok.com/signup**
2. Download Windows: **https://ngrok.com/download**
3. Unzip it → put **`ngrok.exe`** in a simple folder like `C:\ngrok`
4. Open **Command Prompt** (search "cmd" → right-click → Run as administrator)
5. Add your authtoken (get it from https://dashboard.ngrok.com/get-started/your-authtoken):
   ```
   ngrok config add-authtoken YOUR_AUTHTOKEN
   ```
6. Start the tunnel to your app:
   ```
   ngrok http 80
   ```
   (if you changed Apache to port 8080 in Step 3, use: `ngrok http 8080`)
7. You'll see a screen with a **forwarding URL** that looks like:
   ```
   https://luckless-surgical-candle.ngrok-free.dev
   ```
   **COPY THIS URL — you'll need it in Step 9.** (It's random; keep this window open!)

> ⚠️ **Keep the ngrok window open** — if you close it, the tunnel dies. Restart it anytime with the same command.

---

# ✅ STEP 8 — Get your AI key (Groq — free, no card)

1. Go to: **https://console.groq.com** → sign up / log in
2. Click **API Keys** (left menu) → **Create API Key**
3. Give it a name (e.g. `whatsdesk`) → **Create** → **Copy the key** (starts with `gsk_...` — shown only once!)

> 💡 You'll paste this key into the app's Settings in Step 11.

---

# ✅ STEP 9 — Set up Meta (WhatsApp Cloud API) — the big one

### 9.1 Create the app
1. Go to: **https://developers.facebook.com**
2. Log in → click **My Apps** (top right) → **Create App**
3. Use case: **"Other"** → **Next**
4. App type: **Business** → **Next**
5. App name: `WhatsDesk` → App email: your email → **Create app**
6. Enter your Facebook password when asked

### 9.2 Add the WhatsApp product
1. In your app dashboard, find **"Add products to your app"** → click **Set up** next to **WhatsApp**
2. Scroll down to **"Getting started"** → you'll see a **temporary access token** and a **phone number ID** already generated

### 9.3 Add your real phone number
1. In **WhatsApp → API Setup**, find **"Add a phone number"** → click **Add phone number**
2. Country: **Pakistan (+92)** → enter your **real mobile number**
3. Meta sends a **6-digit code** by SMS/call → enter it → your number is now registered

### 9.4 Get the values you'll paste into the app

| Value | Where to find it in Meta | Example |
|---|---|---|
| **Access token** | WhatsApp → API Setup → top box → **Generate token** (re-enter password) | `EAAV...` (long!) |
| **Phone number ID** | WhatsApp → API Setup → under "From" | `1302390639617328` |
| **App secret** | App dashboard → **App settings → Basic** → App secret → **Show** → Copy | `b8319095...` |
| **Verify token** | Make up any random string yourself (e.g. `mybot-verify-123`) | `bizbot_verify_5995a09c` |

> ⚠️ **Permanent token (recommended, never expires):** Instead of the temporary token, create a System User token:
> 1. Open **https://business.facebook.com/settings/system-users**
> 2. **Add** → name `WhatsDesk` → role **Admin** → **Create**
> 3. Click the user → **Add assets** → **Apps** → select your app → **Full control**
> 4. **Generate new token** → your app → expiry **Never** → permissions `whatsapp_business_messaging` + `whatsapp_business_management` → **Generate** → **Copy**
> (The "How to create a permanent token" guide is also inside your app: Settings → WhatsApp.)

### 9.5 Configure the webhook (the critical part)
1. In **WhatsApp → Configuration** (or App dashboard → **WhatsApp → Webhook**):
2. **Callback URL:** enter your ngrok URL + the webhook path:
   ```
   https://luckless-surgical-candle.ngrok-free.dev/whatsapp_chatbot/whatsapp/webhook
   ```
   (replace the ngrok part with YOUR forwarding URL from Step 7 — but keep `/whatsapp_chatbot/whatsapp/webhook` at the end)
3. **Verify token:** enter your verify token (the random string you made in 9.4)
4. Click **Verify and save** → should show ✅ green check "Webhook is verified"
5. Under **Webhook fields**, click **Manage** → subscribe to **`messages`** → **Save changes**

> ✅ If verification fails: make sure ngrok is still running, Apache is running, and the URL exactly matches. Test it in your browser first: `https://YOUR-NGROK-URL/whatsapp_chatbot/whatsapp/webhook` → it should show an error page (not "site not found").

---

# ✅ STEP 10 — Register the webhook with Meta (subscribe)

After the webhook verifies, one more click in Meta:
1. Still in **WhatsApp → Configuration** (or App → **Webhook** → **WhatsApp**):
2. Under the **"Webhook fields"** section → click **Manage** → **Subscribe** to the **`messages`** field
3. Click **Save changes**

> The dashboard in your app will show **"Webhook: registered"** once this works.

---

# ✅ STEP 11 — Put everything into your app's Settings

Open your admin panel:

```
http://localhost/whatsapp_chatbot/admin
```

Go to **Settings** and fill in (each field has a **?** button explaining it):

### WhatsApp section
| Field | Paste |
|---|---|
| **Access token** | the `EAAV...` token from 9.4 |
| **Phone number ID** | the number from 9.4 |
| **App secret** | the secret from 9.4 |
| **Verify token** | your verify token from 9.4 |
| **Owner WhatsApp number** | your full number with country code, no + or spaces → `923493498980` |

Click **Save** → then click **"Check WhatsApp connection"** → should turn **GREEN** ✅

### AI section
| Field | Paste |
|---|---|
| **Provider** | `Groq (free tier)` |
| **Model** | `openai/gpt-oss-20b` (pre-filled when you pick Groq) |
| **API key** | your `gsk_...` key from Step 8 |

Click **Save** → then click **"Test AI connection"** → should say **"AI test passed"** ✅

### Business info
Go to **Business info** → paste everything about your business (menu, prices, hours, policies) — the bot answers customers from this.

---

# ✅ STEP 12 — Test it end-to-end 🎉

1. **Open the demo on your site:** `http://localhost/whatsapp_chatbot` → click the chat widget → try: *"What's on the menu?"* → the bot replies with YOUR menu ✅
2. **Real WhatsApp test:**
   - Open WhatsApp on your phone
   - Message **your own business number** (the one you registered in Meta): *"Hi"*
   - The bot replies within a few seconds ✅
3. **Place a test order:** *"I want 1 chicken biryani"* → follow the flow → confirm → check **Orders** in your admin panel ✅

---

# 📍 Your URLs (bookmark these)

| Page | URL |
|---|---|
| Your website | `http://localhost/whatsapp_chatbot/` |
| Admin login | `http://localhost/whatsapp_chatbot/admin` |
| phpMyAdmin | `http://localhost/phpmyadmin` |
| Webhook (for Meta) | `https://YOUR-NGROK-URL/whatsapp_chatbot/whatsapp/webhook` |
| Cron (optional safety net) | `http://localhost/whatsapp_chatbot/cron/run?key=cron_6a3915136353` |
| Health check | `http://localhost/whatsapp_chatbot/health` |

---

# 🔁 Daily startup routine (30 seconds)

1. Open **XAMPP Control Panel** → Start **Apache** + **MySQL**
2. Open Command Prompt → `ngrok http 80` (if not already running)
3. Done — your bot is online. (Your PC must stay on for it to work.)

> ⚠️ **Important:** your ngrok URL **changes every time** you restart ngrok (free plan). If it changes, you must update the **Callback URL in Meta** (Step 9.5) to the new URL. Consider the paid static domain to avoid this.

---

# ⏰ BONUS — Set up the cron worker (hidden, no window)

> The cron is the **optional safety net**: the webhook already replies to customers instantly (~1 second). The cron just catches any message that somehow didn't get a reply (AI timeout, glitch, etc.) and answers it within a minute — so **zero messages ever go unanswered** while your PC is on. You can skip this and the bot still works.

This setup runs the cron **every minute with no visible window** — no cmd window flashing on screen. It needs to be done **once per PC** (do it on your PC, and again on your friend's PC if they host their own copy).

## Step 1 — Create the hidden launcher file

Open **Notepad** and paste this (**change the paths to match YOUR XAMPP and project folder**):

```vbs
Set sh = CreateObject("WScript.Shell")
sh.Run "C:\xampp\php\php.exe C:\xampp\htdocs\whatsapp_chatbot\index.php cron run", 0, False
```

> ⚠️ **Important:** paths differ between PCs:
> - XAMPP is usually at `C:\xampp\` (this guide's setup uses `C:\xampp_new\`)
> - The project folder may not be named `whatsapp_chatbot`
> - Both paths in the line above must match your actual folders

Then **File → Save As** → name it `whatsdesk_cron_hidden.vbs` → **Save as type: All files** → save it somewhere safe, e.g. `C:\xampp\htdocs\whatsapp_chatbot\application\cache\` (anywhere works, even the Desktop).

## Step 2 — Create the scheduled task (one command)

Open **CMD** (or PowerShell) and run (**adjust the path** inside `wscript.exe "..."` to where you saved the .vbs file):

```bat
schtasks /Create /TN "WhatsDeskCron" /TR "wscript.exe \"C:\xampp\htdocs\whatsapp_chatbot\application\cache\whatsdesk_cron_hidden.vbs\"" /SC MINUTE /MO 1 /F
```

You should see: **`SUCCESS: The scheduled task "WhatsDeskCron" has successfully been created.`**

## Step 3 — Test it

```bat
schtasks /Run /TN "WhatsDeskCron"
```

Wait ~5 seconds, then refresh your admin **dashboard** → the top bar should show **"Bot worker: live"** ✅ (the heartbeat updates every minute).

## Manage it later

| What | Command |
|---|---|
| Check if it's running | `schtasks /Query /TN "WhatsDeskCron"` |
| Run it once manually | `schtasks /Run /TN "WhatsDeskCron"` |
| Stop it (but keep it) | `schtasks /End /TN "WhatsDeskCron"` |
| Start it again | `schtasks /Run /TN "WhatsDeskCron"` |
| Remove it completely | `schtasks /Delete /TN "WhatsDeskCron" /F` |

Or use the **Task Scheduler app** (Windows search → "Task Scheduler") → find **WhatsDeskCron** → right-click → Run / End / Disable.

> 💡 If you move your project folder later, update the path inside `whatsdesk_cron_hidden.vbs` and re-create the task.
> ⚠️ Your PC must be **on** (Apache + MySQL running) for the cron to work — it's a localhost setup.

---

# ❓ Troubleshooting

| Problem | Fix |
|---|---|
| **"No direct script access allowed"** | You opened the wrong folder — must go through `http://localhost/whatsapp_chatbot/`, not `file:///` |
| **Database error on the site** | Re-check Step 4 (import) + Step 5 (credentials) — database must be named `bizbot` |
| **Webhook won't verify in Meta** | ngrok window closed? Apache stopped? URL mismatch? Check: `https://YOUR-NGROK-URL/whatsapp_chatbot/whatsapp/webhook` opens (error text is fine) |
| **WhatsApp check fails in Settings** | Token expired (temporary ones die in ~24h) → generate a new one (Step 9.4) — or use the permanent System User token |
| **"Bot worker: off (optional)"** | That's fine — the optional cron safety net isn't running. Your bot still replies instantly through the webhook. To enable it, follow the **BONUS — Set up the cron worker** section above. |
| **Can't reach localhost from phone** | That's normal — the phone talks to your app through the **ngrok URL**, not localhost |
| **Port 80 in use** | Change Apache to 8080 (Step 3) and use `ngrok http 8080` |

---

# ✅ Checklist — you're done when...

- [ ] `http://localhost/whatsapp_chatbot/` shows the landing page
- [ ] `http://localhost/whatsapp_chatbot/admin` lets you log in with YOUR account
- [ ] Settings → **Check WhatsApp connection** = GREEN
- [ ] Settings → **Test AI connection** = passed
- [ ] Meta shows the webhook as **verified**
- [ ] You messaged your number on WhatsApp and the bot **replied**
- [ ] A test order appears in **Orders**

**All checked? Congratulations — your restaurant's AI WhatsApp assistant is LIVE! 🎉🚀**
