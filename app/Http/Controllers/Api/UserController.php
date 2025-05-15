<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Follow;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    // Cập nhật thông tin người dùng
    // public function updateUser(Request $request, $id)
    // {
    //     $user = User::findOrFail($id);

    //     // Xác thực dữ liệu
    //     $validator = Validator::make($request->all(), [
    //         'name' => ['required', 'string', 'max:255'],
    //         'email' => [
    //             'required',
    //             'email',
    //             'max:255',
    //             Rule::unique('users')->ignore($user->id),
    //         ],
    //         'password' => ['nullable', 'min:6', 'confirmed'],
    //         'avatar' => ['nullable', 'image', 'mimes:jpg,png,jpeg,gif'],
    //         // 'status' => ['required', Rule::in(['active', 'locked'])],
    //         'id_cooklab' => [
    //             'required',
    //             'regex:/^[A-Za-z0-9_]+$/',
    //             'min:4',
    //             'max:20',
    //             Rule::unique('users', 'id_cooklab')->ignore($user->id),
    //         ],
    //     ]);

    //     if ($validator->fails()) {
    //         return response()->json([
    //             'message' => 'Validation failed.',
    //             'errors' => $validator->errors()
    //         ], 422);
    //     }

    //     $data = $validator->validated();

    //     // Cập nhật thông tin cơ bản
    //     // $user->fill([
    //     //     'name' => $data['name'],
    //     //     'email' => $data['email'],
    //     //     'id_cooklab' => $data['id_cooklab'],
    //     //     'status' => $data['status'],
    //     // ]);

    //     $updateData = [
    //         'name' => $data['name'],
    //         'email' => $data['email'],
    //         'id_cooklab' => $data['id_cooklab'],
    //     ];

    //     if (isset($data['status'])) {
    //         $updateData['status'] = $data['status'];
    //     }

    //     $user->fill($updateData);


    //     // Nếu có mật khẩu mới
    //     if (!empty($data['password'])) {
    //         $user->password = Hash::make($data['password']);
    //     }

    //     // Xử lý avatar
    //     if ($request->hasFile('avatar')) {
    //         // Nếu gửi file ảnh
    //         $avatar = $request->file('avatar');
    //         $fileName = time() . '_' . $avatar->getClientOriginalName();
    //         $path = 'admin-assets/images/cook_lab/avata_users/' . $fileName;
    //         $avatar->move(public_path('admin-assets/images/cook_lab/avata_users'), $fileName);

    //         // Xóa avatar cũ nếu có
    //         if ($user->avatar && file_exists(public_path($user->avatar))) {
    //             @unlink(public_path($user->avatar));
    //         }

    //         $user->avatar = $path;
    //     } elseif (!empty($data['avatar']) && is_string($data['avatar'])) {
    //         // Nếu gửi chuỗi đường dẫn ảnh
    //         $user->avatar = $data['avatar'];
    //     }

    //     $user->save();

    //     return response()->json([
    //         'success' => true,
    //         'user' => [
    //             'id' => $user->id,
    //             'name' => $user->name,
    //             'email' => $user->email,
    //             'avatar' => $user->avatar,
    //             'id_cooklab' => $user->id_cooklab,
    //         ]
    //     ]);
    // }

    public function updateUser(Request $request, $id)
    {
        $user = User::findOrFail($id);

        // Xác thực dữ liệu - chỉ validate nếu có gửi lên
        $validator = Validator::make($request->all(), [
            'name' => ['sometimes', 'string', 'max:255'],
            'email' => [
                'sometimes',
                'email',
                'max:255',
                Rule::unique('users')->ignore($user->id),
            ],
            'password' => ['sometimes', 'nullable', 'min:6', 'confirmed'],
            'avatar' => ['sometimes', 'nullable', 'image', 'mimes:jpg,png,jpeg,gif'],
            // 'status' => ['sometimes', Rule::in(['active', 'locked'])],
            'id_cooklab' => [
                'sometimes',
                'regex:/^[A-Za-z0-9_]+$/',
                'min:4',
                'max:20',
                Rule::unique('users', 'id_cooklab')->ignore($user->id),
            ],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed.',
                'errors' => $validator->errors()
            ], 422);
        }

        $data = $validator->validated();

        // Nếu không có dữ liệu nào được gửi lên
        if (empty($data) && !$request->hasFile('avatar')) {
            return response()->json([
                'message' => 'No data provided for update.'
            ], 400);
        }

        $updateData = [];

        if (isset($data['name'])) {
            $updateData['name'] = $data['name'];
        }

        if (isset($data['email'])) {
            $updateData['email'] = $data['email'];
        }

        if (isset($data['id_cooklab'])) {
            $updateData['id_cooklab'] = $data['id_cooklab'];
        }

        if (isset($data['status'])) {
            $updateData['status'] = $data['status'];
        }

        $user->fill($updateData);

        if (!empty($data['password'])) {
            $user->password = Hash::make($data['password']);
        }

        if ($request->hasFile('avatar')) {
            $avatar = $request->file('avatar');
            $fileName = time() . '_' . $avatar->getClientOriginalName();
            $path = 'admin-assets/images/cook_lab/avata_users/' . $fileName;
            $avatar->move(public_path('admin-assets/images/cook_lab/avata_users'), $fileName);

            if ($user->avatar && file_exists(public_path($user->avatar))) {
                @unlink(public_path($user->avatar));
            }

            $user->avatar = $path;
        } elseif (!empty($data['avatar']) && is_string($data['avatar'])) {
            $user->avatar = $data['avatar'];
        }

        $user->save();

        return response()->json([
            'success' => true,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'avatar' => $user->avatar,
                'id_cooklab' => $user->id_cooklab,
            ]
        ]);
    }


    public function show($id)
    {

        // 1. Lấy thông tin người dùng
        $user = User::findOrFail($id);
        // Lấy số lượng người theo dõi
        $followersCount = Follow::where('followee_id', $id)->count();

        // Lấy số lượng người mà người dùng đang theo dõi
        $followingCount = Follow::where('follower_id', $id)->count();

        // 2. Lấy danh sách recipes gần đây của user
        $recipes = $user->recipes()->latest()->get();

        // 3. Chuyển thông tin user thành mảng
        $userData = [
            'id' => $user->id,
            'name' => $user->name,
            'id_cooklab' => $user->id_cooklab,
            'email' => $user->email,
            'avatar' => $user->avatar,
            'followers_count' => $followersCount,
            'following_count' => $followingCount,
        ];

        // 4. Chuyển từng recipe thành mảng
        $recipesData = $recipes->map(function ($recipe) {
            return [
                'id' => $recipe->id,
                'user_id' => $recipe->user_id,
                'category_id' => $recipe->category_id,
                'title' => $recipe->title,
                'image' => $recipe->image,
                'description' => $recipe->description,
                'servings' => $recipe->servings,
                'cook_time' => $recipe->cook_time,
                'status' => $recipe->status,
                'reason_rejected' => $recipe->reason_rejected,
                'created_at' => $recipe->created_at->toISOString(),
                'updated_at' => $recipe->updated_at->toISOString(),
                'user' => [
                    'id' => $recipe->user->id,
                    'name' => $recipe->user->name,
                    'avatar' => $recipe->user->avatar,
                    'id_cooklab' => $recipe->user->id_cooklab,
                ],
                'category' => [
                    'id' => $recipe->category->id,
                    'name' => $recipe->category->name,
                ],
                'ingredients' => $recipe->ingredients->map(function ($ingredient) {
                    return [
                        'id' => $ingredient->id,
                        'recipe_id' => $ingredient->recipe_id,
                        'name' => $ingredient->name
                    ];
                }),
                'steps' => $recipe->steps->map(function ($step) {
                    return [
                        'id' => $step->id,
                        'recipe_id' => $step->recipe_id,
                        'step_number' => $step->step_number,
                        'description' => $step->description,
                        'image' => $step->image
                    ];
                }),
                'reactions' => $recipe->reactions->map(function ($reaction) {
                    return [
                        'id' => $reaction->id,
                        'user_id' => $reaction->user_id,
                        'recipe_id' => $reaction->recipe_id,
                        'type' => $reaction->type,
                        'created_at' => $reaction->created_at->toISOString(),
                        'updated_at' => $reaction->updated_at->toISOString(),
                    ];
                }),
                'comments' => $recipe->comments->map(function ($comment) {
                    return [
                        'id' => $comment->id,
                        'user_id' => $comment->user_id,
                        'recipe_id' => $comment->recipe_id,
                        'content' => $comment->content,
                        'created_at' => $comment->created_at->toISOString(),
                        'updated_at' => $comment->updated_at->toISOString(),
                        'user' => [
                            'id' => $comment->user->id,
                            'name' => $comment->user->name,
                            'avatar' => $comment->user->avatar
                        ]
                    ];
                }),
            ];
        });

        // 5. Trả về JSON
        return response()->json([
            'success' => true,
            'user' => $userData,
            'recipes' => $recipesData,
        ]);
    }

    public function showCustomer($id)
    {
        // 1. Lấy thông tin người dùng
        $user = User::find($id); // Dùng find() thay vì findOrFail để kiểm tra nếu không có user

        // Kiểm tra nếu không tìm thấy người dùng
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Không tìm thấy người dùng với ID: ' . $id
            ], 404); // Trả về lỗi 404 nếu không tìm thấy người dùng
        }

        // Lấy số lượng người theo dõi
        $followersCount = Follow::where('followee_id', $id)->count();

        // Lấy số lượng người mà người dùng đang theo dõi
        $followingCount = Follow::where('follower_id', $id)->count();

        // 2. Lấy danh sách recipes đã được phê duyệt
        $recipes = $user->recipes()->where('status', 'approved')->latest()->get();

        // 3. Chuyển thông tin user thành mảng
        $userData = [
            'id' => $user->id,
            'name' => $user->name,
            'id_cooklab' => $user->id_cooklab,
            'email' => $user->email,
            'avatar' => $user->avatar,
            'followers_count' => $followersCount,
            'following_count' => $followingCount,
        ];

        // 4. Chuyển từng recipe thành mảng
        $recipesData = $recipes->map(function ($recipe) {
            return [
                'id' => $recipe->id,
                'user_id' => $recipe->user_id,
                'category_id' => $recipe->category_id,
                'title' => $recipe->title,
                'image' => $recipe->image,
                'description' => $recipe->description,
                'servings' => $recipe->servings,
                'cook_time' => $recipe->cook_time,
                'status' => $recipe->status,
                'reason_rejected' => $recipe->reason_rejected,
                'created_at' => $recipe->created_at->toISOString(),
                'updated_at' => $recipe->updated_at->toISOString(),
                'user' => [
                    'id' => $recipe->user->id,
                    'name' => $recipe->user->name,
                    'avatar' => $recipe->user->avatar,
                    'id_cooklab' => $recipe->user->id_cooklab,
                ],
                'category' => [
                    'id' => $recipe->category->id,
                    'name' => $recipe->category->name,
                ],
                'ingredients' => $recipe->ingredients->map(function ($ingredient) {
                    return [
                        'id' => $ingredient->id,
                        'recipe_id' => $ingredient->recipe_id,
                        'name' => $ingredient->name
                    ];
                }),
                'steps' => $recipe->steps->map(function ($step) {
                    return [
                        'id' => $step->id,
                        'recipe_id' => $step->recipe_id,
                        'step_number' => $step->step_number,
                        'description' => $step->description,
                        'image' => $step->image
                    ];
                }),
                'reactions' => $recipe->reactions->map(function ($reaction) {
                    return [
                        'id' => $reaction->id,
                        'user_id' => $reaction->user_id,
                        'recipe_id' => $reaction->recipe_id,
                        'type' => $reaction->type,
                        'created_at' => $reaction->created_at->toISOString(),
                        'updated_at' => $reaction->updated_at->toISOString(),
                    ];
                }),
                'comments' => $recipe->comments->map(function ($comment) {
                    return [
                        'id' => $comment->id,
                        'user_id' => $comment->user_id,
                        'recipe_id' => $comment->recipe_id,
                        'content' => $comment->content,
                        'created_at' => $comment->created_at->toISOString(),
                        'updated_at' => $comment->updated_at->toISOString(),
                        'user' => [
                            'id' => $comment->user->id,
                            'name' => $comment->user->name,
                            'avatar' => $comment->user->avatar
                        ]
                    ];
                }),
            ];
        });

        // 5. Trả về JSON
        return response()->json([
            'success' => true,
            'user' => $userData,
            'recipes' => $recipesData,
        ]);
    }
}
