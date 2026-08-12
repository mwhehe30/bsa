<template>
    <Head>
        <title>Detail Ujian - Buweuk Sipit Academy</title>
    </Head>
    <div class="container-fluid mt-5 mb-5 admin-exam-detail">
        <div class="row">
            <div class="col-md-12">
                <div class="mb-3">
                    <Link href="/admin/exams" class="btn btn-primary border-0 shadow-sm">
                        <i class="fa fa-chevron-left me-2"></i>Kembali
                    </Link>
                </div>

                <div class="card mb-4 border-0 shadow">
                    <div class="card-header">
                        <h2 class="h6 fw-bold"><i class="fa fa-info-circle me-2 text-primary"></i>Informasi Ujian</h2>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive exam-info-wrap">
                            <table class="table exam-info-table mb-0">
                                <tbody>
                                    <tr><th>Nama Ujian</th><td>{{ exam.title }}</td></tr>
                                    <tr>
                                        <th>Tipe</th>
                                        <td>
                                            <span v-if="isPersonality" class="badge bg-info">Kepribadian</span>
                                            <span v-else class="badge bg-primary">Pilihan Ganda</span>
                                        </td>
                                    </tr>
                                    <tr>
                                        <th>Kategori</th>
                                        <td>
                                            <span v-if="exam.lesson.category === 'psikologi'" class="badge bg-primary">Psikologi</span>
                                            <span v-else class="badge bg-success">Akademik</span>
                                        </td>
                                    </tr>
                                    <tr><th>Mata Pelajaran</th><td>{{ exam.lesson.name }}</td></tr>
                                    <tr>
                                        <th>Jumlah Soal</th>
                                        <td>{{ exam.questions_total || exam.questions?.data?.length || 0 }} Soal</td>
                                    </tr>
                                    <tr><th>Durasi</th><td>{{ exam.duration }} Menit</td></tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="card border-0 shadow">
                    <div class="card-body">
                        <div
                            class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2"
                        >
                            <h2 class="h5 mb-0">
                                <i class="fa fa-question-circle"></i> Soal Ujian
                                <span class="badge bg-secondary ms-2">
                                    Total:
                                    {{
                                        exam.questions_total ||
                                        exam.questions?.data?.length ||
                                        0
                                    }}
                                    soal
                                </span>
                            </h2>
                            <div v-if="!exam.is_kecermatan" class="question-actions d-flex flex-wrap gap-2">
                                <Link
                                    :href="`/admin/exams/${exam.id}/questions/create`"
                                    class="btn btn-sm btn-primary border-0 shadow"
                                >
                                    <i class="fa fa-plus-circle"></i> Tambah
                                </Link>
                                <Link
                                    :href="`/admin/exams/${exam.id}/questions/import`"
                                    class="btn btn-sm btn-success border-0 text-white shadow"
                                >
                                    <i class="fa fa-file-excel"></i> Import
                                    Excel
                                </Link>
                                <Link
                                    :href="`/admin/exams/${exam.id}/questions/import-word`"
                                    class="btn btn-sm btn-info border-0 text-white shadow"
                                >
                                    <i class="fa fa-file-word"></i> Import Word
                                </Link>
                                <!-- TOMBOL RESET SOAL -->
                                <button
                                    @click.prevent="resetQuestions(exam.id)"
                                    class="btn btn-sm btn-danger border-0 shadow"
                                    :disabled="
                                        (exam.questions_total || 0) === 0
                                    "
                                >
                                    <i class="fa fa-trash"></i> Reset Soal
                                </button>
                            </div>
                            <div v-else class="alert alert-info mb-0 py-2">
                                <i class="fa fa-info-circle me-2"></i>
                                <strong>Soal Auto-generated:</strong> 2000 soal telah dibuat otomatis oleh sistem
                            </div>
                        </div>
                        <hr />

                        <!-- Flash Messages -->
                        <div
                            v-if="$page.props.flash?.success"
                            class="alert alert-success alert-dismissible fade show"
                            role="alert"
                        >
                            <i class="fa fa-check-circle me-2"></i>
                            {{ $page.props.flash.success }}
                            <button
                                type="button"
                                class="btn-close"
                                data-bs-dismiss="alert"
                            ></button>
                        </div>

                        <div
                            v-if="$page.props.flash?.info"
                            class="alert alert-info alert-dismissible fade show"
                            role="alert"
                        >
                            <i class="fa fa-info-circle me-2"></i>
                            {{ $page.props.flash.info }}
                            <button
                                type="button"
                                class="btn-close"
                                data-bs-dismiss="alert"
                            ></button>
                        </div>

                        <div
                            v-if="$page.props.flash?.warning"
                            class="alert alert-warning alert-dismissible fade show"
                            role="alert"
                        >
                            <i class="fa fa-exclamation-triangle me-2"></i>
                            {{ $page.props.flash.warning }}
                            <button
                                type="button"
                                class="btn-close"
                                data-bs-dismiss="alert"
                            ></button>
                        </div>

                        <!-- ALERT: Undetected Answers -->
                        <div
                            v-if="
                                undetectedAnswers &&
                                undetectedAnswers.length > 0
                            "
                            class="alert alert-warning alert-dismissible fade show mb-3"
                            role="alert"
                        >
                            <h6 class="alert-heading">
                                <i
                                    class="fa fa-exclamation-triangle text-warning me-2"
                                ></i>
                                Jawaban Tidak Terdeteksi (Default ke A)
                            </h6>
                            <p class="mb-2">
                                Berikut adalah soal yang jawabannya tidak
                                terdeteksi saat import, sehingga secara otomatis
                                di-set ke <strong>A</strong>. Mohon cek dan
                                perbaiki manual:
                            </p>
                            <ul class="mb-0">
                                <li
                                    v-for="(item, index) in undetectedAnswers"
                                    :key="index"
                                    class="mb-1"
                                >
                                    <strong>Soal #{{ item.number }}:</strong>
                                    {{ item.preview }}...
                                </li>
                            </ul>
                            <button
                                type="button"
                                class="btn-close"
                                data-bs-dismiss="alert"
                                aria-label="Close"
                                @click="undetectedAnswers = []"
                            ></button>
                        </div>

                        <!-- Import Errors Alert -->
                        <div
                            v-if="importErrors && importErrors.length > 0"
                            class="alert alert-danger alert-dismissible fade show mb-3"
                            role="alert"
                        >
                            <h6 class="alert-heading">
                                <i class="fa fa-times-circle me-2"></i>
                                Beberapa soal tidak berhasil diimport
                            </h6>
                            <p class="mb-2">
                                Berikut adalah daftar soal yang memiliki error
                                dan perlu input manual:
                            </p>
                            <ul class="mb-0">
                                <li
                                    v-for="(error, index) in importErrors"
                                    :key="index"
                                    class="mb-2"
                                >
                                    <strong
                                        >Soal #{{
                                            error.question_number
                                        }}:</strong
                                    >
                                    {{ error.question_preview }}...
                                    <br />
                                    <span class="text-danger">
                                        <i class="fa fa-times-circle me-1"></i>
                                        {{ error.errors.join(', ') }}
                                    </span>
                                </li>
                            </ul>
                            <button
                                type="button"
                                class="btn-close"
                                data-bs-dismiss="alert"
                                aria-label="Close"
                                @click="importErrors = []"
                            ></button>
                        </div>

                        <!-- Import Warnings Alert -->
                        <div
                            v-if="importWarnings && importWarnings.length > 0"
                            class="alert alert-warning alert-dismissible fade show mb-3"
                            role="alert"
                        >
                            <h6 class="alert-heading">
                                <i class="fa fa-exclamation-triangle me-2"></i>
                                Beberapa soal berhasil diimport tapi perlu cek
                                manual
                            </h6>
                            <p class="mb-2">
                                Berikut adalah daftar soal yang berhasil
                                diimport tapi mungkin memiliki masalah:
                            </p>
                            <ul class="mb-0">
                                <li
                                    v-for="(warning, index) in importWarnings"
                                    :key="index"
                                    class="mb-2"
                                >
                                    <strong
                                        >Soal #{{
                                            warning.question_number
                                        }}:</strong
                                    >
                                    {{ warning.question_preview }}...
                                    <br />
                                    <span class="text-warning">
                                        <i
                                            class="fa fa-exclamation-triangle me-1"
                                        ></i>
                                        {{ warning.errors.join(', ') }}
                                    </span>
                                </li>
                            </ul>
                            <button
                                type="button"
                                class="btn-close"
                                data-bs-dismiss="alert"
                                aria-label="Close"
                                @click="importWarnings = []"
                            ></button>
                        </div>

                        <div class="table-responsive mt-3">
                            <table
                                class="questions-table table-bordered table-centered mb-0 table rounded"
                            >
                                <thead class="thead-dark">
                                    <tr class="border-0">
                                        <th
                                            class="rounded-start border-0"
                                            style="width: 5%"
                                        >
                                            No. Soal
                                        </th>
                                        <th class="border-0">Soal</th>
                                        <th
                                            v-if="isPersonality"
                                            class="border-0"
                                            style="width: 20%"
                                        >
                                            Point
                                        </th>
                                        <th
                                            class="rounded-end border-0"
                                            style="width: 15%"
                                        >
                                            Aksi
                                        </th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr
                                        v-for="(question, index) in exam
                                            .questions.data"
                                        :key="index"
                                    >
                                        <td class="question-number fw-bold text-center" data-label="No. Soal">
                                            {{
                                                ++index +
                                                (exam.questions.current_page -
                                                    1) *
                                                    exam.questions.per_page
                                            }}
                                        </td>
                                        <td class="question-content" data-label="Soal">
                                            <div
                                                class="fw-bold"
                                                v-html="sanitize(question.question)"
                                            ></div>
                                            <!-- Badge untuk soal yang perlu review -->
                                            <div
                                                v-if="question.needs_review"
                                                class="alert alert-warning alert-sm mt-2 mb-2 py-1 px-2"
                                                style="font-size: 0.85rem"
                                            >
                                                <i
                                                    class="fa fa-exclamation-triangle me-1"
                                                ></i>
                                                <strong
                                                    >Perlu Review:</strong
                                                >
                                                {{
                                                    question.review_notes ||
                                                    'Jawaban tidak terdeteksi saat import, default ke A'
                                                }}
                                            </div>
                                            <hr />
                                            <ol type="A">
                                                <li
                                                    v-html="sanitize(question.option_1)"
                                                    :class="{ 'text-success fw-bold': !isPersonality && question.answer == '1', }"
                                                ></li>
                                                <li
                                                    v-html="sanitize(question.option_2)"
                                                    :class="{ 'text-success fw-bold': !isPersonality && question.answer == '2', }"
                                                ></li>
                                                <li
                                                    v-html="sanitize(question.option_3)"
                                                    :class="{ 'text-success fw-bold': !isPersonality && question.answer == '3', }"
                                                ></li>
                                                <li
                                                    v-html="sanitize(question.option_4)"
                                                    :class="{ 'text-success fw-bold': !isPersonality && question.answer == '4', }"
                                                ></li>
                                                <li
                                                    v-html="sanitize(question.option_5)"
                                                    :class="{ 'text-success fw-bold': !isPersonality && question.answer == '5', }"
                                                ></li>
                                            </ol>
                                        </td>
                                        <td v-if="isPersonality" data-label="Point">
                                            <div class="small">
                                                <span
                                                    class="badge bg-primary me-1"
                                                    >A={{
                                                        getPoint(question, '1')
                                                    }}</span
                                                >
                                                <span
                                                    class="badge bg-secondary me-1"
                                                    >B={{
                                                        getPoint(question, '2')
                                                    }}</span
                                                >
                                                <span class="badge bg-info me-1"
                                                    >C={{
                                                        getPoint(question, '3')
                                                    }}</span
                                                >
                                                <span
                                                    class="badge bg-warning me-1"
                                                    >D={{
                                                        getPoint(question, '4')
                                                    }}</span
                                                >
                                                <span class="badge bg-danger"
                                                    >E={{
                                                        getPoint(question, '5')
                                                    }}</span
                                                >
                                            </div>
                                        </td>
                                        <td class="question-row-actions text-center" data-label="Aksi">
                                            <!-- Hide edit/delete untuk kecermatan -->
                                            <template v-if="exam.is_kecermatan">
                                                <span class="badge bg-secondary">
                                                    <i class="fa fa-lock me-1"></i>
                                                    Auto-generated
                                                </span>
                                            </template>
                                            <template v-else>
                                                <Link
                                                    :href="`/admin/exams/${exam.id}/questions/${question.id}/edit`"
                                                    class="btn btn-sm btn-info me-2 border-0 shadow"
                                                    type="button"
                                                    ><i class="fa fa-pencil-alt"></i
                                                ></Link>
                                                <button
                                                    @click.prevent="
                                                        destroy(
                                                            exam.id,
                                                            question.id,
                                                        )
                                                    "
                                                    class="btn btn-sm btn-danger border-0"
                                                >
                                                    <i class="fa fa-trash"></i>
                                                </button>
                                            </template>
                                        </td>
                                    </tr>
                                    <tr v-if="exam.questions.data.length === 0">
                                        <td
                                            :colspan="isPersonality ? 4 : 3"
                                            class="text-muted py-4 text-center"
                                        >
                                            <i
                                                class="fa fa-info-circle me-2"
                                            ></i>
                                            Belum ada soal untuk ujian ini.
                                            <Link
                                                :href="`/admin/exams/${exam.id}/questions/create`"
                                                class="btn btn-sm btn-primary ms-2"
                                            >
                                                <i
                                                    class="fa fa-plus-circle"
                                                ></i>
                                                Tambah Soal
                                            </Link>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <Pagination
                            :links="exam.questions.links"
                            align="end"
                        />
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import LayoutAdmin from '../../../Layouts/Admin.vue';
import Pagination from '../../../Components/Pagination.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import Swal from 'sweetalert2';
import { sanitizeHtml } from '../../../utils/sanitize';

export default {
    layout: LayoutAdmin,
    components: { Head, Link, Pagination },
    props: {
        errors: Object,
        exam: Object,
        importErrors: Array,
        importWarnings: Array,
        undetectedAnswers: Array,
    },
    setup(props) {
        const importErrors = ref(props.importErrors || []);
        const importWarnings = ref(props.importWarnings || []);
        const undetectedAnswers = ref(props.undetectedAnswers || []);

        const isPersonality = computed(() => {
            const name = props.exam?.lesson?.name;
            if (!name || typeof name !== 'string') return false;
            const normalized = name.toLowerCase().trim();
            return normalized === 'kepribadian' || normalized.startsWith('kepribadian ');
        });

        const destroy = (exam_id, question_id) => {
            Swal.fire({
                title: 'Apakah Anda yakin?',
                text: 'Anda tidak akan dapat mengembalikan ini!',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Yes, delete it!',
            }).then((result) => {
                if (result.isConfirmed) {
                    router.delete(
                        `/admin/exams/${exam_id}/questions/${question_id}/destroy`,
                    );

                    Swal.fire({
                        title: 'Deleted!',
                        text: 'Soal Ujian Berhasil Dihapus!.',
                        icon: 'success',
                        timer: 2000,
                        showConfirmButton: false,
                    });
                }
            });
        };

        const resetQuestions = (exam_id) => {
            const totalQuestions = props.exam.questions_total || 0;

            if (totalQuestions === 0) {
                Swal.fire({
                    title: 'Tidak Ada Soal',
                    text: 'Ujian ini tidak memiliki soal untuk direset.',
                    icon: 'info',
                    confirmButtonText: 'OK',
                });
                return;
            }

            Swal.fire({
                title: 'Reset Semua Soal?',
                html: `Anda akan menghapus <strong>${totalQuestions}</strong> soal dari ujian ini.<br><br> <span class="text-danger">Tindakan ini tidak dapat dibatalkan!</span>`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Ya, Reset Semua!',
                cancelButtonText: 'Batal',
            }).then((result) => {
                if (result.isConfirmed) {
                    router.delete(`/admin/exams/${exam_id}/questions/reset`, {
                        onSuccess: () => {
                            Swal.fire({
                                title: 'Berhasil!',
                                text: 'Semua soal berhasil direset.',
                                icon: 'success',
                                timer: 2000,
                                showConfirmButton: false,
                            });
                        },
                        onError: () => {
                            Swal.fire({
                                title: 'Gagal!',
                                text: 'Terjadi kesalahan saat mereset soal.',
                                icon: 'error',
                                confirmButtonText: 'OK',
                            });
                        },
                    });
                }
            });
        };

        const getPoint = (question, option) => {
            const points = question.points || { 1: 5, 2: 4, 3: 3, 4: 2, 5: 1 };
            return points[option] || 0;
        };

        return {
            destroy,
            resetQuestions,
            getPoint,
            isPersonality,
            importErrors,
            importWarnings,
            undetectedAnswers,
            sanitize: sanitizeHtml,
        };
    },
};
</script>

<style scoped>
.exam-info-wrap {
    border: 0;
}

.exam-info-table {
    min-width: 0;
}

.exam-info-table th {
    width: 190px;
    color: #64748b;
    font-weight: 600;
}

.exam-info-table th,
.exam-info-table td {
    padding: 0.75rem 0.5rem !important;
    background: transparent !important;
    border-bottom: 1px solid #e2e8f0 !important;
    text-align: left;
    text-transform: none;
    letter-spacing: normal;
}

.exam-info-table tr:last-child th,
.exam-info-table tr:last-child td {
    border-bottom: 0 !important;
}

.question-content {
    min-width: 420px;
    white-space: normal;
}

.question-content :deep(img) {
    height: auto;
    max-width: 100%;
}

.question-content ol {
    padding-left: 1.5rem;
}

.question-actions .btn {
    min-height: 40px;
    padding: 0.625rem 0.875rem;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 0.4rem;
    line-height: 1;
    text-align: center;
}

.question-actions .btn i {
    margin: 0;
    line-height: 1;
}

@media (max-width: 767.98px) {
    .exam-info-table th {
        width: 42%;
    }

    .question-actions,
    .question-actions .btn {
        width: 100%;
    }

    .questions-table,
    .questions-table tbody,
    .questions-table tr,
    .questions-table td {
        display: block;
        width: 100%;
    }

    .questions-table {
        min-width: 0 !important;
    }

    .questions-table thead {
        display: none;
    }

    .questions-table tbody {
        padding: 0.75rem;
        background: #f8fafc;
    }

    .questions-table tbody tr {
        margin-bottom: 0.875rem;
        overflow: hidden;
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 10px;
    }

    .questions-table tbody tr:last-child {
        margin-bottom: 0;
    }

    .questions-table tbody td {
        min-width: 0;
        padding: 0.875rem !important;
        white-space: normal;
        border: 0;
        border-bottom: 1px solid #e2e8f0;
    }

    .questions-table tbody td:last-child {
        border-bottom: 0;
    }

    .question-number {
        text-align: left !important;
        color: #64748b;
    }

    .question-row-actions {
        display: flex !important;
        gap: 0.5rem;
        text-align: left !important;
    }

    .question-row-actions .btn {
        flex: 1;
    }
}
</style>
