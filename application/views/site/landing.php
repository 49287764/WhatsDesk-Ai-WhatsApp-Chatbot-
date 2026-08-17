<?php /* Header/footer are rendered by Site::_render() — do NOT load them here. */ ?>

<!-- ============ Hero ============ -->
<header class="hero" id="top">
  <div class="container-lg position-relative">
    <div class="row align-items-center g-5">
      <div class="col-lg-6">
        <span class="eyebrow mb-3"><i class="bi bi-stars"></i> AI-powered WhatsApp for business</span>
        <h1 class="mb-3">Your business, <em>always open</em> for customers.</h1>
        <p class="sub mb-4">An AI assistant that answers questions, shows your products and prices, and takes orders or bookings on WhatsApp — 24/7, in seconds. No app for customers to install, no missed messages.</p>
        <div class="d-flex gap-3 flex-wrap mb-4">
          <a href="<?= html_escape($cta_url) ?>" class="btn btn-brand btn-lg"><?= html_escape($cta_label) ?></a>
          <a href="<?= site_url('#demo') ?>" class="btn btn-ghost btn-lg"><i class="bi bi-play-circle me-1"></i> Watch it work</a>
        </div>
        <div class="trust-row">
          <span><i class="bi bi-check-circle-fill"></i> Free WhatsApp service conversations</span>
          <span><i class="bi bi-check-circle-fill"></i> No credit card</span>
          <span><i class="bi bi-check-circle-fill"></i> Real catalog, real prices</span>
        </div>
      </div>

      <div class="col-lg-6">
        <div class="mock-stage">
          <div class="mock">
            <div class="mock-screen">
              <div class="mock-head">
                <div class="mock-avatar"><i class="bi bi-chat-heart-fill"></i></div>
                <div>
                  <div class="mock-name"><?= html_escape($business_name) ?></div>
                  <div class="mock-status">● online — usually replies instantly</div>
                </div>
              </div>
              <div class="mock-body">
                <div class="bub bub-user msg-anim" style="animation-delay:.3s;">Hi! What do you offer?<div class="bub-time">7:02 PM</div></div>
                <div class="bub bub-bot msg-anim" style="animation-delay:.9s;">Here’s what we offer 👇<br><br><?php if ( ! empty($first_names)): ?>• <?= html_escape(ucfirst($first_names[0] ?? 'menu item')) ?><br>• <?= html_escape(ucfirst($first_names[1] ?? 'another item')) ?><br>• and more…<br><br>Want me to add something to your order?<?php else: ?>Full menu, prices &amp; delivery — just ask!<br><br>Want me to add something to your order?<?php endif; ?><div class="bub-time">7:02 PM</div></div>
                <div class="bub bub-user msg-anim" style="animation-delay:1.7s;">I’ll take 1 <?= html_escape($first_names[0] ?? 'chicken biryani') ?>, please 🙏<div class="bub-time">7:03 PM</div></div>
                <div class="bub bub-bot msg-anim" style="animation-delay:2.4s;">Added to your cart ✅<br><br>• 1x <?= html_escape($first_names[0] ?? 'chicken biryani') ?><br><br>Reply <strong>YES</strong> to confirm.<div class="bub-time">7:03 PM</div></div>
                <div class="bub bub-user msg-anim" style="animation-delay:3.2s;">Yes<div class="bub-time">7:03 PM</div></div>
                <div class="bub bub-bot msg-anim" style="animation-delay:3.9s;"><span class="typing"><span></span><span></span><span></span></span><div class="bub-time">7:03 PM</div></div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</header>

<!-- ============ Stats strip ============ -->
<div class="stats py-4">
  <div class="container-lg">
    <div class="row text-center g-3">
      <div class="col-6 col-md-3"><div class="num count-up">24</div><div class="lbl">/7 orders &amp; bookings</div></div>
      <div class="col-6 col-md-3"><div class="num count-up">&lt; 60</div><div class="lbl">seconds average reply</div></div>
      <div class="col-6 col-md-3"><div class="num count-up">$0</div><div class="lbl">WhatsApp service fees*</div></div>
      <div class="col-6 col-md-3"><div class="num count-up">5</div><div class="lbl">minutes to go live</div></div>
    </div>
  </div>
</div>

<!-- ============ Features ============ -->
<section id="features">
  <div class="container-lg">
    <div class="text-center mb-5">
      <div class="sec-kicker mb-2">Everything it does</div>
      <h2 class="sec-title">A whole customer team in one bot</h2>
      <p class="sec-sub mx-auto">Every answer comes from <em>your</em> catalog and info — the AI never invents prices, hours or policies.</p>
    </div>
    <div class="row g-3">
      <?php
        $features = array(
          array('bi-chat-square-text', '#fef3c7', '#b45309', 'Answers any question', 'Hours, location, availability, prices — customers get instant, accurate answers day or night.'),
          array('bi-journal-text', '#fef3c7', '#b45309', 'Explains your catalog &amp; prices', 'Products, services, packages — described and priced straight from your catalog page.'),
          array('bi-basket2', '#fef3c7', '#b45309', 'Takes orders &amp; bookings in chat', 'Builds the order, shows the total, confirms with the customer and sends it to your team.'),
          array('bi-headset', '#e0f2fe', '#0369a1', 'Hands off to a human', 'When a customer needs a person, the bot pauses and alerts your staff instantly.'),
          array('bi-sliders', '#e0f2fe', '#0369a1', 'Full admin panel', 'Manage catalog, orders, FAQs and settings from one clean dashboard.'),
          array('bi-bell', '#e0f2fe', '#0369a1', 'Alerts on your phone', 'New order or booking? You get a WhatsApp notification the moment it arrives.'),
          array('bi-phone', '#dcfce7', '#15803d', 'No app for customers', 'Works inside WhatsApp — the app your customers already use every day.'),
          array('bi-shield-check', '#dcfce7', '#15803d', 'Secure by default', 'Verified webhooks, encrypted traffic, locked-down admin login.'),
        );
        foreach ($features as $f):
      ?>
        <div class="col-md-6 col-lg-3">
          <div class="feat-card" data-reveal>
            <div class="feat-ico" style="background:<?= $f[1] ?>;color:<?= $f[2] ?>;"><i class="bi <?= $f[0] ?>"></i></div>
            <h3><?= $f[3] ?></h3>
            <p><?= $f[4] ?></p>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- ============ How it works ============ -->
<section id="how" class="section-band">
  <div class="container-lg">
    <div class="text-center mb-5">
      <div class="sec-kicker mb-2">How it works</div>
      <h2 class="sec-title">Live in three steps</h2>
      <p class="sec-sub mx-auto">No technical team required. If you can fill in a form, you can launch this.</p>
    </div>
    <div class="row g-4">
      <div class="col-md-4">
        <div class="step d-flex gap-3" data-reveal>
          <div class="step-num">1</div>
          <div>
            <h3>Connect your WhatsApp number</h3>
            <p>Register your business number with the free WhatsApp Business API and paste four values into the settings page. Test message included.</p>
          </div>
        </div>
      </div>
      <div class="col-md-4">
        <div class="step d-flex gap-3" data-reveal>
          <div class="step-num">2</div>
          <div>
            <h3>Add your catalog &amp; info</h3>
            <p>Products, prices, opening hours, delivery info, FAQs — typed once in the admin panel. The bot only ever answers from these.</p>
          </div>
        </div>
      </div>
      <div class="col-md-4">
        <div class="step d-flex gap-3" data-reveal>
          <div class="step-num">3</div>
          <div>
            <h3>Go live</h3>
            <p>Share your number. Customers message it like any contact — the bot greets them, answers and takes orders while you run your business.</p>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- ============ Live demo (real bot) ============ -->
<section id="demo">
  <div class="container-lg">
    <div class="row align-items-center g-5">
      <div class="col-lg-5">
        <div class="sec-kicker mb-2">Try it yourself</div>
        <h2 class="sec-title mb-3">Tap a message. The real bot answers.</h2>
        <p class="sec-sub mb-4">No script — this demo runs the actual bot logic against your catalog and prices, in a private preview conversation. Try adding items, then confirm.</p>
        <div class="d-flex flex-wrap gap-2 mb-3" id="demoChips">
          <button class="chip" data-q="What do you offer?">What do you offer?</button>
          <button class="chip" data-q="What are your opening hours?">Opening hours?</button>
          <button class="chip" data-q="Do you deliver?">Do you deliver?</button>
          <?php if ( ! empty($demo_items)): ?>
            <?php foreach ($demo_items as $di): ?>
              <button class="chip" data-q="<?= html_escape($di['q']) ?>">Add <?= html_escape($di['name']) ?></button>
            <?php endforeach; ?>
          <?php else: ?>
            <button class="chip" data-q="I want 1 chicken biryani">Add 1 chicken biryani</button>
          <?php endif; ?>
          <button class="chip" data-q="Yes">Yes, confirm</button>
        </div>
        <p class="text-secondary small mb-0"><i class="bi bi-arrow-repeat me-1"></i> Each tap continues your session’s conversation.</p>
      </div>
      <div class="col-lg-7">
        <div class="demo-card">
          <div class="d-flex align-items-center gap-2 px-3 py-2 border-bottom" style="background:#f7f5f0;">
            <div class="mock-avatar" style="width:30px;height:30px;font-size:.85rem;"><i class="bi bi-chat-heart-fill"></i></div>
            <div class="small fw-semibold"><?= html_escape($business_name) ?> · <span class="text-secondary fw-normal">live preview</span></div>
          </div>
          <div class="demo-chat" id="demoChat">
            <div class="bub bub-bot">Hi! 👋 I can help with our menu, prices, hours and orders. What would you like? (Try a question below 👇)</div>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- ============ Free / pricing ============ -->
<section id="pricing" class="section-band">
  <div class="container-lg">
    <div class="text-center mb-4">
      <div class="sec-kicker mb-2">Pricing</div>
      <h2 class="sec-title">Free for businesses — no subscriptions</h2>
      <p class="sec-sub mx-auto">The software costs nothing. WhatsApp service conversations are free too. You only pay your own AI key and hosting — typically a few dollars a month.</p>
    </div>
    <div class="text-center">
      <a href="<?= site_url('site/pricing') ?>" class="btn btn-brand btn-lg">See what's free</a>
      <p class="text-secondary small mt-3 mb-0">No credit card · No lock-in · It's yours</p>
    </div>
  </div>
</section>

<!-- ============ FAQ ============ -->
<section id="faq">
  <div class="container-lg" style="max-width:820px;">
    <div class="text-center mb-5">
      <div class="sec-kicker mb-2">FAQ</div>
      <h2 class="sec-title">Honest answers</h2>
    </div>
    <div class="accordion" id="faqAcc">
      <?php
        $faqs = array(
          array('How long does setup really take?', 'About 30 minutes for the WhatsApp Business API approval (mostly clicking in Meta’s dashboard) plus 5 minutes to import the database, paste four credentials and add your catalog. Our Docs page walks through every click.'),
          array('What do I need to run it?', 'A WhatsApp-capable phone number (new SIM or your existing business number), a PHP + MySQL host with HTTPS and cron (most shared hosts qualify), and an AI API key — Groq has a free tier, or use OpenAI / DeepSeek.'),
          array('How much does it cost to run?', 'WhatsApp service conversations (customer messages and your replies) are free and unlimited since 2024. AI usage typically lands around $2–10/month for a typical business’s volume. Hosting is $5–20/month. Total: usually under $30/month.'),
          array('Can customers pay through WhatsApp?', 'Not natively yet — WhatsApp doesn’t offer payments over the Business API in most countries. Orders are confirmed in chat and payment is collected on delivery/pickup, or via a payment link you add later.'),
          array('Can I take over and reply myself?', 'Yes. Any conversation can be answered by a human from the panel — the bot pauses automatically and re-enables when you say so.'),
          array('What happens if the AI is wrong?', 'The bot can only quote your catalog, prices, hours and FAQs from your database — it physically can’t invent them. For anything unusual, the customer is routed to a human.'),
          array('Is this the official WhatsApp API?', 'Yes. It uses Meta’s official WhatsApp Cloud API with verified webhooks — no unofficial libraries or banned automation.'),
          array('Why CodeIgniter?', 'The project is built on the CodeIgniter framework you already have. It’s a pragmatic choice for a webhook + cron architecture; the same logic ports cleanly to any modern stack later.'),
        );
        foreach ($faqs as $i => $f):
      ?>
      <div class="accordion-item">
        <h2 class="accordion-header">
          <button class="accordion-button <?= $i === 0 ? '' : 'collapsed' ?>" type="button" data-bs-toggle="collapse" data-bs-target="#faq<?= $i ?>">
            <?= $f[0] ?>
          </button>
        </h2>
        <div id="faq<?= $i ?>" class="accordion-collapse collapse <?= $i === 0 ? 'show' : '' ?>" data-bs-parent="#faqAcc">
          <div class="accordion-body"><?= $f[1] ?></div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>


<script>
// ---------- Live demo: talks to the real bot ----------
(function () {
  var chat = document.getElementById('demoChat');
  var chips = document.getElementById('demoChips');
  if (!chat || !chips) return;
  var busy = false;

  var csrfName = (document.querySelector('meta[name="csrf-name"]') || {}).content || '';
  var csrfHash = (document.querySelector('meta[name="csrf-hash"]') || {}).content || '';
  var base = <?= json_encode(base_url()) ?>;

  function addMsg(text, isUser) {
    var d = document.createElement('div');
    d.className = 'bub ' + (isUser ? 'bub-user' : 'bub-bot');
    d.style.whiteSpace = 'pre-line';
    d.textContent = text;
    chat.appendChild(d);
    chat.scrollTop = chat.scrollHeight;
    return d;
  }

  function typing() {
    var d = document.createElement('div');
    d.className = 'bub bub-bot';
    d.innerHTML = '<span class="typing"><span></span><span></span><span></span></span>';
    chat.appendChild(d);
    chat.scrollTop = chat.scrollHeight;
    return d;
  }

  chips.addEventListener('click', function (e) {
    var btn = e.target.closest('.chip');
    if (!btn || busy) return;
    busy = true;
    btn.disabled = true;

    var text = btn.getAttribute('data-q');
    addMsg(text, true);
    var t = typing();

    var body = encodeURIComponent(csrfName) + '=' + encodeURIComponent(csrfHash) + '&body=' + encodeURIComponent(text);

    fetch(base + 'site/demo_chat', {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: body
    })
    .then(function (r) { return r.json(); })
    .then(function (data) {
      t.remove();
      // The server rotates the CSRF token after each POST — store the fresh
      // one so the next tap keeps working.
      if (data && data.csrf_hash) {
        csrfHash = data.csrf_hash;
        var m = document.querySelector('meta[name="csrf-hash"]');
        if (m) m.setAttribute('content', data.csrf_hash);
      }
      if (data && data.reply) {
        addMsg(data.reply, false);
      } else {
        addMsg('Sorry, I had trouble thinking just now. Try again in a moment.', false);
      }
    })
    .catch(function () {
      t.remove();
      addMsg('Oops — the demo couldn’t reach the server. Try again.', false);
    })
    .finally(function () {
      busy = false;
      btn.disabled = false;
    });
  });
})();
</script>
