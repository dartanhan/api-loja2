<?php

namespace App\Http\Models;

use Illuminate\Database\Eloquent\Model;

class VendasProdutosDesconto extends Model
{
    public $table = 'loja_vendas_produtos_descontos';
    public $timestamps = false;
    protected $fillable = ['venda_id','valor_desconto','valor_recebido','valor_percentual'];


}
