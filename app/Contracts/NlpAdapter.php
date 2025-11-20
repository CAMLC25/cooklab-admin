<?php
namespace App\Contracts;

interface NlpAdapter {
    /** @return array{ingredients:string[], dish_names:string[], constraints:array} */
    public function extract(string $text): array;
}
