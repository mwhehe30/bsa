<template>
    <Head>
        <title>Siswa Terisolir - Buweuk Sipit Academy</title>
    </Head>
    <div class="container-fluid mt-5 mb-5">
        <div class="row">
            <div class="col-md-8">
                <div class="row">
                    <div class="col-md-5 col-12 mb-2">
                        <div class="row">
                            <div class="col-md-6 col-12 mb-2">
                                <Link
                                    href="/admin/students"
                                    class="btn btn-md btn-primary w-100 border-0 shadow"
                                    type="button"
                                    ><i class="fa fa-chevron-left me-2"></i>
                                    Kembali</Link
                                >
                            </div>
                            <div class="col-md-6 col-12 mb-2">
                                <button
                                    v-if="selectedStudents.length > 0"
                                    @click.prevent="bulkActivate"
                                    class="btn btn-md btn-success w-100 border-0 text-white shadow"
                                >
                                    <i class="fa fa-check-circle"></i> Aktifkan
                                    ({{ selectedStudents.length }})
                                </button>
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
                                        <th
                                            class="rounded-end border-0"
                                            style="width: 10%"
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
                                        <td class="text-center">
                                            <button
                                                @click.prevent="
                                                    activateSingle(student.id)
                                                "
                                                class="btn btn-sm btn-success border-0 text-white shadow"
                                                title="Buka Isolir"
                                            >
                                                <i class="fa fa-unlock"></i>
                                                Keluarkan dari isolir
                                            </button>
                                        </td>
                                    </tr>
                                    <tr v-if="students.data.length === 0">
                                        <td
                                            colspan="6"
                                            class="text-muted py-3 text-center"
                                        >
                                            <i
                                                class="fa fa-info-circle me-2"
                                            ></i>
                                            Tidak ada siswa yang terisolir.
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
import { ref, computed, onMounted, onBeforeUnmount } from 'vue';
import Swal from 'sweetalert2';

export default {
    layout: LayoutAdmin,
    components: { Head, Link, Pagination },
    props: {
        students: Object,
    },
    setup(props) {
        const search = ref(
            '' || new URL(document.location).searchParams.get('q') || '',
        );
        const selectedStudents = ref([]);
        let reloadInterval = null;

        onMounted(() => {
            // Polling data setiap 3 detik untuk realtime
            reloadInterval = setInterval(() => {
                // Hanya auto-reload jika admin tidak sedang mencari (search kosong)
                // dan tidak sedang memilih siswa (checkbox kosong)
                if (!search.value && selectedStudents.value.length === 0) {
                    router.reload({ 
                        only: ['students'], 
                        preserveScroll: true, 
                        preserveState: true 
                    });
                }
            }, 3000);
        });

        onBeforeUnmount(() => {
            if (reloadInterval) {
                clearInterval(reloadInterval);
            }
        });

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

        const handleSearch = () => {
            router.get('/admin/students/isolated', { q: search.value });
        };

        const activateSingle = (id) => {
            Swal.fire({
                title: 'Buka Isolir Siswa?',
                text: 'Siswa akan dapat melanjutkan ujian kembali.',
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#28a745',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Ya, Buka!',
            }).then((result) => {
                if (result.isConfirmed) {
                    router.put(
                        `/admin/students/${id}/toggle-active`,
                        { is_active: true },
                        {
                            onSuccess: () => {
                                Swal.fire({
                                    title: 'Success!',
                                    text: 'Siswa berhasil dibuka isolirnya.',
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

        const bulkActivate = () => {
            if (selectedStudents.value.length === 0) return;

            Swal.fire({
                title: 'Buka Isolir Siswa?',
                text: `Anda akan membuka isolir ${selectedStudents.value.length} siswa.`,
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#28a745',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Ya, Buka Semua!',
            }).then((result) => {
                if (result.isConfirmed) {
                    router.post(
                        '/admin/students/bulk-activate',
                        {
                            student_ids: selectedStudents.value,
                        },
                        {
                            onSuccess: () => {
                                selectedStudents.value = [];
                                Swal.fire({
                                    title: 'Success!',
                                    text: 'Siswa berhasil dibuka isolirnya.',
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
            activateSingle,
            bulkActivate,
        };
    },
};
</script>
