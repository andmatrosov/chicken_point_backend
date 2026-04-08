<?php

namespace App\Http\Resources;

use App\Models\MvpSetting;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin MvpSetting
 */
class MvpSettingResource extends JsonResource
{
    /**
     * @return array<string, bool|string|null>
     */
    public function toArray(Request $request): array
    {
        return [
            'version' => $this->version->value,
            'mvp_link' => $this->mvp_link,
            'is_active' => (bool) $this->is_active,
        ];
    }
}
