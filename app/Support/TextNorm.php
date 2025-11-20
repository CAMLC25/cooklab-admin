<?php
namespace App\Support;

class TextNorm {
    public static function viLowerNoDiacritics(string $s): string {
        $s = mb_strtolower(trim($s), 'UTF-8');
        $from = ['à','á','ạ','ả','ã','â','ầ','ấ','ậ','ẩ','ẫ','ă','ằ','ắ','ặ','ẳ','ẵ','è','é','ẹ','ẻ','ẽ','ê','ề','ế','ệ','ể','ễ','ì','í','ị','ỉ','ĩ','ò','ó','ọ','ỏ','õ','ô','ồ','ố','ộ','ổ','ỗ','ơ','ờ','ớ','ợ','ở','ỡ','ù','ú','ụ','ủ','ũ','ư','ừ','ứ','ự','ử','ữ','ỳ','ý','ỵ','ỷ','ỹ','đ'];
        $to   = ['a','a','a','a','a','a','a','a','a','a','a','a','a','a','a','a','a','e','e','e','e','e','e','e','e','e','e','i','i','i','i','i','o','o','o','o','o','o','o','o','o','o','o','o','o','o','o','o','o','u','u','u','u','u','u','u','u','u','u','y','y','y','y','y','d'];
        return preg_replace('/\s+/', ' ', str_replace($from, $to, $s));
    }

    public static function normalizeArray(array $arr): array {
        $out = [];
        foreach ($arr as $x) {
            if (!is_string($x)) continue;
            $t = trim($x);
            if ($t === '') continue;
            $out[$t] = true;
        }
        return array_keys($out);
    }
}
