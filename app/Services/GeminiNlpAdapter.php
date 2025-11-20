<?php

namespace App\Services;

use App\Contracts\NlpAdapter;
use App\Support\NlpJsonValidator;
use App\Support\TextNorm;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GeminiNlpAdapter implements NlpAdapter
{
    public function __construct(private NlpJsonValidator $validator) {}

    public function extract(string $text): array
    {
        $base   = rtrim((string) config('services.gemini.base'), '/');
        $model  = (string) config('services.gemini.model', 'gemini-2.5-pro');
        $apiKey = (string) config('services.gemini.api_key');

        $endpoint = sprintf('%s/models/%s:generateContent', $base, $model);

        // schema kiểu Gemini (có thể gây “câm text” đôi khi)
        $schema = require base_path('resources/nlp/schema_gemini.php');

        $genConfigWithSchema = [
            'temperature'       => 0.0,
            'maxOutputTokens'   => 1024,
            'responseMimeType'  => 'application/json',
            'responseSchema'    => $schema,
        ];

        $genConfigNoSchema = [
            'temperature'       => 0.0,
            'maxOutputTokens'   => 1024,
            'responseMimeType'  => 'application/json',
        ];

        $makePayload = fn(array $genConfig) => [
            'contents' => [[
                'role'  => 'user',
                'parts' => [['text' => $this->prompt($text)]],
            ]],
            'generationConfig' => $genConfig,
        ];

        try {
            // 1) gọi có schema
            $payload = $makePayload($genConfigWithSchema);
            Log::info('Gemini request (with schema)', ['endpoint' => $endpoint, 'model' => $model]);

            $resp = Http::asJson()
                ->acceptJson()->timeout(20)->retry(2, 500)
                ->withHeaders(['x-goog-api-key' => $apiKey])
                ->post($endpoint, $payload);

            $data = $this->decodeGemini($resp->json());

            // 2) nếu vẫn rỗng → thử lại không schema
            if ($data === null) {
                Log::warning('Gemini empty/invalid JSON on first try. Retrying without schema...');
                $payload = $makePayload($genConfigNoSchema);

                $resp = Http::asJson()
                    ->acceptJson()->timeout(20)->retry(2, 500)
                    ->withHeaders(['x-goog-api-key' => $apiKey])
                    ->post($endpoint, $payload);

                $data = $this->decodeGemini($resp->json());
            }

            if (!is_array($data)) {
                Log::warning('Gemini gave up, using fallback');
                return $this->fallback();
            }

            // Validate “mềm tay” + normalize
            try {
                $out = $this->validator->validate($data);
            } catch (\Throwable $ve) {
                Log::warning('Validator failed; soft repairing', ['e' => $ve->getMessage()]);
                $out = $this->softRepair($data);
            }

            // Chuẩn hoá
            $out['ingredients'] = TextNorm::normalizeArray($out['ingredients'] ?? []);
            $out['dish_names']  = TextNorm::normalizeArray($out['dish_names'] ?? []);

            $out['constraints'] = $out['constraints'] ?? [];
            $out['constraints']['avoid'] = TextNorm::normalizeArray(Arr::get($out, 'constraints.avoid', []));
            $out['constraints']['tools'] = TextNorm::normalizeArray(Arr::get($out, 'constraints.tools', []));

            $diet = Arr::get($out, 'constraints.diet', 'none');
            $allowed = ['none','vegan','vegetarian','halal','kosher','gluten_free'];
            $out['constraints']['diet'] = in_array($diet, $allowed, true) ? $diet : 'none';

            $tmm = Arr::get($out, 'constraints.time_max_min');
            $out['constraints']['time_max_min'] = is_numeric($tmm) ? (int) $tmm : null;

            // đảm bảo các field tối thiểu
            $out += [
                'ai_suggestions' => $out['ai_suggestions'] ?? [],
                'catalog_query'  => $out['catalog_query']  ?? [
                    'need_catalog' => true,
                    'filters'      => ['intent' => $out['intent'] ?? 'recipes'],
                ],
                'catalog_hits'   => $out['catalog_hits']   ?? [],
            ];

            // BẢO HIỂM: howto mà thiếu ai_recipe → dựng khung rỗng để FE vẫn hiển thị
            if (($out['intent'] ?? '') === 'howto' && empty($out['ai_recipe'])) {
                $title = $out['dish_names'][0] ?? '';
                $out['ai_recipe'] = [
                    'title'       => (string) $title,
                    'servings'    => null,
                    'time_min'    => null,
                    'ingredients' => [],
                    'steps'       => [],
                    'tips'        => [],
                ];
            }

            return $out;

        } catch (\Throwable $e) {
            Log::error('Gemini error', ['exception' => $e->getMessage()]);
            Log::debug('Gemini exception trace', ['trace' => $e->getTraceAsString()]);
            return $this->fallback();
        }
    }

    /**
     * Trích tất cả parts.text, lấy JSON đầu tiên hợp lệ.
     */
    private function decodeGemini(?array $jsonAll): ?array
    {
        if (!$jsonAll) return null;

        // gom toàn bộ text từ mọi candidate/part
        $texts = [];
        foreach ((array) ($jsonAll['candidates'] ?? []) as $cand) {
            foreach ((array) (data_get($cand, 'content.parts') ?? []) as $p) {
                $t = (string) ($p['text'] ?? '');
                if ($t !== '') $texts[] = $t;
            }
        }

        foreach ($texts as $t) {
            $clean = $this->stripCodeFence(trim($t));
            $json  = $this->extractJson($clean);
            if (is_array($json)) return $json;
        }

        return null;
    }

    private function stripCodeFence(string $s): string
    {
        // bỏ ```json ... ``` hoặc ``` ...
        if (preg_match('/^```[a-zA-Z]*\s*(.+?)\s*```$/s', $s, $m)) {
            return trim($m[1]);
        }
        return $s;
    }

    /**
     * Cố gắng decode JSON; nếu thất bại thì tìm khối { ... } lớn nhất.
     */
    private function extractJson(string $s): ?array
    {
        $try = json_decode($s, true);
        if (is_array($try)) return $try;

        // tìm khối JSON dạng object
        if (preg_match('/\{.*\}/s', $s, $m)) {
            $try = json_decode($m[0], true);
            if (is_array($try)) return $try;
        }
        // tìm khối JSON dạng array
        if (preg_match('/\[(.|\s)*\]/s', $s, $m)) {
            $try = json_decode($m[0], true);
            if (is_array($try)) return $try;
        }
        return null;
    }

    private function prompt(string $userText): string
    {
        return <<<P
Bạn là Trợ lý Ẩm thực CookLab. Hãy PHÂN TÍCH yêu cầu và trả về DUY NHẤT JSON theo schema đã khai báo (không thêm chữ ngoài JSON).

NHIỆM VỤ:
1) Xác định intent:
   - "howto": có các cụm như "cách nấu", "cách làm", "hướng dẫn", "recipe", "how to", "làm món".
   - "drinks": đồ uống/nước giải khát (nước ép, sinh tố, smoothie, soda, trà sữa, cà phê...).
   - "tea_dessert": chè, sâm bổ lượng, pudding trà sữa... (đồ ngọt dạng chè/tráng miệng).
   - "recipes": còn lại liên quan tới món ăn.
   - "chit_chat": chào hỏi, giao tiếp xã giao.
   - "unsupported": nội dung không liên quan ẩm thực.
2) Trích xuất:
   - "ingredients": liệt kê nguyên liệu xuất hiện (dạng ngắn gọn: "bò", "hành tây", "sữa"...).
   - "constraints": {avoid[], diet, time_max_min|null, tools[]}
   - "dish_names": LUÔN đưa tên món nếu có (ví dụ "bò xào hành tây").
3) Gợi ý:
   - "ai_suggestions": 3–5 mục (hoặc rỗng) gồm title/summary/tags/time_min.
4) Tìm trong kho:
   - "catalog_query.need_catalog": true khi có thể tìm trong kho.
   - "catalog_query.filters": luôn điền intent và các bộ lọc đã hiểu (ingredients/avoid/diet/time_max_min).
5) Riêng intent = "howto":
   - Điền "ai_recipe" tối thiểu gồm: title (lấy từ dish_names nếu có), steps[] (ngắn gọn, tuần tự).
   - Vẫn đặt need_catalog=true để kiểm tra xem kho có món trùng tên không.
   - **Kể cả món KHÔNG có trong kho, vẫn phải điền ai_recipe để người dùng có thể làm theo.**
6) Nếu không có gì để lưu ý, "note" = null.

TUÂN THỦ:
- Chỉ xuất JSON đúng schema đã khai báo từ hệ thống.
- Không giải thích bên ngoài JSON.
- Nếu không chắc một trường, để rỗng hoặc null (đặc biệt time_max_min=null).

VÍ DỤ:
User: "Cách nấu Bò xào hành tây, tránh sữa"
Kết quả (mang tính minh hoạ):
{
  "intent": "howto",
  "ingredients": ["bò","hành tây"],
  "constraints": {"avoid":["sữa"], "diet":"none", "time_max_min": null, "tools":[]},
  "ai_suggestions": [],
  "catalog_query": {
    "need_catalog": true,
    "filters": {"intent":"recipes","ingredients":["bò","hành tây"],"avoid":["sữa"]}
  },
  "catalog_hits": [],
  "dish_names": ["bò xào hành tây"],
  "ai_recipe": {
    "title": "Bò xào hành tây",
    "servings": null,
    "time_min": 15,
    "ingredients": ["thịt bò","hành tây","tỏi","dầu ăn","nước mắm","tiêu"],
    "steps": [
      "Ướp bò với chút nước mắm, tiêu 10 phút.",
      "Phi tỏi, xào nhanh bò lửa lớn đến tái.",
      "Cho hành tây, đảo 1–2 phút cho giòn.",
      "Nêm lại và tắt bếp."
    ],
    "tips": ["Xào lửa lớn để bò không bị dai."]
  },
  "note": null
}

Văn bản người dùng:
"""{$userText}"""
P;
    }

    private function softRepair(array $data): array
    {
        $out = [
            'intent' => $data['intent'] ?? 'recipes',
            'ingredients' => [],
            'constraints' => [
                'avoid' => [],
                'diet' => 'none',
                'time_max_min' => null,
                'tools' => [],
            ],
            'ai_suggestions' => [],
            'catalog_query' => ['need_catalog' => true, 'filters' => ['intent' => 'recipes']],
            'catalog_hits' => [],
        ];

        foreach (['ingredients'] as $k) {
            if (!empty($data[$k]) && is_array($data[$k])) {
                $out[$k] = array_values(array_filter(array_map('strval', $data[$k])));
            }
        }

        if (isset($data['constraints']) && is_array($data['constraints'])) {
            $c = $data['constraints'];
            foreach (['avoid', 'tools'] as $k) {
                if (!empty($c[$k]) && is_array($c[$k])) {
                    $out['constraints'][$k] = array_values(array_filter(array_map('strval', $c[$k])));
                }
            }
            if (!empty($c['diet']) && is_string($c['diet'])) {
                $allowed = ['none','vegan','vegetarian','halal','kosher','gluten_free'];
                $out['constraints']['diet'] = in_array($c['diet'], $allowed, true) ? $c['diet'] : 'none';
            }
            if (array_key_exists('time_max_min', $c) && ($c['time_max_min'] === null || is_numeric($c['time_max_min']))) {
                $out['constraints']['time_max_min'] = is_numeric($c['time_max_min']) ? (int) $c['time_max_min'] : null;
            }
        }

        if (($data['intent'] ?? '') === 'howto' && !empty($data['ai_recipe']) && is_array($data['ai_recipe'])) {
            $out['ai_recipe'] = [
                'title'       => (string) ($data['ai_recipe']['title'] ?? ''),
                'servings'    => isset($data['ai_recipe']['servings']) && is_numeric($data['ai_recipe']['servings'])
                    ? (int) $data['ai_recipe']['servings'] : null,
                'time_min'    => isset($data['ai_recipe']['time_min']) && is_numeric($data['ai_recipe']['time_min'])
                    ? (int) $data['ai_recipe']['time_min'] : null,
                'ingredients' => array_values(array_filter(array_map('strval', $data['ai_recipe']['ingredients'] ?? []))),
                'steps'       => array_values(array_filter(array_map('strval', $data['ai_recipe']['steps'] ?? []))),
                'tips'        => array_values(array_filter(array_map('strval', $data['ai_recipe']['tips'] ?? []))),
            ];
        }

        return $out;
    }

    private function fallback(): array
    {
        return [
            'intent' => 'recipes',
            'ingredients' => [],
            'constraints' => [
                'avoid' => [],
                'diet' => 'none',
                'time_max_min' => null,
                'tools' => [],
            ],
            'ai_suggestions' => [],
            'catalog_query' => ['need_catalog' => true, 'filters' => ['intent' => 'recipes']],
            'catalog_hits' => [],
        ];
    }
}
