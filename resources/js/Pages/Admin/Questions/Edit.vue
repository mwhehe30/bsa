<template>
    <Head>
        <title>Edit Soal Ujian - Buweuk Sipit Academy</title>
    </Head>
    <div class="container-fluid mt-5 mb-5">
        <div class="row">
            <div class="col-md-12">
                <Link
                    :href="`/admin/exams/${exam.id}`"
                    class="btn btn-md btn-primary mb-3 border-0 shadow"
                    type="button"
                    ><i class="fa fa-chevron-left me-2"></i>
                    Kembali</Link
                >
                <div class="card border-0 shadow">
                    <div class="card-body">
                        <h5>
                            <i class="fa fa-question-circle"></i> Edit Soal Ujian
                        </h5>
                        <h6 v-if="isPersonality" class="text-muted">
                            <i class="fa fa-info-circle me-1"></i> Tipe: Kepribadian
                        </h6>
                        <hr />
                        <form @submit.prevent="submit">
                            <div class="table-responsive mb-4">
                                <table class="table-bordered table-centered table-nowrap mb-0 table rounded">
                                    <tbody>
                                        <tr>
                                            <td style="width: 20%" class="fw-bold">Soal</td>
                                            <td>
                                                <Editor
                                                    :api-key="TinyMCEApiKey"
                                                    v-model="form.question"
                                                    :init="editorConfig(300)"
                                                />
                                            </td>
                                        </tr>
                                        <tr>
                                            <td style="width: 20%" class="fw-bold">Pilihan A</td>
                                            <td>
                                                <Editor :api-key="TinyMCEApiKey" v-model="form.option_1" :init="editorConfig(130)" />
                                            </td>
                                        </tr>
                                        <tr>
                                            <td style="width: 20%" class="fw-bold">Pilihan B</td>
                                            <td>
                                                <Editor :api-key="TinyMCEApiKey" v-model="form.option_2" :init="editorConfig(130)" />
                                            </td>
                                        </tr>
                                        <tr>
                                            <td style="width: 20%" class="fw-bold">Pilihan C</td>
                                            <td>
                                                <Editor :api-key="TinyMCEApiKey" v-model="form.option_3" :init="editorConfig(130)" />
                                            </td>
                                        </tr>
                                        <tr>
                                            <td style="width: 20%" class="fw-bold">Pilihan D</td>
                                            <td>
                                                <Editor :api-key="TinyMCEApiKey" v-model="form.option_4" :init="editorConfig(130)" />
                                            </td>
                                        </tr>
                                        <tr>
                                            <td style="width: 20%" class="fw-bold">Pilihan E</td>
                                            <td>
                                                <Editor :api-key="TinyMCEApiKey" v-model="form.option_5" :init="editorConfig(130)" />
                                            </td>
                                        </tr>
                                        <tr v-if="!isPersonality">
                                            <td style="width: 20%" class="fw-bold">Jawaban Benar</td>
                                            <td>
                                                <select class="form-control" v-model="form.answer">
                                                    <option value="1">A</option>
                                                    <option value="2">B</option>
                                                    <option value="3">C</option>
                                                    <option value="4">D</option>
                                                    <option value="5">E</option>
                                                </select>
                                            </td>
                                        </tr>
                                        <tr v-if="isPersonality">
                                            <td style="width: 20%" class="fw-bold">Point Pilihan</td>
                                            <td>
                                                <div class="row">
                                                    <div class="col-md-2">
                                                        <label>A</label>
                                                        <input type="number" class="form-control" v-model="form.point_1" min="1" max="5" />
                                                    </div>
                                                    <div class="col-md-2">
                                                        <label>B</label>
                                                        <input type="number" class="form-control" v-model="form.point_2" min="1" max="5" />
                                                    </div>
                                                    <div class="col-md-2">
                                                        <label>C</label>
                                                        <input type="number" class="form-control" v-model="form.point_3" min="1" max="5" />
                                                    </div>
                                                    <div class="col-md-2">
                                                        <label>D</label>
                                                        <input type="number" class="form-control" v-model="form.point_4" min="1" max="5" />
                                                    </div>
                                                    <div class="col-md-2">
                                                        <label>E</label>
                                                        <input type="number" class="form-control" v-model="form.point_5" min="1" max="5" />
                                                    </div>
                                                </div>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>

                            <button type="submit" class="btn btn-md btn-primary me-2 border-0 shadow">
                                Simpan
                            </button>
                            <button type="reset" class="btn btn-md btn-warning border-0 shadow">
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
import { reactive, computed } from 'vue';
import Swal from 'sweetalert2';
import Editor from '@tinymce/tinymce-vue';

export default {
    layout: LayoutAdmin,
    components: { Head, Link, Editor },
    props: {
        errors: Object,
        exam: Object,
        question: Object,
        TinyMCEApiKey: String,
    },
    setup(props) {
        const isPersonality = computed(() => {
            const name = props.exam?.lesson?.name;
            if (!name || typeof name !== 'string') return false;
            const normalized = name.toLowerCase().trim();
            return normalized === 'kepribadian' || normalized.startsWith('kepribadian ');
        });

        // Ambil CSRF token dari meta tag yang disisipkan Laravel
        const getCsrfToken = () =>
            document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '';

        /**
         * Konfigurasi TinyMCE dengan dukungan upload gambar dari laptop.
         * Klik icon gambar → pilih file dari laptop → otomatis upload ke server
         * → gambar langsung muncul di editor dan tersimpan sebagai <img src="..."> di DB.
         */
        const editorConfig = (height = 250) => ({
            height,
            menubar: false,
            plugins: 'lists link image emoticons table',
            toolbar:
                'styleselect | bold italic | ' +
                'alignleft aligncenter alignright alignjustify | ' +
                'bullist numlist | table | link image emoticons',
            // Upload gambar dari laptop ke server
            automatic_uploads: true,
            images_reuse_filename: false,
            file_picker_types: 'image',
            // Handler custom: kirim CSRF token agar Laravel tidak tolak request
            images_upload_handler: (blobInfo, progress) =>
                new Promise((resolve, reject) => {
                    const formData = new FormData();
                    formData.append('file', blobInfo.blob(), blobInfo.filename());

                    const xhr = new XMLHttpRequest();
                    xhr.open('POST', '/admin/upload-image');
                    xhr.setRequestHeader('X-CSRF-TOKEN', getCsrfToken());
                    xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');

                    xhr.upload.onprogress = (e) => {
                        if (e.lengthComputable) {
                            progress(Math.round((e.loaded / e.total) * 100));
                        }
                    };

                    xhr.onload = () => {
                        if (xhr.status < 200 || xhr.status >= 300) {
                            reject({ message: 'Upload gagal: HTTP ' + xhr.status, remove: true });
                            return;
                        }
                        try {
                            const json = JSON.parse(xhr.responseText);
                            if (!json.location) {
                                reject({ message: 'Respon tidak valid dari server', remove: true });
                                return;
                            }
                            resolve(json.location);
                        } catch {
                            reject({ message: 'Gagal parsing respon server', remove: true });
                        }
                    };

                    xhr.onerror = () =>
                        reject({ message: 'Upload gagal (koneksi error)', remove: true });

                    xhr.send(formData);
                }),
            // Izinkan semua atribut HTML agar tag <img> tersimpan utuh
            valid_elements: '*[*]',
            extended_valid_elements: 'img[*]',
        });

        const points = props.question.points || { 1: 5, 2: 4, 3: 3, 4: 2, 5: 1 };

        const form = reactive({
            question: props.question.question,
            option_1: props.question.option_1,
            option_2: props.question.option_2,
            option_3: props.question.option_3,
            option_4: props.question.option_4,
            option_5: props.question.option_5,
            answer: props.question.answer,
            point_1: points['1'] || 5,
            point_2: points['2'] || 4,
            point_3: points['3'] || 3,
            point_4: points['4'] || 2,
            point_5: points['5'] || 1,
        });

        const submit = () => {
            const data = {
                question: form.question,
                option_1: form.option_1,
                option_2: form.option_2,
                option_3: form.option_3,
                option_4: form.option_4,
                option_5: form.option_5,
            };

            if (!isPersonality.value) {
                data.answer = form.answer;
            }

            if (isPersonality.value) {
                data.point_1 = form.point_1;
                data.point_2 = form.point_2;
                data.point_3 = form.point_3;
                data.point_4 = form.point_4;
                data.point_5 = form.point_5;
            }

            router.put(
                `/admin/exams/${props.exam.id}/questions/${props.question.id}/update`,
                data,
                {
                    onSuccess: () => {
                        Swal.fire({
                            title: 'Berhasil!',
                            text: 'Soal Ujian Berhasil Diupdate!',
                            icon: 'success',
                            showConfirmButton: false,
                            timer: 2000,
                        });
                    },
                },
            );
        };

        return { form, submit, isPersonality, editorConfig };
    },
};
</script>
