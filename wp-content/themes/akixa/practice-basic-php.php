<?php

/**
 * Dữ liệu thô: có dòng đầy đủ, dòng rỗng, chỉ có khoảng trắng (mô phỏng nhập liệu lỗi / bỏ trống).
 * Cấu trúc tương tự IMPL-01: text + link tuỳ chọn.
 */
$promotions_raw = [
    ['text' => 'Giảm 15% khi đặt bản vẽ trong tháng này', 'link' => 'https://example.com/khuyen-mai-thang'],
    ['text' => '', 'link' => ''], // bỏ qua khi build
    ['text' => 'Tặng kèm file PDF bản vẽ chi tiết', 'link' => ''],
    ['text' => '   ', 'link' => 'https://ignored.example/'], // text chỉ khoảng trắng -> bỏ qua
    ['text' => 'Miễn phí tư vấn 1 buổi với kiến trúc sư', 'link' => 'https://example.com/tu-van'],
    ['text' => '', 'link' => 'https://example.com/no-text'], // có link nhưng không text -> bỏ qua
    ['text' => 'Hỗ trợ chỉnh sửa mặt bằng lần đầu', 'link' => ''],
];

$promotions = [];
foreach ($promotions_raw as $row) {
    $text = isset($row['text']) ? trim((string) $row['text']) : '';
    if ($text === '') {
        continue;
    }
    $link = isset($row['link']) ? trim((string) $row['link']) : '';
    $promotions[] = [
        'text' => $text,
        'link' => $link,
    ];
}

function format_sold_count($n) {
    if ($n >= 1000000) {
        return number_format($n / 1000000, 1) . 'M';
    } elseif ($n >= 1000) {
        return number_format($n / 1000, 1) . 'K';
    } else {
        return $n;
    }
}
