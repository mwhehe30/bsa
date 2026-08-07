<template>
    <Head>
        <title>Ujian Kecermatan - Buweuk Sipit Academy</title>
    </Head>
    <div class="container-fluid mt-4 mb-5">
        <div class="row align-items-center mb-3">
            <div class="col-md-6">
                <h4 class="fw-bold mb-0 text-dark">
                    <i class="fa fa-bolt me-2 text-primary"></i>Ujian Kecermatan
                </h4>
                <p class="text-muted small mb-0">Kelola modul ujian kecermatan dan lihat hasil nilai siswa</p>
            </div>
            <div class="col-md-6 text-end">
                <form @submit.prevent="handleSearch" class="d-inline-block col-md-8">
                    <div class="input-group">
                        <input
                            type="text"
                            class="form-control border-0 shadow-sm"
                            v-model="search"
                            placeholder="Cari ujian kecermatan..."
                        />
                        <button class="btn btn-primary shadow-sm" type="submit">
                            <i class="fa fa-search"></i>
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <div class="row">
            <div class="col-md-12">
                <div class="card border-0 shadow-sm rounded-3">
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="bg-light">
                                    <tr>
                                        <th class="px-4 py-3" style="width: 5%">No.</th>
                                        <th class="py-3">Judul Ujian</th>
                                        <th class="py-3 text-center">Durasi</th>
                                        <th class="py-3 text-center">Peserta</th>
                                        <th class="py-3 text-center">Status</th>
                                        <th class="py-3 text-center">Dibuat Oleh</th>
                                        <th class="px-4 py-3 text-center" style="width: 20%">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="(exam, index) in exams.data" :key="exam.id">
                                        <td class="px-4 fw-semibold text-muted">
                                            {{ ++index + (exams.current_page - 1) * exams.per_page }}
                                        </td>
                                        <td>
                                            <strong class="text-dark d-block">{{ exam.title }}</strong>
                                            <small class="text-muted">{{ exam.created_at }}</small>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge bg-info bg-opacity-10 text-info fw-bold">
                                                {{ Math.round(exam.duration / 60) }} Menit
                                            </span>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge bg-primary rounded-pill px-3">
                                                {{ exam.total_participants }} Peserta
                                            </span>
                                        </td>
                                        <td class="text-center">
                                            <span v-if="exam.is_active" class="badge bg-success">Aktif</span>
                                            <span v-else class="badge bg-secondary">Non-Aktif</span>
                                        </td>
                                        <td class="text-center small text-muted">{{ exam.creator }}</td>
                                        <td class="px-4 text-center">
                                            <Link
                                                :href="`/admin/kecermatan/${exam.id}`"
                                                class="btn btn-sm btn-primary me-2 shadow-sm"
                                                title="Lihat Peserta & Hasil"
                                            >
                                                <i class="fa fa-users me-1"></i> Hasil Siswa
                                            </Link>
                                        </td>
                                    </tr>
                                    <tr v-if="exams.data.length === 0">
                                        <td colspan="7" class="text-center py-4 text-muted">
                                            <i class="fa fa-info-circle me-1"></i> Belum ada data ujian kecermatan.
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <Pagination :links="exams.links" class="mt-4" />
            </div>
        </div>
    </div>
</template>

<script>
import LayoutAdmin from '../../../Layouts/Admin.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import Pagination from '../../../Components/Pagination.vue';
import { ref } from 'vue';

export default {
    layout: LayoutAdmin,
    components: { Head, Link, Pagination },
    props: {
        exams: Object,
        filters: Object,
    },
    setup(props) {
        const search = ref(props.filters.search || '');

        const handleSearch = () => {
            router.get('/admin/kecermatan', { search: search.value }, { preserveState: true });
        };

        return { search, handleSearch };
    },
};
</script>
