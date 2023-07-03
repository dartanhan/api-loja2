<?php
namespace App\Http\Models;

use Illuminate\Database\Eloquent\Model;

class VendasProdutos extends Model
{
    public $table = 'loja_vendas_produtos';
    public $timestamps = false;
    protected $fillable = ['venda_id','codigo_produto','descricao','valor_produto','quantidade'];

    public function vendas() {
        return $this->belongsTo('App\Http\Models\Vendas');
    }

}
