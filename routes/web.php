<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Hash;

// Aplikasi langsung membuka halaman login.
Route::redirect('/', '/login')->name('home');

// CSRF token terbaru untuk sesi ini — route PUBLIK (tanpa auth) karena
// dipakai recovery 419 di halaman login yang belum terautentikasi. Endpoint
// ini berlabel no-store (middleware web global) dan responsnya otomatis
// mengirim ulang cookie sesi + XSRF-TOKEN yang segar.
Route::get('/csrf-token', function () {
    return response()->json(['csrf_token' => csrf_token()]);
})->name('csrfToken');

// Route Test UI Kecermatan (Inertia Vue) - hanya untuk pengembangan lokal
if (app()->environment('local')) {
    Route::get('/test', function () {
        return \Inertia\Inertia::render('Student/Kecermatan/ExamTest');
    })->name('test.ui');

    // Route Test UI Halaman Ujian (improved, flat design) - hanya untuk pengembangan lokal
    Route::get('/test/exam-show', function () {
        return \Inertia\Inertia::render('Student/Exams/ShowTest');
    })->name('test.exam-show');
}

// Route Login Page
Route::get('/login', function () {
    if (auth()->check()) {
        return redirect()->route('admin.dashboard');
    }
    if (auth()->guard('student')->check()) {
        return redirect()->route('student.dashboard');
    }
    return \Inertia\Inertia::render('Auth/Login');
})->name('login');

//prefix "admin"
Route::prefix('admin')->group(function () {
    Route::redirect('/', '/admin/dashboard');

    // Admin Login - Rate limiting per email (bukan per IP)
    Route::post('/login', \App\Http\Controllers\Auth\AdminLoginController::class)->name('admin.login');

    //middleware "auth" (no-store kini dipasang global di web group)
    Route::group(['middleware' => ['auth']], function () {

        // CSRF token terbaru untuk sesi admin (dipakai frontend saat 419)
        Route::get('/csrf-token', function () {
            return response()->json(['csrf_token' => csrf_token()]);
        })->name('admin.csrfToken');

        //route dashboard
        Route::get('/dashboard', App\Http\Controllers\Admin\DashboardController::class)->name('admin.dashboard');

        //route resource lessons
        Route::resource('/lessons', \App\Http\Controllers\Admin\LessonController::class, ['as' => 'admin']);

        //route student import
        Route::get('/students/import', [\App\Http\Controllers\Admin\StudentController::class, 'import'])->name('admin.students.import');
        Route::post('/students/import', [\App\Http\Controllers\Admin\StudentController::class, 'storeImport'])->name('admin.students.storeImport');

        //route students - ISOLATED HARUS DI ATAS RESOURCE
        Route::get('/students/isolated', [\App\Http\Controllers\Admin\StudentController::class, 'isolated'])->name('admin.students.isolated');
        Route::put('/students/{student}/toggle-active', [\App\Http\Controllers\Admin\StudentController::class, 'toggleActive'])->name('admin.students.toggleActive');
        Route::post('/students/bulk-activate', [\App\Http\Controllers\Admin\StudentController::class, 'bulkActivate'])->name('admin.students.bulkActivate');
        Route::post('/students/bulk-delete', [\App\Http\Controllers\Admin\StudentController::class, 'bulkDelete'])->name('admin.students.bulkDelete');
        Route::post('/students/bulk-toggle-active', [\App\Http\Controllers\Admin\StudentController::class, 'bulkToggleActive'])->name('admin.students.bulkToggleActive');

        //route resource students
        Route::resource('/students', \App\Http\Controllers\Admin\StudentController::class, ['as' => 'admin']);

        //route resource exams
        Route::resource('/exams', \App\Http\Controllers\Admin\ExamController::class, ['as' => 'admin']);

        //route detail hasil kecermatan siswa
        Route::get('/kecermatan/{kecermatan}/result/{session}', [\App\Http\Controllers\Admin\KecermatanController::class, 'studentResult'])->name('admin.kecermatan.studentResult');

        // Route reset soal
        Route::delete('/exams/{id}/questions/reset', [\App\Http\Controllers\Admin\ExamController::class, 'resetQuestions'])->name('admin.exams.resetQuestions');

        //route monitor exams
        Route::get('/monitor', [\App\Http\Controllers\Admin\ExamController::class, 'monitor'])->name('admin.monitor');

        // Upload gambar dari TinyMCE (soal & pilihan jawaban)
        Route::post('/upload-image', [\App\Http\Controllers\Admin\ExamController::class, 'uploadImage'])->name('admin.uploadImage');

        //custom route for create question exam
        Route::get('/exams/{exam}/questions/create', [\App\Http\Controllers\Admin\ExamController::class, 'createQuestion'])->name('admin.exams.createQuestion');
        Route::post('/exams/{exam}/questions/store', [\App\Http\Controllers\Admin\ExamController::class, 'storeQuestion'])->name('admin.exams.storeQuestion');
        Route::get('/exams/{exam}/questions/{question}/edit', [\App\Http\Controllers\Admin\ExamController::class, 'editQuestion'])->name('admin.exams.editQuestion');
        Route::put('/exams/{exam}/questions/{question}/update', [\App\Http\Controllers\Admin\ExamController::class, 'updateQuestion'])->name('admin.exams.updateQuestion');
        Route::delete('/exams/{exam}/questions/{question}/destroy', [\App\Http\Controllers\Admin\ExamController::class, 'destroyQuestion'])->name('admin.exams.destroyQuestion');

        // Mark question as reviewed
        Route::put('/exams/{exam}/questions/{question}/mark-reviewed', [\App\Http\Controllers\Admin\ExamController::class, 'markQuestionReviewed'])->name('admin.exams.markQuestionReviewed');
        Route::post('/exams/{exam}/questions/bulk-mark-reviewed', [\App\Http\Controllers\Admin\ExamController::class, 'bulkMarkReviewed'])->name('admin.exams.bulkMarkReviewed');

        // Import Excel
        Route::get('/exams/{exam}/questions/import', [\App\Http\Controllers\Admin\ExamController::class, 'import'])->name('admin.exam.questionImport');
        Route::post('/exams/{exam}/questions/import', [\App\Http\Controllers\Admin\ExamController::class, 'storeImport'])->name('admin.exam.questionStoreImport');

        // Import Word
        Route::get('/exams/{exam}/questions/import-word', [\App\Http\Controllers\Admin\ExamController::class, 'importWord'])->name('admin.exams.importWord');
        Route::post('/exams/{exam}/questions/import-word', [\App\Http\Controllers\Admin\ExamController::class, 'storeImportWord'])->name('admin.exams.storeImportWord');

        //route index reports
        Route::get('/reports', [\App\Http\Controllers\Admin\ReportController::class, 'index'])->name('admin.reports.index');
        Route::get('/reports/filter', [\App\Http\Controllers\Admin\ReportController::class, 'filter'])->name('admin.reports.filter');
        Route::get('/reports/students', [\App\Http\Controllers\Admin\ReportController::class, 'students'])->name('admin.reports.students');
        Route::get('/reports/students/select-exams', [\App\Http\Controllers\Admin\ReportController::class, 'selectExams'])->name('admin.reports.students.selectExams');
        Route::get('/reports/students/bulk-print', [\App\Http\Controllers\Admin\ReportController::class, 'bulkPrintStudent'])->name('admin.reports.students.bulkPrint');
        Route::get('/reports/export', [\App\Http\Controllers\Admin\ReportController::class, 'export'])->name('admin.reports.export');
        Route::get('/reports/{id}/print', [\App\Http\Controllers\Admin\ReportController::class, 'print'])->name('admin.reports.print');
        Route::get('/reports/student/{id}/print', [\App\Http\Controllers\Admin\ReportController::class, 'printStudent'])->name('admin.reports.printStudent');

        //route logout admin
        Route::post('/logout', function () {
            auth()->logout();
            session()->invalidate();
            session()->regenerateToken();
            return redirect('/login');
        })->name('admin.logout');
    });
});

// Send OTP
Route::post('/student/send-otp', \App\Http\Controllers\Student\OtpController::class)->name('student.sendOtp');

//login students — Rate limiting per email (bukan per IP)
Route::post('/student/login', App\Http\Controllers\Student\LoginController::class)
    ->name('student.login');

//logout student
Route::post('/student/logout', function () {
    auth()->guard('student')->logout();
    session()->invalidate();
    session()->regenerateToken();
    return redirect('/login');
})->name('student.logout');

//prefix "student"
Route::prefix('student')->group(function () {
    Route::redirect('/', '/student/dashboard');

    //middleware "student" (no-store kini dipasang global di web group)
    Route::group(['middleware' => ['student']], function () {

        // CSRF token terbaru untuk sesi siswa (dipakai frontend saat 419)
        Route::get('/csrf-token', function () {
            return response()->json(['csrf_token' => csrf_token()]);
        })->name('student.csrfToken');

        //route dashboard
        Route::get('/dashboard', App\Http\Controllers\Student\DashboardController::class)->name('student.dashboard');

        //route exam confirmation
        Route::get('/exam-confirmation/{id}', [App\Http\Controllers\Student\ExamController::class, 'confirmation'])->name('student.exams.confirmation');
        Route::get('/exam-start/{id}', [App\Http\Controllers\Student\ExamController::class, 'startExam'])->name('student.exams.startExam');
        Route::get('/exam/{exam_id}/{grade_id}/{page}', [App\Http\Controllers\Student\ExamController::class, 'show'])->name('student.exams.show');
        Route::put('/exam-duration/update/{grade_id}', [App\Http\Controllers\Student\ExamController::class, 'updateDuration'])->name('student.exams.update_duration');
        Route::post('/exam-answer', [App\Http\Controllers\Student\ExamController::class, 'answerQuestion'])->name('student.exams.answerQuestion');
        Route::post('/exam-answers', [App\Http\Controllers\Student\ExamController::class, 'answerQuestions'])->name('student.exams.answerQuestions');
        Route::post('/exam-end', [App\Http\Controllers\Student\ExamController::class, 'endExam'])->name('student.exams.endExam');
        Route::get('/exam-result/{grade_id}', [App\Http\Controllers\Student\ExamController::class, 'resultExam'])->name('student.exams.resultExam');
        Route::get('/exam-result/{grade_id}/discussion', [App\Http\Controllers\Student\ExamController::class, 'downloadDiscussion'])->name('student.exams.downloadDiscussion');

        //route profile & change password
        Route::get('/profile', [App\Http\Controllers\Student\ProfileController::class, 'index'])->name('student.profile');
        Route::put('/update-password', [App\Http\Controllers\Student\ProfileController::class, 'updatePassword'])->name('student.updatePassword');

        // Keamanan
        Route::post('/exam-security/log-violation', [App\Http\Controllers\Student\ExamSecurityController::class, 'logViolation'])->name('student.exam.logViolation');
        Route::get('/exam-security/check-status', [App\Http\Controllers\Student\ExamSecurityController::class, 'checkStatus'])->name('student.exam.checkStatus');

        // Kecermatan (Tes Kecermatan)

        // Endpoint pengambilan CSRF token terbaru untuk sesi ini.
        // GET (tidak butuh CSRF) dan tidak pernah di-cache; dipakai frontend
        // untuk memulihkan diri saat request POST ditolak 419.
        Route::get('/kecermatan/csrf-token', [\App\Http\Controllers\Student\KecermatanStudentController::class, 'csrfToken'])->name('student.kecermatan.csrfToken');

        Route::get('/kecermatan/{exam}/select-type', [\App\Http\Controllers\Student\KecermatanStudentController::class, 'selectType'])->name('student.kecermatan.selectType');
        Route::post('/kecermatan/{exam}/start', [\App\Http\Controllers\Student\KecermatanStudentController::class, 'startExam'])->name('student.kecermatan.start');
        Route::get('/kecermatan/exam/{session}/{column}/{question}', [\App\Http\Controllers\Student\KecermatanStudentController::class, 'showExam'])->name('student.kecermatan.exam');
        Route::post('/kecermatan/submit-answer', [\App\Http\Controllers\Student\KecermatanStudentController::class, 'submitAnswer'])->name('student.kecermatan.submitAnswer');
        Route::post('/kecermatan/submit-answers', [\App\Http\Controllers\Student\KecermatanStudentController::class, 'submitAnswers'])->name('student.kecermatan.submitAnswers');
        Route::post('/kecermatan/{session}/column-timeout', [\App\Http\Controllers\Student\KecermatanStudentController::class, 'columnTimeout'])->name('student.kecermatan.columnTimeout');
        Route::post('/kecermatan/log-violation', [\App\Http\Controllers\Student\KecermatanStudentController::class, 'logViolation'])->name('student.kecermatan.logViolation');
        Route::get('/kecermatan/{session}/check-status', [\App\Http\Controllers\Student\KecermatanStudentController::class, 'checkStatus'])->name('student.kecermatan.checkStatus');
        Route::post('/kecermatan/{session}/force-finish', [\App\Http\Controllers\Student\KecermatanStudentController::class, 'forceFinish'])->name('student.kecermatan.forceFinish');
        Route::get('/kecermatan/result/{session}', [\App\Http\Controllers\Student\KecermatanStudentController::class, 'result'])->name('student.kecermatan.result');
    });

});
