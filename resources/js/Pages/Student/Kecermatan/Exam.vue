<template>
    <Head :title="'Tes Kecermatan - Kolom ' + currentColumn" />

    <!-- Fullscreen Container -->
    <div class="exam-fullscreen bg-white">
        <!-- Header / Navbar -->
        <div class="exam-header bg-white shadow-sm border-bottom">
            <div class="container-fluid px-4">
                <div
                    class="d-flex justify-content-between align-items-center py-3"
                >
                    <div>
                        <h2
                            class="text-4xl sm:text-6xl font-semibold flex items-center gap-2 mb-0"
                        >
                            <span class="text-2xl text-muted">Durasi:</span>
                            <span class="timer-display" :class="timerClass">
                                {{ formattedTime }}
                            </span>
                        </h2>
                    </div>
                    <div class="text-end">
                        <small class="text-muted d-block">KOLOM</small>
                        <strong class="text-dark fs-4"
                            >{{ currentColumn }} dari 10</strong
                        >
                    </div>
                </div>
            </div>
        </div>

        <!-- Progress Bar -->
        <div class="progress-wrapper bg-white py-2 border-bottom">
            <div class="container-fluid px-4">
                <div class="d-flex justify-content-between align-items-center">
                    <small class="text-muted">Progress</small>
                    <small class="text-muted"
                        >{{ answeredInColumn }} / 50</small
                    >
                </div>
                <div class="progress" style="height: 8px">
                    <div
                        class="progress-bar bg-primary"
                        role="progressbar"
                        :style="{ width: progressPercentage + '%' }"
                    ></div>
                </div>
            </div>
        </div>

        <!-- Main Content -->
        <div
            class="exam-content d-flex flex-column align-items-center justify-content-center flex-grow-1 py-4 bg-white"
        >
            <div class="container px-2 px-sm-3" v-if="!isBlocked">
                <!-- Referensi Box -->
                <div class="reference-box mb-4">
                    <div class="card border-0">
                        <!-- Label Kolom -->
                        <div class="text-center mb-3">
                            <span
                                class="fw-bold text-dark"
                                style="font-size: 1.2rem"
                            >
                                Kolom {{ currentColumn }}
                            </span>
                        </div>
                        <div class="card-body p-2 p-md-4">
                            <div
                                class="d-flex justify-content-center align-items-center gap-1 gap-md-3 flex-nowrap"
                            >
                                <!-- Reference Items -->
                                <div
                                    v-for="(
                                        item, index
                                    ) in activeQuestion.reference_sequence"
                                    :key="index"
                                    class="reference-item text-center"
                                >
                                    <div
                                        class="reference-value bg-white border mb-1"
                                    >
                                        {{ item }}
                                    </div>
                                    <div
                                        class="reference-label text-muted fw-bold"
                                        style="font-size: 1.1rem"
                                    >
                                        {{ ["A", "B", "C", "D", "E"][index] }}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Nomor Soal -->
                <div class="text-center mb-3">
                    <span
                        class="fw-bold text-primary"
                        style="font-size: 1.3rem"
                    >
                        Soal {{ currentIndex + 1 }}
                    </span>
                </div>

                <!-- Question Box -->
                <div class="question-box mb-4">
                    <div class="card border-0">
                        <div class="card-body p-2 p-md-4">
                            <div
                                class="d-flex justify-content-center align-items-center gap-2 gap-md-3 flex-nowrap"
                            >
                                <div
                                    v-for="(
                                        item, index
                                    ) in activeQuestion.question_sequence"
                                    :key="index"
                                    class="question-value bg-white border"
                                >
                                    {{ item }}
                                </div>
                                <div
                                    class="question-value missing bg-white border"
                                >
                                    ?
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Answer Buttons -->
                <div
                    class="answer-buttons d-flex justify-content-center gap-2 gap-md-3 mb-4 flex-nowrap"
                >
                    <button
                        v-for="(letter, index) in ['A', 'B', 'C', 'D', 'E']"
                        :key="letter"
                        class="btn answer-btn bg-white border"
                        :class="{
                            selected: activeQuestion.student_answer === letter,
                            'answer-clicked': clickedLetter === letter,
                        }"
                        @click="selectAnswer(letter)"
                    >
                        <span class="answer-number d-none d-md-inline">{{
                            index + 1
                        }}</span>
                        <span class="answer-letter">{{ letter }}</span>
                    </button>
                </div>

                <!-- Keyboard Hint (Desktop Only) -->
                <div class="text-center d-none d-md-block">
                    <div
                        class="alert alert-info d-inline-block bg-white border"
                    >
                        <i class="fas fa-keyboard me-2"></i>
                        <strong>Tip:</strong> Gunakan keyboard 1, 2, 3, 4, 5
                        untuk jawab cepat
                    </div>
                </div>
            </div>
        </div>

        <!-- Footer -->
        <div class="exam-footer bg-white border-top py-3">
            <div class="container-fluid">
                <p class="text-center text-muted mb-0 small">
                    <i class="fas fa-info-circle me-1"></i>
                    Jawaban akan otomatis tersimpan dan langsung ke soal
                    berikutnya
                </p>
            </div>
        </div>
    </div>

    <!-- Fullscreen Warning Overlay -->
    <div v-if="showFullscreenWarning && !isBlocked" class="fullscreen-warning">
        <div class="warning-content">
            <i class="fas fa-exclamation-triangle fa-4x text-warning mb-3"></i>
            <h2 class="mb-2">Peringatan!</h2>
            <p class="lead mb-4">
                Anda harus mengaktifkan mode fullscreen untuk melanjutkan ujian.
            </p>
            <button @click="enterFullscreen" class="btn btn-lg btn-warning">
                <i class="fa fa-expand me-2"></i> Aktifkan Fullscreen
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

    <!-- Loading Overlay - Simple & Clean -->
    <div v-show="isSubmitting" class="loading-overlay-simple">
        <div class="loading-card-simple">
            <div class="spinner-simple"></div>
            <h3 class="mt-4 mb-2 text-dark fw-bold">{{ submittingMessage }}</h3>
            <p class="text-muted mb-0">Mohon tunggu sebentar...</p>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, watch, onMounted, onUnmounted } from "vue";
import { Head, router } from "@inertiajs/vue3";

const props = defineProps({
    session: Object,
    all_columns: Object,
    column_questions: Array,
    current_index: Number,
    question: Object,
    progress: Object,
    timer: Object,
    violations: Array,
});

// Current column & all columns local reactive state for 0ms instant switching
const currentColumn = ref(props.session?.current_column || 1);
const allColumnsMap = ref(props.all_columns || {});

// Local questions list for active column
const questionsList = computed(() => {
    if (allColumnsMap.value && allColumnsMap.value[currentColumn.value]) {
        return allColumnsMap.value[currentColumn.value];
    }
    return props.column_questions || [];
});

const currentIndex = ref(props.current_index || 0);

// Active question computed property
const activeQuestion = computed(() => {
    if (questionsList.value && questionsList.value[currentIndex.value]) {
        return questionsList.value[currentIndex.value];
    }
    return props.question || {};
});

// Watch column questions change from server if props update
watch(
    () => props.column_questions,
    (newQuestions) => {
        if (newQuestions && newQuestions.length > 0) {
            // CRITICAL: Deep clone untuk memastikan reactivity dan mencegah reference issues
            const clonedQuestions = JSON.parse(JSON.stringify(newQuestions));
            allColumnsMap.value[currentColumn.value] = clonedQuestions;
            console.log(`[watch] Updated column ${currentColumn.value} with ${clonedQuestions.length} questions from server`);
            
            // Force reactivity update
            allColumnsMap.value = { ...allColumnsMap.value };
        }
    },
    { immediate: true, deep: true }
);

// Number of answered in column
const answeredInColumn = computed(() => {
    if (!questionsList.value) return 0;
    return questionsList.value.filter((q) => q.student_answer != null).length;
});

// Use server-calculated remaining time
// Gunakan nullish coalescing agar nilai 0 dari server tidak berubah menjadi 60.
const timeLeft = ref(props.timer?.remaining_seconds ?? 60);
const beepAudio = ref(null);

// Violation tracking - initialize from server data
const isBlocked = ref(props.session?.is_blocked || false);
const showFullscreenWarning = ref(false);
const gracePeriod = ref(false);
const violationCount = ref(props.session?.violation_count || 0);
const violations = ref(props.violations || []);
let timerInterval = null;
let checkStatusInterval = null;
let fullscreenCheckInterval = null;
let answerFlushInterval = null;
let columnFinishing = false;

// Loading overlay state for column submission
const isSubmitting = ref(false);
const submittingMessage = ref("");

// Antrean jawaban lokal yang belum terkirim ke server.
// key = question_id, value = { answer, time_spent }.
const answerQueue = new Map();
const BATCH_SIZE = 25;
let flushing = false;
let flushPromise = null;

// Kirim jawaban antrean sebagai satu request batch. Hanya satu flush yang
// boleh berjalan dalam satu waktu; sisanya menunggu giliran. Jawaban yang
// gagal tetap di antrean dan dicoba lagi pada flush berikutnya, sehingga
// tidak ada jawaban yang hilang meski server sempat lambat.
const flushAnswers = async () => {
    if (flushing) return flushPromise;
    if (answerQueue.size === 0) return Promise.resolve();

    flushing = true;

    const p = (async () => {
        try {
            // Kosongkan seluruh antrean: kirim per batch sampai habis.
            // Maksimal 50 jawaban per kolom, jadi paling banyak 2 batch.
            while (answerQueue.size > 0) {
                const batch = [...answerQueue.entries()].slice(0, BATCH_SIZE);
                const body = batch.map(([question_id, data]) => ({
                    question_id,
                    answer: data.answer,
                    time_spent: data.time_spent,
                }));

                try {
                    await fetchJsonWithRetry(
                        "/student/kecermatan/submit-answers",
                        {
                            method: "POST",
                            headers: {
                                "Content-Type": "application/json",
                                "X-CSRF-TOKEN": getCsrfToken(),
                                Accept: "application/json",
                            },
                            body: JSON.stringify({ answers: body }),
                        },
                        3,
                        10000,
                    );

                    // Sukses: buang jawaban yang sudah tersimpan.
                    batch.forEach(([question_id]) =>
                        answerQueue.delete(question_id),
                    );
                } catch (error) {
                    // Gagal: berhenti dulu, biarkan jawaban di antrean.
                    // Interval flush berikutnya (1,5 detik) atau finalisasi
                    // kolom akan mencoba lagi.
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

// Usaha terakhir saat halaman ditutup: kirim sisa jawaban tanpa menunggu
// respons (keepalive). Tidak bisa di-retry, tapi jauh lebih baik daripada hilang.
const flushOnPageHide = () => {
    if (answerQueue.size === 0) return;

    const body = [...answerQueue.entries()].map(([question_id, data]) => ({
        question_id,
        answer: data.answer,
        time_spent: data.time_spent,
    }));

    try {
        fetch("/student/kecermatan/submit-answers", {
            method: "POST",
            keepalive: true,
            headers: {
                "Content-Type": "application/json",
                "X-CSRF-TOKEN": getCsrfToken(),
                Accept: "application/json",
            },
            body: JSON.stringify({ answers: body }),
        }).catch(() => {});
    } catch (e) {
        // abaikan
    }
};

// Finalisasi kolom 1-9 dibuat berurutan supaya request kolom berikutnya
// tidak mendahului finalisasi kolom sebelumnya. Backend kini toleran terhadap
// urutan, jadi antrean ini hanya pengaman tambahan, bukan penentu hasil.
let columnFinalizationChain = Promise.resolve();

const getCsrfToken = () => {
    return document.querySelector('meta[name="csrf-token"]')?.content || "";
};

// Timeout per percobaan (ms) agar request yang menggantung tidak membuat
// overlay "menunggu hasil" berputar selamanya.
const REQUEST_TIMEOUT_MS = 10000;

const fetchJsonWithRetry = async (url, options, maxAttempts = 3, timeoutMs = REQUEST_TIMEOUT_MS) => {
    let lastError = null;

    for (let attempt = 1; attempt <= maxAttempts; attempt++) {
        const controller = new AbortController();
        const timeoutId = setTimeout(() => controller.abort(), timeoutMs);

        try {
            const response = await fetch(url, {
                ...options,
                signal: controller.signal,
            });
            const data = await response.json().catch(() => ({}));

            if (!response.ok) {
                throw new Error(
                    data.message ||
                        data.error ||
                        `Request gagal dengan status HTTP ${response.status}`,
                );
            }

            return data;
        } catch (error) {
            lastError = error;

            if (attempt < maxAttempts) {
                await new Promise((resolve) =>
                    setTimeout(resolve, attempt * 300),
                );
            }
        } finally {
            clearTimeout(timeoutId);
        }
    }

    throw lastError || new Error("Request gagal diproses.");
};

// Tunggu jawaban tersimpan dengan batas waktu. Jika ada yang gagal atau
// timeout, tetap lanjutkan: backend menghitung hasil dari database, jadi
// finalisasi tidak boleh dibatalkan hanya karena 1-2 request jawaban gagal.
const waitForAnswerRequests = async (requests, timeoutMs = 6000) => {
    if (!requests.length) return;

    let results;
    try {
        results = await Promise.race([
            Promise.allSettled(requests),
            new Promise((resolve) =>
                setTimeout(() => resolve("timeout"), timeoutMs),
            ),
        ]);
    } catch (e) {
        return;
    }

    if (results === "timeout") {
        console.warn("[waitForAnswerRequests] Timeout menunggu jawaban tersimpan.");
        return;
    }

    const failed = results.filter((result) => result.status === "rejected");
    if (failed.length > 0) {
        console.warn(
            `[waitForAnswerRequests] ${failed.length} jawaban gagal tersimpan; tetap melanjutkan.`,
        );
    }
};

const submitColumnResult = async (columnNumber) => {
    // Pastikan jawaban kolom ini sudah masuk database sebelum dihitung.
    await waitForAnswerRequests([flushAnswers()], 6000);

    return fetchJsonWithRetry(
        `/student/kecermatan/${props.session.id}/column-timeout`,
        {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
                "X-CSRF-TOKEN": getCsrfToken(),
                Accept: "application/json",
            },
            body: JSON.stringify({
                column_number: columnNumber,
            }),
        },
    );
};

const enqueueColumnFinalization = (columnNumber) => {
    columnFinalizationChain = columnFinalizationChain.then(async () => {
        try {
            await submitColumnResult(columnNumber);
        } catch (error) {
            // Tidak menggagalkan kolom berikutnya: backend akan mengisi kolom
            // yang terlewat saat finalisasi kolom berikutnya diterima.
            console.error(
                `[nextColumn] Gagal menyimpan hasil kolom ${columnNumber}:`,
                error,
            );
        }
    });
};

const exitFullscreenSafely = async () => {
    try {
        if (document.fullscreenElement && document.exitFullscreen) {
            await document.exitFullscreen();
        } else if (
            document.webkitFullscreenElement &&
            document.webkitExitFullscreen
        ) {
            document.webkitExitFullscreen();
        } else if (document.msFullscreenElement && document.msExitFullscreen) {
            document.msExitFullscreen();
        }
    } catch (error) {
        console.warn("Gagal keluar dari fullscreen:", error);
    }
};

// Pindah kolom secara lokal tetap instan, sedangkan penyimpanan dijalankan
// berurutan di background. Khusus kolom 10, redirect menunggu backend selesai.
const nextColumn = async () => {
    const finishedColumn = currentColumn.value;

    console.log(
        `[nextColumn] Finishing column ${finishedColumn}, timer: ${timeLeft.value}s`,
    );

    // Kolom 10: selesaikan ujian secepat mungkin.
    // Backend kini toleran terhadap request out-of-order (mengisi kolom yang
    // terlewat), sehingga tidak perlu menunggu finalisasi kolom 1-9 di sini.
    if (finishedColumn >= 10) {
        if (timerInterval) {
            clearInterval(timerInterval);
            timerInterval = null;
        }
        if (checkStatusInterval) {
            clearInterval(checkStatusInterval);
            checkStatusInterval = null;
        }
        if (fullscreenCheckInterval) {
            clearInterval(fullscreenCheckInterval);
            fullscreenCheckInterval = null;
        }

        isSubmitting.value = true;
        submittingMessage.value = "Menyimpan jawaban terakhir...";

        try {
            // Tunggu finalisasi kolom 1-9 selesai (dengan batas waktu agar tidak
            // macet). Ini memastikan jawaban kolom sebelumnya sudah tersimpan
            // sebelum kolom 10 dihitung. Chain normalnya selesai < 1 detik,
            // jadi hampir tidak menambah delay.
            await Promise.race([
                columnFinalizationChain,
                new Promise((resolve) =>
                    setTimeout(resolve, 4000),
                ),
            ]);

            // Tunggu jawaban kolom 10 tersimpan (dengan batas waktu).
            await waitForAnswerRequests([flushAnswers()], 6000);

            submittingMessage.value = "Menghitung hasil ujian...";
            await submitColumnResult(finishedColumn);

            await exitFullscreenSafely();

            router.visit(`/student/kecermatan/result/${props.session.id}`, {
                method: "get",
                replace: true,
                preserveState: false,
                preserveScroll: false,
            });
        } catch (error) {
            console.error("[nextColumn] Final submit gagal:", error);

            // Fallback: paksa selesaikan ujian via force-finish. Backend
            // menghitung semua kolom (termasuk yang terlewat) lalu mengarahkan
            // ke halaman hasil, jadi siswa selalu sampai ke hasilnya.
            try {
                submittingMessage.value = "Menyelesaikan ujian...";
                await exitFullscreenSafely();

                router.post(
                    `/student/kecermatan/${props.session.id}/force-finish`,
                    {},
                    {
                        preserveState: false,
                        preserveScroll: false,
                        onError: () => {
                            isSubmitting.value = false;
                            columnFinishing = false;
                            alert(
                                "Hasil ujian belum berhasil disimpan. Silakan muat ulang halaman atau hubungi pengawas.",
                            );
                        },
                    },
                );
            } catch (fallbackError) {
                console.error("[nextColumn] Fallback force-finish gagal:", fallbackError);

                isSubmitting.value = false;
                columnFinishing = false;

                alert(
                    "Hasil ujian belum berhasil disimpan. Silakan muat ulang halaman atau hubungi pengawas.",
                );
            }
        }

        return;
    }

    // Kolom 1-9: antrekan finalisasi dibuat sebelum berpindah secara lokal.
    enqueueColumnFinalization(finishedColumn);

    // Perpindahan tampilan tetap instan.
    currentColumn.value++;
    currentIndex.value = 0;
    timeLeft.value = 60;

    console.log(
        `[nextColumn] Switched to column ${currentColumn.value}, timer reset to 60s`,
    );

    try {
        window.history.replaceState(
            window.history.state,
            "",
            `/student/kecermatan/exam/${props.session.id}/${currentColumn.value}/1`,
        );
    } catch (error) {
        console.warn("Gagal memperbarui URL kolom:", error);
    }

    startTimer();
};

const startTimer = () => {
    if (timerInterval) clearInterval(timerInterval);

    // Reset columnFinishing flag when starting new timer
    columnFinishing = false;

    timerInterval = setInterval(() => {
        if (isBlocked.value) return; // Pause timer while student is blocked by admin

        if (timeLeft.value > 0) {
            timeLeft.value--;

            // Beep sound at last 5 seconds
            if (timeLeft.value <= 5 && timeLeft.value > 0) {
                if (beepAudio.value) {
                    beepAudio.value.play().catch(() => {});
                }
            }
        } else {
            // Time's up! Only trigger if not already finishing
            clearInterval(timerInterval);
            timerInterval = null;
            if (!columnFinishing) {
                columnFinishing = true;
                void nextColumn();
            }
        }
    }, 1000);
};

// Watch answeredInColumn - pindah kolom otomatis 0ms saat semua 50 soal terjawab
watch(
    answeredInColumn,
    (count) => {
        // Debounce: only trigger if not already finishing column
        if (count >= 50 && !columnFinishing) {
            columnFinishing = true;
            void nextColumn();
        }
    },
    { flush: "post" },
); // Execute after DOM updates

// Computed
const formattedTime = computed(() => {
    const minutes = Math.floor(timeLeft.value / 60);
    const seconds = Math.floor(timeLeft.value % 60);
    return `${String(minutes).padStart(2, "0")}:${String(seconds).padStart(2, "0")}`;
});

const timerClass = computed(() => {
    if (timeLeft.value <= 5) return "text-danger timer-pulse-fast";
    if (timeLeft.value <= 10) return "text-warning timer-pulse";
    return "text-success";
});

const progressPercentage = computed(() => {
    return Math.round((answeredInColumn.value / 50) * 100);
});

// Click animation state
const clickedLetter = ref(null);

// Instant 0ms selectAnswer!
const selectAnswer = (letter) => {
    if (isBlocked.value || isSubmitting.value || columnFinishing) return;

    const q = activeQuestion.value;
    if (!q || !q.id) return;

    clickedLetter.value = letter;
    setTimeout(() => {
        clickedLetter.value = null;
    }, 200);

    const questionId = q.id;
    const timeSpent = Math.max(0, 60 - timeLeft.value);

    // Simpan jawaban secara lokal agar perpindahan soal tidak menunggu server.
    // Update di questionsList DAN allColumnsMap agar persistent saat refresh/switch kolom
    const qIndex = questionsList.value.findIndex((qq) => qq.id === questionId);
    if (qIndex !== -1) {
        // Update di questionsList (computed dari allColumnsMap)
        questionsList.value[qIndex].student_answer = letter;
        
        // CRITICAL: Update di allColumnsMap juga agar jawaban tidak hilang saat refresh
        if (allColumnsMap.value[currentColumn.value]) {
            // Clone untuk trigger reactivity
            const updatedColumn = [...allColumnsMap.value[currentColumn.value]];
            if (updatedColumn[qIndex]) {
                updatedColumn[qIndex] = {
                    ...updatedColumn[qIndex],
                    student_answer: letter
                };
                allColumnsMap.value[currentColumn.value] = updatedColumn;
                
                // Force reactivity
                allColumnsMap.value = { ...allColumnsMap.value };
            }
        }
    }

    // Pindah ke soal berikutnya
    if (currentIndex.value < questionsList.value.length - 1) {
        currentIndex.value++;
    }

    // Simpan ke database secara batch di background.
    // Jawaban masuk antrean lokal dulu (UI sudah instan), lalu dikirim 1
    // request per beberapa jawaban agar tidak membanjiri server.
    answerQueue.set(questionId, { answer: letter, time_spent: timeSpent });

    // Jika antrean sudah besar, segera kirim tanpa menunggu interval.
    if (answerQueue.size >= BATCH_SIZE) {
        void flushAnswers();
    }
};

// Keyboard support
const handleKeyPress = (e) => {
    const keyMap = { 1: "A", 2: "B", 3: "C", 4: "D", 5: "E" };
    if (keyMap[e.key] && !isBlocked.value) {
        selectAnswer(keyMap[e.key]);
    }
};

// Timer countdown timeout -> move to next column
const handleTimeout = () => {
    router.post(
        `/student/kecermatan/${props.session.id}/column-timeout`,
        {
            column_number: props.session.current_column,
        },
        {
            preserveState: false,
            preserveScroll: false,
        },
    );
};

// Fullscreen handling
const enterFullscreen = () => {
    const elem = document.documentElement;
    try {
        if (elem.requestFullscreen) {
            elem.requestFullscreen().catch(() => {});
        } else if (elem.webkitRequestFullscreen) {
            elem.webkitRequestFullscreen();
        } else if (elem.msRequestFullscreen) {
            elem.msRequestFullscreen();
        }
        showFullscreenWarning.value = false;
    } catch (err) {}
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

// Fullscreen tracking
const handleFullscreenChange = () => {
    checkFullscreen();
};

let blurTimer = null;

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

const handleContextMenu = (e) => {
    e.preventDefault();
    return false;
};

const handleSecurityKeyDown = (e) => {
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

// Debounce flag to prevent duplicate violations
let isLoggingViolation = false;
let lastViolationTime = 0;
const VIOLATION_COOLDOWN = 2000; // 2 seconds cooldown between violations

const logViolation = async (type) => {
    if (gracePeriod.value || isBlocked.value || isLoggingViolation) return;

    // Prevent spamming violations
    const now = Date.now();
    if (now - lastViolationTime < VIOLATION_COOLDOWN) {
        return;
    }

    isLoggingViolation = true;
    lastViolationTime = now;

    try {
        const response = await fetch("/student/kecermatan/log-violation", {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
                "X-CSRF-TOKEN": document.querySelector(
                    'meta[name="csrf-token"]',
                )?.content || "",
                Accept: "application/json",
            },
            body: JSON.stringify({
                session_id: props.session.id,
                violation_type: type,
                column_number: currentColumn.value,
                question_number: currentIndex.value + 1,
            }),
        });

        // Handle both success (200) and blocked (403) responses
        if (response.ok || response.status === 403) {
            const data = await response.json().catch(() => ({}));

            violationCount.value = data.violation_count || 0;
            violations.value = data.violations || [];

            if (data.is_blocked) {
                isBlocked.value = true;

                if (data.should_auto_submit) {
                    // 3 violations - auto submit exam after 2 seconds
                    setTimeout(() => {
                        autoSubmitExam();
                    }, 2000);
                }
                // < 3 violations - overlay stays, checkStatus polling handles unblock
            }
        } else {
            console.warn("Violation log failed with status:", response.status);
        }
    } catch (e) {
        console.warn("Failed to log violation:", e);
    } finally {
        setTimeout(() => {
            isLoggingViolation = false;
        }, 500);
    }
};

// Check status polling (sync admin unblock in real-time)
const checkStatus = async () => {
    if (!props.session?.id) return;
    try {
        const response = await fetch(
            `/student/kecermatan/${props.session.id}/check-status`,
        );
        if (response.ok) {
            const data = await response.json();

            if (isBlocked.value === true && data.is_blocked === false) {
                // Admin unblocked the student! Sync timer from server
                isBlocked.value = false;
                gracePeriod.value = true;

                if (data.remaining_seconds !== undefined) {
                    timeLeft.value = data.remaining_seconds; // Only sync on unblock
                }

                startTimer();
                setTimeout(() => {
                    gracePeriod.value = false;
                    checkFullscreen();
                }, 2000);
            } else {
                isBlocked.value = data.is_blocked;
                // NO TIMER SYNC - let client countdown run independently
            }
            violationCount.value = data.violation_count ?? violationCount.value;
            if (data.violations) {
                violations.value = data.violations;
            }
        }
    } catch (e) {
        console.warn("Failed to check status:", e);
    }
};

const autoSubmitExam = async () => {
    if (isSubmitting.value) return;

    isSubmitting.value = true;
    submittingMessage.value = "Menyimpan dan mengakhiri ujian...";

    try {
        await columnFinalizationChain;
        await waitForAnswerRequests([flushAnswers()], 6000);
        await exitFullscreenSafely();

        router.post(
            `/student/kecermatan/${props.session.id}/force-finish`,
            {},
            {
                preserveState: false,
                preserveScroll: false,
                onError: () => {
                    isSubmitting.value = false;
                    alert("Ujian gagal diselesaikan. Silakan coba kembali.");
                },
            },
        );
    } catch (error) {
        console.error("Auto submit gagal:", error);
        isSubmitting.value = false;
        alert("Ujian gagal diselesaikan. Periksa koneksi lalu coba kembali.");
    }
};

const formatViolationTime = (timeStr) => {
    if (!timeStr) return "-";
    if (/^\d{2}:\d{2}:\d{2}$/.test(timeStr)) return timeStr;
    const d = new Date(timeStr);
    if (isNaN(d.getTime())) return timeStr;
    return d.toLocaleTimeString("id-ID", {
        hour: "2-digit",
        minute: "2-digit",
        second: "2-digit",
    });
};

onMounted(() => {
    // Request fullscreen
    enterFullscreen();

    // Grace period saat page load
    gracePeriod.value = true;

    // Keyboard listener (jawaban 1-5) - safe to add immediately
    document.addEventListener("keydown", handleKeyPress);

    // Security listeners (blokir shortcut curang) - safe to add immediately
    document.addEventListener("keydown", handleSecurityKeyDown);
    document.addEventListener("contextmenu", handleContextMenu);

    // Initialize beep audio
    beepAudio.value = new Audio("/assets/sound/beep.mp3");

    // Start 60s column timer
    startTimer();

    // Start checkStatus polling every 3 seconds for admin unblock sync
    checkStatusInterval = setInterval(checkStatus, 3000);

    // Kirim jawaban antrean secara berkala (batch).
    answerFlushInterval = setInterval(() => void flushAnswers(), 1500);

    // Usaha terakhir: kirim sisa jawaban saat halaman ditutup/ditinggalkan.
    window.addEventListener("pagehide", flushOnPageHide);

    // ADD VIOLATION LISTENERS AFTER GRACE PERIOD (3 seconds)
    setTimeout(() => {
        gracePeriod.value = false;
        checkFullscreen();

        // Now safe to add violation detection listeners
        document.addEventListener("fullscreenchange", handleFullscreenChange);
        document.addEventListener(
            "webkitfullscreenchange",
            handleFullscreenChange,
        );
        document.addEventListener("visibilitychange", handleVisibilityChange);
        window.addEventListener("blur", handleBlur);
        window.addEventListener("blur", handleWindowBlur, true); // Capture phase
        window.addEventListener("focus", handleWindowFocus, true); // Capture phase

        // Check fullscreen status continuously (every 1 second)
        fullscreenCheckInterval = setInterval(checkFullscreen, 1000);

        console.log(
            "[Security] Violation detection activated after grace period",
        );
    }, 3000); // 3 seconds grace period
});

onUnmounted(() => {
    document.removeEventListener("keydown", handleKeyPress);
    document.removeEventListener("keydown", handleSecurityKeyDown);
    document.removeEventListener("contextmenu", handleContextMenu);
    document.removeEventListener("fullscreenchange", handleFullscreenChange);
    document.removeEventListener(
        "webkitfullscreenchange",
        handleFullscreenChange,
    );
    document.removeEventListener("visibilitychange", handleVisibilityChange);
    window.removeEventListener("blur", handleBlur);
    window.removeEventListener("blur", handleWindowBlur, true);
    window.removeEventListener("focus", handleWindowFocus, true);

    if (timerInterval) clearInterval(timerInterval);
    if (checkStatusInterval) clearInterval(checkStatusInterval);
    if (fullscreenCheckInterval) clearInterval(fullscreenCheckInterval);
    if (answerFlushInterval) clearInterval(answerFlushInterval);

    window.removeEventListener("pagehide", flushOnPageHide);
});
</script>

<style scoped>
/* Fullscreen */
.exam-fullscreen {
    width: 100vw;
    height: 100vh;
    display: flex;
    flex-direction: column;
    background: #ffffff;
    overflow: hidden;
}

/* Header */
.exam-header {
    flex-shrink: 0;
}

/* Timer */
.timer-display {
    font-size: 2.5rem;
    font-weight: 700;
    font-family: "Courier New", monospace;
    line-height: 1;
}

.timer-pulse {
    animation: pulse 1s ease-in-out infinite;
}

.timer-pulse-fast {
    animation: pulse 0.5s ease-in-out infinite;
}

@keyframes pulse {
    0%,
    100% {
        transform: scale(1);
    }
    50% {
        transform: scale(1.05);
    }
}

/* Progress */
.progress-wrapper {
    flex-shrink: 0;
}

/* Main Content */
.exam-content {
    flex: 1;
    overflow-y: auto;
}

/* Reference Values */
.reference-value {
    font-size: 3.8rem;
    font-weight: 700;
    color: #1f2937;
    width: 115px;
    height: 115px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 12px;
    border: 2px solid #dee2e6;
    background: #ffffff;
}

/* Question Values */
.question-value {
    font-size: 3.8rem;
    font-weight: 700;
    color: #1f2937;
    width: 110px;
    height: 110px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 14px;
    border: 2px solid #dee2e6;
    background: #ffffff;
}

.question-value.missing {
    background: #ffffff;
    border-color: #dee2e6;
    color: #6c757d;
    font-size: 3.2rem;
}

/* Answer Buttons */
.answer-btn {
    position: relative;
    font-size: 3.2rem;
    font-weight: 700;
    width: 110px;
    height: 110px;
    border: 2px solid #dee2e6;
    border-radius: 14px;
    background: #ffffff;
    color: #1f2937; /* CRITICAL: Explicit text color agar tidak hilang saat refresh */
    transition: all 0.2s ease;
    display: flex;
    align-items: center;
    justify-content: center;
}

.answer-btn:hover:not(:disabled) {
    transform: translateY(-4px);
    box-shadow: 0 8px 16px rgba(0, 0, 0, 0.1);
    border-color: #0d6efd;
    background: #e7f1ff;
}

.answer-btn.selected {
    background: #0d6efd;
    color: white;
    border-color: #0d6efd;
    transform: scale(1.05);
}

.answer-btn.answer-clicked {
    animation: answer-pop 0.2s ease-out;
}

@keyframes answer-pop {
    0% {
        transform: scale(1);
    }
    50% {
        transform: scale(0.88);
        background: #dbeafe;
    }
    100% {
        transform: scale(1.05);
    }
}

.answer-btn:active {
    transform: scale(0.92) !important;
    transition: transform 0.05s ease;
}

.answer-btn:disabled {
    opacity: 0.6;
    cursor: not-allowed;
}

.answer-number {
    position: absolute;
    top: 4px;
    right: 8px;
    font-size: 0.75rem;
    font-weight: 600;
    color: #adb5bd;
}

.answer-btn.selected .answer-number {
    color: rgba(255, 255, 255, 0.7);
}

.answer-letter {
    font-size: 3.2rem;
    font-weight: 700;
}

/* Footer */
.exam-footer {
    flex-shrink: 0;
}

/* Fullscreen Warning */
.fullscreen-warning {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.95);
    z-index: 10000;
    display: flex;
    align-items: center;
    justify-content: center;
}

.warning-content {
    text-align: center;
    color: white;
    padding: 2rem;
    max-width: 500px;
}

.warning-content h2 {
    color: white;
}

.warning-content .lead {
    color: rgba(255, 255, 255, 0.9);
}

/* Blocked Overlay */
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

/* Responsive */
@media (max-width: 768px) {
    .timer-display {
        font-size: 2rem;
    }

    .reference-value {
        font-size: 2.6rem;
        width: 80px;
        height: 80px;
    }

    .question-value {
        font-size: 2.2rem;
        width: 70px;
        height: 70px;
    }

    .answer-btn {
        font-size: 2rem;
        width: 70px;
        height: 70px;
        color: #1f2937; /* Explicit color for mobile */
    }

    .answer-letter {
        font-size: 2rem;
        color: inherit; /* Inherit from parent */
    }
}

@media (max-width: 576px) {
    .timer-display {
        font-size: 1.5rem;
    }

    .reference-value {
        font-size: 1.7rem;
        width: 60px;
        height: 60px;
        border-radius: 6px;
    }

    .question-value {
        font-size: 1.4rem;
        width: 50px;
        height: 50px;
        border-radius: 8px;
    }

    .question-value.missing {
        font-size: 1.3rem;
    }

    .answer-btn {
        font-size: 1.4rem;
        width: 50px;
        height: 50px;
        border-radius: 8px;
        color: #1f2937; /* Explicit color for small mobile */
    }

    .answer-number {
        font-size: 0.6rem;
        top: 2px;
        right: 4px;
    }

    .answer-letter {
        font-size: 1.4rem;
        color: inherit; /* Inherit from parent */
    }

    .alert {
        display: none;
    }
}

/* Loading Overlay - Simple & Clean */
.loading-overlay-simple {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(255, 255, 255, 0.95);
    backdrop-filter: blur(4px);
    z-index: 10000;
    display: flex;
    align-items: center;
    justify-content: center;
    animation: fadeIn 0.2s ease-out;
}

.loading-card-simple {
    text-align: center;
    padding: 3rem 2rem;
    background: white;
    border-radius: 16px;
    box-shadow: 0 10px 40px rgba(0, 0, 0, 0.1);
    border: 1px solid rgba(0, 0, 0, 0.05);
    min-width: 320px;
}

.spinner-simple {
    width: 64px;
    height: 64px;
    margin: 0 auto;
    border: 4px solid #e9ecef;
    border-top-color: #0d6efd;
    border-radius: 50%;
    animation: spin 0.8s linear infinite;
}

@keyframes spin {
    to {
        transform: rotate(360deg);
    }
}

@keyframes fadeIn {
    from {
        opacity: 0;
        transform: translateY(-10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}
</style>
