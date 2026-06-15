<?php

namespace App\Modules\StatusPage\Models;

use App\Modules\Monitor\Models\Monitor;
use Database\Factories\StatusPageFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Str;

#[Fillable(['name', 'slug', 'custom_domain', 'branding', 'is_public'])]
class StatusPage extends Model
{
    /** @use HasFactory<StatusPageFactory> */
    use HasFactory;

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (StatusPage $statusPage): void {
            if (empty($statusPage->slug)) {
                $statusPage->slug = Str::slug($statusPage->name);
            }
        });
    }

    protected function casts(): array
    {
        return [
            'branding' => 'array',
            'is_public' => 'boolean',
        ];
    }

    public function monitors(): BelongsToMany
    {
        return $this->belongsToMany(Monitor::class, 'monitor_status_page')
            ->withPivot(['group_name', 'sort_order'])
            ->withTimestamps();
    }

    protected static function newFactory(): Factory
    {
        return StatusPageFactory::new();
    }
}
