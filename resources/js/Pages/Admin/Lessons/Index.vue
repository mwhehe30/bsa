<template>
    <Head>
        <title>Mata Pelajaran - Buweuk Sipit Academy</title>
    </Head>
    <div class="container-fluid mt-5 mb-5">
        <div class="row">
            <div class="col-md-8">
                <div class="row">
                    <div class="col-md-4 col-12 mb-2">
                        <Link
                            href="/admin/lessons/create"
                            class="btn btn-md btn-primary w-100 border-0 shadow"
                            type="button"
                        >
                            <i class="fa fa-plus-circle"></i> Tambah Mapel
                        </Link>
                    </div>
                    <div class="col-md-8 col-12 mb-2">
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

        <div class="row mt-3">
            <!-- Psikologi -->
            <div class="col-md-6">
                <div class="card border-0 shadow">
                    <div class="card-header bg-primary text-white">
                        <i class="fa fa-brain me-2"></i> PSIKOLOGI
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table
                                class="table-bordered table-centered table-nowrap mb-0 table rounded"
                            >
                                <thead class="thead-dark">
                                    <tr class="border-0">
                                        <th
                                            class="border-0"
                                            style="width: 5%"
                                        >
                                            No.
                                        </th>
                                        <th class="border-0">Nama Mapel</th>
                                        <th
                                            class="border-0"
                                            style="width: 20%"
                                        >
                                            Aksi
                                        </th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr
                                        v-for="(
                                            lesson, index
                                        ) in lessons.psikologi"
                                        :key="index"
                                    >
                                        <td class="fw-bold text-center">
                                            {{ index + 1 }}
                                        </td>
                                        <td>{{ lesson.name }}</td>
                                        <td class="text-center">
                                            <Link
                                                :href="`/admin/lessons/${lesson.id}/edit`"
                                                class="btn btn-sm btn-info me-2 border-0 shadow"
                                            >
                                                <i class="fa fa-pencil-alt"></i>
                                            </Link>
                                            <button
                                                @click.prevent="
                                                    destroy(lesson.id)
                                                "
                                                class="btn btn-sm btn-danger border-0"
                                            >
                                                <i class="fa fa-trash"></i>
                                            </button>
                                        </td>
                                    </tr>
                                    <tr
                                        v-if="
                                            !lessons.psikologi ||
                                            lessons.psikologi.length === 0
                                        "
                                    >
                                        <td
                                            colspan="3"
                                            class="text-muted text-center"
                                        >
                                            Belum ada mapel
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Akademik -->
            <div class="col-md-6">
                <div class="card border-0 shadow">
                    <div class="card-header bg-success text-white">
                        <i class="fa fa-book me-2"></i> AKADEMIK
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table
                                class="table-bordered table-centered table-nowrap mb-0 table rounded"
                            >
                                <thead class="thead-dark">
                                    <tr class="border-0">
                                        <th
                                            class="border-0"
                                            style="width: 5%"
                                        >
                                            No.
                                        </th>
                                        <th class="border-0">Nama Mapel</th>
                                        <th
                                            class="border-0"
                                            style="width: 20%"
                                        >
                                            Aksi
                                        </th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr
                                        v-for="(
                                            lesson, index
                                        ) in lessons.akademik"
                                        :key="index"
                                    >
                                        <td class="fw-bold text-center">
                                            {{ index + 1 }}
                                        </td>
                                        <td>{{ lesson.name }}</td>
                                        <td class="text-center">
                                            <Link
                                                :href="`/admin/lessons/${lesson.id}/edit`"
                                                class="btn btn-sm btn-info me-2 border-0 shadow"
                                            >
                                                <i class="fa fa-pencil-alt"></i>
                                            </Link>
                                            <button
                                                @click.prevent="
                                                    destroy(lesson.id)
                                                "
                                                class="btn btn-sm btn-danger border-0"
                                            >
                                                <i class="fa fa-trash"></i>
                                            </button>
                                        </td>
                                    </tr>
                                    <tr
                                        v-if="
                                            !lessons.akademik ||
                                            lessons.akademik.length === 0
                                        "
                                    >
                                        <td
                                            colspan="3"
                                            class="text-muted text-center"
                                        >
                                            Belum ada mapel
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import LayoutAdmin from '../../../Layouts/Admin.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import Swal from 'sweetalert2';

export default {
    layout: LayoutAdmin,
    components: { Head, Link },
    props: {
        lessons: Object,
    },
    setup() {
        const search = ref(
            '' || new URL(document.location).searchParams.get('q'),
        );

        const handleSearch = () => {
            router.get('/admin/lessons', { q: search.value });
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
                    router.delete(`/admin/lessons/${id}`);
                    Swal.fire({
                        title: 'Deleted!',
                        text: 'Mapel Berhasil Dihapus!.',
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

