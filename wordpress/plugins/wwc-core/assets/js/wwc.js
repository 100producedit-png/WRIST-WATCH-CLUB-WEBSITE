/* Wrist Watch Club — front-end behaviors
 * 1. Collection grids: category filter chips + price sort (client-side).
 * 2. Store-credit application: 3-step wizard that POSTs directly to the
 *    external underwriting endpoint. Sensitive fields NEVER touch WordPress.
 */
(function () {
	'use strict';

	// ------------------------------------------------------------------
	// Collection filter + sort
	// ------------------------------------------------------------------
	document.querySelectorAll('[data-wwc-collection]').forEach(function (root) {
		var grid = root.querySelector('.wwc-grid');
		var chips = root.querySelectorAll('.wwc-chip');
		var sort = root.querySelector('.wwc-sort select');
		if (!grid) return;

		var cards = Array.prototype.slice.call(grid.querySelectorAll('.wwc-card'));
		var original = cards.slice();

		function apply() {
			var active = root.querySelector('.wwc-chip.is-active');
			var cat = active ? active.getAttribute('data-cat') : 'all';
			var mode = sort ? sort.value : 'featured';

			var list = original.slice();
			if (mode === 'low') list.sort(function (a, b) { return parseFloat(a.dataset.price) - parseFloat(b.dataset.price); });
			if (mode === 'high') list.sort(function (a, b) { return parseFloat(b.dataset.price) - parseFloat(a.dataset.price); });

			list.forEach(function (card) {
				var cats = (card.getAttribute('data-cats') || '').split('|');
				card.style.display = (cat === 'all' || cats.indexOf(cat) !== -1) ? '' : 'none';
				grid.appendChild(card);
			});
		}

		chips.forEach(function (chip) {
			chip.addEventListener('click', function () {
				chips.forEach(function (c) { c.classList.remove('is-active'); });
				chip.classList.add('is-active');
				apply();
			});
		});
		if (sort) sort.addEventListener('change', apply);
	});

	// ------------------------------------------------------------------
	// Store-credit application wizard
	// ------------------------------------------------------------------
	var app = document.querySelector('[data-wwc-credit-app]');
	if (app) {
		var step = 1;
		var panels = [app.querySelector('[data-step="1"]'), app.querySelector('[data-step="2"]'), app.querySelector('[data-step="3"]')];
		var dots = app.querySelectorAll('.wwc-wstep');
		var form = app.querySelector('form');
		var confirmEl = document.querySelector('[data-wwc-credit-confirm]');
		var errEl = app.querySelector('.wwc-credit-error');

		function show(n) {
			step = Math.max(1, Math.min(3, n));
			panels.forEach(function (p, i) { if (p) p.style.display = (i === step - 1) ? 'block' : 'none'; });
			dots.forEach(function (d, i) { d.classList.toggle('is-on', i < step); });
			window.scrollTo({ top: app.offsetTop - 20, behavior: 'smooth' });
		}

		app.querySelectorAll('[data-next]').forEach(function (b) { b.addEventListener('click', function () { show(step + 1); }); });
		app.querySelectorAll('[data-back]').forEach(function (b) { b.addEventListener('click', function () { show(step - 1); }); });
		show(1);

		if (form) {
			form.addEventListener('submit', function (e) {
				e.preventDefault();
				if (errEl) errEl.style.display = 'none';

				var required = ['consent_softpull', 'consent_ecoa', 'consent_terms'];
				var missing = required.some(function (n) { var el = form.elements[n]; return !el || !el.checked; });
				var esign = form.elements.esignature ? form.elements.esignature.value.trim() : '';
				if (missing || !esign) {
					if (errEl) {
						errEl.textContent = 'Please check all three consents and type your full legal name to sign.';
						errEl.style.display = 'block';
					}
					return;
				}

				var fd = new FormData(form);
				var payload = {};
				fd.forEach(function (v, k) { payload[k] = v; });
				payload.consent_softpull = !!(form.elements.consent_softpull && form.elements.consent_softpull.checked);
				payload.consent_ecoa = !!(form.elements.consent_ecoa && form.elements.consent_ecoa.checked);
				payload.consent_terms = !!(form.elements.consent_terms && form.elements.consent_terms.checked);
				payload.submitted_at = new Date().toISOString();

				var endpoint = (window.WWC && window.WWC.underwritingEndpoint) || '';

				function done() {
					// Blank the sensitive fields immediately after hand-off.
					if (form.elements.ssn) form.elements.ssn.value = '';
					if (form.elements.dob) form.elements.dob.value = '';
					app.style.display = 'none';
					if (confirmEl) confirmEl.style.display = 'block';
					window.scrollTo({ top: 0, behavior: 'smooth' });
				}

				if (!endpoint) {
					// SUBMIT_ENDPOINT not configured yet (see WooCommerce →
					// Wrist Watch Club settings). No data leaves the browser.
					console.warn('WWC: underwriting endpoint not configured; application NOT transmitted.');
					done();
					return;
				}

				var btn = form.querySelector('[type="submit"]');
				if (btn) { btn.disabled = true; btn.textContent = 'SUBMITTING…'; }

				fetch(endpoint, {
					method: 'POST',
					headers: { 'Content-Type': 'application/json' },
					body: JSON.stringify(payload),
					mode: 'cors',
				}).then(function (res) {
					if (!res.ok) throw new Error('HTTP ' + res.status);
					done();
				}).catch(function (err) {
					console.error('WWC credit submit failed', err);
					if (btn) { btn.disabled = false; btn.textContent = 'SUBMIT APPLICATION'; }
					if (errEl) {
						errEl.textContent = 'We could not reach the underwriting service. Please try again in a moment.';
						errEl.style.display = 'block';
					}
				});
			});
		}
	}
})();
