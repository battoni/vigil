<?php

namespace App\Modules\Project\Models;

use App\Modules\Monitor\Models\Monitor;
use Database\Factories\ProjectFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

#[Fillable(['name', 'slug'])]
class Project extends Model
{
    /** @use HasFactory<ProjectFactory> */
    use HasFactory;

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (Project $project): void {
            if (empty($project->slug)) {
                $project->slug = Str::slug($project->name);
            }
        });
    }

    public function monitors(): HasMany
    {
        return $this->hasMany(Monitor::class);
    }

    protected static function newFactory(): Factory
    {
        return ProjectFactory::new();
    }
}
