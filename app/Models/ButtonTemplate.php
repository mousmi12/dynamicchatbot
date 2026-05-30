<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ButtonTemplate extends Model
{
    protected $fillable = [
        'company_id',
        'template_name',
        'context',
        'buttons',
        'priority',
        'is_active'
    ];

    protected $casts = [
        'buttons' => 'array',
        'is_active' => 'boolean'
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function getOrderedButtons()
    {
        $buttons = $this->buttons;
        usort($buttons, function ($a, $b) {
            return ($a['order'] ?? 0) <=> ($b['order'] ?? 0);
        });
        return $buttons;
    }
 
    // 🔥 Custom accessor - auto-decode when reading
    public function getButtonsAttribute($value)
    {
        if (is_array($value)) {
            return $value;
        }
        return json_decode($value, true) ?? [];
    }

    // 🔥 Custom mutator - proper encoding when saving
    public function setButtonsAttribute($value)
    {
        // If already JSON string, use as-is
        if (is_string($value)) {
            $this->attributes['buttons'] = $value;
        } 
        // If array, encode with Unicode support
        else if (is_array($value)) {
            $this->attributes['buttons'] = json_encode(
                $value, 
                JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
            );
        }
    }
}
