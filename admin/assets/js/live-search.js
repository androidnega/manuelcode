// Live Search and Filter Functionality
class LiveSearch {
    constructor(options = {}) {
        this.options = {
            searchInput: '#search',
            filterInputs: '.filter-input',
            tableRows: 'tbody tr',
            noResultsSelector: '.no-results',
            resultsContainer: '.results-container',
            debounceDelay: 300,
            ...options
        };
        
        this.debounceTimer = null;
        this.init();
    }
    
    init() {
        this.bindEvents();
    }
    
    bindEvents() {
        // Search input event
        const searchInput = document.querySelector(this.options.searchInput);
        if (searchInput) {
            searchInput.addEventListener('input', (e) => {
                this.debounce(() => this.performSearch(), this.options.debounceDelay);
            });
        }
        
        // Filter inputs events
        const filterInputs = document.querySelectorAll(this.options.filterInputs);
        filterInputs.forEach(input => {
            input.addEventListener('change', (e) => {
                this.debounce(() => this.performSearch(), this.options.debounceDelay);
            });
        });
        
        // Clear filters button
        const clearButton = document.querySelector('.clear-filters');
        if (clearButton) {
            clearButton.addEventListener('click', (e) => {
                e.preventDefault();
                this.clearFilters();
            });
        }
    }
    
    debounce(func, delay) {
        clearTimeout(this.debounceTimer);
        this.debounceTimer = setTimeout(func, delay);
    }
    
    performSearch() {
        const searchTerm = this.getSearchTerm();
        const filters = this.getFilters();
        
        const rows = document.querySelectorAll(this.options.tableRows);
        let visibleCount = 0;
        
        rows.forEach(row => {
            const text = row.textContent.toLowerCase();
            const matchesSearch = !searchTerm || text.includes(searchTerm.toLowerCase());
            const matchesFilters = this.matchesFilters(row, filters);
            
            if (matchesSearch && matchesFilters) {
                row.style.display = '';
                visibleCount++;
            } else {
                row.style.display = 'none';
            }
        });
        
        this.updateResultsCount(visibleCount, rows.length);
        this.showNoResults(visibleCount === 0);
    }
    
    getSearchTerm() {
        const searchInput = document.querySelector(this.options.searchInput);
        return searchInput ? searchInput.value.trim() : '';
    }
    
    getFilters() {
        const filters = {};
        const filterInputs = document.querySelectorAll(this.options.filterInputs);
        
        filterInputs.forEach(input => {
            if (input.value) {
                filters[input.name] = input.value;
            }
        });
        
        return filters;
    }
    
    matchesFilters(row, filters) {
        for (const [filterName, filterValue] of Object.entries(filters)) {
            if (filterName === 'date') {
                // Handle date filtering
                const rowDate = row.getAttribute('data-date');
                if (rowDate && filterValue) {
                    const rowDateObj = new Date(rowDate);
                    const now = new Date();
                    let matchesDate = true;
                    
                    switch(filterValue) {
                        case 'today':
                            matchesDate = rowDateObj.toDateString() === now.toDateString();
                            break;
                        case 'week':
                            const weekAgo = new Date(now.getTime() - 7 * 24 * 60 * 60 * 1000);
                            matchesDate = rowDateObj >= weekAgo;
                            break;
                        case 'month':
                            const monthAgo = new Date(now.getFullYear(), now.getMonth(), 1);
                            matchesDate = rowDateObj >= monthAgo;
                            break;
                        case 'quarter':
                            const quarterStart = new Date(now.getFullYear(), Math.floor(now.getMonth() / 3) * 3, 1);
                            matchesDate = rowDateObj >= quarterStart;
                            break;
                        case 'year':
                            const yearStart = new Date(now.getFullYear(), 0, 1);
                            matchesDate = rowDateObj >= yearStart;
                            break;
                    }
                    
                    if (!matchesDate) {
                        return false;
                    }
                }
            } else {
                // Handle regular filters
                const cellValue = row.getAttribute(`data-${filterName}`);
                if (cellValue && cellValue !== filterValue) {
                    return false;
                }
            }
        }
        return true;
    }
    
    updateResultsCount(visible, total) {
        const countElement = document.querySelector('.results-count');
        if (countElement) {
            countElement.textContent = `Showing ${visible} of ${total} results`;
        }
    }
    
    showNoResults(show) {
        const noResults = document.querySelector(this.options.noResultsSelector);
        if (noResults) {
            noResults.style.display = show ? 'block' : 'none';
        }
    }
    
    clearFilters() {
        // Clear search input
        const searchInput = document.querySelector(this.options.searchInput);
        if (searchInput) {
            searchInput.value = '';
        }
        
        // Clear filter inputs
        const filterInputs = document.querySelectorAll(this.options.filterInputs);
        filterInputs.forEach(input => {
            input.value = '';
        });
        
        // Show all rows
        const rows = document.querySelectorAll(this.options.tableRows);
        rows.forEach(row => {
            row.style.display = '';
        });
        
        this.updateResultsCount(rows.length, rows.length);
        this.showNoResults(false);
    }
}

// Initialize live search when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    // Initialize live search for different pages
    if (document.querySelector('#search')) {
        new LiveSearch({
            searchInput: '#search',
            filterInputs: '.filter-input',
            tableRows: 'tbody tr',
            noResultsSelector: '.no-results',
            debounceDelay: 300
        });
    }
});
