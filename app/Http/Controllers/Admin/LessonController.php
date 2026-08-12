<?php

namespace App\Http\Controllers\Admin;

use App\Models\Lesson;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Storage;

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
            // Ekstensi dicek manual (bukan rule mimes) agar tidak bergantung pada
            // ekstensi fileinfo di server.
            'thumbnail' => 'nullable|file|max:5120',
        ], [
            'thumbnail.max' => 'Ukuran thumbnail maksimal 5 MB.',
        ]);

        if ($request->hasFile('thumbnail')) {
            $thumbExt = strtolower($request->file('thumbnail')->getClientOriginalExtension());
            if (!in_array($thumbExt, ['jpg', 'jpeg', 'png', 'webp'])) {
                return redirect()->back()->withInput()->withErrors([
                    'thumbnail' => 'Format thumbnail harus JPG, PNG, atau WebP.',
                ]);
            }
        }

        // Get max order for category
        $maxOrder = Lesson::where('category', $request->category)->max('order') ?? 0;

        $thumbnail = $request->hasFile('thumbnail')
            ? $request->file('thumbnail')->store('lesson-thumbnails', 'public')
            : null;

        Lesson::create([
            'name' => $request->name,
            'category' => $request->category,
            'thumbnail' => $thumbnail,
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
            'thumbnail' => 'nullable|file|max:5120',
        ], [
            'thumbnail.max' => 'Ukuran thumbnail maksimal 5 MB.',
        ]);

        if ($request->hasFile('thumbnail')) {
            $thumbExt = strtolower($request->file('thumbnail')->getClientOriginalExtension());
            if (!in_array($thumbExt, ['jpg', 'jpeg', 'png', 'webp'])) {
                return redirect()->back()->withInput()->withErrors([
                    'thumbnail' => 'Format thumbnail harus JPG, PNG, atau WebP.',
                ]);
            }
        }

        $thumbnail = $lesson->thumbnail;
        if ($request->hasFile('thumbnail')) {
            if ($thumbnail) {
                Storage::disk('public')->delete($thumbnail);
            }
            $thumbnail = $request->file('thumbnail')->store('lesson-thumbnails', 'public');
        }

        $lesson->update([
            'name' => $request->name,
            'category' => $request->category,
            'thumbnail' => $thumbnail,
        ]);

        return redirect()->route('admin.lessons.index');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $lesson = Lesson::findOrFail($id);
        if ($lesson->thumbnail) {
            Storage::disk('public')->delete($lesson->thumbnail);
        }
        $lesson->delete();

        return redirect()->route('admin.lessons.index');
    }
}
