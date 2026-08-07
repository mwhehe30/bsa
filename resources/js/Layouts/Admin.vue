<template>
    <nav class="navbar navbar-dark navbar-theme-primary col-12 d-lg-none px-4">
        <div class="d-flex justify-content-between align-items-center w-100">
            <a
                class="navbar-brand"
                href="/"
            >
                <img
                    class="navbar-brand-dark"
                    src=""
                />
                <img
                    class="navbar-brand-light"
                    src=""
                />
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
        <div :key="$page.url">
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
};
</script>

<style></style>
