<template>
    <Head>
        <title>Edit Siswa - Buweuk Sipit Academy</title>
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
                        <h5><i class="fa fa-user"></i> Edit Siswa</h5>
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
                                            <option value="L">
                                                Laki - Laki
                                            </option>
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
                                <div class="col-md-6">
                                    <div class="mb-4">
                                        <label>Status Akun</label>
                                        <div
                                            class="form-check form-switch mt-2"
                                        >
                                            <input
                                                class="form-check-input"
                                                type="checkbox"
                                                id="statusSwitch"
                                                v-model="form.is_active"
                                                :true-value="true"
                                                :false-value="false"
                                                style="
                                                    cursor: pointer;
                                                    width: 50px;
                                                    height: 25px;
                                                "
                                            />
                                            <label
                                                class="form-check-label ms-2"
                                                for="statusSwitch"
                                            >
                                                <span
                                                    v-if="form.is_active"
                                                    class="badge bg-success"
                                                    >Aktif</span
                                                >
                                                <span
                                                    v-else
                                                    class="badge bg-secondary"
                                                    >Nonaktif</span
                                                >
                                            </label>
                                        </div>
                                        <small class="text-muted"
                                            >Siswa nonaktif tidak bisa
                                            login.</small
                                        >
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
                                        <small class="text-muted d-block mt-1">
                                            Jika tidak dicentang dan field password kosong, password tidak akan diubah
                                        </small>
                                    </div>
                                </div>
                            </div>

                            <div v-if="!useAutoPassword" class="row">
                                <div class="col-md-6">
                                    <div class="mb-4">
                                        <label
                                            >Password (Kosongkan jika tidak
                                            diubah)</label
                                        >
                                        <input
                                            type="password"
                                            class="form-control"
                                            placeholder="Masukkan Password Baru"
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
                            >
                                Update
                            </button>
                            <button
                                type="reset"
                                class="btn btn-md btn-warning border-0 shadow"
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
import { Head, Link, router } from '@inertiajs/vue3';
import { reactive, ref } from 'vue';
import Swal from 'sweetalert2';

export default {
    layout: LayoutAdmin,
    components: { Head, Link },
    props: {
        errors: Object,
        student: Object,
    },
    setup(props) {
        const form = reactive({
            name: props.student.name,
            email: props.student.email,
            gender: props.student.gender,
            is_active: props.student.is_active,
            password: '',
            password_confirmation: '',
        });

        const useAutoPassword = ref(false); // Default: manual (tidak ubah password)

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
            router.put(`/admin/students/${props.student.id}`, form, {
                onSuccess: () => {
                    Swal.fire({
                        title: 'Success!',
                        text: 'Siswa Berhasil Diupdate!.',
                        icon: 'success',
                        showConfirmButton: false,
                        timer: 2000,
                    });
                },
            });
        };

        return { form, useAutoPassword, toggleAutoPassword, submit };
    },
};
</script>
