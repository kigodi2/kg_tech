<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Subject;
use App\Models\SubjectPaperWeight;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SubjectPaperWeightController extends Controller
{
    public function page()
    {
        return view('admin.subject-paper-weights');
    }

    public function subjects(Request $request): JsonResponse
    {
        $q = trim((string) $request->input('q', ''));
        $items = Subject::query()
            ->when($q !== '', fn ($query) => $query->where('name', 'like', "%{$q}%")->orWhere('code', 'like', "%{$q}%"))
            ->orderBy('name')
            ->limit(200)
            ->get(['id', 'code', 'name']);

        return response()->json([
            'success' => true,
            'data' => $items,
        ]);
    }

    public function index(Request $request): JsonResponse
    {
        $subjectId = $request->integer('subject_id');
        $q = trim((string) $request->input('q', ''));

        $rows = SubjectPaperWeight::query()
            ->with('subject:id,code,name')
            ->when($subjectId, fn ($query) => $query->where('subject_id', $subjectId))
            ->when($q !== '', function ($query) use ($q) {
                $query->whereHas('subject', function ($sq) use ($q) {
                    $sq->where('name', 'like', "%{$q}%")
                        ->orWhere('code', 'like', "%{$q}%");
                });
            })
            ->orderBy('subject_id')
            ->orderBy('paper_code')
            ->paginate((int) $request->input('per_page', 50));

        return response()->json([
            'success' => true,
            'data' => $rows,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'subject_id' => ['required', 'integer', 'exists:subjects,id'],
            'paper_code' => ['required', 'in:paper_1,paper_2,paper_3'],
            'weight' => ['required', 'numeric', 'min:0.0001'],
            'max_mark' => ['required', 'numeric', 'min:0.01'],
            'is_required' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $subject = Subject::query()->findOrFail((int) $validated['subject_id']);
        if ($validated['paper_code'] === 'paper_2' && (int) ($subject->written_papers ?? 1) < 2) {
            return response()->json([
                'success' => false,
                'message' => 'paper_2 is only valid for subjects configured with at least two written papers.',
            ], 422);
        }
        if ($validated['paper_code'] === 'paper_3' && !$subject->has_practical) {
            return response()->json([
                'success' => false,
                'message' => 'paper_3 is reserved for practical and can only be added to subjects with practical component.',
            ], 422);
        }

        $row = SubjectPaperWeight::query()->create([
            'subject_id' => (int) $validated['subject_id'],
            'paper_code' => $validated['paper_code'],
            'weight' => (float) $validated['weight'],
            'max_mark' => (float) $validated['max_mark'],
            'is_required' => (bool) ($validated['is_required'] ?? true),
            'is_active' => (bool) ($validated['is_active'] ?? true),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Paper weight added.',
            'data' => $row->load('subject:id,code,name'),
        ], 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $row = SubjectPaperWeight::query()->findOrFail($id);

        $validated = $request->validate([
            'paper_code' => ['required', 'in:paper_1,paper_2,paper_3'],
            'weight' => ['required', 'numeric', 'min:0.0001'],
            'max_mark' => ['required', 'numeric', 'min:0.01'],
            'is_required' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        if ($validated['paper_code'] === 'paper_2' && (int) ($row->subject?->written_papers ?? 1) < 2) {
            return response()->json([
                'success' => false,
                'message' => 'paper_2 is only valid for subjects configured with at least two written papers.',
            ], 422);
        }
        if ($validated['paper_code'] === 'paper_3' && !$row->subject?->has_practical) {
            return response()->json([
                'success' => false,
                'message' => 'paper_3 is reserved for practical and can only be used on subjects with practical component.',
            ], 422);
        }

        $row->update([
            'paper_code' => $validated['paper_code'],
            'weight' => (float) $validated['weight'],
            'max_mark' => (float) $validated['max_mark'],
            'is_required' => (bool) ($validated['is_required'] ?? true),
            'is_active' => (bool) ($validated['is_active'] ?? true),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Paper weight updated.',
            'data' => $row->load('subject:id,code,name'),
        ]);
    }

    public function destroy(int $id): JsonResponse
    {
        $row = SubjectPaperWeight::query()->findOrFail($id);
        $row->delete();

        return response()->json([
            'success' => true,
            'message' => 'Paper weight deleted.',
        ]);
    }
}
