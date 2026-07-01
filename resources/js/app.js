import './bootstrap';
import Alpine from 'alpinejs';

window.Alpine = Alpine;

Alpine.data('directoryPicker', (config = {}) => ({
    mode: config.mode ?? 'single',
    namePrefix: config.namePrefix ?? 'to',
    searchUrl: config.searchUrl ?? '/api/directory/search',
    placeholder: config.placeholder ?? 'Search by name or email...',
    query: '',
    results: [],
    open: false,
    loading: false,
    selected: config.initial ?? (config.mode === 'multiple' ? [] : null),
    debounce: null,

    init() {
        if (this.mode === 'multiple' && !Array.isArray(this.selected)) {
            this.selected = [];
        }
    },

    onInput() {
        clearTimeout(this.debounce);
        this.debounce = setTimeout(() => this.fetchResults(), 150);
    },

    async fetchResults() {
        if (!this.query.trim()) {
            this.results = [];
            this.open = false;
            return;
        }

        this.loading = true;
        try {
            const response = await fetch(`${this.searchUrl}?q=${encodeURIComponent(this.query)}`, {
                headers: { Accept: 'application/json' },
            });
            const data = await response.json();
            this.results = data.results ?? [];
            this.open = true;
        } finally {
            this.loading = false;
        }
    },

    select(item) {
        if (this.mode === 'single') {
            this.selected = item;
            this.query = '';
            this.open = false;
            this.results = [];
            return;
        }

        if (!this.selected.some((entry) => entry.email === item.email)) {
            this.selected.push(item);
        }

        this.query = '';
        this.open = false;
        this.results = [];
    },

    remove(email) {
        if (this.mode === 'single') {
            this.selected = null;
            return;
        }

        this.selected = this.selected.filter((entry) => entry.email !== email);
    },
}));

Alpine.start();
