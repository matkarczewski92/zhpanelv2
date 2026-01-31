const containerId = 'animals-index-container';
const titleId = 'animals-index-title';
const searchSelector = '.js-animals-search';

const getScrollTarget = () => document.getElementById(titleId);

const scrollToTop = () => {
    const target = getScrollTarget();
    if (!target) {
        return;
    }

    const top = target.getBoundingClientRect().top + window.scrollY - 16;
    window.scrollTo({ top, behavior: 'smooth' });

    target.setAttribute('tabindex', '-1');
    target.focus({ preventScroll: true });
};

const replaceContainer = (html) => {
    const parser = new DOMParser();
    const doc = parser.parseFromString(html, 'text/html');
    const next = doc.getElementById(containerId);
    const current = document.getElementById(containerId);

    if (!next || !current) {
        return false;
    }

    current.innerHTML = next.innerHTML;
    return true;
};

const getSearchTokens = (value) =>
    value
        .toLowerCase()
        .trim()
        .split(/\s+/)
        .filter((token) => token.length > 0);

const isPlaceholderRow = (row) => {
    const cell = row.querySelector('td');
    const colspan = cell?.getAttribute('colspan');

    return Boolean(colspan && row.children.length === 1);
};

const applySearchFilter = () => {
    const input = document.querySelector(searchSelector);
    const container = document.getElementById(containerId);
    if (!input || !container) {
        return;
    }

    const tokens = getSearchTokens(input.value);

    container.querySelectorAll('table').forEach((table) => {
        const tbody = table.tBodies[0];
        if (!tbody) {
            return;
        }

        const rows = Array.from(tbody.rows);
        let visibleCount = 0;

        rows.forEach((row) => {
            if (row.dataset.filterEmpty === 'true') {
                row.remove();
                return;
            }

            if (isPlaceholderRow(row)) {
                row.style.display = tokens.length === 0 ? '' : 'none';
                return;
            }

            const text = row.textContent?.toLowerCase() ?? '';
            const matches = tokens.length === 0 || tokens.every((token) => text.includes(token));
            row.style.display = matches ? '' : 'none';
            if (matches) {
                visibleCount += 1;
            }
        });

        if (tokens.length > 0 && visibleCount === 0) {
            const colCount = table.querySelectorAll('thead th').length || 1;
            const emptyRow = document.createElement('tr');
            emptyRow.dataset.filterEmpty = 'true';
            emptyRow.innerHTML = `<td colspan="${colCount}" class="text-center text-muted">Brak danych.</td>`;
            tbody.appendChild(emptyRow);
        }
    });
};

const fetchAndSwap = async (url, pushState = true) => {
    try {
        const response = await fetch(url, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
            },
        });

        if (!response.ok) {
            window.location.href = url;
            return;
        }

        const html = await response.text();
        const replaced = replaceContainer(html);

        if (!replaced) {
            window.location.href = url;
            return;
        }

        if (pushState) {
            window.history.pushState({}, '', url);
        }

        applySearchFilter();
        scrollToTop();
    } catch (error) {
        window.location.href = url;
    }
};

const initAnimalsIndexSort = () => {
    const container = document.getElementById(containerId);
    if (!container) {
        return;
    }

    document.addEventListener('click', (event) => {
        const link = event.target.closest('.js-animals-sort');
        if (!link) {
            return;
        }

        if (event.defaultPrevented || event.button !== 0 || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) {
            return;
        }

        event.preventDefault();
        const url = link.getAttribute('href');
        if (!url) {
            return;
        }

        fetchAndSwap(url);
    });

    window.addEventListener('popstate', () => {
        fetchAndSwap(window.location.href, false);
    });
};

const initAnimalsIndexSearch = () => {
    const input = document.querySelector(searchSelector);
    if (!input) {
        return;
    }

    input.addEventListener('input', () => {
        applySearchFilter();
    });

    applySearchFilter();
};

document.addEventListener('DOMContentLoaded', () => {
    initAnimalsIndexSort();
    initAnimalsIndexSearch();
});
