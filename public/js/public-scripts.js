/**
 * Education Resources Manager - Public Scripts
 *
 * @package Education_Resources_Manager
 */

(function () {
	'use strict';

	const ERM = {
		container: null,
		perPage: 9,
		currentPage: 1,
		totalPages: 1,
		searchTimeout: null,

		init: function () {
			this.container = document.querySelector('.erm-resources-container');
			if (!this.container) return;

			this.perPage = parseInt(this.container.dataset.perPage || 9, 10);
			this.bindEvents();
			this.loadResources();
		},

		bindEvents: function () {
			const filterType = document.getElementById('erm-filter-type');
			const filterLevel = document.getElementById('erm-filter-level');
			const filterCategory = document.getElementById('erm-filter-category');
			const filterSearch = document.getElementById('erm-filter-search');

			if (filterType) filterType.addEventListener('change', () => this.onFilterChange());
			if (filterLevel) filterLevel.addEventListener('change', () => this.onFilterChange());
			if (filterCategory) filterCategory.addEventListener('change', () => this.onFilterChange());
			if (filterSearch) {
				filterSearch.addEventListener('input', () => {
					clearTimeout(this.searchTimeout);
					this.searchTimeout = setTimeout(() => this.onFilterChange(), 400);
				});
			}
		},

		onFilterChange: function () {
			this.currentPage = 1;
			this.loadResources();
		},

		getFilters: function () {
			return {
				page: this.currentPage,
				per_page: this.perPage,
				type: document.getElementById('erm-filter-type')?.value || '',
				level: document.getElementById('erm-filter-level')?.value || '',
				category: document.getElementById('erm-filter-category')?.value || '',
				search: document.getElementById('erm-filter-search')?.value || ''

			};
		},

		loadResources: function () {
			const grid = this.container?.querySelector('.erm-resources-grid');
			const loading = this.container?.querySelector('.erm-loading');
			const noResults = this.container?.querySelector('.erm-no-results');
			const pagination = this.container?.querySelector('.erm-pagination');

			if (!grid) return;

			if (loading) loading.style.display = 'block';
			if (noResults) noResults.style.display = 'none';
			grid.innerHTML = '';

			const params = new URLSearchParams(this.getFilters());
			const url = `${ermData.apiUrl}/resources?${params.toString()}`;

			fetch(url, {
				method: 'GET',
				headers: {
					'X-WP-Nonce': ermData.nonce,
					'Accept': 'application/json'
				}
			})
				.then(response => {
					if (!response.ok) throw new Error('Network error');
					this.totalPages = parseInt(response.headers.get('X-WP-TotalPages') || 1, 10);
					return response.json();
				})
				.then(data => {
					if (loading) loading.style.display = 'none';

					if (!data || data.length === 0) {
						if (noResults) noResults.style.display = 'block';
						return;
					}

					data.forEach(resource => {
						grid.appendChild(this.createCard(resource));
					});

					this.renderPagination(pagination);
				})
				.catch(() => {
					if (loading) loading.style.display = 'none';
					if (noResults) {
						noResults.querySelector('p').textContent = ermData.i18n.noResults || 'No se encontraron recursos.';
						noResults.style.display = 'block';
					}
				});
		},

		createCard: function (resource) {
			const article = document.createElement('article');
			article.className = 'erm-resource-card';
			article.setAttribute('role', 'listitem');

			const typeLabels = {
				course: 'Curso',
				tutorial: 'Tutorial',
				ebook: 'Ebook',
				video: 'Video'
			};
			const levelLabels = {
				beginner: 'Principiante',
				intermediate: 'Intermedio',
				advanced: 'Avanzado'
			};

			const typeLabel = typeLabels[resource.type] || resource.type;
			const levelLabel = levelLabels[resource.level] || resource.level;
			const priceDisplay = !resource.price || resource.price === '0' || resource.price.toLowerCase() === 'gratuito'
				? (ermData.i18n.free || 'Gratuito')
				: resource.price;
			const durationText = resource.duration
				? `${resource.duration} ${ermData.i18n.min || 'min'}`
				: '';

			const imageHtml = resource.thumbnail
				? `<img src="${this.escapeHtml(resource.thumbnail)}" alt="" loading="lazy" />`
				: '';

			article.innerHTML = `
				<div class="erm-resource-card__image">${imageHtml}</div>
				<div class="erm-resource-card__body">
					<div class="erm-resource-card__meta">
						<span>${this.escapeHtml(typeLabel)}</span>
						<span>${this.escapeHtml(levelLabel)}</span>
					</div>
					<h3 class="erm-resource-card__title">
						<a href="${this.escapeHtml(resource.permalink)}" target="_blank" rel="noopener">${this.escapeHtml(resource.title)}</a>
					</h3>
					${resource.excerpt ? `<p class="erm-resource-card__excerpt">${this.escapeHtml(resource.excerpt)}</p>` : ''}
					<div class="erm-resource-card__footer">
						${durationText ? `<span class="erm-resource-card__duration">${durationText}</span>` : ''}
						${priceDisplay ? `<span class="erm-resource-card__price">${this.escapeHtml(priceDisplay)}</span>` : ''}
					</div>
					<a href="${this.escapeHtml(resource.url || resource.permalink)}" 
					   class="erm-resource-card__btn erm-view-resource" 
					   data-id="${resource.id}"
					   data-nonce="${this.escapeHtml(resource.track_nonce || '')}"
					   target="_blank" 
					   rel="noopener">
						${ermData.i18n.viewResource || 'Ver recurso'}
					</a>
				</div>
			`;

			const btn = article.querySelector('.erm-view-resource');
			if (btn && resource.track_nonce) {
				btn.addEventListener('click', (e) => this.trackResource(resource.id, btn));
			}

			return article;
		},

		trackResource: function (resourceId, btn) {
			if (btn.disabled) return;

			const nonce = btn.dataset.nonce || '';
			if (!nonce) return;

			btn.disabled = true;

			fetch(`${ermData.apiUrl}/resources/${resourceId}/track`, {
				method: 'POST',
				headers: {
					'Content-Type': 'application/json',
					'X-WP-Nonce': ermData.nonce
				},
				body: JSON.stringify({ action: 'view', nonce: nonce })
			}).catch(() => {}).finally(() => {
				btn.disabled = false;
			});
		},

		escapeHtml: function (text) {
			if (!text) return '';
			const div = document.createElement('div');
			div.textContent = text;
			return div.innerHTML;
		},

		renderPagination: function (container) {
			if (!container || this.totalPages <= 1) {
				if (container) container.innerHTML = '';
				return;
			}

			let html = '';

			if (this.currentPage > 1) {
				html += `<button class="erm-pagination__btn" data-page="${this.currentPage - 1}" type="button">&larr; Anterior</button>`;
			}

			html += `<span class="erm-pagination__info">Página ${this.currentPage} de ${this.totalPages}</span>`;

			if (this.currentPage < this.totalPages) {
				html += `<button class="erm-pagination__btn" data-page="${this.currentPage + 1}" type="button">Siguiente &rarr;</button>`;
			}

			container.innerHTML = html;

			container.querySelectorAll('.erm-pagination__btn').forEach(btn => {
				btn.addEventListener('click', () => {
					this.currentPage = parseInt(btn.dataset.page, 10);
					this.loadResources();
					this.container.querySelector('.erm-resources-grid')?.scrollIntoView({ behavior: 'smooth' });
				});
			});
		}
	};

	document.addEventListener('DOMContentLoaded', () => ERM.init());
})();
