<template>
    <Head>
        <title>Profile Siswa - Buweuk Sipit Academy</title>
    </Head>

    <div class="page-wrap">
        <div class="row justify-content-center">
            <div class="col-12 col-md-8 col-lg-6">
                <!-- Tombol Kembali -->
                <div class="mb-4 d-flex justify-content-start">
                    <Link
                        href="/student/dashboard"
                        class="btn-flat btn-flat-secondary btn-sm"
                    >
                        <i class="fa fa-chevron-left me-1"></i> Kembali
                    </Link>
                </div>

                <div class="flat-card">
                    <div class="flat-header">
                        <h5 class="flat-title mb-0">
                            <i class="fa fa-user-circle me-2 text-indigo"></i>Profile Siswa
                        </h5>
                    </div>
                    <div class="card-body p-4">
                        <!-- Info Profile -->
                        <div class="info-table mb-4">
                            <div class="info-row">
                                <span class="info-label">Nama</span>
                                <span class="info-value">{{ auth.student.name }}</span>
                            </div>
                            <div class="info-row">
                                <span class="info-label">Email</span>
                                <span class="info-value">{{ auth.student.email }}</span>
                            </div>
                            <div class="info-row">
                                <span class="info-label">Jenis Kelamin</span>
                                <span class="info-value">
                                    {{ auth.student.gender === 'L' ? 'Laki-laki' : 'Perempuan' }}
                                </span>
                            </div>
                        </div>

                        <hr class="divider-line my-4" />

                        <!-- Form Ganti Password -->
                        <h6 class="form-section-title mb-3">
                            <i class="fa fa-key me-2 text-indigo"></i>Ganti Password
                        </h6>
                        <form @submit.prevent="submit">
                            <div class="mb-3">
                                <label class="form-label-flat">Password Lama</label>
                                <div class="input-group-flat">
                                    <span class="input-icon"><i class="fa fa-lock"></i></span>
                                    <input
                                        type="password"
                                        class="form-control-flat"
                                        v-model="form.current_password"
                                        placeholder="Masukkan password lama"
                                    />
                                </div>
                                <div
                                    v-if="errors.current_password"
                                    class="text-danger small mt-1"
                                >
                                    {{ errors.current_password }}
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label-flat">Password Baru</label>
                                <div class="input-group-flat">
                                    <span class="input-icon"><i class="fa fa-key"></i></span>
                                    <input
                                        type="password"
                                        class="form-control-flat"
                                        v-model="form.new_password"
                                        placeholder="Minimal 6 karakter"
                                    />
                                </div>
                                <div
                                    v-if="errors.new_password"
                                    class="text-danger small mt-1"
                                >
                                    {{ errors.new_password }}
                                </div>
                            </div>

                            <div class="mb-4">
                                <label class="form-label-flat">Konfirmasi Password Baru</label>
                                <div class="input-group-flat">
                                    <span class="input-icon"><i class="fa fa-check-circle"></i></span>
                                    <input
                                        type="password"
                                        class="form-control-flat"
                                        v-model="form.new_password_confirmation"
                                        placeholder="Ulangi password baru"
                                    />
                                </div>
                            </div>

                            <div
                                v-if="$page.props.flash?.success"
                                class="flat-alert alert-success-flat mb-3"
                            >
                                <i class="fa fa-check-circle me-2"></i>
                                {{ $page.props.flash.success }}
                            </div>

                            <button
                                type="submit"
                                class="btn-flat btn-flat-primary w-100 justify-content-center py-2"
                                :disabled="loading"
                            >
                                <span v-if="loading">
                                    <span
                                        class="spinner-border spinner-border-sm me-2"
                                    ></span>
                                    Loading...
                                </span>
                                <span v-else>
                                    <i class="fa fa-save me-2"></i> Update Password
                                </span>
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import LayoutStudent from '../../../Layouts/Student.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { reactive, ref } from 'vue';
import Swal from 'sweetalert2';

export default {
    layout: LayoutStudent,
    components: { Head, Link },
    props: {
        errors: Object,
        auth: Object,
    },
    setup() {
        const form = reactive({
            current_password: '',
            new_password: '',
            new_password_confirmation: '',
        });
        const loading = ref(false);

        const submit = () => {
            loading.value = true;
            router.put('/student/update-password', form, {
                preserveScroll: true,
                onFinish: () => {
                    loading.value = false;
                },
                onSuccess: () => {
                    form.current_password = '';
                    form.new_password = '';
                    form.new_password_confirmation = '';
                    Swal.fire({
                        title: 'Success!',
                        text: 'Password berhasil diubah.',
                        icon: 'success',
                        timer: 2000,
                        showConfirmButton: false,
                    });
                },
            });
        };

        return { form, submit, loading };
    },
};
</script>

<style scoped>
.page-wrap {
    font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
    padding: 32px 0 64px;
}

/* ── Flat card ──────────────────────────────────── */
.flat-card {
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 16px;
    overflow: hidden;
}

.flat-header {
    background: #f8fafc;
    border-bottom: 1px solid #e2e8f0;
    padding: 16px 24px;
}

.flat-title {
    font-size: 1rem;
    font-weight: 600;
    color: #1e293b;
}

.text-indigo { color: #1A2332; }

/* ── Info table ─────────────────────────────────── */
.info-table {
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    overflow: hidden;
}

.info-row {
    display: flex;
    align-items: center;
    padding: 12px 16px;
    border-bottom: 1px solid #f1f5f9;
    gap: 12px;
}

.info-row:last-child {
    border-bottom: none;
}

.info-label {
    width: 120px;
    flex-shrink: 0;
    font-size: 0.85rem;
    font-weight: 600;
    color: #475569;
}

.info-value {
    font-size: 0.9rem;
    color: #1e293b;
}

.divider-line {
    border-top: 1px solid #e2e8f0;
}

/* ── Form Inputs ────────────────────────────────── */
.form-section-title {
    font-size: 0.9rem;
    font-weight: 600;
    color: #475569;
}

.form-label-flat {
    font-size: 0.8rem;
    font-weight: 600;
    color: #475569;
    margin-bottom: 6px;
}

.input-group-flat {
    display: flex;
    border: 1px solid #cbd5e1;
    border-radius: 8px;
    overflow: hidden;
    background: #ffffff;
    transition: border-color 0.15s ease;
}

.input-group-flat:focus-within {
    border-color: #1A2332;
}

.input-icon {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 40px;
    background: #f8fafc;
    color: #64748b;
    border-right: 1px solid #cbd5e1;
}

.form-control-flat {
    flex: 1;
    border: none;
    padding: 8px 12px;
    font-size: 0.9rem;
    color: #1e293b;
    outline: none;
}

.form-control-flat::placeholder {
    color: #94a3b8;
}

/* ── Alerts ─────────────────────────────────────── */
.flat-alert {
    border-radius: 12px;
    padding: 14px 18px;
    font-size: 0.88rem;
}

.alert-success-flat {
    background: #f0fdf4;
    color: #15803d;
    border: 1px solid #bbf7d0;
}

/* ── Buttons ─────────────────────────────────────── */
.btn-flat {
    display: inline-flex;
    align-items: center;
    font-size: 0.875rem;
    font-weight: 600;
    padding: 9px 20px;
    border-radius: 8px;
    border: none;
    cursor: pointer;
    text-decoration: none;
    transition: background 0.15s ease;
}

.btn-flat-primary {
    background: #1A2332;
    color: #fff;
}

.btn-flat-primary:hover {
    background: #1A2332;
    color: #fff;
}

.btn-flat-secondary {
    background: #f1f5f9;
    color: #475569;
}

.btn-flat-secondary:hover {
    background: #e2e8f0;
    color: #1e293b;
}

.btn-flat-secondary.btn-sm {
    padding: 6px 12px;
    font-size: 0.8rem;
}

/* ── Mobile Responsive ───────────────────────────── */
@media (max-width: 575.98px) {
    .page-wrap {
        padding: 16px 0 48px;
    }

    .info-row {
        flex-direction: column;
        align-items: flex-start;
        gap: 4px;
    }

    .info-label {
        width: auto;
    }

    .info-value {
        width: 100%;
        overflow-wrap: anywhere;
    }
}
</style>
