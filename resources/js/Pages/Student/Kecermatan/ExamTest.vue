<template>
    <Head>
        <title>Tes Kecermatan - Kolom {{ currentColumn }}</title>
    </Head>

    <main class="exam-page">
        <header class="exam-topbar">
            <div class="brand-block">
                <div class="brand-mark" aria-hidden="true">K</div>
                <div>
                    <p class="eyebrow">SIMULASI CAT POLRI</p>
                    <h1>Tes Kecermatan</h1>
                </div>
            </div>

            <div class="exam-meta">
                <div class="meta-item">
                    <span>Kolom</span>
                    <strong>{{ currentColumn }} / 10</strong>
                </div>
                <div class="meta-divider"></div>
                <div class="meta-item meta-item--timer">
                    <span>Sisa waktu</span>
                    <strong class="timer" :class="timerClass">
                        {{ formattedTime }}
                    </strong>
                </div>
            </div>
        </header>

        <section class="progress-panel" aria-label="Progress ujian">
            <div class="progress-copy">
                <span>Progress pengerjaan</span>
                <strong>{{ answeredCount }} dari 50 soal</strong>
            </div>
            <div class="progress-track">
                <div
                    class="progress-value"
                    :style="{ width: `${progressPercentage}%` }"
                ></div>
            </div>
        </section>

        <section class="exam-stage">
            <div class="workspace">
                <aside class="instruction-card">
                    <span class="section-badge">Petunjuk</span>
                    <h2>Temukan simbol yang hilang</h2>
                    <p>
                        Cocokkan urutan simbol pada soal dengan daftar
                        referensi, lalu pilih huruf jawaban yang tepat.
                    </p>

                    <div class="instruction-tip">
                        <span class="tip-icon">⌨</span>
                        <div>
                            <strong>Jawab lebih cepat</strong>
                            <p>Gunakan tombol keyboard 1 sampai 5.</p>
                        </div>
                    </div>

                    <div class="status-list">
                        <div>
                            <span>Soal saat ini</span>
                            <strong>{{ currentQuestion }}</strong>
                        </div>
                        <div>
                            <span>Terjawab</span>
                            <strong>{{ answeredCount }}</strong>
                        </div>
                        <div>
                            <span>Belum dijawab</span>
                            <strong>{{ remainingQuestions }}</strong>
                        </div>
                    </div>
                </aside>

                <div class="question-area">
                    <section class="reference-card">
                        <div class="card-heading">
                            <div>
                                <span class="section-badge section-badge--soft">
                                    Referensi kolom {{ currentColumn }}
                                </span>
                                <h2>Pasangan simbol</h2>
                            </div>
                            <span class="small-label">A–E</span>
                        </div>

                        <div class="reference-grid">
                            <div
                                v-for="(item, index) in referenceSequence"
                                :key="`${item}-${index}`"
                                class="reference-item"
                            >
                                <span class="reference-letter">
                                    {{ answerLetters[index] }}
                                </span>
                                <span class="symbol symbol--reference">
                                    {{ item }}
                                </span>
                            </div>
                        </div>
                    </section>

                    <section class="question-card">
                        <div class="question-number">
                            <span>Soal</span>
                            <strong>{{ currentQuestion }}</strong>
                        </div>

                        <div class="question-content">
                            <p>Pilih simbol yang melengkapi urutan berikut.</p>
                            <div class="sequence" aria-label="Urutan soal">
                                <span
                                    v-for="(item, index) in questionSequence"
                                    :key="`${item}-${index}`"
                                    class="symbol symbol--question"
                                >
                                    {{ item }}
                                </span>
                                <span
                                    class="symbol symbol--question symbol--missing"
                                    aria-label="Simbol yang hilang"
                                >
                                    ?
                                </span>
                            </div>
                        </div>
                    </section>

                    <section class="answer-section">
                        <div class="answer-heading">
                            <div>
                                <span class="section-badge section-badge--soft">
                                    Pilihan jawaban
                                </span>
                                <h2>Pilih satu jawaban</h2>
                            </div>
                            <span class="keyboard-copy">Tombol 1–5</span>
                        </div>

                        <div class="answer-grid">
                            <button
                                v-for="(letter, index) in answerLetters"
                                :key="letter"
                                type="button"
                                class="answer-button"
                                :class="{
                                    'answer-button--selected':
                                        selectedAnswer === letter,
                                }"
                                :aria-pressed="selectedAnswer === letter"
                                @click="selectAnswer(letter)"
                            >
                                <span class="key-number">{{ index + 1 }}</span>
                                <span class="answer-symbol">
                                    {{ referenceSequence[index] }}
                                </span>
                                <span class="answer-letter">{{ letter }}</span>
                            </button>
                        </div>
                    </section>
                </div>
            </div>
        </section>

        <footer class="exam-footer">
            <span class="autosave-dot"></span>
            Jawaban tersimpan otomatis dan soal berikutnya akan langsung tampil.
        </footer>

        <Transition name="modal-fade">
            <div v-if="showWarning" class="modal-overlay" role="alertdialog">
                <div class="warning-modal">
                    <div class="warning-icon">!</div>
                    <span class="section-badge section-badge--danger">
                        Peringatan waktu
                    </span>
                    <h2>Waktu hampir habis</h2>
                    <p>
                        Selesaikan jawaban terakhir sebelum berpindah ke kolom
                        berikutnya.
                    </p>
                    <button type="button" @click="showWarning = false">
                        Mengerti
                    </button>
                </div>
            </div>
        </Transition>
    </main>
</template>

<script setup>
import { computed, onMounted, onUnmounted, ref } from "vue";
import { Head } from "@inertiajs/vue3";

const TOTAL_QUESTIONS = 50;
const COLUMN_DURATION = 60;
const answerLetters = ["A", "B", "C", "D", "E"];

const currentColumn = ref(1);
const currentQuestion = ref(15);
const timeLeft = ref(45);
const answeredCount = ref(15);
const selectedAnswer = ref(null);
const showWarning = ref(false);
const isTransitioning = ref(false);

const referenceSequence = ref(["Δ", "Φ", "Ψ", "ψ", "L"]);
const questionSequence = ref(["ε", "Φ", "ψ"]);

const formattedTime = computed(() => {
    const minutes = Math.floor(timeLeft.value / 60);
    const seconds = timeLeft.value % 60;

    return `${String(minutes).padStart(2, "0")}:${String(seconds).padStart(2, "0")}`;
});

const timerClass = computed(() => ({
    "timer--danger": timeLeft.value <= 5,
    "timer--warning": timeLeft.value > 5 && timeLeft.value <= 10,
}));

const progressPercentage = computed(() =>
    Math.min(100, Math.round((answeredCount.value / TOTAL_QUESTIONS) * 100)),
);

const remainingQuestions = computed(() =>
    Math.max(0, TOTAL_QUESTIONS - answeredCount.value),
);

const selectAnswer = (letter) => {
    if (isTransitioning.value || !answerLetters.includes(letter)) return;

    isTransitioning.value = true;
    selectedAnswer.value = letter;

    window.setTimeout(() => {
        answeredCount.value = Math.min(
            answeredCount.value + 1,
            TOTAL_QUESTIONS,
        );
        currentQuestion.value = Math.min(
            currentQuestion.value + 1,
            TOTAL_QUESTIONS,
        );
        selectedAnswer.value = null;
        isTransitioning.value = false;
    }, 250);
};

const handleKeyPress = (event) => {
    const keyMap = {
        1: "A",
        2: "B",
        3: "C",
        4: "D",
        5: "E",
    };

    if (keyMap[event.key]) selectAnswer(keyMap[event.key]);
};

const moveToNextColumn = () => {
    timeLeft.value = COLUMN_DURATION;
    currentColumn.value = Math.min(currentColumn.value + 1, 10);
    currentQuestion.value = 1;
    answeredCount.value = 0;
    selectedAnswer.value = null;
    showWarning.value = false;
};

let timerInterval;
let warningTimeout;

onMounted(() => {
    document.addEventListener("keydown", handleKeyPress);

    timerInterval = window.setInterval(() => {
        if (timeLeft.value <= 0) {
            moveToNextColumn();
            return;
        }

        timeLeft.value -= 1;

        if (timeLeft.value === 5) {
            showWarning.value = true;
            window.clearTimeout(warningTimeout);
            warningTimeout = window.setTimeout(() => {
                showWarning.value = false;
            }, 2200);
        }
    }, 1000);
});

onUnmounted(() => {
    document.removeEventListener("keydown", handleKeyPress);
    window.clearInterval(timerInterval);
    window.clearTimeout(warningTimeout);
});
</script>

<style scoped>
:global(*) {
    box-sizing: border-box;
}

:global(body) {
    margin: 0;
    background: #f4f7fb;
}

button {
    font: inherit;
}

.exam-page {
    --navy: #0d213f;
    --blue: #1f5eff;
    --blue-soft: #edf3ff;
    --line: #dfe6f0;
    --muted: #64748b;
    --surface: #ffffff;
    min-height: 100vh;
    display: flex;
    flex-direction: column;
    color: var(--navy);
    background:
        radial-gradient(circle at top left, #e9f1ff 0, transparent 28rem),
        #f4f7fb;
    font-family:
        Inter,
        ui-sans-serif,
        system-ui,
        -apple-system,
        BlinkMacSystemFont,
        "Segoe UI",
        sans-serif;
}

.exam-topbar {
    min-height: 86px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 24px;
    padding: 18px clamp(20px, 4vw, 56px);
    background: rgba(255, 255, 255, 0.92);
    border-bottom: 1px solid var(--line);
    backdrop-filter: blur(14px);
}

.brand-block,
.exam-meta,
.meta-item,
.instruction-tip,
.progress-copy,
.card-heading,
.answer-heading {
    display: flex;
    align-items: center;
}

.brand-block {
    gap: 14px;
}

.brand-mark {
    width: 48px;
    height: 48px;
    display: grid;
    place-items: center;
    border-radius: 15px;
    color: #fff;
    background: linear-gradient(135deg, #173969, #1f5eff);
    font-weight: 900;
    font-size: 1.25rem;
    box-shadow: 0 10px 24px rgba(31, 94, 255, 0.22);
}

.eyebrow {
    margin: 0 0 2px;
    color: var(--blue);
    font-size: 0.68rem;
    font-weight: 800;
    letter-spacing: 0.14em;
}

h1,
h2,
p {
    margin-top: 0;
}

h1 {
    margin-bottom: 0;
    font-size: clamp(1.15rem, 2vw, 1.5rem);
    line-height: 1.2;
}

.exam-meta {
    gap: 22px;
}

.meta-item {
    flex-direction: column;
    align-items: flex-start;
    gap: 3px;
}

.meta-item span {
    color: var(--muted);
    font-size: 0.72rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.08em;
}

.meta-item strong {
    font-size: 1.08rem;
}

.meta-divider {
    width: 1px;
    height: 38px;
    background: var(--line);
}

.timer {
    min-width: 88px;
    color: #16805a;
    font-family: "SFMono-Regular", Consolas, monospace;
    font-size: 1.55rem !important;
    letter-spacing: 0.03em;
}

.timer--warning {
    color: #d97706;
}

.timer--danger {
    color: #dc2626;
    animation: timer-pulse 0.7s ease-in-out infinite;
}

.progress-panel {
    padding: 12px clamp(20px, 4vw, 56px) 14px;
    background: #fff;
    border-bottom: 1px solid var(--line);
}

.progress-copy {
    justify-content: space-between;
    margin-bottom: 8px;
    color: var(--muted);
    font-size: 0.78rem;
}

.progress-copy strong {
    color: var(--navy);
}

.progress-track {
    height: 7px;
    overflow: hidden;
    border-radius: 999px;
    background: #e8edf5;
}

.progress-value {
    height: 100%;
    border-radius: inherit;
    background: linear-gradient(90deg, #1f5eff, #2f80ed);
    transition: width 0.3s ease;
}

.exam-stage {
    flex: 1;
    padding: clamp(20px, 3vw, 42px) clamp(16px, 4vw, 56px);
}

.workspace {
    width: min(1180px, 100%);
    min-height: 610px;
    margin: 0 auto;
    display: grid;
    grid-template-columns: minmax(230px, 0.72fr) minmax(0, 2fr);
    gap: 24px;
}

.instruction-card,
.reference-card,
.question-card,
.answer-section {
    background: rgba(255, 255, 255, 0.96);
    border: 1px solid rgba(214, 224, 238, 0.9);
    box-shadow: 0 18px 44px rgba(13, 33, 63, 0.07);
}

.instruction-card {
    align-self: stretch;
    padding: 28px;
    border-radius: 24px;
}

.section-badge {
    display: inline-flex;
    width: fit-content;
    padding: 7px 11px;
    border-radius: 999px;
    color: #fff;
    background: var(--blue);
    font-size: 0.7rem;
    font-weight: 800;
    letter-spacing: 0.06em;
    text-transform: uppercase;
}

.section-badge--soft {
    color: var(--blue);
    background: var(--blue-soft);
}

.section-badge--danger {
    color: #c62828;
    background: #ffebee;
}

.instruction-card h2,
.card-heading h2,
.answer-heading h2 {
    margin: 14px 0 8px;
    font-size: 1.18rem;
}

.instruction-card > p,
.question-content > p,
.warning-modal p {
    color: var(--muted);
    line-height: 1.7;
}

.instruction-tip {
    gap: 13px;
    margin: 26px 0;
    padding: 15px;
    border-radius: 16px;
    background: #f6f9fe;
    border: 1px solid #e4ebf5;
}

.tip-icon {
    width: 40px;
    height: 40px;
    flex: 0 0 auto;
    display: grid;
    place-items: center;
    border-radius: 12px;
    color: var(--blue);
    background: #fff;
    border: 1px solid #dce6f4;
    font-size: 1.15rem;
}

.instruction-tip strong {
    display: block;
    margin-bottom: 3px;
    font-size: 0.85rem;
}

.instruction-tip p {
    margin: 0;
    color: var(--muted);
    font-size: 0.75rem;
}

.status-list {
    display: grid;
    gap: 12px;
}

.status-list div {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 13px 0;
    border-bottom: 1px dashed #dde5ef;
}

.status-list div:last-child {
    border-bottom: 0;
}

.status-list span {
    color: var(--muted);
    font-size: 0.8rem;
}

.status-list strong {
    font-size: 0.95rem;
}

.question-area {
    display: grid;
    gap: 20px;
}

.reference-card,
.question-card,
.answer-section {
    border-radius: 24px;
}

.reference-card,
.answer-section {
    padding: 24px;
}

.card-heading,
.answer-heading {
    justify-content: space-between;
    gap: 18px;
    margin-bottom: 20px;
}

.card-heading h2,
.answer-heading h2 {
    margin-bottom: 0;
}

.small-label,
.keyboard-copy {
    color: var(--muted);
    font-size: 0.75rem;
    font-weight: 700;
}

.reference-grid,
.answer-grid {
    display: grid;
    grid-template-columns: repeat(5, minmax(0, 1fr));
    gap: 12px;
}

.reference-item {
    position: relative;
    min-height: 104px;
    display: grid;
    place-items: center;
    border-radius: 18px;
    background: #f8faff;
    border: 1px solid #dfe7f3;
}

.reference-letter {
    position: absolute;
    top: 9px;
    left: 10px;
    width: 25px;
    height: 25px;
    display: grid;
    place-items: center;
    border-radius: 8px;
    color: var(--blue);
    background: #eaf1ff;
    font-size: 0.72rem;
    font-weight: 900;
}

.symbol {
    font-family: "Times New Roman", Georgia, serif;
    font-weight: 700;
}

.symbol--reference {
    font-size: clamp(2rem, 4vw, 2.7rem);
}

.question-card {
    min-height: 210px;
    display: grid;
    grid-template-columns: 90px minmax(0, 1fr);
    overflow: hidden;
}

.question-number {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 3px;
    color: #fff;
    background: linear-gradient(160deg, #173969, #1f5eff);
}

.question-number span {
    font-size: 0.72rem;
    font-weight: 700;
    letter-spacing: 0.08em;
    text-transform: uppercase;
}

.question-number strong {
    font-size: 2rem;
}

.question-content {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 28px;
}

.question-content > p {
    margin-bottom: 20px;
    font-size: 0.84rem;
}

.sequence {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: clamp(9px, 2vw, 16px);
}

.symbol--question {
    width: clamp(62px, 8vw, 82px);
    aspect-ratio: 1;
    display: grid;
    place-items: center;
    border-radius: 18px;
    border: 2px solid #dbe4f0;
    background: #fff;
    font-size: clamp(2rem, 4vw, 3rem);
    box-shadow: 0 8px 20px rgba(13, 33, 63, 0.06);
}

.symbol--missing {
    color: var(--blue);
    border-color: #8bb0ff;
    background: #eef4ff;
}

.answer-button {
    position: relative;
    min-height: 112px;
    display: grid;
    place-items: center;
    border: 1.5px solid #dce5f1;
    border-radius: 18px;
    color: var(--navy);
    background: #fff;
    cursor: pointer;
    transition:
        transform 0.18s ease,
        border-color 0.18s ease,
        box-shadow 0.18s ease,
        background 0.18s ease;
}

.answer-button:hover,
.answer-button:focus-visible {
    transform: translateY(-4px);
    border-color: #7da7ff;
    box-shadow: 0 12px 28px rgba(31, 94, 255, 0.14);
    outline: none;
}

.answer-button--selected {
    color: #fff;
    border-color: var(--blue);
    background: linear-gradient(145deg, #1949bb, #1f5eff);
    transform: translateY(-3px) scale(1.02);
}

.key-number,
.answer-letter {
    position: absolute;
    display: grid;
    place-items: center;
    font-weight: 800;
}

.key-number {
    top: 9px;
    right: 9px;
    width: 25px;
    height: 25px;
    border-radius: 8px;
    color: #64748b;
    background: #f0f4f9;
    font-size: 0.7rem;
}

.answer-letter {
    bottom: 10px;
    left: 12px;
    color: var(--blue);
    font-size: 0.75rem;
}

.answer-symbol {
    font-family: "Times New Roman", Georgia, serif;
    font-size: 2.45rem;
    font-weight: 700;
}

.answer-button--selected .key-number,
.answer-button--selected .answer-letter {
    color: #fff;
    background: rgba(255, 255, 255, 0.14);
}

.answer-button--selected .answer-letter {
    background: transparent;
}

.exam-footer {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 9px;
    padding: 13px 20px;
    color: var(--muted);
    background: rgba(255, 255, 255, 0.92);
    border-top: 1px solid var(--line);
    font-size: 0.78rem;
    text-align: center;
}

.autosave-dot {
    width: 8px;
    height: 8px;
    border-radius: 50%;
    background: #20a76b;
    box-shadow: 0 0 0 4px rgba(32, 167, 107, 0.12);
}

.modal-overlay {
    position: fixed;
    inset: 0;
    z-index: 1000;
    display: grid;
    place-items: center;
    padding: 20px;
    background: rgba(7, 20, 39, 0.58);
    backdrop-filter: blur(5px);
}

.warning-modal {
    width: min(390px, 100%);
    padding: 30px;
    border-radius: 24px;
    background: #fff;
    text-align: center;
    box-shadow: 0 24px 70px rgba(0, 0, 0, 0.22);
}

.warning-icon {
    width: 64px;
    height: 64px;
    display: grid;
    place-items: center;
    margin: 0 auto 18px;
    border-radius: 50%;
    color: #fff;
    background: #e53935;
    font-size: 1.75rem;
    font-weight: 900;
}

.warning-modal h2 {
    margin: 16px 0 8px;
}

.warning-modal button {
    width: 100%;
    margin-top: 8px;
    padding: 12px 18px;
    border: 0;
    border-radius: 12px;
    color: #fff;
    background: #d32f2f;
    font-weight: 800;
    cursor: pointer;
}

.modal-fade-enter-active,
.modal-fade-leave-active {
    transition: opacity 0.2s ease;
}

.modal-fade-enter-from,
.modal-fade-leave-to {
    opacity: 0;
}

@keyframes timer-pulse {
    50% {
        opacity: 0.5;
        transform: scale(1.03);
    }
}

@media (max-width: 900px) {
    .workspace {
        grid-template-columns: 1fr;
    }

    .instruction-card {
        display: none;
    }
}

@media (max-width: 640px) {
    .exam-topbar {
        align-items: flex-start;
        padding: 14px 16px;
    }

    .brand-mark {
        width: 42px;
        height: 42px;
        border-radius: 13px;
    }

    .eyebrow {
        display: none;
    }

    .exam-meta {
        gap: 12px;
    }

    .meta-divider {
        display: none;
    }

    .meta-item:first-child {
        display: none;
    }

    .timer {
        min-width: auto;
        font-size: 1.3rem !important;
    }

    .progress-panel {
        padding-inline: 16px;
    }

    .exam-stage {
        padding: 16px 12px 22px;
    }

    .reference-card,
    .answer-section {
        padding: 18px 14px;
        border-radius: 19px;
    }

    .card-heading,
    .answer-heading {
        margin-bottom: 15px;
    }

    .small-label,
    .keyboard-copy {
        display: none;
    }

    .reference-grid,
    .answer-grid {
        gap: 7px;
    }

    .reference-item {
        min-height: 76px;
        border-radius: 13px;
    }

    .reference-letter,
    .key-number {
        width: 21px;
        height: 21px;
        font-size: 0.62rem;
    }

    .symbol--reference {
        font-size: 1.75rem;
    }

    .question-card {
        min-height: 178px;
        grid-template-columns: 62px minmax(0, 1fr);
        border-radius: 19px;
    }

    .question-number strong {
        font-size: 1.55rem;
    }

    .question-content {
        padding: 18px 12px;
    }

    .question-content > p {
        margin-bottom: 13px;
        font-size: 0.76rem;
        text-align: center;
    }

    .sequence {
        gap: 7px;
    }

    .symbol--question {
        width: clamp(50px, 15vw, 64px);
        border-radius: 14px;
        font-size: 2rem;
    }

    .answer-button {
        min-height: 82px;
        border-radius: 14px;
    }

    .answer-symbol {
        font-size: 1.85rem;
    }

    .answer-letter {
        bottom: 6px;
        left: 8px;
        font-size: 0.64rem;
    }

    .exam-footer {
        font-size: 0.7rem;
    }
}

@media (max-width: 390px) {
    .reference-grid,
    .answer-grid {
        gap: 5px;
    }

    .reference-card,
    .answer-section {
        padding-inline: 10px;
    }

    .reference-item {
        min-height: 68px;
    }

    .answer-button {
        min-height: 74px;
    }

    .answer-symbol,
    .symbol--reference {
        font-size: 1.6rem;
    }
}
</style>
