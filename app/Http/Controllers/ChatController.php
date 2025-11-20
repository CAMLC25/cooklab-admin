<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Contracts\NlpAdapter;
use App\Services\RecipeSearch;
use App\Services\GreetingResponder;
use App\Services\AiTextService;
use Illuminate\Support\Facades\Log; // <-- THÊM

class ChatController extends Controller
{
    public function __construct(
        private NlpAdapter $nlp,
        private RecipeSearch $search,
        private GreetingResponder $greeter,
        private AiTextService $aiText
    ) {}

    public function handle(Request $req)
    {
        $text = (string) $req->json('text', $req->input('text', ''));
        abort_if(trim($text) === '', 422, 'text required');

        $preferHowto = (bool) $req->boolean('prefer_howto', false);

        // --- Log: request thô
        Log::info('[CHAT] Incoming', [
            'text' => $text,
            'prefer_howto' => $preferHowto,
            'ip' => $req->ip(),
            'ua' => $req->userAgent(),
        ]);

        $parsed = (array) $this->nlp->extract($text);

        // Khung mặc định cho FE
        $parsed['intent']          = $parsed['intent']          ?? 'recipes';
        $parsed['ingredients']     = $parsed['ingredients']     ?? [];
        $parsed['constraints']     = $parsed['constraints']     ?? ['avoid'=>[], 'diet'=>'none', 'time_max_min'=>null, 'tools'=>[]];
        $parsed['ai_suggestions']  = $parsed['ai_suggestions']  ?? [];
        $parsed['ai_recipe']       = $parsed['ai_recipe']       ?? null;
        $parsed['dish_names']      = $parsed['dish_names']      ?? [];

        // --- Log: tóm tắt NLP
        Log::info('[CHAT] NLP parsed', [
            'intent' => $parsed['intent'],
            'ingredients_count' => count($parsed['ingredients']),
            'dish_names' => $parsed['dish_names'],
            'has_ai_recipe' => !empty($parsed['ai_recipe']),
            'constraints' => $parsed['constraints'],
            'ai_suggestions_count' => count($parsed['ai_suggestions']),
        ]);

        /* ---------------- (A) Chit-chat → trả khung chào, không gọi DB ---------------- */
        if ($parsed['intent'] === 'chit_chat') {
            Log::info('[CHAT] Intent chit_chat → return greeter');
            $parsed['ai_suggestions'] = $this->greeter->build();
            $parsed['catalog_query']  = [
                'need_catalog' => false,
                'filters'      => [
                    'intent'       => 'chit_chat',
                    'ingredients'  => [],
                    'avoid'        => [],
                    'diet'         => 'none',
                    'time_max_min' => null,
                ],
            ];
            $parsed['catalog_hits'] = [];
            $parsed['note']         = null;

            // --- Log: response nhanh
            Log::info('[CHAT] Response (chit_chat)', [
                'ai_suggestions_count' => count($parsed['ai_suggestions']),
            ]);

            return response()->json($parsed);
        }

        /* ---------------- (B) Gom tên món từ nhiều nguồn ---------------- */
        $names = $this->collectDishNames(
            $text,
            (array) $parsed['dish_names'],
            (array) $parsed['ai_suggestions'],
            $parsed['intent'] === 'howto'
        );

        Log::info('[CHAT] Dish name candidates', [
            'names' => $names,
        ]);

        /* ---------------- (C) Có tên món → tra kho, và có thể sinh HOWTO ---------------- */
        if (!empty($names)) {
            $hits = $this->search->searchByDishNames($names, 12);
            $parsed['catalog_hits'] = $hits;

            $shouldGenerate = ($parsed['intent'] === 'howto') || $preferHowto || empty($hits);

            Log::info('[CHAT] Catalog & generation decision', [
                'hits_count' => count($hits),
                'should_generate_howto' => $shouldGenerate,
            ]);

            if ($shouldGenerate && empty($parsed['ai_recipe'])) {
                $dish = $names[0]; // lấy món đại diện đầu tiên
                $parsed['ai_recipe'] = $this->safeGenerateHowto($dish, $parsed['constraints']);
                Log::info('[CHAT] AI howto generated', [
                    'dish' => $dish,
                    'has_steps' => !empty($parsed['ai_recipe']['steps']),
                    'time_min' => $parsed['ai_recipe']['time_min'] ?? null,
                ]);
            }

            // Ghi chú cho FE
            if (empty($hits) && !empty($parsed['ai_recipe'])) {
                $parsed['note'] = 'Kho CookLab chưa có món trùng tên. Mình gửi bạn hướng dẫn tham khảo:';
            } elseif (empty($hits)) {
                $parsed['note'] = 'Kho CookLab hiện chưa có món trùng tên.';
            } else {
                $parsed['note'] = $parsed['note'] ?? null;
            }

            // Chuẩn hoá query cho FE
            $parsed['catalog_query'] = [
                'need_catalog' => true,
                'filters'      => [
                    'intent'       => $parsed['intent'],
                    'ingredients'  => $parsed['ingredients'],
                    'avoid'        => $parsed['constraints']['avoid'] ?? [],
                    'diet'         => $parsed['constraints']['diet'] ?? 'none',
                    'time_max_min' => $parsed['constraints']['time_max_min'] ?? null,
                ],
            ];

            // --- Log: response tóm tắt
            Log::info('[CHAT] Response (names branch)', [
                'return_hits' => count($parsed['catalog_hits']),
                'has_ai_recipe' => !empty($parsed['ai_recipe']),
                'note' => $parsed['note'] ?? null,
            ]);

            return response()->json($parsed);
        }

        /* ---------------- (D) Không có tên món ---------------- */
        if ($parsed['intent'] === 'howto') {
            $parsed['ai_recipe'] = $this->safeGenerateHowto($text, $parsed['constraints']);
            $parsed['catalog_hits'] = [];
            $parsed['catalog_query'] = [
                'need_catalog' => false,
                'filters'      => [
                    'intent'       => 'howto',
                    'ingredients'  => [],
                    'avoid'        => $parsed['constraints']['avoid'] ?? [],
                    'diet'         => $parsed['constraints']['diet'] ?? 'none',
                    'time_max_min' => $parsed['constraints']['time_max_min'] ?? null,
                ],
            ];
            $parsed['note'] = 'Mình chưa tìm thấy món trong kho, gửi bạn hướng dẫn tham khảo:';

            Log::info('[CHAT] Response (howto no-name)', [
                'title' => $parsed['ai_recipe']['title'] ?? null,
                'has_steps' => !empty($parsed['ai_recipe']['steps']),
            ]);

            return response()->json($parsed);
        }

        // Trường hợp thường: không có tên → không tra kho
        $parsed['catalog_hits'] = [];
        $parsed['catalog_query'] = [
            'need_catalog' => false,
            'filters'      => [
                'intent'       => $parsed['intent'],
                'ingredients'  => $parsed['ingredients'],
                'avoid'        => $parsed['constraints']['avoid'] ?? [],
                'diet'         => $parsed['constraints']['diet'] ?? 'none',
                'time_max_min' => $parsed['constraints']['time_max_min'] ?? null,
            ],
        ];
        $parsed['note'] = 'Bạn hãy nêu rõ tên món để mình kiểm tra trong kho CookLab nhé.';

        Log::info('[CHAT] Response (no-name normal)', [
            'intent' => $parsed['intent'],
            'ingredients_count' => count($parsed['ingredients']),
        ]);

        return response()->json($parsed);
    }

    /* ======================= Helpers ======================= */

    /**
     * Gom danh sách tên món từ dish_names, ai_suggestions.title và
     * nếu intent=howto thì cố gắng rút từ câu tự nhiên.
     */
    private function collectDishNames(string $text, array $dishNames, array $aiSuggestions, bool $isHowto): array
    {
        $fromDishNames = array_values(array_filter(array_map(fn($s) => trim((string) $s), $dishNames)));

        $fromSuggest = array_values(array_filter(array_map(function ($x) {
            return is_array($x) && isset($x['title']) ? trim((string) $x['title']) : null;
        }, $aiSuggestions)));

        $names = array_merge($fromDishNames, $fromSuggest);

        if ($isHowto && empty($names)) {
            if (preg_match('/(?:cách làm|hướng dẫn|nấu|pha|xào|chiên)\s+(.+)/iu', $text, $m)) {
                $dish = preg_replace('/\b(được không|thì sao|như thế nào)\b.*$/iu', '', $m[1]);
                $dish = trim((string) $dish, " .,!?:;()[]{}\"'");
                if ($dish !== '') {
                    $names[] = $dish;
                }
            }
        }

        $names = array_map(function ($s) {
            $s = mb_strtolower($s);
            return preg_replace('/\s+/', ' ', trim($s));
        }, $names);

        return array_values(array_unique(array_filter($names)));
    }

    /**
     * Gọi AiTextService để sinh how-to, có chốt chặn an toàn.
     */
    private function safeGenerateHowto(string $dish, array $constraints): array
    {
        try {
            $howto = $this->aiText->generateRecipeHowto($dish, $constraints);
            return [
                'title'       => (string) ($howto['title'] ?? $dish),
                'servings'    => $howto['servings'] ?? null,
                'time_min'    => $howto['time_min'] ?? null,
                'ingredients' => array_values(array_filter($howto['ingredients'] ?? [])),
                'steps'       => array_values(array_filter($howto['steps'] ?? [])),
                'tips'        => array_values(array_filter($howto['tips'] ?? [])),
            ];
        } catch (\Throwable $e) {
            Log::warning('[CHAT] safeGenerateHowto failed, fallback', [
                'dish' => $dish,
                'error' => $e->getMessage(),
            ]);
            return [
                'title'       => $dish,
                'servings'    => null,
                'time_min'    => null,
                'ingredients' => [],
                'steps'       => [],
                'tips'        => [],
            ];
        }
    }
}
