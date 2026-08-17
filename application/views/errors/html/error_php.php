<?php
defined('BASEPATH') OR exit('No direct script access allowed');
?><!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>Something went wrong · WhatsDesk</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<style>
	*{ -webkit-font-smoothing:antialiased; }
	body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Inter", sans-serif; background: #f7f5f0; color: #1c1917; margin: 0; }
	main { min-height: 100vh; display: grid; place-items: center; padding: 6rem 1.5rem; }
	.card { background: #fff; border: 1px solid #e7e5e4; border-radius: 16px; padding: 2.5rem; max-width: 34rem; text-align: center; box-shadow: 0 1px 2px rgba(0,0,0,.05); }
	.badge { display: inline-block; font-size: .8rem; font-weight: 700; letter-spacing: .08em; color: #b45309; background: #fef3c7; border-radius: 999px; padding: .25rem .75rem; margin-bottom: 1rem; }
	h1 { font-size: 1.5rem; margin: 0 0 .5rem; }
	p { color: #57534e; line-height: 1.6; margin: 0 0 1.5rem; word-break: break-word; }
	.detail { background: #f5f5f4; border: 1px solid #e7e5e4; border-radius: 10px; padding: 1rem 1.2rem; font-size: .78rem; color: #78716c; text-align: left; margin-bottom: 1.5rem; overflow-wrap: anywhere; line-height: 1.7; }
	.detail strong { color: #44403c; }
	.actions { display: flex; justify-content: center; gap: .75rem; flex-wrap: wrap; }
	a { border-radius: 10px; padding: .6rem 1.1rem; font-size: .9rem; font-weight: 600; text-decoration: none; }
	.btn-primary { background: #b45309; color: #fff; }
	.btn-primary:hover { background: #92400e; }
	.btn-link { color: #1c1917; }
</style>
</head>
<body>
	<main>
		<div class="card">
			<span class="badge">Application error</span>
			<h1>Something went wrong</h1>
			<p>An unexpected error occurred. The details have been logged — if this keeps happening, please contact support.</p>
			<div class="detail">
				<strong>Severity:</strong> <?= html_escape($severity) ?><br>
				<strong>Message:</strong> <?= html_escape($message) ?><br>
				<strong>File:</strong> <?= html_escape($filepath) ?><br>
				<strong>Line:</strong> <?= (int)$line ?>
			</div>
			<div class="actions">
				<a class="btn-primary" href="<?= function_exists('site_url') ? site_url() : '/' ?>">Go back home</a>
				<a class="btn-link" href="<?= function_exists('site_url') ? site_url('site/contact') : '/site/contact' ?>">Contact support &rarr;</a>
			</div>
		</div>
	</main>
</body>
</html>
