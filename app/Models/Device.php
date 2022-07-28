<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Device extends Model
{
    protected $table = 'devices';
    protected $guarded = [];
    public $timestamps = false;

    public function user(){
        return $this->belongsTo(User::class);
    }

    public function device_model(){
        return $this->belongsTo(DeviceModel::class);
    }

    public function location(){
        return $this->hasOne(Location::class, 'imei', 'imei');
    }

    public function last_traces(){
        return $this->hasMany(Trace::class, 'imei', 'imei')->limit(10);
    }

    public function groups(){
        return $this->belongsToMany(Group::class, 'groups_devices');
    }

    public function geofences(){
        return $this->belongsToMany(Group::class, 'geofences_devices');
    }
}
