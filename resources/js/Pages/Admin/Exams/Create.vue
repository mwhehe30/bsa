<template>
    <Head>
        <title>Tambah Ujian - Buweuk Sipit Academy</title>
    </Head>

    <!-- Loading Overlay -->
    <div v-if="isSubmitting" class="position-fixed top-0 start-0 w-100 h-100 d-flex align-items-center justify-content-center" style="background: rgba(0,0,0,0.7); z-index: 9999;">
        <div class="text-center text-white">
            <div class="spinner-border mb-3" style="width: 4rem; height: 4rem;" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
            <h4 v-if="selectedLessonIsKecermatan">Sedang membuat ujian kecermatan...</h4>
            <h4 v-else>Sedang menyimpan ujian...</h4>
            <p v-if="selectedLessonIsKecermatan" class="mb-0">2000 soal digenerate otomatis oleh sistem (kurang dari 1 detik).</p>
            <p v-else class="mb-0">Mohon tunggu sebentar...</p>
        </div>
    </div>

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

                <!-- ====== CARD 1: INFORMASI UJIAN ====== -->
                <div class="card border-0 shadow mb-4">
                    <div class="card-body">
                        <h5><i class="fa fa-edit"></i> Informasi Ujian</h5>
                        <hr />

                        <div class="mb-4">
                            <label>Nama Ujian <span class="text-danger">*</span></label>
                            <input
                                type="text"
                                class="form-control"
                                placeholder="Masukkan Nama Ujian"
                                v-model="form.title"
                            />
                            <div v-if="errors.title" class="alert alert-danger mt-2">{{ errors.title }}</div>
                        </div>

                        <div class="mb-4">
                            <label>Kategori <span class="text-danger">*</span></label>
                            <div class="d-flex gap-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" value="psikologi" v-model="form.category" id="cat-psikologi" />
                                    <label class="form-check-label" for="cat-psikologi">Psikologi</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" value="akademik" v-model="form.category" id="cat-akademik" />
                                    <label class="form-check-label" for="cat-akademik">Akademik</label>
                                </div>
                            </div>
                        </div>

                        <div class="mb-4">
                            <label>Mata Pelajaran <span class="text-danger">*</span></label>
                            <select class="form-select" v-model="form.lesson_id">
                                <option value="">-- Pilih Mapel --</option>
                                <optgroup v-if="form.category === 'psikologi' && psikologiLessons.length" label="Psikologi">
                                    <option v-for="lesson in psikologiLessons" :key="lesson.id" :value="lesson.id">
                                        {{ lesson.name || 'Tanpa Nama' }}
                                    </option>
                                </optgroup>
                                <optgroup v-if="form.category === 'akademik' && akademikLessons.length" label="Akademik">
                                    <option v-for="lesson in akademikLessons" :key="lesson.id" :value="lesson.id">
                                        {{ lesson.name || 'Tanpa Nama' }}
                                    </option>
                                </optgroup>
                            </select>
                            <div v-if="selectedLessonIsKecermatan" class="alert alert-info mt-2">
                                <i class="fa fa-info-circle me-2"></i>
                                <strong>Tipe: Kecermatan</strong> - Soal akan dibuat otomatis oleh sistem (2000 soal), tidak perlu input manual
                            </div>
                            <div v-else-if="selectedLessonIsPersonality" class="alert alert-info mt-2">
                                <i class="fa fa-info-circle me-2"></i>
                                <strong>Tipe: Kepribadian</strong> - Soal akan menggunakan sistem point (A=5, B=4, C=3, D=2, E=1)
                            </div>
                            <div v-if="errors.lesson_id" class="alert alert-danger mt-2">{{ errors.lesson_id }}</div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-4">
                                    <label>Durasi (Menit) <span class="text-danger">*</span></label>
                                    <input type="number" min="1" class="form-control" placeholder="Masukkan Durasi" v-model="form.duration" />
                                    <div v-if="errors.duration" class="alert alert-danger mt-2">{{ errors.duration }}</div>
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
                            <div v-if="errors.description" class="alert alert-danger mt-2">{{ errors.description }}</div>
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
                            <small class="text-muted d-block mt-1">PDF atau Word (.docx). Siswa bisa download setelah ujian selesai.</small>
                            <div v-if="errors.discussion_file" class="alert alert-danger mt-2">{{ errors.discussion_file }}</div>
                            <div v-if="discussionFile" class="mt-2 d-flex align-items-center gap-2">
                                <span class="badge bg-success"><i class="fa fa-file me-1"></i>{{ discussionFile.name }}</span>
                                <button type="button" class="btn btn-sm btn-outline-danger" @click="clearDiscussionFile">
                                    <i class="fa fa-times me-1"></i> Hapus
                                </button>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-4">
                                <div class="mb-4">
                                    <label>Acak Soal</label>
                                    <select class="form-select" v-model="form.random_question">
                                        <option value="Y">Ya</option>
                                        <option value="N">Tidak</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-4">
                                    <label>Acak Jawaban</label>
                                    <select class="form-select" v-model="form.random_answer" :disabled="selectedLessonIsPersonality">
                                        <option value="Y">Ya</option>
                                        <option value="N">Tidak</option>
                                    </select>
                                    <small v-if="selectedLessonIsPersonality" class="text-muted d-block mt-1">Otomatis aktif untuk kepribadian</small>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-4">
                                    <label>Tampilkan Hasil</label>
                                    <select class="form-select" v-model="form.show_answer">
                                        <option value="Y">Ya</option>
                                        <option value="N">Tidak</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ====== CARD 2: TAMBAH SOAL ====== -->
                <div v-if="!selectedLessonIsKecermatan" class="card border-0 shadow mb-4">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h5 class="mb-0">
                                <i class="fa fa-question-circle"></i> Tambah Soal
                                <small class="text-muted fs-6 fw-normal">(Opsional)</small>
                            </h5>
                            <span class="badge bg-primary">{{ questions.length }} soal</span>
                        </div>
                        <hr />

                        <!-- Import Excel / Word -->
                        <div class="mb-4 p-3 rounded" style="background:#f8f9fa;border:1px dashed #dee2e6;">
                            <h6 class="mb-2"><i class="fa fa-file-excel text-success me-1"></i> Import Soal via Excel / Word (.docx)</h6>
                            <input
                                type="file"
                                ref="importFileInput"
                                accept=".xlsx,.xls,.csv,.doc,.docx"
                                @change="handleFileChange"
                                class="form-control mb-2"
                            />
                            <div class="alert alert-info py-2 mb-0">
                                <i class="fa fa-info-circle me-1"></i>
                                <span v-if="!selectedLessonIsPersonality">
                                    Format kolom: <strong>question, option_1, option_2, option_3, option_4, option_5, answer</strong>
                                </span>
                                <span v-else>
                                    Format kolom: <strong>question, option_1, option_2, option_3, option_4, option_5</strong>
                                    <br /><small>Opsional: point_1, point_2, point_3, point_4, point_5 (default: 5,4,3,2,1)</small>
                                </span>
                            </div>
                            <div v-if="importFile" class="mt-2 d-flex align-items-center gap-2">
                                <span class="badge bg-success"><i class="fa fa-file me-1"></i>{{ importFile.name }}</span>
                                <button type="button" class="btn btn-sm btn-outline-danger" @click="clearImport">
                                    <i class="fa fa-times me-1"></i> Hapus
                                </button>
                            </div>
                        </div>

                        <div class="text-center text-muted mb-4">
                            <span>- atau tambah soal manual satu per satu -</span>
                        </div>

                        <!-- List Soal Manual -->
                        <div v-for="(q, i) in questions" :key="i" class="card border mb-3">
                            <div class="card-header d-flex justify-content-between align-items-center py-2" style="background:#f1f3f5;">
                                <strong class="text-primary">Soal {{ i + 1 }}</strong>
                                <button type="button" class="btn btn-sm btn-danger border-0" @click="removeQuestion(i)">
                                    <i class="fa fa-trash"></i>
                                </button>
                            </div>
                            <div class="card-body">
                                <div class="mb-3">
                                    <label class="fw-bold">Pertanyaan <span class="text-danger">*</span></label>
                                    <textarea class="form-control" rows="3" v-model="q.question" placeholder="Masukkan teks pertanyaan..."></textarea>
                                </div>
                                <div class="row">
                                    <div v-for="n in 5" :key="n" class="col-md-6 mb-2">
                                        <label>Pilihan {{ ['A','B','C','D','E'][n-1] }}</label>
                                        <input type="text" class="form-control" v-model="q['option_' + n]" :placeholder="`Pilihan ${['A','B','C','D','E'][n-1]}`" />
                                    </div>
                                </div>

                                <!-- Jawaban (Pilihan Ganda) -->
                                <div v-if="!selectedLessonIsPersonality" class="mt-2">
                                    <label class="fw-bold">Jawaban Benar <span class="text-danger">*</span></label>
                                    <select class="form-select" v-model="q.answer">
                                        <option value="1">A</option>
                                        <option value="2">B</option>
                                        <option value="3">C</option>
                                        <option value="4">D</option>
                                        <option value="5">E</option>
                                    </select>
                                </div>

                                <!-- Point (Kepribadian) -->
                                <div v-else class="mt-2">
                                    <label class="fw-bold">Point per Pilihan</label>
                                    <div class="row">
                                        <div v-for="n in 5" :key="n" class="col-md-2">
                                            <label>{{ ['A','B','C','D','E'][n-1] }}</label>
                                            <input type="number" class="form-control" v-model="q['point_' + n]" min="1" max="5" />
                                        </div>
                                    </div>
                                    <small class="text-muted">Default: A=5, B=4, C=3, D=2, E=1</small>
                                </div>
                            </div>
                        </div>

                        <button type="button" class="btn btn-outline-primary border-0 shadow-sm" @click="addQuestion">
                            <i class="fa fa-plus me-1"></i> Tambah Soal
                        </button>
                    </div>
                </div>

                <!-- ====== TOMBOL SUBMIT ====== -->
                <div class="d-flex gap-2">
                    <button
                        type="button"
                        @click="submit"
                        class="btn btn-md btn-primary border-0 shadow"
                        :disabled="isSubmitting"
                    >
                        <span v-if="isSubmitting">
                            <i class="fa fa-spinner fa-spin me-1"></i> Menyimpan...
                        </span>
                        <span v-else>
                            <i class="fa fa-save me-1"></i> Simpan Ujian
                        </span>
                    </button>
                    <button type="button" class="btn btn-md btn-warning border-0 shadow" @click="resetAll">
                        <i class="fa fa-undo me-1"></i> Reset
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import LayoutAdmin from '../../../Layouts/Admin.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { reactive, ref, computed, watch } from 'vue';
import Editor from '@tinymce/tinymce-vue';

export default {
    layout: LayoutAdmin,
    components: { Head, Link, Editor },
    props: {
        errors: Object,
        lessons: {
            type: Object,
            default: () => ({ psikologi: [], akademik: [] }),
        },
        TinyMCEApiKey: String,
    },
    setup(props) {
        const psikologiLessons = computed(() => props.lessons?.psikologi || []);
        const akademikLessons  = computed(() => props.lessons?.akademik  || []);

        const form = reactive({
            title: '',
            category: 'psikologi',
            lesson_id: '',
            duration: '',
            description: '',
            random_question: 'Y',
            random_answer: 'Y',
            show_answer: 'Y',
        });

        const questions = ref([]);
        const importFile = ref(null);
        const importFileInput = ref(null);
        const discussionFile = ref(null);
        const discussionFileInput = ref(null);
        const isSubmitting = ref(false);

        const isPersonalityLesson = (name) => {
            if (!name || typeof name !== 'string') return false;
            const normalized = name.toLowerCase().trim();
            return normalized === 'kepribadian' || normalized.startsWith('kepribadian ');
        };

        const isKecermatanLesson = (name) => {
            if (!name || typeof name !== 'string') return false;
            const normalized = name.toLowerCase().trim();
            return normalized === 'kecermatan' || normalized.startsWith('kecermatan ');
        };

        const selectedLessonIsPersonality = computed(() => {
            if (!form.lesson_id) return false;
            const all = [...psikologiLessons.value, ...akademikLessons.value];
            const sel = all.find((l) => l.id == form.lesson_id);
            return sel ? isPersonalityLesson(sel.name) : false;
        });

        const selectedLessonIsKecermatan = computed(() => {
            if (!form.lesson_id) return false;
            const all = [...psikologiLessons.value, ...akademikLessons.value];
            const sel = all.find((l) => l.id == form.lesson_id);
            return sel ? isKecermatanLesson(sel.name) : false;
        });

        watch(() => form.category, () => { 
            form.lesson_id = '';
        });
        
        watch(() => selectedLessonIsPersonality.value, (isPersonality) => {
            if (isPersonality) form.random_answer = 'Y';
        });

        watch(() => selectedLessonIsKecermatan.value, (isKecermatan) => {
            if (isKecermatan) {
                form.duration = 10; // Default 10 menit untuk kecermatan
                clearDiscussionFile();
            }
        });

        // ---- Manajemen Soal ----
        const freshQuestion = () => ({
            question: '',
            option_1: '', option_2: '', option_3: '', option_4: '', option_5: '',
            answer: '1',
            point_1: 5, point_2: 4, point_3: 3, point_4: 2, point_5: 1,
        });

        const addQuestion = () => questions.value.push(freshQuestion());
        const removeQuestion = (i) => questions.value.splice(i, 1);

        // ---- Import File ----
        const handleFileChange = (e) => { importFile.value = e.target.files[0] || null; };
        const clearImport = () => {
            importFile.value = null;
            if (importFileInput.value) importFileInput.value.value = '';
        };

        const handleDiscussionFileChange = (e) => { discussionFile.value = e.target.files[0] || null; };
        const clearDiscussionFile = () => {
            discussionFile.value = null;
            if (discussionFileInput.value) discussionFileInput.value.value = '';
        };

        // ---- Submit ----
        const submit = () => {
            isSubmitting.value = true;

            const data = {
                title: form.title,
                category: form.category,
                lesson_id: form.lesson_id,
                duration: form.duration,
                description: form.description,
                random_question: form.random_question,
                random_answer: form.random_answer,
                show_answer: form.show_answer,
                questions: questions.value.filter((q) => q.question.trim()),
            };

            if (importFile.value) {
                data.import_file = importFile.value;
            }

            if (discussionFile.value && !selectedLessonIsKecermatan.value) {
                data.discussion_file = discussionFile.value;
            }

            router.post('/admin/exams', data, {
                forceFormData: true,
                onFinish: () => { isSubmitting.value = false; },
                onError: () => { isSubmitting.value = false; },
            });
        };

        const resetAll = () => {
            Object.assign(form, {
                title: '', category: 'psikologi', lesson_id: '',
                duration: '', description: '',
                random_question: 'Y', random_answer: 'Y', show_answer: 'N',
            });
            questions.value = [];
            clearImport();
            clearDiscussionFile();
        };

        return {
            form, errors: props.errors,
            psikologiLessons, akademikLessons, 
            selectedLessonIsPersonality,
            selectedLessonIsKecermatan,
            questions, addQuestion, removeQuestion,
            importFile, importFileInput, handleFileChange, clearImport,
            discussionFile, discussionFileInput, handleDiscussionFileChange, clearDiscussionFile,
            isSubmitting, submit, resetAll,
        };
    },
};
</script>
