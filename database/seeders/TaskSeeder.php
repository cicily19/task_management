<?php

namespace Database\Seeders;

use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use App\Models\Task;
use Illuminate\Database\Seeder;

class TaskSeeder extends Seeder
{
    public function run(): void
    {
        $today = now()->toDateString();
        $tomorrow = now()->addDay()->toDateString();

        $rows = [
            [
                'title' => 'Draft API documentation',
                'due_date' => $today,
                'priority' => TaskPriority::High,
                'status' => TaskStatus::Pending,
            ],
            [
                'title' => 'Review pull requests',
                'due_date' => $today,
                'priority' => TaskPriority::Medium,
                'status' => TaskStatus::InProgress,
            ],
            [
                'title' => 'Update README deployment section',
                'due_date' => $tomorrow,
                'priority' => TaskPriority::Low,
                'status' => TaskStatus::Done,
            ],
        ];

        foreach ($rows as $row) {
            Task::query()->updateOrCreate(
                [
                    'title' => $row['title'],
                    'due_date' => $row['due_date'],
                ],
                [
                    'priority' => $row['priority'],
                    'status' => $row['status'],
                ]
            );
        }
    }
}
