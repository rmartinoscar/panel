<?php

namespace App\Filament\Resources\NodeResource\Widgets\Allocated;

use App\Models\Node;
use Filament\Widgets\ChartWidget;
use Illuminate\Database\Eloquent\Model;

class NodeCpuChart extends ChartWidget
{
    protected static ?string $heading = 'CPU';
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

        $node::query()
            ->withSum('servers', 'cpu')->get();

        $total = [$node->servers_sum_cpu ?: 0.01];

        return [
            'datasets' => [
                [
                    'data' => [$total],
                    'backgroundColor' => [
                        'rgb(255, 205, 86)',
                    ],
                ],
            ],
            'labels' => ['Allocated'],
        ];
    }

    protected function getType(): string
    {
        return 'pie';
    }
}
