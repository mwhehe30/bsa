<template>
    <div>
        <!-- Navbar -->
        <nav
            class="navbar navbar-expand-lg navbar-transparent navbar-dark navbar-theme-primary mb-4 shadow"
        >
            <div class="position-relative container">
                <!-- Brand logo - Disabled link when in exam page -->
                <span v-if="isExamPage" class="navbar-brand me-lg-3">
                    <img
                        class="navbar-brand-dark me-2"
                        src="/assets/images/logo.png"
                        style="height: 70px"
                    />
                    <span class="text-white fw-bold d-none d-lg-inline brand-text-desktop">Buweuk Sipit Academy</span>
                    <span class="text-white fw-bold d-inline d-lg-none brand-text-mobile">BSA</span>
                </span>
                <Link
                    v-else
                    class="navbar-brand me-lg-3"
                    href="/student/dashboard"
                >
                    <img
                        class="navbar-brand-dark me-2"
                        src="/assets/images/logo.png"
                        style="height: 70px"
                    />
                    <span class="text-white fw-bold d-none d-lg-inline brand-text-desktop">Buweuk Sipit Academy</span>
                    <span class="text-white fw-bold d-inline d-lg-none brand-text-mobile">BSA</span>
                </Link>

                <!-- Desktop Menu -->
                <div
                    class="navbar-collapse collapse"
                    id="navbarCollapse"
                >
                    <ul class="navbar-nav mb-md-0 me-auto mb-2"></ul>
                    <div v-if="!isExamPage" class="d-flex align-items-center gap-2">
                        <!-- Logout Button -->
                        <button
                            @click="handleLogout"
                            class="btn btn-sm btn-danger shadow"
                        >
                            <i class="fa fa-sign-out-alt"></i>
                        </button>

                        <!-- Profile Avatar -->
                        <Link
                            href="/student/profile"
                            class="btn btn-avatar rounded-circle"
                        >
                            <span class="avatar-initial">{{
                                $page.props.auth?.student?.name?.charAt(0).toUpperCase()
                            }}</span>
                        </Link>
                    </div>
                </div>

                <!-- Right Side Mobile -->
                <div v-if="!isExamPage" class="d-flex flex-row align-items-center d-lg-none mobile-actions">
                    <!-- Logout Button -->
                    <button
                        @click="handleLogout"
                        class="btn btn-sm btn-danger shadow me-2"
                    >
                        <i class="fa fa-sign-out-alt"></i>
                    </button>

                    <!-- Mobile Profile Link -->
                    <Link
                        href="/student/profile"
                        class="btn btn-avatar rounded-circle"
                    >
                        <span class="avatar-initial">{{
                            $page.props.auth?.student?.name?.charAt(0).toUpperCase()
                        }}</span>
                    </Link>
                </div>
            </div>
        </nav>

        <!-- Content -->
        <div class="container">
            <slot />
        </div>
    </div>
</template>

<script>
import { Link, router, usePage } from '@inertiajs/vue3';
import Swal from 'sweetalert2';
import { computed } from 'vue';

export default {
    components: {
        Link,
    },
    props: {
        auth: Object,
    },
    setup() {
        const page = usePage();
        
        const isExamPage = computed(() => {
            const url = page.url;
            return url.includes('/student/exam/') && !url.includes('/student/exam-confirmation/') && !url.includes('/student/exam-result/');
        });

        const handleLogout = () => {
            Swal.fire({
                title: 'Konfirmasi Logout',
                text: 'Apakah Anda yakin ingin keluar dari akun ini?',
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Ya, Logout',
                cancelButtonText: 'Batal',
            }).then((result) => {
                if (result.isConfirmed) {
                    router.post('/student/logout');
                }
            });
        };

        return { handleLogout, isExamPage };
    },
};
</script>

<style scoped>
.navbar-transparent {
    background: #1a2332 !important;
}

.btn-avatar {
    width: 40px;
    height: 40px;
    min-width: 40px;
    max-width: 40px;
    flex: 0 0 40px;
    aspect-ratio: 1 / 1;
    padding: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    background: #4e73df;
    border: 2px solid rgba(255, 255, 255, 0.2);
    transition: all 0.3s ease;
    text-decoration: none;
    cursor: pointer;
}

.btn-avatar:hover {
    background: #2e59d9;
    border-color: rgba(255, 255, 255, 0.4);
    transform: scale(1.05);
}

.avatar-initial {
    color: white;
    font-weight: 700;
    font-size: 18px;
    line-height: 1;
    text-transform: uppercase;
}

.btn-avatar:focus {
    box-shadow: 0 0 0 0.2rem rgba(78, 115, 223, 0.5);
}

/* Brand text styling */
.brand-text-desktop {
    font-size: 1.25rem;
    letter-spacing: 0.3px;
}

.brand-text-mobile {
    font-size: 1.3rem;
    letter-spacing: 0.5px;
}

@media (max-width: 991.98px) {
    .navbar-collapse {
        background: #1a2332;
        padding: 20px;
        border-radius: 12px;
        margin-top: 12px;
        border: 1px solid rgba(255, 255, 255, 0.1);
    }
    
    .mobile-actions {
        display: flex !important;
        flex-direction: row !important;
        gap: 8px;
    }
}

@media (max-width: 768px) {
    .navbar-brand img {
        height: 55px !important;
    }
    
    .navbar-brand {
        display: flex;
        align-items: center;
        gap: 8px;
    }
}

@media (max-width: 576px) {
    .navbar-brand img {
        height: 45px !important;
    }

    .navbar {
        padding: 10px 0 !important;
        margin-bottom: 1rem !important;
    }

    .navbar-collapse {
        padding: 15px;
    }

    .btn-avatar {
        width: 35px;
        height: 35px;
        min-height: 35px !important;
        min-width: 35px;
        max-width: 35px;
        flex-basis: 35px;
        border-radius: 50% !important;
        padding: 0 !important;
    }

    .avatar-initial {
        font-size: 15px;
    }

    .mobile-actions .btn-danger {
        width: 35px;
        height: 35px;
        padding: 0;
        display: inline-flex;
        align-items: center;
        justify-content: center;
    }
}
</style>

<style>
/* Sembunyikan navbar saat overlay aktif (blocked / fullscreen warning) */
body.overlay-active .navbar {
    display: none !important;
}
</style>
