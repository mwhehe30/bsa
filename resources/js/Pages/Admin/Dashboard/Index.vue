<template>
    <Head>
        <title>Dashboard - Buweuk Sipit Academy</title>
    </Head>
    <div class="container-fluid mt-5 mb-5">
        <div class="admin-page-heading mb-4">
            <h1 class="h4 fw-bold mb-1">Dashboard</h1>
            <p class="text-muted mb-0">Ringkasan data Buweuk Sipit Academy</p>
        </div>
        <div class="row">
            <div class="col-12 col-sm-6 col-xl-6 mb-4">
                <div class="card border-0 shadow">
                    <div class="card-body">
                        <div class="row d-block d-xl-flex align-items-center">
                            <div
                                class="col-12 col-xl-5 text-xl-center mb-xl-0 d-flex align-items-center justify-content-xl-center mb-3"
                            >
                                <div
                                    class="icon-shape icon-shape-success me-sm-0 me-4 rounded"
                                >
                                    <svg
                                        xmlns="http://www.w3.org/2000/svg"
                                        fill="currentColor"
                                        class="bi bi-people-fill icon icon-md"
                                        viewBox="0 0 16 16"
                                    >
                                        <path
                                            d="M7 14s-1 0-1-1 1-4 5-4 5 3 5 4-1 1-1 1H7zm4-6a3 3 0 1 0 0-6 3 3 0 0 0 0 6z"
                                        />
                                        <path
                                            fill-rule="evenodd"
                                            d="M5.216 14A2.238 2.238 0 0 1 5 13c0-1.355.68-2.75 1.936-3.72A6.325 6.325 0 0 0 5 9c-4 0-5 3-5 4s1 1 1 1h4.216z"
                                        />
                                        <path
                                            d="M4.5 8a2.5 2.5 0 1 0 0-5 2.5 2.5 0 0 0 0 5z"
                                        />
                                    </svg>
                                </div>
                                <div class="d-sm-none">
                                    <h2 class="h5">Siswa</h2>
                                    <h3 class="fw-extrabold mb-1">
                                        {{ students }}
                                    </h3>
                                </div>
                            </div>
                            <div class="col-12 col-xl-7 px-xl-0">
                                <div class="d-none d-sm-block">
                                    <h2 class="h5">Siswa</h2>
                                    <h3 class="fw-extrabold mb-1">
                                        {{ students }}
                                    </h3>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-12 col-sm-6 col-xl-6 mb-4">
                <div class="card border-0 shadow">
                    <div class="card-body">
                        <div class="row d-block d-xl-flex align-items-center">
                            <div
                                class="col-12 col-xl-5 text-xl-center mb-xl-0 d-flex align-items-center justify-content-xl-center mb-3"
                            >
                                <div
                                    class="icon-shape icon-shape-tertiary me-sm-0 me-4 rounded"
                                >
                                    <svg
                                        xmlns="http://www.w3.org/2000/svg"
                                        fill="currentColor"
                                        class="bi bi-pencil-square icon icon-md"
                                        viewBox="0 0 16 16"
                                    >
                                        <path
                                            d="M15.502 1.94a.5.5 0 0 1 0 .706L14.459 3.69l-2-2L13.502.646a.5.5 0 0 1 .707 0l1.293 1.293zm-1.75 2.456-2-2L4.939 9.21a.5.5 0 0 0-.121.196l-.805 2.414a.25.25 0 0 0 .316.316l2.414-.805a.5.5 0 0 0 .196-.12l6.813-6.814z"
                                        />
                                        <path
                                            fill-rule="evenodd"
                                            d="M1 13.5A1.5 1.5 0 0 0 2.5 15h11a1.5 1.5 0 0 0 1.5-1.5v-6a.5.5 0 0 0-1 0v6a.5.5 0 0 1-.5.5h-11a.5.5 0 0 1-.5-.5v-11a.5.5 0 0 1 .5-.5H9a.5.5 0 0 0 0-1H2.5A1.5 1.5 0 0 0 1 2.5v11z"
                                        />
                                    </svg>
                                </div>
                                <div class="d-sm-none">
                                    <h2 class="h5">Ujian</h2>
                                    <h3 class="fw-extrabold mb-1">
                                        {{ exams }}
                                    </h3>
                                </div>
                            </div>
                            <div class="col-12 col-xl-7 px-xl-0">
                                <div class="d-none d-sm-block">
                                    <h2 class="h5">Ujian</h2>
                                    <h3 class="fw-extrabold mb-1">
                                        {{ exams }}
                                    </h3>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import LayoutAdmin from "../../../Layouts/Admin.vue";
import { Head } from "@inertiajs/vue3";

export default {
    layout: LayoutAdmin,
    components: { Head },
    props: {
        students: Number,
        exams: Number,
    },
};
</script>

<style></style>
