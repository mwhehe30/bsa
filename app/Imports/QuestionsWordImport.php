<?php
namespace App\Imports;

use App\Helpers\ImageConverter;
use App\Models\Question;
use Illuminate\Http\UploadedFile;
use PhpOffice\PhpWord\IOFactory;
use Illuminate\Support\Facades\Log;

class QuestionsWordImport
{
    protected $exam_id;
    protected $isPersonality;
    protected $imgCounter = 1;
    protected $undetectedAnswers = [];
    protected $personalityPoints = [];

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
        try {
            $phpWord = IOFactory::load($file->getPathname());
            $questions = [];
            $current = null;
            $seqOptIdx = 0;
            $optMap = [0 => 'A', 1 => 'B', 2 => 'C', 3 => 'D', 4 => 'E'];
            $prevWasQuestion = false;
            $explanationText = '';
            $hasOptions = false;
            $questionNumber = 0;
            $pendingImages = [];
            $lastElementClass = null; // Track element class untuk context

            foreach ($phpWord->getSections() as $section) {
                foreach ($section->getElements() as $element) {
                    $elementClass = basename(str_replace('\\', '/', get_class($element)));
                    
                    // 1. EKSTRAK GAMBAR DARI SEMUA ELEMEN (TERMASUK TABEL)
                    $images = [];
                    $this->extractImages($element, $images);

                    // 2. EKSTRAK TEKS DARI SEMUA ELEMEN (TERMASUK TABEL)
                    $text = $this->getElemText($element);

                    // Cek apakah ini adalah ListItem (Soal atau Opsi ber-nomor)
                    if ($element instanceof \PhpOffice\PhpWord\Element\ListItemRun
                        || $element instanceof \PhpOffice\PhpWord\Element\ListItem) {

                        $depth = method_exists($element, 'getDepth') ? $element->getDepth() : 0;

                        if ($depth == 0) {
                            // Simpan pertanyaan sebelumnya
                            if ($current !== null) {
                                $this->attachPendingImages($current, $pendingImages, $prevWasQuestion, $hasOptions, $seqOptIdx, $optMap);
                                $pendingImages = [];
                                $current['explanation'] = $explanationText;
                                if ($current['answer'] === null) $this->extractAnswerFromExplanation($current);
                                $questions[] = $current;
                                $explanationText = '';
                                $hasOptions = false;
                            }

                            $questionNumber++;
                            $current = [
                                'question' => $this->deduplicateText(trim($text)),
                                'options' => [],
                                'answer' => null,
                                'question_number' => $questionNumber,
                            ];
                            
                            // Handle images di ListItem depth=0 (soal)
                            if (!empty($images)) {
                                Log::info("Processing " . count($images) . " images in ListItem depth=0 (question)");
                                foreach ($images as $img) {
                                    $url = $this->saveImage($img);
                                    if ($url) {
                                        $imgTag = '<br/><img src="' . $url . '" style="max-width:100%;height:auto">';
                                        $current['question'] .= $imgTag;
                                        Log::info("Image added to question from ListItem depth=0");
                                    }
                                }
                            }
                            
                            $seqOptIdx = 0;
                            $prevWasQuestion = true;
                            $hasOptions = false;
                            $this->extractAnswerFromText($current, $text);
                        } elseif ($depth == 1 && $current !== null) {
                            $prevWasQuestion = false;
                            $hasOptions = true;
                            $this->extractAnswerFromText($current, $text);
                            $cleanText = $this->cleanOptionText($text);
                            $this->parseOptionLine($cleanText, $current['options'], $optMap, $seqOptIdx);
                            
                            // Handle images di ListItem depth=1 (opsi)
                            if (!empty($images)) {
                                Log::info("Processing " . count($images) . " images in ListItem depth=1 (option)");
                                $optionKeys = array_keys($current['options']);
                                $lastOptKey = end($optionKeys);
                                
                                if ($lastOptKey && isset($current['options'][$lastOptKey])) {
                                    foreach ($images as $img) {
                                        $url = $this->saveImage($img);
                                        if ($url) {
                                            $imgTag = '<br/><img src="' . $url . '" style="max-width:100%;height:auto">';
                                            $current['options'][$lastOptKey] .= $imgTag;
                                            Log::info("Image added to option $lastOptKey from ListItem depth=1");
                                        }
                                    }
                                } else {
                                    Log::warning("No option key found for images in ListItem depth=1");
                                }
                            }
                        }
                    } else {
                        // Elemen biasa: TextRun, Table, Image, dll.

                        // Handle Gambar DULU sebelum reset prevWasQuestion
                        if (!empty($images) && $current !== null) {
                            Log::info("Processing " . count($images) . " images for Q#{$questionNumber}, prevWasQuestion=" . ($prevWasQuestion ? 'true' : 'false') . ", hasOptions=" . ($hasOptions ? 'true' : 'false') . ", seqOptIdx=$seqOptIdx");
                            
                            foreach ($images as $img) {
                                $url = $this->saveImage($img);
                                if ($url) {
                                    Log::info("Image saved: $url");
                                    $imgTag = '<br/><img src="' . $url . '" style="max-width:100%;height:auto">';
                                    
                                    // Logic untuk menentukan kemana gambar ditambahkan:
                                    // 1. Jika langsung setelah pertanyaan (prevWasQuestion=true) → ke question
                                    // 2. Jika setelah opsi DAN ada text/explanation → ke question (gambar pembahasan)
                                    // 3. Jika setelah opsi dan belum ada text → ke opsi terakhir (gambar opsi)
                                    
                                    if ($prevWasQuestion) {
                                        // Gambar langsung setelah pertanyaan
                                        $current['question'] .= $imgTag;
                                        Log::info("Image added to question (prevWasQuestion=true)");
                                    } elseif ($hasOptions && count($current['options']) > 0) {
                                        // Ada opsi, masukkan ke opsi terakhir yang dibaca
                                        $optionKeys = array_keys($current['options']);
                                        $lastOptKey = end($optionKeys);
                                        if ($lastOptKey && isset($current['options'][$lastOptKey])) {
                                            $current['options'][$lastOptKey] .= $imgTag;
                                            Log::info("Image added to option $lastOptKey (count=" . count($current['options']) . ")");
                                        } else {
                                            $pendingImages[] = $imgTag;
                                            Log::info("Image added to pendingImages (no option found)");
                                        }
                                    } else {
                                        $pendingImages[] = $imgTag;
                                        Log::info("Image added to pendingImages (default)");
                                    }
                                } else {
                                    Log::warning("saveImage() returned null");
                                }
                            }
                        }

                        // Handle Teks (Termasuk teks di dalam Tabel)
                        // Reset prevWasQuestion SETELAH gambar diproses
                        if (!empty(trim($text)) && $current !== null) {
                            if (preg_match('/^\s*(\d+)\.\s+[A-Z0-9]/', $text) && !$prevWasQuestion) {
                                // Biarkan diproses di iterasi berikutnya jika ini ListItem
                            } else {
                                // EKSTRAK POIN UNTUK SOAL KEPRIBADIAN
                                if ($this->isPersonality) {
                                    $this->extractPersonalityPoints($current, $text);
                                } else {
                                    $explanationText .= ' ' . $text;
                                    $this->extractAnswerFromText($current, $text);
                                }
                                
                                // Reset prevWasQuestion untuk elemen berikutnya
                                // Kecuali jika ini element kosong (Title, TextBreak, dll)
                                if (strlen(trim(strip_tags($text))) > 3) {
                                    $prevWasQuestion = false;
                                }
                            }
                        } else {
                            // Element kosong (TextBreak, Title tanpa text, dll)
                            // JANGAN reset prevWasQuestion agar gambar di element berikutnya masih bisa masuk
                        }
                        
                        // Update last element class for next iteration
                        $lastElementClass = $elementClass;
                    }
                }
            }

            // Handle pertanyaan terakhir
            if ($current !== null) {
                $this->attachPendingImages($current, $pendingImages, $prevWasQuestion, $hasOptions, $seqOptIdx, $optMap);
                $current['explanation'] = $explanationText;
                if ($current['answer'] === null) $this->extractAnswerFromExplanation($current);
                $questions[] = $current;
            }

            // Filter pertanyaan kosong
            $questions = array_values(
                array_filter($questions, fn($q) => trim(strip_tags($q['question'])) !== '')
            );

            // Post-processing
            foreach ($questions as &$q) {
                if ($q['answer'] === null && !empty($q['options'])) $this->extractAnswerFromExplanation($q);
                if ($q['answer'] === null && !empty($q['options'])) $this->extractAnswerFromMarkedOption($q);
                if ($q['answer'] === null && !empty($q['options'])) $this->extractAnswerFromSingleOption($q);

                // CEK APAKAH SOAL BERGAMBAR
                $hasImages = stripos($q['question'], '<img') !== false;

                // JIKA TIDAK ADA OPSI TAPI SOAL BERGAMBAR, BUAT PLACEHOLDER A-E
                if (empty($q['options']) || count(array_filter($q['options'])) === 0) {
                    if ($hasImages) {
                        $q['options'] = [
                            'A' => '[Gambar A]', 'B' => '[Gambar B]', 'C' => '[Gambar C]',
                            'D' => '[Gambar D]', 'E' => '[Gambar E]',
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

            $undetectedList = [];
            foreach ($questions as $q) {
                if (isset($q['answer_detected']) && $q['answer_detected'] === false) {
                    $undetectedList[] = [
                        'number' => $q['question_number'] ?? '?',
                        'preview' => substr(strip_tags($q['question']), 0, 80),
                    ];
                }
            }
            $result['undetected_answers'] = $undetectedList;

            return $result;
        } catch (\Exception $e) {
            Log::error("Import failed: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Gagal membaca file: ' . $e->getMessage(),
                'count' => 0,
            ];
        }
    }

    protected function attachPendingImages(&$current, $pendingImages, $prevWasQuestion, $hasOptions, $seqOptIdx, $optMap)
    {
        if (empty($pendingImages)) return;
        foreach ($pendingImages as $imgTag) {
            if ($prevWasQuestion) {
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
    // METODE EKSTRAKSI JAWABAN
    // -------------------------------------------------------------------------
    
    /**
     * EKSTRAKSI POIN UNTUK SOAL KEPRIBADIAN
     * Format: POIN: A=5|B=4|C=3|D=2|E=1
     */
    protected function extractPersonalityPoints(array &$question, string $text): void
    {
        // Cari pattern POIN: A=5|B=4|C=3|D=2|E=1
        if (preg_match('/POIN\s*:\s*(.+)$/i', $text, $matches)) {
            $pointsStr = trim($matches[1]);
            $pairs = explode('|', $pointsStr);
            
            $points = [];
            foreach ($pairs as $pair) {
                if (preg_match('/([A-E])\s*=\s*(\d+)/i', $pair, $m)) {
                    $option = strtoupper($m[1]);
                    $value = (int)$m[2];
                    
                    // Map A-E ke 1-5
                    $optionMap = ['A' => 1, 'B' => 2, 'C' => 3, 'D' => 4, 'E' => 5];
                    if (isset($optionMap[$option])) {
                        $points[$optionMap[$option]] = $value;
                    }
                }
            }
            
            if (!empty($points)) {
                $question['personality_points'] = $points;
                Log::info("Personality points extracted: " . json_encode($points));
            }
        }
    }
    
    protected function deduplicateText(string $text): string
    {
        // Remove exact duplicate patterns (e.g., "text text" -> "text")
        // Match any sequence of 3+ chars that repeats immediately
        $text = preg_replace('/(.{3,}?)\1+/', '$1', $text);
        return $text;
    }

    protected function extractAnswerFromText(array &$question, ?string $text = null): void
    {
        $textToCheck = $text ?? $question['question'] ?? '';
        $availableOptions = array_keys($question['options'] ?? ['A', 'B', 'C', 'D', 'E']);

        // Pattern: Jawaban : C atau Jawaban: C
        if (preg_match('/Jawaban\s*:\s*([A-E])/i', $textToCheck, $m)) {
            $found = strtoupper($m[1]);
            $question['answer'] = $found;
            return;
        }
        if (preg_match('/jawaban\s+(?:yang\s+paling\s+tepat\s+)?(?:adalah\s+)?["\']?([A-E])["\']?/i', $textToCheck, $m)) {
            $question['answer'] = strtoupper($m[1]);
            return;
        }
    }

    protected function extractAnswerFromExplanation(array &$question): void
    {
        $explanationText = $question['explanation'] ?? '';
        $availableOptions = array_keys($question['options'] ?? ['A', 'B', 'C', 'D', 'E']);
        if (empty($explanationText) || empty($availableOptions)) return;

        if (preg_match('/(?:jawaban(?:nya)?|yang\s+paling\s+tepat)\s+(?:adalah\s+)?["\']?([A-E])["\']?/i', $explanationText, $m)) {
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
        $text = preg_replace('/[#>]*\s*Jawaban\s*[:\-]\s*[A-E].*$/i', '', $text);
        $text = preg_replace('/\s*Pembahasan\s*[:\-].*$/i', '', $text);
        return trim($text);
    }

    protected function parseOptionLine(string $text, array &$options, array $optMap, int &$seqOptIdx): void
    {
        // Deduplicate text first
        $text = $this->deduplicateText($text);
        
        $parts = preg_split('/\t+|\s{2,}/', $text);
        foreach ($parts as $part) {
            $part = trim($part);
            if ($part === '') continue;
            if (preg_match('/^(?:\d+\.)?\s*([A-E])\.\s*(.+)$/i', $part, $m)) {
                $options[strtoupper($m[1])] = trim($m[2]);
            } elseif (preg_match('/^([A-E])\s+(.+)$/i', $part, $m)) {
                $options[strtoupper($m[1])] = trim($m[2]);
            } else {
                $letter = $optMap[$seqOptIdx] ?? null;
                if ($letter !== null) $options[$letter] = $part;
                $seqOptIdx++;
            }
        }
    }

    /**
     * EKSTRAKSI GAMBAR REKURSIF (TERMASUK TABEL, ROW, CELL)
     */
    protected function extractImages($elem, array &$images): void
    {
        if (!is_object($elem)) return;
        $class = basename(str_replace('\\', '/', get_class($elem)));

        if ($class === 'Image') {
            $images[] = $elem;
            return;
        }

        // TextRun, ListItemRun, Cell
        if (method_exists($elem, 'getElements')) {
            foreach ($elem->getElements() as $child) $this->extractImages($child, $images);
        }
        // Table -> Rows
        if (method_exists($elem, 'getRows')) {
            foreach ($elem->getRows() as $row) $this->extractImages($row, $images);
        }
        // Row -> Cells
        if (method_exists($elem, 'getCells')) {
            foreach ($elem->getCells() as $cell) $this->extractImages($cell, $images);
        }
        // Fallback children
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
            
            // TURUNKAN THRESHOLD DARI 200 KE 50 BYTES
            if (!$binary || strlen($binary) < 50) {
                return null;
            }
            
            return ImageConverter::convertAndStore($binary, 'question-images', 85);
        } catch (\Exception $e) {
            Log::error("Failed to save image: " . $e->getMessage());
            return null;
        }
    }

    /**
     * EKSTRAKSI TEKS REKURSIF (TERMASUK TABEL, ROW, CELL)
     * SKIP IMAGE ELEMENTS
     */
    protected function getElemText($elem): string
    {
        $t = '';
        if (!is_object($elem)) return '';
        $class = basename(str_replace('\\', '/', get_class($elem)));
        if ($class === 'Image') return '';

        if (method_exists($elem, 'getElements')) {
            foreach ($elem->getElements() as $child) $t .= $this->getElemText($child);
        }
        if (method_exists($elem, 'getRows')) {
            foreach ($elem->getRows() as $row) $t .= $this->getElemText($row);
        }
        if (method_exists($elem, 'getCells')) {
            foreach ($elem->getCells() as $cell) $t .= $this->getElemText($cell);
        }
        if (method_exists($elem, 'getText')) {
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

            if (empty(trim(strip_tags($q['question'])))) {
                $validationErrors[] = 'Pertanyaan kosong';
            }

            $hasContentOptions = false;
            foreach (['A', 'B', 'C', 'D', 'E'] as $opt) {
                $optionText = $q['options'][$opt] ?? '';
                if (!empty(trim($optionText)) && !in_array(strtolower(trim($optionText)), ['[gambar a]', '[gambar b]', '[gambar c]', '[gambar d]', '[gambar e]'])) {
                    $hasContentOptions = true;
                    break;
                }
            }

            $hasImages = stripos($q['question'], '<img') !== false;
            if (!$isPersonality && !$hasContentOptions && !$hasImages) {
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
                    
                    // Gunakan poin yang diekstrak dari Word, atau default
                    if (isset($q['personality_points']) && !empty($q['personality_points'])) {
                        $data['points'] = $q['personality_points'];
                        Log::info("Using extracted points for Q#{$questionNumber}: " . json_encode($q['personality_points']));
                    } else {
                        $data['points'] = ['1' => 5, '2' => 4, '3' => 3, '4' => 2, '5' => 1];
                        Log::info("Using default points for Q#{$questionNumber}");
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
            $result['message'] = 'Tidak ada soal yang berhasil diimport.';
        }

        return $result;
    }

    public function getUndetectedAnswers() { return $this->undetectedAnswers; }
}
