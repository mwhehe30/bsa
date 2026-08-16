<template>
    <Head>
        <title>Test Center - Buweuk Sipit Academy</title>
    </Head>

    <main class="tc-wrap">
        <header class="tc-header">
            <div>
                <p class="tc-eyebrow">SIMULASI TANPA LOGIN</p>
                <h1>Test Center</h1>
                <p class="tc-sub">
                    Mengetes semua fitur ujian dengan alur asli. Otomatis login
                    sebagai siswa test — tidak perlu form login.
                </p>
            </div>
            <div class="tc-header-actions">
                <span class="tc-student">
                    <i class="fa fa-user"></i>
                    {{ student.name }}
                    <small>{{ student.email }}</small>
                </span>
                <button type="button" class="tc-btn tc-btn-danger" @click="resetAll">
                    <i class="fa fa-rotate-left"></i> Reset Semua Percobaan
                </button>
            </div>
        </header>

        <section class="tc-card">
            <div class="tc-card-head">
                <h2><i class="fa fa-file-text"></i> Ujian Reguler</h2>
                <span class="tc-count">{{ exams.length }} ujian</span>
            </div>

            <div v-if="exams.length === 0" class="tc-empty">
                Belum ada ujian. Buat dulu lewat halaman admin.
            </div>

            <div v-else class="tc-table-wrap">
            <table class="tc-table">
                <thead>
                    <tr>
                        <th>Ujian</th>
                        <th class="d-none d-sm-table-cell">Mapel</th>
                        <th class="d-none d-sm-table-cell">Durasi</th>
                        <th>Tipe</th>
                        <th>Status</th>
                        <th class="tc-right">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="exam in exams" :key="exam.id">
                        <td class="tc-title">{{ exam.title }}</td>
                        <td class="d-none d-sm-table-cell">{{ exam.lesson }}</td>
                        <td class="d-none d-sm-table-cell">{{ exam.duration }} menit</td>
                        <td>
                            <span
                                class="tc-badge"
                                :class="exam.is_kecermatan ? 'tc-badge-kecermatan' : 'tc-badge-regular'"
                            >
                                {{ exam.is_kecermatan ? 'Kecermatan' : 'Reguler' }}
                            </span>
                        </td>
                        <td>
                            <span class="tc-badge" :class="exam.is_active ? 'tc-badge-active' : 'tc-badge-inactive'">
                                {{ exam.is_active ? 'Aktif' : 'Nonaktif' }}
                            </span>
                        </td>
                        <td class="tc-right">
                            <Link
                                :href="`/student/exam-confirmation/${exam.id}`"
                                class="tc-btn tc-btn-primary"
                            >
                                <i class="fa fa-play"></i> Mulai Ujian
                            </Link>
                        </td>
                    </tr>
                </tbody>
            </table>
            </div>
        </section>

        <section class="tc-card">
            <div class="tc-card-head">
                <h2><i class="fa fa-bolt"></i> Ujian Kecermatan Standalone</h2>
                <span class="tc-count">{{ standalone_kecermatan.length }} ujian</span>
            </div>

            <div v-if="standalone_kecermatan.length === 0" class="tc-empty">
                Tidak ada ujian kecermatan standalone.
            </div>

            <div v-else class="tc-table-wrap">
            <table class="tc-table">
                <thead>
                    <tr>
                        <th>Ujian</th>
                        <th>Status</th>
                        <th class="tc-right">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="exam in standalone_kecermatan" :key="exam.id">
                        <td class="tc-title">{{ exam.title }}</td>
                        <td>
                            <span class="tc-badge" :class="exam.is_active ? 'tc-badge-active' : 'tc-badge-inactive'">
                                {{ exam.is_active ? 'Aktif' : 'Nonaktif' }}
                            </span>
                        </td>
                        <td class="tc-right">
                            <button
                                type="button"
                                class="tc-btn tc-btn-primary"
                                @click="startStandalone(exam)"
                            >
                                <i class="fa fa-play"></i> Mulai Ujian
                            </button>
                        </td>
                    </tr>
                </tbody>
            </table>
            </div>
        </section>

        <section class="tc-card">
            <div class="tc-card-head">
                <h2><i class="fa fa-flask"></i> Yang Bisa Diuji</h2>
            </div>
            <ul class="tc-checklist">
                <li>
                    <strong>Ujian reguler:</strong> konfirmasi → mulai → jawab soal
                    (keyboard 1-5) → submit → lihat hasil.
                </li>
                <li>
                    <strong>Ujian kecermatan:</strong> pilih tipe → 10 kolom × 60
                    detik → hasil per kolom + grafik.
                </li>
                <li>
                    <strong>Blokir pindah tab:</strong> buka halaman lain di tengah
                    ujian → otomatis dipaksa balik + dihitung pelanggaran.
                </li>
                <li>
                    <strong>Auto-submit waktu habis:</strong> tutup tab, tunggu
                    melewati durasi, buka lagi → ujian otomatis diselesaikan.
                </li>
                <li>
                    <strong>Isolir:</strong> siswa yang diblokir muncul di admin
                    (Siswa Terisolir) dan bisa dibuka isolirnya.
                </li>
            </ul>
        </section>
    </main>
</template>

<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import Swal from 'sweetalert2';

defineProps({
    student: Object,
    exams: Array,
    standalone_kecermatan: Array,
});

const startStandalone = (exam) => {
    router.post(`/student/kecermatan/${exam.id}/start`, {
        exam_type: 'huruf',
    });
};

const resetAll = () => {
    Swal.fire({
        title: 'Reset Semua Percobaan?',
        text: 'Semua grade, sesi kecermatan, dan pelanggaran siswa test akan dihapus agar bisa tes dari nol.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc2626',
        cancelButtonColor: '#64748b',
        confirmButtonText: 'Ya, Reset!',
        cancelButtonText: 'Batal',
    }).then((result) => {
        if (result.isConfirmed) {
            router.post('/test-center/reset', {}, {
                onSuccess: () => {
                    Swal.fire({
                        title: 'Berhasil',
                        text: 'Percobaan siswa test sudah dihapus.',
                        icon: 'success',
                        timer: 1500,
                        showConfirmButton: false,
                    });
                },
            });
        }
    });
};
</script>

<style scoped>
:global(body) {
    margin: 0;
    background: #f4f7fb;
}

.tc-wrap {
    min-height: 100vh;
    max-width: 980px;
    margin: 0 auto;
    padding: 36px 20px 72px;
    color: #0d213f;
    font-family:
        Inter,
        ui-sans-serif,
        system-ui,
        -apple-system,
        BlinkMacSystemFont,
        'Segoe UI',
        sans-serif;
}

.tc-header {
    display: flex;
    align-items: flex-end;
    justify-content: space-between;
    gap: 20px;
    flex-wrap: wrap;
    margin-bottom: 28px;
}

.tc-eyebrow {
    margin: 0 0 6px;
    color: #1f5eff;
    font-size: 0.7rem;
    font-weight: 800;
    letter-spacing: 0.14em;
}

h1 {
    margin: 0 0 8px;
    font-size: 1.9rem;
}

.tc-sub {
    margin: 0;
    color: #64748b;
    font-size: 0.92rem;
}

.tc-header-actions {
    display: flex;
    align-items: center;
    gap: 12px;
    flex-wrap: wrap;
}

.tc-student {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 9px 14px;
    border-radius: 12px;
    background: #fff;
    border: 1px solid #dfe6f0;
    font-weight: 700;
    font-size: 0.88rem;
}

.tc-student small {
    display: block;
    color: #64748b;
    font-weight: 500;
}

.tc-card {
    margin-bottom: 24px;
    padding: 22px 24px;
    border-radius: 18px;
    background: #fff;
    border: 1px solid #e2e8f0;
}

.tc-card-head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 16px;
}

.tc-card-head h2 {
    margin: 0;
    font-size: 1.05rem;
}

.tc-card-head h2 i {
    color: #1f5eff;
    margin-right: 8px;
}

.tc-count {
    color: #64748b;
    font-size: 0.8rem;
    font-weight: 600;
}

.tc-table-wrap {
    overflow-x: auto;
    -webkit-overflow-scrolling: touch;
    margin: 0 -6px;
    padding: 0 6px;
}

.tc-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 0.9rem;
    min-width: 0;
}

@media (max-width: 575.98px) {
    .tc-table-wrap {
        margin: 0;
        padding: 0;
    }

    .tc-table {
        min-width: 320px;
    }
}

.tc-table th {
    padding: 10px 12px;
    text-align: left;
    color: #64748b;
    font-size: 0.72rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    border-bottom: 2px solid #eef2f7;
}

.tc-table td {
    padding: 13px 12px;
    border-bottom: 1px solid #eef2f7;
    color: #334155;
    vertical-align: middle;
}

.tc-table tr:last-child td {
    border-bottom: 0;
}

.tc-title {
    font-weight: 700;
    color: #0d213f;
}

.tc-right {
    text-align: right;
}

.tc-badge {
    display: inline-block;
    padding: 4px 10px;
    border-radius: 999px;
    font-size: 0.72rem;
    font-weight: 700;
    white-space: nowrap;
}

.tc-badge-regular {
    color: #1e40af;
    background: #e0e7ff;
}

.tc-badge-kecermatan {
    color: #7c2d12;
    background: #ffedd5;
}

.tc-badge-active {
    color: #15803d;
    background: #dcfce7;
}

.tc-badge-inactive {
    color: #64748b;
    background: #e2e8f0;
}

.tc-btn {
    display: inline-flex;
    align-items: center;
    gap: 7px;
    padding: 9px 16px;
    border: 0;
    border-radius: 10px;
    font-size: 0.85rem;
    font-weight: 700;
    cursor: pointer;
    text-decoration: none;
    transition:
        transform 0.15s ease,
        box-shadow 0.15s ease;
}

.tc-btn:hover {
    transform: translateY(-1px);
}

.tc-btn-primary {
    color: #fff;
    background: linear-gradient(135deg, #173969, #1f5eff);
}

.tc-btn-danger {
    color: #fff;
    background: #dc2626;
}

.tc-empty {
    padding: 26px;
    text-align: center;
    color: #94a3b8;
    background: #f8fafc;
    border-radius: 12px;
    font-size: 0.9rem;
}

.tc-checklist {
    margin: 0;
    padding: 0;
    list-style: none;
    display: grid;
    gap: 10px;
}

.tc-checklist li {
    padding: 12px 14px;
    border-radius: 12px;
    background: #f8fafc;
    border: 1px solid #eef2f7;
    color: #475569;
    font-size: 0.9rem;
    line-height: 1.6;
}

.tc-checklist strong {
    color: #0d213f;
}

@media (max-width: 640px) {
    .tc-header {
        align-items: flex-start;
    }

    .tc-table {
        font-size: 0.82rem;
    }

    .tc-table th,
    .tc-table td {
        padding: 10px 8px;
    }

    .tc-btn {
        padding: 8px 12px;
        font-size: 0.8rem;
    }
}
</style>
