<template>
    <Head>
        <title>Ujian - Buweuk Sipit Academy</title>
    </Head>
    <div class="container-fluid mt-5 mb-5">
        <div class="row">
            <div class="col-md-8">
                <div class="row">
                    <div class="col-md-3 col-12 mb-2">
                        <Link
                            href="/admin/exams/create"
                            class="btn btn-md btn-primary w-100 border-0 shadow"
                            type="button"
                        >
                            <i class="fa fa-plus-circle"></i> Tambah Ujian
                        </Link>
                    </div>
                    <div class="col-md-9 col-12 mb-2">
                        <form @submit.prevent="handleSearch">
                            <div class="input-group">
                                <input
                                    type="text"
                                    class="form-control border-0 shadow"
                                    v-model="search"
                                    placeholder="masukkan kata kunci dan enter..."
                                />
                                <span class="input-group-text border-0 shadow">
                                    <i class="fa fa-search"></i>
                                </span>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        <div class="row mt-1">
            <div class="col-md-12">
                <div class="card border-0 shadow">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table
                                class="table-bordered table-centered table-nowrap mb-0 table rounded"
                            >
                                <thead class="thead-dark">
                                    <tr class="border-0">
                                        <th
                                            class="rounded-start border-0"
                                            style="width: 5%"
                                        >
                                            No.
                                        </th>
                                        <th class="border-0">Ujian</th>
                                        <th class="border-0">Kategori</th>
                                        <th class="border-0">Mapel</th>
                                        <th class="border-0">Jumlah Soal</th>
                                        <th
                                            class="rounded-end border-0"
                                            style="width: 18%"
                                        >
                                            Aksi
                                        </th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr
                                        v-for="(exam, index) in exams.data"
                                        :key="index"
                                    >
                                        <td class="fw-bold text-center">
                                            {{
                                                ++index +
                                                (exams.current_page - 1) *
                                                    exams.per_page
                                            }}
                                        </td>
                                        <td>{{ exam.title }}</td>
                                        <td>
                                            <span
                                                v-if="
                                                    exam.lesson.category ===
                                                    'psikologi'
                                                "
                                                class="badge bg-primary"
                                                >Psikologi</span
                                            >
                                            <span
                                                v-else
                                                class="badge bg-success"
                                                >Akademik</span
                                            >
                                        </td>
                                        <td>{{ exam.lesson.name }}</td>
                                        <td class="text-center">
                                            {{ exam.questions_count }}
                                        </td>
                                        <td class="text-center">
                                            <Link
                                                :href="`/admin/exams/${exam.id}`"
                                                class="btn btn-sm btn-primary me-1 border-0 shadow"
                                                title="Soal Ujian"
                                            >
                                                <i
                                                    class="fa fa-plus-circle"
                                                ></i>
                                                Soal
                                            </Link>
                                            <Link
                                                :href="`/admin/exams/${exam.id}/edit`"
                                                class="btn btn-sm btn-info me-1 border-0 shadow"
                                                type="button"
                                            >
                                                <i class="fa fa-pencil-alt"></i>
                                            </Link>
                                            <button
                                                @click.prevent="
                                                    destroy(exam.id)
                                                "
                                                class="btn btn-sm btn-danger border-0"
                                            >
                                                <i class="fa fa-trash"></i>
                                            </button>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <Pagination
                            :links="exams.links"
                            align="end"
                        />
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import LayoutAdmin from '../../../Layouts/Admin.vue';
import Pagination from '../../../Components/Pagination.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import Swal from 'sweetalert2';

export default {
    layout: LayoutAdmin,
    components: { Head, Link, Pagination },
    props: {
        exams: Object,
    },
    setup() {
        const search = ref(
            '' || new URL(document.location).searchParams.get('q'),
        );

        const handleSearch = () => {
            router.get('/admin/exams', { q: search.value });
        };

        const destroy = (id) => {
            Swal.fire({
                title: 'Apakah Anda yakin?',
                text: 'Anda tidak akan dapat mengembalikan ini!',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Yes, delete it!',
            }).then((result) => {
                if (result.isConfirmed) {
                    router.delete(`/admin/exams/${id}`);
                    Swal.fire({
                        title: 'Deleted!',
                        text: 'Ujian Berhasil Dihapus!.',
                        icon: 'success',
                        timer: 2000,
                        showConfirmButton: false,
                    });
                }
            });
        };

        return { search, handleSearch, destroy };
    },
};
</script>
