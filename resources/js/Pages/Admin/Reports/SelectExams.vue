<template>
    <Head>
        <title>Pilih Ujian untuk Rapor - Buweuk Sipit Academy</title>
    </Head>
    <div class="container-fluid mt-5 mb-5">

        <!-- Header -->
        <div class="row align-items-center mb-4">
            <div class="col-md-8">
                <h5 class="mb-1 fw-bold">Pilih Ujian yang Ingin Dimasukkan ke Rapor</h5>
                <p class="text-muted mb-0">
                    Rapor akan dicetak untuk <strong>{{ students.length }} siswa</strong> yang dipilih.
                    Centang ujian yang ingin ditampilkan, lalu klik "Cetak Rapor".
                </p>
            </div>
            <div class="col-md-4 text-md-end mt-2 mt-md-0 d-flex justify-content-md-end gap-2">
                <Link href="/admin/reports/students" class="btn btn-secondary shadow border-0">
                    <i class="fa fa-arrow-left"></i> Kembali
                </Link>
                <a
                    v-if="selectedExams.length > 0"
                    :href="printUrl"
                    target="_blank"
                    class="btn btn-primary shadow border-0"
                >
                    <i class="fa fa-print"></i> Cetak Rapor ({{ selectedExams.length }} ujian)
                </a>
                <button v-else class="btn btn-primary shadow border-0" disabled>
                    <i class="fa fa-print"></i> Cetak Rapor
                </button>
            </div>
        </div>

        <div class="row">
            <!-- Daftar Siswa Terpilih -->
            <div class="col-md-3 mb-4">
                <div class="card border-0 shadow h-100">
                    <div class="card-header border-0 bg-white pt-3">
                        <h6 class="fw-bold mb-0"><i class="fa fa-users text-primary me-2"></i>Siswa Terpilih</h6>
                    </div>
                    <div class="card-body pt-2">
                        <ul class="list-unstyled mb-0">
                            <li v-for="student in students" :key="student.id" class="d-flex align-items-center gap-2 py-2 border-bottom">
                                <span class="badge bg-primary rounded-circle" style="width:28px;height:28px;line-height:18px;font-size:11px;">
                                    {{ student.name.charAt(0).toUpperCase() }}
                                </span>
                                <span style="font-size:14px;">{{ student.name }}</span>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- Daftar Ujian -->
            <div class="col-md-9">
                <div class="card border-0 shadow">
                    <div class="card-header border-0 bg-white pt-3 d-flex align-items-center justify-content-between">
                        <h6 class="fw-bold mb-0"><i class="fa fa-file-alt text-success me-2"></i>Ujian Reguler</h6>
                        <div class="d-flex gap-2">
                            <button class="btn btn-sm btn-outline-primary" @click="selectAll">Pilih Semua</button>
                            <button class="btn btn-sm btn-outline-secondary" @click="deselectAll">Hapus Pilihan</button>
                        </div>
                    </div>
                    <div class="card-body">
                        <div v-if="exams.length === 0" class="text-center text-muted py-3">
                            <i class="fa fa-inbox fa-2x mb-2 d-block"></i>
                            Tidak ada ujian reguler untuk siswa terpilih.
                        </div>
                        <div v-else class="row g-3">
                            <div v-for="exam in exams" :key="'reg-'+exam.id" class="col-md-6">
                                <label
                                    :for="`exam-${exam.id}`"
                                    class="exam-card d-flex align-items-start gap-3 p-3 border rounded cursor-pointer"
                                    :class="{ 'selected': selectedExams.includes(exam.id) }"
                                >
                                    <input
                                        type="checkbox"
                                        :id="`exam-${exam.id}`"
                                        :value="exam.id"
                                        v-model="selectedExams"
                                        class="form-check-input mt-1 flex-shrink-0"
                                        style="width:18px;height:18px;"
                                    >
                                    <div>
                                        <div class="fw-semibold" style="font-size:14px;">{{ exam.title }}</div>
                                        <div class="text-muted" style="font-size:12px;">
                                            <i class="fa fa-book me-1"></i>{{ exam.lesson?.name ?? '-' }}
                                        </div>
                                    </div>
                                </label>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Ujian Kecermatan -->
                <div class="card border-0 shadow mt-4" v-if="kecermatanExams && kecermatanExams.length > 0">
                    <div class="card-header border-0 bg-white pt-3 d-flex align-items-center justify-content-between">
                        <h6 class="fw-bold mb-0"><i class="fa fa-bolt text-warning me-2"></i>Ujian Kecermatan</h6>
                        <div class="d-flex gap-2">
                            <button class="btn btn-sm btn-outline-warning" @click="selectAllKec">Pilih Semua</button>
                            <button class="btn btn-sm btn-outline-secondary" @click="deselectAllKec">Hapus Pilihan</button>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div v-for="kec in kecermatanExams" :key="'kec-'+kec.id" class="col-md-6">
                                <label
                                    :for="`kec-${kec.id}`"
                                    class="exam-card d-flex align-items-start gap-3 p-3 border rounded cursor-pointer"
                                    :class="{ 'selected': selectedKecExams.includes(kec.id) }"
                                >
                                    <input
                                        type="checkbox"
                                        :id="`kec-${kec.id}`"
                                        :value="kec.id"
                                        v-model="selectedKecExams"
                                        class="form-check-input mt-1 flex-shrink-0"
                                        style="width:18px;height:18px;"
                                    >
                                    <div>
                                        <div class="fw-semibold" style="font-size:14px;">{{ kec.title }}</div>
                                        <div class="text-muted" style="font-size:12px;">
                                            <i class="fa fa-bolt me-1"></i>Ujian Kecermatan
                                        </div>
                                    </div>
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>
</template>

<script>
import LayoutAdmin from '../../../Layouts/Admin.vue';
import { Head, Link } from '@inertiajs/vue3';
import { ref, computed } from 'vue';

export default {
    layout: LayoutAdmin,
    components: { Head, Link },
    props: {
        students:       Array,
        studentIds:     String,
        exams:          Array,
        kecermatanExams: Array,
    },
    setup(props) {
        const selectedExams    = ref(props.exams.map(e => e.id));
        const selectedKecExams = ref((props.kecermatanExams ?? []).map(e => e.id));

        const selectAll    = () => { selectedExams.value    = props.exams.map(e => e.id); };
        const deselectAll  = () => { selectedExams.value    = []; };
        const selectAllKec = () => { selectedKecExams.value = (props.kecermatanExams ?? []).map(e => e.id); };
        const deselectAllKec = () => { selectedKecExams.value = []; };

        const printUrl = computed(() => {
            return `/admin/reports/students/bulk-print?students=${props.studentIds}&exams=${selectedExams.value.join(',')}&kec_exams=${selectedKecExams.value.join(',')}`;
        });

        return { selectedExams, selectedKecExams, selectAll, deselectAll, selectAllKec, deselectAllKec, printUrl };
    }
};
</script>

<style scoped>
.cursor-pointer { cursor: pointer; }
.exam-card {
    cursor: pointer;
    transition: all 0.2s ease;
    background: #fff;
}
.exam-card:hover {
    border-color: #0d6efd !important;
    background: #f0f5ff;
}
.exam-card.selected {
    border-color: #0d6efd !important;
    background: #eef3ff;
}
</style>
