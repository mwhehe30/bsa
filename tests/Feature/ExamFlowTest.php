<?php

namespace Tests\Feature;

use App\Models\Exam;
use App\Models\ExamGroup;
use App\Models\ExamViolation;
use App\Models\Grade;
use App\Models\KecermatanExam;
use App\Models\KecermatanMasterQuestion;
use App\Models\KecermatanQuestion;
use App\Models\KecermatanResult;
use App\Models\KecermatanSession;
use App\Models\KecermatanViolation;
use App\Models\Student;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Hash;

/**
 * Uji end-to-end semua alur ujian terhadap database asli (BUKAN :memory:).
 * Menjalankan: ujian reguler, ujian 0 soal, auto-submit waktu habis,
 * blokir middleware, dan ujian kecermatan lengkap.
 */
class ExamFlowTest extends BaseTestCase
{
    public function createApplication()
    {
        // Paksa environment testing agar VerifyCsrfToken melewatkan verifikasi
        // (runningUnitTests()) — phpunit.xml tidak mengubah env binding karena
        // app sudah ter-bootstrap oleh artisan sebelum PHPUnit berjalan.
        putenv('APP_ENV=testing');
        $_ENV['APP_ENV'] = 'testing';
        $_SERVER['APP_ENV'] = 'testing';

        $app = require __DIR__.'/../../bootstrap/app.php';
        $app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

        return $app;
    }

    protected function setUp(): void
    {
        parent::setUp();

        // Tes ini butuh database MySQL asli beserta data ujiannya. Kalau
        // dijalankan dengan DB default (sqlite :memory:), langsung di-skip.
        if (config('database.default') === 'sqlite') {
            $this->markTestSkipped('Butuh database MySQL asli dengan data ujian.');
        }

        // Bersihkan data percobaan siswa test agar pengujian idempotent.
        $student = Student::where('email', 'test@buweuk.test')->first();
        if ($student) {
            Grade::where('student_id', $student->id)->delete();

            $sessionIds = KecermatanSession::where('student_id', $student->id)->pluck('id');
            KecermatanResult::whereIn('session_id', $sessionIds)->delete();
            KecermatanViolation::whereIn('session_id', $sessionIds)->delete();
            KecermatanQuestion::whereIn('session_id', $sessionIds)->delete();
            KecermatanSession::where('student_id', $student->id)->delete();

            $examGroupIds = ExamGroup::where('student_id', $student->id)->pluck('id');
            ExamViolation::whereIn('exam_group_id', $examGroupIds)->delete();
            ExamGroup::where('student_id', $student->id)->delete();
        }
    }

    protected function testStudent(): Student
    {
        return Student::firstOrCreate(
            ['email' => 'test@buweuk.test'],
            [
                'name' => 'Siswa Test',
                'gender' => 'L',
                'password' => Hash::make('password'),
                'must_change_password' => false,
                'is_active' => true,
            ]
        );
    }

    public function test_regular_exam_full_flow(): void
    {
        $student = $this->testStudent();
        $exam = Exam::with('questions')->findOrFail(1);

        $this->actingAs($student, 'student');

        // 1. Halaman konfirmasi
        $this->get(route('student.exams.confirmation', $exam->id))->assertOk();

        // 2. Mulai ujian -> redirect ke halaman ujian
        $this->get(route('student.exams.startExam', $exam->id))->assertRedirect();

        $grade = Grade::where('student_id', $student->id)
            ->where('exam_id', $exam->id)
            ->where('status', 'in_progress')
            ->first();
        $this->assertNotNull($grade, 'Grade in_progress harus dibuat');
        $this->assertEquals(
            $exam->questions()->count(),
            \App\Models\Answer::where('grade_id', $grade->id)->count()
        );

        // 3. Halaman ujian terbuka
        $this->get(route('student.exams.show', [$exam->id, $grade->id, 1]))->assertOk();

        // 4. Coba buka dashboard saat ujian aktif -> harus dipaksa balik ke ujian
        $res = $this->get(route('student.dashboard'));
        $this->assertTrue($res->isRedirect(), 'Dashboard harus diblokir saat ujian aktif');
        $this->assertStringContainsString('/student/exam/', (string) $res->headers->get('Location'));

        // 5. Simpan jawaban batch (5 soal) — format sama seperti frontend
        $questions = $exam->questions()->take(5)->get();
        $answerBatch = $questions->map(fn ($q) => ['question_id' => $q->id, 'answer' => 1])->all();
        $this->postJson(route('student.exams.answerQuestions'), [
            'grade_id' => $grade->id,
            'answers' => $answerBatch,
        ])->assertOk();

        // 6. Submit (is_auto_submit=true agar tidak wajib semua terjawab)
        $answerMap = $questions->mapWithKeys(fn ($q) => [$q->id => 1])->all();
        $this->post(route('student.exams.endExam'), [
            'exam_id' => $exam->id,
            'grade_id' => $grade->id,
            'exam_group_id' => $grade->id,
            'answers' => $answerMap,
            'is_auto_submit' => true,
        ])->assertRedirect();

        $grade->refresh();
        $this->assertEquals('completed', $grade->status);
        $this->assertNotNull($grade->end_time);

        // 7. Halaman hasil
        $this->get(route('student.exams.resultExam', $grade->id))->assertOk();

        // 8. Setelah selesai, siswa keluar dari isolir
        $examGroup = ExamGroup::where('student_id', $student->id)->where('exam_id', $exam->id)->first();
        $this->assertNotNull($examGroup);
        $this->assertFalse($examGroup->is_blocked, 'Exam group harus terbuka setelah ujian selesai');

        fwrite(STDOUT, "  [OK] Ujian reguler #{$exam->id} ({$exam->title}): alur lengkap selesai, nilai tersimpan\n");
    }

    public function test_regular_exam_zero_questions(): void
    {
        $student = $this->testStudent();
        $exam = Exam::findOrFail(3); // 0 soal

        $this->actingAs($student, 'student');

        $this->get(route('student.exams.startExam', $exam->id))->assertRedirect();

        $grade = Grade::where('student_id', $student->id)
            ->where('exam_id', $exam->id)
            ->where('status', 'in_progress')
            ->first();
        $this->assertNotNull($grade);

        $this->post(route('student.exams.endExam'), [
            'exam_id' => $exam->id,
            'grade_id' => $grade->id,
            'exam_group_id' => $grade->id,
            'answers' => [],
            'is_auto_submit' => true,
        ])->assertRedirect();

        $grade->refresh();
        $this->assertEquals('completed', $grade->status);

        fwrite(STDOUT, "  [OK] Ujian reguler #{$exam->id} ({$exam->title}): 0 soal tetap bisa selesai\n");
    }

    public function test_expired_regular_exam_auto_submits_on_return(): void
    {
        $student = $this->testStudent();
        $exam = Exam::findOrFail(2);

        $this->actingAs($student, 'student');

        $this->get(route('student.exams.startExam', $exam->id));
        $grade = Grade::where('student_id', $student->id)
            ->where('exam_id', $exam->id)
            ->where('status', 'in_progress')
            ->first();
        $this->assertNotNull($grade);

        // Palsukan start_time ke masa lalu: durasi (29 menit) sudah lewat.
        $grade->update(['start_time' => now()->subMinutes($exam->duration + 5)]);

        // Buka dashboard -> middleware harus memfinalisasi (200, bukan redirect)
        $res = $this->get(route('student.dashboard'));
        $res->assertOk();

        $grade->refresh();
        $this->assertEquals('completed', $grade->status, 'Ujian yang lewat durasi harus otomatis difinalisasi');

        fwrite(STDOUT, "  [OK] Auto-submit waktu habis: ujian #{$exam->id} difinalisasi middleware saat siswa kembali\n");
    }

    public function test_kecermatan_full_flow(): void
    {
        $student = $this->testStudent();
        $this->actingAs($student, 'student');

        $admin = \App\Models\User::first();
        $kecExam = KecermatanExam::create([
            'title' => 'TEST Flow Kecermatan',
            'is_active' => true,
            'created_by' => $admin ? $admin->id : null,
        ]);

        try {
            // 1. Mulai lewat route start kecermatan (standalone)
            $this->post(route('student.kecermatan.start', $kecExam->id), ['exam_type' => 'huruf'])
                ->assertRedirect();

            $session = KecermatanSession::where('student_id', $student->id)
                ->where('kecermatan_exam_id', $kecExam->id)
                ->where('status', 'in_progress')
                ->first();
            $this->assertNotNull($session, 'Sesi kecermatan harus dibuat');
            $this->assertEquals(500, $session->questions()->count(), '500 soal harus tergenerate');

            // 2. Halaman ujian kolom 1
            $this->get(route('student.kecermatan.exam', [$session->id, 1, 1]))->assertOk();

            // 3. Jawab 5 soal kolom 1 (batch)
            $questions = $session->questions()->where('column_number', 1)->take(5)->get();
            $answers = $questions
                ->map(fn ($q) => ['question_id' => $q->id, 'answer' => 'A', 'time_spent' => 2])
                ->all();
            $this->postJson(route('student.kecermatan.submitAnswers'), ['answers' => $answers])->assertOk();

            // 4. Finalisasi semua kolom lewat column-timeout kolom 10
            $this->post(route('student.kecermatan.columnTimeout', $session->id), [
                'column_number' => 10,
                'answers' => [],
            ])->assertRedirect();

            $session->refresh();
            $this->assertEquals('completed', $session->status);
            $this->assertEquals(10, KecermatanResult::where('session_id', $session->id)->count());

            // 5. Halaman hasil
            $this->get(route('student.kecermatan.result', $session->id))->assertOk();

            fwrite(STDOUT, "  [OK] Kecermatan flow: sesi selesai, 10 kolom terhitung, halaman hasil terbuka\n");
        } finally {
            // Bersihkan ujian test + datanya
            $sessionIds = KecermatanSession::where('kecermatan_exam_id', $kecExam->id)->pluck('id');
            KecermatanResult::whereIn('session_id', $sessionIds)->delete();
            KecermatanViolation::whereIn('session_id', $sessionIds)->delete();
            KecermatanQuestion::whereIn('session_id', $sessionIds)->delete();
            KecermatanSession::whereIn('id', $sessionIds)->delete();
            KecermatanMasterQuestion::where('kecermatan_exam_id', $kecExam->id)->delete();
            $kecExam->delete();
        }

        fwrite(STDOUT, "  [OK] Ujian kecermatan test sudah dibersihkan dari database\n");
    }

    // ── Fitur blokir + sisi admin ────────────────────────────────────────────

    /** Ambil props Inertia dari respons halaman. */
    private function inertiaProps($response): array
    {
        $html = $response->getContent();
        if (preg_match('/data-page="(.*?)"/s', $html, $m)) {
            $json = html_entity_decode($m[1], ENT_QUOTES | ENT_HTML5);
            $decoded = json_decode($json, true);

            return $decoded['props'] ?? [];
        }

        return [];
    }

    /** Catat N pelanggaran tab_switch pada ujian tertentu (siswa harus sudah login). */
    private function blockStudentInExam(Student $student, Exam $exam, int $times = 1): void
    {
        $examGroup = ExamGroup::where('student_id', $student->id)
            ->where('exam_id', $exam->id)
            ->firstOrFail();

        for ($i = 0; $i < $times; $i++) {
            $this->postJson(route('student.exam.logViolation'), [
                'exam_group_id' => $examGroup->id,
                'exam_id' => $exam->id,
                'violation_type' => 'tab_switch',
            ])->assertStatus(403);
        }
    }

    public function test_block_violation_and_admin_unblock(): void
    {
        $student = $this->testStudent();
        $exam = Exam::findOrFail(1);

        $this->actingAs($student, 'student');
        $this->get(route('student.exams.startExam', $exam->id));
        $examGroup = ExamGroup::where('student_id', $student->id)->where('exam_id', $exam->id)->first();
        $this->assertNotNull($examGroup);

        // 1 pelanggaran -> langsung blokir
        $res = $this->postJson(route('student.exam.logViolation'), [
            'exam_group_id' => $examGroup->id,
            'exam_id' => $exam->id,
            'violation_type' => 'tab_switch',
        ]);
        $res->assertStatus(403);
        $data = $res->json();
        $this->assertTrue($data['is_blocked']);
        $this->assertEquals(1, $data['violation_count']);
        $this->assertFalse($data['should_auto_submit']);

        $examGroup->refresh();
        $this->assertTrue($examGroup->is_blocked);

        // Admin: halaman Siswa Terisolir menampilkan siswa + info isolir
        $admin = \App\Models\User::first();
        $this->actingAs($admin);
        $res = $this->get(route('admin.students.isolated'));
        $res->assertOk();
        $props = $this->inertiaProps($res);
        $found = collect($props['students']['data'] ?? [])->firstWhere('id', $student->id);
        $this->assertNotNull($found, 'Siswa terblokir harus muncul di halaman isolir');
        $this->assertNotEmpty($found['isolation_info'] ?? [], 'Info isolir (ujian + pelanggaran) harus terisi');
        $this->assertEquals($exam->title, $found['isolation_info'][0]['exam_name']);
        $this->assertEquals(1, $found['isolation_info'][0]['violation_count']);

        // Admin buka isolir
        $this->put(route('admin.students.toggleActive', $student->id), ['is_active' => true])->assertRedirect();

        $examGroup->refresh();
        $this->assertFalse($examGroup->is_blocked);
        $student->refresh();
        $this->assertTrue($student->is_active);

        // Halaman isolir sudah tidak menampilkan siswa
        $res = $this->get(route('admin.students.isolated'));
        $props = $this->inertiaProps($res);
        $found = collect($props['students']['data'] ?? [])->firstWhere('id', $student->id);
        $this->assertNull($found, 'Siswa harus hilang dari halaman isolir setelah dibuka');

        fwrite(STDOUT, "  [OK] Blokir -> tampil di isolir admin (dengan info) -> buka isolir -> hilang dari daftar\n");
    }

    public function test_three_violations_auto_submit(): void
    {
        $student = $this->testStudent();
        $exam = Exam::findOrFail(1);

        $this->actingAs($student, 'student');
        $this->get(route('student.exams.startExam', $exam->id));
        $grade = Grade::where('student_id', $student->id)->where('exam_id', $exam->id)->where('status', 'in_progress')->first();
        $examGroup = ExamGroup::where('student_id', $student->id)->where('exam_id', $exam->id)->first();
        $this->assertNotNull($grade);

        // Mencapai 3 pelanggaran lewat middleware: tiap kali mencoba keluar ke
        // route lain = 1 pelanggaran (endpoint logViolation berhenti di 1 karena
        // langsung memblokir; yang menumpuk adalah percobaan keluar).
        $escapeRoutes = [
            route('student.dashboard'),
            route('student.profile'),
            route('student.exams.confirmation', $exam->id),
        ];
        foreach ($escapeRoutes as $url) {
            $this->get($url)->assertRedirect();
        }

        $this->assertEquals(3, $examGroup->refresh()->violation_count);
        $this->assertTrue($examGroup->is_blocked);

        // Frontend mendeteksi violation_count >= 3 lewat polling -> auto-submit

        // Simulasi auto-submit dari frontend
        $this->post(route('student.exams.endExam'), [
            'exam_id' => $exam->id,
            'grade_id' => $grade->id,
            'exam_group_id' => $grade->id,
            'answers' => [],
            'is_auto_submit' => true,
        ])->assertRedirect();

        $grade->refresh();
        $this->assertEquals('completed', $grade->status);
        $this->assertFalse($examGroup->refresh()->is_blocked, 'Setelah selesai, isolir harus terbuka');

        fwrite(STDOUT, "  [OK] Pelanggaran ke-3 -> should_auto_submit -> ujian selesai -> keluar isolir\n");
    }

    public function test_isolated_search_does_not_leak(): void
    {
        $student = $this->testStudent();
        $exam = Exam::findOrFail(1);
        $otherStudent = Student::where('email', '!=', 'test@buweuk.test')->first();
        $this->assertNotNull($otherStudent, 'Butuh minimal satu siswa lain untuk uji kebocoran pencarian');

        // Blokir siswa test
        $this->actingAs($student, 'student');
        $this->get(route('student.exams.startExam', $exam->id));
        $this->blockStudentInExam($student, $exam, 1);

        $admin = \App\Models\User::first();
        $this->actingAs($admin);

        // Cari email siswa TIDAK terisolir -> tidak boleh muncul
        $res = $this->get(route('admin.students.isolated', ['q' => $otherStudent->email]));
        $props = $this->inertiaProps($res);
        $ids = collect($props['students']['data'] ?? [])->pluck('id')->all();
        $this->assertNotContains($otherStudent->id, $ids, 'Siswa non-isolir tidak boleh bocor saat cari email');

        // Cari nama siswa terisolir -> muncul
        $res = $this->get(route('admin.students.isolated', ['q' => $student->name]));
        $props = $this->inertiaProps($res);
        $ids = collect($props['students']['data'] ?? [])->pluck('id')->all();
        $this->assertContains($student->id, $ids);

        fwrite(STDOUT, "  [OK] Pencarian halaman isolir tidak bocor ke siswa non-isolir\n");
    }

    public function test_admin_bulk_activate(): void
    {
        $student = $this->testStudent();

        // Blokir lewat alur normal di ujian 1
        $this->actingAs($student, 'student');
        $this->get(route('student.exams.startExam', 1));
        $this->blockStudentInExam($student, Exam::findOrFail(1), 1);

        // Selesaikan ujian 1 agar bisa mulai ujian lain (selesai membuka isolir)
        $grade1 = Grade::where('student_id', $student->id)->where('exam_id', 1)->where('status', 'in_progress')->first();
        $this->post(route('student.exams.endExam'), [
            'exam_id' => 1,
            'grade_id' => $grade1->id,
            'exam_group_id' => $grade1->id,
            'answers' => [],
            'is_auto_submit' => true,
        ]);

        // Blokir lagi di ujian 2 lewat alur normal
        $this->get(route('student.exams.startExam', 2));
        $this->blockStudentInExam($student, Exam::findOrFail(2), 1);

        // Simulasikan blokir kedua (ujian 1) yang masih tersisa di DB — bulk
        // activate harus membuka SEMUA exam_group siswa sekaligus.
        ExamGroup::where('student_id', $student->id)->where('exam_id', 1)->update(['is_blocked' => true]);

        $group1 = ExamGroup::where('student_id', $student->id)->where('exam_id', 1)->first();
        $group2 = ExamGroup::where('student_id', $student->id)->where('exam_id', 2)->first();
        $this->assertTrue($group1->is_blocked);
        $this->assertTrue($group2->is_blocked);

        // Admin bulk activate
        $admin = \App\Models\User::first();
        $this->actingAs($admin);
        $this->post(route('admin.students.bulkActivate'), ['student_ids' => [$student->id]])->assertRedirect();

        $this->assertFalse($group1->refresh()->is_blocked);
        $this->assertFalse($group2->refresh()->is_blocked);
        $this->assertTrue($student->refresh()->is_active);

        fwrite(STDOUT, "  [OK] Bulk activate admin: semua exam_group siswa terbuka\n");
    }

    public function test_kecermatan_block_and_admin_unblock(): void
    {
        $student = $this->testStudent();
        $admin = \App\Models\User::first();

        // Ujian kecermatan test yang tertaut ke ujian reguler #1 agar sync
        // ke exam_groups (halaman isolir) berjalan.
        $kecExam = KecermatanExam::create([
            'exam_id' => 1,
            'title' => 'TEST Kecermatan Admin',
            'is_active' => true,
            'created_by' => $admin ? $admin->id : null,
        ]);

        try {
            $this->actingAs($student, 'student');
            $this->post(route('student.kecermatan.start', $kecExam->id), ['exam_type' => 'huruf'])->assertRedirect();

            $session = KecermatanSession::where('student_id', $student->id)
                ->where('kecermatan_exam_id', $kecExam->id)
                ->where('status', 'in_progress')
                ->first();
            $this->assertNotNull($session);

            // Pelanggaran kecermatan -> blokir sesi + sync ke exam_groups
            $res = $this->postJson(route('student.kecermatan.logViolation'), [
                'session_id' => $session->id,
                'violation_type' => 'tab_switch',
                'column_number' => 1,
                'question_number' => 1,
            ]);
            $res->assertStatus(403);
            $data = $res->json();
            $this->assertTrue($data['is_blocked']);
            $this->assertEquals(1, $data['violation_count']);

            $session->refresh();
            $this->assertTrue($session->is_blocked);

            $examGroup = ExamGroup::where('student_id', $student->id)->where('exam_id', 1)->first();
            $this->assertNotNull($examGroup);
            $this->assertTrue($examGroup->is_blocked, 'Sync blokir ke exam_groups harus jalan');

            // Admin: muncul di halaman isolir
            $this->actingAs($admin);
            $res = $this->get(route('admin.students.isolated'));
            $props = $this->inertiaProps($res);
            $found = collect($props['students']['data'] ?? [])->firstWhere('id', $student->id);
            $this->assertNotNull($found, 'Siswa terblokir kecermatan harus muncul di halaman isolir');

            // Admin buka isolir -> sesi kecermatan ikut terbuka
            $this->put(route('admin.students.toggleActive', $student->id), ['is_active' => true])->assertRedirect();
            $session->refresh();
            $this->assertFalse($session->is_blocked, 'Sesi kecermatan harus ikut terbuka');
            $this->assertFalse($examGroup->refresh()->is_blocked);

            fwrite(STDOUT, "  [OK] Blokir kecermatan -> sync isolir admin -> buka isolir ikut membuka sesi\n");
        } finally {
            $sessionIds = KecermatanSession::where('kecermatan_exam_id', $kecExam->id)->pluck('id');
            KecermatanResult::whereIn('session_id', $sessionIds)->delete();
            KecermatanViolation::whereIn('session_id', $sessionIds)->delete();
            KecermatanQuestion::whereIn('session_id', $sessionIds)->delete();
            KecermatanSession::whereIn('id', $sessionIds)->delete();
            KecermatanMasterQuestion::where('kecermatan_exam_id', $kecExam->id)->delete();
            $kecExam->delete();
        }
    }
}
