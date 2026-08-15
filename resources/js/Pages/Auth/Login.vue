<template>
    <Head>
        <title>Login - Buweuk Sipit Academy</title>
    </Head>
    <section class="vh-lg-100 mt-lg-0 bg-soft d-flex align-items-center mt-5">
        <div class="container">
            <div
                class="row justify-content-center form-bg-image"
                style="background: url(&quot;/assets/images/signin.svg&quot;)"
            >
                <div
                    class="col-12 d-flex align-items-center justify-content-center"
                >
                    <div
                        class="border-light p-lg-5 fmxw-500 w-100 rounded border-0 bg-white p-4 shadow"
                    >
                        <div class="mb-4 text-center">
                            <h3 class="fw-bold">BUWEUK SIPIT ACADEMY</h3>
                            <p class="text-muted">Sistem Ujian Digital</p>
                        </div>

                        <ul class="nav nav-tabs nav-fill mb-4" role="tablist">
                            <li class="nav-item" role="presentation">
                                <button
                                    class="nav-link"
                                    :class="{ active: activeTab === 'admin' }"
                                    @click="activeTab = 'admin'"
                                    type="button"
                                    role="tab"
                                >
                                    <i class="fa fa-user-cog me-2"></i> Admin
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button
                                    class="nav-link"
                                    :class="{ active: activeTab === 'student' }"
                                    @click="activeTab = 'student'"
                                    type="button"
                                    role="tab"
                                >
                                    <i class="fa fa-user-graduate me-2"></i>
                                    Siswa
                                </button>
                            </li>
                        </ul>

                        <div
                            v-if="$page.props.session.error"
                            class="alert alert-danger mt-2"
                        >
                            {{ $page.props.session.error }}
                        </div>

                        <div
                            v-if="$page.props.session.success"
                            class="alert alert-success mt-2"
                        >
                            {{ $page.props.session.success }}
                        </div>

                        <!-- Admin Login -->
                        <div v-show="activeTab === 'admin'">
                            <form @submit.prevent="submitAdmin">
                                <div class="form-group mb-4">
                                    <label for="admin-email">Email</label>
                                    <div class="input-group">
                                        <span
                                            class="input-group-text"
                                            id="basic-addon1"
                                        >
                                            <i class="fa fa-envelope"></i>
                                        </span>
                                        <input
                                            id="admin-email"
                                            type="email"
                                            class="form-control"
                                            v-model="adminForm.email"
                                            placeholder="admin@email.com"
                                        />
                                    </div>
                                    <div
                                        v-if="errors.email"
                                        class="alert alert-danger mt-2"
                                    >
                                        {{ errors.email }}
                                    </div>
                                </div>

                                <div class="form-group">
                                    <div class="form-group mb-4">
                                        <label for="admin-password"
                                            >Password</label
                                        >
                                        <div class="input-group">
                                            <span
                                                class="input-group-text"
                                                id="basic-addon2"
                                            >
                                                <i class="fa fa-lock"></i>
                                            </span>
                                            <input
                                                id="admin-password"
                                                :type="
                                                    showAdminPassword
                                                        ? 'text'
                                                        : 'password'
                                                "
                                                placeholder="Password"
                                                class="form-control"
                                                v-model="adminForm.password"
                                            />
                                            <button
                                                type="button"
                                                class="btn btn-outline-secondary"
                                                tabindex="-1"
                                                @click="
                                                    showAdminPassword =
                                                        !showAdminPassword
                                                "
                                                :aria-label="
                                                    showAdminPassword
                                                        ? 'Sembunyikan password'
                                                        : 'Tampilkan password'
                                                "
                                            >
                                                <i
                                                    :class="
                                                        showAdminPassword
                                                            ? 'fa fa-eye-slash'
                                                            : 'fa fa-eye'
                                                    "
                                                ></i>
                                            </button>
                                        </div>
                                        <div
                                            v-if="errors.password"
                                            class="alert alert-danger mt-2"
                                        >
                                            {{ errors.password }}
                                        </div>
                                    </div>
                                </div>

                                <div
                                    class="d-flex justify-content-between align-items-top mb-4"
                                >
                                    <div class="form-check">
                                        <input
                                            class="form-check-input"
                                            type="checkbox"
                                            id="remember-admin"
                                            v-model="adminForm.remember"
                                        />
                                        <label
                                            class="form-check-label mb-0"
                                            for="remember-admin"
                                        >
                                            Remember me
                                        </label>
                                    </div>
                                </div>

                                <div class="d-grid">
                                    <button
                                        type="submit"
                                        class="btn btn-gray-800"
                                        :disabled="adminLoading"
                                    >
                                        <span v-if="adminLoading">
                                            <span
                                                class="spinner-border spinner-border-sm me-2"
                                            ></span>
                                            Loading...
                                        </span>
                                        <span v-else>
                                            <i
                                                class="fa fa-sign-in-alt me-2"
                                            ></i>
                                            LOGIN ADMIN
                                        </span>
                                    </button>
                                </div>
                            </form>
                        </div>

                        <!-- Student Login -->
                        <div v-show="activeTab === 'student'">
                            <!-- Pesan info alur OTP siswa (mis. OTP masih valid) -->
                            <div
                                v-if="$page.props.session.info"
                                class="alert alert-info mt-2"
                            >
                                <i class="fa fa-info-circle me-1"></i>
                                {{ $page.props.session.info }}
                            </div>

                            <div v-if="!showOtpForm">
                                <form @submit.prevent="sendOtp">
                                    <div class="form-group mb-4">
                                        <label for="student-email">Email</label>
                                        <div class="input-group">
                                            <span
                                                class="input-group-text"
                                                id="basic-addon3"
                                            >
                                                <i class="fa fa-envelope"></i>
                                            </span>
                                            <input
                                                id="student-email"
                                                type="email"
                                                class="form-control"
                                                :class="{
                                                    'is-invalid': emailError,
                                                }"
                                                v-model="studentForm.email"
                                                placeholder="siswa@email.com"
                                                autocomplete="username"
                                                @input="validateEmail"
                                                @change="syncStudentCredentials"
                                                @blur="syncStudentCredentials"
                                                ref="emailInput"
                                            />
                                        </div>
                                        <div
                                            v-if="emailError"
                                            class="text-danger small mt-1"
                                        >
                                            {{ emailError }}
                                        </div>
                                        <div
                                            v-if="errors.email"
                                            class="alert alert-danger mt-2"
                                        >
                                            {{ errors.email }}
                                        </div>
                                    </div>

                                    <div class="form-group mb-4">
                                        <label for="student-password"
                                            >Password</label
                                        >
                                        <div class="input-group">
                                            <span
                                                class="input-group-text"
                                                id="basic-addon4"
                                            >
                                                <i class="fa fa-lock"></i>
                                            </span>
                                            <input
                                                id="student-password"
                                                :type="
                                                    showStudentPassword
                                                        ? 'text'
                                                        : 'password'
                                                "
                                                placeholder="Password"
                                                class="form-control"
                                                :class="{
                                                    'is-invalid': passwordError,
                                                }"
                                                v-model="studentForm.password"
                                                autocomplete="current-password"
                                                @input="validatePassword"
                                                @change="syncStudentCredentials"
                                                @blur="syncStudentCredentials"
                                                ref="passwordInput"
                                            />
                                            <button
                                                type="button"
                                                class="btn btn-outline-secondary"
                                                tabindex="-1"
                                                @click="
                                                    showStudentPassword =
                                                        !showStudentPassword
                                                "
                                                :aria-label="
                                                    showStudentPassword
                                                        ? 'Sembunyikan password'
                                                        : 'Tampilkan password'
                                                "
                                            >
                                                <i
                                                    :class="
                                                        showStudentPassword
                                                            ? 'fa fa-eye-slash'
                                                            : 'fa fa-eye'
                                                    "
                                                ></i>
                                            </button>
                                        </div>
                                        <div
                                            v-if="passwordError"
                                            class="text-danger small mt-1"
                                        >
                                            {{ passwordError }}
                                        </div>
                                        <div
                                            v-if="errors.password"
                                            class="alert alert-danger mt-2"
                                        >
                                            {{ errors.password }}
                                        </div>
                                    </div>

                                    <div
                                        class="d-flex justify-content-between align-items-top mb-4"
                                    >
                                        <div class="form-check">
                                            <input
                                                class="form-check-input"
                                                type="checkbox"
                                                id="remember-student"
                                                v-model="studentForm.remember"
                                            />
                                            <label
                                                class="form-check-label mb-0"
                                                for="remember-student"
                                            >
                                                Remember me
                                            </label>
                                        </div>
                                    </div>

                                    <div class="d-grid">
                                        <button
                                            type="submit"
                                            class="btn btn-gray-800"
                                            :disabled="otpLoading"
                                        >
                                            <span v-if="otpLoading">
                                                <span
                                                    class="spinner-border spinner-border-sm me-2"
                                                ></span>
                                                Loading...
                                            </span>
                                            <span v-else>
                                                <i
                                                    class="fa fa-sign-in-alt me-2"
                                                ></i>
                                                LOGIN SISWA
                                            </span>
                                        </button>
                                    </div>
                                </form>
                            </div>

                            <div v-else>
                                <div class="alert alert-info mb-4">
                                    <i class="fa fa-info-circle me-2"></i>
                                    Kode OTP telah dikirim ke
                                    <strong>{{ studentForm.email }}</strong>
                                </div>

                                <!-- Error tingkat form (mis. rate limit login) —
                                     ditampilkan juga di langkah OTP karena di sinilah
                                     siswa berada saat submit login ditolak. -->
                                <div
                                    v-if="errors.email"
                                    class="alert alert-danger mb-4"
                                >
                                    <i class="fa fa-exclamation-circle me-1"></i>
                                    {{ errors.email }}
                                </div>

                                <form @submit.prevent="submitStudent">
                                    <div class="form-group mb-4">
                                        <label for="student-otp"
                                            >Kode OTP (6 digit)</label
                                        >
                                        <div v-if="!studentLoading">
                                            <div
                                                class="d-flex justify-content-center gap-2 mb-3 otp-container"
                                            >
                                                <input
                                                    v-for="(digit, index) in 6"
                                                    :key="index"
                                                    type="text"
                                                    inputmode="numeric"
                                                    pattern="[0-9]*"
                                                    autocomplete="one-time-code"
                                                    class="form-control fw-bold text-center otp-box"
                                                    :class="{
                                                        'is-invalid': otpError,
                                                    }"
                                                    v-model="otpDigits[index]"
                                                    @input="
                                                        onOtpDigitInput(
                                                            $event,
                                                            index,
                                                        )
                                                    "
                                                    @keydown="
                                                        onOtpDigitKeydown(
                                                            $event,
                                                            index,
                                                        )
                                                    "
                                                    @paste="onOtpPaste($event)"
                                                    :ref="
                                                        (el) =>
                                                            (otpRefs[index] =
                                                                el)
                                                    "
                                                />
                                            </div>
                                            <div
                                                v-if="otpError"
                                                class="text-danger text-center small mt-1"
                                            >
                                                {{ otpError }}
                                            </div>
                                        </div>
                                        <div v-else class="text-center py-3">
                                            <div
                                                class="spinner-border text-primary"
                                                role="status"
                                            >
                                                <span class="visually-hidden"
                                                    >Loading...</span
                                                >
                                            </div>
                                            <p class="mt-2 text-muted small">
                                                Memverifikasi OTP...
                                            </p>
                                        </div>
                                        <div
                                            v-if="errors.otp"
                                            class="alert alert-danger mt-2"
                                        >
                                            {{ errors.otp }}
                                        </div>
                                        <small class="text-muted">
                                            <i class="fa fa-clock me-1"></i>
                                            Kode OTP berlaku 5 menit
                                        </small>
                                    </div>

                                    <div
                                        class="d-flex justify-content-between align-items-top mb-4"
                                    >
                                        <button
                                            type="button"
                                            class="btn btn-link btn-sm text-muted p-0"
                                            @click="resendOtp"
                                            :disabled="otpCooldown > 0"
                                        >
                                            <i
                                                class="fa fa-paper-plane me-1"
                                            ></i>
                                            <span v-if="otpCooldown > 0"
                                                >Kirim Ulang ({{
                                                    otpCooldown
                                                }}s)</span
                                            >
                                            <span v-else>Kirim Ulang OTP</span>
                                        </button>
                                    </div>

                                    <div class="d-grid">
                                        <button
                                            type="submit"
                                            class="btn btn-gray-800"
                                            :disabled="studentLoading"
                                        >
                                            <span v-if="studentLoading">
                                                Memverifikasi OTP...
                                            </span>
                                            <span v-else>MASUK DASHBOARD</span>
                                        </button>
                                    </div>
                                </form>

                                <div class="mt-3 text-center">
                                    <button
                                        type="button"
                                        class="btn btn-link text-muted"
                                        @click="backToEmailForm"
                                    >
                                        <i class="fa fa-chevron-left me-1"></i>
                                        Ganti Email
                                    </button>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>
            </div>
        </div>
    </section>
</template>

<script>
import { Head, router } from "@inertiajs/vue3";
import {
    reactive,
    ref,
    computed,
    nextTick,
    onMounted,
    onBeforeUnmount,
} from "vue";

// Draft kredensial alur OTP disimpan di sessionStorage (hanya hidup selama
// tab terbuka). Ini membuat langkah form OTP tahan terhadap re-mount/reload
// komponen oleh Inertia — state lokal Vue (studentForm, showOtpForm) tidak
// selalu dipertahankan setelah server membalas redirect, sehingga tanpa draft
// ini email/password bisa hilang dan login gagal dengan "Email wajib diisi".
const OTP_DRAFT_KEY = "otp_login_draft";

const saveOtpDraft = (email, password) => {
    try {
        sessionStorage.setItem(
            OTP_DRAFT_KEY,
            JSON.stringify({ email, password }),
        );
    } catch {
        // Storage tidak tersedia (mis. mode privat) — abaikan.
    }
};

const loadOtpDraft = () => {
    try {
        const raw = sessionStorage.getItem(OTP_DRAFT_KEY);
        return raw ? JSON.parse(raw) : null;
    } catch {
        return null;
    }
};

const clearOtpDraft = () => {
    try {
        sessionStorage.removeItem(OTP_DRAFT_KEY);
    } catch {
        // abaikan
    }
};

export default {
    components: {
        Head,
    },
    props: {
        errors: Object,
    },
    setup() {
        const activeTab = ref("student");
        const showOtpForm = ref(false);
        const showAdminPassword = ref(false);
        const showStudentPassword = ref(false);
        const emailInput = ref(null);
        const passwordInput = ref(null);
        const otpRefs = ref([]);
        const otpDigits = ref(["", "", "", "", "", ""]);

        const adminForm = reactive({
            email: "",
            password: "",
            remember: false,
        });
        const adminLoading = ref(false);

        const studentForm = reactive({
            email: "",
            password: "",
            otp: "",
            remember: false,
        });
        const studentLoading = ref(false);
        const otpLoading = ref(false);
        const otpCooldown = ref(0);
        let cooldownInterval = null;

        // Pulihkan draft login OTP bila komponen ter-mount ulang (mis. setelah
        // navigasi/redirect Inertia) atau halaman di-refresh saat OTP berjalan.
        const draft = loadOtpDraft();
        if (draft?.email) {
            studentForm.email = draft.email;
            studentForm.password = draft.password ?? "";
            showOtpForm.value = true;
        }

        const emailError = ref("");
        const passwordError = ref("");
        const otpError = ref("");

        // Browser autofill kadang mengisi tampilan input tanpa memicu event input Vue.
        // Fungsi ini membaca nilai DOM dan menyinkronkannya ke state reactive.
        const syncStudentCredentials = () => {
            // Hanya sinkronkan saat form email/password benar-benar tampil.
            // Ketika form OTP sedang ditampilkan, input tersebut sudah tidak ada
            // di DOM — membaca null akan MENGHAPUS kredensial dari state.
            if (!emailInput.value || !passwordInput.value) return;

            const emailValue = emailInput.value?.value ?? "";
            const passwordValue = passwordInput.value?.value ?? "";

            if (emailValue !== studentForm.email) {
                studentForm.email = emailValue;
            }

            if (passwordValue !== studentForm.password) {
                studentForm.password = passwordValue;
            }

            // Bersihkan pesan error ketika nilai hasil autofill sudah valid.
            if (studentForm.email) validateEmail();
            if (studentForm.password) validatePassword();
        };

        const isFormValid = computed(() => {
            return (
                !emailError.value &&
                !passwordError.value &&
                studentForm.email &&
                studentForm.password
            );
        });

        const validateEmail = () => {
            const email = studentForm.email.trim();
            if (!email) {
                emailError.value = "Email wajib diisi";
                return false;
            }
            if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
                emailError.value = "Format email tidak valid";
                return false;
            }
            emailError.value = "";
            return true;
        };

        const validatePassword = () => {
            const password = studentForm.password;
            if (!password) {
                passwordError.value = "Password wajib diisi";
                return false;
            }
            if (password.length < 6) {
                passwordError.value = "Password minimal 6 karakter";
                return false;
            }
            passwordError.value = "";
            return true;
        };

        const startCooldown = () => {
            otpCooldown.value = 60;
            if (cooldownInterval) clearInterval(cooldownInterval);
            cooldownInterval = setInterval(() => {
                otpCooldown.value--;
                if (otpCooldown.value <= 0) {
                    clearInterval(cooldownInterval);
                    cooldownInterval = null;
                }
            }, 1000);
        };

        onMounted(() => {
            // Tunggu browser/password manager menyelesaikan autofill.
            nextTick(() => {
                syncStudentCredentials();
                setTimeout(syncStudentCredentials, 100);
                setTimeout(syncStudentCredentials, 500);
            });
        });

        onBeforeUnmount(() => {
            if (cooldownInterval) {
                clearInterval(cooldownInterval);
                cooldownInterval = null;
            }
        });

        const sendOtp = () => {
            // Pastikan nilai autofill browser masuk ke state sebelum divalidasi.
            syncStudentCredentials();

            if (!validateEmail()) {
                emailInput.value?.focus();
                return;
            }
            if (!validatePassword()) {
                passwordInput.value?.focus();
                return;
            }

            otpLoading.value = true;

            // Simpan draft SEBELUM request dikirim: jika komponen ter-mount ulang
            // saat respons diproses (state lokal hilang), nilai email/password dan
            // langkah OTP tetap bisa dipulihkan dari sessionStorage.
            saveOtpDraft(studentForm.email, studentForm.password);

            router.post(
                "/student/send-otp",
                { email: studentForm.email, password: studentForm.password },
                {
                    preserveScroll: true,
                    preserveState: true,
                    onSuccess: () => {
                        otpLoading.value = false;
                        showOtpForm.value = true;
                        setTimeout(() => {
                            otpRefs.value[0]?.focus();
                        }, 100);
                        startCooldown();
                    },
                    onError: () => {
                        otpLoading.value = false;
                        // OTP tidak terkirim — buang draft agar form OTP tidak
                        // muncul tanpa kode OTP saat halaman dimuat ulang.
                        clearOtpDraft();
                    },
                },
            );
        };

        const resendOtp = () => {
            if (otpCooldown.value > 0) return;

            studentForm.otp = "";
            otpError.value = "";

            router.post(
                "/student/send-otp",
                { email: studentForm.email, password: studentForm.password },
                {
                    preserveScroll: true,
                    // Sama seperti sendOtp: cegah komponen di-mount ulang yang
                    // akan mengembalikan pengguna ke langkah email/password.
                    preserveState: true,
                    onSuccess: () => {
                        startCooldown();
                        nextTick(() => {
                            otpRefs.value[0]?.focus();
                        });
                    },
                },
            );
        };

        const fillOtpDigits = (rawValue, startIndex = 0) => {
            const digits = String(rawValue || "")
                .replace(/\D/g, "")
                .slice(0, 6);

            if (!digits) return false;

            const nextDigits = [...otpDigits.value];
            const availableLength = Math.max(0, 6 - startIndex);
            const digitsToApply = digits.slice(0, availableLength);

            for (let i = 0; i < digitsToApply.length; i++) {
                nextDigits[startIndex + i] = digitsToApply[i];
            }

            otpDigits.value = nextDigits;

            const focusIndex = Math.min(startIndex + digitsToApply.length, 5);
            nextTick(() => {
                otpRefs.value[focusIndex]?.focus();
                checkAndSubmitOtp();
            });

            return true;
        };

        const onOtpDigitInput = (e, index) => {
            const val = e.target.value.replace(/\D/g, "");

            if (val.length > 1) {
                e.target.value = val[0] || "";
                fillOtpDigits(val, index);
                return;
            }

            otpDigits.value[index] = val;

            if (val) {
                if (index < 5) {
                    otpRefs.value[index + 1]?.focus();
                } else {
                    checkAndSubmitOtp();
                }
            }
        };

        const onOtpDigitKeydown = (e, index) => {
            if (e.key === "Backspace" && !otpDigits.value[index]) {
                if (index > 0) {
                    otpRefs.value[index - 1]?.focus();
                }
            }
        };

        const onOtpPaste = (e) => {
            e.preventDefault();
            const pasteData = e.clipboardData?.getData("text") || "";
            const activeIndex = otpRefs.value.findIndex(
                (input) => input === e.target,
            );
            fillOtpDigits(pasteData, activeIndex >= 0 ? activeIndex : 0);
        };

        const checkAndSubmitOtp = () => {
            const fullOtp = otpDigits.value.join("");
            studentForm.otp = fullOtp;
            if (fullOtp.length === 6) {
                otpError.value = "";
            } else {
                otpError.value = "";
            }
        };

        const submitAdmin = () => {
            adminLoading.value = true;
            router.post("/admin/login", adminForm, {
                // Jaga nilai email/password tetap terisi saat server membalas
                // error (redirect balik ke halaman login) agar tidak terhapus.
                preserveState: true,
                onFinish: () => {
                    adminLoading.value = false;
                },
                onError: () => {
                    adminLoading.value = false;
                },
            });
        };

        const submitStudent = () => {
            studentForm.otp = otpDigits.value.join("");

            if (studentForm.otp.length !== 6) {
                otpError.value = "Kode OTP harus 6 digit";
                otpRefs.value[0]?.focus();
                return;
            }

            if (studentLoading.value) return;

            studentLoading.value = true;
            router.post("/student/login", studentForm, {
                // Jaga state komponen (email/password/OTP/langkah) tetap utuh
                // saat server membalas error, mis. karena rate limit.
                preserveState: true,
                onSuccess: () => {
                    studentLoading.value = false;
                    // Login berhasil — hapus draft agar tidak terbawa saat
                    // logout lalu login lagi dengan akun yang berbeda.
                    clearOtpDraft();
                },
                onFinish: () => {
                    studentLoading.value = false;
                },
                onError: (errors) => {
                    studentLoading.value = false;
                    if (errors.otp) {
                        otpError.value = errors.otp;
                        otpDigits.value = ["", "", "", "", "", ""];
                        studentForm.otp = "";
                        otpRefs.value[0]?.focus();
                    }
                },
            });
        };

        // Kembali ke langkah email/password dan buang draft alur OTP.
        const backToEmailForm = () => {
            clearOtpDraft();
            showOtpForm.value = false;
        };

        return {
            activeTab,
            showOtpForm,
            showAdminPassword,
            showStudentPassword,
            emailInput,
            passwordInput,
            otpRefs,
            otpDigits,
            onOtpDigitInput,
            onOtpDigitKeydown,
            onOtpPaste,
            adminForm,
            adminLoading,
            studentForm,
            studentLoading,
            otpLoading,
            otpCooldown,
            emailError,
            passwordError,
            otpError,
            isFormValid,
            syncStudentCredentials,
            validateEmail,
            validatePassword,
            submitAdmin,
            submitStudent,
            sendOtp,
            resendOtp,
            backToEmailForm,
        };
    },
};
</script>

<style scoped>
.nav-tabs .nav-link {
    color: #6c757d;
    border: none;
    border-bottom: 3px solid transparent;
    padding: 10px 15px;
    font-weight: 500;
    transition: all 0.3s ease;
    font-size: 14px;
}

.nav-tabs .nav-link:hover {
    color: #1a2332;
    border-bottom-color: #1a2332;
}

.nav-tabs .nav-link.active {
    color: #1a2332;
    border-bottom-color: #1a2332;
    background: transparent;
}

.nav-tabs .nav-link i {
    font-size: 14px;
}

.fmxw-500 {
    max-width: 500px;
    width: 100%;
}

.btn-gray-800 {
    background: #1a2332;
    color: white;
    border: none;
    transition: all 0.3s ease;
    padding: 12px 24px;
    font-weight: 600;
    font-size: 14px;
}

.btn-gray-800:hover {
    background: #2a3a52;
    color: white;
    transform: translateY(-2px);
}

.btn-gray-800:disabled {
    opacity: 0.7;
    transform: none;
}

.bg-soft {
    background: #f8f9fa;
}

.form-bg-image {
    background-size: contain;
    background-position: center;
    background-repeat: no-repeat;
}

.is-invalid {
    border-color: #dc3545 !important;
    box-shadow: 0 0 0 0.2rem rgba(220, 53, 69, 0.25) !important;
}

.is-invalid:focus {
    border-color: #dc3545 !important;
    box-shadow: 0 0 0 0.2rem rgba(220, 53, 69, 0.25) !important;
}

input.text-center {
    font-size: 20px;
    letter-spacing: 4px;
}

/* ===== RESPONSIVE ===== */

@media (max-width: 992px) {
    .vh-lg-100 {
        min-height: 100vh;
        padding: 40px 0;
        margin-top: 0 !important;
    }

    .form-bg-image {
        background-image: none !important;
    }

    .fmxw-500 {
        max-width: 450px;
    }
}

@media (max-width: 576px) {
    section.vh-lg-100 {
        min-height: 100vh;
        padding: 0 !important;
        margin-top: 0 !important;
        align-items: center !important;
        display: flex !important;
    }

    section.vh-lg-100 .container {
        padding: 0 !important;
    }

    section.vh-lg-100 .row {
        margin: 0 !important;
        min-height: 100vh;
        align-items: center !important;
    }

    .fmxw-500 {
        max-width: 100%;
        padding: 25px 20px !important;
        border-radius: 0 !important;
        box-shadow: none !important;
        border: none !important;
        background: white !important;
    }

    .bg-white {
        border-radius: 0 !important;
    }

    .shadow {
        box-shadow: none !important;
    }

    .nav-tabs .nav-link {
        padding: 8px 10px;
        font-size: 13px;
    }

    .nav-tabs .nav-link i {
        font-size: 13px;
        margin-right: 4px !important;
    }

    .form-group label {
        font-size: 14px;
        font-weight: 500;
    }

    .input-group-text {
        padding: 0 12px;
        font-size: 14px;
    }

    .form-control {
        font-size: 14px;
        padding: 10px 12px;
        height: auto;
    }

    .btn-gray-800 {
        padding: 10px 20px;
        font-size: 13px;
    }

    .text-center h3 {
        font-size: 20px;
    }

    .text-muted {
        font-size: 13px;
    }

    .alert {
        font-size: 13px;
        padding: 10px 12px;
    }

    small.text-muted {
        font-size: 12px;
    }
}

@media (max-width: 400px) {
    section.vh-lg-100 {
        padding: 0 !important;
    }

    .fmxw-500 {
        padding: 20px 15px !important;
    }

    .nav-tabs .nav-link {
        padding: 6px 8px;
        font-size: 12px;
    }

    .nav-tabs .nav-link i {
        font-size: 12px;
        margin-right: 3px !important;
    }

    .form-group label {
        font-size: 13px;
    }

    .input-group-text {
        padding: 0 10px;
        font-size: 13px;
    }

    .form-control {
        font-size: 13px;
        padding: 8px 10px;
    }

    .btn-gray-800 {
        padding: 8px 16px;
        font-size: 12px;
    }

    .text-center h3 {
        font-size: 18px;
    }

    .text-muted {
        font-size: 12px;
    }

    .alert {
        font-size: 12px;
        padding: 8px 10px;
    }
}

@media (max-height: 600px) and (orientation: landscape) {
    section.vh-lg-100 {
        min-height: 100vh;
        padding: 10px 0 !important;
        margin-top: 0 !important;
        align-items: center !important;
        display: flex !important;
    }

    .fmxw-500 {
        max-width: 450px;
        padding: 20px !important;
    }

    .bg-white {
        border-radius: 12px !important;
        box-shadow: 0 0 20px rgba(0, 0, 0, 0.08) !important;
    }

    .text-center h3 {
        font-size: 18px;
        margin-bottom: 8px !important;
    }

    .text-center p {
        font-size: 13px;
        margin-bottom: 12px !important;
    }

    .form-group.mb-4 {
        margin-bottom: 12px !important;
    }

    .form-group label {
        font-size: 13px;
    }

    .form-control {
        padding: 8px 12px;
        font-size: 13px;
        height: 38px;
    }

    .input-group-text {
        padding: 0 10px;
        font-size: 13px;
        height: 38px;
    }

    .btn-gray-800 {
        padding: 8px 16px;
        font-size: 13px;
    }

    .nav-tabs .nav-link {
        padding: 6px 10px;
        font-size: 13px;
    }

    .mt-3 {
        margin-top: 8px !important;
    }
}

@media (min-width: 768px) and (max-width: 992px) {
    .fmxw-500 {
        max-width: 420px;
    }
}

.form-check-input {
    width: 1.15em !important;
    height: 1.15em !important;
    min-width: 1.15em !important;
    min-height: 1.15em !important;
    max-width: 1.15em !important;
    max-height: 1.15em !important;
    margin-top: 0.2em !important;
    vertical-align: top !important;
    flex-shrink: 0 !important;
    display: inline-block !important;
}

.otp-container {
    display: flex !important;
    flex-direction: row !important;
    flex-wrap: nowrap !important;
    justify-content: center !important;
    align-items: center !important;
    gap: 8px !important;
    width: 100% !important;
}

.otp-box {
    width: 44px !important;
    max-width: 44px !important;
    height: 52px !important;
    font-size: 22px !important;
    border-radius: 10px !important;
    border: 2px solid #e2e8f0 !important;
    transition: all 0.2s !important;
    padding: 0 !important;
    text-align: center !important;
    flex-shrink: 1 !important;
    min-width: 0 !important;
}

.otp-box:focus {
    border-color: #1A2332 !important;
    box-shadow: 0 0 0 4px rgba(79, 70, 229, 0.1) !important;
}

.otp-box.is-invalid {
    border-color: #ef4444 !important;
}

.otp-box.is-invalid:focus {
    box-shadow: 0 0 0 4px rgba(239, 68, 68, 0.1) !important;
}

@media (max-width: 420px) {
    .otp-container {
        gap: 6px !important;
    }

    .otp-box {
        width: 36px !important;
        max-width: 36px !important;
        height: 46px !important;
        font-size: 18px !important;
    }
}
</style>
