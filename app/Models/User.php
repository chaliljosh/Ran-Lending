<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class User extends Model
{
    protected $table = 'users';
    protected $primaryKey = 'user_id';
    public $timestamps = false;

    protected $fillable = [
        'name', 'first_name', 'last_name', 'middle_name',
        'birthday', 'email', 'password_hash', 'role', 'is_active',
    ];

    protected $hidden = ['password_hash'];
}