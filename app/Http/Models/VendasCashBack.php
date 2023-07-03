<?php
namespace App\Http\Models;

use Illuminate\Database\Eloquent\Model;

class VendasCashBack extends Model
{
    public $table = 'loja_vendas_cashback';
    protected $fillable = ['cliente_id','venda_id','valor','created_at','update_at'];

}
