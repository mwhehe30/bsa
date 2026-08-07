<template>
    <Head title="Dashboard Ujian" />
    
    <StudentLayout>
        <div class="container py-4">
            <!-- Header Section -->
            <div class="header-section mb-4">
                <div class="greeting-card bg-gradient-primary text-white rounded-3 shadow p-4">
                    <div class="d-flex align-items-center justify-content-between flex-wrap gap-3">
                        <div>
                            <h4 class="fw-bold mb-2 text-white">
                                <i class="fas fa-hand-wave me-2"></i>
                                Selamat Datang, {{ $page.props.auth?.student?.name }}!
                            </h4>
                            <p class="mb-0 opacity-90">Semangat mengerjakan ujian hari ini 💪</p>
                        </div>
                        <div class="stats-mini d-flex gap-3">
                            <div class="stat-item text-center">
                                <div class="stat-value fw-bold">{{ availableExamsList.length }}</div>
                                <div class="stat-label small">Tersedia</div>
                            </div>
                            <div class="stat-divider"></div>
                            <div class="stat-item text-center">
                                <div class="stat-value fw-bold">{{ historyList.length }}</div>
                                <div class="stat-label small">Selesai</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tabs/Filters -->
            <div class="tabs-container bg-white rounded-3 shadow-sm mb-4 p-3">
                <!-- Tab Buttons - Full width on mobile -->
                <div class="d-flex gap-2 mb-3 mb-md-0">
                    <button 
                        @click="activeTab = 'available'"
                        class="btn btn-sm flex-fill"
                        :class="activeTab === 'available' ? 'btn-primary' : 'btn-outline-primary'"
                    >
                        <i class="fas fa-list d-none d-sm-inline me-1"></i>
                        <span class="d-none d-sm-inline">Ujian Tersedia</span>
                        <span class="d-inline d-sm-none">Tersedia</span>
                    </button>
                    <button 
                        @click="activeTab = 'history'"
                        class="btn btn-sm flex-fill"
                        :class="activeTab === 'history' ? 'btn-primary' : 'btn-outline-primary'"
                    >
                        <i class="fas fa-history d-none d-sm-inline me-1"></i>
                        <span class="d-none d-sm-inline">Riwayat Ujian</span>
                        <span class="d-inline d-sm-none">Riwayat</span>
                    </button>
                </div>
                
                <!-- Lesson Filter - Full width on mobile -->
                <div class="d-flex align-items-center gap-2">
                    <span class="text-muted small fw-bold text-nowrap">Mapel:</span>
                    <select class="form-select form-select-sm shadow-none border-primary text-primary fw-semibold flex-grow-1" v-model="selectedLesson">
                        <option v-for="lesson in availableLessons" :key="lesson" :value="lesson">
                            {{ lesson }}
                        </option>
                    </select>
                </div>
            </div>

            <!-- Available Exams Section -->
            <div v-if="activeTab === 'available'">
                <div class="row g-4">
                    <div v-if="availableExamsList.length === 0" class="col-12">
                        <div class="card border-0 shadow-sm">
                            <div class="card-body text-center py-5">
                                <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                                <p class="text-muted mb-0">Belum ada ujian yang tersedia untuk Anda.</p>
                            </div>
                        </div>
                    </div>

                    <div v-for="item in availableExamsList" :key="item.exam.id" class="col-md-6 col-lg-4">
                        <div class="card exam-card h-100 border-0 shadow-sm">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-start mb-3">
                                    <h5 class="fw-bold mb-0">{{ item.exam.title }}</h5>
                                    <span class="badge" :class="getStatusBadgeClass(item.status)">
                                        {{ getStatusLabel(item.status) }}
                                    </span>
                                </div>

                                <div class="mb-3">
                                    <span class="badge bg-primary mb-2 me-2">
                                        <i class="fas fa-book me-1"></i> {{ item.exam.lesson?.name || 'Umum' }}
                                    </span>
                                    <span v-if="item.is_kecermatan" class="badge bg-info mb-2 text-white">
                                        <i class="fas fa-bolt me-1"></i> Kecermatan
                                    </span>

                                    <div class="d-flex align-items-center text-muted mb-2 mt-2">
                                        <i class="fas fa-clock me-2"></i>
                                        <small v-if="item.is_kecermatan">Durasi: 60 detik / kolom</small>
                                        <small v-else>Durasi: {{ item.exam.duration }} menit</small>
                                    </div>
                                    <div class="d-flex align-items-center text-muted mb-2">
                                        <i class="fas fa-list me-2"></i>
                                        <small v-if="item.is_kecermatan">Pilih 1 Kategori (10 Kolom × 50 Soal)</small>
                                        <small v-else>{{ item.exam.questions_count }} Soal</small>
                                    </div>
                                </div>

                                <!-- Progress info for in_progress -->
                                <div v-if="item.status === 'in_progress'" class="alert alert-warning mb-3 py-2">
                                    <small class="fw-bold">
                                        <i class="fas fa-play-circle me-1"></i>
                                        <span v-if="item.is_kecermatan">Kolom {{ item.status_info?.current_column }}, Soal {{ item.status_info?.current_question }}</span>
                                        <span v-else>Sedang Dikerjakan</span>
                                    </small>
                                    <div v-if="item.is_kecermatan && item.status_info" class="progress mt-2" style="height: 5px;">
                                        <div class="progress-bar" :style="{ width: item.status_info.progress + '%' }"></div>
                                    </div>
                                </div>

                                <!-- Score info for completed -->
                                <div v-if="item.status === 'completed'" class="alert alert-success mb-3 py-2">
                                    <small class="fw-bold">
                                        <i class="fas fa-check-circle me-1"></i>
                                        <span v-if="item.is_kecermatan">Selesai Dikerjakan</span>
                                        <span v-else>Nilai Terbaik: {{ item.highest_grade }}</span>
                                    </small>
                                </div>

                                <!-- Action Buttons -->
                                <div class="d-grid mt-auto pt-2">
                                    <Link 
                                        v-if="item.can_start"
                                        :href="getStartLink(item)"
                                        class="btn btn-primary"
                                    >
                                        <i class="fas fa-play me-2"></i> {{ (item.attempt_count > 0 || (item.all_sessions && item.all_sessions.length > 0)) ? 'Kerjakan Ulang Ujian' : 'Mulai Ujian' }}
                                    </Link>

                                    <Link 
                                        v-else-if="item.status === 'in_progress'"
                                        :href="getResumeLink(item)"
                                        class="btn btn-warning"
                                    >
                                        <i class="fas fa-arrow-right me-2"></i> Lanjutkan
                                    </Link>

                                    <button 
                                        v-else
                                        class="btn btn-secondary" disabled
                                    >
                                        <i class="fas fa-lock me-2"></i> Tidak Tersedia
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- History Section -->
            <div v-if="activeTab === 'history'">
                <div class="row g-4">
                    <div v-if="historyList.length === 0" class="col-12">
                        <div class="card border-0 shadow-sm">
                            <div class="card-body text-center py-5">
                                <i class="fas fa-history fa-3x text-muted mb-3"></i>
                                <p class="text-muted mb-0">Belum ada riwayat ujian.</p>
                            </div>
                        </div>
                    </div>

                    <div v-for="(history, index) in historyList" :key="index" class="col-md-6 col-lg-4">
                        <div class="card exam-card h-100 border-0 shadow-sm">
                            <div class="card-body d-flex flex-column">
                                <div class="d-flex justify-content-between align-items-start mb-3">
                                    <h5 class="fw-bold mb-0">{{ history.exam.title }}</h5>
                                    <span class="badge bg-success">Percobaan #{{ history.attempt }}</span>
                                </div>

                                <div class="mb-3">
                                    <span class="badge bg-primary mb-2 me-2">
                                        <i class="fas fa-book me-1"></i> {{ history.exam.lesson?.name || 'Umum' }}
                                    </span>
                                    <span v-if="history.is_kecermatan" class="badge bg-info text-white mb-2">
                                        <i class="fas fa-bolt me-1"></i> Kecermatan
                                    </span>
                                    <div class="text-muted small mt-1">
                                        <i class="fas fa-calendar-alt me-1"></i>
                                        {{ history.date ? new Date(history.date).toLocaleString('id-ID', { dateStyle: 'medium', timeStyle: 'short' }) : '-' }}
                                    </div>
                                </div>

                                <div class="alert alert-info py-3 mb-3 text-center mt-auto">
                                    <small class="d-block text-uppercase fw-bold mb-1">Skor / Nilai Akhir</small>
                                    <h3 class="fw-bold mb-0 text-primary">{{ history.score }}</h3>
                                </div>

                                <div class="text-center">
                                    <Link
                                        v-if="history.is_kecermatan"
                                        :href="`/student/kecermatan/result/${history.session_id}`"
                                        class="btn btn-sm btn-outline-info w-100"
                                    >
                                        <i class="fas fa-chart-line me-1"></i> Detail Hasil Kecermatan
                                    </Link>
                                    <Link
                                        v-else
                                        :href="`/student/exam-result/${history.grade_id}`"
                                        class="btn btn-sm btn-outline-primary w-100"
                                    >
                                        <i class="fas fa-eye me-1"></i> Detail Hasil Ujian
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
import { Head, Link } from '@inertiajs/vue3'
import StudentLayout from '@/Layouts/Student.vue'
import { ref, computed } from 'vue'

const props = defineProps({
    available_exams: Array
})

const activeTab = ref('available')
const selectedLesson = ref('Semua')

// Compute all unique lessons for the filter dropdown
const availableLessons = computed(() => {
    const lessons = new Set()
    props.available_exams.forEach(item => {
        lessons.add(item.exam.lesson?.name || 'Umum')
    })
    return ['Semua', ...Array.from(lessons)]
})

// Filter available exams strictly for "Ujian Tersedia" section, and filter by selectedLesson
const availableExamsList = computed(() => {
    return props.available_exams.filter(item => {
        if (selectedLesson.value === 'Semua') return true
        const lessonName = item.exam.lesson?.name || 'Umum'
        return lessonName === selectedLesson.value
    })
})

// Flatten history from all exams, and filter by selectedLesson
const historyList = computed(() => {
    let history = []
    
    props.available_exams.forEach(item => {
        // filter early by lesson
        const lessonName = item.exam.lesson?.name || 'Umum'
        if (selectedLesson.value !== 'Semua' && lessonName !== selectedLesson.value) return;

        if (item.is_kecermatan) {
            if (item.all_sessions && item.all_sessions.length > 0) {
                item.all_sessions.forEach((sess, idx) => {
                    history.push({
                        exam: item.exam,
                        is_kecermatan: true,
                        session_id: sess.id,
                        score: sess.total_score,
                        attempt: item.all_sessions.length - idx,
                        date: sess.created_at
                    })
                })
            }
        } else {
            if (item.all_grades && item.all_grades.length > 0) {
                item.all_grades.forEach((grade, idx) => {
                    history.push({
                        exam: item.exam,
                        is_kecermatan: false,
                        grade_id: grade.id,
                        score: grade.grade,
                        attempt: grade.attempt_number || (item.all_grades.length - idx),
                        date: grade.created_at
                    })
                })
            }
        }
    })
    
    return history.sort((a, b) => new Date(b.date) - new Date(a.date))
})

const getStatusLabel = (status) => {
    const labels = {
        'available': 'Tersedia',
        'in_progress': 'Sedang Dikerjakan',
        'completed': 'Selesai',
        'unavailable': 'Tidak Tersedia'
    }
    return labels[status] || status
}

const getStatusBadgeClass = (status) => {
    const classes = {
        'available': 'bg-success',
        'in_progress': 'bg-warning text-dark',
        'completed': 'bg-info text-white',
        'unavailable': 'bg-secondary'
    }
    return classes[status] || 'bg-secondary'
}

const getStartLink = (item) => {
    if (item.is_kecermatan) {
        return `/student/kecermatan/${item.exam.id}/select-type`
    }
    return `/student/exam-confirmation/${item.exam.id}`
}

const getResumeLink = (item) => {
    if (item.is_kecermatan) {
        return `/student/kecermatan/exam/${item.session_id}/${item.status_info?.current_column || 1}/${item.status_info?.current_question || 1}`
    }
    const gradeId = item.in_progress_grade?.id || item.latest_grade?.id || 1
    return `/student/exam/${item.exam.id}/${gradeId}/1`
}
</script>

<style scoped>
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

/* Mobile optimizations */
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
    
    .exam-card .badge {
        font-size: 0.75rem;
    }
    
    .exam-card .btn {
        font-size: 0.875rem;
        padding: 0.5rem;
    }
}

@media (max-width: 575.98px) {
    .container {
        padding-left: 0.5rem;
        padding-right: 0.5rem;
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
}

/* Desktop layout - side by side */
@media (min-width: 768px) {
    .tabs-container {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 1rem;
    }
    
    .tabs-container > div:first-child {
        flex-shrink: 0;
    }
    
    .tabs-container > div:last-child {
        flex-shrink: 0;
        min-width: 200px;
    }
}

@media (max-width: 575.98px) {
    .container {
        padding-left: 0.5rem;
        padding-right: 0.5rem;
    }
    
    h3.fw-bold {
        font-size: 1.25rem;
    }
    
    .tabs-container {
        padding: 0.75rem !important;
    }
}

/* Desktop layout - side by side */
@media (min-width: 768px) {
    .tabs-container {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 1rem;
    }
    
    .tabs-container > div:first-child {
        flex-shrink: 0;
    }
    
    .tabs-container > div:last-child {
        flex-shrink: 0;
        min-width: 200px;
    }
}

.exam-card {
    transition: transform 0.2s ease, box-shadow 0.2s ease;
}

.exam-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 8px 16px rgba(0,0,0,0.1) !important;
}

.alert-info {
    background-color: #f8fafc;
    border: 1px solid #e2e8f0;
    color: #475569;
}

/* Custom form select styling for filter */
.form-select.border-primary {
    border-color: #0d6efd !important;
    background-color: #f8f9fa;
    cursor: pointer;
}
.form-select.border-primary:focus {
    box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25) !important;
}
</style>
