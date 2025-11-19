<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CompanyWorker extends Model
{
    protected $table = 'company_worker';
    
    protected $fillable = [
        'company_id',
        'name',
        'email',
        'role',
        'department',
        'supervisor',
    ];
    
    public $timestamps = true;
}
