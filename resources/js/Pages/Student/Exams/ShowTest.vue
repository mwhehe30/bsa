<template>
    <Head>
        <title>Test Halaman Ujian - Buweuk Sipit Academy</title>
    </Head>

    <div class="page-wrap">
        <!-- Top Bar Info Ujian -->
        <div class="exam-topbar mb-4">
            <div class="d-flex align-items-center gap-3">
                <div class="topbar-mark">
                    <i class="fa fa-clipboard-check"></i>
                </div>
                <div class="lh-sm">
                    <div class="fw-bold exam-title-topbar">Tes Kecerdasan</div>
                    <small class="text-muted">Ujian Pilihan Ganda • 15 soal</small>
                </div>
            </div>
            <div class="d-flex align-items-center gap-3">
                <div class="text-end lh-sm d-none d-sm-block">
                    <small class="text-muted d-block">Progress</small>
                    <strong>{{ answeredCount }} / {{ totalQuestions }}</strong>
                </div>
                <div class="timer-box" :class="timerClass">
                    <i class="fa fa-clock me-1"></i>{{ formattedTime }}
                </div>
            </div>
        </div>

        <div class="row g-4">
            <!-- Kartu Soal -->
            <div class="col-12 col-lg-8">
                <div class="flat-card">
                    <div class="flat-header d-flex justify-content-between align-items-center">
                        <h5 class="flat-title mb-0">
                            <i class="fa fa-question-circle me-2 text-indigo"></i>Soal No. {{ currentPage }}
                        </h5>
                        <span class="flat-badge badge-indigo">
                            {{ answeredCount }}/{{ totalQuestions }} terjawab
                        </span>
                    </div>

                    <div class="card-body p-4 p-md-5">
                        <div
                            class="question-text"
                            v-html="sanitizeHtml(currentQuestion.question)"
                        ></div>

                        <div class="options-list mt-4">
                            <button
                                v-for="(opt, index) in currentQuestion.options"
                                :key="index"
                                type="button"
                                class="option-btn"
                                :class="{
                                    'option-btn-selected':
                                        answers[currentQuestion.id] === index + 1,
                                }"
                                @click="selectOption(index + 1)"
                            >
                                <span class="option-letter">{{ letters[index] }}</span>
                                <span
                                    class="option-text"
                                    v-html="sanitizeHtml(opt)"
                                ></span>
                            </button>
                        </div>
                    </div>

                    <div class="flat-footer">
                        <button
                            type="button"
                            class="btn-flat btn-flat-secondary"
                            :disabled="currentPage <= 1"
                            @click="goTo(currentPage - 1)"
                        >
                            <i class="fa fa-chevron-left me-1"></i> Prev
                        </button>
                        <span class="small text-muted d-none d-md-inline">
                            <i class="fa fa-keyboard me-1"></i>Gunakan angka 1-5 untuk jawab cepat
                        </span>
                        <button
                            type="button"
                            class="btn-flat btn-flat-primary"
                            :disabled="currentPage >= totalQuestions"
                            @click="goTo(currentPage + 1)"
                        >
                            Next <i class="fa fa-chevron-right ms-1"></i>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Sidebar Navigasi (Desktop) -->
            <div class="col-12 col-lg-4 d-none d-lg-block">
                <div class="flat-card">
                    <div class="flat-header d-flex justify-content-between align-items-center">
                        <h5 class="flat-title mb-0">
                            <i class="fa fa-th-large me-2 text-indigo"></i>Navigasi Soal
                        </h5>
                        <span class="flat-badge badge-green">{{ answeredCount }} terjawab</span>
                    </div>
                    <div class="card-body p-3">
                        <div class="row g-2">
                            <div v-for="q in questions" :key="q.id" class="col-3">
                                <button
                                    type="button"
                                    class="grid-btn w-100"
                                    :class="gridClass(q.id)"
                                    @click="goTo(q.id)"
                                >
                                    {{ q.id }}
                                </button>
                            </div>
                        </div>
                    </div>
                    <div class="card-body pt-0">
                        <button
                            type="button"
                            class="btn-flat btn-flat-danger w-100 justify-content-center py-2"
                            :disabled="answeredCount < totalQuestions"
                            @click="submitExam"
                        >
                            <i class="fa fa-paper-plane me-2"></i> Submit Jawaban
                        </button>
                        <small
                            v-if="answeredCount < totalQuestions"
                            class="text-danger d-block text-center mt-2 small"
                        >
                            <i class="fa fa-exclamation-circle me-1"></i>
                            Jawab semua soal dahulu ({{ totalQuestions - answeredCount }} tersisa)
                        </small>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tombol Soal (Mobile) -->
        <div class="d-lg-none mt-3">
            <button
                type="button"
                class="btn-flat btn-flat-secondary w-100 justify-content-center py-2"
                @click="showNavigation = true"
            >
                <i class="fa fa-th-large me-2"></i> Soal: {{ currentPage }}/{{ totalQuestions }}
            </button>
            <button
                type="button"
                class="btn-flat btn-flat-danger w-100 justify-content-center py-2 mt-2"
                :disabled="answeredCount < totalQuestions"
                @click="submitExam"
            >
                <i class="fa fa-paper-plane me-2"></i> Submit Jawaban
            </button>
        </div>

        <!-- Bottom Sheet Navigasi (Mobile) -->
        <div
            v-if="showNavigation"
            class="bottom-sheet-overlay"
            @click.self="showNavigation = false"
        >
            <div class="bottom-sheet">
                <div class="bottom-sheet-handle-wrap">
                    <div class="bottom-sheet-handle"></div>
                </div>
                <div class="flat-header d-flex justify-content-between align-items-center py-3 px-4">
                    <h6 class="flat-title mb-0">
                        <i class="fa fa-th-large me-2 text-indigo"></i>Navigasi Soal
                    </h6>
                    <span class="flat-badge badge-green">{{ answeredCount }} terjawab</span>
                </div>
                <div class="px-4 pt-3 pb-2 border-bottom flex-shrink-0">
                    <div class="sheet-search-wrap">
                        <i class="fa fa-search search-icon"></i>
                        <input
                            v-model="searchQuery"
                            type="text"
                            class="sheet-search-input"
                            placeholder="Cari nomor soal..."
                        />
                    </div>
                </div>
                <div class="card-body p-4 scrollable-grid">
                    <div class="row g-2">
                        <div v-for="q in filteredQuestions" :key="q.id" class="col-3">
                            <button
                                type="button"
                                class="grid-btn w-100"
                                :class="gridClass(q.id)"
                                @click="goTo(q.id)"
                            >
                                {{ q.id }}
                            </button>
                        </div>
                    </div>
                </div>
                <div class="flat-header d-flex justify-content-between align-items-center py-3 px-4">
                    <span class="small text-muted">Progres Pengerjaan</span>
                    <span class="flat-badge badge-indigo">{{ answeredCount }} / {{ totalQuestions }}</span>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { Head, Link } from '@inertiajs/vue3';
import LayoutStudent from '@/Layouts/Student.vue';
import { computed, onMounted, onUnmounted, ref } from 'vue';
import Swal from 'sweetalert2';
import { sanitizeHtml } from '@/utils/sanitize';

defineOptions({ layout: LayoutStudent });

const letters = ['A', 'B', 'C', 'D', 'E'];
const TOTAL_DURATION = 600; // 10 menit (demo)

const questions = [
    { id: 1, question: 'Berapakah hasil dari <strong>15 + 23</strong>?', options: ['35', '38', '40', '42', '45'] },
    { id: 2, question: 'Apa ibu kota negara <em>Indonesia</em>?', options: ['Bandung', 'Jakarta', 'Surabaya', 'Medan', 'Bali'] },
    { id: 3, question: '100 − 37 = ...', options: ['57', '61', '63', '67', '73'] },
    { id: 4, question: 'Ibu kota Provinsi Jawa Barat adalah...', options: ['Bandung', 'Bogor', 'Cirebon', 'Tasikmalaya', 'Garut'] },
    { id: 5, question: '3² + 4² = ...', options: ['12', '14', '25', '49', '7'] },
    { id: 6, question: 'Planet terdekat dari Matahari adalah...', options: ['Venus', 'Merkurius', 'Bumi', 'Mars', 'Jupiter'] },
    { id: 7, question: 'Pencipta lagu <strong>Indonesia Raya</strong> adalah...', options: ['C. Simanjuntak', 'W.R. Supratman', 'Ismail Marzuki', 'Ibu Sud', 'Kusbini'] },
    { id: 8, question: 'Lanjutkan deret: 2, 4, 8, 16, ...', options: ['20', '24', '30', '32', '36'] },
    { id: 9, question: 'Alat pernapasan pada ikan adalah...', options: ['Paru-paru', 'Insang', 'Trakea', 'Kulit', 'Sirip'] },
    { id: 10, question: '144 : 12 = ...', options: ['10', '11', '12', '13', '14'] },
    { id: 11, question: 'Semboyan negara Indonesia adalah...', options: ['Garuda di Dadaku', 'Merdeka atau Mati', 'Bersatu Kita Teguh', 'Bhinneka Tunggal Ika', 'Jayalah Indonesiaku'] },
    { id: 12, question: 'Warna bendera negara Indonesia adalah...', options: ['Merah Putih', 'Kuning Hijau', 'Biru Putih', 'Merah Biru', 'Hijau Putih'] },
    { id: 13, question: 'Bapak Proklamator Kemerdekaan Indonesia adalah...', options: ['Soekarno', 'Moh. Hatta', 'Soeharto', 'Ki Hajar Dewantara', 'Cut Nyak Dien'] },
    { id: 14, question: '25% dari 200 adalah...', options: ['40', '45', '50', '55', '60'] },
    { id: 15, question: 'Rumah adat <strong>Joglo</strong> berasal dari daerah...', options: ['Sumatera Barat', 'Jawa Tengah', 'Bali', 'Papua', 'Kalimantan'] },
];

const totalQuestions = questions.length;
const currentPage = ref(1);
const answers = ref({});
const timeLeft = ref(TOTAL_DURATION);
const showNavigation = ref(false);
const searchQuery = ref('');

const currentQuestion = computed(() => {
    return questions.find((q) => q.id === currentPage.value) || questions[0];
});

const answeredCount = computed(() => Object.keys(answers.value).length);

const filteredQuestions = computed(() => {
    if (!searchQuery.value) return questions;
    return questions.filter((q) =>
        String(q.id).includes(searchQuery.value.trim()),
    );
});

const formattedTime = computed(() => {
    const minutes = Math.floor(timeLeft.value / 60);
    const seconds = timeLeft.value % 60;
    return `${String(minutes).padStart(2, '0')}:${String(seconds).padStart(2, '0')}`;
});

const timerClass = computed(() => {
    if (timeLeft.value <= 10) return 'timer-danger';
    if (timeLeft.value <= 60) return 'timer-warning';
    return 'timer-safe';
});

const selectOption = (optionNumber) => {
    answers.value = {
        ...answers.value,
        [currentQuestion.id]: optionNumber,
    };
};

const goTo = (page) => {
    if (page < 1 || page > totalQuestions) return;
    currentPage.value = page;
    showNavigation.value = false;
};

const gridClass = (id) => {
    if (id === currentPage.value) return 'grid-btn grid-btn-active';
    if (answers.value[id]) return 'grid-btn grid-btn-answered';
    return 'grid-btn';
};

const submitExam = () => {
    if (answeredCount.value < totalQuestions) return;
    Swal.fire({
        title: 'Submit Jawaban?',
        text: 'Apakah Anda yakin ingin men-submit semua jawaban?',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#1A2332',
        cancelButtonColor: '#94a3b8',
        confirmButtonText: 'Ya, Submit!',
        cancelButtonText: 'Batal',
    }).then((result) => {
        if (result.isConfirmed) {
            Swal.fire({
                title: 'Berhasil!',
                text: `Semua ${totalQuestions} jawaban berhasil disimpan. (Demo)`,
                icon: 'success',
                confirmButtonColor: '#1A2332',
            });
        }
    });
};

const handleKeyPress = (e) => {
    const keyMap = { 1: 1, 2: 2, 3: 3, 4: 4, 5: 5 };
    if (keyMap[e.key]) {
        selectOption(keyMap[e.key]);
    } else if (e.key === 'ArrowRight') {
        goTo(currentPage.value + 1);
    } else if (e.key === 'ArrowLeft') {
        goTo(currentPage.value - 1);
    }
};

let timerInterval;

onMounted(() => {
    document.addEventListener('keydown', handleKeyPress);
    timerInterval = setInterval(() => {
        if (timeLeft.value > 0) timeLeft.value--;
    }, 1000);
});

onUnmounted(() => {
    document.removeEventListener('keydown', handleKeyPress);
    clearInterval(timerInterval);
});
</script>

<style scoped>
.page-wrap {
    font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
    padding: 24px 0 64px;
}

/* ── Top Bar ───────────────────────────────────── */
.exam-topbar {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 16px;
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 16px;
    padding: 14px 20px;
}

.topbar-mark {
    width: 44px;
    height: 44px;
    flex-shrink: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 12px;
    color: #1A2332;
    background: #eef2ff;
    font-size: 1.2rem;
}

.exam-title-topbar {
    font-size: 1rem;
    color: #1e293b;
}

.timer-box {
    min-width: 118px;
    text-align: center;
    padding: 8px 14px;
    border-radius: 10px;
    font-family: 'SFMono-Regular', Consolas, monospace;
    font-size: 1.25rem;
    font-weight: 700;
}

.timer-safe {
    color: #15803d;
    background: #f0fdf4;
}

.timer-warning {
    color: #d97706;
    background: #fffbeb;
}

.timer-danger {
    color: #dc2626;
    background: #fef2f2;
    animation: timer-pulse 0.7s ease-in-out infinite;
}

@keyframes timer-pulse {
    50% {
        opacity: 0.55;
    }
}

/* ── Flat card ─────────────────────────────────── */
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

.flat-footer {
    background: #f8fafc;
    border-top: 1px solid #e2e8f0;
    padding: 14px 24px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
}

.flat-title {
    font-size: 1rem;
    font-weight: 600;
    color: #1e293b;
}

.text-indigo {
    color: #1A2332;
}

/* ── Badges ────────────────────────────────────── */
.flat-badge {
    font-size: 0.75rem;
    font-weight: 600;
    padding: 3px 10px;
    border-radius: 20px;
    display: inline-block;
    white-space: nowrap;
}

.badge-indigo {
    background: #e0e7ff;
    color: #1A2332;
}

.badge-green {
    background: #dcfce7;
    color: #15803d;
}

/* ── Question ──────────────────────────────────── */
.question-text {
    font-size: 1.15rem;
    color: #1e293b;
    line-height: 1.6;
}

.question-text :deep(img),
.option-text :deep(img) {
    max-width: 100%;
    height: auto;
    border-radius: 8px;
}

.options-list {
    display: flex;
    flex-direction: column;
    gap: 10px;
}

.option-btn {
    display: flex;
    align-items: center;
    gap: 14px;
    width: 100%;
    text-align: left;
    padding: 12px 16px;
    border: 1px solid #cbd5e1;
    border-radius: 12px;
    background: #ffffff;
    cursor: pointer;
    transition: all 0.15s ease;
}

.option-btn:hover {
    border-color: #1A2332;
    background: #f8fafc;
    transform: translateX(2px);
}

.option-btn-selected {
    border-color: #1A2332;
    background: #eef2ff;
    box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.12);
}

.option-letter {
    width: 32px;
    height: 32px;
    flex-shrink: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 8px;
    font-weight: 700;
    font-size: 0.85rem;
    color: #1A2332;
    background: #eef2ff;
}

.option-btn-selected .option-letter {
    color: #ffffff;
    background: #1A2332;
}

.option-text {
    font-size: 1rem;
    color: #334155;
    line-height: 1.5;
}

/* ── Grid Navigasi ─────────────────────────────── */
.grid-btn {
    border-radius: 8px;
    font-weight: 600;
    border: 1px solid #cbd5e1;
    background: #ffffff;
    color: #64748b;
    padding: 8px 0;
    transition: all 0.15s ease;
}

.grid-btn:hover {
    background: #f1f5f9;
}

.grid-btn-answered {
    background-color: #1A2332;
    color: #ffffff;
    border-color: #1A2332;
}

.grid-btn-active {
    box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.35);
    border-color: #1A2332;
    font-weight: 800;
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

.btn-flat:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

.btn-flat-primary {
    background: #1A2332;
    color: #fff;
}

.btn-flat-primary:hover:not(:disabled) {
    background: #1A2332;
    color: #fff;
}

.btn-flat-secondary {
    background: #f1f5f9;
    color: #475569;
}

.btn-flat-secondary:hover:not(:disabled) {
    background: #e2e8f0;
    color: #1e293b;
}

.btn-flat-danger {
    background: #dc2626;
    color: #fff;
}

.btn-flat-danger:hover:not(:disabled) {
    background: #b91c1c;
    color: #fff;
}

/* ── Bottom Sheet (Mobile) ─────────────────────── */
.bottom-sheet-overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(15, 23, 42, 0.45);
    backdrop-filter: blur(2px);
    z-index: 9999;
    display: flex;
    align-items: flex-end;
    justify-content: center;
}

.bottom-sheet {
    width: 100%;
    max-width: 480px;
    background: #ffffff;
    border-top-left-radius: 20px;
    border-top-right-radius: 20px;
    display: flex;
    flex-direction: column;
    max-height: 80vh;
    animation: sheetSlideUp 0.25s cubic-bezier(0.16, 1, 0.3, 1);
}

.bottom-sheet-handle-wrap {
    display: flex;
    justify-content: center;
    padding: 12px 0;
}

.bottom-sheet-handle {
    width: 40px;
    height: 4px;
    border-radius: 2px;
    background: #cbd5e1;
}

.sheet-search-wrap {
    display: flex;
    align-items: center;
    background: #f1f5f9;
    border-radius: 8px;
    padding: 0 10px;
    border: 1px solid #e2e8f0;
}

.sheet-search-wrap:focus-within {
    border-color: #1A2332;
    background: #ffffff;
}

.search-icon {
    color: #94a3b8;
    margin-right: 8px;
    font-size: 0.85rem;
}

.sheet-search-input {
    flex: 1;
    border: none;
    background: transparent;
    padding: 9px 0;
    font-size: 0.85rem;
    color: #1e293b;
    outline: none;
    font-family: inherit;
}

.scrollable-grid {
    flex: 1;
    overflow-y: auto;
}

@keyframes sheetSlideUp {
    from {
        transform: translateY(100%);
    }
    to {
        transform: translateY(0);
    }
}

/* ── Responsive ────────────────────────────────── */
@media (max-width: 767.98px) {
    .exam-topbar {
        padding: 12px 14px;
    }

    .flat-header {
        padding: 12px 16px;
    }

    .flat-footer {
        padding: 12px 16px;
    }

    .card-body {
        padding: 1.25rem !important;
    }
}

@media (max-width: 575.98px) {
    .page-wrap {
        padding: 14px 0 48px;
    }

    .topbar-mark {
        width: 38px;
        height: 38px;
        font-size: 1rem;
    }

    .timer-box {
        min-width: 96px;
        font-size: 1.05rem;
        padding: 6px 10px;
    }

    .question-text {
        font-size: 1rem;
    }

    .option-btn {
        padding: 10px 12px;
        gap: 10px;
    }

    .option-letter {
        width: 28px;
        height: 28px;
        font-size: 0.78rem;
    }

    .option-text {
        font-size: 0.92rem;
    }

    .card-body {
        padding: 1rem !important;
    }
}
</style>
