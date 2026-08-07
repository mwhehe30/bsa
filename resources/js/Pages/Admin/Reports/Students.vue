<template>
    <Head>
        <title>Cetak Rapor Siswa - Buweuk Sipit Academy</title>
    </Head>
    <div class="container-fluid mb-5 mt-5">
        <div class="row">
            <div class="col-md-8">
                <div class="row">
                    <div class="col-md-9 col-12 mb-2">
                        <div class="row">
                            <div class="col-md-6 col-12 mb-2">
                                <form @submit.prevent="handleSearch">
                                    <div class="input-group">
                                        <input type="text" class="form-control border-0 shadow" v-model="search" placeholder="Cari nama atau email...">
                                        <span class="input-group-text border-0 shadow">
                                            <i class="fa fa-search"></i>
                                        </span>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-4 text-end">
                <Link href="/admin/reports" class="btn btn-secondary shadow border-0 me-2" type="button">
                    <i class="fa fa-arrow-left"></i> Kembali ke Nilai
                </Link>
                <a 
                    v-if="selectedStudents.length > 0" 
                    :href="`/admin/reports/students/select-exams?students=${selectedStudents.join(',')}`" 
                    class="btn btn-primary shadow border-0"
                >
                    <i class="fa fa-arrow-right"></i> Pilih Ujian & Cetak ({{ selectedStudents.length }} siswa)
                </a>
                <button v-else class="btn btn-primary shadow border-0" disabled>
                    <i class="fa fa-print"></i> Pilih Ujian & Cetak
                </button>
            </div>
        </div>

        <div class="row mt-1">
            <div class="col-md-12">
                <div class="card border-0 shadow">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-bordered table-centered table-nowrap mb-0 rounded">
                                <thead class="thead-dark">
                                    <tr class="border-0">
                                        <th class="border-0 rounded-start" style="width: 5%">
                                            <div class="form-check">
                                                <input 
                                                    class="form-check-input" 
                                                    type="checkbox" 
                                                    id="selectAll" 
                                                    @change="toggleSelectAll" 
                                                    :checked="isAllSelected"
                                                >
                                            </div>
                                        </th>
                                        <th class="border-0" style="width: 5%">No.</th>
                                        <th class="border-0">Nama Siswa</th>
                                        <th class="border-0">Email</th>
                                        <th class="border-0 rounded-end">Jenis Kelamin</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="(student, index) in students.data" :key="index">
                                        <td>
                                            <div class="form-check">
                                                <input 
                                                    class="form-check-input" 
                                                    type="checkbox" 
                                                    :value="student.id" 
                                                    v-model="selectedStudents"
                                                >
                                            </div>
                                        </td>
                                        <td class="fw-bold text-center">
                                            {{ ++index + (students.current_page - 1) * students.per_page }}
                                        </td>
                                        <td>{{ student.name }}</td>
                                        <td>{{ student.email }}</td>
                                        <td>{{ student.gender ?? '-' }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <Pagination :links="students.links" align="end" />
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

    export default {
        layout: LayoutAdmin,
        components: {
            Head,
            Link,
            Pagination
        },
        props: {
            students: Object,
            filters: Object
        },
        setup(props) {
            const search = ref(props.filters.search || '');
            const selectedStudents = ref([]);

            const handleSearch = () => {
                router.get('/admin/reports/students', {
                    search: search.value,
                });
            }

            const isAllSelected = computed(() => {
                return props.students.data.length > 0 && selectedStudents.value.length === props.students.data.length;
            });

            const toggleSelectAll = (event) => {
                if (event.target.checked) {
                    selectedStudents.value = props.students.data.map(student => student.id);
                } else {
                    selectedStudents.value = [];
                }
            };
            
            const clearSelection = () => {
                setTimeout(() => {
                    selectedStudents.value = [];
                    document.getElementById('selectAll').checked = false;
                }, 1000);
            }
            
            // clear selection when data changes (e.g. pagination)
            watch(() => props.students.data, () => {
                selectedStudents.value = [];
            });

            return {
                search,
                handleSearch,
                selectedStudents,
                isAllSelected,
                toggleSelectAll,
                clearSelection
            }
        }
    }
</script>
