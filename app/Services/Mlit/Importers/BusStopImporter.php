<?php

namespace App\Services\Mlit\Importers;

use App\Services\Mlit\AbstractMlitImporter;

/**
 * P11: バス停留所
 * 属性: P11_001=停留所名, P11_002=バス区分, P11_003_1~19=事業者名, P11_004_1~19=バス系統
 */
class BusStopImporter extends AbstractMlitImporter
{
    public function datasetCode(): string { return 'P11'; }

    public function datasetName(): string { return 'バス停留所'; }

    public function category(): string { return 'transport'; }

    protected function mapFeature(array $properties, array $geometry): ?array
    {
        $name = trim((string) ($properties['P11_001'] ?? ''));

        if ($name === '') {
            return null;
        }

        $operators = [];
        $routes = [];

        for ($i = 1; $i <= 19; $i++) {
            $op = trim((string) ($properties["P11_003_{$i}"] ?? ''));
            $rt = trim((string) ($properties["P11_004_{$i}"] ?? ''));

            if ($op !== '') {
                $operators[] = $op;
            }

            if ($rt !== '') {
                $routes[] = $rt;
            }
        }

        return [
            'sub_category' => 'bus_stop',
            'name'         => $name,
            'attributes'   => [
                'bus_type'  => $properties['P11_002'] ?? null,
                'operators' => array_values(array_unique($operators)),
                'routes'    => array_values(array_unique($routes)),
            ],
        ];
    }
}
