<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UploadTestMediaFile extends Model
{
    use HasFactory;
    protected $table = 'media_files';
    protected $fillable = ['file_name','file_blob'];
}
