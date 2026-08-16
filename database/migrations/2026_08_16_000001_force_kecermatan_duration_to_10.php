<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Normalisasi durasi ujian kecermatan menjadi tepat 10 menit.
     *
     * Sebelumnya nilainya tidak konsisten: 600 (10x60 detik) dari form admin,
     * 10 (menit) dari salinan ujian reguler, atau null dari pembuatan otomatis
     * dashboard. Model KecermatanExam kini memaksa 10 di setiap penyimpanan,
     * dan data lama disamakan di sini.
     */
    public function up(): void
    {
        DB::table('kecermatan_exams')->update(['duration' => 10]);
    }

    public function down(): void
    {
        // Nilai lama tidak dapat dikembalikan secara pasti; tidak ada aksi.
    }
};
