<?php

namespace App\Support;

use Opis\JsonSchema\Validator;

class NlpJsonValidator
{
    /** @var array<string,mixed> */
    private array $schema;

    public function __construct()
    {
        $path = base_path('resources/nlp/schema.json');
        $json = file_get_contents($path);
        if ($json === false) {
            // fallback tối thiểu nếu thiếu file
            $this->schema = [
                'type' => 'object',
                'properties' => [
                    'ingredients' => ['type' => 'array', 'items' => ['type' => 'string']],
                    'dish_names'  => ['type' => 'array', 'items' => ['type' => 'string']],
                    'constraints' => [
                        'type' => 'object',
                        'properties' => [
                            'avoid'        => ['type' => 'array', 'items' => ['type' => 'string']],
                            'diet'         => ['type' => 'string'],
                            'time_max_min' => ['type' => ['integer','null']],
                            'tools'        => ['type' => 'array', 'items' => ['type' => 'string']],
                        ],
                        'additionalProperties' => false,
                    ],
                ],
                'required' => ['ingredients','dish_names','constraints'],
                'additionalProperties' => false,
            ];
        } else {
            /** @var array<string,mixed> $schema */
            $schema = json_decode($json, true) ?: [];
            $this->schema = $schema;
        }
    }

    /** @param array<string,mixed> $data */
    public function validate(array $data): array
    {
        $validator = new Validator();                 // v2 API
        $result = $validator->validate($data, json_decode(json_encode($this->schema)));

        if ($result->isValid()) {
            return $data;
        }

        // mềm hoá dữ liệu khi chưa hợp lệ
        return $this->softRepair($data);
    }

    /** @param array<string,mixed> $d */
    private function softRepair(array $d): array
    {
        $d['ingredients'] = array_values(array_filter(($d['ingredients'] ?? []), 'is_string'));
        $d['dish_names']  = array_values(array_filter(($d['dish_names']  ?? []), 'is_string'));

        $c = $d['constraints'] ?? [];
        $c['avoid'] = array_values(array_filter(($c['avoid'] ?? []), 'is_string'));

        $diet = $c['diet'] ?? 'none';
        $allowed = ['none','vegan','vegetarian','halal','kosher','gluten_free'];
        $c['diet'] = in_array($diet, $allowed, true) ? $diet : 'none';

        $c['time_max_min'] = (isset($c['time_max_min']) && is_numeric($c['time_max_min']))
            ? (int) $c['time_max_min'] : null;

        $c['tools'] = array_values(array_filter(($c['tools'] ?? []), 'is_string'));

        $d['constraints'] = $c;
        return $d;
    }
}
