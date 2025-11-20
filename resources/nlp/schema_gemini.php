<?php
// resources/nlp/schema_gemini.php
// Gemini response schema (NOT JSON-Schema)

return [
  'type' => 'OBJECT',
  'properties' => [
    'intent' => [
      'type' => 'STRING',
      'enum' => ['recipes','drinks','tea_dessert','howto','chit_chat','unsupported'],
    ],

    'ingredients' => [
      'type' => 'ARRAY',
      'items' => ['type' => 'STRING'],
    ],

    'constraints' => [
      'type' => 'OBJECT',
      'properties' => [
        'avoid' => ['type' => 'ARRAY', 'items' => ['type' => 'STRING']],
        'diet'  => [
          'type' => 'STRING',
          'enum' => ['none','vegan','vegetarian','halal','kosher','gluten_free'],
        ],
        'time_max_min' => ['type' => 'INTEGER', 'nullable' => true],
        'tools' => ['type' => 'ARRAY', 'items' => ['type' => 'STRING']],
      ],
      'required' => ['avoid','diet'],
    ],

    'ai_suggestions' => [
      'type' => 'ARRAY',
      'items' => [
        'type' => 'OBJECT',
        'properties' => [
          'title'    => ['type' => 'STRING'],
          'summary'  => ['type' => 'STRING', 'nullable' => true],
          'tags'     => ['type' => 'ARRAY', 'items' => ['type' => 'STRING']],
          'time_min' => ['type' => 'INTEGER', 'nullable' => true],
        ],
        'required' => ['title'],
      ],
    ],

    'catalog_query' => [
      'type' => 'OBJECT',
      'properties' => [
        'need_catalog' => ['type' => 'BOOLEAN'],
        'filters' => [
          'type' => 'OBJECT',
          'properties' => [
            'ingredients'  => ['type' => 'ARRAY', 'items' => ['type' => 'STRING']],
            'avoid'        => ['type' => 'ARRAY', 'items' => ['type' => 'STRING']],
            'diet'         => [
              'type' => 'STRING',
              'enum' => ['none','vegan','vegetarian','halal','kosher','gluten_free'],
            ],
            'intent'       => [
              'type' => 'STRING',
              'enum' => ['recipes','drinks','tea_dessert','howto','chit_chat','unsupported'],
            ],
            'time_max_min' => ['type' => 'INTEGER', 'nullable' => true],
          ],
          'required' => ['intent'],
        ],
      ],
      'required' => ['need_catalog','filters'],
    ],

    'catalog_hits' => [
      'type' => 'ARRAY',
      'items' => [
        'type' => 'OBJECT',
        'properties' => [
          'id'          => ['type' => 'INTEGER'], // đổi từ STRING -> INTEGER (nếu DB dùng int)
          'title'       => ['type' => 'STRING'],
          'match_score' => ['type' => 'NUMBER', 'nullable' => true],
        ],
        'required' => ['id','title'],
      ],
    ],

    // ✅ BỔ SUNG để model có thể xuất tên món cho flow tìm theo tên
    'dish_names' => [
      'type' => 'ARRAY',
      'items' => ['type' => 'STRING'],
    ],

    'ai_recipe' => [
      'type' => 'OBJECT',
      'nullable' => true,
      'properties' => [
        'title'      => ['type' => 'STRING'],
        'servings'   => ['type' => 'INTEGER', 'nullable' => true],
        'time_min'   => ['type' => 'INTEGER', 'nullable' => true],
        'ingredients'=> ['type' => 'ARRAY', 'items' => ['type' => 'STRING']],
        'steps'      => ['type' => 'ARRAY', 'items' => ['type' => 'STRING']],
        'tips'       => ['type' => 'ARRAY', 'items' => ['type' => 'STRING']],
      ],
    ],

    'note' => ['type' => 'STRING', 'nullable' => true],
  ],
  'required' => [
    'intent','ingredients','constraints',
    'ai_suggestions','catalog_query','catalog_hits'
  ],
];
