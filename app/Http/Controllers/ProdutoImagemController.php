<?php

namespace App\Http\Controllers;


use App\Http\Models\ProdutoImagem;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Validator;

use Intervention\Image\Facades\Image;
use Throwable;


class ProdutoImagemController extends Controller
{
    protected $request,$produtoImagem;

    public function __construct(Request $request, ProdutoImagem $produtoImagem){
        $this->request = $request;
        $this->produtoImagem = $produtoImagem;
    }

    /**
     * Display a listing of the resource.
     *
     * @return void
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return void
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @return JsonResponse
     */
    public function store()
    {
       //  dd($this->request->all());
        //dd($this->request->allFiles()['images'][0]);

        try {
            $validator = Validator::make($this->request->all(), [
                'images' => 'required|image|mimes:gif,jpg,jpeg,png|max:2048',
            ],[
                'images.required'=> 'Informe a Imagem do Produto!',
                'images.max'=> 'Imagem deve ter no máximo 2MB!'
            ]);
            if ($validator->fails()) {
                $error = $validator->errors()->first();
                return Response::json(array('success' => false,'message' => $error), 400);
            }
			
			$file = $this->request->allFiles()['images'];

                $image_name =  $file->hashName();
                $path = storage_path('app/public/produtos/'.$this->request->products_variation_id) ;

                File::makeDirectory($path , 0775, true, true);
                $image_resize = Image::make($file->path());
                $image_resize->resize(350,350)->save($path .'/'.$image_name);

                $productsImages = new ProdutoImagem();
                $productsImages->produto_variacao_id = $this->request->products_variation_id;
                //$productsImages->path = $file->store('produtos/' . $this->request->products_variation_id);
                $productsImages->path = 'produtos/' . $this->request->products_variation_id .'/'. $image_name;
                $productsImages->save();
                unset($this->productsImages);
				
			//$destinationPath = public_path('/produtos');
            /*$total = count($this->request->allFiles()['images']);
            if ($total > 0) {
               for ($j = 0; $j < $total; $j++) {
                   $file = $this->request->allFiles()['images'][$j];

                    $productsImages = new ProdutoImagem();
                    $productsImages->produto_variacao_id = $this->request->products_variation_id;
                    $productsImages->path = $file->store('produtos/' . $this->request->products_variation_id);
                  //  $productsImages->path = 'produtos/' . $this->request->products_variation_id .'/'. $image_name;
                    $productsImages->save();
                    unset($this->productsImages);
                }
            }*/
        }catch (Throwable $e){
            return Response::json(array('success' => false, 'message' => $e->getMessage()), 500);
        }
        return Response::json(array('success' => true, 'message' => "Imagem cadastrada com sucesso!"), 201);
    }

    /**
     * Display the specified resource.
     *
     * @return void
     */
    public function show()
    {
        //
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
     * @return JsonResponse
     */
    public function destroy()
    {
        try {
            $id = $this->request->input("id");

            $image = $this->produtoImagem::find($id);
           // dd($image);
            if(!$image){
                return Response::json(array("success" => false, "message" => utf8_encode("Imagem não localizado id: [ {$id} ]")), 400);
            }else {

                $path = public_path('../storage/' . $image->path);
                  //dd($path);
                if (!File::exists($path)) {
                   // dd($path);
                    //Remove a imagem
                    File::delete(public_path('storage/' .  $image->path));
                    $this->produtoImagem::destroy($id);

                    //Verifica quantas imagens existem
                    $count = $this->produtoImagem::where('produto_variacao_id', '=', $id)->count();

                    // se tiver 0 apaga o diretório também
                    if ($count === 0) {
                        if ($id < 10)
                            $id = "0" . $image->produto_variacao_id;

                        //dd($path.'/'.$id );
                        File::deleteDirectory('storage/app/public/produtos/' . $id);
                    }
                }
            }
         }catch (Throwable $e){
            return Response::json(array('success' => false, 'message' => $e->getMessage()), 500);
        }
        return Response::json(array('success' => true, 'message' => "Imagem removida com sucesso!"), 200);
    }
}
