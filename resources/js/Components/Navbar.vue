<template>
    <nav
        class="navbar navbar-top navbar-expand navbar-dashboard navbar-dark ps-0 pe-2 pb-0"
    >
        <div class="container-fluid px-0">
            <div
                class="d-flex justify-content-between w-100"
                id="navbarSupportedContent"
            >
                <div class="d-flex align-items-center"></div>
                <!-- Navbar links -->
                <ul class="navbar-nav align-items-center">
                    <li class="nav-item dropdown ms-lg-3">
                        <a
                            class="nav-link dropdown-toggle px-0 pt-1"
                            href="#"
                            role="button"
                            data-bs-toggle="dropdown"
                            aria-expanded="false"
                        >
                            <div class="media d-flex align-items-center">
                                <img
                                    class="avatar rounded-circle"
                                    alt="Image placeholder"
                                    :src="`https://ui-avatars.com/api/?name=${$page.props.auth.user.name}&amp;background=4e73df&amp;color=ffffff&amp;size=100`"
                                />
                                <div
                                    class="media-body text-dark align-items-center d-none d-lg-block ms-2"
                                >
                                    <span
                                        class="fw-bold mb-0 font-small text-gray-900"
                                        >{{ $page.props.auth.user.name }}</span
                                    >
                                </div>
                            </div>
                        </a>
                        <div
                            class="dropdown-menu dashboard-dropdown dropdown-menu-end mt-2 border-0 py-1 shadow"
                        >
                            <button
                                class="dropdown-item d-flex align-items-center"
                                @click="handleLogout"
                            >
                                <svg
                                    class="dropdown-icon text-danger me-2"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                    xmlns="http://www.w3.org/2000/svg"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"
                                    ></path>
                                </svg>
                                Logout
                            </button>
                        </div>
                    </li>
                </ul>
            </div>
        </div>
    </nav>
</template>

<script>
import { Link, router } from '@inertiajs/vue3';
import Swal from 'sweetalert2';

export default {
    components: {
        Link,
    },
    setup() {
        const handleLogout = () => {
            Swal.fire({
                title: 'Konfirmasi Logout',
                text: 'Apakah Anda yakin ingin keluar dari akun admin ini?',
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Ya, Logout',
                cancelButtonText: 'Batal',
            }).then((result) => {
                if (result.isConfirmed) {
                    router.post('/admin/logout');
                }
            });
        };

        return { handleLogout };
    },
};
</script>
