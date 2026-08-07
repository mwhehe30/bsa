<template>
    <Head>
        <title>Detail Hasil Peserta Kecermatan - Buweuk Sipit Academy</title>
    </Head>
    <div class="container-fluid mt-4 mb-5">
        <!-- Header & Breadcrumb -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h4 class="fw-bold mb-1 text-dark">
                    <i class="fa fa-chart-bar me-2 text-primary"></i>Hasil Peserta Kecermatan
                </h4>
                <p class="text-muted small mb-0">{{ exam.title }}</p>
            </div>
            <Link href="/admin/kecermatan" class="btn btn-outline-secondary btn-sm">
                <i class="fa fa-arrow-left me-1"></i> Kembali ke Daftar Kecermatan
            </Link>
        </div>

        <!-- Summary Stats Cards -->
        <div class="row g-3 mb-4">
            <div class="col-md-3">
                <div class="card border-0 shadow-sm rounded-3 p-3 bg-white border-start border-4 border-primary">
                    <div class="small text-muted text-uppercase fw-bold">Total Peserta</div>
                    <div class="fs-3 fw-bold text-dark mt-1">{{ stats.total_participants }}</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card border-0 shadow-sm rounded-3 p-3 bg-white border-start border-4 border-success">
                    <div class="small text-muted text-uppercase fw-bold">Selesai</div>
                    <div class="fs-3 fw-bold text-success mt-1">{{ stats.total_completed }}</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card border-0 shadow-sm rounded-3 p-3 bg-white border-start border-4 border-warning">
                    <div class="small text-muted text-uppercase fw-bold">Sedang Ujian</div>
                    <div class="fs-3 fw-bold text-warning mt-1">{{ stats.total_in_progress }}</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card border-0 shadow-sm rounded-3 p-3 bg-white border-start border-4 border-info">
                    <div class="small text-muted text-uppercase fw-bold">Rata-rata Skor</div>
                    <div class="fs-3 fw-bold text-info mt-1">{{ stats.average_score }}</div>
                </div>
            </div>
        </div>

        <!-- Participants List Table -->
        <div class="card border-0 shadow-sm rounded-3">
            <div class="card-header bg-white py-3 border-bottom">
                <h6 class="fw-bold mb-0 text-dark">
                    <i class="fa fa-list me-2 text-primary"></i>Daftar Hasil Siswa ({{ participants.length }} Peserta)
                </h6>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="bg-light">
                            <tr>
                                <th class="px-4 py-3" style="width: 5%">No.</th>
                                <th class="py-3">Nama Siswa</th>
                                <th class="py-3 text-center">Tipe Soal</th>
                                <th class="py-3 text-center">Benar</th>
                                <th class="py-3 text-center">Salah</th>
                                <th class="py-3 text-center">Total Skor</th>
                                <th class="py-3 text-center">Status</th>
                                <th class="py-3 text-center">Waktu Selesai</th>
                                <th class="px-4 py-3 text-center" style="width: 15%">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="(p, index) in participants" :key="p.id">
                                <td class="px-4 fw-semibold text-muted">{{ index + 1 }}</td>
                                <td>
                                    <strong class="text-dark d-block">{{ p.student_name }}</strong>
                                    <small class="text-muted">{{ p.student_email }}</small>
                                </td>
                                <td class="text-center">
                                    <span class="badge bg-indigo bg-opacity-10 text-primary uppercase">
                                        {{ p.exam_type ? p.exam_type.toUpperCase() : 'GAMBAR' }}
                                    </span>
                                </td>
                                <td class="text-center fw-bold text-success">+{{ p.total_correct || 0 }}</td>
                                <td class="text-center fw-bold text-danger">{{ p.total_wrong || 0 }}</td>
                                <td class="text-center fw-extrabold text-primary fs-6">{{ p.total_score || 0 }}</td>
                                <td class="text-center">
                                    <span v-if="p.status === 'completed'" class="badge bg-success">Selesai</span>
                                    <span v-else class="badge bg-warning text-dark">Sedang Ujian</span>
                                </td>
                                <td class="text-center small text-muted">{{ p.finished_at }}</td>
                                <td class="px-4 text-center">
                                    <Link
                                        :href="`/admin/kecermatan/${exam.id}/result/${p.id}`"
                                        class="btn btn-sm btn-primary shadow-sm"
                                        title="Lihat Detail Hasil Siswa"
                                    >
                                        <i class="fa fa-eye me-1"></i> Detail Hasil
                                    </Link>
                                </td>
                            </tr>
                            <tr v-if="participants.length === 0">
                                <td colspan="9" class="text-center py-4 text-muted">
                                    <i class="fa fa-info-circle me-1"></i> Belum ada siswa yang mengerjakan ujian kecermatan ini.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import LayoutAdmin from '../../../Layouts/Admin.vue';
import { Head, Link } from '@inertiajs/vue3';

export default {
    layout: LayoutAdmin,
    components: { Head, Link },
    props: {
        exam: Object,
        stats: Object,
        participants: Array,
    },
};
</script>
