<?php

namespace App\Modules\Auth\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['role_id', 'permission_key', 'value'])]
class RolePermission extends Model
{
    protected function casts(): array
    {
        return [
            'value' => 'boolean',
        ];
    }

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }
}
