<div class="page-hero">
  <div class="container-lg position-relative">
    <div class="sec-kicker mb-3">Docs</div>
    <h1 class="mb-2">Set up the bot</h1>
    <p class="sub mb-0">The complete, click-by-click guide — from Meta’s dashboard to your first live customer conversation.</p>
    <div class="callout mt-3">🚀 <strong>Prefer a single easy file?</strong> Download <a href="<?= base_url('SETUP_GUIDE.md') ?>" target="_blank" rel="noopener">SETUP_GUIDE.md</a> — the full localhost setup (XAMPP → database → ngrok → Meta → go live) in one document with all the URLs.</div>
  </div>
</div>

<section style="padding-top:2rem;">
  <div class="container-lg">
    <div class="row g-5">
      <div class="col-lg-3 d-none d-lg-block">
        <nav class="docs-nav">
          <a href="#meta">1 · Meta WhatsApp setup</a>
          <a href="#database">2 · Database</a>
          <a href="#config">3 · Configuration</a>
          <a href="#deploy">4 · Deploy &amp; webhook</a>
          <a href="#cron">5 · Cron worker</a>
          <a href="#test">6 · Test end to end</a>
          <a href="#security">7 · Security checklist</a>
          <a href="#troubleshoot">Troubleshooting</a>
        </nav>
      </div>

      <div class="col-lg-9 docs-body">

        <h2 id="meta">1 · Meta WhatsApp setup — click by click (~30 min)</h2>
        <div class="step-block"><span class="n">1</span><div><strong>Create the app.</strong> Go to <a href="https://developers.facebook.com" target="_blank" rel="noopener">developers.facebook.com</a> → <strong>My Apps → Create App</strong> → type <strong>Business</strong> → use case <strong>“Connect with customers through WhatsApp”</strong> → name it and create it.</div></div>
        <div class="step-block"><span class="n">2</span><div><strong>Get the test number.</strong> On the <strong>WhatsApp → API Setup</strong> page you’ll find a free <strong>test number</strong>. Copy the <strong>Phone number ID</strong> from the same page.</div></div>
        <div class="step-block"><span class="n">3</span><div><strong>Create the access token.</strong> Open <a href="https://business.facebook.com" target="_blank" rel="noopener">business.facebook.com</a> → <strong>Settings → System users → Add</strong> → name it <code>bot-server</code>, role Admin → <strong>Assign assets</strong> (your app + WhatsApp account, Full control) → <strong>Generate token</strong> with permissions <code>business_management</code>, <code>whatsapp_business_messaging</code>, <code>whatsapp_business_management</code>. Copy it — it’s shown only once.</div></div>
        <div class="step-block"><span class="n">4</span><div><strong>Get the App secret.</strong> Back in your Meta app: <strong>App settings → Basic</strong> → copy the <strong>App secret</strong>.</div></div>
        <div class="step-block"><span class="n">5</span><div><strong>Pick a verify token.</strong> Any random string you invent (e.g. <code>mybusiness-9f3k2</code>). It proves to Meta that the webhook belongs to you.</div></div>
        <div class="step-block"><span class="n">6</span><div><strong>Connect the webhook</strong> (after deploying — section 4). In your app: <strong>WhatsApp → Configuration</strong>. Callback URL <code>https://your-domain.com/whatsapp/webhook</code>, verify token from step 5 → <strong>Verify and save</strong>. Then under <strong>Webhook fields → Manage</strong>, subscribe to <strong>messages</strong>.</div></div>
        <div class="callout">Paste the four values (token, phone number ID, app secret, verify token) into the admin <strong>Settings</strong> page, then press <strong>Send test message</strong>. If your phone buzzes, you’re connected.</div>

        <h2 id="database">2 · Database</h2>
        <p>Create an empty MySQL database and import the schema + seed data:</p>
<pre><code>mysql -u USER -p whatsdesk_db &lt; database/whatsapp_chatbot.sql</code></pre>
        <p>This creates all tables plus starter content: an <strong>unclaimed seed account</strong> (no usable password — you claim it by creating your own account), a sample catalog and sample FAQ entries. <code>database/whatsapp_chatbot.sql</code> is the <strong>only</strong> database file you need — import it once and everything is created.</p>

        <h2 id="config">3 · Configuration</h2>
        <ul>
          <li><code>application/config/database.php</code> — your database credentials.</li>
          <li><code>application/config/config.php</code> — set <code>base_url</code> to your real domain and change <code>encryption_key</code> to a long random string.</li>
          <li>Open <code>/admin</code> and click <strong>Create your account</strong> — there are no default credentials. Once your account exists, the sidebar's <strong>Setup guide</strong> walks you through all 7 steps in order.</li>
          <li>Fill in <strong>Settings</strong>: WhatsApp credentials, owner number (for alerts), AI key + model (free option: <strong>Groq</strong> — no card; or OpenAI <code>gpt-4o-mini</code> / DeepSeek <code>deepseek-chat</code>), and your business info. Use the <strong>Test AI connection</strong> and <strong>Check WhatsApp connection</strong> buttons to verify each piece before going live — the 5 connection chips at the top of Settings turn green as you complete each one.</li>
          <li><strong>Business info</strong> — paste (or upload) one document about your business. On save, the business name, hours, address, phone and delivery info are <strong>auto-filled</strong> from it, so the bot greets customers with your real details. (Demo placeholders like “Your Business” are replaced automatically.)</li>
          <li>Add your real products or services to <strong>Catalog</strong> so customers can order them, and extend <strong>Knowledge</strong> with your own FAQs.</li>
          <li>Customers writing in <strong>Urdu (script or Roman) or Hindi (Devanagari)</strong> get replies in their own language — even without an AI key.</li>
        </ul>

        <h2 id="deploy">4 · Deploy &amp; connect the webhook</h2>
        <ul>
          <li>Upload the project to a PHP host (PHP 7.4+ with <code>curl</code>, <code>json</code>, <code>mbstring</code>, <code>mysqli</code>; MySQL 5.7+). The repo ships a root <code>.htaccess</code> for clean URLs.</li>
          <li>Ensure <code>application/cache</code> and <code>application/logs</code> are writable.</li>
          <li>Set the app to production: <code>CI_ENV=production</code> in your server config.</li>
          <li>HTTPS is required (Let’s Encrypt is free on most hosts).</li>
          <li>Then do Meta setup step 6 above to connect the webhook.</li>
        </ul>
        <div class="callout">On nginx, add <code>try_files $uri $uri/ /index.php?$query_string;</code>. On PHP 8.x with CodeIgniter 3.1.11, upgrade the <code>system/</code> folder to 3.1.13 to avoid deprecation warnings.</div>

        <h2 id="cron">5 · Cron worker (optional)</h2>
        <p>The webhook replies <strong>instantly on its own</strong> (inline mode, default) — so the cron
        worker is <strong>not required</strong>: your bot works without it. It's only a
        <strong>safety net</strong> that retries anything which fails. If you want it, run it every minute:</p>
<pre><code>* * * * * php /path/to/project/index.php cron run &gt;&gt; /path/to/project/application/logs/cron.log 2&gt;&amp;1</code></pre>
        <p>Or use an HTTP service like cron-job.org:</p>
<pre><code>https://your-domain.com/cron/run?key=YOUR_CRON_KEY</code></pre>
        <p><strong>Near-instant replies on shared hosting:</strong> swap <code>cron run</code> for the fast
        poller, which checks the queue every second (single-instance guarded):</p>
<pre><code>* * * * * php /path/to/project/index.php cron fast &gt;&gt; /path/to/project/application/logs/cron.log 2&gt;&amp;1</code></pre>
        <p>The key is set in admin <strong>Settings → Security → cron key</strong>. The dashboard’s green
        <strong>“Bot worker: live”</strong> pill turns red if the cron stops. Inline replies can be
        toggled in <strong>Settings → Bot behavior</strong>.</p>

        <h2 id="test">6 · Test end to end</h2>
        <ul>
          <li>Use Meta’s free <strong>test number</strong> and chat with yourself while developing.</li>
          <li>Try: “Hi” → greeting · “What are your opening hours?” · “What do you offer?” · “How much is the chicken biryani?” · “I want 1 chicken biryani and 2 drinks” → YES → name → address → order placed ✅</li>
          <li>Check the admin panel: <strong>Orders</strong> shows the order, <strong>Conversations</strong> shows the thread, and your owner number gets an alert (the owner must message the bot once first so WhatsApp opens a window).</li>
        </ul>

        <h2 id="security">7 · Security checklist</h2>
        <ul>
          <li>✅ Webhook signature verification (HMAC SHA-256) — forged payloads are rejected</li>
          <li>✅ CSRF protection app-wide (webhook URI excluded)</li>
          <li>✅ bcrypt password hashing + 15-minute login lockout after 5 failed attempts</li>
          <li>✅ Output escaping and parameterized queries everywhere</li>
          <li>✅ Secret-protected cron endpoint; <code>robots.txt</code> blocks crawlers from admin paths</li>
          <li>☐ Change <code>cron_key</code> and <code>encryption_key</code> before going live (accounts already use your own passwords — no defaults exist)</li>
          <li>☐ Use <code>CI_ENV=production</code> and keep PHP/MySQL updated</li>
        </ul>

        <h2 id="troubleshoot">Troubleshooting</h2>
        <table class="table">
          <tbody>
            <tr><td class="fw-semibold" style="width:45%;">Webhook shows “Verification failed”</td><td class="text-secondary">Verify token in Meta ≠ value in Settings.</td></tr>
            <tr><td class="fw-semibold">Webhook 403 on POST</td><td class="text-secondary">App secret wrong.</td></tr>
            <tr><td class="fw-semibold">Messages arrive but no replies</td><td class="text-secondary">Cron down (red dashboard pill), bot paused in Conversations, or AI key missing.</td></tr>
            <tr><td class="fw-semibold">Replies show the fallback message</td><td class="text-secondary">AI key invalid or model wrong in Settings.</td></tr>
            <tr><td class="fw-semibold">Owner gets no alerts</td><td class="text-secondary">Owner must message the bot number once; check the owner number format.</td></tr>
            <tr><td class="fw-semibold">“Message failed to send”</td><td class="text-secondary">24h window closed — the customer must message first, or use an approved template.</td></tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</section>
