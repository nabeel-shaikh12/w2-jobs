<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model; 
class ResumeDownload extends Model
{
    protected $fillable = ['user_id', 'resume_path', 'downloaded_at'];
}
