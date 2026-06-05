<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Contact extends Model
{
    use HasFactory;

    protected $fillable = ['user_id', 'name', 'phone'];

    public function user()
    {
        return $this->belongsTo('App\Models\User');
    }

    public function schedulecontacts()
    {
        return $this->hasMany('App\Models\Schedulecontact');
    }


    public function groupcontacts()
    {
        return $this->hasMany(Groupcontact::class);
    }

    public function groupcontact()
    {
        return $this->belongsToMany(Group::class,'groupcontacts');
    }
}
