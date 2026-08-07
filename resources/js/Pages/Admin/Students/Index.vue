<template>
    <Head>
        <title>Siswa - Buweuk Sipit Academy</title>
    </Head>
    <div class="container-fluid mt-5 mb-5">
        <div class="row">
            <div class="col-md-8">
                <div class="row">
                    <div class="col-md-5 col-12 mb-2">
                        <div class="row">
                            <div class="col-md-6 col-12 mb-2">
                                <Link
                                    href="/admin/students/create"
                                    class="btn btn-md btn-primary w-100 border-0 shadow"
                                    type="button"
                                    ><i class="fa fa-plus-circle"></i>
                                    Tambah</Link
                                >
                            </div>
                            <div class="col-md-6 col-12 mb-2">
                                <Link
                                    href="/admin/students/import"
                                    class="btn btn-md btn-success w-100 border-0 text-white shadow"
                                    type="button"
                                    ><i class="fa fa-file-excel"></i>
                                    Import</Link
                                >
                            </div>
                        </div>
                    </div>
                    <div class="col-md-7 col-12 mb-2">
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
                        <div
                            class="d-flex justify-content-between align-items-center mb-3"
                        >
                            <div>
                                <span class="badge bg-secondary me-2"
                                    >Total: {{ students.total }} siswa</span
                                >
                                <span class="badge bg-primary"
                                    >{{ selectedStudents.length }} dipilih</span
                                >
                            </div>
                            <div
                                class="d-flex gap-2"
                                v-if="selectedStudents.length > 0"
                            >
                                <button
                                    @click.prevent="bulkDelete"
                                    class="btn btn-sm btn-danger border-0 shadow"
                                >
                                    <i class="fa fa-trash"></i> Hapus ({{
                                        selectedStudents.length
                                    }})
                                </button>
                                <button
                                    @click.prevent="bulkToggleActive"
                                    class="btn btn-sm btn-warning border-0 text-white shadow"
                                >
                                    <i class="fa fa-exchange-alt"></i> Ubah
                                    Status ({{ selectedStudents.length }})
                                </button>
                            </div>
                        </div>
                        <hr />

                        <div
                            v-if="$page.props.flash?.success"
                            class="alert alert-success"
                        >
                            <i class="fa fa-check-circle me-2"></i>
                            {{ $page.props.flash.success }}
                        </div>

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
                                            <input
                                                type="checkbox"
                                                v-model="allSelected"
                                                @change="selectAll"
                                            />
                                        </th>
                                        <th
                                            class="border-0"
                                            style="width: 5%"
                                        >
                                            No.
                                        </th>
                                        <th class="border-0">Nama</th>
                                        <th class="border-0">Email</th>
                                        <th class="border-0">Jenis Kelamin</th>
                                        <th class="border-0">Status</th>
                                        <th
                                            class="border-0"
                                            style="width: 13%"
                                        >
                                            Dibuat Pada
                                        </th>
                                        <th
                                            class="rounded-end border-0"
                                            style="width: 15%"
                                        >
                                            Aksi
                                        </th>
                                    </tr>
                                </thead>
                                <div class="mt-2"></div>
                                <tbody>
                                    <tr
                                        v-for="(
                                            student, index
                                        ) in students.data"
                                        :key="student.id"
                                    >
                                        <td>
                                            <input
                                                type="checkbox"
                                                v-model="selectedStudents"
                                                :value="student.id"
                                            />
                                        </td>
                                        <td class="fw-bold text-center">
                                            {{
                                                ++index +
                                                (students.current_page - 1) *
                                                    students.per_page
                                            }}
                                        </td>
                                        <td>{{ student.name }}</td>
                                        <td>{{ student.email }}</td>
                                        <td class="text-center">
                                            {{
                                                student.gender === 'L'
                                                    ? 'Laki-laki'
                                                    : 'Perempuan'
                                            }}
                                        </td>
                                        <td>
                                            <span
                                                v-if="!student.is_active"
                                                class="badge bg-secondary"
                                            >
                                                <i class="fa fa-ban me-1"></i>
                                                Nonaktif
                                            </span>
                                            <span
                                                v-else
                                                class="badge bg-success"
                                            >
                                                <i
                                                    class="fa fa-check-circle me-1"
                                                ></i>
                                                Aktif
                                            </span>
                                        </td>
                                        <td class="text-center text-nowrap">
                                            <small class="text-muted">{{
                                                formatDate(student.created_at)
                                            }}</small>
                                        </td>
                                        <td class="text-center">
                                            <Link
                                                :href="`/admin/students/${student.id}/edit`"
                                                class="btn btn-sm btn-info me-1 border-0 shadow"
                                                type="button"
                                            >
                                                <i class="fa fa-pencil-alt"></i>
                                            </Link>
                                            <button
                                                @click.prevent="
                                                    destroy(student.id)
                                                "
                                                class="btn btn-sm btn-danger border-0"
                                                title="Hapus"
                                            >
                                                <i class="fa fa-trash"></i>
                                            </button>
                                        </td>
                                    </tr>
                                    <tr v-if="students.data.length === 0">
                                        <td
                                            colspan="8"
                                            class="text-muted py-3 text-center"
                                        >
                                            <i
                                                class="fa fa-info-circle me-2"
                                            ></i>
                                            Tidak ada data siswa.
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <Pagination
                            :links="students.links"
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
import { ref, computed, watch } from 'vue';
import Swal from 'sweetalert2';

export default {
    layout: LayoutAdmin,
    components: { Head, Link, Pagination },
    props: {
        students: Object,
    },
    setup(props) {
        const search = ref(
            '' || new URL(document.location).searchParams.get('q'),
        );
        const selectedStudents = ref([]);

        const allSelected = computed({
            get: () => {
                return (
                    props.students.data.length > 0 &&
                    selectedStudents.value.length === props.students.data.length
                );
            },
            set: (value) => {
                if (value) {
                    selectedStudents.value = props.students.data.map(
                        (s) => s.id,
                    );
                } else {
                    selectedStudents.value = [];
                }
            },
        });

        const selectAll = () => {
            if (allSelected.value) {
                selectedStudents.value = props.students.data.map((s) => s.id);
            } else {
                selectedStudents.value = [];
            }
        };

        watch(
            () => props.students.data,
            () => {
                selectedStudents.value = [];
            },
        );

        const handleSearch = () => {
            router.get('/admin/students', { q: search.value });
        };

        const formatDate = (value) => {
            if (!value) return '-';
            const date = new Date(value);
            if (isNaN(date.getTime())) return '-';
            return date.toLocaleString('id-ID', {
                day: '2-digit',
                month: 'short',
                year: 'numeric',
                hour: '2-digit',
                minute: '2-digit',
            });
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
                    router.delete(`/admin/students/${id}`);
                    Swal.fire({
                        title: 'Deleted!',
                        text: 'Siswa Berhasil Dihapus!.',
                        icon: 'success',
                        timer: 2000,
                        showConfirmButton: false,
                    });
                }
            });
        };

        const bulkDelete = () => {
            if (selectedStudents.value.length === 0) return;

            Swal.fire({
                title: 'Hapus Siswa?',
                text: `Anda akan menghapus ${selectedStudents.value.length} siswa.`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Ya, Hapus!',
            }).then((result) => {
                if (result.isConfirmed) {
                    router.post(
                        '/admin/students/bulk-delete',
                        {
                            student_ids: selectedStudents.value,
                        },
                        {
                            onSuccess: () => {
                                selectedStudents.value = [];
                                Swal.fire({
                                    title: 'Deleted!',
                                    text: 'Siswa berhasil dihapus.',
                                    icon: 'success',
                                    timer: 2000,
                                    showConfirmButton: false,
                                });
                            },
                        },
                    );
                }
            });
        };

        const bulkToggleActive = () => {
            if (selectedStudents.value.length === 0) return;

            const selectedData = props.students.data.filter((s) =>
                selectedStudents.value.includes(s.id),
            );
            const allActive = selectedData.every((s) => s.is_active);
            const allInactive = selectedData.every((s) => !s.is_active);

            let action = '';
            let actionText = '';

            if (allActive) {
                action = 'nonaktifkan';
                actionText = 'menonaktifkan';
            } else if (allInactive) {
                action = 'aktifkan';
                actionText = 'mengaktifkan';
            } else {
                action = 'toggle';
                actionText = 'mengubah status';
            }

            Swal.fire({
                title: `Ubah Status Siswa?`,
                text: `Anda akan ${actionText} ${selectedStudents.value.length} siswa.`,
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Ya, Ubah!',
            }).then((result) => {
                if (result.isConfirmed) {
                    router.post(
                        '/admin/students/bulk-toggle-active',
                        {
                            student_ids: selectedStudents.value,
                            action: action,
                        },
                        {
                            onSuccess: () => {
                                selectedStudents.value = [];
                                Swal.fire({
                                    title: 'Success!',
                                    text: 'Status siswa berhasil diubah.',
                                    icon: 'success',
                                    timer: 2000,
                                    showConfirmButton: false,
                                });
                            },
                        },
                    );
                }
            });
        };

        return {
            search,
            selectedStudents,
            allSelected,
            selectAll,
            handleSearch,
            formatDate,
            destroy,
            bulkDelete,
            bulkToggleActive,
        };
    },
};
</script>
