<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class User extends Model
{
    protected $table = 'users';
    protected $guarded = [];
    protected $hidden = [
        'password',
        'validation_code',
        'recovery_code'
    ];
    public $timestamps = false;

    public function devices() {
        return $this->hasMany(Device::class);
    }

    public function geofences() {
        return $this->hasMany(Geofence::class);
    }

    public function groups() {
        return $this->hasMany(Group::class);
    }

    public function suggestions() {
        return $this->hasMany(Suggestion::class);
    }
}
