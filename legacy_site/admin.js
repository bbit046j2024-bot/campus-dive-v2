document.addEventListener('DOMContentLoaded', () => {
    // 1. Sidebar Toggle
    const sidebar = document.getElementById('sidebar');
    const sidebarToggle = document.getElementById('sidebarToggle');
    // const mainContent = document.querySelector('.main-content'); // Not strictly needed if grid handles it

    if (sidebarToggle) {
        sidebarToggle.addEventListener('click', () => {
            sidebar.classList.toggle('collapsed');
            // Save preference to localStorage?
            const isCollapsed = sidebar.classList.contains('collapsed');
            localStorage.setItem('sidebarCollapsed', isCollapsed);
        });
    }

    // Restore Sidebar State
    if (localStorage.getItem('sidebarCollapsed') === 'true' && sidebar) {
        sidebar.classList.add('collapsed');
    }

    // 2. Global Search
    const searchInput = document.getElementById('globalSearch');
    const searchResults = document.getElementById('searchResults');
    let debounceTimer;

    if (searchInput) {
        searchInput.addEventListener('input', (e) => {
            clearTimeout(debounceTimer);
            const query = e.target.value.trim();

            if (query.length < 2) {
                searchResults.innerHTML = '';
                searchResults.classList.remove('show');
                return;
            }

            debounceTimer = setTimeout(() => {
                fetch(`search.php?q=${encodeURIComponent(query)}`)
                    .then(response => response.json())
                    .then(data => {
                        renderSearchResults(data);
                    })
                    .catch(err => console.error('Search error:', err));
            }, 300);
        });

        // Hide results on click outside
        document.addEventListener('click', (e) => {
            if (!searchInput.contains(e.target) && !searchResults.contains(e.target)) {
                searchResults.classList.remove('show');
            }
        });
    }

    function renderSearchResults(results) {
        if (!results || results.length === 0) {
            searchResults.innerHTML = '<div class="p-3 text-muted">No results found</div>';
        } else {
            let html = '';
            results.forEach(item => {
                html += `
                    <a href="${item.url}" class="search-result-item" style="display: flex; align-items: center; gap: 10px; padding: 10px; text-decoration: none; color: var(--text-main); border-bottom: 1px solid var(--border-color);">
                        <div class="icon-box" style="width: 30px; text-align: center; color: var(--primary-color);">
                            <i class="${item.icon}"></i>
                        </div>
                        <div>
                            <div style="font-weight: 500;">${item.title}</div>
                            <div style="font-size: 0.8em; color: var(--text-muted);">${item.subtitle}</div>
                        </div>
                    </a>
                `;
            });
            searchResults.innerHTML = html;
        }
        searchResults.classList.add('show');
    }
});
