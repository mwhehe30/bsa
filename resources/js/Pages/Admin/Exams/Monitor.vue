<template>
    <Head>
        <title>Monitor Ujian - Buweuk Sipit Academy</title>
    </Head>
    <div class="container-fluid mt-5 mb-5">
        <div class="row mb-3">
            <div class="col-md-12">
                <div class="card rounded-3 border-0 shadow-sm">
                    <div class="card-body">
                        <div class="row align-items-center mb-4">
                            <div class="col-md-6 mb-md-0 mb-3">
                                <h5 class="fw-bold mb-0">
                                    <i
                                        class="fa fa-desktop text-primary me-2"
                                    ></i>
                                    Monitor Siswa Ujian
                                </h5>
                            </div>
                            <div class="col-md-6">
                                <div
                                    class="d-flex justify-content-md-end align-items-center gap-3"
                                >
                                    <div
                                        v-if="selectedExamId"
                                        class="badge bg-light text-primary border p-2 shadow-sm"
                                    >
                                        <i
                                            class="fa fa-sync fa-spin text-primary me-1"
                                        ></i>
                                        Auto-refresh
                                    </div>
                                    <div style="min-width: 250px">
                                        <select
                                            v-model="currentExamId"
                                            @change="changeExam"
                                            class="form-select border-0 shadow-sm"
                                        >
                                            <option value="">
                                                -- Pilih Ujian untuk Dimonitor
                                                --
                                            </option>
                                            <option
                                                v-for="e in allExams"
                                                :key="e.id"
                                                :value="e.id"
                                            >
                                                {{ e.title }} ({{
                                                    e.lesson.name
                                                }})
                                            </option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div v-if="!selectedExamId" class="py-5 text-center">
                            <i class="fa fa-arrow-up fa-3x text-muted mb-3"></i>
                            <h5 class="text-muted">
                                Silakan pilih ujian terlebih dahulu pada
                                dropdown di atas
                            </h5>
                        </div>

                        <div v-else class="table-responsive">
                            <table
                                class="table-bordered table-centered table-nowrap mb-0 table rounded"
                            >
                                <thead class="table-dark">
                                    <tr>
                                        <th style="width: 5%">No.</th>
                                        <th>Nama Siswa</th>
                                        <th>Email</th>
                                        <th>Status Pengerjaan</th>
                                        <th>Mulai</th>
                                        <th>Selesai</th>
                                        <th>Nilai/Skor</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-if="monitorData.length === 0">
                                        <td
                                            colspan="8"
                                            class="text-muted py-4 text-center"
                                        >
                                            Tidak ada siswa yang terdaftar untuk
                                            ujian ini.
                                        </td>
                                    </tr>
                                    <tr
                                        v-for="(data, index) in monitorData"
                                        :key="index"
                                    >
                                        <td class="text-center">
                                            {{ index + 1 }}
                                        </td>
                                        <td class="fw-bold">
                                            {{ data.student.name }}
                                        </td>
                                        <td>{{ data.student.email }}</td>
                                        <td class="text-center">
                                            <span
                                                v-if="
                                                    data.status ===
                                                    'belum_mulai'
                                                "
                                                class="badge bg-secondary px-3 py-2"
                                            >
                                                {{ data.label }}
                                            </span>
                                            <span
                                                v-else-if="
                                                    data.status ===
                                                    'sedang_mengerjakan'
                                                "
                                                class="badge bg-warning text-dark px-3 py-2"
                                            >
                                                <i
                                                    class="fa fa-spinner fa-spin me-1"
                                                ></i>
                                                {{ data.label }}
                                            </span>
                                            <span
                                                v-else
                                                class="badge bg-success px-3 py-2"
                                            >
                                                <i class="fa fa-check me-1"></i>
                                                {{ data.label }}
                                            </span>
                                        </td>
                                        <td>
                                            {{ formatTime(data.start_time) }}
                                        </td>
                                        <td>{{ formatTime(data.end_time) }}</td>
                                        <td class="fw-bold text-center">
                                            {{
                                                data.score !== null
                                                    ? data.score
                                                    : "-"
                                            }}
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
import LayoutAdmin from "../../../Layouts/Admin.vue";
import { Head, Link, router } from "@inertiajs/vue3";
import { onMounted, onUnmounted, ref } from "vue";

export default {
    layout: LayoutAdmin,
    components: { Head, Link },
    props: {
        allExams: Array,
        selectedExamId: [String, Number],
        exam: Object,
        monitorData: Array,
    },
    setup(props) {
        let intervalId = null;
        const currentExamId = ref(props.selectedExamId || "");

        onMounted(() => {
            // Auto refresh setiap 10 detik jika ada ujian yang dipilih
            if (props.selectedExamId) {
                intervalId = setInterval(() => {
                    router.reload({ only: ["monitorData"] });
                }, 10000);
            }
        });

        onUnmounted(() => {
            if (intervalId) {
                clearInterval(intervalId);
            }
        });

        const changeExam = () => {
            if (currentExamId.value) {
                router.get("/admin/monitor", { exam_id: currentExamId.value });
            } else {
                router.get("/admin/monitor");
            }
        };

        const formatTime = (datetime) => {
            if (!datetime) return "-";
            const date = new Date(datetime);
            return date.toLocaleTimeString("id-ID", {
                hour: "2-digit",
                minute: "2-digit",
                second: "2-digit",
            });
        };

        return { formatTime, currentExamId, changeExam };
    },
};
</script>

<style scoped>
.table th {
    font-weight: 600;
}
.badge {
    font-size: 0.85rem;
}
</style>
