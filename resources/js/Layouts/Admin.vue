<template>
    <nav class="navbar navbar-dark navbar-theme-primary col-12 d-lg-none px-4">
        <div class="d-flex justify-content-between align-items-center w-100">
            <a class="navbar-brand d-flex align-items-center gap-2" href="/admin/dashboard">
                <img class="admin-mobile-logo" src="/assets/images/logo.png" alt="Buweuk Sipit Academy" />
                <span class="text-white fw-bold">BSA Admin</span>
            </a>
            <div>
                <button
                    class="navbar-toggler d-lg-none collapsed"
                    type="button"
                    data-bs-toggle="collapse"
                    data-bs-target="#sidebarMenu"
                    aria-controls="sidebarMenu"
                    aria-expanded="false"
                    aria-label="Toggle navigation"
                >
                    <span class="navbar-toggler-icon"></span>
                </button>
            </div>
        </div>
    </nav>

    <!-- sidebar -->
    <Sidebar />

    <main class="content">
        <!-- navbar -->
        <Navbar />

        <!-- content -->
        <div class="admin-page" :key="$page.url">
            <slot />
        </div>
    </main>
</template>

<script>
//import navbar
import Navbar from '../Components/Navbar.vue';

//import sidebar
import Sidebar from '../Components/Sidebar.vue';

export default {
    //register components
    components: {
        Navbar,
        Sidebar,
    },
    mounted() {
        this.applyResponsiveTableLabels();
    },
    updated() {
        this.applyResponsiveTableLabels();
    },
    methods: {
        applyResponsiveTableLabels() {
            this.$nextTick(() => {
                document.querySelectorAll('.admin-page .table-responsive table').forEach((table) => {
                    const headers = Array.from(table.querySelectorAll('thead th')).map((header) =>
                        header.textContent.replace(/\s+/g, ' ').trim(),
                    );

                    if (!headers.length) return;

                    table.querySelectorAll('tbody tr').forEach((row) => {
                        Array.from(row.children).forEach((cell, index) => {
                            if (cell.tagName !== 'TD' || cell.hasAttribute('colspan')) return;
                            if (!cell.dataset.label && headers[index]) {
                                cell.dataset.label = headers[index];
                            }
                        });
                    });
                });
            });
        },
    },
};
</script>

<style scoped>
.admin-mobile-logo {
    width: 40px;
    height: 40px;
    object-fit: contain;
}

@media (max-width: 575.98px) {
    .navbar {
        padding: 0.625rem 1rem !important;
    }

    .navbar-brand {
        font-size: 1rem;
    }
}
</style>
