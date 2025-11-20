<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

class RecipeSearch
{
    /**
     * Tìm công thức theo danh sách tên món (ưu tiên exact rồi LIKE).
     * Trả về: id, title, image, thumb (relative path), cook_time (string), time_min (int|null).
     */
    public function searchByDishNames(array $dishNames, int $limit = 12): array
    {
        // 1) Chuẩn hoá input
        $names = array_values(array_filter(array_map(
            fn($s) => mb_strtolower(trim((string) $s)),
            $dishNames
        ), fn($s) => $s !== ''));

        if (empty($names)) {
            return [];
        }

        // 2) Query
        $q = DB::table('recipes')
            ->selectRaw('recipes.id, recipes.title, recipes.image, recipes.cook_time')
            ->where(function ($outer) use ($names) {
                $outer
                    // exact
                    ->where(function ($or) use ($names) {
                        foreach ($names as $n) {
                            $or->orWhereRaw('LOWER(recipes.title) = ?', [$n]);
                        }
                    })
                    // like
                    ->orWhere(function ($or) use ($names) {
                        foreach ($names as $n) {
                            $or->orWhereRaw(
                                "LOWER(recipes.title) LIKE ? ESCAPE '~'",
                                ['%' . self::escapeLike($n) . '%']
                            );
                        }
                    });
            });

        // 3) ORDER BY theo độ khớp -> độ dài tiêu đề -> id
        $orderParts = [];
        $bindings   = [];
        foreach ($names as $n) {
            $orderParts[] = "CASE WHEN LOWER(recipes.title) = ? THEN 2
                               WHEN LOWER(recipes.title) LIKE ? ESCAPE '~' THEN 1
                               ELSE 0 END";
            $bindings[] = $n;
            $bindings[] = '%' . self::escapeLike($n) . '%';
        }
        if (!empty($orderParts)) {
            $q->orderByRaw(implode(' + ', $orderParts) . ' DESC', $bindings);
        }
        $q->orderByRaw('CHAR_LENGTH(recipes.title) ASC');
        $q->orderBy('recipes.id', 'ASC');

        // 4) Map kết quả về dạng FE cần
        return $q->limit($limit)->get()
            ->map(function ($r) {
                $imgCol   = (string) ($r->image ?? '');
                $cookStr  = (string) ($r->cook_time ?? '');

                return [
                    'id'        => (string) $r->id,
                    'title'     => (string) $r->title,
                    // path tương đối để Android ghép BASE_URL
                    'image'     => self::normalizePath($imgCol),
                    'thumb'     => self::normalizePath($imgCol), // có cột thumb thì đổi ở đây
                    'cook_time' => $cookStr,                      // giữ nguyên chuỗi cho FE hiển thị
                    'time_min'  => self::toMinutes($cookStr),     // >>> đã parse ra phút
                ];
            })
            ->all();
    }

    /** Escape wildcard cho LIKE. */
    private static function escapeLike(string $s): string
    {
        return strtr($s, [
            '~' => '~~',
            '%' => '~%',
            '_' => '~_',
        ]);
    }

    /** Chuẩn hoá path về dạng tương đối (bỏ leading slash). */
    private static function normalizePath(?string $path): ?string
    {
        $path = trim((string) $path);
        if ($path === '') return null;

        if (str_starts_with($path, 'http://')
            || str_starts_with($path, 'https://')
            || str_starts_with($path, 'data:')
        ) {
            return $path; // giữ nguyên nếu là URL đầy đủ
        }
        return ltrim($path, '/');
    }

    /**
     * Parse cook_time (string) -> minutes (int|null).
     * Hỗ trợ: "30 phút", "1 giờ 15 phút", "2 tiếng", "10–15 phút", "khoảng 20 phút", "1h30", "1h", "45m".
     */
    private static function toMinutes(string $s): ?int
    {
        if ($s === '') return null;

        $src = mb_strtolower(trim($s));

        // Chuẩn hoá dấu gạch range
        $src = str_replace(['–','—','~','to'], '-', $src);
        $src = preg_replace('/\s+/', ' ', $src);

        // 1) "1 giờ 15 phút" / "1 tiếng 15 phút"
        if (preg_match('/(\d+)\s*(giờ|tiếng|hour|h)\s*(\d+)\s*(phút|min|m)/u', $src, $m)) {
            $h = (int)$m[1]; $m2 = (int)$m[3];
            return $h * 60 + $m2;
        }

        // 2) "1 giờ" / "2 tiếng" / "1h30" / "1h"
        if (preg_match('/(\d+)\s*(giờ|tiếng|hour|h)\b/u', $src, $m)) {
            $h = (int)$m[1];
            // có thể còn thêm "30" sau chữ h: "1h30"
            if (preg_match('/\b'.$m[1].'\s*(?:giờ|tiếng|hour|h)\s*(\d{1,2})\b/u', $src, $m2)) {
                return $h * 60 + (int)$m2[1];
            }
            return $h * 60;
        }

        // 3) "30 phút" / "45 min" / "45m"
        if (preg_match('/(\d+)\s*(phút|min|m)\b/u', $src, $m)) {
            return (int)$m[1];
        }

        // 4) Range "10-15 phút" -> lấy số phải (bảo thủ) hoặc số trái.
        if (preg_match('/(\d+)\s*-\s*(\d+)\s*(phút|min|m)/u', $src, $m)) {
            return (int)$m[2]; // chọn mốc trên
        }

        // 5) Chỉ 1 số —— giả định là phút: "≈ 25"
        if (preg_match('/(\d{1,3})\b/u', $src, $m)) {
            return (int)$m[1];
        }

        return null;
    }
}
