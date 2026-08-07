<?php

namespace App\Http\Controllers\Admin;

use App\Models\Student;
use App\Models\ExamGroup;
use Illuminate\Http\Request;
use App\Imports\StudentsImport;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Hash;
use Maatwebsite\Excel\Facades\Excel;
use App\Events\StudentBlockStatusChanged;

class StudentController extends Controller
{
    public function index()
    {
        //get per_page from request, default to 5, max 100
        $perPage = request()->get('per_page', 5);
        $perPage = min(max((int) $perPage, 5), 100);

        $students = Student::when(request()->q, function($students) {
            $students = $students->where('name', 'like', '%'. request()->q . '%')
                                 ->orWhere('email', 'like', '%'. request()->q . '%');
        })->latest()->paginate($perPage);

        $students->appends(['q' => request()->q, 'per_page' => $perPage]);

        return inertia('Admin/Students/Index', [
            'students' => $students,
        ]);
    }

    public function create()
    {
        return inertia('Admin/Students/Create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name'          => 'required|string|max:255',
            'email'         => 'required|email|unique:students',
            'gender'        => 'required|string',
            'password'      => 'required|confirmed|min:6',
        ]);

        Student::create([
            'name'          => $request->name,
            'email'         => $request->email,
            'gender'        => $request->gender,
            'password'      => Hash::make($request->password),
            'must_change_password' => true,
        ]);

        return redirect()->route('admin.students.index');
    }

    public function edit($id)
    {
        $student = Student::findOrFail($id);

        return inertia('Admin/Students/Edit', [
            'student' => $student,
        ]);
    }

    public function update(Request $request, Student $student)
    {
        $request->validate([
            'name'          => 'required|string|max:255',
            'email'         => 'required|email|unique:students,email,'.$student->id,
            'gender'        => 'required|string',
            'is_active'     => 'sometimes|boolean',
            'password'      => 'nullable|confirmed|min:6',
        ]);

        $data = [
            'name'          => $request->name,
            'email'         => $request->email,
            'gender'        => $request->gender,
        ];

        if ($request->has('is_active')) {
            $data['is_active'] = $request->is_active;
        }

        if ($request->filled('password')) {
            $data['password'] = Hash::make($request->password);
            $data['must_change_password'] = true;
        }

        $student->update($data);

        return redirect()->route('admin.students.index');
    }

    public function destroy($id)
    {
        $student = Student::findOrFail($id);
        $student->delete();

        return redirect()->route('admin.students.index');
    }

    public function import()
    {
        return inertia('Admin/Students/Import');
    }

    public function storeImport(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:csv,xls,xlsx',
        ]);

        Excel::import(new StudentsImport(), $request->file('file'));

        return redirect()->route('admin.students.index');
    }

    public function toggleActive(Request $request, Student $student)
    {
        $request->validate([
            'is_active' => 'required|boolean',
        ]);

        $student->update([
            'is_active' => $request->is_active,
        ]);

        if ($request->is_active == true) {
            $examGroups = ExamGroup::where('student_id', $student->id)
                ->where('is_blocked', true)
                ->get();

            foreach ($examGroups as $examGroup) {
                $examGroup->update([
                    'is_blocked' => false,
                ]);

                // Broadcast event for real-time update
                try {
                    broadcast(new StudentBlockStatusChanged(
                        $student->id,
                        false,
                        $examGroup->id,
                        $examGroup->violation_count
                    ));
                } catch (\Exception $e) {
                    \Log::error('Failed to broadcast StudentBlockStatusChanged: ' . $e->getMessage());
                }
            }

            // Also unblock kecermatan_sessions and reset column_start_time for student
            \App\Models\KecermatanSession::where('student_id', $student->id)
                ->where('is_blocked', true)
                ->update([
                    'is_blocked' => false,
                    'column_start_time' => now(),
                ]);
        }

        return redirect()->back();
    }

    public function isolated()
    {
        //get per_page from request, default to 10, max 100
        $perPage = request()->get('per_page', 10);
        $perPage = min(max((int) $perPage, 5), 100);

        $blockedStudentIds = ExamGroup::where('is_blocked', true)
            ->pluck('student_id')
            ->unique();

        $students = Student::whereIn('id', $blockedStudentIds)
            ->when(request()->q, function($query) {
                $query->where('name', 'like', '%'. request()->q . '%')
                      ->orWhere('email', 'like', '%'. request()->q . '%');
            })
            ->latest()
            ->paginate($perPage);

        $students->appends(['q' => request()->q, 'per_page' => $perPage]);

        return inertia('Admin/Students/Isolated', [
            'students' => $students,
        ]);
    }

    public function bulkActivate(Request $request)
    {
        $request->validate([
            'student_ids' => 'required|array',
            'student_ids.*' => 'exists:students,id',
        ]);

        Student::whereIn('id', $request->student_ids)->update([
            'is_active' => true,
        ]);

        $examGroups = ExamGroup::whereIn('student_id', $request->student_ids)
            ->where('is_blocked', true)
            ->get();

        foreach ($examGroups as $examGroup) {
            $examGroup->update([
                'is_blocked' => false,
            ]);

            // Broadcast event for real-time update
            try {
                broadcast(new StudentBlockStatusChanged(
                    $examGroup->student_id,
                    false,
                    $examGroup->id,
                    $examGroup->violation_count
                ));
            } catch (\Exception $e) {
                \Log::error('Failed to broadcast StudentBlockStatusChanged: ' . $e->getMessage());
            }
        }

        // Also unblock kecermatan_sessions and reset column_start_time
        \App\Models\KecermatanSession::whereIn('student_id', $request->student_ids)
            ->where('is_blocked', true)
            ->update([
                'is_blocked' => false,
                'column_start_time' => now(),
            ]);

        $count = count($request->student_ids);
        return redirect()->back()->with('success', "Berhasil mengaktifkan {$count} siswa dan membuka isolir.");
    }

    public function bulkDelete(Request $request)
    {
        $request->validate([
            'student_ids' => 'required|array',
            'student_ids.*' => 'exists:students,id',
        ]);

        Student::whereIn('id', $request->student_ids)->delete();

        $count = count($request->student_ids);
        return redirect()->back()->with('success', "Berhasil menghapus {$count} siswa.");
    }

    public function bulkToggleActive(Request $request)
    {
        $request->validate([
            'student_ids' => 'required|array',
            'student_ids.*' => 'exists:students,id',
            'action' => 'required|in:aktifkan,nonaktifkan,toggle',
        ]);

        $students = Student::whereIn('id', $request->student_ids);

        if ($request->action === 'toggle') {
            $students->get()->each(function($student) {
                $student->update(['is_active' => !$student->is_active]);
            });
        } elseif ($request->action === 'aktifkan') {
            $students->update(['is_active' => true]);
        } elseif ($request->action === 'nonaktifkan') {
            $students->update(['is_active' => false]);
        }

        $count = count($request->student_ids);
        return redirect()->back()->with('success', "Berhasil mengubah status {$count} siswa.");
    }
}
