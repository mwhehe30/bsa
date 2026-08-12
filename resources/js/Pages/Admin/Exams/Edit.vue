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

                            <div v-if="!selectedLessonIsKecermatan" class="mb-4">
                                <label>File Pembahasan</label>
                                <input
                                    type="file"
                                    ref="discussionFileInput"
                                    accept=".pdf,.docx"
                                    @change="handleDiscussionFileChange"
                                    class="form-control"
                                />
                                <small class="text-muted d-block mt-1">PDF atau Word (.docx). Upload baru akan mengganti file lama.</small>
                                <div
                                    v-if="errors.discussion_file"
                                    class="alert alert-danger mt-2"
                                >
                                    {{ errors.discussion_file }}
                                </div>
                                <div v-if="form.discussion_file" class="mt-2 d-flex align-items-center gap-2">
                                    <span class="badge bg-success"><i class="fa fa-file me-1"></i>{{ form.discussion_file.name }}</span>
                                    <button type="button" class="btn btn-sm btn-outline-danger" @click="clearDiscussionFile">
                                        <i class="fa fa-times me-1"></i> Hapus
                                    </button>
                                </div>
                                <div v-else-if="exam.discussion_file_name && !form.remove_discussion_file" class="mt-2 d-flex align-items-center gap-2">
                                    <span class="badge bg-secondary"><i class="fa fa-file me-1"></i>{{ exam.discussion_file_name }}</span>
                                    <button type="button" class="btn btn-sm btn-outline-danger" @click="markDiscussionFileRemoved">
                                        <i class="fa fa-trash me-1"></i> Hapus File
                                    </button>
                                </div>
                                <div v-else-if="form.remove_discussion_file" class="alert alert-warning py-2 mt-2 mb-0">
                                    File pembahasan akan dihapus saat update.
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
import { computed, reactive, ref, watch } from 'vue';
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
            discussion_file: null,
            remove_discussion_file: false,
        });

        const discussionFileInput = ref(null);
        const allLessons = computed(() => [
            ...(props.lessons?.psikologi || []),
            ...(props.lessons?.akademik || []),
        ]);

        const selectedLessonIsKecermatan = computed(() => {
            const lesson = allLessons.value.find((item) => item.id == form.lesson_id);
            const name = lesson?.name;
            if (!name || typeof name !== 'string') return false;
            const normalized = name.toLowerCase().trim();
            return normalized === 'kecermatan' || normalized.startsWith('kecermatan ');
        });

        // Reset lesson_id jika category berubah
        watch(
            () => form.category,
            () => {
                form.lesson_id = '';
            },
        );

        watch(() => selectedLessonIsKecermatan.value, (isKecermatan) => {
            if (isKecermatan) {
                clearDiscussionFile();
                form.remove_discussion_file = true;
            }
        });

        const handleDiscussionFileChange = (event) => {
            form.discussion_file = event.target.files[0] || null;
            if (form.discussion_file) {
                form.remove_discussion_file = false;
            }
        };

        const clearDiscussionFile = () => {
            form.discussion_file = null;
            if (discussionFileInput.value) discussionFileInput.value.value = '';
        };

        const markDiscussionFileRemoved = () => {
            clearDiscussionFile();
            form.remove_discussion_file = true;
        };

        const submit = () => {
            router.post(`/admin/exams/${props.exam.id}`, {
                ...form,
                _method: 'put',
            }, {
                forceFormData: true,
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

        return {
            form,
            submit,
            discussionFileInput,
            selectedLessonIsKecermatan,
            handleDiscussionFileChange,
            clearDiscussionFile,
            markDiscussionFileRemoved,
        };
    },
};
</script>
