<template>
    <Head>
        <title>Ujian : {{ exam_group?.exam?.title }} - Buweuk Sipit Academy</title>
    </Head>

    <div
        v-if="!isBlocked && !showFullscreenWarning"
        class="page-wrap"
        style="
            user-select: none;
            -webkit-user-select: none;
            -moz-user-select: none;
            -ms-user-select: none;
        "
    >
        <!-- Top Bar Info Ujian -->
        <div class="exam-topbar mb-4">
            <div class="d-flex align-items-center gap-3">
                <div class="topbar-mark">
                    <i class="fa fa-clipboard-check"></i>
                </div>
                <div class="lh-sm">
                    <div class="fw-bold exam-title-topbar">
                        {{ exam_group?.exam?.title }}
                    </div>
                    <small class="text-muted">
                        <template v-if="exam_group?.exam?.lesson?.name">
                            {{ exam_group.exam.lesson.name }} •
                        </template>
                        {{ total_questions }} soal
                    </small>
                </div>
            </div>
            <div class="d-flex align-items-center gap-3">
                <div class="text-end lh-sm d-none d-sm-block">
                    <small class="text-muted d-block">Progress</small>
                    <strong>{{ question_answered }} / {{ total_questions }}</strong>
                </div>
                <VueCountdown
                    :time="duration"
                    @progress="handleChangeDuration"
                    @end="autoSubmitExam"
                    v-slot="{ hours, minutes, seconds, totalSeconds }"
                >
                    <div :class="timerClass(totalSeconds)">
                        <i class="fa fa-clock me-1"></i>{{ formatTimer(hours, minutes, seconds) }}
                    </div>
                </VueCountdown>
            </div>
        </div>

        <div class="row g-4">
            <!-- Kartu Soal -->
            <div class="col-12 col-lg-8">
                <div class="flat-card">
                    <div class="flat-header d-flex justify-content-between align-items-center">
                        <h5 class="flat-title mb-0">
                            <i class="fa fa-question-circle me-2 text-indigo"></i>Soal No.
                            <span class="text-indigo">{{ currentPage }}</span>
                        </h5>
                        <span class="flat-badge badge-indigo">
                            {{ question_answered }}/{{ total_questions }} terjawab
                        </span>
                    </div>

                    <div class="card-body p-4 p-md-5">
                        <div v-if="activeQuestion !== null">
                            <div
                                class="question-text"
                                v-html="sanitize(activeQuestion.question.question)"
                            ></div>

                            <div class="options-list mt-4">
                                <button
                                    v-for="(answer, index) in activeAnswerOrder"
                                    :key="index"
                                    type="button"
                                    class="option-btn"
                                    :class="{
                                        'option-btn-selected':
                                            answer == activeQuestion.answer,
                                    }"
                                    @click="
                                        answer != activeQuestion.answer &&
                                            submitAnswer(
                                                exam?.id,
                                                activeQuestion.question.id,
                                                answer,
                                            )
                                    "
                                >
                                    <span class="option-letter">{{ options[index] }}</span>
                                    <span
                                        class="option-text"
                                        v-html="
                                            sanitize(
                                                activeQuestion.question[
                                                    'option_' + answer
                                                ],
                                            )
                                        "
                                    ></span>
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="flat-footer">
                        <button
                            v-if="currentPage > 1"
                            type="button"
                            class="btn-flat btn-flat-secondary"
                            @click="goToPage(currentPage - 1)"
                        >
                            <i class="fa fa-chevron-left me-1"></i> Prev
                        </button>
                        <div v-else></div>

                        <span class="small text-muted d-none d-md-inline">
                            <i class="fa fa-keyboard me-1"></i>Gunakan angka 1-5 untuk jawab cepat
                        </span>

                        <button
                            v-if="currentPage < total_questions"
                            type="button"
                            class="btn-flat btn-flat-primary"
                            @click="goToPage(currentPage + 1)"
                        >
                            Next <i class="fa fa-chevron-right ms-1"></i>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Sidebar Navigasi (Desktop) -->
            <div class="col-lg-4 d-none d-lg-block">
                <div class="flat-card">
                    <div class="flat-header d-flex justify-content-between align-items-center">
                        <h5 class="flat-title mb-0">
                            <i class="fa fa-th-large me-2 text-indigo"></i>Navigasi Soal
                        </h5>
                        <span class="flat-badge badge-green">{{ question_answered }} terjawab</span>
                    </div>
                    <div class="card-body p-3">
                        <div class="row g-2">
                            <div
                                v-for="(question, index) in all_questions"
                                :key="index"
                                class="col-3"
                            >
                                <button
                                    type="button"
                                    class="grid-btn w-100"
                                    :class="gridClass(question)"
                                    @click="goToPage(question.question_order)"
                                >
                                    {{ question.question_order }}
                                </button>
                            </div>
                        </div>
                    </div>
                    <div class="card-body pt-0">
                        <button
                            type="button"
                            class="btn-flat btn-flat-danger w-100 justify-content-center py-2"
                            :disabled="question_answered < total_questions"
                            @click="endExam"
                        >
                            <i class="fa fa-paper-plane me-2"></i> Submit Jawaban
                        </button>
                        <small
                            v-if="question_answered < total_questions"
                            class="text-danger d-block text-center mt-2 small"
                        >
                            <i class="fa fa-exclamation-circle me-1"></i>
                            Jawab semua soal dahulu ({{ total_questions - question_answered }} tersisa)
                        </small>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tombol Navigasi & Submit (Mobile) -->
        <div class="d-lg-none mt-3">
            <button
                type="button"
                class="btn-flat btn-flat-secondary w-100 justify-content-center py-2 mb-2"
                @click="showNavigationModal = true"
            >
                <i class="fa fa-th-large me-1"></i> Soal:
                {{ currentPage }}/{{ total_questions }}
            </button>
            <button
                type="button"
                class="btn-flat btn-flat-danger w-100 justify-content-center py-2"
                :disabled="question_answered < total_questions"
                @click="endExam"
            >
                <i class="fa fa-paper-plane me-2"></i> Submit Jawaban
            </button>
            <small
                v-if="question_answered < total_questions"
                class="text-danger d-block text-center mt-2 small"
            >
                <i class="fa fa-exclamation-circle me-1"></i>
                Jawab semua soal dahulu ({{ total_questions - question_answered }} tersisa)
            </small>
        </div>
    </div>

    <!-- Fullscreen Warning Overlay -->
    <div
        v-if="showFullscreenWarning && !isBlocked"
        class="fullscreen-warning d-flex align-items-center justify-content-center"
    >
        <div class="overlay-content p-5 text-center text-white">
            <i class="fa fa-exclamation-triangle fa-4x text-warning mb-3"></i>
            <h2 class="mb-2">Peringatan!</h2>
            <p class="lead mb-2">
                Anda harus mengaktifkan mode fullscreen untuk melanjutkan ujian.
            </p>
            <p class="text-muted small mb-4">
                (Keluar fullscreen tidak dihitung sebagai pelanggaran)
            </p>

            <!-- Violation List -->
            <div v-if="violations.length > 0" class="violation-list mb-4">
                <h6 class="text-danger mb-3">
                    <i class="fa fa-list-ul me-1"></i> Riwayat Pelanggaran Tab Switch ({{
                        violations.length
                    }} / 3)
                </h6>
                <div
                    v-for="(v, i) in violations"
                    :key="i"
                    class="violation-item d-flex align-items-center justify-content-between"
                >
                    <div class="d-flex align-items-center gap-2">
                        <span class="violation-number">{{ i + 1 }}</span>
                        <span class="violation-badge badge-tab">
                            <i class="fa fa-share-square"></i>
                            Pindah Tab/Aplikasi
                        </span>
                    </div>
                    <span class="violation-time">{{
                        formatViolationTime(v.violation_time)
                    }}</span>
                </div>
            </div>

            <button
                @click="enterFullscreen"
                class="btn btn-lg btn-warning mt-1"
            >
                <i class="fa fa-expand"></i> Aktifkan Fullscreen
            </button>
        </div>
    </div>

    <!-- Blocked Overlay -->
    <div v-if="isBlocked" class="blocked-overlay">
        <div class="overlay-content">
            <i class="fa fa-lock fa-4x text-danger mb-3"></i>
            <h2 class="mb-2">Anda Diblokir!</h2>
            <p class="lead mb-1">
                Anda pindah tab/aplikasi selama ujian berlangsung.
            </p>

            <!-- Pesan berbeda berdasarkan jumlah pelanggaran -->
            <div v-if="violationCount >= 3">
                <p
                    class="text-danger mb-2"
                    style="font-size: 1.2em; font-weight: bold"
                >
                    <i class="fa fa-exclamation-triangle me-1"></i>
                    Pelanggaran ke-{{ violationCount }}! Ujian Anda akan
                    otomatis disubmit.
                </p>
            </div>
            <div v-else>
                <p class="text-warning mb-2" style="font-size: 1.1em">
                    <i class="fa fa-exclamation-circle me-1"></i>
                    Pelanggaran ke-{{ violationCount }} dari 3.
                </p>
                <p class="text-muted mb-4">
                    Silakan hubungi pengawas untuk membuka blokir dan
                    melanjutkan ujian.
                </p>
            </div>

            <!-- Violation List -->
            <div v-if="violations.length > 0" class="violation-list mt-4">
                <h6 class="text-danger mb-3">
                    <i class="fa fa-list-ul me-1"></i> Riwayat Pelanggaran ({{
                        violations.length
                    }})
                </h6>
                <div
                    v-for="(v, i) in violations"
                    :key="i"
                    class="violation-item"
                >
                    <span class="violation-number">{{ i + 1 }}</span>
                    <span class="violation-badge">
                        <i class="fa fa-share-square"></i> Pindah Tab/Aplikasi
                    </span>
                    <span class="violation-time">{{
                        formatViolationTime(v.violation_time)
                    }}</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Mobile Navigation Bottom Sheet -->
    <div
        v-if="showNavigationModal"
        class="bottom-sheet-overlay"
        @click.self="showNavigationModal = false"
    >
        <div
            class="bottom-sheet"
            :style="sheetStyle"
            @touchstart="onTouchStart"
            @touchmove="onTouchMove"
            @touchend="onTouchEnd"
        >
            <!-- Sheet Drag Handle -->
            <div class="bottom-sheet-handle-wrap">
                <div class="bottom-sheet-handle"></div>
            </div>
            <div
                class="flat-header d-flex justify-content-between align-items-center py-3 px-4"
            >
                <h6 class="flat-title mb-0">
                    <i class="fa fa-th-large me-2 text-indigo"></i>Navigasi Soal
                </h6>
            </div>

            <!-- Search Input -->
            <div class="px-4 pt-3 pb-2 border-bottom flex-shrink-0">
                <div class="sheet-search-wrap">
                    <i class="fa fa-search search-icon"></i>
                    <input
                        type="text"
                        v-model="searchQuery"
                        placeholder="Cari nomor soal..."
                        class="sheet-search-input"
                    />
                    <button
                        v-if="searchQuery"
                        @click="searchQuery = ''"
                        class="search-clear-btn"
                    >
                        <i class="fa fa-times-circle"></i>
                    </button>
                </div>
            </div>

            <div class="card-body p-4 scrollable-grid">
                <div class="row g-2">
                    <div
                        class="col-3"
                        v-for="(question, index) in filteredQuestions"
                        :key="index"
                    >
                        <button
                            type="button"
                            class="grid-btn w-100"
                            :class="gridClass(question)"
                            @click="
                                showNavigationModal = false;
                                goToPage(question.question_order);
                            "
                        >
                            {{ question.question_order }}
                        </button>
                    </div>
                </div>
            </div>
            <div
                class="flat-header d-flex justify-content-between align-items-center py-3 px-4"
            >
                <span class="small text-muted">Progres Pengerjaan</span>
                <span class="flat-badge badge-indigo">{{ question_answered }} / {{ total_questions }}</span>
            </div>
        </div>
    </div>
</template>

<script>
import LayoutStudent from "../../../Layouts/Student.vue";
import { Head, router } from "@inertiajs/vue3";
import { ref, onMounted, onBeforeUnmount, computed, watch } from "vue";
import VueCountdown from "@chenfengyuan/vue-countdown";
import Swal from "sweetalert2";
import { sanitizeHtml } from "../../../utils/sanitize";
import {
    csrfHeaders,
    refreshCsrfToken,
    fetchJsonWithCsrf,
} from "../../../utils/csrf";

export default {
    layout: LayoutStudent,
    components: { Head, VueCountdown },
    props: {
        id: Number,
        page: Number,
        exam: Object,
        grade: Object,
        exam_group: Object,
        all_questions: Array,
        question_answered: Number,
        total_questions: Number,
        question_active: Object,
        answer_order: Array,
        remaining_seconds: Number, // Server-calculated remaining time
        server_time: Number,
        student_id: Number,
    },
    setup(props) {
        const options = ["A", "B", "C", "D", "E"];
        const isBlocked = ref(false);
        const showFullscreenWarning = ref(false);
        const showNavigationModal = ref(false);
        const searchQuery = ref("");

        const examGroupId = props.exam_group?.id || 0;
        const examId = props.exam?.id || 0;
        const studentId = props.student_id || 0;

        // Sertakan attempt_number agar jawaban attempt sebelumnya tidak
        // bocor/ter-load ke attempt berikutnya di localStorage.
        const storageKey = computed(
            () =>
                `exam_answers_${examGroupId}_${examId}_${studentId}_a${props.grade?.attempt_number || 0}`,
        );
        const localAnswers = ref({});

        const computedAllQuestions = computed(() => {
            return props.all_questions.map((q) => {
                if (localAnswers.value[q.question.id]) {
                    return { ...q, answer: localAnswers.value[q.question.id] };
                }
                return q;
            });
        });

        // Pindah soal tanpa delay: halaman aktif disimpan lokal dan URL diperbarui
        // via history.replaceState (tanpa request server). Semua soal sudah dimuat
        // di all_questions, jadi navigasi Prev/Next/grid cukup mengubah index.
        const currentPage = ref(props.page);

        const activeQuestion = computed(() => {
            const found = computedAllQuestions.value.find(
                (q) => Number(q.question_order) === Number(currentPage.value),
            );
            return found || props.question_active;
        });

        const activeAnswerOrder = computed(() => {
            const raw =
                activeQuestion.value?.answer_order ?? props.answer_order;
            if (Array.isArray(raw)) return raw;
            if (typeof raw === "string" && raw.trim() !== "") {
                return raw.split(",");
            }
            return props.answer_order || [];
        });

        const goToPage = (pageNum) => {
            const target = Math.max(
                1,
                Math.min(Number(pageNum) || 1, props.total_questions),
            );
            if (target === currentPage.value) return;
            currentPage.value = target;

            // Perbarui URL agar refresh/F5 tetap berada di soal yang sama.
            try {
                window.history.replaceState(
                    window.history.state,
                    "",
                    `/student/exam/${props.exam?.id}/${props.id}/${target}`,
                );
            } catch (e) {
                console.warn("Gagal memperbarui URL soal:", e);
            }
        };

        const computedQuestionAnswered = computed(() => {
            return computedAllQuestions.value.filter((q) => q.answer != 0)
                .length;
        });

        // Filter questions based on search input
        const filteredQuestions = computed(() => {
            if (!searchQuery.value) return computedAllQuestions.value;
            return computedAllQuestions.value.filter((q) =>
                q.question_order.toString().includes(searchQuery.value),
            );
        });

        // Touch Drag State for Bottom Sheet
        const startY = ref(0);
        const currentY = ref(0);
        const isDragging = ref(false);

        const sheetStyle = computed(() => {
            if (!isDragging.value)
                return {
                    transform: "translateY(0)",
                    transition: "transform 0.3s cubic-bezier(0.16, 1, 0.3, 1)",
                };
            const diff = currentY.value - startY.value;
            const translateY = diff > 0 ? diff : 0;
            return {
                transform: `translateY(${translateY}px)`,
                transition: "none",
            };
        });

        const onTouchStart = (e) => {
            startY.value = e.touches[0].clientY;
            currentY.value = startY.value;
            isDragging.value = true;
        };

        const onTouchMove = (e) => {
            currentY.value = e.touches[0].clientY;
        };

        const onTouchEnd = () => {
            isDragging.value = false;
            const diff = currentY.value - startY.value;
            // Jika di-drag ke bawah lebih dari 80px, tutup sheet
            if (diff > 80) {
                showNavigationModal.value = false;
            }
            startY.value = 0;
            currentY.value = 0;
        };

        const violationCount = ref(0);
        const violations = ref([]);
        const gracePeriod = ref(false);
        let checkStatusInterval = null;
        let fullscreenCheckInterval = null;

        // FIX BUG #2: Initialize from server-calculated remaining_seconds (in milliseconds)
        const duration = ref((props.remaining_seconds || 0) * 1000);

        const isPersonality = computed(() => {
            const name = props.exam_group?.exam?.lesson?.name;
            if (!name || typeof name !== "string") return false;
            const normalized = name.toLowerCase().trim();
            return (
                normalized === "kepribadian" ||
                normalized.startsWith("kepribadian ")
            );
        });

        // Fullscreen handling
        const enterFullscreen = () => {
            const elem = document.documentElement;
            if (elem.requestFullscreen) {
                elem.requestFullscreen();
            } else if (elem.webkitRequestFullscreen) {
                elem.webkitRequestFullscreen();
            } else if (elem.msRequestFullscreen) {
                elem.msRequestFullscreen();
            }
            showFullscreenWarning.value = false;
        };

        const checkFullscreen = () => {
            if (gracePeriod.value) return;
            const isFull =
                document.fullscreenElement ||
                document.webkitFullscreenElement ||
                document.msFullscreenElement;
            if (!isFull) {
                showFullscreenWarning.value = true;
            }
        };

        // Security / Anti-cheat
        const logViolation = async (type) => {
            if (gracePeriod.value || isBlocked.value) return;

            try {
                const buildRequest = () => ({
                    method: "POST",
                    headers: csrfHeaders(),
                    body: JSON.stringify({
                        exam_group_id: examGroupId,
                        exam_id: examId,
                        violation_type: type,
                    }),
                });

                let response = await fetch(
                    "/student/exam-security/log-violation",
                    buildRequest(),
                );

                // CSRF token basi: ambil token baru dari server lalu ulangi
                // sekali. Respons refresh memperbarui cookie XSRF-TOKEN, jadi
                // buildRequest() otomatis memakai token baru.
                if (response.status === 419) {
                    const freshToken = await refreshCsrfToken(
                        "/student/csrf-token",
                        true,
                    );
                    if (freshToken) {
                        response = await fetch(
                            "/student/exam-security/log-violation",
                            buildRequest(),
                        );
                    }
                }

                const data = await response.json();
                if (data.is_blocked) {
                    isBlocked.value = true;
                    // Hanya auto-submit jika sudah 3x pelanggaran
                    if (data.should_auto_submit) {
                        autoSubmitExam();
                    }
                }
                violationCount.value =
                    data.violation_count ?? violationCount.value;
                if (data.violations) {
                    violations.value = data.violations;
                }
            } catch (e) {
                console.error("Error logging violation:", e);
            }
        };

        const setBlockedStatus = (status, count, violationList) => {
            const wasBlocked = isBlocked.value;

            if (isBlocked.value === true && status === false) {
                isBlocked.value = false;
                gracePeriod.value = true;

                // Beri waktu 2 detik bagi siswa untuk kembali ke tab dan fullscreen
                setTimeout(() => {
                    gracePeriod.value = false;
                    checkFullscreen();
                }, 2000);
            } else {
                isBlocked.value = status;

                // Auto-submit ujian HANYA jika baru saja diblokir DAN sudah 3x pelanggaran
                if (!wasBlocked && status === true && count >= 3) {
                    autoSubmitExam();
                }
            }
            violationCount.value = count;
            if (violationList) {
                violations.value = violationList;
            }
        };

        const checkStatus = async () => {
            try {
                const response = await fetch(
                    `/student/exam-security/check-status?exam_group_id=${examGroupId}`,
                );
                const data = await response.json();

                // Sync timer from server if unblocked (prevent time manipulation)
                if (isBlocked.value === true && data.is_blocked === false && data.remaining_seconds) {
                    duration.value = data.remaining_seconds * 1000; // Convert to milliseconds
                }

                setBlockedStatus(
                    data.is_blocked,
                    data.violation_count,
                    data.violations,
                );
            } catch (e) {
                console.error("Error checking status:", e);
            }
        };

        // Event listeners
        let blurTimer = null;

        const handleVisibilityChange = () => {
            if (gracePeriod.value || isBlocked.value) return;

            if (document.hidden) {
                // Wait 2 seconds before logging violation
                blurTimer = setTimeout(() => {
                    if (document.hidden) {
                        logViolation("tab_switch");
                    }
                }, 2000);
            } else {
                // Clear timer if visibility returns within 2 seconds
                if (blurTimer) {
                    clearTimeout(blurTimer);
                    blurTimer = null;
                }
            }
        };

        const handleBlur = () => {
            if (gracePeriod.value || isBlocked.value) return;

            // Only log violation if document is actually hidden (real tab switch)
            if (document.hidden) {
                // Wait 2 seconds before logging violation
                blurTimer = setTimeout(() => {
                    if (document.hidden) {
                        logViolation("tab_switch");
                    }
                }, 2000);
            }
        };

        // Track if user was in fullscreen when losing focus
        let wasInFullscreen = false;

        const handleWindowBlur = () => {
            if (gracePeriod.value || isBlocked.value) return;

            // Check if in fullscreen when blur happens
            wasInFullscreen = !!(
                document.fullscreenElement ||
                document.webkitFullscreenElement ||
                document.msFullscreenElement
            );

            // If in fullscreen, wait 2 seconds before logging violation
            if (wasInFullscreen) {
                blurTimer = setTimeout(() => {
                    // Double check still blurred after 2 seconds
                    if (wasInFullscreen && !document.hasFocus()) {
                        logViolation("tab_switch");
                    }
                }, 2000);
            }
        };

        const handleWindowFocus = () => {
            // Clear timer if focus returns within 2 seconds
            if (blurTimer) {
                clearTimeout(blurTimer);
                blurTimer = null;
            }
            // Reset flag when window regains focus
            wasInFullscreen = false;
        };

        const handleContextMenu = (e) => {
            e.preventDefault();
            return false;
        };

        const handleKeyDown = (e) => {
            if (
                e.key === "F11" ||
                e.key === "F12" ||
                (e.ctrlKey &&
                    (e.key === "r" ||
                        e.key === "R" ||
                        e.key === "t" ||
                        e.key === "T")) ||
                (e.altKey && (e.key === "Tab" || e.key === "F4"))
            ) {
                e.preventDefault();
                return false;
            }
        };

        // Shortcut angka 1-5 untuk memilih opsi secara cepat
        const handleAnswerKeyPress = (e) => {
            // Jangan ganggu saat mengetik di input (mis. pencarian nomor soal)
            if (e.target.matches("input, textarea, select")) return;
            if (e.key >= "1" && e.key <= "5") {
                const idx = parseInt(e.key, 10) - 1;
                const answer = activeAnswerOrder.value?.[idx];
                if (
                    answer &&
                    activeQuestion.value &&
                    answer != activeQuestion.value.answer
                ) {
                    submitAnswer(
                        props.exam?.id,
                        activeQuestion.value.question.id,
                        answer,
                    );
                }
            }
        };

        onMounted(() => {
            enterFullscreen();

            // Grace period saat page load
            gracePeriod.value = true;

            const saved = localStorage.getItem(storageKey.value);
            if (saved) {
                localAnswers.value = JSON.parse(saved);
            }
            // Jawaban yang tersimpan di localStorage otomatis tampil melalui
            // computedAllQuestions (activeQuestion membaca dari sana).

            // Safe listeners (non-violation) - add immediately
            document.addEventListener("contextmenu", handleContextMenu);
            document.addEventListener("keydown", handleKeyDown);
            document.addEventListener("keydown", handleAnswerKeyPress);
            document.addEventListener("fullscreenchange", checkFullscreen);
            document.addEventListener("webkitfullscreenchange", checkFullscreen);

            // Fallback polling if Echo is not working
            checkStatusInterval = setInterval(checkStatus, 3000);

            // Kirim jawaban antrean secara berkala (batch).
            answerFlushInterval = setInterval(() => void flushAnswers(), 1500);

            // Usaha terakhir: kirim sisa jawaban saat halaman ditutup.
            window.addEventListener("pagehide", flushOnPageHide);

            // Listen for real-time block status changes via WebSocket
            if (window.Echo) {
                window.Echo.channel(`student.${studentId}`).listen(
                    "StudentBlockStatusChanged",
                    (e) => {
                        // Key payload mengikuti nama properti PHP event
                        // (isBlocked, violationCount), bukan snake_case.
                        setBlockedStatus(e.isBlocked, e.violationCount);
                    },
                );
            }

            // Add violation listeners AFTER 3 seconds grace period
            setTimeout(() => {
                gracePeriod.value = false;
                checkFullscreen();
                checkStatus();

                // Now safe to add violation detection
                document.addEventListener("visibilitychange", handleVisibilityChange);
                window.addEventListener("blur", handleBlur);
                window.addEventListener("blur", handleWindowBlur, true); // Capture phase
                window.addEventListener("focus", handleWindowFocus, true); // Capture phase

                // Check fullscreen status continuously (every 1 second)
                fullscreenCheckInterval = setInterval(checkFullscreen, 1000);

                console.log('[Security] Violation detection activated after grace period');
            }, 3000);
        });

        // Sembunyikan navbar saat overlay aktif
        watch(
            () => isBlocked.value || showFullscreenWarning.value,
            (overlayActive) => {
                if (overlayActive) {
                    document.body.classList.add("overlay-active");
                } else {
                    document.body.classList.remove("overlay-active");
                }
            },
            { immediate: true },
        );

        onBeforeUnmount(() => {
            if (checkStatusInterval) {
                clearInterval(checkStatusInterval);
            }
            if (fullscreenCheckInterval) {
                clearInterval(fullscreenCheckInterval);
            }
            if (answerFlushInterval) {
                clearInterval(answerFlushInterval);
                answerFlushInterval = null;
            }

            // Kirim sisa jawaban yang masih di antrean saat keluar halaman.
            window.removeEventListener("pagehide", flushOnPageHide);
            void flushAnswers();

            // Bersihkan class overlay saat komponen unmount
            document.body.classList.remove("overlay-active");

            // Leave WebSocket channel
            if (window.Echo) {
                window.Echo.leave(`student.${studentId}`);
            }

            document.removeEventListener(
                "visibilitychange",
                handleVisibilityChange,
            );
            window.removeEventListener("blur", handleBlur);
            window.removeEventListener("blur", handleWindowBlur, true);
            window.removeEventListener("focus", handleWindowFocus, true);
            document.removeEventListener("contextmenu", handleContextMenu);
            document.removeEventListener("keydown", handleKeyDown);
            document.removeEventListener("keydown", handleAnswerKeyPress);
            document.removeEventListener("fullscreenchange", checkFullscreen);
            document.removeEventListener(
                "webkitfullscreenchange",
                checkFullscreen,
            );
        });

        // ── Penyimpanan batch (antrean lokal) ────────────────────────
        // Jawaban tidak dikirim 1 request per soal, melainkan dikumpulkan
        // dalam antrean lokal lalu dikirim batch (tiap 1,5 detik atau saat
        // 25 jawaban terkumpul). Jauh lebih cepat & ringan untuk server.
        // Jika request gagal, jawaban tetap aman di localStorage dan ikut
        // terkirim lengkap saat endExam/autoSubmitExam.
        const BATCH_SIZE = 25;
        const answerQueue = new Map(); // question_id -> answer
        let answerFlushInterval = null;
        let flushing = false;
        let flushPromise = null;

        const enqueueAnswer = (questionId, answer) => {
            answerQueue.set(questionId, answer);
            if (answerQueue.size >= BATCH_SIZE) {
                void flushAnswers();
            }
        };

        // Kirim jawaban antrean sebagai satu request batch. Hanya satu flush
        // yang boleh berjalan dalam satu waktu. Jawaban yang gagal tetap di
        // antrean dan dicoba lagi pada flush berikutnya.
        const flushAnswers = async () => {
            if (flushing) return flushPromise;
            if (answerQueue.size === 0) return Promise.resolve();

            flushing = true;

            const p = (async () => {
                try {
                    while (answerQueue.size > 0) {
                        const batch = [...answerQueue.entries()].slice(
                            0,
                            BATCH_SIZE,
                        );
                        const body = batch.map(([question_id, answer]) => ({
                            question_id,
                            answer,
                        }));

                        try {
                            const data = await fetchJsonWithCsrf(
                                "/student/exam-answers",
                                {
                                    method: "POST",
                                    headers: csrfHeaders(),
                                    body: JSON.stringify({
                                        grade_id:
                                            props.grade?.id ||
                                            props.exam_group?.id,
                                        answers: body,
                                    }),
                                },
                                { csrfRefreshUrl: "/student/csrf-token" },
                            );

                            // Waktu habis saat menyimpan → langsung auto-submit
                            if (data && data.time_up) {
                                autoSubmitExam();
                            }

                            // Sukses: buang jawaban yang sudah tersimpan.
                            batch.forEach(([question_id]) =>
                                answerQueue.delete(question_id),
                            );
                        } catch (error) {
                            // Gagal: berhenti dulu, biarkan jawaban di antrean.
                            // Flush berikutnya akan mencoba lagi.
                            console.warn(
                                `[flushAnswers] ${batch.length} jawaban gagal terkirim; akan dicoba lagi.`,
                                error,
                            );
                            break;
                        }
                    }
                } finally {
                    flushing = false;
                    flushPromise = null;
                }
            })();

            flushPromise = p;
            return p;
        };

        // Usaha terakhir saat halaman ditutup: kirim sisa jawaban tanpa
        // menunggu respons (keepalive). Tidak bisa di-retry, tapi jauh lebih
        // baik daripada hilang.
        const flushOnPageHide = () => {
            if (answerQueue.size === 0) return;

            const body = [...answerQueue.entries()].map(
                ([question_id, answer]) => ({ question_id, answer }),
            );

            try {
                fetch("/student/exam-answers", {
                    method: "POST",
                    keepalive: true,
                    headers: csrfHeaders(),
                    body: JSON.stringify({
                        grade_id: props.grade?.id || props.exam_group?.id,
                        answers: body,
                    }),
                }).catch(() => {});
            } catch (e) {
                // abaikan
            }
        };

        const submitAnswer = (exam_id, question_id, answer) => {
            localAnswers.value[question_id] = answer;
            localStorage.setItem(
                storageKey.value,
                JSON.stringify(localAnswers.value),
            );
            // Antrekan untuk dikirim batch di background.
            enqueueAnswer(question_id, answer);
        };

        // FIX BUG #2: Client-side countdown only, no periodic API calls
        const handleChangeDuration = (data) => {
            duration.value = data.totalMilliseconds;
            // No DB write - countdown is purely client-side
            // Server validates time on next page load or answer submit
        };

        // Guard: cegah auto-submit ganda (dari countdown @end, blokir
        // pelanggaran ke-3, dan response time_up yang tumpang tindih).
        let autoSubmitInFlight = false;

        const autoSubmitExam = () => {
            if (autoSubmitInFlight) return;
            autoSubmitInFlight = true;

            // Exit fullscreen before auto-submit
            if (document.exitFullscreen) {
                document.exitFullscreen();
            } else if (document.webkitExitFullscreen) {
                document.webkitExitFullscreen();
            } else if (document.msExitFullscreen) {
                document.msExitFullscreen();
            }

            // Submit jawaban otomatis tanpa konfirmasi dan tanpa validasi semua soal harus dijawab
            setTimeout(() => {
                router.post(
                    "/student/exam-end",
                    {
                        exam_id: props.exam?.id || props.exam_group?.exam?.id,
                        grade_id: props.grade?.id || props.exam_group?.id,
                        exam_group_id: props.grade?.id || props.exam_group?.id,
                        answers: localAnswers.value,
                        is_auto_submit: true, // Flag untuk skip validasi
                    },
                    {
                        onSuccess: () => {
                            localStorage.removeItem(storageKey.value);
                        },
                        onError: () => {
                            // Izinkan percobaan ulang jika request gagal
                            autoSubmitInFlight = false;
                        },
                    },
                );
            }, 1000); // Delay 1 detik agar siswa sempat melihat notifikasi blokir
        };

        const endExam = () => {
            if (computedQuestionAnswered.value < props.total_questions) {
                Swal.fire({
                    title: "Belum Selesai!",
                    text: `Anda masih memiliki ${props.total_questions - computedQuestionAnswered.value} soal yang belum dijawab.`,
                    icon: "warning",
                    confirmButtonText: "OK",
                });
                return;
            }

            Swal.fire({
                title: "Submit Jawaban?",
                text: "Apakah Anda yakin ingin men-submit semua jawaban?",
                icon: "question",
                showCancelButton: true,
                confirmButtonColor: "#1A2332",
                cancelButtonColor: "#94a3b8",
                confirmButtonText: "Ya, Submit!",
                cancelButtonText: "Batal",
            }).then((result) => {
                if (result.isConfirmed) {
                    // Exit fullscreen before submit
                    if (document.exitFullscreen) {
                        document.exitFullscreen();
                    } else if (document.webkitExitFullscreen) {
                        document.webkitExitFullscreen();
                    } else if (document.msExitFullscreen) {
                        document.msExitFullscreen();
                    }

                    router.post(
                        "/student/exam-end",
                        {
                            exam_id: props.exam?.id || props.exam_group?.exam?.id,
                            grade_id: props.grade?.id || props.exam_group?.id,
                            exam_group_id: props.grade?.id || props.exam_group?.id,
                            answers: localAnswers.value,
                        },
                        {
                            onSuccess: () => {
                                localStorage.removeItem(storageKey.value);
                            },
                        },
                    );
                }
            });
        };

        const formatViolationTime = (timeStr) => {
            if (!timeStr) return "-";
            const d = new Date(timeStr);
            return d.toLocaleTimeString("id-ID", {
                hour: "2-digit",
                minute: "2-digit",
                second: "2-digit",
            });
        };

        // ── Helpers UI (desain flat) ────────────────────────────
        const gridClass = (question) => {
            if (Number(question.question_order) === Number(currentPage.value)) {
                return "grid-btn grid-btn-active";
            }
            if (question.answer != 0) {
                return "grid-btn grid-btn-answered";
            }
            return "grid-btn";
        };

        const formatTimer = (hours, minutes, seconds) => {
            if (hours > 0) {
                return `${hours}j ${minutes}m ${seconds}s`;
            }
            return `${String(minutes).padStart(2, "0")}:${String(seconds).padStart(2, "0")}`;
        };

        const timerClass = (totalSeconds) => {
            if (totalSeconds <= 10) return "timer-box timer-danger";
            if (totalSeconds <= 60) return "timer-box timer-warning";
            return "timer-box timer-safe";
        };

        return {
            options,
            currentPage,
            activeQuestion,
            activeAnswerOrder,
            goToPage,
            isBlocked,
            showFullscreenWarning,
            showNavigationModal,
            searchQuery,
            filteredQuestions,
            sheetStyle,
            onTouchStart,
            onTouchMove,
            onTouchEnd,
            violationCount,
            violations,
            formatViolationTime,
            isPersonality,
            all_questions: computedAllQuestions,
            question_answered: computedQuestionAnswered,
            enterFullscreen,
            submitAnswer,
            handleChangeDuration,
            endExam,
            autoSubmitExam,
            duration,
            gridClass,
            formatTimer,
            timerClass,
            sanitize: sanitizeHtml,
        };
    },
};
</script>

<style scoped>
.page-wrap {
    font-family:
        "Inter",
        -apple-system,
        BlinkMacSystemFont,
        "Segoe UI",
        Roboto,
        sans-serif;
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
    font-family: "SFMono-Regular", Consolas, monospace;
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
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border-radius: 8px;
    font-weight: 600;
    font-size: 0.875rem;
    border: 1px solid #cbd5e1;
    background: #ffffff;
    color: #64748b;
    padding: 8px 0;
    text-decoration: none;
    transition: all 0.15s ease;
}

.grid-btn:hover {
    background: #f1f5f9;
    color: #475569;
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

/* ── Overlay Fullscreen & Blocked ──────────────── */
.fullscreen-warning {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.92);
    z-index: 9999;
    overflow-y: auto;
}

.blocked-overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.95);
    z-index: 10001;
    display: flex;
    align-items: center;
    justify-content: center;
    overflow-y: auto;
}

.overlay-content {
    text-align: center;
    color: white;
    padding: 2rem;
    max-width: 520px;
}

.overlay-content h2 {
    color: white;
}

.violation-list {
    background: rgba(255, 255, 255, 0.06);
    border: 1px solid rgba(255, 255, 255, 0.1);
    border-radius: 12px;
    padding: 16px;
    text-align: left;
}

.violation-item {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 8px 12px;
    border-radius: 8px;
    margin-bottom: 6px;
    background: rgba(255, 255, 255, 0.04);
    border: 1px solid rgba(255, 255, 255, 0.07);
}

.violation-item:last-child {
    margin-bottom: 0;
}

.violation-number {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 22px;
    height: 22px;
    border-radius: 50%;
    background: rgba(255, 255, 255, 0.15);
    color: #fff;
    font-size: 11px;
    font-weight: 700;
    flex-shrink: 0;
}

.violation-badge {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    font-size: 0.8rem;
    font-weight: 600;
    padding: 3px 10px;
    border-radius: 20px;
    background: rgba(239, 68, 68, 0.25);
    color: #fca5a5;
    border: 1px solid rgba(239, 68, 68, 0.3);
    flex-grow: 1;
}

.violation-time {
    font-size: 0.75rem;
    color: rgba(255, 255, 255, 0.45);
    white-space: nowrap;
    flex-shrink: 0;
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
    touch-action: none; /* Mencegah default scrolling saat men-drag area handle */
    animation: sheetSlideUp 0.25s cubic-bezier(0.16, 1, 0.3, 1);
}

.bottom-sheet-handle-wrap {
    display: flex;
    justify-content: center;
    padding: 12px 0;
    cursor: pointer;
    flex-shrink: 0;
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

.search-clear-btn {
    border: none;
    background: none;
    color: #94a3b8;
    cursor: pointer;
    padding: 0 4px;
}

.search-clear-btn:hover {
    color: #64748b;
}

.scrollable-grid {
    flex: 1;
    overflow-y: auto;
    padding-bottom: 20px;
    touch-action: auto; /* Izinkan scroll dalam grid nomor */
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
@media (max-width: 991.98px) {
    .flat-footer {
        flex-wrap: wrap;
    }
}

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

    .violation-list {
        padding: 10px;
    }

    .violation-item {
        display: grid;
        grid-template-columns: 28px minmax(0, 1fr);
        grid-template-rows: auto auto;
        column-gap: 10px;
        row-gap: 5px;
        align-items: center;
        padding: 10px;
    }

    .violation-number {
        grid-column: 1;
        grid-row: 1 / 3;
        width: 28px;
        height: 28px;
    }

    .violation-badge {
        grid-column: 2;
        grid-row: 1;
        min-width: 0;
        padding: 4px 8px;
        border-radius: 7px;
        white-space: normal;
    }

    .violation-time {
        grid-column: 2;
        grid-row: 2;
        color: rgba(255, 255, 255, 0.7);
        font-size: 0.78rem;
        font-weight: 600;
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
