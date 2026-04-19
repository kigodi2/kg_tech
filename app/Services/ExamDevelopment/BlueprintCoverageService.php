<?php

namespace App\Services\ExamDevelopment;

use App\Models\ExamDevelopment\ExamProjectPaper;

class BlueprintCoverageService
{
    public function analysePaper(ExamProjectPaper $paper): array
    {
        $paper->loadMissing(['formatPaper.blueprints.topics', 'sections.slots.assignedQuestion.metadataRow']);

        $assignedByTopic = [];
        foreach ($paper->sections as $section) {
            foreach ($section->slots as $slot) {
                $topic = $slot->assignedQuestion?->metadataRow?->blueprint_topic_label ?: $slot->assignedQuestion?->topic_name;
                if ($topic) {
                    $assignedByTopic[$topic] = ($assignedByTopic[$topic] ?? 0) + 1;
                }
            }
        }

        $warnings = [];
        $targets = [];
        foreach ($paper->formatPaper->blueprints as $blueprint) {
            foreach ($blueprint->topics as $topic) {
                $actual = $assignedByTopic[$topic->topic_name] ?? 0;
                $targets[] = [
                    'topic' => $topic->topic_name,
                    'target_items' => $topic->items_count,
                    'actual_items' => $actual,
                    'percentage_weight' => $topic->percentage_weight,
                ];

                if ($topic->items_count > 0 && $actual === 0) {
                    $warnings[] = "{$topic->topic_name} has no assigned question coverage.";
                }
            }
        }

        return [
            'targets' => $targets,
            'warnings' => $warnings,
        ];
    }
}
