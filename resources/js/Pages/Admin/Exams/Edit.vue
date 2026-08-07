<template>
    <Head>
        <title>Edit Ujian - Buweuk Sipit Academy</title>
    </Head>
    <div class="container-fluid mt-5 mb-5">
        <div class="row">
            <div class="col-md-12">
                <Link
                    href="/admin/exams"
                    class="btn btn-md btn-primary mb-3 border-0 shadow"
                    type="button"
                >
                    <i class="fa fa-chevron-left me-2"></i> Kembali
                </Link>
                <div class="card border-0 shadow">
                    <div class="card-body">
                        <h5><i class="fa fa-edit"></i> Edit Ujian</h5>
                        <hr />
                        <form @submit.prevent="submit">
                            <div class="mb-4">
                                <label>Nama Ujian</label>
                                <input
                                    type="text"
                                    class="form-control"
                                    placeholder="Masukkan Nama Ujian"
                                    v-model="form.title"
                                />
                                <div
                                    v-if="errors.title"
                                    class="alert alert-danger mt-2"
                                >
                                    {{ errors.title }}
                                </div>
                            </div>

                            <!-- STEP 1: Pilih Kategori -->
                            <div class="mb-4">
                                <label>Kategori</label>
                                <div class="d-flex gap-3">
                                    <div class="form-check">
                                        <input
                                            class="form-check-input"
                                            type="radio"
                                            value="psikologi"
                                            v-model="form.category"
                                            id="category-psikologi"
                                        />
                                        <label
                                            class="form-check-label"
                                            for="category-psikologi"
                                            >Psikologi</label
                                        >
                                    </div>
                                    <div class="form-check">
                                        <input
                                            class="form-check-input"
                                            type="radio"
                                            value="akademik"
                                            v-model="form.category"
                                            id="category-akademik"
                                        />
                                        <label
                                            class="form-check-label"
                                            for="category-akademik"
                                            >Akademik</label
                                        >
                                    </div>
                                </div>
                            </div>

                            <!-- STEP 2: Pilih Mapel -->
                            <div class="mb-4">
                                <label>Mata Pelajaran</label>
                                <select
                                    class="form-select"
                                    v-model="form.lesson_id"
                                >
                                    <option value="">-- Pilih Mapel --</option>
                                    <optgroup
                                        v-if="form.category === 'psikologi'"
                                        label="Psikologi"
                                    >
                                        <option
                                            v-for="lesson in lessons.psikologi"
                                            :key="lesson.id"
                                            :value="lesson.id"
                                        >
                                            {{ lesson.name }}
                                        </option>
                                    </optgroup>
                                    <optgroup
                                        v-if="form.category === 'akademik'"
                                        label="Akademik"
                                    >
                                        <option
                                            v-for="lesson in lessons.akademik"
                                            :key="lesson.id"
                                            :value="lesson.id"
                                        >
                                            {{ lesson.name }}
                                        </option>
                                    </optgroup>
                                </select>
                                <div
                                    v-if="errors.lesson_id"
                                    class="alert alert-danger mt-2"
                                >
                                    {{ errors.lesson_id }}
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-4">
                                        <label>Durasi (Menit)</label>
                                        <input
                                            type="number"
                                            min="1"
                                            class="form-control"
                                            placeholder="Masukkan Durasi"
                                            v-model="form.duration"
                                        />
                                        <div
                                            v-if="errors.duration"
                                            class="alert alert-danger mt-2"
                                        >
                                            {{ errors.duration }}
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="mb-4">
                                <label>Deskripsi</label>
                                <Editor
                                    :api-key="TinyMCEApiKey"
                                    v-model="form.description"
                                    :init="{
                                        menubar: false,
                                        plugins: 'lists link image emoticons',
                                        toolbar:
                                            'styleselect | bold italic | alignleft aligncenter alignright alignjustify | bullist numlist | link image emoticons',
                                    }"
                                />
                                <div
                                    v-if="errors.description"
                                    class="alert alert-danger mt-2"
                                >
                                    {{ errors.description }}
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-4">
                                    <div class="mb-4">
                                        <label>Acak Soal</label>
                                        <select
                                            class="form-select"
                                            v-model="form.random_question"
                                        >
                                            <option value="Y">Ya</option>
                                            <option value="N">Tidak</option>
                                        </select>
                                        <div
                                            v-if="errors.random_question"
                                            class="alert alert-danger mt-2"
                                        >
                                            {{ errors.random_question }}
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="mb-4">
                                        <label>Acak Jawaban</label>
                                        <select
                                            class="form-select"
                                            v-model="form.random_answer"
                                        >
                                            <option value="Y">Ya</option>
                                            <option value="N">Tidak</option>
                                        </select>
                                        <div
                                            v-if="errors.random_answer"
                                            class="alert alert-danger mt-2"
                                        >
                                            {{ errors.random_answer }}
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="mb-4">
                                        <label>Tampilkan Hasil</label>
                                        <select
                                            class="form-select"
                                            v-model="form.show_answer"
                                        >
                                            <option value="Y">Ya</option>
                                            <option value="N">Tidak</option>
                                        </select>
                                        <div
                                            v-if="errors.show_answer"
                                            class="alert alert-danger mt-2"
                                        >
                                            {{ errors.show_answer }}
                                        </div>
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
import { reactive, watch } from 'vue';
import Swal from 'sweetalert2';
import Editor from '@tinymce/tinymce-vue';

export default {
    layout: LayoutAdmin,
    components: { Head, Link, Editor },
    props: {
        errors: Object,
        exam: Object,
        lessons: Object,
        TinyMCEApiKey: String,
    },
    setup(props) {
        const form = reactive({
            title: props.exam.title,
            category: props.exam.lesson.category,
            lesson_id: props.exam.lesson_id,
            duration: props.exam.duration,
            description: props.exam.description,
            random_question: props.exam.random_question,
            random_answer: props.exam.random_answer,
            show_answer: props.exam.show_answer,
        });

        // Reset lesson_id jika category berubah
        watch(
            () => form.category,
            () => {
                form.lesson_id = '';
            },
        );

        const submit = () => {
            router.put(`/admin/exams/${props.exam.id}`, form, {
                onSuccess: () => {
                    Swal.fire({
                        title: 'Success!',
                        text: 'Ujian Berhasil Diupdate!.',
                        icon: 'success',
                        showConfirmButton: false,
                        timer: 2000,
                    });
                },
            });
        };

        return { form, submit };
    },
};
</script>

