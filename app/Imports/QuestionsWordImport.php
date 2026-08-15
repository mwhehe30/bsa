<?php
namespace App\Imports;

use App\Helpers\ImageConverter;
use App\Models\Question;
use Illuminate\Http\UploadedFile;
use PhpOffice\PhpWord\Element\Image;
use PhpOffice\PhpWord\Element\ListItem;
use PhpOffice\PhpWord\Element\ListItemRun;
use PhpOffice\PhpWord\Element\Table;
use PhpOffice\PhpWord\Element\TextRun;
use PhpOffice\PhpWord\Element\Title;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\Shared\ZipArchive;
use Illuminate\Support\Facades\Log;

class QuestionsWordImport
{
    protected $exam_id;
    protected $isPersonality;
    protected $imgCounter = 1;
    protected $undetectedAnswers = [];
    protected $personalityPoints = [];

    /**
     * Format template (soal = list bernomor 1,2,3 + opsi = sub-list a,b,c,d,e)
     * menjadi prioritas. Aktif otomatis saat dokumen memakai struktur list
     * (ada list item depth-0 DAN depth-1). Saat aktif, paragraf biasa bernomor
     * ("1. langkah pertama...") di dalam pembahasan TIDAK dianggap soal baru
     * — soal hanya dari list item. Fallback tetap berjalan untuk dokumen yang
     * tidak memakai struktur list sama sekali.
     */
    protected $templateMode = false;

    /** Namespace OOXML main (w) — dipakai untuk menulis node XML */
    const NS_W = 'http://schemas.openxmlformats.org/wordprocessingml/2006/main';
    const NS_MC = 'http://schemas.openxmlformats.org/markup-compatibility/2006';

    // Mapping jawaban teks ke huruf
    protected $textAnswerMap = [
        'tidak ada kesimpulan' => 'E',
        'tidak ada' => 'E',
        'semua salah' => 'E',
        'tidak dapat ditentukan' => 'E',
        'belum tentu' => 'E',
    ];

    public function __construct($exam_id, $isPersonality = false)
    {
        $this->exam_id = $exam_id;
        $this->isPersonality = $isPersonality;
        $this->undetectedAnswers = [];
    }

    public function import(UploadedFile $file)
    {
        $preprocessedPath = null;
        try {
            // Beberapa file .docx (mis. hasil konversi) menaruh gambar di dalam
            // blok <mc:AlternateContent>. PHPWord mengabaikan blok tsb, sehingga
            // gambar hilang. Di sini kita "unwrap" blok tsb menjadi <w:r> biasa
            // sebelum dibaca PHPWord.
            $preprocessedPath = $this->preprocessDocx($file->getPathname());
            $phpWord = IOFactory::load($preprocessedPath);

            // Deteksi format template SEBELUM parsing: dokumen yang memakai
            // struktur list (soal depth-0 + opsi depth-1) diproses dengan mode
            // terstruktur sebagai prioritas.
            $this->templateMode = $this->detectTemplateMode($phpWord);

            $questions = [];
            $current = null;
            $seqOptIdx = 0;
            $optMap = [0 => 'A', 1 => 'B', 2 => 'C', 3 => 'D', 4 => 'E'];
            $prevWasQuestion = false;
            $explanationText = '';
            $hasOptions = false;
            $questionNumber = 0;
            $pendingImages = [];
            $answerFound = false;      // Jawaban sudah ditemukan untuk soal berjalan
            $isTableQuestion = false;  // Soal dibuat dari tabel (opsi lanjutan pendek)

            foreach ($phpWord->getSections() as $section) {
                foreach ($section->getElements() as $element) {
                    // 1. EKSTRAK GAMBAR DARI SEMUA ELEMEN (TERMASUK TABEL & TITLE)
                    $images = [];
                    $this->extractImages($element, $images);

                    // 2. EKSTRAK TEKS DARI SEMUA ELEMEN (TERMASUK TABEL & TITLE)
                    $text = $this->getElemText($element);

                    // 3. TABEL: bisa berisi opsi, pembahasan, atau soal itu sendiri
                    if ($element instanceof Table) {
                        $this->handleTable(
                            $element,
                            $current,
                            $questions,
                            $explanationText,
                            $hasOptions,
                            $seqOptIdx,
                            $optMap,
                            $questionNumber,
                            $prevWasQuestion,
                            $answerFound,
                            $isTableQuestion,
                            $pendingImages
                        );

                        // Gambar di dalam tabel -> bagian pembahasan
                        if (!empty($images) && $current !== null) {
                            foreach ($images as $img) {
                                $url = $this->saveImage($img);
                                if ($url) {
                                    $explanationText .= ' <img src="' . $url . '" style="max-width:100%;height:auto">';
                                }
                            }
                        }
                        continue;
                    }

                    // 4. LIST ITEM: soal (depth 0) atau opsi (depth >= 1)
                    if ($element instanceof ListItemRun || $element instanceof ListItem) {
                        $depth = method_exists($element, 'getDepth') ? $element->getDepth() : 0;

                        if ($depth == 0) {
                            $trimmedQuestion = trim($text);

                            // List item kosong di tengah dokumen = pemisah, bukan
                            // pertanyaan baru. Lewati agar tidak mengganggu status.
                            // Kecuali item berisi GAMBAR (soal bergambar tanpa teks)
                            // — itu adalah soal baru yang valid.
                            if ($trimmedQuestion === '' && empty($images)) {
                                continue;
                            }

                            // Item list angka pendek ("1 7", "1", "23") di tengah
                            // dokumen adalah sisa diagram/penomoran, bukan soal.
                            // Lewati agar tidak jadi pertanyaan sampah.
                            if (
                                $current !== null
                                && mb_strlen($trimmedQuestion) <= 5
                                && preg_match('/^[\d\s.]+$/', $trimmedQuestion)
                            ) {
                                continue;
                            }

                            // Kasus khusus: soal yang dibuat dari tabel + opsi lanjutan
                            // berupa list pendek (mis. "enke, dahga, danga").
                            if (
                                $isTableQuestion
                                && $current !== null
                                && $current['answer'] === null
                                && mb_strlen($trimmedQuestion) < 12
                            ) {
                                $prevWasQuestion = false;
                                $hasOptions = true;
                                $this->extractAnswerFromText($current, $text);
                                $this->parseOptionLine($this->cleanOptionText($text), $current['options'], $optMap, $seqOptIdx);
                                // Matikan mode ini setelah 5 opsi terisi, agar soal
                                // pendek berikutnya tidak ikut tertelan jadi opsi.
                                if (count(array_filter($current['options'])) >= 5) {
                                    $isTableQuestion = false;
                                }
                                continue;
                            }

                            // Simpan pertanyaan sebelumnya
                            if ($current !== null) {
                                $this->finalizeQuestion($current, $questions, $explanationText, $pendingImages, $hasOptions, $seqOptIdx, $optMap);
                                $explanationText = '';
                                $pendingImages = [];
                                $hasOptions = false;
                            }

                            $questionNumber++;
                            $current = [
                                'question' => $this->deduplicateText(trim($text)),
                                'options' => [],
                                'answer' => null,
                                'question_number' => $questionNumber,
                            ];

                            // Gambar di ListItem depth=0 (soal bergambar)
                            if (!empty($images)) {
                                foreach ($images as $img) {
                                    $url = $this->saveImage($img);
                                    if ($url) {
                                        $imgTag = '<img src="' . $url . '" style="max-width:100%;height:auto">';
                                        if ($current['question'] !== '') {
                                            $imgTag = '<br/>' . $imgTag;
                                        }
                                        $current['question'] .= $imgTag;
                                    }
                                }
                            }

                            $seqOptIdx = 0;
                            $prevWasQuestion = true;
                            $hasOptions = false;
                            $answerFound = false;
                            $isTableQuestion = false;
                            $this->extractAnswerFromText($current, $text);
                        } elseif ($depth >= 1 && $current !== null) {
                            $prevWasQuestion = false;
                            $hasOptions = true;
                            $this->extractAnswerFromText($current, $text);
                            $cleanText = $this->cleanOptionText($text);
                            $before = count($current['options']);
                            $this->parseOptionLine($cleanText, $current['options'], $optMap, $seqOptIdx);

                            // Opsi berupa gambar saja (teks kosong) -> placeholder
                            if (count($current['options']) === $before && !empty($images)) {
                                $letter = $optMap[$seqOptIdx] ?? null;
                                if ($letter !== null) {
                                    $current['options'][$letter] = '[Gambar]';
                                    $seqOptIdx++;
                                }
                            }

                            // Gambar di dalam opsi
                            if (!empty($images)) {
                                $optionKeys = array_keys($current['options']);
                                $lastOptKey = end($optionKeys);
                                if ($lastOptKey && isset($current['options'][$lastOptKey])) {
                                    foreach ($images as $img) {
                                        $url = $this->saveImage($img);
                                        if ($url) {
                                            $current['options'][$lastOptKey] .= '<br/><img src="' . $url . '" style="max-width:100%;height:auto">';
                                        }
                                    }
                                }
                            }
                        }
                        continue;
                    }

                    // 5. ELEMEN BIASA (TextRun, Title, Image, dsb.)
                    if (!empty($images) && $current !== null) {
                        foreach ($images as $img) {
                            $url = $this->saveImage($img);
                            if ($url) {
                                $imgTag = '<br/><img src="' . $url . '" style="max-width:100%;height:auto">';

                                if ($answerFound) {
                                    // Gambar setelah kunci jawaban -> bagian pembahasan
                                    $explanationText .= ' ' . $imgTag;
                                } elseif ($prevWasQuestion) {
                                    // Gambar langsung setelah pertanyaan -> ke soal
                                    $current['question'] .= $imgTag;
                                } elseif ($hasOptions && count($current['options']) > 0) {
                                    $optionKeys = array_keys($current['options']);
                                    $lastOptKey = end($optionKeys);
                                    if ($lastOptKey && isset($current['options'][$lastOptKey])) {
                                        $current['options'][$lastOptKey] .= $imgTag;
                                    } else {
                                        $pendingImages[] = $imgTag;
                                    }
                                } else {
                                    $pendingImages[] = $imgTag;
                                }
                            }
                        }
                    }

                    if (!empty(trim($text))) {
                        // SOAL BER-NOMOR TULIS TANGAN: "1. Apa ibu kota Indonesia?"
                        // (tanpa auto-numbering Word). Dikenali dari angka + titik.
                        // Bisa jadi soal pertama dokumen (current masih null).
                        //
                        // Di templateMode, baris bernomor seperti "1. langkah
                        // pertama..." yang muncul SETELAH jawaban ditemukan (konteks
                        // pembahasan) bukan soal baru — soal hanya dari list item.
                        // Namun jika current masih null (mis. soal pertama ditulis
                        // manual sebelum ada list item) atau jawaban belum ditemukan,
                        // deteksi plain-number tetap aktif (fleksibel).
                        $inExplanationContext = $this->templateMode
                            && $current !== null
                            && $current['answer'] !== null;
                        if (!$inExplanationContext
                            && $this->isPlainQuestionLine($text)
                            && ($current === null || $hasOptions || $current['answer'] !== null)) {
                            if ($current !== null) {
                                $this->finalizeQuestion($current, $questions, $explanationText, $pendingImages, $hasOptions, $seqOptIdx, $optMap);
                                $explanationText = '';
                                $pendingImages = [];
                                $hasOptions = false;
                            }

                            $questionNumber++;
                            $cleanQuestion = preg_replace('/^\s*\d+[\.\)]\s*/', '', $text);
                            $current = [
                                'question' => $this->deduplicateText(trim($cleanQuestion)),
                                'options' => [],
                                'answer' => null,
                                'question_number' => $questionNumber,
                            ];
                            $seqOptIdx = 0;
                            $prevWasQuestion = true;
                            $answerFound = false;
                            $isTableQuestion = false;
                            $this->extractAnswerFromText($current, $text);
                            continue;
                        }

                        // Teks sebelum soal pertama (judul/petunjuk) — abaikan
                        if ($current === null) {
                            continue;
                        }

                        // OPSI TULIS TANGAN: "A. Jakarta", "B.6", "E.Semua ..."
                        // (boleh lanjutan setelah opsi pertama; berhenti otomatis
                        // begitu kunci jawaban/pembahasan ditemukan)
                        //
                        // Berlaku juga untuk soal kepribadian: opsi skala
                        // ("A. Sangat Setuju") bisa ditulis sebagai teks biasa,
                        // bukan hanya list item. Dibatasi hingga 5 opsi agar teks
                        // setelah opsi kelima (mis. baris POIN) tidak ikut tertelan.
                        if (
                            $current['answer'] === null
                            && count(array_filter($current['options'])) < 5
                            && preg_match('/^\s*[A-Ea-e][\.\)]\s*/', $text)
                        ) {
                            $prevWasQuestion = false;
                            $hasOptions = true;
                            $this->extractAnswerFromText($current, $text);
                            $this->parseOptionLine($this->cleanOptionText($text), $current['options'], $optMap, $seqOptIdx);
                            continue;
                        }

                        // EKSTRAK POIN UNTUK SOAL KEPRIBADIAN
                        if ($this->isPersonality) {
                            $this->extractPersonalityPoints($current, $text);
                        } else {
                            $explanationText .= ' ' . $text;
                            $this->extractAnswerFromText($current, $text);
                            if ($current['answer'] !== null) {
                                $answerFound = true;
                            }
                        }

                        if (strlen(trim(strip_tags($text))) > 3) {
                            $prevWasQuestion = false;
                        }
                    }
                }
            }

            // Handle pertanyaan terakhir
            if ($current !== null) {
                $this->finalizeQuestion($current, $questions, $explanationText, $pendingImages, $hasOptions, $seqOptIdx, $optMap);
            }

            // Filter pertanyaan kosong (soal yang hanya berisi gambar tetap dipertahankan)
            $questions = array_values(
                array_filter(
                    $questions,
                    fn($q) => trim(strip_tags($q['question'])) !== '' || stripos($q['question'], '<img') !== false
                )
            );

            // Post-processing
            foreach ($questions as &$q) {
                if ($q['answer'] === null && !empty($q['options'])) $this->extractAnswerFromExplanation($q);
                if ($q['answer'] === null && !empty($q['options'])) $this->extractAnswerFromMarkedOption($q);
                if ($q['answer'] === null && !empty($q['options'])) $this->extractAnswerFromSingleOption($q);

                $hasImages = stripos($q['question'], '<img') !== false;

                // Jika tidak ada opsi tapi soal bergambar -> placeholder A-E
                if (empty($q['options']) || count(array_filter($q['options'])) === 0) {
                    if ($hasImages) {
                        $q['options'] = [
                            'A' => 'A', 'B' => 'B', 'C' => 'C',
                            'D' => 'D', 'E' => 'E',
                        ];
                    } else {
                        $q['options'] = ['A' => '', 'B' => '', 'C' => '', 'D' => '', 'E' => ''];
                    }
                }

                if ($q['answer'] === null) {
                    $q['answer'] = 'A';
                    $q['answer_detected'] = false;
                } else {
                    $q['answer_detected'] = true;
                }
            }

            $result = $this->saveQuestions($questions, $this->isPersonality);
            // Beri tahu pemanggil format mana yang terdeteksi (berguna untuk
            // umpan balik: "Format template terdeteksi" vs "Format fleksibel").
            $result['format'] = $this->templateMode ? 'list' : 'flexible';

            if ($result['success'] && $this->templateMode) {
                $result['message'] .= ' Format template terdeteksi (soal list bernomor + opsi berhuruf).';
            } elseif ($result['success']) {
                $result['message'] .= ' Format fleksibel terdeteksi.';
            }

            // Soal kepribadian tidak punya kunci jawaban (pakai poin), jadi
            // tidak perlu dimasukkan ke daftar jawaban tidak terdeteksi.
            $undetectedList = [];
            if (!$this->isPersonality) {
                foreach ($questions as $q) {
                    if (isset($q['answer_detected']) && $q['answer_detected'] === false) {
                        $undetectedList[] = [
                            'number' => $q['question_number'] ?? '?',
                            'preview' => substr(strip_tags($q['question']), 0, 80),
                        ];
                    }
                }
            }
            $result['undetected_answers'] = $undetectedList;

            return $result;
        } catch (\Throwable $e) {
            Log::error("Import failed: " . $e->getMessage() . "\n" . $e->getTraceAsString());
            return [
                'success' => false,
                'message' => 'Gagal membaca file: ' . $e->getMessage(),
                'count' => 0,
            ];
        } finally {
            if ($preprocessedPath && is_file($preprocessedPath)) {
                @unlink($preprocessedPath);
            }
        }
    }

    /**
     * Deteksi apakah dokumen memakai struktur list khas format template:
     * list item depth-0 (soal bernomor) DAN list item depth-1 (opsi berhuruf).
     * Dokumen seperti ini diparsing dengan mode terstruktur sebagai prioritas.
     */
    protected function detectTemplateMode($phpWord): bool
    {
        $hasDepth0Question = false;
        $hasDepth1Option = false;

        foreach ($phpWord->getSections() as $section) {
            foreach ($section->getElements() as $element) {
                if (!$element instanceof ListItemRun && !$element instanceof ListItem) {
                    continue;
                }
                $depth = method_exists($element, 'getDepth') ? (int) $element->getDepth() : 0;
                if ($depth === 0) {
                    // Hanya list item dengan teks soal yang wajar (>= 8 karakter)
                    // yang dihitung sebagai "soal" — item pendek seperti bullet
                    // kecil di pembahasan tidak memicu mode template.
                    $text = trim($this->getElemText($element));
                    if (mb_strlen($text) >= 8) {
                        $hasDepth0Question = true;
                    }
                } elseif ($depth >= 1) {
                    $hasDepth1Option = true;
                }
                if ($hasDepth0Question && $hasDepth1Option) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Proses elemen Tabel:
     *  1. Tabel soal (sel pertama diawali nomor soal, mis. "27. hayam") -> soal baru
     *  2. Tabel opsi (soal berjalan belum punya opsi) -> ekstrak opsi per baris/sel
     *  3. Selain itu -> teks pembahasan
     */
    protected function handleTable(
        Table $table,
        &$current,
        array &$questions,
        &$explanationText,
        &$hasOptions,
        int &$seqOptIdx,
        array $optMap,
        int &$questionNumber,
        bool &$prevWasQuestion,
        bool &$answerFound,
        bool &$isTableQuestion,
        array &$pendingImages
    ): void {
        $lines = $this->getTableLines($table);
        $lines = array_values(array_filter($lines, fn($l) => trim($l) !== ''));
        if (empty($lines)) return;

        $first = trim($lines[0] ?? '');

        // --- 1. Tabel berisi soal baru (nomor di sel pertama) ---
        if ($current !== null && $hasOptions && preg_match('/^\s*\d+[\.\)]\s+/', $first)) {
            $this->finalizeQuestion($current, $questions, $explanationText, $pendingImages, $hasOptions, $seqOptIdx, $optMap);
            $explanationText = '';
            $pendingImages = [];
            $hasOptions = false;

            $questionNumber++;
            $questionText = preg_replace('/^\s*\d+[\.\)]\s*/', '', implode(' ', $lines));
            $current = [
                'question' => $this->deduplicateText(trim($questionText)),
                'options' => [],
                'answer' => null,
                'question_number' => $questionNumber,
            ];
            $seqOptIdx = 0;
            $prevWasQuestion = true;
            $answerFound = false;
            $isTableQuestion = true; // opsi lanjutan mungkin list pendek
            $this->extractAnswerFromText($current, $questionText);
            return;
        }

        // --- 2. Tabel berisi opsi soal berjalan ---
        if ($current !== null && !$hasOptions && $current['answer'] === null) {
            foreach ($lines as $line) {
                // Kunci jawaban muncul di dalam tabel ("Jawaban : C Pembahasan : ...")
                if (preg_match('/Jawaban\s*:?\s*([A-Ea-e])/i', $line, $m)) {
                    $current['answer'] = strtoupper($m[1]);
                    $answerFound = true;
                    $hasOptions = true;
                    $rest = preg_replace('/Jawaban\s*:?\s*[A-Ea-e]/i', '', $line);
                    $rest = preg_replace('/^\s*Pembahasan\s*:?\s*/i', '', $rest);
                    $rest = trim($rest);
                    if ($rest !== '' && !$this->startsWithExplanation($rest)) {
                        $explanationText .= ' ' . $rest;
                    }
                    continue;
                }

                if (!$answerFound && $this->isOptionLine($line)) {
                    $hasOptions = true;
                    $this->parseOptionLine($this->cleanOptionText($line), $current['options'], $optMap, $seqOptIdx);
                } elseif ($this->startsWithExplanation($line)) {
                    $answerFound = true; // masuk pembahasan
                    $explanationText .= ' ' . $line;
                } elseif ($answerFound) {
                    $explanationText .= ' ' . $line;
                }
            }
            if ($hasOptions) {
                $prevWasQuestion = false;
            }
            return;
        }

        // --- 3. Tabel pembahasan biasa ---
        $explanationText .= ' ' . implode(' ', $lines);
    }

    /**
     * Ambil semua baris teks tabel (per paragraf per sel), urut baris-mayor.
     *
     * @return string[]
     */
    protected function getTableLines(Table $table): array
    {
        $lines = [];
        foreach ($table->getRows() as $row) {
            foreach ($row->getCells() as $cell) {
                foreach ($cell->getElements() as $cellElem) {
                    $t = trim($this->getElemText($cellElem));
                    if ($t !== '') $lines[] = $t;
                }
            }
        }
        return $lines;
    }

    protected function isPlainQuestionLine(string $text): bool
    {
        // "1. Apa ibu kota Indonesia?" — angka + titik + spasi, sisa teks cukup
        // panjang. "1. 200" (opsi bernomor di pembahasan) tidak dianggap soal.
        if (!preg_match('/^\s*\d+[\.\)]\s+/', $text)) return false;
        $rest = trim(preg_replace('/^\s*\d+[\.\)]\s*/', '', $text));
        return mb_strlen($rest) >= 8;
    }

    protected function isOptionLine(string $text): bool
    {
        // "A. 10 km", "B) ...", "E.Semua ..."
        if (preg_match('/^\s*[A-Ea-e][\.\)]\s*/', $text)) return true;
        // Baris pendek bernomor/unit, mis. "13 menit", "10%", "Rp.2.375.000,00"
        if (preg_match('/^\s*[\d.,]+\s*[A-Za-z%Rp.]{0,14}$/', $text) && mb_strlen($text) < 25) return true;
        return false;
    }

    protected function startsWithExplanation(string $text): bool
    {
        return (bool) preg_match('/^\s*(Pembahasan|Diketahui|Ditanya|Jawab)\s*[:：]?/i', $text);
    }

    protected function finalizeQuestion(
        &$current,
        array &$questions,
        &$explanationText,
        array $pendingImages,
        bool $hasOptions,
        int $seqOptIdx,
        array $optMap
    ): void {
        $this->attachPendingImages($current, $pendingImages, $hasOptions, $seqOptIdx, $optMap);
        $current['explanation'] = $explanationText;
        if ($current['answer'] === null) $this->extractAnswerFromExplanation($current);
        $questions[] = $current;
    }

    protected function attachPendingImages(&$current, $pendingImages, $hasOptions, $seqOptIdx, $optMap)
    {
        if (empty($pendingImages)) return;
        foreach ($pendingImages as $imgTag) {
            if (!$hasOptions) {
                $current['question'] .= $imgTag;
            } elseif ($hasOptions && $seqOptIdx > 0) {
                $lastOptKey = $optMap[$seqOptIdx - 1] ?? null;
                if ($lastOptKey && isset($current['options'][$lastOptKey])) {
                    $current['options'][$lastOptKey] .= $imgTag;
                }
            } else {
                $current['question'] .= $imgTag;
            }
        }
    }

    // -------------------------------------------------------------------------
    // PREPROCESS DOCX: unwrap <mc:AlternateContent> agar gambar terlihat PHPWord
    // -------------------------------------------------------------------------

    /**
     * Salin .docx ke file temp dengan document.xml yang sudah "dibersihkan":
     * blok <mc:AlternateContent> yang berisi <w:drawing> diganti menjadi
     * <w:r><w:drawing>...</w:drawing></w:r> (struktur standar yang dipahami
     * PHPWord). Kembalikan path file temp (harus di-unlink pemanggil).
     */
    protected function preprocessDocx(string $path): string
    {
        $zip = new ZipArchive();
        if (!$zip->open($path)) {
            throw new \RuntimeException('File .docx tidak dapat dibuka (bukan dokumen Word yang valid).');
        }

        $xml = $zip->getFromName('word/document.xml');
        if ($xml === false || $xml === '') {
            $zip->close();
            throw new \RuntimeException('File .docx tidak memiliki konten dokumen (word/document.xml).');
        }

        $newXml = $this->unwrapAlternateContent($xml);

        // Normalisasi tambahan:
        //  1. Rumus (<m:oMath>) diubah jadi teks biasa — tanpa ini PHPWord
        //     menyerahkan fragmen oMath ke PhpOffice\Math yang gagal loadXML
        //     (namespace m/w hanya dideklarasikan di root, tidak ikut tersalin)
        //     sehingga import fatal: "Namespace prefix m on oMath is not defined".
        //  2. Opsi berhuruf (A., B., ...) yang ditulis sebagai list level-0
        //     dinaikkan jadi level-1 agar tidak salah dibaca sebagai soal.
        $numberingXml = $zip->getFromName('word/numbering.xml');
        $newXml = $this->normalizeDocumentXml($newXml, $numberingXml === false ? '' : $numberingXml);

        // Terapkan crop Word (a:srcRect) ke file media. Word sering menampilkan
        // gambar terpotong (mis. screenshot 1920x1080 yang ditampilkan hanya
        // bagian persegi). Tanpa ini PHPWord mengambil gambar utuh sehingga
        // tampilan di sistem berbeda dari Word.
        $mediaCrops = $this->mapCropsToMedia(
            $this->extractSrcRectCrops($newXml),
            $zip->getFromName('word/_rels/document.xml.rels')
        );

        $tmp = tempnam(sys_get_temp_dir(), 'docx_');
        if ($tmp === false) {
            $zip->close();
            throw new \RuntimeException('Tidak dapat membuat file sementara untuk import.');
        }
        $tmp .= '.docx';

        $outZip = new ZipArchive();
        if (!$outZip->open($tmp, ZipArchive::CREATE | ZipArchive::OVERWRITE)) {
            $zip->close();
            @unlink($tmp);
            throw new \RuntimeException('Tidak dapat menulis file sementara untuk import.');
        }

        $numFiles = $zip->numFiles;
        for ($i = 0; $i < $numFiles; $i++) {
            $name = $zip->getNameIndex($i);
            if ($name === false) continue;
            if ($name === 'word/document.xml') {
                $outZip->addFromString($name, $newXml);
            } else {
                $content = $zip->getFromName($name);
                if ($content !== false) {
                    if (isset($mediaCrops[$name])) {
                        $cropped = $this->cropImageBinary($content, $mediaCrops[$name]);
                        if ($cropped !== null) {
                            $content = $cropped;
                        }
                    }
                    $outZip->addFromString($name, $content);
                }
            }
        }

        $zip->close();
        $outZip->close();

        return $tmp;
    }

    /**
     * Baca atribut crop gambar (a:srcRect) dari document.xml. Nilai l/t/r/b
     * adalah perseribu persen (100000 = 100%), dikonversi ke pecahan 0..1.
     *
     * @return array<string, array{l: float, t: float, r: float, b: float}> map rId => crop
     */
    protected function extractSrcRectCrops(string $xml): array
    {
        if (strpos($xml, 'srcRect') === false) return [];

        $dom = new \DOMDocument();
        $prev = libxml_use_internal_errors(true);
        $ok = $dom->loadXML($xml);
        libxml_clear_errors();
        libxml_use_internal_errors($prev);
        if (!$ok) return [];

        $xpath = new \DOMXPath($dom);
        $xpath->registerNamespace('a', 'http://schemas.openxmlformats.org/drawingml/2006/main');
        $xpath->registerNamespace('r', 'http://schemas.openxmlformats.org/officeDocument/2006/relationships');

        $crops = [];
        foreach ($xpath->query('//a:blip') as $blip) {
            $embed = $blip->getAttributeNS('http://schemas.openxmlformats.org/officeDocument/2006/relationships', 'embed');
            if ($embed === '') continue;

            // a:srcRect adalah saudara a:blip di dalam pic:blipFill
            $srcRect = null;
            $parent = $blip->parentNode;
            if ($parent) {
                foreach ($parent->childNodes as $c) {
                    if ($c->nodeType === XML_ELEMENT_NODE
                        && $c->localName === 'srcRect'
                        && $c->namespaceURI === 'http://schemas.openxmlformats.org/drawingml/2006/main'
                    ) {
                        $srcRect = $c;
                        break;
                    }
                }
            }
            if (!$srcRect) continue;

            $frac = function (string $attr) use ($srcRect): float {
                $v = $srcRect->getAttribute($attr);
                return $v === '' ? 0.0 : ((float) $v) / 100000.0;
            };
            $l = $frac('l');
            $t = $frac('t');
            $r = $frac('r');
            $b = $frac('b');
            if ($l == 0 && $t == 0 && $r == 0 && $b == 0) continue;

            $crops[$embed] = ['l' => $l, 't' => $t, 'r' => $r, 'b' => $b];
        }
        return $crops;
    }

    /**
     * Petakan rId -> path media di dalam zip (via word/_rels/document.xml.rels),
     * lalu kembalikan map path => crop.
     *
     * @param array<string, array{l: float, t: float, r: float, b: float}> $crops
     * @return array<string, array{l: float, t: float, r: float, b: float}>
     */
    protected function mapCropsToMedia(array $crops, $relsXml): array
    {
        if (empty($crops) || !is_string($relsXml) || $relsXml === '') return [];

        $dom = new \DOMDocument();
        $prev = libxml_use_internal_errors(true);
        $ok = $dom->loadXML($relsXml);
        libxml_clear_errors();
        libxml_use_internal_errors($prev);
        if (!$ok) return [];

        $xpath = new \DOMXPath($dom);
        $xpath->registerNamespace('r', 'http://schemas.openxmlformats.org/package/2006/relationships');

        $map = [];
        foreach ($xpath->query('//r:Relationship') as $rel) {
            $id = $rel->getAttribute('Id');
            $target = $rel->getAttribute('Target');
            if ($id !== '' && isset($crops[$id]) && $target !== '') {
                // Target relatif terhadap folder word/
                $map['word/' . ltrim($target, '/')] = $crops[$id];
            }
        }
        return $map;
    }

    /**
     * Potong binary gambar sesuai crop Word (a:srcRect). Kembalikan binary PNG
     * hasil crop, atau null jika gagal / GD tidak tersedia.
     *
     * @param array{l: float, t: float, r: float, b: float} $crop
     */
    protected function cropImageBinary(string $binary, array $crop): ?string
    {
        if (!extension_loaded('gd') || !function_exists('imagecrop')) return null;
        $src = @imagecreatefromstring($binary);
        if (!$src) return null;

        $w = imagesx($src);
        $h = imagesy($src);
        $x = (int) round($crop['l'] * $w);
        $y = (int) round($crop['t'] * $h);
        $cw = max(1, min((int) round($w * (1 - $crop['l'] - $crop['r'])), $w - $x));
        $ch = max(1, min((int) round($h * (1 - $crop['t'] - $crop['b'])), $h - $y));

        $dst = imagecrop($src, ['x' => $x, 'y' => $y, 'width' => $cw, 'height' => $ch]);
        imagedestroy($src);
        if ($dst === false) return null;

        ob_start();
        imagepng($dst);
        $png = ob_get_clean();
        imagedestroy($dst);
        return ($png === false || $png === '') ? null : $png;
    }

    /**
     * Ganti blok <mc:AlternateContent> yang memuat <w:drawing> dengan
     * <w:r><w:drawing>...</w:drawing></w:r>. Jika tidak ada perubahan, XML
     * asli dikembalikan.
     */
    protected function unwrapAlternateContent(string $xml): string
    {
        if (strpos($xml, 'AlternateContent') === false) {
            return $xml;
        }

        $dom = new \DOMDocument();
        $prev = libxml_use_internal_errors(true);
        $ok = $dom->loadXML($xml);
        libxml_clear_errors();
        libxml_use_internal_errors($prev);
        if (!$ok) return $xml;

        $xpath = new \DOMXPath($dom);
        $xpath->registerNamespace('mc', self::NS_MC);
        $xpath->registerNamespace('w', self::NS_W);

        $changed = false;
        $acs = $xpath->query('//mc:AlternateContent');
        foreach ($acs as $ac) {
            $choice = null;
            foreach ($ac->childNodes as $c) {
                if ($c->nodeType === XML_ELEMENT_NODE && $c->localName === 'Choice') {
                    $choice = $c;
                    break;
                }
            }
            if (!$choice) continue;

            $drawings = [];
            foreach ($choice->childNodes as $c) {
                if ($c->nodeType === XML_ELEMENT_NODE && $c->localName === 'drawing' && $c->namespaceURI === self::NS_W) {
                    $drawings[] = $c;
                }
            }
            if (empty($drawings)) continue;

            $parent = $ac->parentNode;
            if (!$parent) continue;

            // Teks di dalam text box (wps:txbx / w:txbxContent) — mis. "Jawaban :
            // B" — ikut disalin sebagai <w:t> biasa. Tanpa ini, PHPWord hanya
            // melihat gambar dan teks jawabannya hilang.
            $boxText = '';
            $textParts = [];
            foreach ($drawings as $d) {
                foreach ($xpath->query('.//w:t', $d) as $tn) {
                    $textParts[] = $tn->textContent;
                }
            }
            $boxText = trim(implode(' ', $textParts));

            // Jika blok AlternateContent sudah berada di dalam <w:r>, gambar
            // langsung disisipkan di tempatnya (tanpa run baru — run bersarang
            // tidak dibaca PHPWord). Jika tidak, bungkus dengan <w:r> baru.
            $parentIsRun = $parent->nodeType === XML_ELEMENT_NODE
                && $parent->localName === 'r'
                && $parent->namespaceURI === self::NS_W;

            if ($parentIsRun) {
                foreach ($drawings as $d) {
                    $parent->insertBefore($dom->importNode($d, true), $ac);
                }
                if ($boxText !== '') {
                    $parent->insertBefore($this->createTextNode($dom, $boxText), $ac);
                }
                $parent->removeChild($ac);
            } else {
                $run = $dom->createElementNS(self::NS_W, 'w:r');
                foreach ($drawings as $d) {
                    $run->appendChild($dom->importNode($d, true));
                }
                if ($boxText !== '') {
                    $run->appendChild($this->createTextNode($dom, $boxText));
                }
                $parent->insertBefore($run, $ac);
                $parent->removeChild($ac);
            }
            $changed = true;
        }

        if (!$changed) return $xml;
        return $dom->saveXML();
    }

    protected function createTextNode(\DOMDocument $dom, string $text): \DOMElement
    {
        $t = $dom->createElementNS(self::NS_W, 'w:t');
        $t->setAttributeNS('http://www.w3.org/XML/1998/namespace', 'xml:space', 'preserve');
        $t->textContent = $text;
        return $t;
    }

    /**
     * Normalisasi document.xml sebelum dibaca PHPWord:
     *  1. <m:oMath> (rumus) -> <w:r><w:t>teks</w:t></w:r> agar PHPWord tidak
     *     memanggil PhpOffice\Math (sering gagal parse namespace / konstruk
     *     rumus yang belum didukung).
     *  2. List item level-0 dengan penomoran huruf (upperLetter/lowerLetter,
     *     mis. opsi "A." "B." yang ditulis setara dengan soal) dinaikkan ke
     *     level-1 agar dibaca sebagai opsi, bukan soal baru.
     *
     * @return string XML hasil normalisasi (atau XML asli jika tidak berubah)
     */
    protected function normalizeDocumentXml(string $xml, string $numberingXml): string
    {
        $hasMath = strpos($xml, 'oMath') !== false;
        $hasNumPr = strpos($xml, 'numPr') !== false;
        if (!$hasMath && (!$hasNumPr || $numberingXml === '')) {
            return $xml;
        }

        $dom = new \DOMDocument();
        $prev = libxml_use_internal_errors(true);
        $ok = $dom->loadXML($xml);
        libxml_clear_errors();
        libxml_use_internal_errors($prev);
        if (!$ok) return $xml;

        $xpath = new \DOMXPath($dom);
        $xpath->registerNamespace('w', self::NS_W);

        $changed = false;
        if ($hasMath) {
            $changed = $this->convertOMathToText($dom, $xpath) || $changed;
        }
        if ($hasNumPr && $numberingXml !== '') {
            $changed = $this->bumpLetterOptionsToLevel1($dom, $xpath, $numberingXml) || $changed;
        }

        if (!$changed) return $xml;
        return $dom->saveXML();
    }

    /**
     * Ganti semua <m:oMath> dengan run teks biasa (pecahan -> "a/b",
     * superskrip -> "a^b", subskrip -> "a_b", delimiter -> "(...)").
     */
    protected function convertOMathToText(\DOMDocument $dom, \DOMXPath $xpath): bool
    {
        $xpath->registerNamespace('m', 'http://schemas.openxmlformats.org/officeDocument/2006/math');
        $maths = $xpath->query('//m:oMath');
        if ($maths === false || $maths->length === 0) return false;

        $changed = false;
        foreach ($maths as $m) {
            $text = $this->oMathToText($m);
            $run = $dom->createElementNS(self::NS_W, 'w:r');
            $run->appendChild($this->createTextNode($dom, $text));
            $parent = $m->parentNode;
            if ($parent) {
                $parent->replaceChild($run, $m);
                $changed = true;
            }
        }
        return $changed;
    }

    /**
     * Ekstrak teks terbaca dari node OMML secara rekursif.
     */
    protected function oMathToText(\DOMNode $node): string
    {
        $out = '';
        foreach ($node->childNodes as $c) {
            if ($c->nodeType !== XML_ELEMENT_NODE) continue;
            switch ($c->localName) {
                case 't':
                    $out .= $c->textContent;
                    break;
                case 'f': // pecahan: pembilang/penyebut
                    $out .= $this->oMathChildText($c, 'num')
                        . '/'
                        . $this->oMathChildText($c, 'den');
                    break;
                case 'sSup': // pangkat: a^b
                    $out .= $this->oMathChildText($c, 'e')
                        . '^'
                        . $this->oMathChildText($c, 'sup');
                    break;
                case 'sSub': // indeks: a_b
                    $out .= $this->oMathChildText($c, 'e')
                        . '_'
                        . $this->oMathChildText($c, 'sub');
                    break;
                case 'd': // delimiter/kurung
                    $out .= '(' . $this->oMathToText($c) . ')';
                    break;
                default:
                    $out .= $this->oMathToText($c);
            }
        }
        return $out;
    }

    protected function oMathChildText(\DOMNode $node, string $tag): string
    {
        foreach ($node->childNodes as $c) {
            if ($c->nodeType === XML_ELEMENT_NODE && $c->localName === $tag) {
                return $this->oMathToText($c);
            }
        }
        return '';
    }

    /**
     * Naikkan list item level-0 yang memakai penomoran huruf (upperLetter /
     * lowerLetter) menjadi level-1, agar opsi "A. xxx" yang ditulis setara
     * dengan soal tidak dibaca sebagai soal baru. Hanya dilakukan jika dokumen
     * juga memuat list bernomor desimal (soal) — dokumen yang seluruhnya list
     * berhuruf dibiarkan apa adanya.
     */
    protected function bumpLetterOptionsToLevel1(\DOMDocument $dom, \DOMXPath $xpath, string $numberingXml): bool
    {
        $fmt = $this->resolveNumberingFormats($numberingXml);
        if (empty($fmt)) return false;

        // Pastikan ada indikasi dokumen ini berisi SOAL sebelum mengubah
        // struktur. Indikatornya: list bernomor desimal (soal otomatis), ATAU
        // paragraf biasa bernomor "1. ..." (soal diketik manual). Tanpa ini,
        // opsi berhuruf level-0 di dokumen yang soalnya manual tidak dinaikkan
        // sehingga malah terbaca sebagai soal baru.
        $hasQuestionIndicator = false;
        foreach ($xpath->query('//w:p') as $p) {
            $numId = $xpath->query('./w:pPr/w:numPr/w:numId/@w:val', $p)->item(0);
            if ($numId) {
                if (($fmt[$numId->nodeValue] ?? '') === 'decimal') {
                    $hasQuestionIndicator = true;
                    break;
                }
                continue;
            }

            // Paragraf biasa (tanpa penomoran) — cek pola "1. teks soal..."
            if ($xpath->query('./w:pPr/w:numPr', $p)->length > 0) {
                continue;
            }
            $text = '';
            foreach ($xpath->query('.//w:t', $p) as $t) {
                $text .= $t->textContent;
            }
            $text = trim($text);
            if (preg_match('/^\s*\d+[\.\)]\s+/', $text)) {
                $rest = trim(preg_replace('/^\s*\d+[\.\)]\s*/', '', $text));
                if (mb_strlen($rest) >= 8) {
                    $hasQuestionIndicator = true;
                    break;
                }
            }
        }
        if (!$hasQuestionIndicator) return false;

        $changed = false;
        foreach ($xpath->query('//w:p') as $p) {
            $ilvl = $xpath->query('./w:pPr/w:numPr/w:ilvl/@w:val', $p)->item(0);
            $numId = $xpath->query('./w:pPr/w:numPr/w:numId/@w:val', $p)->item(0);
            if (!$ilvl || !$numId) continue;
            if ((int) $ilvl->nodeValue !== 0) continue;
            $f = $fmt[$numId->nodeValue] ?? '';
            if ($f === 'upperLetter' || $f === 'lowerLetter') {
                $ilvl->nodeValue = '1';
                $changed = true;
            }
        }
        return $changed;
    }

    /**
     * Petakan numId -> numFmt level 0 dari word/numbering.xml.
     *
     * @return array<string, string>
     */
    protected function resolveNumberingFormats(string $numberingXml): array
    {
        $ndom = new \DOMDocument();
        $prev = libxml_use_internal_errors(true);
        $ok = $ndom->loadXML($numberingXml);
        libxml_clear_errors();
        libxml_use_internal_errors($prev);
        if (!$ok) return [];

        $nx = new \DOMXPath($ndom);
        $nx->registerNamespace('w', self::NS_W);

        $abstractFmt = [];
        foreach ($nx->query('//w:abstractNum') as $an) {
            $id = $an->getAttributeNS(self::NS_W, 'abstractNumId');
            $lvl0 = $nx->query('./w:lvl[@w:ilvl="0"]/w:numFmt/@w:val', $an)->item(0);
            if ($id !== '' && $lvl0 !== null) {
                $abstractFmt[$id] = $lvl0->nodeValue;
            }
        }

        $fmt = [];
        foreach ($nx->query('//w:num') as $n) {
            $id = $n->getAttributeNS(self::NS_W, 'numId');
            $abs = $nx->query('./w:abstractNumId/@w:val', $n)->item(0);
            if ($id !== '' && $abs !== null && isset($abstractFmt[$abs->nodeValue])) {
                $fmt[$id] = $abstractFmt[$abs->nodeValue];
            }
        }
        return $fmt;
    }

    // -------------------------------------------------------------------------
    // METODE EKSTRAKSI JAWABAN
    // -------------------------------------------------------------------------

    /**
     * EKSTRAKSI POIN UNTUK SOAL KEPRIBADIAN
     * Format: POIN: A=5|B=4|C=3|D=2|E=1
     */
    protected function extractPersonalityPoints(array &$question, string $text): void
    {
        if (preg_match('/POIN\s*[:：]\s*(.+)$/i', $text, $matches)) {
            $pointsStr = trim($matches[1]);
            $pairs = explode('|', $pointsStr);

            $points = [];
            foreach ($pairs as $pair) {
                if (preg_match('/([A-E])\s*=\s*(\d+)/i', $pair, $m)) {
                    $option = strtoupper($m[1]);
                    $value = (int)$m[2];

                    $optionMap = ['A' => 1, 'B' => 2, 'C' => 3, 'D' => 4, 'E' => 5];
                    if (isset($optionMap[$option])) {
                        $points[$optionMap[$option]] = $value;
                    }
                }
            }

            if (!empty($points)) {
                $question['personality_points'] = $points;
            }
        }
    }

    protected function deduplicateText(string $text): string
    {
        // Hanya rapikan duplikasi frasa KATA (mis. "Jawaban : BJawaban : B" ->
        // "Jawaban : B"). Pengulangan ANGKA seperti "000.000" pada
        // "Rp.2.000.000,00" TIDAK boleh digabung.
        return preg_replace_callback('/(.{4,}?)\1+/u', function ($m) {
            return preg_match('/[A-Za-z]{2,}/', $m[1]) ? $m[1] : $m[0];
        }, $text);
    }

    protected function extractAnswerFromText(array &$question, ?string $text = null): void
    {
        $textToCheck = $text ?? $question['question'] ?? '';
        if ($question['answer'] !== null) return;

        if (preg_match('/Jawaban\s*[:：]\s*([A-Ea-e])/i', $textToCheck, $m)) {
            $question['answer'] = strtoupper($m[1]);
            return;
        }
        if (preg_match('/jawaban\s+(?:yang\s+paling\s+tepat\s+)?(?:adalah\s+)?["\']?([A-Ea-e])["\']?/i', $textToCheck, $m)) {
            $question['answer'] = strtoupper($m[1]);
            return;
        }
    }

    protected function extractAnswerFromExplanation(array &$question): void
    {
        $explanationText = $question['explanation'] ?? '';
        $availableOptions = array_keys($question['options'] ?? ['A', 'B', 'C', 'D', 'E']);
        if (empty($explanationText) || empty($availableOptions)) return;

        if (preg_match('/(?:jawaban(?:nya)?|yang\s+paling\s+tepat)\s+(?:adalah\s+)?["\']?([A-Ea-e])["\']?/i', $explanationText, $m)) {
            $question['answer'] = strtoupper($m[1]);
            return;
        }
        // "Pembahasan : A" — jawaban ditulis di awal baris pembahasan
        if (preg_match('/Pembahasan\s*[:：]?\s*([A-Ea-e])\b/i', $explanationText, $m)
            && in_array(strtoupper($m[1]), $availableOptions)) {
            $question['answer'] = strtoupper($m[1]);
            return;
        }
        if (preg_match('/(?:sebab|karena)\s+(\d+)\b/i', $explanationText, $m)) {
            $num = (int) $m[1];
            $optionMap = [1 => 'A', 2 => 'B', 3 => 'C', 4 => 'D', 5 => 'E'];
            if (isset($optionMap[$num])) $question['answer'] = $optionMap[$num];
            return;
        }
        foreach ($this->textAnswerMap as $key => $value) {
            if (stripos($explanationText, $key) !== false && in_array($value, $availableOptions)) {
                $question['answer'] = $value;
                return;
            }
        }
    }

    protected function extractAnswerFromMarkedOption(array &$question): void
    {
        foreach ($question['options'] as $letter => $optionText) {
            if (preg_match('/^[A-E]\.\s*\*\*/', $optionText) || preg_match('/<strong>[A-E]<\/strong>/', $optionText)) {
                $question['answer'] = $letter;
                return;
            }
        }
    }

    protected function extractAnswerFromSingleOption(array &$question): void
    {
        $nonEmptyOptions = [];
        foreach ($question['options'] as $letter => $text) {
            if (!empty(trim($text))) $nonEmptyOptions[] = $letter;
        }
        if (count($nonEmptyOptions) === 1) $question['answer'] = $nonEmptyOptions[0];
    }

    protected function cleanOptionText(string $text): string
    {
        $text = preg_replace('/[#>]*\s*Jawaban\s*[:：\-]\s*[A-Ea-e].*$/i', '', $text);
        $text = preg_replace('/\s*Pembahasan\s*[:：\-].*$/i', '', $text);
        return trim($text);
    }

    protected function parseOptionLine(string $text, array &$options, array $optMap, int &$seqOptIdx): void
    {
        $text = $this->deduplicateText($text);

        // 1. Pecah berdasarkan tab / spasi ganda
        $parts = preg_split('/\t+|\s{2,}/', $text);
        $split = [];
        foreach ($parts as $part) {
            if (trim($part) === '') continue;
            // 2. Pecah lebih lanjut jika dua opsi menempel, mis. "18 menitE. 20 menit"
            foreach ($this->splitCombinedOptions($part) as $sub) {
                if (trim($sub) !== '') $split[] = trim($sub);
            }
        }

        foreach ($split as $part) {
            if (preg_match('/^(?:\d+\.)?\s*([A-Ea-e])\.\s*(.+)$/i', $part, $m)) {
                $options[strtoupper($m[1])] = trim($m[2]);
            } elseif (preg_match('/^([A-Ea-e])\s+(.+)$/i', $part, $m)) {
                $options[strtoupper($m[1])] = trim($m[2]);
            } else {
                $letter = $optMap[$seqOptIdx] ?? null;
                if ($letter !== null) $options[$letter] = $part;
                $seqOptIdx++;
            }
        }
    }

    /**
     * Pecah baris opsi yang menempel tanpa spasi, mis.:
     *   "18 menitE. 20 menit"  -> ["18 menit", "E. 20 menit"]
     *   "12.000 kgD. 11.761 kg"-> ["12.000 kg", "D. 11.761 kg"]
     * Huruf di tengah kata (mis. "berbeda.") tidak ikut terpecah karena harus
     * diikuti titik.
     */
    protected function splitCombinedOptions(string $text): array
    {
        // Hanya huruf KAPITAL A-E yang dipecah, mis. "18 menitE. 20 menit",
        // "12.000 kgD. 11.761 kg". Huruf kecil di tengah kata ("sepeda. 15")
        // tidak boleh terpecah.
        if (!preg_match('/[A-E]\./', $text)) {
            return [$text];
        }
        $parts = preg_split('/(?<=[^\sA-E])[A-E](?=\.\s)/', $text);
        return $parts === false ? [$text] : $parts;
    }

    // -------------------------------------------------------------------------
    // EKSTRAKSI GAMBAR REKURSIF (TERMASUK TABEL, ROW, CELL, TITLE)
    // -------------------------------------------------------------------------

    protected function extractImages($elem, array &$images): void
    {
        if (!is_object($elem)) return;
        $class = basename(str_replace('\\', '/', get_class($elem)));

        if ($class === 'Image') {
            $images[] = $elem;
            return;
        }

        // Title menyimpan teksnya dalam TextRun internal
        if ($elem instanceof Title) {
            $t = $elem->getText();
            if ($t instanceof TextRun) {
                foreach ($t->getElements() as $child) $this->extractImages($child, $images);
            }
            return;
        }

        if (method_exists($elem, 'getElements')) {
            foreach ($elem->getElements() as $child) $this->extractImages($child, $images);
        }
        if (method_exists($elem, 'getRows')) {
            foreach ($elem->getRows() as $row) $this->extractImages($row, $images);
        }
        if (method_exists($elem, 'getCells')) {
            foreach ($elem->getCells() as $cell) $this->extractImages($cell, $images);
        }
        if (property_exists($elem, 'children') && is_array($elem->children)) {
            foreach ($elem->children as $child) $this->extractImages($child, $images);
        }
    }

    protected function saveImage($imgElem): ?string
    {
        try {
            if (!method_exists($imgElem, 'getImageStringData')) return null;
            $b64 = $imgElem->getImageStringData(true);
            if (!$b64) return null;
            $binary = base64_decode($b64);

            if (!$binary || strlen($binary) < 50) {
                return null;
            }

            // Cegah fatal memory_limit di server (mis. VPS 128M) saat memproses
            // foto besar. GD butuh ± W*H*4 byte per buffer; jika perkiraan
            // kebutuhan melebihi sisa memori, simpan gambar asli apa adanya.
            $info = function_exists('getimagesizefromstring') ? @getimagesizefromstring($binary) : false;
            if ($info) {
                $needBytes = (int) $info[0] * (int) $info[1] * 8; // buffer src + dst
                $limitBytes = $this->memoryLimitBytes();
                if ($limitBytes > 0 && (memory_get_usage(true) + $needBytes) > (int) ($limitBytes * 0.8)) {
                    Log::warning('Import: memori terbatas, gambar disimpan apa adanya (' . $needBytes . ' bytes).');
                    return $this->storeRawImage($binary, $this->detectImageExtension($binary));
                }
            }

            $url = ImageConverter::convertAndStore($binary, 'question-images', 80, 1400);
            if ($url) return $url;

            // GD/webp tidak tersedia -> simpan gambar asli
            return $this->storeRawImage($binary, $this->detectImageExtension($binary));
        } catch (\Throwable $e) {
            Log::error("Failed to save image: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Simpan gambar asli tanpa konversi (fallback saat GD/webp/memori terbatas).
     */
    protected function storeRawImage(string $binary, string $ext = 'bin'): ?string
    {
        try {
            if (!preg_match('/^[a-z0-9]{2,5}$/', $ext)) $ext = 'bin';
            $filename = uniqid('img_', true) . '.' . $ext;
            \Illuminate\Support\Facades\Storage::disk('public')->put('question-images/' . $filename, $binary);
            return \Illuminate\Support\Facades\Storage::url('question-images/' . $filename);
        } catch (\Throwable $e) {
            Log::error("Failed to store raw image: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Deteksi ekstensi dari signature biner (tanpa bergantung pada GD).
     */
    protected function detectImageExtension(string $binary): string
    {
        if (strncmp($binary, "\x89PNG", 4) === 0) return 'png';
        if (strncmp($binary, "\xFF\xD8", 2) === 0) return 'jpg';
        if (strncmp($binary, 'GIF8', 4) === 0) return 'gif';
        if (strncmp($binary, 'RIFF', 4) === 0 && substr($binary, 8, 4) === 'WEBP') return 'webp';
        return 'bin';
    }

    protected function memoryLimitBytes(): int
    {
        $val = ini_get('memory_limit');
        if ($val === false || $val === '' || $val === '-1') return 0;
        $unit = strtolower(substr($val, -1));
        $num = (int) $val;
        switch ($unit) {
            case 'g': $num *= 1024 * 1024 * 1024; break;
            case 'm': $num *= 1024 * 1024; break;
            case 'k': $num *= 1024; break;
        }
        return $num;
    }

    /**
     * EKSTRAKSI TEKS REKURSIF (TERMASUK TABEL, ROW, CELL, TITLE)
     * SKIP IMAGE ELEMENTS
     */
    protected function getElemText($elem): string
    {
        $t = '';
        if (!is_object($elem)) return '';
        $class = basename(str_replace('\\', '/', get_class($elem)));
        if ($class === 'Image') return '';

        // Title (Heading) menyimpan teks dalam TextRun internal
        if ($elem instanceof Title) {
            $titleText = $elem->getText();
            if (is_string($titleText)) return $titleText;
            if ($titleText instanceof TextRun) {
                foreach ($titleText->getElements() as $child) {
                    $t .= $this->getElemText($child);
                }
            }
            return $t;
        }

        // Elemen wadah (TextRun, ListItemRun, Cell, dst.) punya BAIK getElements
        // MAUPUN getText() yang menggabungkan anak-anaknya. Menggunakan keduanya
        // akan menggandakan teks — cukup rekursi anak-anaknya saja.
        if (method_exists($elem, 'getElements')) {
            foreach ($elem->getElements() as $child) $t .= $this->getElemText($child);
        }
        if (method_exists($elem, 'getRows')) {
            foreach ($elem->getRows() as $row) $t .= $this->getElemText($row);
        }
        if (method_exists($elem, 'getCells')) {
            foreach ($elem->getCells() as $cell) $t .= $this->getElemText($cell);
        }

        // Daun (Text, ListItem, dsb.): teks langsung dari getText()
        if (!method_exists($elem, 'getElements')
            && !method_exists($elem, 'getRows')
            && !method_exists($elem, 'getCells')
            && method_exists($elem, 'getText')
        ) {
            $val = $elem->getText();
            if (is_string($val)) $t .= $val;
        }
        return $t;
    }

    protected function saveQuestions(array $questions, $isPersonality): array
    {
        $savedCount = 0;
        $errors = [];
        $warnings = [];
        $undetectedList = [];

        foreach ($questions as $index => $q) {
            $questionNumber = $index + 1;
            $validationErrors = [];
            $validationWarnings = [];

            if (empty(trim(strip_tags($q['question']))) && stripos($q['question'], '<img') === false) {
                $validationErrors[] = 'Pertanyaan kosong';
            }

            $hasContentOptions = false;
            foreach (['A', 'B', 'C', 'D', 'E'] as $opt) {
                $optionText = $q['options'][$opt] ?? '';
                if (!empty(trim($optionText)) && !in_array(strtolower(trim($optionText)), ['[gambar a]', '[gambar b]', '[gambar c]', '[gambar d]', '[gambar e]', '[gambar]'])) {
                    $hasContentOptions = true;
                    break;
                }
            }

            $hasImages = stripos($q['question'], '<img') !== false;
            foreach ($q['options'] ?? [] as $optText) {
                if (is_string($optText) && stripos($optText, '<img') !== false) {
                    $hasImages = true;
                    break;
                }
            }
            // Berlaku untuk semua tipe (termasuk kepribadian): tanpa opsi, soal
            // tidak bisa dikerjakan. Sebelumnya kepribadian dengan opsi kosong
            // disimpan diam-diam tanpa peringatan karena validasi ini dilewati.
            if (!$hasContentOptions && !$hasImages) {
                $validationErrors[] = 'Semua opsi kosong dan tidak ada gambar (perlu input manual)';
            }

            $isUndetected = isset($q['answer_detected']) && $q['answer_detected'] === false;
            if (!$isPersonality && $isUndetected && $hasContentOptions) {
                $validationWarnings[] = 'Jawaban tidak terdeteksi, default ke A (perlu cek manual)';
                $undetectedList[] = [
                    'number' => $questionNumber,
                    'preview' => substr(strip_tags($q['question']), 0, 80),
                ];
            }

            if (!empty($validationErrors)) {
                $errors[] = [
                    'question_number' => $questionNumber,
                    'question_preview' => substr(strip_tags($q['question']), 0, 100),
                    'errors' => $validationErrors,
                ];
            } else {
                $data = [
                    'exam_id' => $this->exam_id,
                    'question' => $q['question'],
                    'option_1' => $q['options']['A'] ?? '',
                    'option_2' => $q['options']['B'] ?? '',
                    'option_3' => $q['options']['C'] ?? '',
                    'option_4' => $q['options']['D'] ?? '',
                    'option_5' => $q['options']['E'] ?? '',
                ];

                if ($isPersonality) {
                    $data['answer'] = 1;

                    if (isset($q['personality_points']) && !empty($q['personality_points'])) {
                        $data['points'] = $q['personality_points'];
                    } else {
                        $data['points'] = ['1' => 5, '2' => 4, '3' => 3, '4' => 2, '5' => 1];
                    }
                } else {
                    $answerMap = ['A' => 1, 'B' => 2, 'C' => 3, 'D' => 4, 'E' => 5];
                    $data['answer'] = $answerMap[$q['answer'] ?? ''] ?? 1;
                    if (isset($q['answer_detected']) && $q['answer_detected'] === false) {
                        $data['needs_review'] = true;
                        $data['review_notes'] = 'Jawaban tidak terdeteksi saat import, default ke A. Harap verifikasi jawaban yang benar.';
                    }
                }

                Question::create($data);
                $savedCount++;

                if (!empty($validationWarnings)) {
                    $warnings[] = [
                        'question_number' => $questionNumber,
                        'question_preview' => substr(strip_tags($q['question']), 0, 100),
                        'errors' => $validationWarnings,
                    ];
                }
            }
        }

        $result = [
            'success' => true, 'count' => $savedCount, 'total' => count($questions),
            'errors' => $errors, 'warnings' => $warnings, 'undetected_answers' => $undetectedList,
            'message' => "Berhasil import {$savedCount} soal.",
        ];

        if (!empty($errors)) {
            $result['message'] = "Berhasil import {$savedCount} dari {$result['total']} soal. " . count($errors) . " soal memiliki error.";
            $result['has_errors'] = true;
        }
        if (!empty($warnings) || !empty($undetectedList)) {
            $result['message'] .= " " . count($undetectedList) . " soal default ke A.";
            $result['has_warnings'] = true;
        }
        if ($savedCount === 0) {
            $result['success'] = false;
            if (!empty($errors)) {
                $result['message'] = 'Import gagal: tidak ada soal yang berhasil diimport ('
                    . count($errors) . ' soal gagal validasi). Periksa kembali isi dan format soal di file.';
            } else {
                $result['message'] = 'Import gagal: tidak ada soal yang terdeteksi di dalam file. '
                    . 'Pastikan format file sesuai template — soal pakai list bernomor (1. 2. 3.) '
                    . 'dan opsi pakai sub-list berhuruf (a. b. c. d. e.).';
            }
        }

        return $result;
    }

    public function getUndetectedAnswers() { return $this->undetectedAnswers; }
}
