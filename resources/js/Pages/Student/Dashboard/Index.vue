<template>
    <Head title="Dashboard Ujian - Buweuk Sipit Academy" />

    <StudentLayout>
        <div class="page-wrap">
            <!-- Header Section -->
            <div class="header-section mb-4">
                <div
                    class="greeting-card bg-gradient-primary text-white rounded-3 shadow p-4"
                >
                    <div
                        class="d-flex align-items-center justify-content-between flex-wrap gap-3"
                    >
                        <div>
                            <h4 class="fw-bold mb-2 text-white">
                                <i class="fas fa-hand-wave me-2"></i>
                                Selamat Datang,
                                {{ $page.props.auth?.student?.name }}!
                            </h4>
                            <p class="mb-0 opacity-90">
                                Semangat mengerjakan ujian hari ini 💪
                            </p>
                        </div>
                        <div class="stats-mini d-flex gap-3">
                            <div class="stat-item text-center">
                                <div class="stat-value fw-bold">
                                    {{ props.available_exams.length }}
                                </div>
                                <div class="stat-label small">Tersedia</div>
                            </div>
                            <div class="stat-divider"></div>
                            <div class="stat-item text-center">
                                <div class="stat-value fw-bold">
                                    {{ totalHistoryCount }}
                                </div>
                                <div class="stat-label small">Selesai</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tabs -->
            <div class="tabs-container bg-white rounded-3 mb-3 p-3">
                <!-- Tab Buttons - Full width on mobile -->
                <div class="d-flex gap-2 mb-3 mb-md-0">
                    <button
                        @click="activeTab = 'available'"
                        class="tab-btn flex-fill"
                        :class="
                            activeTab === 'available'
                                ? 'tab-btn-active'
                                : 'tab-btn-inactive'
                        "
                    >
                        <i class="fas fa-list d-none d-sm-inline me-1"></i>
                        <span class="d-none d-sm-inline">Ujian Tersedia</span>
                        <span class="d-inline d-sm-none">Tersedia</span>
                    </button>
                    <button
                        @click="activeTab = 'history'"
                        class="tab-btn flex-fill"
                        :class="
                            activeTab === 'history'
                                ? 'tab-btn-active'
                                : 'tab-btn-inactive'
                        "
                    >
                        <i class="fas fa-history d-none d-sm-inline me-1"></i>
                        <span class="d-none d-sm-inline">Riwayat Ujian</span>
                        <span class="d-inline d-sm-none">Riwayat</span>
                    </button>
                </div>
            </div>

            <!-- Lesson Cards -->
            <section
                v-if="!selectedLesson"
                class="lesson-filter-section mb-4"
                aria-labelledby="lesson-filter-title"
            >
                <div
                    class="d-flex justify-content-between align-items-end mb-3"
                >
                    <div>
                        <h5 id="lesson-filter-title" class="fw-bold mb-1">
                            Pilih Mata Pelajaran
                        </h5>
                        <p class="text-muted small mb-0">
                            Klik mapel untuk menampilkan ujian yang tersedia.
                        </p>
                    </div>
                </div>

                <div class="lesson-grid">
                    <button
                        v-for="lesson in availableLessons"
                        :key="lesson.id"
                        type="button"
                        class="lesson-card"
                        :class="{
                            'lesson-card-active':
                                selectedLesson === lesson.name,
                            'lesson-card-thumbnail': lesson.thumbnail_url,
                        }"
                        :style="
                            lesson.thumbnail_url
                                ? {
                                      backgroundImage: `url(${lesson.thumbnail_url})`,
                                  }
                                : undefined
                        "
                        :aria-pressed="selectedLesson === lesson.name"
                        @click="selectedLesson = lesson.name"
                    >
                        <span class="lesson-name">{{ lesson.name }}</span>
                    </button>
                </div>
            </section>

            <div v-else class="selected-lesson-header mb-4">
                <div>
                    <span class="selected-lesson-label">Mata Pelajaran</span>
                    <h5 class="fw-bold mb-0">{{ selectedLesson }}</h5>
                </div>
                <button
                    type="button"
                    class="clear-lesson-btn"
                    @click="selectedLesson = null"
                >
                    <i class="fas fa-chevron-left"></i> Pilih Mapel Lain
                </button>
            </div>

            <!-- Available Exams Section -->
            <div v-if="activeTab === 'available'">
                <div class="row g-4 exam-list">
                    <div
                        v-if="selectedLesson && availableExamsList.length === 0"
                        class="col-12"
                    >
                        <div class="empty-card">
                            <div class="card-body text-center py-5">
                                <i
                                    class="fas fa-inbox fa-3x text-muted mb-3"
                                ></i>
                                <p class="text-muted mb-0">
                                    Belum ada ujian yang tersedia untuk Anda.
                                </p>
                            </div>
                        </div>
                    </div>

                    <div
                        v-for="item in selectedLesson ? availableExamsList : []"
                        :key="item.exam.id"
                        class="col-12"
                    >
                        <div
                            class="flat-card exam-card exam-list-card"
                            :class="{
                                'exam-list-clickable':
                                    item.can_start ||
                                    item.status === 'in_progress',
                            }"
                            :role="
                                item.can_start || item.status === 'in_progress'
                                    ? 'link'
                                    : undefined
                            "
                            :tabindex="
                                item.can_start || item.status === 'in_progress'
                                    ? 0
                                    : undefined
                            "
                            @click="openExam(item)"
                            @keydown.enter.prevent="openExam(item)"
                        >
                            <div class="card-body exam-list-body">
                                <div class="exam-list-icon">
                                    <i class="fas fa-file-alt"></i>
                                </div>
                                <div
                                    class="exam-list-title d-flex justify-content-between align-items-start mb-3"
                                >
                                    <h5 class="fw-bold mb-0">
                                        {{ item.exam.title }}
                                    </h5>
                                    <span
                                        class="flat-badge"
                                        :class="[
                                            getStatusBadgeClass(item.status),
                                            {
                                                'mobile-hide-available':
                                                    item.status === 'available',
                                            },
                                        ]"
                                    >
                                        {{ getStatusLabel(item.status) }}
                                    </span>
                                </div>

                                <div class="exam-list-meta mb-3">
                                    <div class="exam-stat">
                                        <i class="fas fa-list"></i>
                                        <template v-if="item.is_kecermatan">
                                            10 Kolom x 50 Soal
                                        </template>
                                        <template v-else>
                                            {{ item.exam.questions_count }} Soal
                                        </template>
                                    </div>
                                    <div class="exam-stat">
                                        <i class="fas fa-clock"></i>
                                        <small v-if="item.is_kecermatan"
                                            >60 detik / kolom</small
                                        >
                                        <small v-else
                                            >
                                            {{
                                                item.exam.duration
                                            }}
                                            menit</small
                                        >
                                    </div>
                                    <div
                                        class="d-flex align-items-center text-muted mb-2"
                                    >
                                        <i class="fas fa-list me-2"></i>
                                        <small v-if="item.is_kecermatan"
                                            >Pilih 1 Kategori (10 Kolom × 50
                                            Soal)</small
                                        >
                                        <small v-else
                                            >{{
                                                item.exam.questions_count
                                            }}
                                            Soal</small
                                        >
                                    </div>
                                </div>

                                <!-- Progress info for in_progress -->
                                <div
                                    v-if="item.status === 'in_progress'"
                                    class="exam-list-state flat-alert alert-warning-flat mb-3 py-2"
                                >
                                    <small class="fw-bold">
                                        <i class="fas fa-play-circle me-1"></i>
                                        <span v-if="item.is_kecermatan"
                                            >Kolom
                                            {{
                                                item.status_info
                                                    ?.current_column
                                            }}, Soal
                                            {{
                                                item.status_info
                                                    ?.current_question
                                            }}</span
                                        >
                                        <span v-else>Sedang Dikerjakan</span>
                                    </small>
                                    <div
                                        v-if="
                                            item.is_kecermatan &&
                                            item.status_info
                                        "
                                        class="progress mt-2"
                                        style="height: 5px"
                                    >
                                        <div
                                            class="progress-bar"
                                            :style="{
                                                width:
                                                    item.status_info.progress +
                                                    '%',
                                            }"
                                        ></div>
                                    </div>
                                </div>

                                <!-- Score info for completed -->
                                <div
                                    v-if="item.status === 'completed'"
                                    class="exam-list-state flat-alert alert-success-flat mb-3 py-2"
                                >
                                    <small class="fw-bold">
                                        <i class="fas fa-check-circle me-1"></i>
                                        <span v-if="item.is_kecermatan"
                                            >Selesai Dikerjakan</span
                                        >
                                        <span v-else
                                            >Nilai Terbaik:
                                            {{ item.highest_grade }}</span
                                        >
                                    </small>
                                </div>

                                <!-- Action Buttons -->
                                <div
                                    class="exam-list-action d-grid mt-auto pt-2"
                                >
                                    <Link
                                        v-if="item.can_start"
                                        :href="getStartLink(item)"
                                        class="btn-flat btn-flat-primary start-exam-btn justify-content-center"
                                        @click.stop
                                    >
                                        <span class="d-none d-md-inline">{{
                                            item.attempt_count > 0 ||
                                            (item.all_sessions &&
                                                item.all_sessions.length > 0)
                                                ? "Kerjakan Ulang Ujian"
                                                : "Mulai Ujian"
                                        }}</span>
                                        <span class="d-inline d-md-none"
                                            >Mulai</span
                                        >
                                    </Link>

                                    <Link
                                        v-else-if="
                                            item.status === 'in_progress'
                                        "
                                        :href="getResumeLink(item)"
                                        class="btn-flat btn-flat-warning justify-content-center"
                                        @click.stop
                                    >
                                        <i class="fas fa-arrow-right"></i>
                                        <span class="d-none d-md-inline"
                                            >Lanjutkan</span
                                        ><span class="d-inline d-md-none"
                                            >Lanjut</span
                                        >
                                    </Link>

                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- History Section -->
            <div v-if="activeTab === 'history'">
                <div class="row g-4 exam-list">
                    <div
                        v-if="selectedLesson && historyList.length === 0"
                        class="col-12"
                    >
                        <div class="empty-card">
                            <div class="card-body text-center py-5">
                                <i
                                    class="fas fa-history fa-3x text-muted mb-3"
                                ></i>
                                <p class="text-muted mb-0">
                                    Belum ada riwayat ujian.
                                </p>
                            </div>
                        </div>
                    </div>

                    <div
                        v-for="(history, index) in selectedLesson
                            ? historyList
                            : []"
                        :key="index"
                        class="col-12 col-md-6 col-lg-4"
                    >
                        <div
                            class="flat-card exam-card exam-list-card history-card"
                        >
                            <div class="card-body exam-list-body history-body">
                                <div class="exam-list-icon">
                                    <i class="fas fa-history"></i>
                                </div>
                                <div
                                    class="exam-list-title d-flex justify-content-between align-items-start mb-3"
                                >
                                    <h5 class="fw-bold mb-0">
                                        {{ history.exam.title }}
                                    </h5>
                                    <span class="flat-badge badge-green"
                                        >Percobaan #{{ history.attempt }}</span
                                    >
                                </div>

                                <div class="exam-list-meta mb-3">
                                    <span
                                        class="flat-badge badge-indigo mb-2 me-2"
                                    >
                                        <i class="fas fa-list me-1"></i>
                                        <template v-if="history.is_kecermatan">
                                            10 Kolom x 50 Soal
                                        </template>
                                        <template v-else>
                                            {{
                                                history.exam.questions_count
                                            }}
                                            Soal
                                        </template>
                                    </span>
                                    <div class="text-muted small mt-1">
                                        <i class="fas fa-calendar-alt me-1"></i>
                                        {{
                                            history.date
                                                ? new Date(
                                                      history.date,
                                                  ).toLocaleString("id-ID", {
                                                      dateStyle: "medium",
                                                      timeStyle: "short",
                                                  })
                                                : "-"
                                        }}
                                    </div>
                                </div>

                                <div
                                    class="exam-list-state flat-alert score-box py-3 mb-3 text-center mt-auto"
                                >
                                    <small
                                        class="d-block text-uppercase fw-bold mb-1"
                                        >Skor / Nilai Akhir</small
                                    >
                                    <h3 class="fw-bold mb-0 text-indigo">
                                        {{ history.score }}
                                    </h3>
                                </div>

                                <div class="exam-list-action text-center">
                                    <Link
                                        v-if="history.is_kecermatan"
                                        :href="`/student/kecermatan/result/${history.session_id}`"
                                        class="btn-flat btn-flat-outline w-100 justify-content-center"
                                    >
                                        <i class="fas fa-chart-line"></i>
                                        <span class="d-none d-md-inline"
                                            >Detail Hasil Kecermatan</span
                                        ><span class="d-inline d-md-none"
                                            >Detail</span
                                        >
                                    </Link>
                                    <Link
                                        v-else
                                        :href="`/student/exam-result/${history.grade_id}`"
                                        class="btn-flat btn-flat-outline w-100 justify-content-center"
                                    >
                                        <i class="fas fa-eye"></i>
                                        <span class="d-none d-md-inline"
                                            >Detail Hasil Ujian</span
                                        ><span class="d-inline d-md-none"
                                            >Detail</span
                                        >
                                    </Link>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </StudentLayout>
</template>

<script setup>
import { Head, Link, router } from "@inertiajs/vue3";
import StudentLayout from "@/Layouts/Student.vue";
import { ref, computed } from "vue";

const props = defineProps({
    available_exams: Array,
    lessons: Array,
});

const activeTab = ref("available");
const selectedLesson = ref(null);

// Compute all unique lessons for the filter dropdown
const availableLessons = computed(() => {
    return props.lessons || [];
});

const getLessonExamCount = (lesson) => {
    return props.available_exams.filter(
        (item) => (item.exam.lesson?.name || "Umum") === lesson,
    ).length;
};

const totalHistoryCount = computed(() =>
    props.available_exams.reduce((total, item) => {
        if (item.is_kecermatan) {
            return total + (item.all_sessions?.length || 0);
        }
        return total + (item.all_grades?.length || 0);
    }, 0),
);

// Filter available exams strictly for "Ujian Tersedia" section, and filter by selectedLesson
const availableExamsList = computed(() => {
    return props.available_exams.filter((item) => {
        const lessonName = item.exam.lesson?.name || "Umum";
        return lessonName === selectedLesson.value;
    });
});

// Flatten history from all exams, and filter by selectedLesson
const historyList = computed(() => {
    let history = [];

    props.available_exams.forEach((item) => {
        // filter early by lesson
        const lessonName = item.exam.lesson?.name || "Umum";
        if (!selectedLesson.value || lessonName !== selectedLesson.value)
            return;

        if (item.is_kecermatan) {
            if (item.all_sessions && item.all_sessions.length > 0) {
                item.all_sessions.forEach((sess, idx) => {
                    history.push({
                        exam: item.exam,
                        is_kecermatan: true,
                        session_id: sess.id,
                        score: sess.total_score,
                        attempt: item.all_sessions.length - idx,
                        date: sess.created_at,
                    });
                });
            }
        } else {
            if (item.all_grades && item.all_grades.length > 0) {
                item.all_grades.forEach((grade, idx) => {
                    history.push({
                        exam: item.exam,
                        is_kecermatan: false,
                        grade_id: grade.id,
                        score: grade.grade,
                        attempt:
                            grade.attempt_number ||
                            item.all_grades.length - idx,
                        date: grade.created_at,
                    });
                });
            }
        }
    });

    return history.sort((a, b) => new Date(b.date) - new Date(a.date));
});

const getStatusLabel = (status) => {
    const labels = {
        available: "Tersedia",
        in_progress: "Sedang Dikerjakan",
        completed: "Selesai",
        unavailable: "Tidak Tersedia",
    };
    return labels[status] || status;
};

const getStatusBadgeClass = (status) => {
    const classes = {
        available: "badge-green",
        in_progress: "badge-amber",
        completed: "badge-indigo",
        unavailable: "badge-gray",
    };
    return classes[status] || "badge-gray";
};

const getStartLink = (item) => {
    if (item.is_kecermatan) {
        return `/student/kecermatan/${item.exam.id}/select-type`;
    }
    return `/student/exam-confirmation/${item.exam.id}`;
};

const getResumeLink = (item) => {
    if (item.is_kecermatan) {
        return `/student/kecermatan/exam/${item.session_id}/${item.status_info?.current_column || 1}/${item.status_info?.current_question || 1}`;
    }
    const gradeId = item.in_progress_grade?.id || item.latest_grade?.id || 1;
    return `/student/exam/${item.exam.id}/${gradeId}/1`;
};

const openExam = (item) => {
    if (item.can_start) {
        router.visit(getStartLink(item));
    } else if (item.status === "in_progress") {
        router.visit(getResumeLink(item));
    }
};
</script>

<style scoped>
/* ── Page wrap ─────────────────────────────────── */
.page-wrap {
    font-family:
        "Inter",
        -apple-system,
        BlinkMacSystemFont,
        "Segoe UI",
        Roboto,
        sans-serif;
    padding: 32px 0 64px;
}

/* ── Flat card ─────────────────────────────────── */
.flat-card {
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 16px;
    overflow: hidden;
}

.empty-card {
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 16px;
}

/* ── Flat badges ───────────────────────────────── */
.flat-badge {
    font-size: 0.75rem;
    font-weight: 600;
    padding: 3px 10px;
    border-radius: 20px;
    display: inline-block;
}

.badge-indigo {
    background: #e0e7ff;
    color: #1a2332;
}

.badge-cyan {
    background: #e0f2fe;
    color: #0891b2;
}

.badge-green {
    background: #dcfce7;
    color: #15803d;
}

.badge-amber {
    background: #fef3c7;
    color: #d97706;
}

.badge-gray {
    background: #f1f5f9;
    color: #475569;
}

.text-indigo {
    color: #1a2332;
}

/* ── Flat buttons ──────────────────────────────── */
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
    background: #1a2332;
    color: #fff;
}

.btn-flat-primary:hover {
    background: #1a2332;
    color: #fff;
}

.btn-flat-warning {
    background: #fef3c7;
    color: #b45309;
}

.btn-flat-warning:hover {
    background: #fde68a;
    color: #92400e;
}

.btn-flat-secondary {
    background: #f1f5f9;
    color: #475569;
}

.btn-flat-secondary:hover {
    background: #e2e8f0;
    color: #1e293b;
}

.btn-flat-outline {
    background: #ffffff;
    border: 1px solid #1a2332;
    color: #1a2332;
}

.btn-flat-outline:hover {
    background: #1a2332;
    color: #ffffff;
}

.btn-flat:disabled {
    opacity: 0.6;
    cursor: not-allowed;
}

/* ── Flat alerts ───────────────────────────────── */
.flat-alert {
    border-radius: 12px;
    padding: 14px 18px;
    font-size: 0.88rem;
}

.alert-warning-flat {
    background: #fffbeb;
    color: #b45309;
    border: 1px solid #fde68a;
    padding: 8px 14px;
}

.alert-success-flat {
    background: #f0fdf4;
    color: #15803d;
    border: 1px solid #bbf7d0;
    padding: 8px 14px;
}

.score-box {
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    color: #475569;
}

/* ── Tabs ──────────────────────────────────────── */
.tabs-container {
    border: 1px solid #e2e8f0;
    border-radius: 16px;
    background: #ffffff;
}

.tab-btn {
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    background: #ffffff;
    padding: 8px 16px;
    font-size: 0.875rem;
    font-weight: 600;
    color: #475569;
    cursor: pointer;
    transition: all 0.15s ease;
}

.tab-btn-active {
    background: #1a2332;
    border-color: #1a2332;
    color: #ffffff;
}

.tab-btn-inactive:hover {
    border-color: #1a2332;
    color: #1a2332;
    background: #f8fafc;
}

/* Lesson filter cards */
.lesson-filter-section {
    padding: 20px;
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 16px;
}

.lesson-grid {
    display: grid;
    grid-template-columns: repeat(7, minmax(0, 1fr));
    gap: 12px;
}

.lesson-card {
    position: relative;
    isolation: isolate;
    overflow: hidden;
    min-width: 0;
    min-height: 126px;
    padding: 14px 10px;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 7px;
    color: #334155;
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    cursor: pointer;
    text-align: center;
    background-position: center;
    background-repeat: no-repeat;
    background-size: cover;
    transition:
        border-color 0.18s ease,
        background-color 0.18s ease,
        color 0.18s ease;
}

.lesson-card > * {
    position: relative;
    z-index: 1;
}

.lesson-card-thumbnail {
    color: #ffffff;
    border-color: #cbd5e1;
}

.lesson-card-thumbnail::before {
    position: absolute;
    z-index: 0;
    inset: 0;
    content: "";
    background: rgba(0, 0, 0, 0.1);
}

.lesson-card-thumbnail .lesson-icon {
    color: #ffffff;
    background: rgba(255, 255, 255, 0.2);
    backdrop-filter: blur(4px);
}

.lesson-card-thumbnail.lesson-card-active::before {
    background: rgba(0, 0, 0, 0.1);
}

.lesson-card:not(.lesson-card-thumbnail):hover,
.lesson-card-active {
    color: #ffffff;
    background: #1a2332;
    border-color: #1a2332;
}

.lesson-icon {
    width: 38px;
    height: 38px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    color: #1a2332;
    background: #f1f5f9;
    border-radius: 10px;
}

.lesson-card:not(.lesson-card-thumbnail):hover .lesson-icon,
.lesson-card-active .lesson-icon {
    color: #1a2332;
    background: #ffffff;
}

.lesson-name {
    width: 100%;
    font-size: 0.82rem;
    font-weight: 700;
    line-height: 1.3;
    overflow-wrap: anywhere;
}

.lesson-count {
    font-size: 0.72rem;
    opacity: 0.75;
}

.clear-lesson-btn {
    min-height: 38px;
    padding: 8px 12px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
    color: #ffffff;
    background: #1a2332;
    border: 1px solid #1a2332;
    border-radius: 8px;
    font-size: 0.8rem;
    font-weight: 600;
}

.selected-lesson-header {
    min-height: 74px;
    padding: 14px 18px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 14px;
}

.selected-lesson-label {
    display: block;
    margin-bottom: 2px;
    color: #64748b;
    font-size: 0.72rem;
    font-weight: 700;
    letter-spacing: 0.04em;
    text-transform: uppercase;
}

.selection-placeholder {
    min-height: 170px;
    margin-bottom: 1rem;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 10px;
    color: #64748b;
    background: #ffffff;
    border: 1px dashed #cbd5e1;
    border-radius: 16px;
    text-align: center;
}

.selection-placeholder i {
    color: #1a2332;
    font-size: 1.75rem;
}

/* Greeting card gradient */
.bg-gradient-primary {
    background: linear-gradient(135deg, #1a2332 0%, #2c3e50 100%);
}

.greeting-card .stat-value {
    font-size: 1.75rem;
    line-height: 1;
}

.greeting-card .stat-label {
    opacity: 0.9;
    font-size: 0.75rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.greeting-card .stat-divider {
    width: 1px;
    height: 40px;
    background: rgba(255, 255, 255, 0.3);
}

/* ── Exam card ─────────────────────────────────── */
.exam-card {
    transition:
        transform 0.2s ease,
        box-shadow 0.2s ease;
}

.exam-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 8px 16px rgba(0, 0, 0, 0.1) !important;
}

.exam-list-card {
    height: auto;
    border-radius: 10px;
    box-shadow: 0 2px 8px rgba(15, 23, 42, 0.04);
}

.exam-list-body {
    min-height: 82px;
    padding: 14px 16px !important;
    display: grid;
    grid-template-columns: 44px minmax(0, 1fr) auto;
    grid-template-areas:
        "icon title action"
        "icon meta action"
        "state state state";
    column-gap: 16px;
    row-gap: 4px;
    align-items: center;
}

.exam-list-action {
    grid-area: action;
    margin: 0;
    padding-top: 0 !important;
    align-self: center;
}

.exam-list-meta {
    grid-area: meta;
    display: flex;
    align-items: center;
    gap: 10px;
    margin-bottom: 0 !important;
}

.exam-list-meta > .d-flex.align-items-center.text-muted.mb-2 {
    display: none !important;
}

.exam-stat {
    min-height: 0;
    padding: 0;
    display: flex;
    align-items: center;
    gap: 5px;
    color: #64748b;
    background: transparent;
    border: 0;
    border-radius: 0;
    font-size: 0.78rem;
    font-weight: 700;
    line-height: 1.2;
    white-space: nowrap;
}

.exam-stat i {
    color: #1a2332;
    font-size: 0.85rem;
}

.exam-stat span,
.exam-stat small {
    min-width: 0;
    overflow: hidden;
    text-overflow: ellipsis;
}

.exam-list-icon {
    grid-area: icon;
    width: 44px;
    height: 44px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    align-self: center;
    color: #ffffff;
    background: #1a2332;
    border-radius: 9px;
    font-size: 1rem;
}

.exam-list-title {
    grid-area: title;
    min-width: 0;
    margin-bottom: 1px !important;
}

.exam-list-title h5 {
    min-width: 0;
    font-size: 0.98rem;
    line-height: 1.25;
    overflow-wrap: anywhere;
}

.exam-list-state {
    grid-area: state;
    margin: 8px 0 0 !important;
}

.mobile-list-info {
    display: none;
}

.exam-list-clickable {
    cursor: pointer;
}

.exam-list-clickable:focus-visible {
    outline: 3px solid rgba(26, 35, 50, 0.22);
    outline-offset: 2px;
}

.exam-list-title .mobile-hide-available {
    display: none;
}

.history-card {
    height: 100%;
    border-radius: 16px;
    box-shadow: none;
}

.history-body {
    height: 100%;
    min-height: 0;
    padding: 20px !important;
    display: flex;
    flex-direction: column;
    align-items: stretch;
}

.history-body .exam-list-icon {
    display: none;
}

.history-body .exam-list-title,
.history-body .exam-list-meta,
.history-body .exam-list-state,
.history-body .exam-list-action {
    grid-area: auto;
}

.history-body .exam-list-title {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    margin-bottom: 1rem !important;
}

.history-body .exam-list-title h5 {
    font-size: 1.1rem;
    line-height: 1.3;
}

.history-body .exam-list-meta {
    display: block;
    margin-bottom: 1rem !important;
}

.history-body .exam-list-state {
    display: block;
    margin-top: auto !important;
    margin-bottom: 1rem !important;
}

.history-body .exam-list-action {
    display: block;
    margin-top: 0;
    padding-top: 0 !important;
    align-self: stretch;
}

/* Progress bar indigo (konsisten dengan aksen) */
.progress-bar {
    background-color: #1a2332;
}

/* ── Form select (aksen indigo) ────────────────── */
.form-select.border-primary {
    border-color: #1a2332 !important;
    background-color: #f8f9fa;
    color: #1a2332;
    cursor: pointer;
}
.form-select.border-primary:focus {
    box-shadow: 0 0 0 0.25rem rgba(79, 70, 229, 0.25) !important;
}

/* ── Mobile optimizations ──────────────────────── */
@media (max-width: 767.98px) {
    .greeting-card {
        padding: 1.5rem 1rem !important;
    }

    .greeting-card h4 {
        font-size: 1.25rem;
    }

    .greeting-card p {
        font-size: 0.875rem;
    }

    .greeting-card .stat-value {
        font-size: 1.5rem;
    }

    .greeting-card .stat-label {
        font-size: 0.7rem;
    }

    .tabs-container {
        padding: 1rem !important;
    }

    .lesson-filter-section {
        padding: 16px;
    }

    .lesson-grid {
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 10px;
    }

    .lesson-card {
        min-height: 112px;
    }

    .exam-list {
        --bs-gutter-y: 8px;
    }

    .exam-list > [class*="col-"] {
        padding-top: 0;
        padding-bottom: 0;
    }

    .exam-list-card,
    .exam-list-card:hover {
        height: auto;
        overflow: hidden;
        background: #ffffff;
        border: 1px solid #e2e8f0 !important;
        border-radius: 10px !important;
        box-shadow: 0 2px 8px rgba(15, 23, 42, 0.04) !important;
        transform: none;
    }

    .exam-list-body {
        min-height: 68px;
        padding: 10px 11px !important;
        display: grid;
        grid-template-columns: 38px minmax(0, 1fr) auto;
        grid-template-areas:
            "icon title action"
            "icon meta action"
            "state state state";
        column-gap: 14px;
        row-gap: 2px;
        align-items: center;
    }

    .exam-list-icon {
        grid-area: icon;
        width: 38px;
        height: 38px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        align-self: center;
        color: #ffffff;
        background: #1a2332;
        border-radius: 8px;
        font-size: 0.95rem;
    }

    .exam-list-title {
        grid-area: title;
        min-width: 0;
        margin-bottom: 2px !important;
    }

    .exam-list-title h5 {
        min-width: 0;
        display: -webkit-box;
        overflow: hidden;
        font-size: 0.9rem;
        line-height: 1.22;
        overflow-wrap: anywhere;
        -webkit-box-orient: vertical;
        -webkit-line-clamp: 2;
    }

    .exam-list-title .flat-badge {
        margin-left: 6px;
        padding: 2px 6px;
        flex-shrink: 0;
        font-size: 0.62rem;
    }

    .exam-list-title .mobile-hide-available {
        display: none;
    }

    .exam-list-meta {
        grid-area: meta;
        min-width: 0;
        margin-bottom: 0 !important;
        display: flex;
        align-items: center;
        gap: 6px;
        color: #64748b;
        font-size: 0.72rem;
    }

    .exam-list-meta > .d-flex.align-items-center.text-muted.mb-2 {
        display: none !important;
    }

    .exam-list-meta > .d-flex,
    .exam-list-meta > .text-muted {
        min-width: 0;
        margin: 0 !important;
        display: inline-flex !important;
        align-items: center;
        color: #64748b !important;
        line-height: 1.2;
        white-space: nowrap;
    }

    .exam-list-meta > .d-flex:nth-of-type(n + 2) {
        display: none !important;
    }

    .exam-list-meta .exam-stat {
        min-height: 0;
        padding: 0;
        display: inline-flex;
        gap: 4px;
        background: transparent;
        border: 0;
        border-radius: 0;
        color: #64748b;
        font-size: 0.68rem;
        font-weight: 700;
        white-space: nowrap;
    }

    .exam-list-meta .exam-stat + .exam-stat::before {
        content: "";
        width: 3px;
        height: 3px;
        margin-right: 2px;
        display: inline-block;
        background: #cbd5e1;
        border-radius: 999px;
    }

    .exam-list-meta > .d-flex small,
    .exam-list-meta > .text-muted {
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .exam-list-meta .flat-badge {
        max-width: 46%;
        margin: 0 !important;
        padding: 2px 6px;
        overflow: hidden;
        font-size: 0.64rem;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .exam-list-state {
        grid-area: state;
        margin: 7px 0 0 !important;
        padding: 6px 8px !important;
        border-radius: 8px;
        line-height: 1.2;
    }

    .exam-list-action {
        grid-area: action;
        width: auto;
        margin: 0;
        padding-top: 0 !important;
        align-self: center;
        display: flex !important;
        align-items: center;
        gap: 6px;
    }

    .exam-list-action .btn-flat {
        min-width: 44px;
        min-height: 34px;
        padding: 7px 9px;
        display: inline-flex;
        border-radius: 8px;
        font-size: 0.72rem;
        line-height: 1;
        white-space: nowrap;
    }

    .exam-list-action .start-exam-btn {
        display: none;
    }

    .exam-list-action .btn-flat i {
        margin-right: 0 !important;
    }

    .mobile-list-info {
        min-width: 0;
        min-height: 0;
        padding: 0;
        display: inline-flex;
        flex-direction: row;
        align-items: center;
        justify-content: center;
        gap: 4px;
        color: #64748b;
        background: transparent;
        border-radius: 0;
        font-size: 0.66rem;
        font-weight: 700;
        line-height: 1;
        text-align: center;
    }

    .mobile-list-info i {
        color: #1a2332;
    }

    /* Smaller text on mobile */
    h3.fw-bold {
        font-size: 1.5rem;
    }

    p.text-muted {
        font-size: 0.875rem;
    }

    /* Card adjustments */
    .exam-card {
        font-size: 0.9rem;
    }

    .exam-card h5 {
        font-size: 1.1rem;
    }

    .exam-card .flat-badge {
        font-size: 0.75rem;
    }

    .exam-card .btn-flat {
        font-size: 0.875rem;
        padding: 0.5rem;
    }

    .history-body {
        min-height: 0;
        padding: 14px !important;
        display: flex;
        flex-direction: column;
        align-items: stretch;
    }

    .history-body .exam-list-icon {
        display: none;
    }

    .history-body .exam-list-title,
    .history-body .exam-list-meta,
    .history-body .exam-list-state,
    .history-body .exam-list-action {
        grid-area: auto;
    }

    .history-body .exam-list-title {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        margin-bottom: 0.75rem !important;
    }

    .history-body .exam-list-title h5 {
        font-size: 1rem;
    }

    .history-body .exam-list-meta {
        display: block;
        margin-bottom: 0.75rem !important;
    }

    .history-body .exam-list-meta .flat-badge {
        display: inline-flex;
        align-items: center;
        margin-bottom: 0.35rem !important;
    }

    .history-body .exam-list-meta > .text-muted {
        display: block !important;
        margin-top: 0.25rem !important;
        white-space: normal;
    }

    .history-body .exam-list-state {
        display: block;
        margin-top: auto !important;
        margin-bottom: 0.75rem !important;
    }

    .history-body .exam-list-action {
        display: block;
        align-self: stretch;
    }
}

@media (max-width: 575.98px) {
    .page-wrap {
        padding: 16px 0 48px;
    }

    .greeting-card {
        padding: 1.25rem 0.875rem !important;
    }

    .greeting-card h4 {
        font-size: 1.1rem;
    }

    .greeting-card .stats-mini {
        width: 100%;
        justify-content: center;
    }

    .tabs-container {
        padding: 0.75rem !important;
    }

    .selected-lesson-header {
        align-items: flex-start;
        flex-direction: column;
    }

    .selected-lesson-header .clear-lesson-btn {
        width: 100%;
    }
}

/* Desktop layout - side by side */
@media (min-width: 768px) {
    .tabs-container {
        display: block;
    }

    .tabs-container > div:first-child {
        max-width: 520px;
    }
}

@media (min-width: 768px) and (max-width: 1199.98px) {
    .lesson-grid {
        grid-template-columns: repeat(4, minmax(0, 1fr));
    }
}
</style>
