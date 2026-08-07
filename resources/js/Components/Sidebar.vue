<template>
    <nav
        id="sidebarMenu"
        class="sidebar d-lg-block collapse bg-gray-800 text-white"
        data-simplebar
    >
        <div class="sidebar-inner px-4 pt-3">
            <!-- Tombol Close untuk mobile -->
            <div class="d-flex d-lg-none justify-content-end mb-3">
                <button
                    type="button"
                    class="btn-close btn-close-white"
                    data-bs-toggle="collapse"
                    data-bs-target="#sidebarMenu"
                    aria-controls="sidebarMenu"
                    aria-expanded="true"
                    aria-label="Close navigation"
                ></button>
            </div>

            <ul class="nav flex-column pt-md-0 pt-3">
                <li class="nav-item">
                    <span class="d-flex justify-content-between mt-2">
                        <span>
                            <span class="sidebar-icon">
                                <svg
                                    xmlns="http://www.w3.org/2000/svg"
                                    width="20"
                                    height="20"
                                    fill="currentColor"
                                    class="bi bi-mortarboard-fill icon icon-xs me-2"
                                    viewBox="0 0 16 16"
                                >
                                    <path
                                        d="M8.211 2.047a.5.5 0 0 0-.422 0l-7.5 3.5a.5.5 0 0 0 .025.917l7.5 3a.5.5 0 0 0 .372 0L14 7.14V13a1 1 0 0 0-1 1v2h3v-2a1 1 0 0 0-1-1V6.739l.686-.275a.5.5 0 0 0 .025-.917l-7.5-3.5Z"
                                    />
                                    <path
                                        d="M4.176 9.032a.5.5 0 0 0-.656.327l-.5 1.7a.5.5 0 0 0 .294.605l4.5 1.8a.5.5 0 0 0 .372 0l4.5-1.8a.5.5 0 0 0 .294-.605l-.5-1.7a.5.5 0 0 0-.656-.327L8 10.466 4.176 9.032Z"
                                    />
                                </svg>
                            </span>
                            <span class="sidebar-text fw-bold"
                                >BUWEUK SIPIT ACADEMY</span
                            >
                        </span>
                    </span>
                </li>

                <li
                    role="separator"
                    class="dropdown-divider mt-4 mb-3 border-gray-700"
                ></li>

                <!-- Dashboard -->
                <li
                    class="nav-item"
                    :class="{ active: $page.url === '/admin/dashboard' }"
                >
                    <Link
                        href="/admin/dashboard"
                        class="nav-link d-flex justify-content-between"
                    >
                        <span>
                            <span class="sidebar-icon">
                                <svg
                                    xmlns="http://www.w3.org/2000/svg"
                                    width="18"
                                    height="18"
                                    fill="currentColor"
                                    class="bi bi-speedometer2 icon icon-xs me-2"
                                    viewBox="0 0 16 16"
                                >
                                    <path
                                        d="M8 4a.5.5 0 0 1 .5.5V6a.5.5 0 0 1-1 0V4.5A.5.5 0 0 1 8 4zM3.732 5.732a.5.5 0 0 1 .707 0l.915.914a.5.5 0 1 1-.708.708l-.914-.915a.5.5 0 0 1 0-.707zM2 10a.5.5 0 0 1 .5-.5h1.586a.5.5 0 0 1 0 1H2.5A.5.5 0 0 1 2 10zm9.5 0a.5.5 0 0 1 .5-.5h1.5a.5.5 0 0 1 0 1H12a.5.5 0 0 1-.5-.5zm.754-4.246a.389.389 0 0 0-.527-.02L7.547 9.31a.91.91 0 1 0 1.302 1.258l3.434-4.297a.389.389 0 0 0-.029-.518z"
                                    />
                                    <path
                                        fill-rule="evenodd"
                                        d="M0 10a8 8 0 1 1 15.547 2.661c-.442 1.253-1.845 1.602-2.932 1.25C11.309 13.488 9.475 13 8 13c-1.474 0-3.31.488-4.615.911-1.087.352-2.49.003-2.932-1.25A7.988 7.988 0 0 1 0 10zm8-7a7 7 0 0 0-6.603 9.329c.203.575.923.876 1.68.63C4.397 12.533 6.358 12 8 12s3.604.532 4.923.96c.757.245 1.477-.056 1.68-.631A7 7 0 0 0 8 3z"
                                    />
                                </svg>
                            </span>
                            <span class="sidebar-text">Dashboard</span>
                        </span>
                    </Link>
                </li>

                <li
                    role="separator"
                    class="dropdown-divider mt-2 mb-2 border-gray-700"
                ></li>

                <!-- Mata Pelajaran -->
                <li
                    class="nav-item"
                    :class="{
                        active:
                            $page.url === '/admin/lessons' ||
                            $page.url.startsWith('/admin/lessons/'),
                    }"
                >
                    <Link
                        href="/admin/lessons"
                        class="nav-link d-flex justify-content-between"
                    >
                        <span>
                            <span class="sidebar-icon">
                                <svg
                                    xmlns="http://www.w3.org/2000/svg"
                                    width="18"
                                    height="18"
                                    fill="currentColor"
                                    class="bi bi-bookmarks icon icon-xs me-2"
                                    viewBox="0 0 16 16"
                                >
                                    <path
                                        d="M2 4a2 2 0 0 1 2-2h6a2 2 0 0 1 2 2v11.5a.5.5 0 0 1-.777.416L7 13.101l-4.223 2.815A.5.5 0 0 1 2 15.5V4zm2-1a1 1 0 0 0-1 1v10.566l3.723-2.482a.5.5 0 0 1 .554 0L11 14.566V4a1 1 0 0 0-1-1H4z"
                                    />
                                    <path
                                        d="M4.268 1H12a1 1 0 0 1 1 1v11.768l.223.148A.5.5 0 0 0 14 13.5V2a2 2 0 0 0-2-2H6a2 2 0 0 0-1.732 1z"
                                    />
                                </svg>
                            </span>
                            <span class="sidebar-text">Mata Pelajaran</span>
                        </span>
                    </Link>
                </li>

                <!-- Siswa -->
                <li
                    class="nav-item"
                    :class="{ active: $page.url === '/admin/students' }"
                >
                    <Link
                        href="/admin/students"
                        class="nav-link d-flex justify-content-between"
                    >
                        <span>
                            <span class="sidebar-icon">
                                <svg
                                    xmlns="http://www.w3.org/2000/svg"
                                    width="18"
                                    height="18"
                                    fill="currentColor"
                                    class="bi bi-people-fill icon icon-xs me-2"
                                    viewBox="0 0 16 16"
                                >
                                    <path
                                        d="M7 14s-1 0-1-1 1-4 5-4 5 3 5 4-1 1-1 1H7zm4-6a3 3 0 1 0 0-6 3 3 0 0 0 0 6z"
                                    />
                                    <path
                                        fill-rule="evenodd"
                                        d="M5.216 14A2.238 2.238 0 0 1 5 13c0-1.355.68-2.75 1.936-3.72A6.325 6.325 0 0 0 5 9c-4 0-5 3-5 4s1 1 1 1h4.216z"
                                    />
                                    <path
                                        d="M4.5 8a2.5 2.5 0 1 0 0-5 2.5 2.5 0 0 0 0 5z"
                                    />
                                </svg>
                            </span>
                            <span class="sidebar-text">Siswa</span>
                        </span>
                    </Link>
                </li>

                <!-- Siswa Terisolir -->
                <li
                    class="nav-item"
                    :class="{
                        active: $page.url === '/admin/students/isolated',
                    }"
                >
                    <Link
                        href="/admin/students/isolated"
                        class="nav-link d-flex justify-content-between"
                    >
                        <span>
                            <span class="sidebar-icon">
                                <svg
                                    xmlns="http://www.w3.org/2000/svg"
                                    width="18"
                                    height="18"
                                    fill="currentColor"
                                    class="bi bi-person-slash icon icon-xs me-2"
                                    viewBox="0 0 16 16"
                                >
                                    <path
                                        d="M11 6a3 3 0 1 1-6 0 3 3 0 0 1 6 0z"
                                    />
                                    <path
                                        d="M2 13c0 1 1 1 1 1h5.256A4.493 4.493 0 0 1 8 12.5a4.49 4.49 0 0 1 1.544-3.393C9.077 9.038 8.564 9 8 9c-5 0-6 3-6 4z"
                                    />
                                    <path
                                        d="M12.5 16a3.5 3.5 0 1 0 0-7 3.5 3.5 0 0 0 0 7zm-1.5-5v1h-1a1 1 0 0 0 0 2h1v1a1 1 0 0 0 2 0v-1h1a1 1 0 0 0 0-2h-1v-1a1 1 0 0 0-2 0z"
                                    />
                                </svg>
                            </span>
                            <span class="sidebar-text">Siswa Terisolir</span>
                        </span>
                    </Link>
                </li>

                <li
                    role="separator"
                    class="dropdown-divider mt-2 mb-2 border-gray-700"
                ></li>

                <!-- Ujian -->
                <li
                    class="nav-item"
                    :class="{
                        active:
                            $page.url === '/admin/exams' ||
                            $page.url.startsWith('/admin/exams/'),
                    }"
                >
                    <Link
                        href="/admin/exams"
                        class="nav-link d-flex justify-content-between"
                    >
                        <span>
                            <span class="sidebar-icon">
                                <svg
                                    xmlns="http://www.w3.org/2000/svg"
                                    width="18"
                                    height="18"
                                    fill="currentColor"
                                    class="bi bi-pencil-square icon icon-xs me-2"
                                    viewBox="0 0 16 16"
                                >
                                    <path
                                        d="M15.502 1.94a.5.5 0 0 1 0 .706L14.459 3.69l-2-2L13.502.646a.5.5 0 0 1 .707 0l1.293 1.293zm-1.75 2.456-2-2L4.939 9.21a.5.5 0 0 0-.121.196l-.805 2.414a.25.25 0 0 0 .316.316l2.414-.805a.5.5 0 0 0 .196-.12l6.813-6.814z"
                                    />
                                    <path
                                        fill-rule="evenodd"
                                        d="M1 13.5A1.5 1.5 0 0 0 2.5 15h11a1.5 1.5 0 0 0 1.5-1.5v-6a.5.5 0 0 0-1 0v6a.5.5 0 0 1-.5.5h-11a.5.5 0 0 1-.5-.5v-11a.5.5 0 0 1 .5-.5H9a.5.5 0 0 0 0-1H2.5A1.5 1.5 0 0 0 1 2.5v11z"
                                    />
                                </svg>
                            </span>
                            <span class="sidebar-text">Ujian</span>
                        </span>
                    </Link>
                </li>

                <li
                    role="separator"
                    class="dropdown-divider mt-2 mb-2 border-gray-700"
                ></li>

                <!-- Monitor Ujian -->
                <li
                    class="nav-item"
                    :class="{
                        active:
                            $page.url === '/admin/monitor' ||
                            $page.url.startsWith('/admin/monitor?'),
                    }"
                >
                    <Link
                        href="/admin/monitor"
                        class="nav-link d-flex justify-content-between"
                    >
                        <span>
                            <span class="sidebar-icon">
                                <svg
                                    xmlns="http://www.w3.org/2000/svg"
                                    width="18"
                                    height="18"
                                    fill="currentColor"
                                    class="bi bi-display icon icon-xs me-2"
                                    viewBox="0 0 16 16"
                                >
                                    <path
                                        d="M0 4s0-2 2-2h12s2 0 2 2v6s0 2-2 2h-4c0 .667.083 1.167.25 1.5H11a.5.5 0 0 1 0 1H5a.5.5 0 0 1 0-1h.75c.167-.333.25-.833.25-1.5H2s-2 0-2-2V4zm1.398-.855a.758.758 0 0 0-.254.302A1.46 1.46 0 0 0 1 4.01V10c0 .325.078.502.145.602.07.105.17.188.302.254a1.464 1.464 0 0 0 .538.143L2.01 11H14c.325 0 .502-.078.602-.145a.758.758 0 0 0 .254-.302 1.464 1.464 0 0 0 .143-.538L15 9.99V4c0-.325-.078-.502-.145-.602a.757.757 0 0 0-.302-.254A1.46 1.46 0 0 0 13.99 3H2c-.325 0-.502.078-.602.145z"
                                    />
                                </svg>
                            </span>
                            <span class="sidebar-text">Monitor Ujian</span>
                        </span>
                    </Link>
                </li>

                <li
                    role="separator"
                    class="dropdown-divider mt-2 mb-2 border-gray-700"
                ></li>

                <!-- Laporan Nilai -->
                <li
                    class="nav-item"
                    :class="{
                        active:
                            $page.url === '/admin/reports' ||
                            $page.url.startsWith('/admin/reports/'),
                    }"
                >
                    <Link
                        href="/admin/reports"
                        class="nav-link d-flex justify-content-between"
                    >
                        <span>
                            <span class="sidebar-icon">
                                <svg
                                    xmlns="http://www.w3.org/2000/svg"
                                    width="18"
                                    height="18"
                                    fill="currentColor"
                                    class="bi bi-graph-up-arrow icon icon-xs me-2"
                                    viewBox="0 0 16 16"
                                >
                                    <path
                                        fill-rule="evenodd"
                                        d="M0 0h1v15h15v1H0V0Zm10 3.5a.5.5 0 0 1 .5-.5h4a.5.5 0 0 1 .5.5v4a.5.5 0 0 1-1 0V4.9l-3.613 4.417a.5.5 0 0 1-.74.037L7.06 6.767l-3.656 5.027a.5.5 0 0 1-.808-.588l4-5.5a.5.5 0 0 1 .758-.06l2.609 2.61L13.445 4H10.5a.5.5 0 0 1-.5-.5Z"
                                    />
                                </svg>
                            </span>
                            <span class="sidebar-text">Laporan Nilai</span>
                        </span>
                    </Link>
                </li>
            </ul>
        </div>
    </nav>
</template>

<script>
import { Link, router } from "@inertiajs/vue3";
import { onMounted, onUnmounted } from "vue";

export default {
    components: {
        Link,
    },
    
    setup() {
        let bootstrap = null;
        let sidebarCollapse = null;
        
        const closeSidebarOnMobile = () => {
            // Only close on mobile (< 992px)
            if (window.innerWidth < 992) {
                const sidebarElement = document.getElementById('sidebarMenu');
                if (sidebarElement && sidebarElement.classList.contains('show')) {
                    // Close sidebar using Bootstrap's collapse
                    if (sidebarCollapse) {
                        sidebarCollapse.hide();
                    }
                }
            }
        };
        
        onMounted(() => {
            // Initialize Bootstrap Collapse
            if (typeof window.bootstrap !== 'undefined') {
                bootstrap = window.bootstrap;
                const sidebarElement = document.getElementById('sidebarMenu');
                if (sidebarElement) {
                    sidebarCollapse = new bootstrap.Collapse(sidebarElement, {
                        toggle: false
                    });
                }
            }
            
            // Listen to Inertia navigation events
            router.on('start', closeSidebarOnMobile);
        });
        
        onUnmounted(() => {
            // Clean up event listener
            router.off('start', closeSidebarOnMobile);
        });
        
        return {};
    }
};
</script>

<style>
.nav-item.active .nav-link {
    background: rgba(255, 255, 255, 0.1) !important;
    border-radius: 8px;
}
</style>
