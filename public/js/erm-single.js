/**
 * Recurso individual - JavaScript Vanilla
 * Compartir, marcar como favorito
 */

(function () {
	'use strict';

	const BOOKMARK_KEY = 'erm-bookmarks';

	function init() {
		initShare();
		initBookmark();
	}

	function initShare() {
		const btn = document.getElementById('erm-share');
		if (!btn) return;

		btn.addEventListener('click', function () {
			if (navigator.share) {
				navigator.share({
					title: document.title,
					url: window.location.href,
					text: document.querySelector('h1')?.textContent || document.title
				}).catch(function () {
					fallbackCopy();
				});
			} else {
				fallbackCopy();
			}
		});
	}

	function fallbackCopy() {
		navigator.clipboard.writeText(window.location.href).then(function () {
			showToast('Enlace copiado');
		}).catch(function () {
			showToast('No se pudo copiar');
		});
	}

	function initBookmark() {
		const btn = document.getElementById('erm-bookmark');
		if (!btn) return;

		const bookmarks = JSON.parse(localStorage.getItem(BOOKMARK_KEY) || '[]');
		const currentUrl = window.location.href;
		const isBookmarked = bookmarks.includes(currentUrl);

		const icon = btn.querySelector('.erm-icon-bookmark');
		if (icon) {
			icon.textContent = isBookmarked ? '★' : '☆';
		}

		btn.addEventListener('click', function () {
			let list = JSON.parse(localStorage.getItem(BOOKMARK_KEY) || '[]');
			const idx = list.indexOf(currentUrl);

			if (idx >= 0) {
				list.splice(idx, 1);
				if (icon) icon.textContent = '☆';
				showToast('Eliminado de guardados');
			} else {
				list.push(currentUrl);
				if (icon) icon.textContent = '★';
				showToast('Guardado');
			}
			localStorage.setItem(BOOKMARK_KEY, JSON.stringify(list));
		});
	}

	function showToast(message) {
		const existing = document.querySelector('.erm-toast');
		if (existing) existing.remove();

		const toast = document.createElement('div');
		toast.className = 'erm-toast';
		toast.textContent = message;
		toast.style.cssText = 'position:fixed;bottom:80px;left:50%;transform:translateX(-50%);padding:0.5rem 1rem;background:#1e293b;color:#f8fafc;border-radius:8px;font-size:0.875rem;z-index:9999;opacity:0;transition:opacity 0.2s;box-shadow:0 4px 20px rgba(0,0,0,0.2);';
		document.body.appendChild(toast);

		requestAnimationFrame(function () {
			toast.style.opacity = '1';
		});

		setTimeout(function () {
			toast.style.opacity = '0';
			setTimeout(function () {
				toast.remove();
			}, 200);
		}, 2000);
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', init);
	} else {
		init();
	}
})();
