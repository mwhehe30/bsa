<template>
    <Head>
        <title>Tambah Siswa - Buweuk Sipit Academy</title>
    </Head>
    <div class="container-fluid mt-5 mb-5">
        <div class="row">
            <div class="col-md-12">
                <Link
                    href="/admin/students"
                    class="btn btn-md btn-primary mb-3 border-0 shadow"
                    type="button"
                >
                    <i class="fa fa-chevron-left me-2"></i> Kembali
                </Link>
                <div class="card border-0 shadow">
                    <div class="card-body">
                        <h5><i class="fa fa-user-plus"></i> Tambah Siswa</h5>
                        <hr />
                        <form @submit.prevent="submit">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-4">
                                        <label>Nama Lengkap</label>
                                        <input
                                            type="text"
                                            class="form-control"
                                            placeholder="Masukkan Nama Siswa"
                                            v-model="form.name"
                                        />
                                        <div
                                            v-if="errors.name"
                                            class="alert alert-danger mt-2"
                                        >
                                            {{ errors.name }}
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-4">
                                        <label>Email</label>
                                        <input
                                            type="email"
                                            class="form-control"
                                            placeholder="Masukkan Email Siswa"
                                            v-model="form.email"
                                        />
                                        <div
                                            v-if="errors.email"
                                            class="alert alert-danger mt-2"
                                        >
                                            {{ errors.email }}
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-4">
                                        <label>Jenis Kelamin</label>
                                        <select
                                            class="form-select"
                                            v-model="form.gender"
                                        >
                                            <option value="">-- Pilih Jenis Kelamin --</option>
                                            <option value="L">Laki - Laki</option>
                                            <option value="P">Perempuan</option>
                                        </select>
                                        <div
                                            v-if="errors.gender"
                                            class="alert alert-danger mt-2"
                                        >
                                            {{ errors.gender }}
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-12">
                                    <div class="mb-4">
                                        <label class="d-flex align-items-center">
                                            <input 
                                                type="checkbox" 
                                                class="form-check-input me-2" 
                                                v-model="useAutoPassword"
                                                @change="toggleAutoPassword"
                                            />
                                            <span>Generate Password Otomatis (password: <code>password</code>)</span>
                                        </label>
                                    </div>
                                </div>
                            </div>

                            <div v-if="!useAutoPassword" class="row">
                                <div class="col-md-6">
                                    <div class="mb-4">
                                        <label>Password</label>
                                        <input
                                            type="password"
                                            class="form-control"
                                            placeholder="Masukkan Password"
                                            v-model="form.password"
                                        />
                                        <div
                                            v-if="errors.password"
                                            class="alert alert-danger mt-2"
                                        >
                                            {{ errors.password }}
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-4">
                                        <label>Konfirmasi Password</label>
                                        <input
                                            type="password"
                                            class="form-control"
                                            placeholder="Masukkan Konfirmasi Password"
                                            v-model="form.password_confirmation"
                                        />
                                    </div>
                                </div>
                            </div>

                            <button
                                type="submit"
                                class="btn btn-md btn-primary me-2 border-0 shadow"
                                :disabled="form.processing"
                            >
                                <span v-if="form.processing">
                                    <i class="fa fa-spinner fa-spin me-1"></i> Menyimpan...
                                </span>
                                <span v-else>Simpan</span>
                            </button>
                            <button
                                type="reset"
                                class="btn btn-md btn-warning border-0 shadow"
                                @click="resetForm"
                            >
                                Reset
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import LayoutAdmin from '../../../Layouts/Admin.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import Swal from 'sweetalert2';

export default {
    layout: LayoutAdmin,
    components: { Head, Link },
    props: {
        errors: Object,
    },
    setup() {
        const form = useForm({
            name: '',
            email: '',
            gender: '',
            password: 'password',
            password_confirmation: 'password',
        });

        const useAutoPassword = ref(true); // Default: auto-generate

        const toggleAutoPassword = () => {
            if (useAutoPassword.value) {
                // Set auto password
                form.password = 'password';
                form.password_confirmation = 'password';
            } else {
                // Clear for manual input
                form.password = '';
                form.password_confirmation = '';
            }
        };

        const submit = () => {
            form.post('/admin/students', {
                onSuccess: () => {
                    Swal.fire({
                        title: 'Berhasil!',
                        text: 'Siswa Berhasil Ditambahkan!',
                        icon: 'success',
                        showConfirmButton: false,
                        timer: 2000,
                    });
                },
            });
        };

        const resetForm = () => {
            form.reset();
            useAutoPassword.value = true;
            form.password = 'password';
            form.password_confirmation = 'password';
        };

        return { form, useAutoPassword, toggleAutoPassword, submit, resetForm };
    },
};
</script>
