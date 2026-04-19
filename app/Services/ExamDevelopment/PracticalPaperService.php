<?php

namespace App\Services\ExamDevelopment;

use App\Models\ExamDevelopment\ExamProjectPaper;
use Illuminate\Support\Facades\DB;

class PracticalPaperService
{
    public function saveSetup(ExamProjectPaper $paper, array $payload): ExamProjectPaper
    {
        return DB::transaction(function () use ($paper, $payload) {
            $paper->apparatusLists()->delete();
            $paper->confidentialInstructions()->delete();

            foreach ($payload['apparatus_lists'] ?? [] as $listData) {
                $list = $paper->apparatusLists()->create([
                    'title' => $listData['title'],
                    'issued_before_days' => $listData['issued_before_days'] ?? null,
                    'notes' => $listData['notes'] ?? null,
                ]);

                foreach ($listData['items'] ?? [] as $index => $itemData) {
                    $list->items()->create([
                        'item_name' => $itemData['item_name'],
                        'quantity' => $itemData['quantity'] ?? null,
                        'unit' => $itemData['unit'] ?? null,
                        'remarks' => $itemData['remarks'] ?? null,
                        'display_order' => $itemData['display_order'] ?? ($index + 1),
                    ]);
                }
            }

            foreach ($payload['confidential_instructions'] ?? [] as $instructionData) {
                $paper->confidentialInstructions()->create([
                    'release_hours_before' => $instructionData['release_hours_before'] ?? null,
                    'instruction_text' => $instructionData['instruction_text'],
                    'is_confidential' => $instructionData['is_confidential'] ?? true,
                ]);
            }

            return $paper->fresh(['apparatusLists.items', 'confidentialInstructions']);
        });
    }
}
