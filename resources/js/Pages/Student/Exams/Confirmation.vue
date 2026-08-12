<template>
    <Head>
        <title>Konfirmasi Ujian - Buweuk Sipit Academy</title>
    </Head>

    <div class="page-wrap">
        <div class="row justify-content-center">
            <div class="col-12 col-md-9 col-lg-8">
                <!-- Tombol Kembali -->
                <div class="mb-4 d-flex justify-content-start">
                    <Link
                        href="/student/dashboard"
                        class="btn-flat btn-flat-secondary btn-sm"
                    >
                        <i class="fa fa-chevron-left me-1"></i> Kembali
                    </Link>
                </div>

                <div class="flat-card">
                    <div class="flat-header">
                        <h5 class="flat-title mb-0">
                            <i
                                class="fa fa-clipboard-check me-2 text-indigo"
                            ></i>Konfirmasi Ujian
                        </h5>
                    </div>
                    <div class="card-body p-4">
                        <!-- Badge Tipe Ujian -->
                        <div class="mb-3">
                            <span
                                v-if="isPersonality"
                                class="type-badge type-badge-info"
                            >
                                <i class="fa fa-brain me-1"></i> Ujian Kepribadian
                            </span>
                            <span v-else class="type-badge">
                                <i class="fa fa-clipboard-check me-1"></i> Ujian Pilihan Ganda
                            </span>
                        </div>

                        <!-- Judul -->
                        <h4 class="exam-title mb-1">{{ exam_group.exam.title }}</h4>
                        <p class="text-muted small mb-4">
                            {{ exam_group.exam.lesson.name }}
                        </p>

                        <!-- Info Ujian -->
                        <div class="info-table mb-4">
                            <div class="info-row">
                                <span class="info-label">Durasi</span>
                                <span class="info-value">
                                    {{ exam_group.exam.duration }} Menit
                                </span>
                            </div>
                            <div class="info-row">
                                <span class="info-label">Jumlah Soal</span>
                                <span class="info-value">
                                    {{ exam_group.exam.questions_count || '?' }} Soal
                                </span>
                            </div>
                            <div class="info-row">
                                <span class="info-label">Sistem</span>
                                <span class="info-value">
                                    {{ isPersonality ? 'Skoring Poin' : 'Nilai Otomatis' }}
                                </span>
                            </div>
                        </div>

                        <!-- Nilai Terakhir -->
                        <div
                            v-if="grade && grade.end_time"
                            class="flat-alert alert-success-flat mb-4"
                        >
                            <div class="d-flex align-items-center">
                                <i class="fa fa-chart-bar fa-lg me-3"></i>
                                <div>
                                    <div class="fw-bold">
                                        Nilai Terakhir: {{ grade.grade }}
                                    </div>
                                    <small>
                                        Anda sudah pernah mengerjakan ujian ini.
                                        Ulangi untuk mendapatkan nilai yang lebih baik.
                                    </small>
                                </div>
                            </div>
                        </div>

                        <hr class="divider-line my-4" />

                        <!-- Perhatian -->
                        <h6 class="form-section-title mb-3">
                            <i
                                class="fa fa-exclamation-triangle me-2 text-indigo"
                            ></i>Perhatian Sebelum Memulai
                        </h6>
                        <ul class="instruction-list">
                            <li>Pastikan koneksi internet Anda stabil</li>
                            <li>Jangan menutup browser atau keluar dari halaman ujian</li>
                            <li>Waktu ujian akan terus berjalan setelah tombol mulai ditekan</li>
                            <li v-if="isPersonality">
                                Semua soal wajib dijawab sebelum dapat mensubmit ujian
                            </li>
                        </ul>

                        <!-- Tombol Mulai -->
                        <button
                            @click="startExam"
                            class="btn-flat btn-flat-primary w-100 justify-content-center py-2 mt-4"
                        >
                            <i class="fa fa-play me-2"></i>
                            {{ grade && grade.end_time ? 'Ulangi Ujian' : 'Mulai Ujian' }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import LayoutStudent from '../../../Layouts/Student.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { computed } from 'vue';

export default {
    layout: LayoutStudent,
    components: { Head, Link },
    props: {
        exam_group: Object,
        grade: Object,
    },
    setup(props) {
        const isPersonality = computed(() => {
            const name = props.exam_group?.exam?.lesson?.name;
            if (!name || typeof name !== 'string') return false;
            const normalized = name.toLowerCase().trim();
            return normalized === 'kepribadian' || normalized.startsWith('kepribadian ');
        });

        const isKecermatan = computed(() => {
            const name = props.exam_group?.exam?.lesson?.name;
            if (!name || typeof name !== 'string') return false;
            const normalized = name.toLowerCase().trim();
            return normalized === 'kecermatan' || normalized.startsWith('kecermatan ');
        });

        const startExam = () => {
            // Redirect ke kecermatan flow jika ujian kecermatan
            if (isKecermatan.value) {
                router.get(`/student/kecermatan/${props.exam_group.exam.id}/select-type`);
            } else {
                // Regular exam flow
                router.get(`/student/exam-start/${props.exam_group.id}`, {
                    exam_id: props.exam_group.exam.id,
                });
            }
        };

        return { isPersonality, isKecermatan, startExam };
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

/* ── Badge & Title ─────────────────────────────── */
.type-badge {
    display: inline-flex;
    align-items: center;
    font-size: 0.75rem;
    font-weight: 600;
    padding: 5px 14px;
    border-radius: 999px;
    background: #eef2ff;
    color: #1A2332;
}

.type-badge-info {
    background: #e0f2fe;
    color: #0891b2;
}

.exam-title {
    font-size: 1.4rem;
    font-weight: 700;
    color: #1e293b;
}

/* ── Info table ────────────────────────────────── */
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
    width: 120px;
    flex-shrink: 0;
    font-size: 0.85rem;
    font-weight: 600;
    color: #475569;
}

.info-value {
    font-size: 0.9rem;
    color: #1e293b;
}

.divider-line {
    border-top: 1px solid #e2e8f0;
}

/* ── Sections ──────────────────────────────────── */
.form-section-title {
    font-size: 0.9rem;
    font-weight: 600;
    color: #475569;
}

.instruction-list {
    margin: 0;
    padding: 0;
    list-style: none;
    font-size: 0.88rem;
    color: #475569;
}

.instruction-list li {
    position: relative;
    padding: 5px 0 5px 22px;
}

.instruction-list li::before {
    content: '';
    position: absolute;
    left: 2px;
    top: 12px;
    width: 7px;
    height: 7px;
    border-radius: 50%;
    background: #1A2332;
}

/* ── Alerts ────────────────────────────────────── */
.flat-alert {
    border-radius: 12px;
    padding: 14px 18px;
    font-size: 0.88rem;
}

.alert-success-flat {
    background: #f0fdf4;
    color: #15803d;
    border: 1px solid #bbf7d0;
}

/* ── Buttons ───────────────────────────────────── */
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

/* ── Responsive ────────────────────────────────── */
@media (max-width: 767.98px) {
    .flat-header {
        padding: 12px 16px;
    }

    .card-body {
        padding: 1.25rem !important;
    }
}

@media (max-width: 575.98px) {
    .page-wrap {
        padding: 16px 0 48px;
    }

    .exam-title {
        font-size: 1.2rem;
    }

    .info-label {
        width: 100px;
        font-size: 0.8rem;
    }

    .info-value {
        font-size: 0.85rem;
    }

    .flat-header {
        padding: 12px 16px;
    }

    .card-body {
        padding: 1rem !important;
    }
}

@media (max-width: 400px) {
    .info-row {
        flex-direction: column;
        align-items: flex-start;
        gap: 2px;
    }

    .info-label {
        width: auto;
    }
}
</style>
