<template>
    <Head>
        <title>Laporan Nilai Ujian - Buweuk Sipit Academy</title>
    </Head>
    <div class="container-fluid mt-5 mb-5">
        <div class="row align-items-center mb-3">
            <div class="col-md-8">
                <form @submit.prevent="filter">
                    <div class="input-group">
                        <select
                            class="form-select border-0 shadow"
                            v-model="form.exam_id"
                            @change="filter"
                            style="max-width: 200px"
                        >
                            <option value="">Semua Ujian</option>
                            <option
                                v-for="exam in exams"
                                :key="exam.id"
                                :value="exam.id"
                            >
                                {{ exam.title }}
                            </option>
                        </select>
                        <input
                            type="text"
                            class="form-control border-0 shadow"
                            v-model="form.search"
                            placeholder="Cari nama siswa atau ujian..."
                        />
                        <button
                            type="submit"
                            class="btn btn-primary border-0 shadow cursor-pointer"
                        >
                            <i class="fa fa-search"></i>
                        </button>
                        <button
                            type="button"
                            class="btn btn-secondary border-0 shadow cursor-pointer"
                            data-bs-toggle="modal"
                            data-bs-target="#filterModal"
                            title="Filter Lengkap"
                        >
                            <i class="fa fa-sliders-h"></i>
                        </button>
                    </div>
                </form>
            </div>
            <div
                class="col-md-4 text-md-end mt-2 mt-md-0 d-flex justify-content-md-end gap-2"
            >
                <a
                    v-if="grades.data.length > 0"
                    :href="exportUrl"
                    target="_blank"
                    class="btn btn-success shadow border-0"
                >
                    <i class="fa fa-file-excel"></i> Download Excel
                </a>
                <Link
                    href="/admin/reports/students"
                    class="btn btn-primary shadow border-0"
                >
                    <i class="fa fa-print"></i> Cetak Rapor
                </Link>
            </div>
        </div>

        <!-- Tabel Gabungan: Ujian Reguler + Kecermatan -->
        <div class="row mt-1">
            <div class="col-md-12">
                <div class="card border-0 shadow">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table
                                class="table-bordered table-centered table-nowrap mb-0 table rounded"
                            >
                                <thead class="thead-dark">
                                    <tr class="border-0">
                                        <th class="rounded-start border-0" style="width: 5%">No.</th>
                                        <th class="border-0">Ujian</th>
                                        <th class="border-0">Nama Siswa</th>
                                        <th class="border-0">Mata Pelajaran / Tipe</th>
                                        <th class="border-0 text-center">Nilai</th>
                                        <th class="border-0 text-center">Waktu Selesai</th>
                                        <th class="rounded-end border-0 text-center">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <!-- Baris kosong jika tidak ada data sama sekali -->
                                    <tr v-if="grades.data?.length === 0 && (!kecermatanSessions || kecermatanSessions.data?.length === 0)">
                                        <td colspan="7" class="text-center text-muted py-4">
                                            <i class="fa fa-search fa-3x mb-3 d-block"></i>
                                            Tidak ada data nilai ujian yang sesuai filter.
                                        </td>
                                    </tr>

                                    <!-- Baris Ujian Reguler -->
                                    <tr
                                        v-for="(grade, index) in grades.data"
                                        :key="'grade-' + grade.id"
                                    >
                                        <td class="fw-bold text-center">
                                            {{ index + 1 + (grades.current_page - 1) * grades.per_page }}
                                        </td>
                                        <td>{{ grade.exam.title }}</td>
                                        <td>{{ grade.student.name }}</td>
                                        <td>{{ grade.exam.lesson.name }}</td>
                                        <td class="text-center fw-bold">{{ grade.grade }}</td>
                                        <td class="text-center small text-muted">
                                            {{ grade.start_time ? new Date(grade.start_time).toLocaleString('id-ID') : '-' }}
                                        </td>
                                        <td class="text-center">
                                            <button
                                                type="button"
                                                class="btn btn-sm btn-info shadow border-0"
                                                @click="showDetail(grade)"
                                                title="Lihat Detail Siswa"
                                            >
                                                <i class="fa fa-eye"></i> Detail
                                            </button>
                                        </td>
                                    </tr>

                                    <!-- Baris Kecermatan -->
                                    <tr
                                        v-for="(s, index) in kecermatanSessions?.data"
                                        :key="'kec-' + s.id"
                                    >
                                        <td class="fw-bold text-center text-muted">
                                            {{ (grades.data?.length || 0) + index + 1 + (kecermatanSessions.current_page - 1) * kecermatanSessions.per_page }}
                                        </td>
                                        <td>
                                            {{ s.exam_title }}
                                        </td>
                                        <td>{{ s.student_name }}</td>
                                        <td>
                                            <span class="badge bg-secondary">{{ s.exam_type }}</span>
                                        </td>
                                        <td class="text-center fw-bold text-primary">{{ s.total_score }}</td>
                                        <td class="text-center small text-muted">{{ s.finished_at }}</td>
                                        <td class="text-center">
                                            <Link
                                                :href="`/admin/kecermatan/${s.kecermatan_exam_id}/result/${s.id}`"
                                                class="btn btn-sm btn-info shadow border-0"
                                                title="Lihat Detail Hasil Kecermatan"
                                            >
                                                <i class="fa fa-eye"></i> Detail
                                            </Link>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <Pagination
                            v-if="grades.links"
                            :links="grades.links"
                            align="end"
                            class="mt-3"
                        />
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Filter Lengkap -->
    <div
        class="modal fade"
        id="filterModal"
        tabindex="-1"
        aria-labelledby="filterModalLabel"
        aria-hidden="true"
    >
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header bg-light border-0">
                    <h5 class="modal-title fw-bold" id="filterModalLabel">
                        <i class="fa fa-sliders-h text-primary"></i> Filter
                        Lengkap
                    </h5>
                    <button
                        type="button"
                        class="btn-close"
                        data-bs-dismiss="modal"
                        aria-label="Close"
                    ></button>
                </div>
                <div class="modal-body">
                    <form @submit.prevent="applyAdvancedFilter">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label fw-bold"
                                    >Mata Pelajaran</label
                                >
                                <select
                                    class="form-select"
                                    v-model="form.lesson_id"
                                >
                                    <option value="">
                                        Semua Mata Pelajaran
                                    </option>
                                    <option
                                        v-for="lesson in lessons"
                                        :key="lesson.id"
                                        :value="lesson.id"
                                    >
                                        {{ lesson.name }}
                                    </option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Siswa</label>
                                <select
                                    class="form-select"
                                    v-model="form.student_id"
                                >
                                    <option value="">Semua Siswa</option>
                                    <option
                                        v-for="student in students"
                                        :key="student.id"
                                        :value="student.id"
                                    >
                                        {{ student.name }}
                                    </option>
                                </select>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-bold"
                                    >Dari Tanggal</label
                                >
                                <input
                                    type="date"
                                    class="form-control"
                                    v-model="form.date_from"
                                />
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold"
                                    >Sampai Tanggal</label
                                >
                                <input
                                    type="date"
                                    class="form-control"
                                    v-model="form.date_to"
                                />
                            </div>

                            <div class="col-md-4">
                                <label class="form-label fw-bold"
                                    >Nilai Minimal</label
                                >
                                <input
                                    type="number"
                                    class="form-control"
                                    v-model="form.grade_min"
                                    placeholder="0"
                                    min="0"
                                    max="100"
                                />
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-bold"
                                    >Nilai Maksimal</label
                                >
                                <input
                                    type="number"
                                    class="form-control"
                                    v-model="form.grade_max"
                                    placeholder="100"
                                    min="0"
                                    max="100"
                                />
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-bold"
                                    >Status Kelulusan</label
                                >
                                <select
                                    class="form-select"
                                    v-model="form.status"
                                >
                                    <option value="">Semua Status</option>
                                    <option value="passed">Lulus (Kepribadian ≥ 50%, Lainnya ≥ 70%)</option>
                                    <option value="failed">
                                        Tidak Lulus (Kepribadian < 50%, Lainnya < 70%)
                                    </option>
                                </select>
                            </div>
                        </div>
                        <div class="mt-4 text-end">
                            <button
                                type="button"
                                class="btn btn-outline-secondary me-2"
                                @click="resetFilter"
                            >
                                Reset
                            </button>
                            <button
                                type="submit"
                                class="btn btn-primary"
                                data-bs-dismiss="modal"
                            >
                                Terapkan Filter
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Detail Siswa -->
    <div
        class="modal fade"
        id="detailModal"
        tabindex="-1"
        aria-labelledby="detailModalLabel"
        aria-hidden="true"
    >
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header bg-light border-0">
                    <h5 class="modal-title fw-bold" id="detailModalLabel">
                        <i class="fa fa-user text-info"></i> Detail Ujian Siswa
                    </h5>
                    <button
                        type="button"
                        class="btn-close"
                        data-bs-dismiss="modal"
                        aria-label="Close"
                    ></button>
                </div>
                <div class="modal-body" v-if="selectedGrade">
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <h6 class="text-muted mb-1">Nama Siswa</h6>
                            <h5 class="fw-bold">
                                {{ selectedGrade.student.name }}
                            </h5>
                        </div>
                        <div class="col-md-6 text-md-end">
                            <h6 class="text-muted mb-1">Nilai Akhir</h6>
                            <h3
                                :class="
                                    isPersonalityExam(selectedGrade.exam)
                                        ? selectedGrade.grade >= 50
                                            ? 'text-success'
                                            : 'text-danger'
                                        : selectedGrade.grade >= 70
                                        ? 'text-success'
                                        : 'text-danger'
                                "
                                class="fw-bold m-0"
                            >
                                {{ selectedGrade.grade }}{{ isPersonalityExam(selectedGrade.exam) ? '%' : '' }}
                            </h3>
                        </div>
                    </div>

                    <div class="card bg-light border-0 mb-4">
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6 mb-2">
                                    <strong>Ujian:</strong>
                                    {{ selectedGrade.exam.title }}
                                </div>
                                <div class="col-md-6 mb-2">
                                    <strong>Mata Pelajaran:</strong>
                                    {{ selectedGrade.exam.lesson.name }}
                                </div>
                                <div class="col-md-6 mb-2">
                                    <strong>
                                        {{
                                            isPersonalityExam(
                                                selectedGrade.exam,
                                            )
                                                ? "Total Poin:"
                                                : "Total Benar:"
                                        }}
                                    </strong>
                                    {{
                                        isPersonalityExam(selectedGrade.exam)
                                            ? `${selectedGrade.total_points ?? 0} / ${selectedGrade.max_points ?? 0} Poin`
                                            : `${selectedGrade.total_correct ?? 0} Soal`
                                    }}
                                </div>
                                <div class="col-md-6 mb-2">
                                    <strong>Waktu Ujian:</strong>
                                    {{
                                        selectedGrade.start_time
                                            ? new Date(
                                                  selectedGrade.start_time,
                                              ).toLocaleString("id-ID")
                                            : "-"
                                    }}
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Informasi Kecurangan -->
                    <h6 class="fw-bold text-danger mb-3">
                        <i class="fa fa-exclamation-triangle"></i> Catatan
                        Kecurangan (Violations)
                    </h6>

                    <div v-if="getViolationList(selectedGrade)?.length > 0">
                        <div class="alert alert-danger border-0">
                            Terdeteksi
                            <strong>{{
                                getViolationList(selectedGrade).length
                            }}</strong>
                            aktivitas mencurigakan selama ujian.
                        </div>
                        <div class="table-responsive">
                            <table class="table table-sm table-bordered">
                                <thead class="table-danger">
                                    <tr>
                                        <th>No.</th>
                                        <th>Waktu Kejadian</th>
                                        <th>Tipe Pelanggaran</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr
                                        v-for="(
                                            violation, vIndex
                                        ) in getViolationList(selectedGrade)"
                                        :key="violation.id || vIndex"
                                    >
                                        <td>{{ vIndex + 1 }}</td>
                                        <td>
                                            {{
                                                new Date(
                                                    violation.violation_time,
                                                ).toLocaleString("id-ID")
                                            }}
                                        </td>
                                        <td>
                                            <span
                                                v-if="
                                                    violation.violation_type ===
                                                    'tab_switch'
                                                "
                                                >Berpindah Tab / Aplikasi</span
                                            >
                                            <span
                                                v-else-if="
                                                    violation.violation_type ===
                                                    'exit_fullscreen'
                                                "
                                                >Keluar Mode Layar Penuh</span
                                            >
                                            <span v-else>{{
                                                violation.violation_type
                                            }}</span>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div v-else>
                        <div class="alert alert-success border-0">
                            <i class="fa fa-check-circle"></i> Tidak terdeteksi
                            adanya pelanggaran atau kecurangan selama ujian
                            berlangsung. Ujian berjalan lancar.
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0 justify-content-between">
                    <button
                        type="button"
                        class="btn btn-secondary"
                        data-bs-dismiss="modal"
                    >
                        Tutup
                    </button>
                    <div>
                        <a
                            v-if="selectedGrade"
                            :href="`/admin/reports/student/${selectedGrade.student_id}/print`"
                            target="_blank"
                            class="btn btn-outline-primary shadow me-2"
                        >
                            <i class="fa fa-print"></i> Cetak Rekap Semua Ujian
                            Siswa Ini
                        </a>
                        <a
                            v-if="selectedGrade"
                            :href="`/admin/reports/${selectedGrade.id}/print`"
                            target="_blank"
                            class="btn btn-primary shadow"
                        >
                            <i class="fa fa-print"></i> Cetak Rapor (Ujian Ini
                            Saja)
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import LayoutAdmin from "../../../Layouts/Admin.vue";
import Pagination from "../../../Components/Pagination.vue";
import { Head, Link, router } from "@inertiajs/vue3";
import { reactive, computed, ref, onMounted } from "vue";

export default {
    layout: LayoutAdmin,
    components: { Head, Link, Pagination },
    props: {
        errors: Object,
        exams: Array,
        lessons: Array,
        students: Array,
        grades: [Array, Object],
        kecermatanSessions: Object,
        statistics: Object,
        isPersonality: Boolean,
        filters: Object,
    },
    setup(props) {
        const form = reactive({
            exam_id: props.filters?.exam_id || "",
            search: props.filters?.search || "",
            lesson_id: props.filters?.lesson_id || "",
            student_id: props.filters?.student_id || "",
            date_from: props.filters?.date_from || "",
            date_to: props.filters?.date_to || "",
            grade_min: props.filters?.grade_min || "",
            grade_max: props.filters?.grade_max || "",
            status: props.filters?.status || "",
        });

        const selectedGrade = ref(null);
        let detailModalInstance = null;

        onMounted(() => {
            if (typeof window !== "undefined" && window.bootstrap) {
                const el = document.getElementById("detailModal");
                if (el) {
                    detailModalInstance = new window.bootstrap.Modal(el);
                }
            }
        });

        const getViolationList = (grade) => {
            if (!grade) return [];

            const relation = grade.exam_group ?? grade.examGroup ?? null;
            if (!relation) return [];

            const violations =
                relation.exam_violations ??
                relation.examViolations ??
                relation.violations ??
                [];

            return Array.isArray(violations) ? violations : [];
        };

        const showDetail = (grade) => {
            const normalizedGrade = {
                ...grade,
                exam_group: grade.exam_group ??
                    grade.examGroup ?? { exam_violations: [] },
            };

            if (
                normalizedGrade.exam_group &&
                !normalizedGrade.exam_group.exam_violations &&
                grade.examGroup?.exam_violations
            ) {
                normalizedGrade.exam_group = grade.examGroup;
            }

            selectedGrade.value = normalizedGrade;
            if (detailModalInstance) {
                detailModalInstance.show();
            } else if (typeof window !== "undefined" && window.bootstrap) {
                const el = document.getElementById("detailModal");
                detailModalInstance = new window.bootstrap.Modal(el);
                detailModalInstance.show();
            }
        };

        const exportUrl = computed(() => {
            const params = new URLSearchParams();
            Object.keys(form).forEach((key) => {
                if (form[key]) params.append(key, form[key]);
            });
            return `/admin/reports/export?${params.toString()}`;
        });

        const filter = () => {
            router.get("/admin/reports/filter", form);
        };

        const applyAdvancedFilter = () => {
            filter();
        };

        const resetFilter = () => {
            Object.keys(form).forEach((key) => {
                form[key] = "";
            });
            filter();
        };

        const isPersonalityExam = (exam) => {
            if (!exam || !exam.lesson || !exam.lesson.name) return false;
            const lessonName = exam.lesson.name.toLowerCase().trim();
            return (
                lessonName === "kepribadian" ||
                lessonName.startsWith("kepribadian ")
            );
        };

        return {
            form,
            exportUrl,
            filter,
            applyAdvancedFilter,
            resetFilter,
            selectedGrade,
            showDetail,
            getViolationList,
            isPersonalityExam,
        };
    },
};
</script>

<style scoped>
.cursor-pointer {
    cursor: pointer;
}
.cursor-pointer:hover {
    opacity: 0.9;
}
</style>
