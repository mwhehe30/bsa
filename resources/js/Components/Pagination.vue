<template>
    <div class="mt-4">
        <!-- Mobile: Stack vertically -->
        <div class="d-flex d-lg-none flex-column gap-3">
            <!-- Per page selector -->
            <div class="d-flex align-items-center justify-content-center">
                <label class="me-2 mb-0 text-muted small">Tampilkan:</label>
                <select 
                    class="form-select form-select-sm" 
                    style="width: auto;"
                    :value="currentPerPage"
                    @change="changePerPage"
                >
                    <option value="5">5</option>
                    <option value="10">10</option>
                    <option value="25">25</option>
                    <option value="50">50</option>
                    <option value="100">100</option>
                </select>
                <span class="ms-2 text-muted small">per halaman</span>
            </div>
            
            <!-- Pagination buttons -->
            <nav>
                <ul class="pagination pagination-sm mb-0 justify-content-center flex-wrap">
                    <li :class="[
                            'page-item', 
                            link.url == null ? 'disabled' : '',
                            link.active ? 'active' : '',
                        ]" 
                        v-for="(link, index) in links" :key="index">
                        <Link 
                            class="page-link" 
                            :href="link.url === null ? '#' : link.url" 
                            v-html="link.label">
                        </Link>
                    </li>
                </ul>
            </nav>
        </div>

        <!-- Desktop: Side by side -->
        <div class="d-none d-lg-flex justify-content-between align-items-center">
            <div class="d-flex align-items-center">
                <label class="me-2 mb-0 text-muted small">Tampilkan:</label>
                <select 
                    class="form-select form-select-sm" 
                    style="width: auto;"
                    :value="currentPerPage"
                    @change="changePerPage"
                >
                    <option value="5">5</option>
                    <option value="10">10</option>
                    <option value="25">25</option>
                    <option value="50">50</option>
                    <option value="100">100</option>
                </select>
                <span class="ms-2 text-muted small">per halaman</span>
            </div>
            <nav>
                <ul class="pagination pagination-sm mb-0">
                    <li :class="[
                            'page-item', 
                            link.url == null ? 'disabled' : '',
                            link.active ? 'active' : '',
                        ]" 
                        v-for="(link, index) in links" :key="index">
                        <Link 
                            class="page-link" 
                            :href="link.url === null ? '#' : link.url" 
                            v-html="link.label">
                        </Link>
                    </li>
                </ul>
            </nav>
        </div>
    </div>
</template>

<script>

    //import Link
    import { Link, router } from '@inertiajs/vue3';

    export default {
        props: {
            links: Array,
            align: String
        },

        components: {
            Link,
        },

        computed: {
            currentPerPage() {
                const urlParams = new URLSearchParams(window.location.search);
                return urlParams.get('per_page') || '5';
            }
        },

        methods: {
            changePerPage(event) {
                const perPage = event.target.value;
                const url = new URL(window.location.href);
                url.searchParams.set('per_page', perPage);
                url.searchParams.delete('page'); // reset to first page
                router.get(url.pathname + url.search, {}, {
                    preserveState: true,
                    preserveScroll: false
                });
            }
        }
    }
</script>

<style scoped>
/* Mobile optimization */
@media (max-width: 991.98px) {
    .pagination {
        font-size: 0.875rem;
    }
    
    .page-link {
        padding: 0.375rem 0.65rem;
        min-width: 2rem;
        text-align: center;
    }
    
    /* Make pagination buttons wrap nicely */
    .pagination.flex-wrap {
        gap: 0.25rem;
    }
}

/* Desktop optimization */
@media (min-width: 992px) {
    .page-link {
        padding: 0.375rem 0.75rem;
    }
}
</style>