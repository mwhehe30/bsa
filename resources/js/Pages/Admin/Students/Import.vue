<template>
    <Head>
        <title>Import Siswa - Buweuk Sipit Academy</title>
    </Head>

    <!-- Drag & Drop Overlay -->
    <div
        v-if="isDragging"
        class="drag-overlay d-flex align-items-center justify-content-center"
    >
        <div class="text-center text-white pointer-events-none">
            <i class="fa fa-cloud-upload-alt fa-5x mb-3"></i>
            <h2>Lepaskan file di sini</h2>
            <p>Untuk langsung memilih dokumen import</p>
        </div>
    </div>
    <div class="container-fluid mt-5 mb-5">
        <div class="row">
            <div class="col-md-12">
                <Link
                    href="/admin/students"
                    class="btn btn-md btn-primary me-3 mb-3 border-0 shadow"
                    type="button"
                >
                    <i class="fa fa-chevron-left me-2"></i> Kembali
                </Link>
                <a
                    href="/assets/excel/students.xlsx"
                    target="_blank"
                    class="btn btn-md btn-success mb-3 border-0 text-white shadow"
                    type="button"
                >
                    <i class="fa fa-file-excel me-2"></i> Contoh Format
                </a>
                <div class="card border-0 shadow">
                    <div class="card-body">
                        <h5><i class="fa fa-user"></i> Import Siswa</h5>
                        <hr />
                        <form @submit.prevent="submit">
                            <div class="mb-4">
                                <label>File Excel</label>
                                <input
                                    type="file"
                                    class="form-control"
                                    ref="fileInput"
                                    @change="form.file = $event.target.files[0]"
                                />
                                <div
                                    v-if="errors.file"
                                    class="alert alert-danger mt-2"
                                >
                                    {{ errors.file }}
                                </div>
                                <div
                                    v-if="errors[0]"
                                    class="alert alert-danger mt-2"
                                >
                                    {{ errors[0] }}
                                </div>
                            </div>

                            <div class="alert alert-info">
                                <i class="fa fa-info-circle me-2"></i>
                                Format:
                                <strong
                                    >name, email, gender (L/P), password</strong
                                >
                            </div>

                            <button
                                type="submit"
                                class="btn btn-md btn-primary me-2 border-0 shadow"
                            >
                                Upload
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
import { reactive, ref, onMounted, onUnmounted } from 'vue';
import Swal from 'sweetalert2';

export default {
    layout: LayoutAdmin,
    components: { Head, Link },
    props: {
        errors: Object,
    },
    setup() {
        const fileInput = ref(null);
        const isDragging = ref(false);
        let dragCounter = 0;

        const form = reactive({
            file: '',
        });

        const onDragEnter = (e) => {
            e.preventDefault();
            dragCounter++;
            isDragging.value = true;
        };

        const onDragLeave = (e) => {
            e.preventDefault();
            dragCounter--;
            if (dragCounter === 0) {
                isDragging.value = false;
            }
        };

        const onDragOver = (e) => {
            e.preventDefault();
        };

        const onDrop = (e) => {
            e.preventDefault();
            dragCounter = 0;
            isDragging.value = false;
            const files = e.dataTransfer.files;
            if (files.length > 0) {
                form.file = files[0];
                if (fileInput.value) {
                    fileInput.value.files = files; // update native input
                }
            }
        };

        onMounted(() => {
            window.addEventListener('dragenter', onDragEnter);
            window.addEventListener('dragleave', onDragLeave);
            window.addEventListener('dragover', onDragOver);
            window.addEventListener('drop', onDrop);
        });

        onUnmounted(() => {
            window.removeEventListener('dragenter', onDragEnter);
            window.removeEventListener('dragleave', onDragLeave);
            window.removeEventListener('dragover', onDragOver);
            window.removeEventListener('drop', onDrop);
        });

        const submit = () => {
            router.post(
                '/admin/students/import',
                { file: form.file },
                {
                    onSuccess: () => {
                        Swal.fire({
                            title: 'Success!',
                            text: 'Import Siswa Berhasil Disimpan!.',
                            icon: 'success',
                            showConfirmButton: false,
                            timer: 2000,
                        });
                    },
                },
            );
        };

        return { form, submit, fileInput, isDragging };
    },
};
</script>

<style scoped>
.drag-overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(79, 70, 229, 0.9);
    z-index: 9999;
    outline: 4px dashed #fff;
    outline-offset: -20px;
}
.pointer-events-none {
    pointer-events: none;
}
</style>
