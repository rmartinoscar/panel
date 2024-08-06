<?php

namespace App\Filament\Resources\NodeResource\Widgets\Allocated;

use App\Models\Node;
use Filament\Widgets\ChartWidget;
use Illuminate\Database\Eloquent\Model;

class NodeStorageChart extends ChartWidget
{
    protected static ?string $heading = 'Storage';
    protected static ?string $pollingInterval = '60s';
    protected static ?string $maxHeight = '300px';

    public ?Model $record = null;

    protected static ?array $options = [
        'scales' => [
            'x' => [
                'grid' => [
                    'display' => false,
                ],
                'ticks' => [
                    'display' => false,
                ],
            ],
            'y' => [
                'grid' => [
                    'display' => false,
                ],
                'ticks' => [
                    'display' => false,
                ],
            ],
        ],
    ];

    protected function getData(): array
    {
        /** @var Node $node */
        $node = $this->record;

        $totalGlobal = ($node->statistics()['disk_total'] ?? 0) / 1024 / 1024 / 1024;
        $usedGlobal = ($node->statistics()['disk_used'] ?? 0) / 1024 / 1024 / 1024;
        $unusedGlobal = $totalGlobal - $usedGlobal;

        $node->getUsageStats();

        $allocated = [$node->servers_sum_memory ?: 0.01];
        $totalAllocated = [$node->memory ?: 0.01];

        return [
            'datasets' => [
                [
                    'data' => [$usedGlobal, $unusedGlobal, $totalAllocated],
                    'backgroundColor' => [
                        'rgb(255, 99, 132)',
                        'rgb(54, 162, 235)',
                        'rgb(255, 205, 86)',
                    ],
                ],
            ],
            'labels' => ['Used', 'Unused', 'Allocated'],
        ];
    }

    protected function getType(): string
    {
        return 'pie';
    }
}
