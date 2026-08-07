<?php

namespace App\Http\Controllers\Admin;

use App\Models\Lesson;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class LessonController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $lessons = Lesson::when(request()->q, function($lessons) {
            $lessons = $lessons->where('name', 'like', '%'. request()->q . '%');
        })
        ->orderBy('category')
        ->orderBy('order')
        ->get();

        // Group by category
        $grouped = [
            'psikologi' => $lessons->where('category', 'psikologi')->values(),
            'akademik' => $lessons->where('category', 'akademik')->values(),
        ];

        return inertia('Admin/Lessons/Index', [
            'lessons' => $grouped,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return inertia('Admin/Lessons/Create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|unique:lessons',
            'category' => 'required|in:psikologi,akademik',
        ]);

        // Get max order for category
        $maxOrder = Lesson::where('category', $request->category)->max('order') ?? 0;

        Lesson::create([
            'name' => $request->name,
            'category' => $request->category,
            'order' => $maxOrder + 1,
        ]);

        return redirect()->route('admin.lessons.index');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        $lesson = Lesson::findOrFail($id);

        return inertia('Admin/Lessons/Edit', [
            'lesson' => $lesson,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Lesson $lesson)
    {
        $request->validate([
            'name' => 'required|string|unique:lessons,name,'.$lesson->id,
            'category' => 'required|in:psikologi,akademik',
        ]);

        $lesson->update([
            'name' => $request->name,
            'category' => $request->category,
        ]);

        return redirect()->route('admin.lessons.index');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $lesson = Lesson::findOrFail($id);
        $lesson->delete();

        return redirect()->route('admin.lessons.index');
    }
}
