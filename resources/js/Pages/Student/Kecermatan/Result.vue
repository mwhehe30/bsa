<template>
    <Head>
        <title>Hasil Tes Kecermatan - Buweuk Sipit Academy</title>
    </Head>

    <div class="page-wrap">
        <div class="row justify-content-center">
            <div class="col-12 col-lg-9">
                    <!-- Tombol Kembali -->
                    <div class="mb-4 d-flex justify-content-start">
                        <Link
                            href="/student/dashboard"
                            class="btn-flat btn-flat-secondary btn-sm"
                        >
                            <i class="fa fa-chevron-left me-1"></i> Kembali
                        </Link>
                    </div>

                    <!-- Main Info Card -->
                    <div class="flat-card mb-4">
                        <div class="flat-header d-flex justify-content-between align-items-center">
                            <h5 class="flat-title mb-0">
                                <i class="fa fa-chart-line me-2 text-indigo"></i>Hasil Tes Kecermatan
                            </h5>
                            <span class="flat-badge badge-indigo">
                                Tipe {{ session.exam_type ? session.exam_type.toUpperCase() : 'GAMBAR' }}
                            </span>
                        </div>
                        <div class="card-body p-4">
                            <div class="info-table mb-3">
                                <div class="info-row">
                                    <span class="info-label">Nama Ujian</span>
                                    <span class="info-value fw-semibold">{{ exam.title }}</span>
                                </div>
                                <div class="info-row">
                                    <span class="info-label">Waktu Selesai</span>
                                    <span class="info-value">{{ session.finished_at }}</span>
                                </div>
                                <div class="info-row">
                                    <span class="info-label">Jawaban Benar</span>
                                    <span class="info-value">
                                        <span class="flat-badge badge-green">{{ session.total_correct }} Soal</span>
                                    </span>
                                </div>
                                <div class="info-row">
                                    <span class="info-label">Jawaban Salah</span>
                                    <span class="info-value">
                                        <span class="flat-badge badge-red">{{ session.total_wrong }} Soal</span>
                                    </span>
                                </div>
                                <div class="info-row">
                                    <span class="info-label">Tidak Dijawab</span>
                                    <span class="info-value">
                                        <span class="flat-badge badge-amber">{{ calculatedUnanswered }} Soal</span>
                                    </span>
                                </div>
                                <div class="info-row">
                                    <span class="info-label">Tingkat Akurasi</span>
                                    <span class="info-value">
                                        <span class="flat-badge badge-indigo">{{ correctPercentage }}%</span>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Line Chart Card -->
                    <div class="flat-card mb-4">
                        <div class="flat-header d-flex justify-content-between align-items-center">
                            <h5 class="flat-title mb-0">
                                <i class="fa fa-chart-area me-2 text-indigo"></i>Grafik Perkembangan Per Kolom
                            </h5>
                            <div class="d-flex align-items-center gap-3">
                                <div class="d-flex align-items-center gap-1">
                                    <span class="dot chart-dot-correct"></span>
                                    <span class="small-text">Benar</span>
                                </div>
                                <div class="d-flex align-items-center gap-1">
                                    <span class="dot bg-red"></span>
                                    <span class="small-text">Salah</span>
                                </div>
                            </div>
                        </div>
                        <div class="card-body p-4">
                            <div style="height: 260px; position: relative;">
                                <canvas ref="chartCanvas"></canvas>
                            </div>
                        </div>
                    </div>

                    <!-- Detail Per Kolom Table Card -->
                    <div class="flat-card mb-4">
                        <div class="flat-header">
                            <h5 class="flat-title mb-0">
                                <i class="fa fa-list-ol me-2 text-indigo"></i>Rincian Hasil Per Kolom
                            </h5>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="flat-table">
                                    <thead>
                                        <tr>
                                            <th class="text-center">KOLOM</th>
                                            <th class="text-center">BENAR</th>
                                            <th class="text-center">SALAH</th>
                                            <th class="text-center">TIDAK DIJAWAB</th>
                                            <th class="text-center">WAKTU PENGERJAAN</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr v-for="detail in columnDetails" :key="detail.column">
                                            <td class="text-center">
                                                <span class="flat-badge badge-indigo"><span class="d-none d-sm-inline">Kolom </span>{{ detail.column }}</span>
                                            </td>
                                            <td class="text-center">
                                                <span class="flat-badge badge-green">+{{ detail.correct }}</span>
                                            </td>
                                            <td class="text-center">
                                                <span class="flat-badge badge-red">{{ detail.wrong }}</span>
                                            </td>
                                            <td class="text-center">
                                                <span class="flat-badge badge-gray">{{ detail.unanswered }}</span>
                                            </td>
                                            <td class="text-center font-monospace">{{ formatColumnDuration(detail.time_spent) }}</td>
                                        </tr>
                                    </tbody>
                                    <tfoot>
                                        <tr class="summary-row">
                                            <td class="text-center fw-bold">TOTAL</td>
                                            <td class="text-center text-indigo fw-bold">+{{ session.total_correct }}</td>
                                            <td class="text-center text-red fw-bold">{{ session.total_wrong }}</td>
                                            <td class="text-center text-muted fw-bold">{{ calculatedUnanswered }}</td>
                                            <td class="text-center font-monospace fw-bold">{{ formatColumnDuration(totalColumnDuration) }}</td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
    </div>
</template>

<script>
import LayoutStudent from '../../../Layouts/Student.vue';
import { Head, Link } from '@inertiajs/vue3';
import { onMounted, ref, computed } from 'vue';
import Chart from 'chart.js/auto';

export default {
    layout: LayoutStudent,
    components: { Head, Link },
    props: {
        exam: Object,
        session: Object,
        chartData: Object,
        columnDetails: Array,
    },
    setup(props) {
        const chartCanvas = ref(null);

        const calculatedUnanswered = computed(() => {
            if (props.session.total_unanswered !== undefined && props.session.total_unanswered !== null) {
                return props.session.total_unanswered;
            }
            return Math.max(0, 500 - ((props.session.total_correct || 0) + (props.session.total_wrong || 0)));
        });

        const correctPercentage = computed(() => {
            return Math.round(((props.session.total_correct || 0) / 500) * 100);
        });

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
                            borderColor: '#4F46E5',
                            backgroundColor: 'rgba(79, 70, 229, 0.1)',
                            tension: 0.3,
                            fill: true,
                            pointRadius: 4,
                            pointHoverRadius: 6,
                        },
                        {
                            label: 'Salah',
                            data: props.chartData.wrong,
                            borderColor: '#ef4444',
                            backgroundColor: 'rgba(239, 68, 68, 0.1)',
                            tension: 0.3,
                            fill: true,
                            pointRadius: 4,
                            pointHoverRadius: 6,
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        },
                        tooltip: {
                            backgroundColor: '#1e293b',
                            padding: 10,
                            titleFont: { size: 13, weight: 'bold' },
                            bodyFont: { size: 12 }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            max: 50,
                            ticks: {
                                stepSize: 10,
                                font: { size: 11, weight: '600' }
                            },
                            grid: {
                                color: '#f1f5f9'
                            }
                        },
                        x: {
                            ticks: {
                                font: { size: 11, weight: '600' }
                            },
                            grid: {
                                display: false
                            }
                        }
                    }
                }
            });
        });

        return {
            chartCanvas,
            calculatedUnanswered,
            correctPercentage,
            totalColumnDuration,
            formatColumnDuration,
        };
    },
};
</script>

<style scoped>
.page-wrap {
    font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
    padding: 32px 0 64px;
}

/* ── Flat card ──────────────────────────────────── */
.flat-card {
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 16px;
    overflow: hidden;
}

.flat-header {
    background: #f8fafc;
    border-bottom: 1px solid #e2e8f0;
    padding: 16px 24px;
}

.flat-title {
    font-size: 1rem;
    font-weight: 600;
    color: #1e293b;
}

.text-indigo { color: #1A2332; }
.text-green { color: #16a34a; }
.text-red { color: #dc2626; }

.dot {
    width: 10px;
    height: 10px;
    border-radius: 50%;
    display: inline-block;
}

.bg-indigo { background-color: #1A2332; }
.chart-dot-correct { background-color: #4F46E5; }
.bg-red { background-color: #ef4444; }

.small-text {
    font-size: 0.8rem;
    font-weight: 600;
    color: #64748b;
}

/* ── Info table ─────────────────────────────────── */
.info-table {
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    overflow: hidden;
}

.info-row {
    display: flex;
    align-items: center;
    padding: 12px 16px;
    border-bottom: 1px solid #f1f5f9;
    gap: 12px;
}

.info-row:last-child {
    border-bottom: none;
}

.info-label {
    width: 170px;
    flex-shrink: 0;
    font-size: 0.85rem;
    font-weight: 600;
    color: #475569;
}

.info-value {
    font-size: 0.9rem;
    color: #1e293b;
}

/* ── Badges ─────────────────────────────────────── */
.flat-badge {
    font-size: 0.75rem;
    font-weight: 600;
    padding: 3px 10px;
    border-radius: 20px;
    display: inline-block;
}

.badge-indigo {
    background: #e0e7ff;
    color: #1A2332;
}

.badge-green {
    background: #dcfce7;
    color: #15803d;
}

.badge-red {
    background: #fee2e2;
    color: #dc2626;
}

.badge-amber {
    background: #fef3c7;
    color: #d97706;
}

.badge-gray {
    background: #f1f5f9;
    color: #475569;
}

/* ── Table ─────────────────────────────────────── */
.flat-table {
    width: 100%;
    border-collapse: collapse;
}

.flat-table th {
    background: #f8fafc;
    color: #475569;
    font-size: 0.75rem;
    font-weight: 700;
    letter-spacing: 0.05em;
    padding: 12px 16px;
    border-bottom: 1px solid #e2e8f0;
}

.flat-table td {
    padding: 12px 16px;
    border-bottom: 1px solid #f1f5f9;
    font-size: 0.875rem;
    color: #1e293b;
}

.flat-table tbody tr:hover {
    background-color: #f8fafc;
}

.summary-row td {
    background: #f8fafc;
    border-top: 2px solid #e2e8f0;
    padding: 14px 16px;
}

/* ── Buttons ─────────────────────────────────────── */
.btn-flat {
    display: inline-flex;
    align-items: center;
    font-size: 0.875rem;
    font-weight: 600;
    padding: 9px 20px;
    border-radius: 8px;
    border: none;
    cursor: pointer;
    text-decoration: none;
    transition: background 0.15s ease;
}

.btn-flat-primary {
    background: #1A2332;
    color: #fff;
}

.btn-flat-primary:hover {
    background: #1A2332;
    color: #fff;
}

.btn-flat-secondary {
    background: #f1f5f9;
    color: #475569;
}

.btn-flat-secondary:hover {
    background: #e2e8f0;
    color: #1e293b;
}

.btn-flat-secondary.btn-sm {
    padding: 6px 12px;
    font-size: 0.8rem;
}

/* ── Mobile Responsive ───────────────────────────── */
@media (max-width: 576px) {
    .page-wrap {
        padding: 16px 0 48px;
    }

    .flat-card {
        border-radius: 12px;
        margin-bottom: 0.85rem !important;
    }

    .flat-header {
        padding: 10px 12px;
        flex-direction: column;
        align-items: flex-start !important;
        gap: 6px;
    }

    .flat-header .flat-badge {
        align-self: flex-start;
    }

    .card-body {
        padding: 12px 10px !important;
    }

    .info-table {
        border-radius: 8px;
    }

    .info-row {
        flex-direction: column;
        align-items: flex-start;
        gap: 2px;
        padding: 8px 10px;
    }

    .info-label {
        width: 100%;
        font-size: 0.72rem;
        color: #64748b;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .info-value {
        font-size: 0.9rem;
        font-weight: 600;
        color: #0f172a;
    }    .btn-flat:not(.btn-sm) {
        width: 100%;
        justify-content: center;
    }
}</style>
