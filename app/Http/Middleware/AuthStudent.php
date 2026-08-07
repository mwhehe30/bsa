<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use App\Events\StudentBlockStatusChanged;

class AuthStudent
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        //check if user is logged in
        $student = auth()->guard('student')->user();

        //if not, redirect to login page
        if (!$student) {
            return redirect('/');
        }

        // Check if student has an active exam (started but not finished)
        $activeGrade = \App\Models\Grade::where('student_id', $student->id)
            ->whereNotNull('start_time')
            ->whereNull('end_time')
            ->first();

        if ($activeGrade) {
            $allowedRoutes = [
                'student.exams.show',
                'student.exams.update_duration',
                'student.exams.answerQuestion',
                'student.exams.endExam',
                'student.exam.logViolation',
                'student.exam.checkStatus',
            ];

            $routeName = $request->route() ? $request->route()->getName() : null;

            if (!in_array($routeName, $allowedRoutes)) {

                // Debouncing: prevent duplicate violations from race conditions
                $violationKey = "violation:{$student->id}:{$request->path()}";

                // If violation was already recorded in last 500ms, skip duplicate
                if (Cache::has($violationKey)) {
                    $examGroup = \App\Models\ExamGroup::where('student_id', $student->id)
                        ->where('exam_id', $activeGrade->exam_id)
                        ->first();

                    if ($examGroup) {
                        return redirect()->route('student.exams.show', [
                            'exam_id' => $activeGrade->exam_id,
                            'grade_id' => $activeGrade->id,
                            'page' => 1,
                        ]);
                    }
                }

                // Set cache for 500ms to prevent duplicate violations
                Cache::put($violationKey, true, now()->addMilliseconds(500));

                // Use transaction with locking to prevent race conditions
                DB::transaction(function () use ($student, $activeGrade, $request) {

                    // Lock row to prevent concurrent modifications
                    $examGroup = \App\Models\ExamGroup::where('student_id', $student->id)
                        ->where('exam_id', $activeGrade->exam_id)
                        ->lockForUpdate()
                        ->first();

                    if (!$examGroup) {
                        return;
                    }

                    // Record violation
                    \App\Models\ExamViolation::create([
                        'exam_group_id' => $examGroup->id,
                        'exam_id' => $activeGrade->exam_id,
                        'violation_type' => 'tab_switch',
                        'violation_time' => \Carbon\Carbon::now(),
                        'notes' => 'Mencoba keluar dari halaman ujian (navigasi ke route lain: ' . $request->path() . ')',
                    ]);

                    // Increment violation count
                    $examGroup->increment('violation_count');

                    // Reload to get fresh data
                    $examGroup->refresh();

                    // Block student immediately for tab switch
                    if (!$examGroup->is_blocked) {
                        $examGroup->is_blocked = true;
                        $examGroup->save();

                        // Broadcast event for real-time update
                        try {
                            broadcast(new StudentBlockStatusChanged(
                                $student->id,
                                true,
                                $examGroup->id,
                                $examGroup->violation_count
                            ));
                        } catch (\Exception $e) {
                            // Log error but don't fail the request
                            \Log::error('Failed to broadcast StudentBlockStatusChanged: ' . $e->getMessage());
                        }
                    }
                });

                // Redirect back to exam page
                $examGroup = \App\Models\ExamGroup::where('student_id', $student->id)
                    ->where('exam_id', $activeGrade->exam_id)
                    ->first();

                if ($examGroup) {
                    return redirect()->route('student.exams.show', [
                        'exam_id' => $activeGrade->exam_id,
                        'grade_id' => $activeGrade->id,
                        'page' => 1,
                    ]);
                }
            }
        }

        //if user is logged in, continue to next middleware
        return $next($request);
    }
}
