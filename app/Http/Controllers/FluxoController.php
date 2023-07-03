<?php

namespace App\Http\Controllers;

use App\Http\Models\FluxoModel;
use App\Http\Models\Vendas;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\View\Factory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Response;
use Illuminate\View\View;
use NumberFormatter;
use Throwable;
use App\Http\Models\Usuario;

class FluxoController extends Controller
{

    protected $request,$flux, $vendas, $formatter;

    public function __construct(Request $request, FluxoModel $fluxo, Vendas $vendas){

        $this->request = $request;
        $this->flux = $fluxo;
        $this->vendas = $vendas;
        $this->formatter = new NumberFormatter('pt_BR',  NumberFormatter::CURRENCY);
    }

    /**
     * Display a listing of the resource.
     *
     * @return Application|Factory|View
     */
    public function index()
    {
      //  dd( $fluxo = $this->fluxo::all());

        //$flux = $this->flux->orderBy('created_at', 'DESC')->get();
		$user_data = Usuario::where("user_id",auth()->user()->id)->first();
		
        return view('admin.fluxo', compact('user_data'));
        //return view('admin.fluxo');
    }

    /**
     * Show the form for creating a new resource.
     *
     * @param int $ano
     * @return JsonResponse
     */
    public function chart(int $ano)
    {
       // dd($ano);
        DB::statement("SET lc_time_names = 'pt_BR'");
        $ret['receitas'] = $this->vendas:: leftjoin('loja_vendas_produtos_tipo_pagamentos as tp', 'tp.venda_id', '=', 'loja_vendas.id')
                                ->leftjoin('loja_forma_pagamentos as fp', 'tp.forma_pagamento_id', '=', 'fp.id')
                                ->leftjoin('loja_taxa_cartoes as lc', 'lc.forma_id', '=', 'fp.id')
            ->select(
                DB::raw('MONTHNAME(loja_vendas.created_at) As nome_mes'),
                DB::raw('DATE_FORMAT(loja_vendas.created_at, "%m") as mes'),
                DB::raw('DATE_FORMAT(loja_vendas.created_at, "%Y") as ano'),
                DB::raw("(SUM(tp.valor_pgto - (tp.valor_pgto * tp.taxa/100))) AS receita")
            )->where(DB::raw('DATE_FORMAT(loja_vendas.created_at, "%Y")'),$ano)
            ->groupBy(DB::raw('DATE_FORMAT(loja_vendas.created_at, "%Y-%m")'))
            ->get();

        $ret['despesas'] =  FluxoModel::select(
                DB::raw('MONTHNAME(created_at) As nome_mes'),
                DB::raw('DATE_FORMAT(created_at, "%m") as mes'),
                DB::raw('DATE_FORMAT(created_at, "%Y") as ano'),
                //DB::raw("FORMAT(SUM(valor_caixa),2, 'de_DE') as despesa")
                DB::raw("(SUM(valor_caixa + valor_sangria)) as despesa")
        )->where(DB::raw('DATE_FORMAT(created_at, "%Y")'),$ano)
            ->where('loja_id', 2)
            ->groupBy(DB::raw('DATE_FORMAT(created_at, "%Y-%m")'))
            ->get();

        return Response::json(array('success' => true, 'data' => $ret), 200);
        // ->where(DB::raw('DATE_FORMAT(loja_vendas.created_at, "%Y-%m")'),'2022-01')
    }

    /**
     * Store a newly created resource in storage.
     *
     * @return JsonResponse
     */
    public function store()
    {
        try {



        } catch (Throwable $e) {
            return Response::json(['error' => $e], 500);
        }

    }

    /**
     * Display the specified resource.
     *
     * @param int $id
     * @return JsonResponse
     */
    public function show(int $id)
    {
      // dd($id);
        try {

            foreach ($this->flux::all()->where("loja_id",$id) as $value){

                //Busaca o total do DIA
                $sales = $this->vendas::
                leftjoin('loja_vendas_produtos_tipo_pagamentos as tp', 'tp.venda_id', '=', 'loja_vendas.id')
                    ->leftjoin('loja_forma_pagamentos as fp', 'tp.forma_pagamento_id', '=', 'fp.id')
                    ->leftjoin('loja_taxa_cartoes as lc', 'lc.forma_id', '=', 'fp.id')
                    ->select(
                        "fp.nome as name",
                        "fp.id as id_payment",
                        // (DB::raw("FORMAT(SUM(tp.valor_pgto - (tp.valor_pgto * tp.taxa/100)),2) AS orderTotal")))
                        (DB::raw("SUM(tp.valor_pgto - (tp.valor_pgto * tp.taxa/100)) AS orderTotal")))
                    ->where('loja_vendas.loja_id', $id)
                    ->where('fp.id', 1)
                    //->whereDate('loja_vendas.created_at', Carbon::now()->subDay('1'))
                    // ->whereDate('loja_vendas.created_at', Carbon::today())
                    ->whereDate('loja_vendas.created_at', $value->created_at)
                    ->groupBy('fp.id')->first();

                if($sales) {
                  //  $retorno['orderTotal'] = $vendas->orderTotal
                    $retorno['total_caixa'] =  $this->formatter->formatCurrency($sales->orderTotal,'BRL');
                } else {
                    //$retorno['orderTotal'] = 0;
                    $retorno['total_caixa'] =  $this->formatter->formatCurrency(0,'BRL');
                }
                //Fim totaL DIA

                //Monta o retorno para a tabela de fluxo caixa
                $retorno['id'] =  $value->id;
                $retorno['valor_caixa'] =  $this->formatter->formatCurrency($value->valor_caixa, 'BRL') ;
                $retorno['valor_sangria'] =  $this->formatter->formatCurrency($value->valor_sangria, 'BRL') ;
                $retorno['descricao'] =  $value->descricao;
                $retorno['loja_id'] =  $value->loja_id == 1 ? "FEIRA" : "BARÃO";
                $retorno['confirme'] =  $value->confirme;

                $retorno['created_at'] =  date('d/m/Y H:i:s', strtotime($value->created_at));
                $retorno['updated_at'] =  date('d/m/Y H:i:s', strtotime($value->updated_at));

                $saida[] = $retorno;
            }

            if(!empty($saida)) {
                return Response()->json($saida);
            }  else {
                return Response()->json(array('data'=>''));
            }

        } catch (Throwable $e) {
            return Response::json(['error' => $e], 500);
        }
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param int $ano
     * @return JsonResponse
     */
    public function edit(int $ano)
    {
        try {
            $ret['year'] = $this->vendas:: leftjoin('loja_vendas_produtos_tipo_pagamentos as tp', 'tp.venda_id', '=', 'loja_vendas.id')
                ->leftjoin('loja_forma_pagamentos as fp', 'tp.forma_pagamento_id', '=', 'fp.id')
                ->leftjoin('loja_taxa_cartoes as lc', 'lc.forma_id', '=', 'fp.id')
                ->select(
                    DB::raw("FORMAT(SUM(tp.valor_pgto - (tp.valor_pgto * tp.taxa/100)),2,'de_DE') AS total_receita")
                )->where(DB::raw('DATE_FORMAT(loja_vendas.created_at, "%Y")'),$ano)
                ->groupBy(DB::raw('DATE_FORMAT(loja_vendas.created_at, "%Y")'))
                ->first();

            $ret['years'] = $this->vendas:: leftjoin('loja_vendas_produtos_tipo_pagamentos as tp', 'tp.venda_id', '=', 'loja_vendas.id')
                ->leftjoin('loja_forma_pagamentos as fp', 'tp.forma_pagamento_id', '=', 'fp.id')
                ->leftjoin('loja_taxa_cartoes as lc', 'lc.forma_id', '=', 'fp.id')
                ->select(
                    DB::raw(DB::raw('DATE_FORMAT(loja_vendas.created_at, "%Y") as ano')),
                    DB::raw("SUM(tp.valor_pgto - (tp.valor_pgto * tp.taxa/100)) AS total_receita")
                   // DB::raw("FORMAT(SUM(tp.valor_pgto - (tp.valor_pgto * tp.taxa/100)),2,'de_DE') AS total_receita")
                )->groupBy(DB::raw('DATE_FORMAT(loja_vendas.created_at, "%Y")'))
                ->get();


            return Response::json(array('success' => true, 'data' => $ret), 200);
        } catch (Throwable $e) {
            return Response::json(['error' => $e], 500);
        }
    }

    /**
     * Update the specified resource in storage.
     *
     * @param int $int
     * @return JsonResponse
     */
    public function update(int $int)
    {
        try {
           // dd($int);

            $flux =  $this->flux::find($int);
            $flux->confirme = true;

            $flux->save();

            return Response::json(array('success' => true, 'message' => 'Fluxo atualizada com sucesso!'), 200);

        } catch (Throwable $e) {
            //return Response::json(['error' => $e], 500);
            return Response::json(array('success' => false, 'message' => $e->getMessage()), 500);

        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param FluxoModel $fluxo
     * @return void
     */
    public function destroy(FluxoModel $fluxo)
    {
        //
    }
}
