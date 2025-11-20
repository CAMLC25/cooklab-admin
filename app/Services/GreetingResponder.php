<?php

namespace App\Services;

class GreetingResponder
{
    public function build(): array
    {
        // Các gợi ý dạng “quick actions” cho FE
        return [
            [
                'title'   => 'Chào bạn! Mình có thể giúp gì hôm nay?',
                'summary' => 'Bạn muốn tìm công thức món ăn, đồ uống, món chè/tráng miệng, hay xem hướng dẫn nấu?',
                'tags'    => ['greeting','help'],
                'time_min'=> null,
            ],
            [
                'title'   => 'Gợi ý nhanh theo nguyên liệu',
                'summary' => 'Ví dụ: “mình có thịt bò, hành tây; tránh sữa; 30 phút”',
                'tags'    => ['recipes','ingredients'],
                'time_min'=> null,
            ],
            [
                'title'   => 'Đồ uống / nước giải khát',
                'summary' => 'Ví dụ: “cho mình đồ uống thanh mát ít đường”',
                'tags'    => ['drinks'],
                'time_min'=> null,
            ],
            [
                'title'   => 'Chè / tráng miệng',
                'summary' => 'Ví dụ: “món chè mát lạnh, dễ làm cuối tuần”',
                'tags'    => ['tea_dessert'],
                'time_min'=> null,
            ],
            [
                'title'   => 'Cách nấu món cụ thể',
                'summary' => 'Ví dụ: “cách nấu bò xào hành tây”',
                'tags'    => ['howto'],
                'time_min'=> null,
            ],
        ];
    }
}
