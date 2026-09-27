<?php

namespace App\Models;

use App\Models\Concerns\HasPublicUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Business extends Model
{
    use HasFactory, HasPublicUuid;

    protected $fillable = ['name', 'legal_name', 'registration_number', 'email', 'phone', 'alternate_phone', 'address_line_1', 'address_line_2', 'city', 'state', 'country', 'postal_code', 'logo_path', 'is_active'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function setting(): HasOne
    {
        return $this->hasOne(BusinessSetting::class);
    }
}
