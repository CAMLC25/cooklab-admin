<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'id_cooklab',
        'name',
        'email',
        'password',
        'avatar',
        'role',    // 'user' hoặc 'admin'
        'status',  // 'active' hoặc 'locked'
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function isAdmin()
    {
        return $this->role === 'admin';
    }
    /** QUAN HỆ VỚI CÁC BẢNG KHÁC **/

    public function recipes()
    {
        return $this->hasMany(Recipe::class);
    }

    public function savedRecipes()
    {
        return $this->belongsToMany(Recipe::class, 'saved_recipes');
    }

    public function views()
    {
        return $this->hasMany(View::class);
    }

    public function notifications()
    {
        return $this->hasMany(Notification::class);
    }

    public function searchLogs()
    {
        return $this->hasMany(SearchLog::class);
    }

    public function followings()
    {
        return $this->hasMany(Follow::class, 'follower_id');
    }

    public function followers()
    {
        return $this->hasMany(Follow::class, 'followee_id');
    }

    public function followingUsers()
    {
        return $this->belongsToMany(User::class, 'follows', 'follower_id', 'followee_id');
    }

    public function followerUsers()
    {
        return $this->belongsToMany(User::class, 'follows', 'followee_id', 'follower_id');
    }

    // $user->followings → danh sách record Follow người này đã follow

    // $user->followers → danh sách record Follow của những người đang follow user này

    // $user->followingUsers → danh sách User mà người này follow

    // $user->followerUsers → danh sách User đang follow người này
}
