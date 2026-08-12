<template>
    <Head>
        <title>Tambah Mata Pelajaran - Buweuk Sipit Academy</title>
    </Head>
    <div class="container-fluid mt-5 mb-5">
        <div class="row">
            <div class="col-md-12">
                <Link
                    href="/admin/lessons"
                    class="btn btn-md btn-primary mb-3 border-0 shadow"
                    type="button"
                >
                    <i class="fa fa-chevron-left me-2"></i> Kembali
                </Link>
                <div class="card border-0 shadow">
                    <div class="card-body">
                        <h5><i class="fa fa-bookmark"></i> Tambah Mapel</h5>
                        <hr />
                        <form @submit.prevent="submit">
                            <div class="mb-4">
                                <label>Nama Mapel</label>
                                <input
                                    type="text"
                                    class="form-control"
                                    placeholder="Masukkan Nama Mapel"
                                    v-model="form.name"
                                />
                                <div
                                    v-if="errors.name"
                                    class="alert alert-danger mt-2"
                                >
                                    {{ errors.name }}
                                </div>
                            </div>

                            <div class="mb-4">
                                <label>Kategori</label>
                                <select
                                    class="form-select"
                                    v-model="form.category"
                                >
                                    <option value="psikologi">Psikologi</option>
                                    <option value="akademik">Akademik</option>
                                </select>
                                <div
                                    v-if="errors.category"
                                    class="alert alert-danger mt-2"
                                >
                                    {{ errors.category }}
                                </div>
                            </div>

                            <div class="mb-4">
                                <label class="form-label">Thumbnail Mapel</label>
                                <input
                                    type="file"
                                    class="form-control"
                                    accept="image/jpeg,image/png,image/webp"
                                    @change="handleThumbnail"
                                />
                                <small class="text-muted">JPG, PNG, atau WebP. Maksimal 5 MB.</small>
                                <div v-if="thumbnailPreview" class="lesson-thumbnail-preview mt-3">
                                    <img :src="thumbnailPreview" alt="Preview thumbnail mapel" />
                                </div>
                                <div v-if="errors.thumbnail" class="alert alert-danger mt-2">
                                    {{ errors.thumbnail }}
                                </div>
                            </div>

                            <button
                                type="submit"
                                class="btn btn-md btn-primary me-2 border-0 shadow"
                            >
                                Simpan
                            </button>
                            <button
                                type="reset"
                                class="btn btn-md btn-warning border-0 shadow"
                            >
                                Reset
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import LayoutAdmin from '../../../Layouts/Admin.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { reactive, ref } from 'vue';
import Swal from 'sweetalert2';

export default {
    layout: LayoutAdmin,
    components: { Head, Link },
    props: {
        errors: Object,
    },
    setup() {
        const form = reactive({
            name: '',
            category: 'psikologi',
            thumbnail: null,
        });

        const thumbnailPreview = ref(null);

        const handleThumbnail = (event) => {
            const file = event.target.files?.[0] || null;
            form.thumbnail = file;
            thumbnailPreview.value = null;

            if (!file) return;

            const reader = new FileReader();
            reader.onload = (loadEvent) => {
                thumbnailPreview.value = loadEvent.target?.result || null;
            };
            reader.readAsDataURL(file);
        };

        const submit = () => {
            router.post('/admin/lessons', form, {
                forceFormData: true,
                onSuccess: () => {
                    Swal.fire({
                        title: 'Success!',
                        text: 'Mapel Berhasil Disimpan!.',
                        icon: 'success',
                        showConfirmButton: false,
                        timer: 2000,
                    });
                },
            });
        };

        return { form, submit, thumbnailPreview, handleThumbnail };
    },
};
</script>

<style scoped>
.lesson-thumbnail-preview {
    width: min(100%, 420px);
    aspect-ratio: 16 / 7;
    overflow: hidden;
    border: 1px solid #e2e8f0;
    border-radius: 12px;
}

.lesson-thumbnail-preview img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}
</style>
