<?php

namespace App\Http\Controllers;

use App\Http\Models\Categoria;
use App\Http\Models\Vendas;
use App\Http\Models\VendasProdutos;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Response;
use Throwable;
use Yajra\DataTables\DataTables;
use App\Http\Models\Usuario;


class ProductBestSellersController extends Controller
{

    protected $request,$sales,$salesProduct,$cat;

    public function __construct(Request $request, Vendas $sales, VendasProdutos $salesProduct, Categoria $cat){
        $this->request = $request;
        $this->sales = $sales;
        $this->salesProduct = $salesProduct;
        $this->cat = $cat;
    }
    /**
     * Display a listing of the resource.
     *
     */
    public function index()
    {
		$user_data = Usuario::where("user_id",auth()->user()->id)->first();
		
        return view('admin.produto_mais_vendidos',compact('user_data'));
        
    }

    /**
     * Show the form for creating a new resource.
     *
     * @param $data
     * @return JsonResponse
     */

    public function cards($data){

        try{
            $dataCarbon = ($data == "") ?  CarbonImmutable::parse(CarbonImmutable::now()->format("Y-m")) : $data;
           // $date =  Date("Y-m");
            //  $datefim = Date("Y-m-t");

            $ret = $this->salesProduct
                ->Join('loja_categorias as c','c.id','=' ,'loja_vendas_produtos.categoria_id')
                ->join('loja_vendas as v', 'v.id', '=','loja_vendas_produtos.venda_id')
                ->join('loja_vendas_produtos_descontos as d','d.venda_id','=' ,'v.id')
                ->join('loja_vendas_produtos_tipo_pagamentos as tp','tp.venda_id','=' ,'v.id')
                ->join('loja_forma_pagamentos as forma','tp.forma_pagamento_id','=' ,'forma.id')
                ->join('loja_taxa_cartoes as taxa','taxa.forma_id','=' ,'forma.id')
				->join('loja_produtos_variacao as pv','pv.subcodigo','=','loja_vendas_produtos.codigo_produto')
                ->select(
                    'c.id',
                    'c.nome',
                    DB::raw("SUM(pv.valor_produto * loja_vendas_produtos.quantidade) as total_custo"),
                    DB::raw("(sum(loja_vendas_produtos.valor_produto
                            * loja_vendas_produtos.quantidade)-valor_desconto)-(((sum( loja_vendas_produtos.valor_produto
                            * loja_vendas_produtos.quantidade)-valor_desconto) * taxa.valor_taxa)/100) as total")
                )
                ->where('c.status',1) //somente categorias ativas
                //->whereBetween(DB::raw('DATE(loja_vendas_produtos.created_at)'), array($dateini, $datefim))
                ->where(DB::raw('DATE_FORMAT(loja_vendas_produtos.created_at, "%Y-%m")'),$dataCarbon)
                //->groupBy((DB::raw('DATE_FORMAT(loja_vendas.created_at, "%Y-%m")')))
                ->groupBy('loja_vendas_produtos.categoria_id')
                ->get();

        }catch (Throwable $e) {
            return Response::json(array('success' => false, 'message' => $e->getMessage() ), 500);
        }

        return Response::json(array('success' => true, 'cards' =>$ret),200);

    }
    public function create()
    {

    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return JsonResponse
     */
    public function store(Request $request)
    {
       // dd($this->request->dateini);

        try{
            //$dataCarbon = CarbonImmutable::parse(CarbonImmutable::now()->format("Y-m-d")); // use en_US as default locale
            //Mês
            if($request->dateini == ""){
                $year =  Date("Y");
                $month = Date("m");

            }else{
                $dataini = explode("/",$request->dateini );

                //$dateini = $dataini[1]."-".$dataini[0];
                $year =  $dataini[1];
                $month = $dataini[0];
            }

            $sales =  $this->sales::Join('loja_vendas_produtos as vp','vp.venda_id','=' ,'loja_vendas.id')
                ->Join('loja_produtos as p','p.codigo_produto','=' ,'vp.codigo_produto')
                ->select(
                    DB::raw('DATE_FORMAT(loja_vendas.created_at, "%m/%Y") as data'),
                    DB::raw('DATE_FORMAT(loja_vendas.created_at, "%Y-%m") as dataSort'),
                    DB::raw('SUM(p.valor_produto) AS total,sum(vp.quantidade) AS quantidade'),
                    'vp.descricao',
                    'vp.codigo_produto',
                    'p.valor_produto'
                )
                ->where('loja_id', 2)
                //->whereYear('loja_vendas.created_at', '=', $dataCarbon->year)
                //->whereMonth('loja_vendas.created_at', '=',$dataCarbon->month)
                ->whereYear('loja_vendas.created_at', '=', $year)
                ->whereMonth('loja_vendas.created_at', '=',$month)
                //->whereBetween(DB::raw('DATE(loja_vendas.created_at)'), array($dateini, $datefim))
                ->groupBy(DB::raw('DATE_FORMAT(loja_vendas.created_at, "%Y-%m")'),'vp.codigo_produto', 'vp.descricao')
                ->orderBy(DB::raw('DATE_FORMAT(loja_vendas.created_at, "%Y-%m")'), 'DESC')
                ->orderBy('quantidade', 'DESC');

            return  DataTables::of($sales)->make(true);

        }catch (Throwable $e) {
            return Response::json(array('success' => false, 'message' => $e->getMessage() ), 500);
        }
    }

    /**
     * Display the specified resource.
     *
     * @param int $categories_id
     * @param $date
     * @return JsonResponse
     */
    public function details(int $categories_id, $date)
    {
        try {
           // dd($categoria_id);
           // $date =  Date("Y-m");
           $ret =  $this->salesProduct
               ->join('loja_produtos_variacao as lpv', 'loja_vendas_produtos.codigo_produto','=', 'lpv.subcodigo')
               ->select(
                        "descricao",
                        "lpv.valor_produto",
                        DB::raw("sum(loja_vendas_produtos.quantidade) as quantidade"),
                        DB::raw('SUM(lpv.valor_produto * loja_vendas_produtos.quantidade) AS total_produto'),
                        DB::raw('SUM(loja_vendas_produtos.valor_produto * loja_vendas_produtos.quantidade) AS total_venda'),
                        DB::raw('SUM(loja_vendas_produtos.valor_produto * loja_vendas_produtos.quantidade) - SUM(lpv.valor_produto * loja_vendas_produtos.quantidade) as lucro'),
                        DB::raw('DATE_FORMAT(loja_vendas_produtos.created_at, "%m/%Y") as data')
               )//->skip(0)
              // ->take(50)
               ->where('categoria_id', $categories_id)
               ->where(DB::raw('DATE_FORMAT(loja_vendas_produtos.created_at, "%Y-%m")'),$date)
               ->groupBy("descricao")
               ->orderBy("quantidade","desc")
               ->get();

           return  DataTables::of($ret)->make(true);

        }catch (Throwable $e) {
            return Response::json(array('success' => false, 'message' => $e->getMessage() ), 500);
        }
    }
	
	  public function detailsCost(int $categories_id, $date)
    {
        try {
            // dd($categoria_id);
            // $date =  Date("Y-m");
            $ret =  $this->salesProduct
                ->join('loja_produtos_variacao as lpv', 'loja_vendas_produtos.codigo_produto','=', 'lpv.subcodigo')
                ->select(
                    "descricao",
                    "lpv.valor_produto",
                    DB::raw("sum(loja_vendas_produtos.quantidade) as quantidade"),
                    DB::raw('SUM(lpv.valor_produto * loja_vendas_produtos.quantidade) AS total_produto'),
                    //DB::raw('SUM(loja_vendas_produtos.valor_produto * loja_vendas_produtos.quantidade) AS total_venda'),
                    //DB::raw('SUM(loja_vendas_produtos.valor_produto * loja_vendas_produtos.quantidade) - SUM(lpv.valor_produto * loja_vendas_produtos.quantidade) as lucro'),
                    DB::raw('DATE_FORMAT(loja_vendas_produtos.created_at, "%m/%Y") as data')
                )
                ->where('categoria_id', $categories_id)
                ->where(DB::raw('DATE_FORMAT(loja_vendas_produtos.created_at, "%Y-%m")'),$date)
                ->groupBy("descricao")
                ->orderBy("quantidade","desc")
                ->get();

            return  DataTables::of($ret)->make(true);

        }catch (Throwable $e) {
            return Response::json(array('success' => false, 'message' => $e->getMessage() ), 500);
        }
    }


    /**
     * Show the form for editing the specified resource.
     *
     * @param $date
     * @return JsonResponse
     */
    public function edit($date)
    {
        try {

            $ret =  $this->salesProduct
                ->join('loja_produtos_variacao as lpv', 'loja_vendas_produtos.codigo_produto','=', 'lpv.subcodigo')
                ->join('loja_produtos_new as pn', 'lpv.products_id','=', 'pn.id')
                ->select(
                    "pn.id",
                    "pn.descricao",
                    "pn.codigo_produto",
                    DB::raw("sum(loja_vendas_produtos.quantidade) as quantidade"),
                    DB::raw('DATE_FORMAT(loja_vendas_produtos.created_at, "%m/%Y") as data')
                )
                ->where(DB::raw('DATE_FORMAT(loja_vendas_produtos.created_at, "%Y-%m")'),$date)
                ->groupBy("pn.descricao","pn.codigo_produto")
                ->orderBy("quantidade","desc")
                ->get();

            return  DataTables::of($ret)->make(true);


        }catch (Throwable $e){
            return Response::json(array('success' => false, 'message' => $e->getMessage() ), 500);
        }

    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }


    /***
     *
     * @param int $codigo_produto
     * @param $date
     * @return JsonResponse
     */
    public function getListProductsSales(int $codigo_produto, $date){
        try {
            $return = [];
            $newArray = null;

            /**
            *Pega data passada e busca 3 meses antes
             */
            $date3m = CarbonImmutable::parse($date)->startOfMonth()->subMonth(3)->format('Y-m');

            $ret =  $this->salesProduct
                ->join('loja_produtos_variacao as lpv', 'loja_vendas_produtos.codigo_produto','=', 'lpv.subcodigo')
                ->join('loja_produtos_new as pn', 'lpv.products_id','=', 'pn.id')
                ->select("pn.id",
                    "loja_vendas_produtos.codigo_produto",
                    "loja_vendas_produtos.descricao",
                    "lpv.quantidade",
                    DB::raw("sum(loja_vendas_produtos.quantidade) as qtd_vendidos"),
                    DB::raw('DATE_FORMAT(loja_vendas_produtos.created_at, "%Y-%m") as data'),
                    DB::raw("sum(loja_vendas_produtos.quantidade * loja_vendas_produtos.valor_produto) as valor_vendido"),
                    DB::raw("sum(loja_vendas_produtos.quantidade * lpv.valor_produto) as valor_produto"),
                    DB::raw("sum(loja_vendas_produtos.quantidade * loja_vendas_produtos.valor_produto) - sum(loja_vendas_produtos.quantidade * lpv.valor_produto) as valor_lucro")
                    /*DB::raw("(SELECT SUM(p.quantidade)
                                       FROM loja_vendas_produtos AS p
                                       JOIN loja_produtos_variacao AS v ON ( v.subcodigo = p.codigo_produto )
                                       JOIN loja_produtos_new AS n ON ( n.id = v.products_id)
                                           WHERE
                                            v.subcodigo = 100002 and
                                             DATE_FORMAT(p.created_at, '%Y-%m') BETWEEN '2022-06' AND '2022-09'
                                    GROUP BY p.codigo_produto) as qtd_3m")*/


                )->where('pn.codigo_produto',$codigo_produto)
                    ->where(DB::raw('DATE_FORMAT(loja_vendas_produtos.created_at, "%Y-%m")'),$date)
                ->groupBy("loja_vendas_produtos.codigo_produto")
                ->get();


            foreach ($ret as $value){
                $newArray['codigo_produto'] =  $value->codigo_produto;
                $newArray['descricao'] =  $value->descricao;
                $newArray['quantidade'] =  $value->quantidade;
                $newArray['qtd_vendidos'] =  $value->qtd_vendidos;
                $newArray['data'] =  $value->data;
                $newArray['valor_vendido'] =  $value->valor_vendido;
                $newArray['valor_produto'] =  $value->valor_produto;
                $newArray['valor_lucro'] =  $value->valor_lucro;
                $newArray['qtd_3m'] = $this->getQtd3mSales($value->codigo_produto,$date3m,$date);


                $return[] = $newArray;
            }

            return  DataTables::of($return)->make(true);

        } catch (Throwable $e) {
            return Response::json(['error' => $e->getMessage()], 500);
        }
        //return Response::json(array('success' => true, "data" => $ret), 200);

    }

    /***
     *  Retorna valor único total de vendas do produto no ultimo 3 meses referente a data atual
     * @param $codigo_produto
     * @param $date3m
     * @param $date
     * @return JsonResponse
     */
    public function getQtd3mSales($codigo_produto,$date3m,$date){

        try{
            $ret =  $this->salesProduct
                    ->select(
                            DB::raw("SUM(quantidade) AS qtd_3m"
                        )
                    )->where('codigo_produto', $codigo_produto)
                        ->whereBetween(DB::raw('DATE_FORMAT(created_at, \'%Y-%m\')'), array($date3m, $date))
                        ->groupBy('codigo_produto')->first();


        } catch (Throwable $e) {
            return Response::json(['error' => $e->getMessage()], 500);
        }
        return $ret->qtd_3m;
    }
    public function getDataAttribute($value)
    {
        //return CarbonImmutable::parse($value)->format('Y-m-d');
        return Carbon::createFromFormat('d/m/Y',$value)->format('Y-m-d');
    }
}
