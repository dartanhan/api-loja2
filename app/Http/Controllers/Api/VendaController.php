<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Models\Cashback;
use App\Http\Models\LogVendas;
use App\Http\Models\Produto;
use App\Http\Models\ProdutoQuantidade;
use App\Http\Models\TaxaCartao;
use App\Http\Models\Vendas;
use App\Http\Models\VendasCashBack;
use App\Http\Models\VendasProdutos;
use App\Http\Models\ProdutoVariation;
use App\Http\Models\VendasProdutosDesconto;
use App\Http\Models\VendasProdutosTipoPagamento;
use App\Http\Models\VendasProdutosValorCartao;
use App\Http\Models\VendasProdutosValorDupla;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Storage;
use Throwable;

class VendaController extends Controller
{
    protected  $request, $product, $vendas,$vendasProdutos, $vendasDescontos,$productVariation,
                        $tipoPagamento,$valorCartao, $valorDuplo,$produtoQuantidade,$taxaCartao,
                        $cashback_vendas, $cashback,$logVendas;

	
    public function __construct(Request $request,
                                    Produto $product,
                                    Vendas $vendas,
                                    VendasProdutos $vendasProdutos,
                                    VendasProdutosDesconto $vendasDescontos,
                                    VendasProdutosTipoPagamento $tipoPagamento,
                                    VendasProdutosValorCartao $valorCartao,
                                    VendasProdutosValorDupla $valorDuplo,
                                    ProdutoQuantidade $produtoQuantidade,
                                    TaxaCartao $taxaCartao,
									ProdutoVariation $productVariation,
									VendasCashBack $cashback_vendas,
                                    Cashback $cashback,
                                    LogVendas $logVendas){
         $this->request = $request;
         $this->product = $product;
         $this->vendas = $vendas;
         $this->vendasProdutos = $vendasProdutos;
         $this->vendasDescontos = $vendasDescontos;
         $this->tipoPagamento = $tipoPagamento;
         $this->valorCartao = $valorCartao;
         $this->valorDuplo  = $valorDuplo;
         $this->produtoQuantidade  = $produtoQuantidade;
         $this->taxaCartao = $taxaCartao;
		 $this->productVariation = $productVariation;
		 $this->cashback_vendas = $cashback_vendas;
         $this->cashback = $cashback;	
         $this->logVendas =  $logVendas;								   
    }

    /**
     * Display a listing of the resource.
     *
     * @return JsonResponse
     */
     public function index()
    {
        //dd($this->request->header('product-code'));
        $codigo_produto = $this->request->header('product-code');
        //$codigo_loja = $this->request->header('store-id');  
        $tipo_pgto = intval($this->request->header('tipo-id'));

            $variations = DB::table('loja_produtos_variacao')
                ->leftJoin('loja_produtos_imagens', 'loja_produtos_imagens.produto_variacao_id', '=', 'loja_produtos_variacao.id')
                ->where('loja_produtos_variacao.subcodigo', '=', $codigo_produto)->first();

            if ($variations) {
                if ($variations->quantidade == 0) {
                    return Response::json(array('success' => false, 'message' => 'Produto sem estoque para venda!'),
                        201, [], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
                } else if ($variations->status == 0) {
                    return Response::json(array('success' => false, 'message' => 'Produto Bloqueado para venda!'),
                        201, [], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
                }

                $products = DB::table('loja_produtos_new')->where('loja_produtos_new.id', '=', $variations->products_id)->first();

                if ($products) {
                    if ($products->status == 0) {
                        return Response::json(array('success' => false, 'message' => 'Produto Bloqueado para venda!'), 201, [], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
                    } else {
                        $data['success'] = true;
                        $data['id'] = $products->id;
                        $data['codigo_produto'] = $codigo_produto;
                        $data['descricao'] = $products->descricao . " - " . $variations->variacao;
                        //$data['variacao'] = $variations->variacao;
                        $data['status'] = $variations->status;
                        //$data['fornecedor_id'] = $products->fornecedor_id;
                        $data['fornecedor_id'] = $variations->fornecedor;
                        $data['categoria_id'] = $products->categoria_id;
                        $data['id_forma_pgto'] = $tipo_pgto;
                        $data['quantidade'] = 1;
                        $data['valor_produto'] = $variations->valor_produto;
                        $data['valor_atacado'] = $variations->valor_atacado;
                        $data['valor_varejo'] = $variations->valor_varejo;
                        $data['valor_venda'] = $variations->valor_varejo;
						$data['valor_atacado_5un'] = $variations->valor_atacado_5un;
                        $data['valor_atacado_10un'] = $variations->valor_atacado_10un;
                        $data['valor_lista'] = $variations->valor_lista;											
                        $data['percentual'] = $variations->percentage;
                        $data['qtdestoque'] = $variations->quantidade;
                        $data['loja_id'] = 2;
                        $data['troca'] = false;
                        $data['path'] = $variations->path;
												
						$storage = $this->request->getHttpHost() === 'administracao.knesmalteria.com.br' ?
                                                                    'https://'.$this->request->getHttpHost()."/public/storage/"  :
                                                                    'http://'.$this->request->getHttpHost()."/api-loja/public/storage/"  ;

                        $result =  Storage::exists($variations->path);

                        if ($result) {
                            $data['path_image'] = $storage.$variations->path;
                        } else {
                            $data['path_image'] = $storage."produtos/not-image.png";
                        }
						
                        return Response::json($data, 200, [], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
                    }
                }
            } else {
                return Response::json(array('success' => false, 'message' => 'Produto não encontrado!'), 201, [], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
            }
	}
    /**
     * Show the form for creating a new resource.
     *
     * @return JsonResponse
     */
    public function create()
    {

        try {
            $pro = [];
            $code_store = $this->request->header('store-id');

            /**
            PEGA A ÚLTIMA VENDA
             */
            $store =  DB::table('loja_vendas')->where('loja_id',$code_store)->orderBy('id', 'DESC')->first();

            if( $store != null) {

                $total_store = $store->valor_total;
                $code_store = $store->codigo_venda;

                $store = DB::table('loja_vendas')
                    ->join('loja_vendas_produtos', 'loja_vendas.id', '=', 'loja_vendas_produtos.venda_id')
                    ->where('loja_vendas.codigo_venda', '=', $code_store)
                    ->select('loja_vendas.*', 'loja_vendas_produtos.quantidade',
                        'loja_vendas_produtos.descricao',
                        'loja_vendas_produtos.codigo_produto',
                        'loja_vendas_produtos.valor_produto')->get();

                if (count($store) > 0) {
                    //Desconto
                    $discount = DB::table('loja_vendas')
                        ->join('loja_vendas_produtos_descontos', 'loja_vendas.id', '=', 'loja_vendas_produtos_descontos.venda_id')
                        ->where('loja_vendas.codigo_venda', '=', $code_store)
                        ->select('loja_vendas_produtos_descontos.valor_desconto',
                            'loja_vendas_produtos_descontos.valor_percentual',
                            'loja_vendas_produtos_descontos.valor_recebido',
                            DB::raw('loja_vendas_produtos_descontos.valor_recebido - loja_vendas.valor_total as valor_troco'),
                            DB::raw('loja_vendas.valor_total + loja_vendas_produtos_descontos.valor_desconto as sub_total'))->first();

                    //Forma de pagamento
                    $payment = DB::table('loja_vendas')
                        ->join('loja_vendas_produtos_tipo_pagamentos', 'loja_vendas.id', '=', 'loja_vendas_produtos_tipo_pagamentos.venda_id')
                        ->join('loja_forma_pagamentos', 'loja_forma_pagamentos.id', '=', 'loja_vendas_produtos_tipo_pagamentos.forma_pagamento_id')
                        ->where('loja_vendas.codigo_venda', '=', $code_store)
                        //->select('loja_forma_pagamentos.id','loja_forma_pagamentos.nome')->first();
                        ->select('loja_forma_pagamentos.id', 'loja_forma_pagamentos.nome')->get();

                    // dd($payment);
                    $pro["produtos"] = $store;
                    $pro["percentual"] = $discount->valor_percentual;
                    $pro["valor_desconto"] = $discount->valor_desconto;
                    $pro["valor_recebido"] = $discount->valor_recebido;
                    $pro["loja_id"] = $store[0]->loja_id;
                    //$pro["tipo_pagamento"] = $payment->id;
                    $pro["listTipoPagamento"] = $payment;
                    $pro["codigo_venda"] = $code_store;
                    $pro["valor_total"] = $total_store;
                    $pro["valor_troco"] = $discount->valor_troco;
                    $pro["valor_sub_total"] = $discount->sub_total;

                    $pro["success"] = true;
                }
            }
            if ($store != null and count($store) > 0) {
                return Response::json($pro, 200,[],JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
            }else{
                return Response::json(array('success' => false, 'message' => 'Venda não localizada [ ' . $code_store . ' ]'), 400);
            }

        } catch (Throwable $e) {
            return Response::json(array('success' => false, 'message' => $e->getMessage(), 'code' => 500), 500);
        }
    }

    /**
     * Store a newly created resource in storage.
     * SALVA A VENDA
     * @return JsonResponse
     */
    public function store()
    {

        DB::beginTransaction();
        try {
            $dados  = $this->request->all();
            $affected = "";

            // Convert JSON string to Array
            //$dados = json_decode($dadosVendaJson['json']);

            //Salvo a venda
            $sale = $this->vendas->create(["codigo_venda" =>  $dados["codigo_venda"],
                                           "loja_id" =>  2,//$dados["loja_id"],
                                           "valor_total" =>  $dados["valor_total"],
                                            "cliente_id" =>  $dados["clienteModel"]["id"] !== 0 ? $dados["clienteModel"]["id"] : null,
                                            "tipo_venda_id" => $dados["tipoEntregaCliente"]]);

            //Pega o total de produtos no array
            $total = count($dados["produtos"]);
            $totalPayment = count($dados["listTipoPagamento"]);
            
            $this->logVendas = new LogVendas();
            $this->logVendas->json = json_encode($dados["produtos"]);
            $this->logVendas->save();

            //Salva os produtos da venda
            if ($sale->exists) {
                for ($i = 0; $i < $total; $i++) {
                    $this->vendasProdutos = new VendasProdutos();
                    $this->vendasProdutos->venda_id = $sale->id;
                    $this->vendasProdutos->codigo_produto = $dados["produtos"][$i]["codigo_produto"];
                    $this->vendasProdutos->descricao = $dados["produtos"][$i]["descricao"];
                    $this->vendasProdutos->valor_produto = $dados["produtos"][$i]["valor_produto"];
                    $this->vendasProdutos->quantidade = $dados["produtos"][$i]["quantidade"];
                    $this->vendasProdutos->troca = $dados["produtos"][$i]["troca"];
                    $this->vendasProdutos->fornecedor_id = $dados["produtos"][$i]["fornecedor_id"];
                    $this->vendasProdutos->categoria_id = $dados["produtos"][$i]["categoria_id"];
                    $this->vendasProdutos->save();
                }

                //Salva o desconto, valor percentual e valor recebido da venda
                $this->vendasDescontos = new VendasProdutosDesconto();
                $this->vendasDescontos->venda_id = $sale->id;
                $this->vendasDescontos->valor_desconto = $dados["valor_desconto"];
                $this->vendasDescontos->valor_recebido = $dados["valor_recebido"];
                $this->vendasDescontos->valor_percentual = $dados["percentual"];
                $this->vendasDescontos->save();

                //Salva o tipo de pagamento
                /*$this->tipoPagamento = new VendasProdutosTipoPagamento();
                $this->tipoPagamento->venda_id = $sale->id;
                $this->tipoPagamento->forma_pagamento_id = $dados["tipo_pagamento"];
                $this->tipoPagamento->save();*/

                //dd($totalPayment);
                for ($i = 0; $i < $totalPayment; $i++) {
                    $this->tipoPagamento = new VendasProdutosTipoPagamento();
                    $this->tipoPagamento->venda_id = $sale->id;
                    $this->tipoPagamento->forma_pagamento_id = $dados["listTipoPagamento"][$i]["id"];
                    $this->tipoPagamento->valor_pgto = $dados["listValorRecebido"][$i];
                    $this->tipoPagamento->taxa = $this->buscaTaxa($dados["listTipoPagamento"][$i]["id"]);
                    $this->tipoPagamento->save();
                }

                //Salva valor cartão
                //$this->valorCartao  = new VendasProdutosValorCartao();
                //$this->valorCartao->venda_id = $sale->id;
               // $this->valorCartao->valor_cartao = $dados["valor_cartao"];
              //  $this->valorCartao->save();

                //Salva valor venda dupla ou cartao apenas
               // $this->valorDuplo   = new VendasProdutosValorDupla();
              //  $this->valorDuplo->venda_id = $sale->id;
               // $this->valorDuplo->valor_cartao = $dados["valor_cartao"];
               // $this->valorDuplo->valor_dinheiro = $dados["valor_recebido"];
               // $this->valorDuplo->save();
            }

            //Realizar baixa do produto
            for ($i = 0; $i < $total; $i++) {
                $id = $dados["produtos"][$i]["id"]; // id do produto
                $sub_codigo = $dados["produtos"][$i]["codigo_produto"]; // id do produto
                //$loja_id = $dados["loja_id"]; //id da loja

                $product =  DB::table('loja_produtos_variacao')
                    ->where('products_id', '=', $id)
                    ->where('subcodigo', '=', $sub_codigo)
                    ->select('quantidade as qtdestoque')->first();

                if($dados["produtos"][$i]["troca"] === false){
                    $qtd = $product->qtdestoque - $dados["produtos"][$i]["quantidade"];
                }else{
                    $qtd = $product->qtdestoque + $dados["produtos"][$i]["quantidade"];
                }

                $affected = DB::table('loja_produtos_variacao')
                    ->where('products_id', '=',$id)
                    ->where('subcodigo', '=', $sub_codigo)
                    ->update(['quantidade' => $qtd]);

              /* $product =  DB::table('loja_produtos_quantidade')
                    ->where('loja_produtos_quantidade.produto_id', '=', $id)
                    ->where('loja_produtos_quantidade.loja_id', '=', $loja_id)
                    ->select('loja_produtos_quantidade.quantidade as qtdestoque')
                    ->first();*/

               //dd($dados["produtos"][$i]["troca"]);
               //dd($dados["produtos"][$i]["quantidade"]);
               /*if($dados["produtos"][$i]["troca"] === false){
                   $qtd = $product->qtdestoque - $dados["produtos"][$i]["quantidade"];
               }else{
                   $qtd = $product->qtdestoque + $dados["produtos"][$i]["quantidade"];
               }

                $affected = DB::table('loja_produtos_quantidade')
                    ->where('loja_produtos_quantidade.produto_id', '=',$id)
                    ->where('loja_produtos_quantidade.loja_id', '=', $loja_id)
                    ->update(['loja_produtos_quantidade.quantidade' => $qtd]);*/
            }
			 //Salva valor cashback
            if($sale->cliente_id) {
                $cashbacks = $this->cashback::all();
                $taxa = 0;
                foreach ($cashbacks as $valor) {
                    if ($valor->valor < $sale->valor_total) {
                        $taxa = $valor->taxa;
                    }
				}
                $valor_cashback = ($sale->valor_total * $taxa) / 100;
			
                $this->cashback_vendas = new VendasCashBack();
                $this->cashback_vendas->cliente_id = $sale->cliente_id;
                $this->cashback_vendas->venda_id = $sale->id;
                $this->cashback_vendas->valor = $valor_cashback;
				//DB::rollBack();
				
                $this->cashback_vendas->save();
            }
			
            if ($affected > 0) {
                DB::commit();
                return Response::json(array('success' => true), 200);
            }else{
                DB::rollBack();
                return Response::json(array('success' => false, 'message' => 'Ocorreu um erro no fechamento da venda!!'), 400);
            }

    } catch (Throwable $e) {
        DB::rollBack();
        return Response::json(array('success' => false, 'message' => $e->getMessage(), 'cod_retorno' => 500), 500);
    }

}

    /**
     * Display the specified resource.
     *
     * @param Vendas
     * @return JsonResponse
     */
    public function show()
    {
        $json = json_decode($this->request->header('json'));
      //  dd($json);

        $total = count($json);
        $ret = [];

        for ($i = 0; $i < $total; $i++) {
            $data = DB::table('loja_produtos_variacao')->where('loja_produtos_variacao.subcodigo', '=', $json[$i]->codigo_produto)->first();

            //$product = $this->produtoQuantidade::where('produto_id', '=', $data->id)->where('loja_id', '=', $json[$i]->loja_id)->first();

            //Quantidade vendida maior que estoque, erro
            if($json[$i]->quantidade > $data->quantidade){
                array_push($ret, [
                    'codigo_produto'   => $data->subcodigo,
                    'message'   =>  'Produto ['.$data->variacao .'] acima da quantidade em estoque, TOTAL [ '.$data->quantidade.' ]',
                ]);
            }
        }
        //$produtos = json_encode($saida);
        if(count($ret) > 0)
            return Response::json(array('success' => false,'produtos'=> $ret), 400);


        return Response::json(array('success' => true), 200);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @return void
     */
    public function edit()
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @return void
     */
    public function update()
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param Vendas $venda
     * @return JsonResponse ao passar o id da venda pega por injeção a venda
     * ao passar o id da venda pega por injeção a venda
     */
   public function destroy(int $id_venda)
    {
        try {
            //Pega a venda pelo codigo
            $vendas = $this->vendas::where('id',$id_venda)->first();
            if($vendas) {

                //Cria a model
                $this->vendas->id = $vendas->id;

                //retorna os dados da venda com o produto
                $quantityProduct = $this->vendas->quantityProduct()->get();

                //atualiza a tabela com as quatidades retornadas
                if ($quantityProduct) {
                    foreach ($quantityProduct as $key => $value) {
                        //Pega o produto para retornar o ID
                        //$product  = $this->product::where('codigo_produto', $quantityProduct[$key]['codigo_produto'])->first();

                        //Retorna produto da variação
                        $product = $this->productVariation::where('subcodigo', $quantityProduct[$key]['codigo_produto'])->first();

                        //Pega a quantidade em estoque e soma com a que foi vendida
                        $qtd = $product->quantidade + $value->quantidade;

                        $this->productVariation::where('subcodigo', $quantityProduct[$key]['codigo_produto'])
                            ->update(['quantidade' => $qtd]);

                        // dd($this->productVariation);
                        /* $productUpdate =  DB::table('loja_produtos_quantidade')
                             ->where('loja_produtos_quantidade.produto_id', '=', $product->id)
                             ->where('loja_produtos_quantidade.loja_id', '=', $venda->loja_id)
                             ->select('loja_produtos_quantidade.quantidade as qtdestoque')
                             ->first();

                         $qtd = $productUpdate->qtdestoque + $value->quantidade;
                         $affected = DB::table('loja_produtos_quantidade')
                             ->where('loja_produtos_quantidade.produto_id', '=',$product->id)
                             ->where('loja_produtos_quantidade.loja_id', '=', $venda->loja_id)
                             ->update(['loja_produtos_quantidade.quantidade' => $qtd]);*/
                    }
                }


                //$delete = $vendas->delete();

                if($vendas->delete())
                    return Response::json(array('success' => true, "message" => 'Venda deletada com sucesso!'), 200, [],JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

            }else{
                return Response::json(array('success' => false, "message" => 'Venda não localizada: [' . $cod_venda .']'), 400, [],JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
            }

        }catch (Throwable $e){
            return Response::json(array('success' => false, "message" => $e->getMessage()), 500, [],JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        }

    }
    //pega o valor da taxa e associa ao tipo de
    // pagmaento da venda, para no futuro alterar a taxa não influenciar nos relatórios
    function buscaTaxa(int $idPagamento){
        $retorno = $this->taxaCartao::select('valor_taxa')->where('forma_id', $idPagamento)->first();
        return $retorno->valor_taxa;
    }
}

