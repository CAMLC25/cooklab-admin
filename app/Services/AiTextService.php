<?php

namespace App\Services;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Facades\Config;

class AiTextService
{
    private Client $http;
    private string $base;

    public function __construct()
    {
        $this->http = new Client(['timeout' => 10, 'http_errors' => false]);
        $this->base = rtrim((string) config('services.ai.base_url'), '/');
    }

    /**
     * Gửi danh sách recognized + results cơ bản sang AI (FastAPI)
     * trả về array: [recognized_display, results[] (có reason)]
     */
    public function beautifyDisplay(array $recognizedNorms, array $basicResults): array
    {
        try {
            $resp = $this->http->post($this->base . '/beautify', [
                'json' => [
                    'recognized' => $recognizedNorms,
                    'results'    => $basicResults,
                ],
            ]);

            $data = json_decode((string) $resp->getBody(), true) ?: [];
            return [
                'recognized_display' => $data['recognized_display'] ?? $this->fallbackDisplay($recognizedNorms),
                'results'            => $data['results'] ?? $basicResults,
            ];
        } catch (\Throwable $e) {
            // lỗi mạng/API → fallback hiển thị đẹp cơ bản
            return [
                'recognized_display' => $this->fallbackDisplay($recognizedNorms),
                'results'            => $basicResults,
            ];
        }
    }

    private function fallbackDisplay(array $recognized): string
    {
        return implode(', ', array_map(fn ($x) => mb_convert_case($x, MB_CASE_TITLE, 'UTF-8'), $recognized));
    }

    /**
     * Sinh hướng dẫn nấu món ăn.
     * - Ưu tiên gọi FastAPI: POST {base}/howto
     * - Nếu API lỗi/không hợp lệ → trả fallback có cấu trúc để FE luôn hiển thị được.
     *
     * Body gửi sang FastAPI (gợi ý):
     *  {
     *    "dish": "cơm chiên gà",
     *    "constraints": {"avoid":[], "diet":"none", "time_max_min": null, "tools":[]}
     *  }
     *
     * TODO: nếu backend của bạn dùng route khác (vd. /recipe/howto), đổi ở $endpoint.
     */
    public function generateRecipeHowto(string $dish, array $constraints = []): array
    {
        $endpoint = $this->base . '/howto'; // TODO: đổi nếu API khác đường dẫn

        // Chuẩn hoá constraints tối thiểu
        $constraints = array_merge(
            ['avoid' => [], 'diet' => 'none', 'time_max_min' => null, 'tools' => []],
            is_array($constraints) ? $constraints : []
        );

        // Gọi FastAPI
        try {
            if ($this->base !== '') {
                $resp = $this->http->post($endpoint, [
                    'json' => [
                        'dish'        => $dish,
                        'constraints' => $constraints,
                    ],
                ]);

                $code = $resp->getStatusCode();
                $data = json_decode((string) $resp->getBody(), true) ?: [];

                // Thành công & có đủ trường cần thiết
                if ($code >= 200 && $code < 300 && is_array($data)) {
                    return $this->normalizeHowto($dish, $data);
                }
            }
        } catch (GuzzleException $e) {
            // rơi xuống fallback bên dưới
        } catch (\Throwable $e) {
            // rơi xuống fallback bên dưới
        }

        // Fallback khi API lỗi/không trả đúng định dạng
        return $this->fallbackHowto($dish, $constraints);
    }

    /* ===================== Helpers ===================== */

    private function normalizeHowto(string $dish, array $data): array
    {
        // Bảo đảm các trường luôn tồn tại đúng kiểu cho FE
        $title = trim((string) ($data['title'] ?? '')) ?: $this->mbUcfirst($dish);

        $ingredients = array_values(array_filter(array_map('strval', $data['ingredients'] ?? [])));
        $steps       = array_values(array_filter(array_map('strval', $data['steps'] ?? [])));
        $tips        = array_values(array_filter(array_map('strval', $data['tips'] ?? [])));

        $servings = $data['servings'] ?? null;
        if (!is_null($servings) && !is_numeric($servings)) {
            $servings = null;
        } elseif (is_numeric($servings)) {
            $servings = (int) $servings;
        }

        $timeMin = $data['time_min'] ?? null;
        if (!is_null($timeMin) && !is_numeric($timeMin)) {
            $timeMin = null;
        } elseif (is_numeric($timeMin)) {
            $timeMin = (int) $timeMin;
        }

        return [
            'title'       => $title,
            'servings'    => $servings,
            'time_min'    => $timeMin,
            'ingredients' => $ingredients,
            'steps'       => $steps,
            'tips'        => $tips,
        ];
    }

    private function fallbackHowto(string $dish, array $constraints): array
    {
        $title = $this->mbUcfirst($dish);

        // Tạo hướng dẫn tối thiểu nhưng có ích, để người dùng đọc được ngay
        $avoid = array_values(array_filter(array_map('strval', $constraints['avoid'] ?? [])));
        $avoidLine = empty($avoid) ? null : 'Tránh: ' . implode(', ', $avoid) . '.';

        $baseSteps = [
            "Chuẩn bị nguyên liệu tươi cho món {$title}.",
            "Sơ chế sạch sẽ; thái/cắt theo yêu cầu của món.",
            "Ướp hoặc pha nêm cơ bản 10–15 phút (nếu cần).",
            "Đun/nấu/xào theo trình tự tiêu chuẩn cho món {$title}.",
            "Nếm lại, điều chỉnh gia vị vừa ăn.",
            "Tắt bếp, trình bày và dùng nóng.",
        ];

        if ($avoidLine) {
            array_splice($baseSteps, 2, 0, [$avoidLine]);
        }

        return [
            'title'       => $title,
            'servings'    => null,
            'time_min'    => $constraints['time_max_min'] ?? 25,
            'ingredients' => [],         // không đoán bừa khi không có API
            'steps'       => $baseSteps, // luôn có nội dung để FE hiển thị
            'tips'        => [
                'Ưu tiên nguyên liệu tươi; chế biến nhanh để giữ độ ngọt.',
                'Điều chỉnh muối/đường/nước mắm theo khẩu vị gia đình.',
            ],
        ];
    }

    private function mbUcfirst(string $str): string
    {
        $str = trim($str);
        if ($str === '') return $str;
        $first = mb_substr($str, 0, 1, 'UTF-8');
        $rest  = mb_substr($str, 1, null, 'UTF-8');
        return mb_strtoupper($first, 'UTF-8') . $rest;
    }
}
