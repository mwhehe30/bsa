<template>
    <Head>
        <title>Import Soal dari Word - Buweuk Sipit Academy</title>
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
                    :href="`/admin/exams/${exam.id}`"
                    class="btn btn-md btn-primary me-3 mb-3 border-0 shadow"
                    type="button"
                >
                    <i class="fa fa-chevron-left me-2"></i> Kembali
                </Link>
                <a
                    :href="templateFileUrl"
                    target="_blank"
                    class="btn btn-md btn-success mb-3 border-0 text-white shadow"
                    type="button"
                >
                    <i class="fa fa-file-word me-2"></i> Download Template
                </a>
                <div class="card border-0 shadow">
                    <div class="card-body">
                        <h5>
                            <i class="fa fa-file-word"></i> Import Soal dari Word
                        </h5>
                        <h6
                            v-if="isPersonality"
                            class="text-muted"
                        >
                            <i class="fa fa-info-circle me-1"></i> Tipe:
                            Kepribadian - Point default otomatis (A=5, B=4, C=3,
                            D=2, E=1)
                        </h6>
                        <h6
                            v-else
                            class="text-muted"
                        >
                            <i class="fa fa-info-circle me-1"></i> Tipe: Pilihan
                            Ganda - Pastikan ada <code>Jawaban: A/B/C/D/E</code>
                        </h6>
                        <hr />

                        <div class="alert alert-info">
                            <i class="fa fa-info-circle me-2"></i>
                            <strong>Format yang didukung:</strong>
                            <ul class="mt-2 mb-0">
                                <li>
                                    Sistem mendukung list otomatis bawaan Word (Auto-numbering / Bullets).
                                </li>
                                <li>
                                    Jika tidak pakai list otomatis, format nomor harus diikuti titik:
                                    <code>1. Apa ibu kota Indonesia?</code>
                                </li>
                                <li>
                                    Pilihan A-E: <code>A. Jakarta</code>,
                                    <code>B. Bandung</code>, dst
                                </li>
                                <li v-if="!isPersonality">
                                    Jawaban ditandai dengan: <code>Jawaban: A</code> (dapat juga menggunakan Pembahasan).
                                </li>
                            </ul>
                        </div>

                        <div
                            v-if="$page.props.flash?.error"
                            class="alert alert-danger"
                        >
                            <i class="fa fa-exclamation-circle me-2"></i>
                            {{ $page.props.flash.error }}
                        </div>
                        <div
                            v-if="$page.props.flash?.success"
                            class="alert alert-success"
                        >
                            <i class="fa fa-check-circle me-2"></i>
                            {{ $page.props.flash.success }}
                        </div>

                        <form @submit.prevent="submit">
                            <div class="mb-4">
                                <label>File Word (.docx)</label>
                                <input
                                    type="file"
                                    class="form-control"
                                    accept=".doc,.docx"
                                    ref="fileInput"
                                    @change="handleFileChange"
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

                            <button
                                type="submit"
                                class="btn btn-md btn-primary border-0 shadow"
                                :disabled="loading"
                            >
                                <span v-if="loading">
                                    <i class="fa fa-spinner fa-spin me-2"></i>
                                    Mengimport...
                                </span>
                                <span v-else>
                                    <i class="fa fa-upload me-2"></i> Upload
                                    &amp; Import
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
import LayoutAdmin from '../../../Layouts/Admin.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { reactive, ref, onMounted, onUnmounted, computed } from 'vue';
import Swal from 'sweetalert2';

export default {
    layout: LayoutAdmin,
    components: {
        Head,
        Link,
    },
    props: {
        errors: Object,
        exam: Object,
    },
    setup(props) {
        const form = reactive({
            file: null,
        });

        const isPersonality = computed(() => {
            const name = props.exam?.lesson?.name;
            if (!name || typeof name !== 'string') return false;
            const normalized = name.toLowerCase().trim();
            return normalized === 'kepribadian' || normalized.startsWith('kepribadian ');
        });

        const templateFileUrl = computed(() => {
            return isPersonality.value
                ? '/assets/word/contoh_soal_kepribadian.docx'
                : '/assets/word/contoh_soal_pilihan_ganda.docx';
        });

        const fileInput = ref(null);
        const isDragging = ref(false);
        let dragCounter = 0;

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
                if (fileInput.value) {
                    fileInput.value.files = files;
                }
                handleFileChange({ target: { files: files } });
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

        const loading = ref(false);

        const handleFileChange = (event) => {
            const file = event.target.files[0];
            if (!file) {
                return;
            }
            form.file = file;
        };

        const resetForm = () => {
            form.file = null;
            if (fileInput.value) {
                fileInput.value.value = '';
            }
        };

        const submit = () => {
            if (!form.file) {
                Swal.fire({
                    title: 'Peringatan!',
                    text: 'Pilih file terlebih dahulu.',
                    icon: 'warning',
                    confirmButtonText: 'OK',
                });
                return;
            }

            loading.value = true;

            const formData = new FormData();
            formData.append('file', form.file);

            router.post(
                `/admin/exams/${props.exam.id}/questions/import-word`,
                formData,
                {
                    preserveScroll: true,
                    onFinish: () => {
                        loading.value = false;
                    },
                    onSuccess: (page) => {
                        if (page.props.flash?.success) {
                            Swal.fire({
                                title: 'Success!',
                                text: page.props.flash.success,
                                icon: 'success',
                                confirmButtonText: 'OK',
                            });
                            resetForm();
                        }
                        if (page.props.flash?.error) {
                            Swal.fire({
                                title: 'Gagal!',
                                text: page.props.flash.error,
                                icon: 'error',
                                confirmButtonText: 'OK',
                            });
                        }
                    },
                    onError: (errors) => {
                        if (errors.file) {
                            Swal.fire({
                                title: 'Gagal!',
                                text: errors.file,
                                icon: 'error',
                                confirmButtonText: 'OK',
                            });
                        }
                    },
                },
            );
        };

        return {
            form,
            submit,
            loading,
            handleFileChange,
            resetForm,
            isPersonality,
            templateFileUrl,
            fileInput,
            isDragging,
        };
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
