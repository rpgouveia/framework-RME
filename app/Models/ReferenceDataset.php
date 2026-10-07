<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

/**
 * The metadata of a versioned reference file as it was loaded, such as the
 * mitigation catalogue. Kept in the database so the app describes the data it
 * actually holds, even if the file changed since.
 *
 * @property int $id
 * @property string $key
 * @property array<string, mixed> $metadata
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 */
#[Fillable(['key', 'metadata'])]
class ReferenceDataset extends Model
{
    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'metadata' => 'array',
        ];
    }
}
