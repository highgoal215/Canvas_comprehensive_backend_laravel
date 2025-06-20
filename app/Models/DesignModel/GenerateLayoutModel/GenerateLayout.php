<?php

namespace App\Models\DesignModel\GenerateLayoutModel;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GenerateLayout extends Model
{
    use HasFactory;

    protected $fillable = [
        'content_type',
        'content_description',
        'style',
        'aspect_ratio',
        'layout_url',
        'user_id',
    ];
}
