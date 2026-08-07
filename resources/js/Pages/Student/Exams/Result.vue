<template>
    <Head>
        <title>Hasil Ujian - Buweuk Sipit Academy</title>
    </Head>
    <div class="page-wrap">
        <div class="row justify-content-center">
            <div class="col-12 col-lg-8">
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
                            <i class="fa fa-chart-line me-2 text-indigo"></i>Hasil Ujian
                        </h5>
                    </div>
                    <div class="card-body p-4">

                        <div class="info-table">
                            <div class="info-row">
                                <span class="info-label">Nama Ujian</span>
                                <span class="info-value">{{ exam_group.exam.title }}</span>
                            </div>
                            <div class="info-row">
                                <span class="info-label">Mata Pelajaran</span>
                                <span class="info-value">{{ exam_group.exam.lesson.name }}</span>
                            </div>
                            <div class="info-row">
                                <span class="info-label">Tipe Ujian</span>
                                <span class="info-value">
                                    <span v-if="isPersonality" class="flat-badge badge-cyan">Kepribadian</span>
                                    <span v-else class="flat-badge badge-indigo">Pilihan Ganda</span>
                                </span>
                            </div>
                            <div v-if="!isPersonality" class="info-row">
                                <span class="info-label">Jawaban Benar</span>
                                <span class="info-value">
                                    <span class="flat-badge badge-green">{{ grade.total_correct }} Soal</span>
                                </span>
                            </div>
                            <div v-if="isPersonality" class="info-row">
                                <span class="info-label">Total Point</span>
                                <span class="info-value">
                                    <span class="flat-badge badge-indigo">{{ grade.total_points }} / {{ grade.max_points }}</span>
                                </span>
                            </div>
                            <div class="info-row">
                                <span class="info-label">Nilai</span>
                                <span class="info-value">
                                    <span class="flat-badge badge-indigo">{{ grade.grade }}</span>
                                </span>
                            </div>
                            <div v-if="isPersonality" class="info-row">
                                <span class="info-label">Status</span>
                                <span class="info-value">
                                    <span v-if="grade.status === 'baik'" class="flat-badge badge-green">
                                        <i class="fa fa-check-circle me-1"></i> Baik
                                    </span>
                                    <span v-else class="flat-badge badge-red">
                                        <i class="fa fa-times-circle me-1"></i> Kurang Baik
                                    </span>
                                </span>
                            </div>
                        </div>

                        <!-- Status Alert Box -->
                        <div v-if="isPersonality" class="mt-4">
                            <div
                                class="flat-alert"
                                :class="grade.status === 'baik' ? 'alert-success-flat' : 'alert-danger-flat'"
                            >
                                <h6 class="fw-semibold mb-2">
                                    <i :class="grade.status === 'baik' ? 'fa fa-check-circle me-2' : 'fa fa-times-circle me-2'"></i>
                                    {{ grade.status === 'baik' ? 'Selamat!' : 'Perlu Perbaikan' }}
                                </h6>
                                <p class="mb-0">
                                    Anda mendapatkan score <strong>{{ grade.total_points }}/{{ grade.max_points }} ({{ grade.grade }}%)</strong> — dinyatakan <strong>{{ grade.status === 'baik' ? 'Baik' : 'Kurang Baik' }}</strong>.
                                </p>
                            </div>
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

        return { isPersonality };
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

.text-indigo { color: #4f46e5; }

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
    width: 160px;
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
}

.badge-indigo {
    background: #e0e7ff;
    color: #4f46e5;
}

.badge-cyan {
    background: #e0f2fe;
    color: #0891b2;
}

.badge-green {
    background: #dcfce7;
    color: #15803d;
}

.badge-red {
    background: #fee2e2;
    color: #dc2626;
}

/* ── Alerts ─────────────────────────────────────── */
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

.alert-danger-flat {
    background: #fee2e2;
    color: #dc2626;
    border: 1px solid #fca5a5;
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
    background: #4f46e5;
    color: #fff;
}

.btn-flat-primary:hover {
    background: #4338ca;
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

/* ── Mobile Responsive ───────────────────────────── */
@media (max-width: 576px) {
    .page-wrap {
        padding: 10px 0 24px;
    }

    .flat-card {
        border-radius: 12px;
        margin-bottom: 0.85rem !important;
    }

    .flat-header {
        padding: 10px 12px;
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
    }

    .btn-flat {
        width: 100%;
        justify-content: center;
    }
}
</style>
