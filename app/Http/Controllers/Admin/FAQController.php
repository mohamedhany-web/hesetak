<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\FAQ;
use Illuminate\Http\Request;

class FAQController extends Controller
{
    public function index(Request $request)
    {
        $query = FAQ::query();

        if ($request->filled('search')) {
            $query->where(function($q) use ($request) {
                $q->where('question', 'like', '%' . $request->search . '%')
                  ->orWhere('answer', 'like', '%' . $request->search . '%');
            });
        }

        if ($request->filled('category')) {
            $query->where('category', $request->category);
        }

        $faqs = $query->orderBy('order')->orderBy('created_at', 'desc')->paginate(20)->withQueryString();
        $categories = FAQ::distinct()->pluck('category')->filter()->values();

        $stats = [
            'total' => FAQ::count(),
            'active' => FAQ::where('is_active', true)->count(),
            'inactive' => FAQ::where('is_active', false)->count(),
            'categories' => $categories->count(),
        ];

        return view('admin.faq.index', compact('faqs', 'categories', 'stats'));
    }

    public function create()
    {
        $categories = FAQ::distinct()->pluck('category')->filter()->values();
        return view('admin.faq.create', compact('categories'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'question' => ['required', 'string', 'max:500', new \App\Rules\NoDemoPlaceholder],
            'answer' => ['required', 'string', 'max:2000', new \App\Rules\NoDemoPlaceholder],
            'category' => 'nullable|string|max:100',
            'order' => 'nullable|integer|min:0',
            'is_active' => 'boolean',
        ]);

        $validated['is_active'] = $request->boolean('is_active');
        $validated['order'] = $validated['order'] ?? 0;

        FAQ::create($validated);

        return redirect()->route('admin.faq.index')
            ->with('success', 'تم إنشاء السؤال بنجاح');
    }

    public function show(FAQ $faq)
    {
        return view('admin.faq.show', compact('faq'));
    }

    public function edit(FAQ $faq)
    {
        $categories = FAQ::distinct()->pluck('category')->filter()->values();
        return view('admin.faq.edit', compact('faq', 'categories'));
    }

    public function update(Request $request, FAQ $faq)
    {
        $validated = $request->validate([
            'question' => ['required', 'string', 'max:500', new \App\Rules\NoDemoPlaceholder],
            'answer' => ['required', 'string', 'max:2000', new \App\Rules\NoDemoPlaceholder],
            'category' => 'nullable|string|max:100',
            'order' => 'nullable|integer|min:0',
            'is_active' => 'boolean',
        ]);

        $validated['is_active'] = $request->boolean('is_active');
        $validated['order'] = $validated['order'] ?? 0;

        $faq->update($validated);

        return redirect()->route('admin.faq.index')
            ->with('success', 'تم تحديث السؤال بنجاح');
    }

    public function destroy(FAQ $faq)
    {
        $faq->delete();

        return redirect()->route('admin.faq.index')
            ->with('success', 'تم حذف السؤال بنجاح');
    }
}



