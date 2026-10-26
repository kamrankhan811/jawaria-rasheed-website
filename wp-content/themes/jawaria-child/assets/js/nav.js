/* Header: mobile menu toggle + submenu toggles + keyboard-friendly dropdowns. */
(function () {
	var header = document.querySelector('[data-jr-header]');
	if (!header) return;
	var burger = header.querySelector('[data-jr-burger]');
	var mobile = window.matchMedia('(max-width: 1024px)');

	if (burger) {
		burger.addEventListener('click', function () {
			var open = header.classList.toggle('is-open');
			burger.setAttribute('aria-expanded', open ? 'true' : 'false');
		});
	}

	// Add a toggle button to every item that has a submenu.
	header.querySelectorAll('.menu-item-has-children').forEach(function (li) {
		var link = li.querySelector(':scope > a');
		var btn = document.createElement('button');
		btn.type = 'button';
		btn.className = 'jr-sub-toggle';
		btn.setAttribute('aria-expanded', 'false');
		btn.innerHTML = '<span class="screen-reader-text">Open submenu</span>';
		if (link) link.after(btn);
		btn.addEventListener('click', function (e) {
			e.preventDefault();
			var open = li.classList.toggle('is-open');
			btn.setAttribute('aria-expanded', open ? 'true' : 'false');
		});
		// Column headings in the mega menu ("#" links) are not clickable pages.
		if (link && link.getAttribute('href') === '#') {
			link.addEventListener('click', function (e) {
				e.preventDefault();
				if (mobile.matches) btn.click();
			});
		}
	});

	// Close dropdowns with Escape.
	document.addEventListener('keydown', function (e) {
		if (e.key !== 'Escape') return;
		header.classList.remove('is-open');
		header.querySelectorAll('.is-open').forEach(function (el) { el.classList.remove('is-open'); });
		if (burger) burger.setAttribute('aria-expanded', 'false');
	});
})();
