<?php

return [
    'required' => ':attribute không được để trống.',
    'email' => ':attribute phải là địa chỉ email hợp lệ.',
    'min' => [
        'string' => ':attribute phải có ít nhất :min ký tự.',
    ],
    'max' => [
        'string' => ':attribute không được vượt quá :max ký tự.',
    ],
    'unique' => ':attribute đã được sử dụng.',
    'confirmed' => ':attribute không khớp với xác nhận.',
    'regex' => ':attribute không đúng định dạng.',
    'in' => ':attribute không hợp lệ.',
    'image' => ':attribute phải là tệp hình ảnh.',
    'mimes' => ':attribute phải là tệp có định dạng: :values.',
    'numeric' => ':attribute phải là số.',
    'file' => ':attribute phải là tệp.',

    // Tùy chỉnh tên hiển thị tiếng Việt
    'attributes' => [
        'name' => 'Tên',
        'email' => 'Email',
        'password' => 'Mật khẩu',
        'password_confirmation' => 'Xác nhận mật khẩu',
        'avatar' => 'Ảnh đại diện',
        'id_cooklab' => 'ID CookLab',
        'status' => 'Trạng thái',
    ],
];
