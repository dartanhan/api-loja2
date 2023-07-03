<?php

namespace App\Http\Models;

use Illuminate\Database\Eloquent\Model;

class LogVendas extends Model
{
    public $table = 'loja_logvendas';
    protected $fillable = ['json','created_at','updated_at'];

}
