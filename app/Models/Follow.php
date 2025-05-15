<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Follow extends Model
{

        // $user->followings → danh sách record Follow người này đã follow

            // $user->followers → danh sách record Follow của những người đang follow user này

            // $user->followingUsers → danh sách User mà người này follow

            // $user->followerUsers → danh sách User đang follow người này
    protected $fillable = [
        'follower_id',
        'followee_id',
    ];

    /**
     * Người theo dõi (follower)
     */
    public function follower(): BelongsTo
    {
        return $this->belongsTo(User::class, 'follower_id');
    }

    /**
     * Người được theo dõi (followee)
     */
    public function followee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'followee_id');
    }
}
