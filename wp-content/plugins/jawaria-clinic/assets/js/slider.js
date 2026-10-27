/* Jawaria Clinic — lightweight banner slider (no libraries). */
(function () {
	document.querySelectorAll('[data-jr-slider]').forEach(function (root) {
		var slides = root.querySelectorAll('.jr-slide');
		var dots = root.querySelectorAll('[data-jr-dot]');
		if (slides.length < 2) return;
		var cur = 0, timer = null, paused = false, stopped = false;
		var interval = parseInt(root.getAttribute('data-interval'), 10) || 6000;
		var reduce = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;

		function go(n) {
			n = (n + slides.length) % slides.length;
			if (n === cur) return;
			slides[cur].classList.remove('is-active');
			slides[cur].setAttribute('aria-hidden', 'true');
			slides[cur].querySelectorAll('a').forEach(function (a) { a.setAttribute('tabindex', '-1'); });
			if (dots[cur]) { dots[cur].classList.remove('is-active'); dots[cur].removeAttribute('aria-current'); }
			cur = n;
			var img = slides[cur].querySelector('img[loading="lazy"]');
			if (img) img.loading = 'eager';
			slides[cur].classList.add('is-active');
			slides[cur].removeAttribute('aria-hidden');
			slides[cur].querySelectorAll('a').forEach(function (a) { a.removeAttribute('tabindex'); });
			if (dots[cur]) { dots[cur].classList.add('is-active'); dots[cur].setAttribute('aria-current', 'true'); }
		}
		function start() { stop(); if (!reduce && !paused && !stopped) timer = setInterval(function () { go(cur + 1); }, interval); }
		function stop() { if (timer) { clearInterval(timer); timer = null; } }

		var prev = root.querySelector('[data-jr-prev]'), next = root.querySelector('[data-jr-next]');
		if (prev) prev.addEventListener('click', function () { go(cur - 1); start(); });
		if (next) next.addEventListener('click', function () { go(cur + 1); start(); });
		dots.forEach(function (d) { d.addEventListener('click', function () { go(+d.getAttribute('data-jr-dot')); start(); }); });

		// Pause button: visitors can stop auto-rotation (WCAG 2.2.2).
		var pauseBtn = root.querySelector('[data-jr-pause]');
		if (pauseBtn) {
			if (reduce) pauseBtn.hidden = true;
			pauseBtn.addEventListener('click', function () {
				stopped = !stopped;
				pauseBtn.setAttribute('aria-pressed', String(stopped));
				pauseBtn.setAttribute('aria-label', stopped ? 'Play slides' : 'Pause slides');
				start();
			});
		}

		// Pause only for a real mouse hover or keyboard focus (not taps on phones).
		root.addEventListener('pointerenter', function (e) { if (e.pointerType === 'mouse') { paused = true; stop(); } });
		root.addEventListener('pointerleave', function (e) { if (e.pointerType === 'mouse') { paused = false; start(); } });
		root.addEventListener('focusin', function (e) { var kb = true; try { kb = e.target.matches(':focus-visible'); } catch (x) {} if (kb) { paused = true; stop(); } });
		root.addEventListener('focusout', function () { if (paused) { paused = false; start(); } });
		document.addEventListener('visibilitychange', function () { if (document.hidden) stop(); else start(); });
		root.addEventListener('keydown', function (e) {
			if (e.key === 'ArrowLeft') { go(cur - 1); } else if (e.key === 'ArrowRight') { go(cur + 1); }
		});

		// Touch swipe
		var x0 = null;
		root.addEventListener('touchstart', function (e) { x0 = e.touches[0].clientX; stop(); }, { passive: true });
		root.addEventListener('touchend', function (e) {
			if (x0 === null) return;
			var dx = e.changedTouches[0].clientX - x0;
			if (Math.abs(dx) > 40) go(cur + (dx < 0 ? 1 : -1));
			x0 = null; start();
		}, { passive: true });

		start();
	});
})();
