<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Trace extends Model
{
    protected $table = 'traces';
    protected $guarded = [];
    public $timestamps = false;

    public function device(){
        return $this->belongsTo(Device::class, 'imei', 'imei');
    }
}
