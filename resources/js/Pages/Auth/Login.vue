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
                                                type="password"
                                                placeholder="Password"
                                                class="form-control"
                                                v-model="adminForm.password"
                                            />
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
                                                type="password"
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
                                                    class="form-control fw-bold text-center otp-box"
                                                    :class="{
                                                        'is-invalid': otpError,
                                                    }"
                                                    maxlength="1"
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
                                </form>

                                <div class="mt-3 text-center">
                                    <button
                                        type="button"
                                        class="btn btn-link text-muted"
                                        @click="showOtpForm = false"
                                    >
                                        <i class="fa fa-chevron-left me-1"></i>
                                        Ganti Email
                                    </button>
                                </div>
                            </div>
                        </div>

                        <div class="mt-3 text-center">
                            <a href="/" class="text-decoration-none text-muted">
                                <i class="fa fa-chevron-left me-1"></i> Kembali
                                ke Beranda
                            </a>
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

        const emailError = ref("");
        const passwordError = ref("");
        const otpError = ref("");

        // Browser autofill kadang mengisi tampilan input tanpa memicu event input Vue.
        // Fungsi ini membaca nilai DOM dan menyinkronkannya ke state reactive.
        const syncStudentCredentials = () => {
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

            router.post(
                "/student/send-otp",
                { email: studentForm.email, password: studentForm.password },
                {
                    preserveScroll: true,
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
                    onSuccess: () => {
                        startCooldown();
                        nextTick(() => {
                            otpRefs.value[0]?.focus();
                        });
                    },
                },
            );
        };

        const onOtpDigitInput = (e, index) => {
            const val = e.target.value.replace(/\D/g, "");
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
            const pasteData = e.clipboardData
                .getData("text")
                .replace(/\D/g, "")
                .slice(0, 6);
            if (pasteData) {
                for (let i = 0; i < pasteData.length; i++) {
                    otpDigits.value[i] = pasteData[i];
                }
                const focusIndex = pasteData.length < 6 ? pasteData.length : 5;
                otpRefs.value[focusIndex]?.focus();
                checkAndSubmitOtp();
            }
        };

        const checkAndSubmitOtp = () => {
            const fullOtp = otpDigits.value.join("");
            studentForm.otp = fullOtp;
            if (fullOtp.length === 6) {
                otpError.value = "";
                submitStudent();
            } else {
                otpError.value = "";
            }
        };

        const submitAdmin = () => {
            adminLoading.value = true;
            router.post("/admin/login", adminForm, {
                onFinish: () => {
                    adminLoading.value = false;
                },
                onError: () => {
                    adminLoading.value = false;
                },
            });
        };

        const submitStudent = () => {
            if (studentForm.otp.length !== 6) {
                otpError.value = "Kode OTP harus 6 digit";
                otpRefs.value[0]?.focus();
                return;
            }

            studentLoading.value = true;
            router.post("/student/login", studentForm, {
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

        return {
            activeTab,
            showOtpForm,
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

    .mt-3 a {
        font-size: 13px;
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
    border-color: #4f46e5 !important;
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
