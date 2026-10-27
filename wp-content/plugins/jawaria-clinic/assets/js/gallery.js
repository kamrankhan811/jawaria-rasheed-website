/* Jawaria Clinic — photo gallery lightbox (native <dialog>, no libraries). */
(function () {
	document.querySelectorAll('[data-jr-gallery]').forEach(function (root) {
		var links = Array.prototype.slice.call(root.querySelectorAll('[data-jr-gallery-link]'));
		if (!links.length || typeof HTMLDialogElement === 'undefined') return; // old browsers: links open the image
		var dlg = document.createElement('dialog');
		dlg.className = 'jr-lightbox';
		dlg.innerHTML = '<button type="button" class="jr-lightbox__close" aria-label="Close">&times;</button>' +
			'<button type="button" class="jr-lightbox__nav jr-lightbox__nav--prev" aria-label="Previous photo">&#8249;</button>' +
			'<figure><img alt=""><figcaption></figcaption></figure>' +
			'<button type="button" class="jr-lightbox__nav jr-lightbox__nav--next" aria-label="Next photo">&#8250;</button>';
		document.body.appendChild(dlg);
		var img = dlg.querySelector('img'), cap = dlg.querySelector('figcaption'), cur = 0;

		function show(i) {
			cur = (i + links.length) % links.length;
			var a = links[cur], thumb = a.querySelector('img'), fig = a.closest('figure').querySelector('figcaption');
			img.src = a.href;
			img.alt = thumb ? thumb.alt : '';
			cap.textContent = fig ? fig.textContent : '';
		}
		links.forEach(function (a, i) {
			a.addEventListener('click', function (e) { e.preventDefault(); show(i); dlg.showModal(); });
		});
		dlg.querySelector('.jr-lightbox__close').addEventListener('click', function () { dlg.close(); });
		dlg.querySelector('.jr-lightbox__nav--prev').addEventListener('click', function () { show(cur - 1); });
		dlg.querySelector('.jr-lightbox__nav--next').addEventListener('click', function () { show(cur + 1); });
		dlg.addEventListener('click', function (e) { if (e.target === dlg) dlg.close(); }); // click on backdrop
		dlg.addEventListener('keydown', function (e) {
			if (e.key === 'ArrowLeft') show(cur - 1);
			if (e.key === 'ArrowRight') show(cur + 1);
		});
		if (links.length < 2) dlg.querySelectorAll('.jr-lightbox__nav').forEach(function (b) { b.hidden = true; });
	});
})();
