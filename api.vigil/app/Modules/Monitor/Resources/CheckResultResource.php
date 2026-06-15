<?php

namespace App\Modules\Monitor\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CheckResultResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => (string) $this->id,
            'result' => $this->result->value,
            'responseTimeMs' => $this->response_time_ms,
            'statusCode' => $this->status_code,
            'error' => $this->error,
            'region' => $this->region,
            'checkedAt' => $this->checked_at?->toIso8601String(),
        ];
    }
}
