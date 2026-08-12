<template>
    <Head :title="'Pilih Tipe - ' + exam.title + ' - Buweuk Sipit Academy'" />

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
                            <i class="fa fa-gauge-high me-2 text-indigo"></i>Pilih Tipe Soal
                        </h5>
                    </div>
                    <div class="card-body p-4">
                        <!-- Badge Tipe -->
                        <div class="mb-3">
                            <span class="type-badge">
                                <i class="fa fa-gauge-high me-1"></i> Tes Kecermatan
                            </span>
                        </div>

                        <!-- Judul -->
                        <h4 class="exam-title mb-1">{{ exam.title }}</h4>
                        <p class="text-muted small mb-4">
                            Pilih salah satu tipe soal untuk memulai ujian
                        </p>

                        <!-- Info Ujian -->
                        <div class="info-table mb-4">
                            <div class="info-row">
                                <span class="info-label">Durasi</span>
                                <span class="info-value">
                                    {{ durationMinutes }} Menit (10 kolom × 60 detik)
                                </span>
                            </div>
                            <div class="info-row">
                                <span class="info-label">Jumlah Soal</span>
                                <span class="info-value">
                                    500 Soal (50 per kolom)
                                </span>
                            </div>
                            <div class="info-row">
                                <span class="info-label">Perubahan</span>
                                <span class="info-value">
                                    Tipe tidak bisa diubah setelah memulai
                                </span>
                            </div>
                        </div>

                        <hr class="divider-line my-4" />

                        <!-- Perhatian -->
                        <h6 class="form-section-title mb-3">
                            <i
                                class="fa fa-exclamation-triangle me-2 text-indigo"
                            ></i>Perhatian Sebelum Memulai
                        </h6>
                        <ul class="instruction-list mb-4">
                            <li>
                                Waktu pengerjaan <strong>10 menit</strong>
                                (10 kolom × 60 detik)
                            </li>
                            <li>
                                Total <strong>500 soal</strong> (50 soal per kolom)
                            </li>
                            <li>
                                Tipe soal yang dipilih
                                <strong>TIDAK BISA DIUBAH</strong> setelah ujian berjalan
                            </li>
                        </ul>

                        <hr class="divider-line my-4" />

                        <!-- Pilih Tipe Soal -->
                        <h6 class="form-section-title mb-3">
                            <i class="fa fa-hand-pointer me-2 text-indigo"></i>Pilih Tipe Soal
                        </h6>
                        <div class="row g-3">
                            <div class="col-6 col-lg-3">
                                <div
                                    class="card h-100 border-0 type-card"
                                    @click="handleSelect('huruf')"
                                >
                                    <div class="card-body text-center py-4">
                                        <div class="type-icon mx-auto mb-3">
                                            <i class="fa fa-font"></i>
                                        </div>
                                        <div class="type-name mb-1">HURUF</div>
                                        <small class="text-muted">A-Z Kapital</small>
                                    </div>
                                </div>
                            </div>
                            <div class="col-6 col-lg-3">
                                <div
                                    class="card h-100 border-0 type-card"
                                    @click="handleSelect('angka')"
                                >
                                    <div class="card-body text-center py-4">
                                        <div class="type-icon mx-auto mb-3">
                                            <i class="fa fa-hashtag"></i>
                                        </div>
                                        <div class="type-name mb-1">ANGKA</div>
                                        <small class="text-muted">0-9</small>
                                    </div>
                                </div>
                            </div>
                            <div class="col-6 col-lg-3">
                                <div
                                    class="card h-100 border-0 type-card"
                                    @click="handleSelect('simbol')"
                                >
                                    <div class="card-body text-center py-4">
                                        <div class="type-icon mx-auto mb-3">
                                            <i class="fa fa-asterisk"></i>
                                        </div>
                                        <div class="type-name mb-1">SIMBOL</div>
                                        <small class="text-muted">Karakter Khusus</small>
                                    </div>
                                </div>
                            </div>
                            <div class="col-6 col-lg-3">
                                <div
                                    class="card h-100 border-0 type-card"
                                    @click="handleSelect('gambar')"
                                >
                                    <div class="card-body text-center py-4">
                                        <div class="type-icon mx-auto mb-3">
                                            <i class="fa fa-image"></i>
                                        </div>
                                        <div class="type-name mb-1">GAMBAR</div>
                                        <small class="text-muted">Emoji &amp; Ikon</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { Head, Link, router } from '@inertiajs/vue3'
import LayoutStudent from '@/Layouts/Student.vue'
import { computed } from 'vue'
import Swal from 'sweetalert2'

defineOptions({ layout: LayoutStudent })

const props = defineProps({
    exam: Object
})

const durationMinutes = computed(() =>
    Math.max(1, Math.round((props.exam?.duration || 0) / 60))
)

const handleSelect = (type) => {
    Swal.fire({
        title: 'Konfirmasi Pilihan',
        html: `
            <div style="text-align: center; margin: 20px 0;">
                <i class="fas ${getTypeIcon(type)}" style="font-size: 3rem; color: #1A2332; margin-bottom: 15px;"></i>
                <h3 style="color: #1A2332; margin-bottom: 10px;">${getTypeName(type)}</h3>
                <p style="color: #dc3545; font-weight: bold;">
                    <i class="fas fa-exclamation-triangle"></i>
                    Yakin pilih tipe ${getTypeName(type)}?
                </p>
                <p style="color: #6c757d; font-size: 0.9rem;">Tipe tidak bisa diubah setelah memulai!</p>
            </div>
        `,
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#1A2332',
        cancelButtonColor: '#6c757d',
        confirmButtonText: '<i class="fas fa-check me-2"></i>Ya, Mulai Ujian',
        cancelButtonText: 'Batal',
        showLoaderOnConfirm: true,
        backdrop: true,
        allowOutsideClick: () => !Swal.isLoading(),
        preConfirm: () => {
            return new Promise((resolve) => {
                router.post(`/student/kecermatan/${props.exam.id}/start`, {
                    exam_type: type
                }, {
                    preserveState: false,
                    preserveScroll: false,
                    onError: () => {
                        Swal.fire({
                            icon: 'error',
                            title: 'Gagal!',
                            text: 'Terjadi kesalahan saat memulai ujian. Silakan coba lagi.',
                            confirmButtonColor: '#dc3545',
                        })
                        resolve(false)
                    },
                    onSuccess: () => {
                        resolve(true)
                    }
                })
            })
        }
    })
}

const getTypeIcon = (type) => {
    const icons = {
        'huruf': 'fa-font',
        'angka': 'fa-hashtag',
        'simbol': 'fa-asterisk',
        'gambar': 'fa-image'
    }
    return icons[type] || 'fa-question'
}

const getTypeName = (type) => {
    const names = {
        'huruf': 'HURUF',
        'angka': 'ANGKA',
        'simbol': 'SIMBOL',
        'gambar': 'GAMBAR'
    }
    return names[type] || type
}
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

/* ── Type Cards ────────────────────────────────── */
.type-card {
    cursor: pointer;
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    transition: all 0.2s ease;
}

.type-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 8px 16px -4px rgba(79, 70, 229, 0.15);
    border-color: #1A2332;
}

.type-icon {
    width: 56px;
    height: 56px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
    color: #1A2332;
    background: #eef2ff;
    border-radius: 50%;
    transition: all 0.2s ease;
}

.type-card:hover .type-icon {
    color: #ffffff;
    background: #1A2332;
}

.type-name {
    font-size: 0.9rem;
    font-weight: 700;
    color: #334155;
    letter-spacing: 0.05em;
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

    .type-icon {
        width: 48px;
        height: 48px;
        font-size: 1.25rem;
    }

    .type-name {
        font-size: 0.8rem;
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
