<template>
    <Head>
        <title>Detail Hasil {{ session.student_name }} - Buweuk Sipit Academy</title>
    </Head>
    <div class="container-fluid mt-4 mb-5" id="print-area">

        <!-- Header -->
        <div class="row align-items-center mb-4 no-print">
            <div class="col">
                <Link href="/admin/reports" class="btn btn-md btn-primary border-0 shadow">
                    <i class="fa fa-arrow-left me-1"></i> Kembali
                </Link>
            </div>
            <div class="col-auto">
                <button @click="printPage" class="btn btn-md btn-danger border-0 shadow">
                    <i class="fa fa-print me-1"></i> Cetak / PDF
                </button>
            </div>
        </div>

        <!-- Print Header (only visible when printing) -->
        <div class="print-only mb-4">
            <h4 class="fw-bold">BUWEUK SIPIT ACADEMY</h4>
            <h5>Laporan Hasil Ujian Kecermatan</h5>
            <hr>
        </div>

        <!-- Info Siswa -->
        <div class="card border-0 shadow-sm rounded-3 mb-4">
            <div class="card-header bg-white py-3 border-bottom">
                <h6 class="fw-bold mb-0 text-dark">
                    <i class="fa fa-id-card me-2 text-primary"></i>Informasi Siswa & Sesi Ujian
                </h6>
            </div>
            <div class="card-body p-4">
                <div class="row g-3">
                    <div class="col-md-3">
                        <div class="small text-muted fw-bold">NAMA SISWA</div>
                        <div class="fw-bold text-dark mt-1">{{ session.student_name }}</div>
                        <div class="small text-muted">{{ session.student_email }}</div>
                    </div>
                    <div class="col-md-3">
                        <div class="small text-muted fw-bold">TIPE KECERMATAN</div>
                        <div class="mt-1">
                            <span class="badge bg-primary px-3 py-2 text-uppercase">
                                Tipe {{ session.exam_type }}
                            </span>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="small text-muted fw-bold">WAKTU SELESAI</div>
                        <div class="fw-semibold text-dark mt-1">{{ session.finished_at }}</div>
                    </div>
                    <div class="col-md-3">
                        <div class="small text-muted fw-bold">TOTAL SOAL DIJAWAB</div>
                        <div class="fs-4 fw-bold text-primary mt-1">{{ (session.total_correct + session.total_wrong) }} / 500</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Stat Cards -->
        <div class="row g-3 mb-4">
            <div class="col-6 col-md-3">
                <div class="card border-0 shadow-sm p-3 rounded-3 text-center bg-white">
                    <div class="small text-muted fw-bold">BENAR</div>
                    <div class="fs-2 fw-bold text-success mt-1">{{ session.total_correct }}</div>
                    <div class="small text-muted">+{{ correctPercentage }}% akurasi</div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="card border-0 shadow-sm p-3 rounded-3 text-center bg-white">
                    <div class="small text-muted fw-bold">SALAH</div>
                    <div class="fs-2 fw-bold text-danger mt-1">{{ session.total_wrong }}</div>
                    <div class="small text-muted">{{ wrongPercentage }}% dari 500</div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="card border-0 shadow-sm p-3 rounded-3 text-center bg-white">
                    <div class="small text-muted fw-bold">TIDAK DIJAWAB</div>
                    <div class="fs-2 fw-bold text-secondary mt-1">{{ calculatedUnanswered }}</div>
                    <div class="small text-muted">{{ unansweredPercentage }}% dari 500</div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="card border-0 shadow-sm p-3 rounded-3 text-center bg-white">
                    <div class="small text-muted fw-bold">TOTAL SKOR</div>
                    <div class="fs-2 fw-bold text-primary mt-1">{{ session.total_score ?? session.total_correct }}</div>
                    <div class="small text-muted">Poin kecermatan</div>
                </div>
            </div>
        </div>

        <!-- Grafik Per Kolom -->
        <div class="card border-0 shadow-sm rounded-3 mb-4">
            <div class="card-header bg-white py-3 border-bottom">
                <h6 class="fw-bold mb-0 text-dark">
                    <i class="fa fa-chart-line me-2 text-primary"></i>Grafik Perkembangan Per Kolom
                </h6>
            </div>
            <div class="card-body p-4">
                <div style="height: 260px; position: relative;">
                    <canvas ref="chartCanvas"></canvas>
                </div>
            </div>
        </div>

        <!-- Tabel Rincian Per Kolom -->
        <div class="card border-0 shadow-sm rounded-3 mb-4">
            <div class="card-header bg-white py-3 border-bottom">
                <h6 class="fw-bold mb-0 text-dark">
                    <i class="fa fa-table me-2 text-primary"></i>Rincian Hasil Per Kolom
                </h6>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="bg-light">
                            <tr>
                                <th class="text-center py-3">KOLOM</th>
                                <th class="text-center py-3">BENAR</th>
                                <th class="text-center py-3">SALAH</th>
                                <th class="text-center py-3">TIDAK DIJAWAB</th>
                                <th class="text-center py-3">WAKTU PENGERJAAN</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="detail in columnDetails" :key="detail.column">
                                <td class="text-center fw-bold"><span class="d-none d-sm-inline">Kolom </span>{{ detail.column }}</td>
                                <td class="text-center fw-bold text-success">+{{ detail.correct }}</td>
                                <td class="text-center fw-bold text-danger">{{ detail.wrong }}</td>
                                <td class="text-center text-muted">{{ detail.unanswered }}</td>
                                <td class="text-center font-monospace text-muted">{{ formatColumnDuration(detail.time_spent) }}</td>
                            </tr>
                        </tbody>
                        <tfoot>
                            <tr class="table-light fw-bold result-total-row">
                                <td class="text-center">TOTAL</td>
                                <td class="text-center text-success">+{{ session.total_correct }}</td>
                                <td class="text-center text-danger">{{ session.total_wrong }}</td>
                                <td class="text-center">{{ calculatedUnanswered }}</td>
                                <td class="text-center font-monospace">{{ formatColumnDuration(totalColumnDuration) }}</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>

        <!-- Pelanggaran -->
        <div v-if="violations && violations.length > 0" class="card border-0 shadow-sm rounded-3 mb-4">
            <div class="card-header bg-white py-3 border-bottom">
                <h6 class="fw-bold mb-0 text-danger">
                    <i class="fa fa-exclamation-triangle me-2"></i>Riwayat Pelanggaran ({{ violations.length }})
                </h6>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="bg-light">
                            <tr>
                                <th class="px-4 py-3" style="width:5%">No.</th>
                                <th class="py-3">Jenis Pelanggaran</th>
                                <th class="py-3 text-center">Kolom</th>
                                <th class="py-3 text-center">Soal Ke</th>
                                <th class="px-4 py-3 text-end">Waktu Terjadi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="(v, i) in violations" :key="i">
                                <td class="px-4 fw-bold text-danger">{{ i + 1 }}</td>
                                <td>
                                    <span class="badge bg-danger bg-opacity-10 text-danger fw-bold">{{ v.type }}</span>
                                </td>
                                <td class="text-center">Kolom {{ v.column || '-' }}</td>
                                <td class="text-center">Soal #{{ v.question || '-' }}</td>
                                <td class="px-4 text-end font-monospace small text-muted">{{ v.time }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <div v-else class="alert alert-success border-0 shadow-sm mb-4">
            <i class="fa fa-check-circle me-1"></i> Tidak ada pelanggaran selama ujian berlangsung.
        </div>

    </div>
</template>

<script>
import LayoutAdmin from '../../../Layouts/Admin.vue';
import { Head, Link } from '@inertiajs/vue3';
import { onMounted, ref, computed } from 'vue';
import Chart from 'chart.js/auto';

export default {
    layout: LayoutAdmin,
    components: { Head, Link },
    props: {
        exam: Object,
        session: Object,
        chartData: Object,
        columnDetails: Array,
        violations: Array,
    },
    setup(props) {
        const chartCanvas = ref(null);

        const calculatedUnanswered = computed(() =>
            Math.max(0, 500 - ((props.session.total_correct || 0) + (props.session.total_wrong || 0)))
        );
        const correctPercentage = computed(() =>
            Math.round(((props.session.total_correct || 0) / 500) * 100)
        );
        const wrongPercentage = computed(() =>
            Math.round(((props.session.total_wrong || 0) / 500) * 100)
        );
        const unansweredPercentage = computed(() =>
            Math.round((calculatedUnanswered.value / 500) * 100)
        );
        const totalColumnDuration = computed(() =>
            props.columnDetails.reduce((total, detail) => total + Number(detail.time_spent || 0), 0)
        );
        const formatColumnDuration = (seconds) => {
            const safeSeconds = Math.max(0, Number(seconds) || 0);
            if (safeSeconds < 60) return `${safeSeconds} detik`;

            const minutes = Math.floor(safeSeconds / 60);
            const remainder = safeSeconds % 60;
            return remainder > 0
                ? `${minutes} menit ${remainder} detik`
                : `${minutes} menit`;
        };

        const printPage = () => {
            window.print();
        };

        onMounted(() => {
            if (!chartCanvas.value) return;
            new Chart(chartCanvas.value, {
                type: 'line',
                data: {
                    labels: props.chartData.labels.map(l => 'Kolom ' + l),
                    datasets: [
                        {
                            label: 'Benar',
                            data: props.chartData.correct,
                            borderColor: '#10b981',
                            backgroundColor: 'rgba(16,185,129,0.1)',
                            tension: 0.3,
                            fill: true,
                            pointRadius: 4,
                            pointHoverRadius: 6,
                        },
                        {
                            label: 'Salah',
                            data: props.chartData.wrong,
                            borderColor: '#ef4444',
                            backgroundColor: 'rgba(239,68,68,0.1)',
                            tension: 0.3,
                            fill: true,
                            pointRadius: 4,
                            pointHoverRadius: 6,
                        },
                    ],
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: true,
                            position: 'top',
                            labels: { usePointStyle: true, padding: 16, font: { size: 12, weight: '600' } },
                        },
                        tooltip: {
                            backgroundColor: '#1f2937',
                            padding: 10,
                        },
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            max: 50,
                            ticks: { stepSize: 10, font: { size: 11 } },
                            grid: { color: '#f1f5f9' },
                        },
                        x: {
                            ticks: { font: { size: 11 } },
                            grid: { display: false },
                        },
                    },
                },
            });
        });

        return {
            chartCanvas,
            calculatedUnanswered,
            correctPercentage,
            wrongPercentage,
            unansweredPercentage,
            totalColumnDuration,
            formatColumnDuration,
            printPage,
        };
    },
};
</script>

<style>
.result-total-row td:not(.text-success):not(.text-danger) {
    color: #1A2332 !important;
}

.result-total-row td {
    background-color: #F1F5F9 !important;
    border-bottom: 0 !important;
}

@media print {
    /* Sembunyikan semua elemen navigasi saat print */
    nav,
    .sidebar,
    .navbar,
    .content > nav,
    .no-print {
        display: none !important;
    }

    /* Buat main content full width */
    .content {
        margin-left: 0 !important;
        padding: 0 !important;
    }

    main.content {
        margin: 0 !important;
    }

    body {
        background: white !important;
    }

    /* Pastikan card tidak ada shadow saat print */
    .card {
        box-shadow: none !important;
        border: 1px solid #dee2e6 !important;
    }

    /* Print header hanya muncul saat print */
    .print-only {
        display: block !important;
    }

    /* Hindari page break di tengah card */
    .card {
        page-break-inside: avoid;
    }

    /* Grafik canvas agar terprint */
    canvas {
        max-width: 100% !important;
    }
}

/* Sembunyikan print header di layar normal */
.print-only {
    display: none;
}
</style>
