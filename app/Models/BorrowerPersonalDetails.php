<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BorrowerPersonalDetails extends Model
{
    protected $table = 'borrowerpersonaldetails';
    protected $primaryKey = 'detail_id';
    public $timestamps = false;

    protected $fillable = [
        'user_id', 'last_name', 'first_name', 'middle_name',
        'suffix', 'birthday', 'gender', 'civil_status',
        'citizenship', 'birth_country', 'birth_province',
        'birth_city', 'cellphone_num', 'id_type', 'id_number',
        'id_file_path', 'monthly_income', 'email', 'status',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }
}