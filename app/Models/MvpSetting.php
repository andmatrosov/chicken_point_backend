<?php

namespace App\Models;

use App\Enums\MvpSettingVersion;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property MvpSettingVersion $version
 * @property string|null $mvp_link
 * @property bool $is_active
 */
#[Fillable([
    'version',
    'mvp_link',
    'is_active',
])]
class MvpSetting extends Model
{
    protected function casts(): array
    {
        return [
            'version' => MvpSettingVersion::class,
            'is_active' => 'boolean',
        ];
    }
}
