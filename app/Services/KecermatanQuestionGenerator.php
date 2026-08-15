<?php

namespace App\Services;

use App\Models\KecermatanExam;
use App\Models\KecermatanMasterQuestion;
use Illuminate\Support\Facades\DB;

class KecermatanQuestionGenerator
{
    // Referensi TETAP per kolom (5 item untuk 50 soal)
    // ANGKA - Referensi per kolom
    private const ANGKA_REFERENCES = [
        1 => ['3', '9', '5', '4', '1'],  // Kolom 1
        2 => ['7', '3', '9', '1', '2'],  // Kolom 2
        3 => ['6', '2', '4', '1', '3'],  // Kolom 3
        4 => ['5', '0', '7', '3', '9'],  // Kolom 4
        5 => ['1', '3', '9', '5', '7'],  // Kolom 5
        6 => ['8', '4', '2', '6', '0'],  // Kolom 6
        7 => ['9', '7', '1', '5', '3'],  // Kolom 7
        8 => ['4', '8', '6', '2', '0'],  // Kolom 8
        9 => ['2', '5', '8', '3', '1'],  // Kolom 9
        10 => ['0', '6', '4', '9', '7'], // Kolom 10
    ];

    // HURUF - Referensi per kolom (TEMPLATE - bisa diubah)
    private const HURUF_REFERENCES = [
        1 => ['W', 'T', 'E', 'Y', 'Q'],  // Kolom 1
        2 => ['C', 'V', 'B', 'G', 'F'],  // Kolom 2
        3 => ['J', 'G', 'H', 'T', 'Y'],  // Kolom 3
        4 => ['O', 'J', 'K', 'U', 'I'],  // Kolom 4
        5 => ['Q', 'X', 'Z', 'S', 'A'],  // Kolom 5
        6 => ['W', 'T', 'E', 'Y', 'Q'],  // Kolom 1
        7 => ['C', 'V', 'B', 'G', 'F'],  // Kolom 2
        8 => ['J', 'G', 'H', 'T', 'Y'],  // Kolom 3
        9 => ['O', 'J', 'K', 'U', 'I'],  // Kolom 4
        10 => ['Q', 'X', 'Z', 'S', 'A'],  // Kolom 5
    ];

    // SIMBOL - Referensi per kolom (TEMPLATE - bisa diubah)
    private const SIMBOL_REFERENCES = [
        1 => ['Π', '┘', '∆', 'X', '┌'],     // Kolom 1
        2 => ['Ψ', '𝜓', '𝜙', 'Ω', 'ℵ'],     // Kolom 2 - ganti � dengan Ψ
        3 => ['Σ', '∋', '¶', 'Π', 'Φ'],     // Kolom 3 - ganti � dengan Σ dan Φ
        4 => ['⊓', '⋀', '⊨', '⋙', 'ℵ'],     // Kolom 4 - ganti � dengan ℵ
        5 => ['∆', '┌', '¶', '⋙', '⊠'],     // Kolom 5
        6 => ['Π', '┘', '∆', 'X', '┌'],     // Kolom 6 (sama dengan kolom 1)
        7 => ['Ψ', '𝜓', '𝜙', 'Ω', 'ℵ'],     // Kolom 7 (sama dengan kolom 2)
        8 => ['Σ', '∋', '¶', 'Π', 'Φ'],     // Kolom 8 (sama dengan kolom 3)
        9 => ['⊓', '⋀', '⊨', '⋙', 'ℵ'],     // Kolom 9 (sama dengan kolom 4)
        10 => ['∆', '┌', '¶', '⋙', '⊠'],    // Kolom 10 (sama dengan kolom 5)
    ];

    // GAMBAR - Referensi per kolom (TETAP seperti yang sudah ada)
    private const GAMBAR_REFERENCES = [
        1 => ['😀', '😁', '😂', '😉', '😍'],  // Kolom 1: Emoji Wajah
        2 => ['🐆', '🐎', '🐕', '🐒', '🦏'],  // Kolom 2: Hewan
        3 => ['🚓', '🛺', '✈️', '🚲', '🛥️'],  // Kolom 3: Kendaraan
        4 => ['✏️', '📗', '📷', '💻', '⏱️'],  // Kolom 4: Alat/Benda
        5 => ['🍎', '🥑', '🍄', '🥕', '🌺'],  // Kolom 5: Makanan/Tanaman
        6 => ['😀', '😁', '😂', '😉', '😍'],  // Kolom 1: Emoji Wajah
        7 => ['🐆', '🐎', '🐕', '🐒', '🦏'],  // Kolom 2: Hewan
        8 => ['🚓', '🛺', '✈️', '🚲', '🛥️'],  // Kolom 3: Kendaraan
        9 => ['✏️', '📗', '📷', '💻', '⏱️'],  // Kolom 4: Alat/Benda
        10 => ['🍎', '🥑', '🍄', '🥕', '🌺'],  // Kolom 5: Makanan/Tanaman
    ];

    /**
     * Generate 2000 soal master untuk ujian
     *
     * @param KecermatanExam $exam
     * @return void
     */
    public function generate(KecermatanExam $exam): void
    {
        $types = ['huruf', 'angka', 'simbol', 'gambar'];

        // Loop 4 tipe
        foreach ($types as $type) {
            $this->generateForType($exam->id, $type);
        }
    }

    /**
     * Pastikan soal master tersedia untuk exam (idempotent & race-safe).
     *
     * Hanya generate tipe yang belum ada, sehingga aman dipanggil dari queue
     * job maupun on-demand saat siswa mulai ujian (fallback jika queue belum
     * sempat memproses). Row lock dipakai untuk mencegah duplikasi saat dua
     * proses mencoba generate secara bersamaan.
     *
     * @param KecermatanExam $exam
     * @param string|null $type Hanya generate tipe tertentu (mis. 'angka')
     * @return void
     */
    public function ensureGenerated(KecermatanExam $exam, ?string $type = null): void
    {
        $types = $type !== null ? [$type] : ['huruf', 'angka', 'simbol', 'gambar'];

        DB::transaction(function () use ($exam, $types) {
            // Lock baris exam agar dua proses tidak generate bersamaan (duplikasi)
            $lockedExam = KecermatanExam::query()
                ->whereKey($exam->id)
                ->lockForUpdate()
                ->firstOrFail();

            foreach ($types as $t) {
                $exists = DB::table('kecermatan_master_questions')
                    ->where('kecermatan_exam_id', $lockedExam->id)
                    ->where('exam_type', $t)
                    ->exists();

                if (!$exists) {
                    $this->generateForType($lockedExam->id, $t);
                }
            }
        }, 3);
    }

    /**
     * Generate 500 soal untuk 1 tipe
     *
     * @param int $examId
     * @param string $type
     * @return void
     */
    private function generateForType(int $examId, string $type): void
    {
        $questions = [];

        // Loop 10 kolom
        for ($column = 1; $column <= 10; $column++) {
            // Generate referensi untuk kolom ini
            $reference = $this->generateReference($type, $column);

            // Generate 50 soal UNIK untuk kolom ini.
            //
            // Sebelumnya posisi item yang hilang dan urutan 4 item sisanya dipilih
            // acak per soal (rand + shuffle), sehingga kombinasi (posisi hilang +
            // urutan) bisa terulang dan menghasilkan soal yang SAMA PERSIS.
            // Sekarang semua varian yang mungkin dibangun dulu (5 posisi hilang x
            // 24 permutasi urutan = 120 varian), lalu diambil 10 soal per posisi
            // hilang dengan urutan yang dijamin berbeda. Hasil: 50 soal per kolom
            // semuanya unik, dan distribusi jawaban A-E tetap merata (masing-masing
            // tepat 10 kali per kolom).
            $selected = [];
            for ($missingPosition = 0; $missingPosition < 5; $missingPosition++) {
                // Buat question sequence (4 item, tanpa yang hilang)
                $remaining = [];
                for ($i = 0; $i < 5; $i++) {
                    if ($i !== $missingPosition) {
                        $remaining[] = $reference[$i];
                    }
                }

                // Semua urutan yang mungkin dari 4 item ini (24 permutasi)
                $permutations = $this->permutations($remaining);
                shuffle($permutations);

                // Ambil 10 soal dengan urutan berbeda untuk posisi hilang ini
                foreach (array_slice($permutations, 0, 10) as $questionSequence) {
                    $selected[] = [
                        'missing_position' => $missingPosition,
                        'question_sequence' => $questionSequence,
                    ];
                }
            }

            // Acak urutan 50 soal dalam kolom agar tidak mengelompok per posisi
            shuffle($selected);

            foreach ($selected as $questionNum => $variant) {
                $missingPosition = $variant['missing_position'];
                $missingItem = $reference[$missingPosition];

                $questions[] = [
                    'kecermatan_exam_id' => $examId,
                    'exam_type' => $type,
                    'column_number' => $column,
                    'question_number' => $questionNum + 1,
                    'reference_sequence' => json_encode($reference),
                    'question_sequence' => json_encode($variant['question_sequence']),
                    'missing_position' => $missingPosition,
                    'missing_item' => $missingItem,
                    'correct_answer' => chr(65 + $missingPosition), // 0->A, 1->B, 2->C, 3->D, 4->E
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
        }

        // Batch insert 500 soal sekaligus
        DB::table('kecermatan_master_questions')->insert($questions);
    }

    /**
     * Generate referensi untuk kolom (TETAP untuk 50 soal)
     *
     * @param string $type
     * @param int $column
     * @return array
     */
    private function generateReference(string $type, int $column): array
    {
        switch ($type) {
            case 'huruf':
                return self::HURUF_REFERENCES[$column];

            case 'angka':
                return self::ANGKA_REFERENCES[$column];

            case 'simbol':
                return self::SIMBOL_REFERENCES[$column];

            case 'gambar':
                return self::GAMBAR_REFERENCES[$column];

            default:
                return [];
        }
    }

    /**
     * Semua permutasi urutan dari sebuah array item.
     *
     * Untuk 4 item menghasilkan 24 permutasi. Dipakai agar setiap soal dalam
     * satu kolom punya urutan item yang dijamin berbeda (tidak ada soal kembar).
     *
     * @param array $items
     * @return array
     */
    private function permutations(array $items): array
    {
        if (count($items) <= 1) {
            return [$items];
        }

        $result = [];
        foreach ($items as $key => $item) {
            $rest = $items;
            array_splice($rest, $key, 1);
            foreach ($this->permutations($rest) as $perm) {
                $result[] = array_merge([$item], $perm);
            }
        }
        return $result;
    }

    /**
     * Get random items from pool (unique)
     * DEPRECATED - Tidak digunakan lagi karena referensi sudah TETAP per kolom
     *
     * @param array $pool
     * @param int $count
     * @return array
     */
    private function getRandomItems(array $pool, int $count): array
    {
        $shuffled = $pool;
        shuffle($shuffled);
        return array_slice($shuffled, 0, $count);
    }

    /**
     * Shuffle dan copy soal master ke questions untuk session siswa
     * Optimized: Direct SQL with random order per column
     *
     * Urutan kolom diacak PER SESSION agar tiap siswa mendapat urutan berbeda:
     * kolom 1-5 diacak sendiri, kolom 6-10 diacak sendiri (duplikasi set
     * referensi 6-10 tetap dipertahankan). Dipastikan juga dua kolom yang
     * berurutan tidak membawa set referensi yang sama persis (kolom N dan N+5
     * identik), supaya tidak muncul kolom "double" berdampingan.
     *
     * @param int $sessionId
     * @param int $examId
     * @param string $type
     * @return void
     */
    public function shuffleAndCopyForSession(int $sessionId, int $examId, string $type): void
    {
        // Optimized: Generate data in PHP and do chunk inserts, much faster than large SQL string building
        $masterQuestions = DB::table('kecermatan_master_questions')
            ->where('kecermatan_exam_id', $examId)
            ->where('exam_type', $type)
            ->get();

        // Acak urutan kolom per session: paruh pertama (kolom 1-5) dan paruh
        // kedua (kolom 6-10) masing-masing diacak terpisah, lalu digabung.
        $firstHalf = range(1, 5);
        $secondHalf = range(6, 10);
        shuffle($firstHalf);
        shuffle($secondHalf);

        // Set referensi per kolom master (untuk deteksi kolom kembar).
        $referenceByColumn = $masterQuestions
            ->keyBy('column_number')
            ->map(fn ($q) => json_encode(json_decode($q->reference_sequence, true)));

        // Kolom N dan kolom N+5 memiliki set referensi yang SAMA PERSIS.
        // Jika kolom terakhir paruh pertama dan kolom pertama paruh kedua
        // berbagi set yang sama, geser paruh kedua sampai batas antar-paruh
        // aman (tidak ada dua kolom berurutan dengan set identik).
        while (
            $referenceByColumn->get($firstHalf[4])
            === $referenceByColumn->get($secondHalf[0])
        ) {
            $secondHalf[] = array_shift($secondHalf);
        }

        $columnOrder = array_merge($firstHalf, $secondHalf);

        $inserts = [];
        $now = now();

        // Salin soal per kolom sesuai urutan acak. column_number pada soal sesi
        // dipetakan ke posisi tampilan (1-10), bukan nomor kolom master, agar
        // frontend/result/laporan admin tetap memakai kolom 1-10 seperti biasa.
        foreach ($columnOrder as $position => $masterColumn) {
            $sessionColumn = $position + 1;
            $colQuestions = $masterQuestions->where('column_number', $masterColumn)->shuffle()->values();
            foreach ($colQuestions as $idx => $q) {
                $inserts[] = [
                    'session_id' => $sessionId,
                    'master_question_id' => $q->id,
                    'column_number' => $sessionColumn,
                    'question_number' => $q->question_number,
                    'shuffled_order' => $idx + 1,
                    'reference_sequence' => $q->reference_sequence,
                    'question_sequence' => $q->question_sequence,
                    'missing_position' => $q->missing_position,
                    'missing_item' => $q->missing_item,
                    'correct_answer' => $q->correct_answer,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
        }

        // Batch insert in chunks of 500 to avoid query size limits (though 500 is very safe)
        foreach (array_chunk($inserts, 500) as $chunk) {
            DB::table('kecermatan_questions')->insert($chunk);
        }
    }

    /**
     * Calculate hasil untuk kolom tertentu
     *
     * @param int $sessionId
     * @param int $columnNumber
     * @return array
     */
    public function calculateColumnResult(int $sessionId, int $columnNumber): array
    {
        $questions = DB::table('kecermatan_questions')
            ->where('session_id', $sessionId)
            ->where('column_number', $columnNumber)
            ->get();

        $correct = 0;
        $wrong = 0;
        $unanswered = 0;
        $timeSpent = 0;

        foreach ($questions as $q) {
            if ($q->student_answer === null) {
                $unanswered++;
            } elseif ($q->is_correct) {
                $correct++;
            } else {
                $wrong++;
            }
            // Nilai setiap jawaban bersifat kumulatif sejak awal kolom.
            $timeSpent = max($timeSpent, (int) $q->time_spent);
        }

        return [
            'correct_count' => $correct,
            'wrong_count' => $wrong,
            'unanswered_count' => $unanswered,
            'time_spent' => min(60, $timeSpent),
        ];
    }
}
