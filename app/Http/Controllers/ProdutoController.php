<?php

namespace App\Http\Controllers;

use App\Http\Models\Categoria;
use App\Http\Models\Cor;
use App\Http\Models\Fornecedor;
use App\Http\Models\Produto;
use App\Http\Models\ProdutoImagem;
use App\Http\Models\ProdutoVariation;
use App\Http\Models\Usuario;
use Carbon\Carbon;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\View\Factory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Validator;
use Illuminate\View\View;
use NumberFormatter;
use Throwable;

class ProdutoController extends Controller
{
    protected $request,$produto,$fornecedor,$category,$cor,$produtoImage,$produtoVariation;

    public function __construct(Request $request, Produto $produto,Fornecedor $fornecedor, Categoria $category,
                                Cor $cor, ProdutoImagem $produto_image, ProdutoVariation $produtoVariation){
        $this->request = $request;
        $this->produto = $produto;
        $this->fornecedor = $fornecedor;
        $this->category = $category;
        $this->cor = $cor;
        $this->produtoImage = $produto_image;
        $this->produtoVariation = $produtoVariation;
    }

    /**
     * Display a listing of the resource.
     *
     * @return Application|Factory|View
     */
    public function index()
    {
        $suppliers = $this->fornecedor->where('status',true)->orderBy('nome', 'ASC')->get();
        $categories = $this->category->where('status',true)->orderBy('nome', 'ASC')->get();
        $cores = $this->cor->where('status',true)->orderBy('nome', 'ASC')->get();

		$user_data = Usuario::where("user_id",auth()->user()->id)->first();
            
        return view('admin.product', compact('suppliers','categories','cores','user_data'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return JsonResponse
     */
    public function create()
    {
        try {

            //$ret =  $this->produto::with('products')
            $ret =  $this->produto->leftJoin('loja_fornecedores','loja_produtos_new.fornecedor_id','=' ,'loja_fornecedores.id')
                ->leftJoin('loja_categorias','loja_produtos_new.categoria_id','=' ,'loja_categorias.id')
                //->leftJoin('loja_produtos_variacao','loja_produtos_new.id','=' ,'loja_produtos_variacao.products_id')
                ->select(
                    'loja_produtos_new.id',
                    'loja_produtos_new.codigo_produto',
                    'loja_produtos_new.descricao',
					'loja_categorias.nome as categoria',
                    'loja_produtos_new.status',
                    (DB::raw("DATE_FORMAT(loja_produtos_new.created_at, '%d/%m/%Y %H:%i:%s') as created")),
                    (DB::raw("DATE_FORMAT(loja_produtos_new.updated_at, '%d/%m/%Y %H:%i:%s') as updated"))
                    /*(DB::raw("FORMAT(loja_produtos_variacao.valor_varejo, 2) as valor_varejo")),

                    'loja_produtos_variacao.valor_atacado',
                    'loja_produtos_variacao.valor_produto',
                    'loja_produtos_variacao.subcodigo',
                    'loja_produtos_variacao.variacao',
                    'loja_produtos_variacao.quantidade',
                    'loja_produtos_variacao.quantidade_minima',
                    'loja_produtos_variacao.estoque',
                    'loja_produtos_variacao.percentage',
                    'loja_produtos_variacao.id as id_variacao',
                    'loja_produtos_variacao.subcodigo',
                    'loja_produtos_variacao.status as status_variacao',
                    (DB::raw("DATE_FORMAT(loja_produtos_variacao.validade, '%d/%m/%Y') as data_validade_variacao")),
                    (DB::raw('IF((loja_produtos_new.status = 1), \'ATIVO\', \'INATIVO\') as status_produto')),
                    'loja_fornecedores.nome as fornecedor',
                    'loja_categorias.nome as categoria',
                    'loja_fornecedores.id as fornecedor_id',
                    'loja_categorias.id as categoria_id'*/

                )->where('block',0)
                ->orderBy('loja_produtos_new.id', 'DESC')
                ->get();

            if(!empty($ret)) {
                return Response()->json($ret);
            }  else {
                return Response()->json(array('data'=>''));
            }

        } catch (Throwable $e) {
            return Response::json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Store a newly created resource in storage.
     *
     * @return JsonResponse
     */
    public function store()
    {
        try{
            //  dd($this->request->all());
            //   dd(count($this->request->allFiles()['images0']));
            if($this->request->input("id") == null){
				$msg = "Produto Cadastrado com sucesso!";								 
                $rules = [
                            'codigo_produto' => 'required|unique:'.$this->produto->table.'|max:15',
                            'descricao' => 'required|max:155',
                            //'fornecedor_id' => 'required|max:5',
                            'categoria_id' => 'required|max:5',
                    ];
            }else{
				$msg = "Produto Atualizado com sucesso!";										 
                $rules = [
                            'codigo_produto' => 'required|max:15|unique:'.$this->produto->table.',codigo_produto,'. $this->request->input("id"),
                            'descricao' => 'required|max:155',
                           // 'fornecedor_id' => 'required|max:5',
                            'categoria_id' => 'required|max:5',
                        ];
            }

            //Valida o form
            $validated = Validator::make($this->request->all(),$rules,$messages = [
                'codigo_produto.required'=> 'Código do produto é obrigatório!',
                'codigo_produto.unique'  => 'Código do produto já cadastrado!',
                'codigo_produto.max'=> 'Código do produto deve ser menos que 15 caracteres!',
                'descricao.required'=> 'Descrição do produto é obrigatório!',
                'descricao.max'=> 'Descrição limtado a 155 caracteres!',
                //'fornecedor_id.required'=> 'O Fornecedor é obrigatório!',
                'categoria_id.required'=> 'A Categoria é obrigatória!',
           //     'cor_id.required'=> 'A Cor é obrigatória!'
            ]);

            //Verifica se temos erros no form
            if ($validated->fails())
            {
                $error = $validated->errors()->first();
                return Response::json(array('success' => false,'message' => $error), 400);
            }

            $data["codigo_produto"] = $this->request->input("codigo_produto");
            $data["descricao"] = $this->request->input("descricao");
            $data["status"] = $this->request->input("status");
            //$data["fornecedor_id"] = $this->request->input("fornecedor_id");
            $data["categoria_id"] = $this->request->input("categoria_id");
            $data["cor_id"] = 1;

            //Cria o produto
            //$products = $this->produto::create($data);
            $matchThese = array('id' => $this->request->input("id"));
            $products = $this->produto::updateOrCreate($matchThese, $data);

           // dd($products);
            // echo $produtos->id;
            /**
             * Quantidade de variações
             */
            $qtd_lines = count($this->request->input("variacao"));
            //     dd($qtd_lines);

            /**
             * Exibindo os dados
             */
            $data["products_id"] = $products->id;

            //dd($data);
            /**
             *   Formata em decimal par o banco
             */
            $formatter = new NumberFormatter('pt_BR',  NumberFormatter::DECIMAL);

            for ($i=0; $i<$qtd_lines; $i++) {
                // $newDate = Carbon::createFromFormat('d/m/Y', $this->request->input("validade".[$i])->format('Y-m-d'));

                $data["subcodigo"] = $data["codigo_produto"].$this->request->input("subcodigo")[$i];
                $data["variacao"] = $this->request->input("variacao")[$i];
                $data["valor_varejo"] = $formatter->parse($this->request->input("valor_varejo")[$i]);
                $data["valor_atacado"] = $formatter->parse($this->request->input("valor_atacado")[$i]);
				$data["valor_atacado_5un"] = $formatter->parse($this->request->input("valor_atacado_5un")[$i]);
                $data["valor_atacado_10un"] = $formatter->parse($this->request->input("valor_atacado_10un")[$i]);
                $data["valor_lista"] = $formatter->parse($this->request->input("valor_lista")[$i]);
                $data["valor_produto"] = $formatter->parse($this->request->input("valor_produto")[$i]);
                $data["quantidade"] = $this->request->input("quantidade")[$i];
                $data["quantidade_minima"] = $this->request->input("quantidade_minima")[$i];
                $data["status"] = $this->request->input("status_variacao")[$i];
                $data["validade"] = Carbon::createFromFormat('d/m/Y',$this->request->input("validade")[$i])->format('Y-m-d');
                $data["fornecedor"] = $this->request->input("fornecedor")[$i];
                $data["estoque"] = $this->request->input("estoque")[$i];

                /**
                 * Cria ou Atualiza a variação do produto
                 */
                $matchThese = array('id' => $this->request->input("variacao_id")[$i]);
                ProdutoVariation::updateOrCreate($matchThese, $data);
                //dd($productsVariation);

                /**
                 * UPLOAD DE IMAGENS
                 */
                /* dd($this->request->all());
                for ($k = 0; $k < count($this->request->allFiles()); $k++) {
                    //(isset($this->request->allFiles('images')[$k]));
                    dd(isset($this->request->allFiles('images'.$k)[$k]));
                    if(isset($this->request->allFiles('images')[$k])){

                        if (count($this->request->allFiles('images' . $k)) > 0) {
                            $total = count($this->request->allFiles()['images' . $i]);

                            for ($j = 0; $j < $total; $j++) {
                                $file = $this->request->allFiles()['images' . $i][$j];

                                $productsImages = new ProdutoImagem();
                                $productsImages->produto_id = $productsVariation->id;
                                $productsImages->path = $file->store('produtos/' . $productsVariation->id);
                                $productsImages->save();
                                unset($this->productsImages);
                            }
                        }
                    }
                }*/
            }

            // return back()->withStatus(__('Produto cadastrado com sucesso!'));
            //return redirect()->route('product.index')->with('success','Produto cadastrado com sucesso!');
            return Response::json(array('success' => true, 'message' =>  $msg), 201);

        } catch (Throwable $e) {
            return Response::json(array('success' => false, 'message' => $e->getMessage(), 'cod_retorno' => 500), 500);
        }
        // return Response::json(array('success' => true, 'message' => 'Produto cadastrado com sucesso!'), 201);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return JsonResponse
     */
    public function show($id)
    {
        $data = $this->produto::select('codigo_produto')->orderBy("id", "desc")->first();
        $id = $this->produto::max('id')+1; //pega próximo ID para criar  a variação do produto

        if($data == null)
            return Response::json(array('success' => true, "data" => 1000, "id" => "01"), 200);

        return Response::json(array('success' => true, "data" => $data->codigo_produto + 1, "id" => "01"), 200);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param Request $request
     * @param int $id
     * @return void
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param int $id
     * @return void
     */
    public function destroy($id)
    {
        //
    }

    /**
     * Retorna imagens do produto
     * Recebe o Id do produto
     * @param int $id
     * @return JsonResponse
     */
    public function pictures(int $id){

        try {
           $data = $this->produtoImage::select('id','produto_variacao_id','path')->where('produto_variacao_id',$id)->get();

        } catch (Throwable $e) {
            return Response::json(['error' => $e->getMessage()], 500);
        }
        return Response::json(array('success' => true, "data" => $data), 200);
    }

    /***
     *
     * @param int $id
     */
    public function getProducts(int $id){
        try {
            $ret =  $this->produto::with('products')
                ->select("id","codigo_produto","descricao","status","block","fornecedor_id","categoria_id")
                ->where('id',$id)->first();

        } catch (Throwable $e) {
            return Response::json(['error' => $e->getMessage()], 500);
        }
        return Response::json(array('success' => true, "data" => $ret), 200);
    }
}
