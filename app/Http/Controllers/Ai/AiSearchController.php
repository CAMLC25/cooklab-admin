<?php

namespace App\Http\Controllers\Ai;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use App\Models\Recipe;

class AiSearchController extends Controller
{
    /**
     * POST /api/ai/search
     * Body ví dụ:
     * {
     *   "ingredients": ["thịt bò", "gừng"], // hoặc bỏ trống và chỉ gửi "text"
     *   "text": "Mình có thịt bò với gừng, gợi ý món gì?",
     *   "mode": "ANY", // ANY | ALL
     *   "top_k": 3
     * }
     */
    public function search(Request $req)
    {
        $topK = max(1, min(10, (int)($req->input('top_k', 3))));
        $mode = strtoupper((string)$req->input('mode', 'ANY')) === 'ALL' ? 'ALL' : 'ANY';

        // --- 1) Chuẩn hóa input ---
        $inputIng = (array) $req->input('ingredients', []);
        $text     = trim((string) $req->input('text', ''));

        if (empty($inputIng) && $text !== '') {
            // Tách nhanh từ text: bỏ dấu / ký tự thừa, lấy n-gram (1–2 từ) đơn giản
            $tokens = $this->tokensFromText($text);
            $inputIng = $tokens;
        }

        // Chuẩn hóa & lọc rác
        $recognizedNorms = [];
        foreach ($inputIng as $w) {
            $n = $this->norm($w);
            if ($n !== '') $recognizedNorms[$n] = true;
        }
        $recognizedNorms = array_keys($recognizedNorms); // unique & giữ thứ tự tương đối

        // Nếu không có gì để tìm → trả về rỗng
        if (empty($recognizedNorms)) {
            return response()->json([
                'input_ingredients'  => $inputIng,
                'recognized'         => [],
                'recognized_display' => '',
                'results'            => [],
            ]);
        }

        // --- 2) Query recipes khớp nguyên liệu ---
        // Với DB hiện tại (recipe_ingredients chỉ có 'name'): dùng LIKE theo từng token đã norm.
        // (Đơn giản & hiệu quả cho MVP)
        $q = Recipe::query()
            ->leftJoin(DB::raw('(select recipe_id, count(*) cnt from reactions group by recipe_id) rct'), 'rct.recipe_id', '=', 'recipes.id')
            ->leftJoin(DB::raw('(select recipe_id, count(*) cnt from views group by recipe_id) vw'), 'vw.recipe_id', '=', 'recipes.id')
            ->select('recipes.*',
                DB::raw('COALESCE(rct.cnt,0) as reactions_count'),
                DB::raw('COALESCE(vw.cnt,0)  as views_count')
            );

        if ($mode === 'ALL') {
            // Mỗi token phải tồn tại ít nhất một nguyên liệu match
            foreach ($recognizedNorms as $tok) {
                $q->whereExists(function($sub) use ($tok) {
                    $sub->from('recipe_ingredients as ri')
                        ->whereColumn('ri.recipe_id', 'recipes.id')
                        ->where('ri.name', 'like', '%'.$tok.'%');
                });
            }
        } else {
            // ANY: chỉ cần khớp một token là được
            $q->whereExists(function($sub) use ($recognizedNorms) {
                $sub->from('recipe_ingredients as ri')
                    ->whereColumn('ri.recipe_id', 'recipes.id')
                    ->where(function($w) use ($recognizedNorms) {
                        foreach ($recognizedNorms as $tok) {
                            $w->orWhere('ri.name', 'like', '%'.$tok.'%');
                        }
                    });
            });
        }

        $rows = $q->orderByDesc('reactions_count')
            ->orderByDesc('views_count')
            ->orderByDesc('recipes.created_at')
            ->limit($topK)
            ->get();

        // --- 3) Lấy nguyên liệu matched cho từng recipe (hiển thị đẹp) ---
        $results = [];
        if ($rows->isNotEmpty()) {
            $ids = $rows->pluck('id')->all();
            $allIngs = DB::table('recipe_ingredients')
                ->whereIn('recipe_id', $ids)
                ->select('recipe_id', 'name')
                ->get()
                ->groupBy('recipe_id');

            foreach ($rows as $r) {
                $list = $allIngs->get($r->id, collect());
                $matched = $this->filterMatchedNames($list->pluck('name')->all(), $recognizedNorms);

                $results[] = [
                    'id'                   => $r->id,
                    'title'                => $r->title,
                    'image'                => $r->image,
                    'reactions'            => (int)$r->reactions_count,
                    'views'                => (int)$r->views_count,
                    'created_at'           => (string)$r->created_at,
                    'matched_ingredients'  => $matched,
                ];
            }
        }

        return response()->json([
            'input_ingredients'  => array_values($inputIng),
            'recognized'         => array_values($recognizedNorms),           // dạng norm (không dấu, thường)
            'recognized_display' => $this->displayJoin($recognizedNorms),     // hiển thị đẹp
            'results'            => $results,
        ]);
    }

    // ----------------- Helpers -----------------

    /** Chuẩn hoá: lower + bỏ dấu + bỏ ký tự lạ + gộp space. */
    private function norm(string $s): string
    {
        $s = Str::of($s)->lower()->squish()->toString();
        // bỏ dấu (nếu iconv không có thì vẫn chạy bình thường)
        $x = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $s);
        if ($x !== false) $s = $x;
        $s = preg_replace('/[^a-z0-9\s]/', ' ', $s);
        $s = preg_replace('/\s+/', ' ', trim($s));
        return $s;
    }

    /** Tách token từ text người dùng (1-gram và 2-gram đơn giản). */
    private function tokensFromText(string $text): array
    {
        $n = $this->norm($text);
        if ($n === '') return [];
        $ws = array_values(array_filter(explode(' ', $n), fn($t)=>$t !== ''));

        // loại stop words đơn giản
        $stop = [
            'toi','minh','ban','co','san','con','nha','ve','voi','lam','tu','nau','mon','gi','ngon','nen','giup','goi','y','chon','ra','cach'
        ];
        $ws = array_values(array_filter($ws, fn($t)=>!in_array($t, $stop, true)));

        // sinh 1-gram + 2-gram
        $out = [];
        for ($i=0; $i<count($ws); $i++) {
            $out[] = $ws[$i];
            if ($i+1 < count($ws)) {
                $out[] = $ws[$i].' '.$ws[$i+1];
            }
        }
        // unique, giữ thứ tự
        $seen = [];
        $out2 = [];
        foreach ($out as $t) {
            if (!isset($seen[$t])) { $seen[$t]=1; $out2[]=$t; }
        }
        return $out2;
    }

    /** Lọc tên nguyên liệu thực sự khớp từ list tên trong DB theo mảng token đã norm. */
    private function filterMatchedNames(array $names, array $normTokens): array
    {
        $matched = [];
        foreach ($names as $name) {
            $n = $this->norm($name);
            foreach ($normTokens as $tok) {
                if ($tok !== '' && str_contains($n, $tok)) {
                    $matched[] = $name; // trả lại tên gốc để hiển thị đẹp
                    break;
                }
            }
        }
        // unique giữ thứ tự
        $seen=[]; $out=[];
        foreach ($matched as $m) {
            if (!isset($seen[$m])) { $seen[$m]=1; $out[]=$m; }
        }
        return $out;
    }

    /** Hiển thị danh sách token norm theo kiểu Title Case, phân tách bằng dấu phẩy. */
    private function displayJoin(array $norms): string
    {
        $pretty = array_map(fn($x)=> Str::title($x), $norms);
        return implode(', ', $pretty);
    }
}
