@extends('layouts.layout')

@section('menu')

    @include('admin.menu')

@endsection

@section('content')

    <div class="container-fluid"  style=" padding-top: 10px;padding-right: 10px">
        <div class="container-fluid px-2">
            <div class="card mb-4">
                <div class="card-header">
                    <div class="col-sm-auto">
                        <h3 class="title"> <i class="fas fa-dollar me-1"></i> {{ __('Estoque') }}</h3>
                    </div>

                    <div class="row row-cols-md-auto g-3 float-right " style="margin-top: -50px;margin-right: -100px">
                        <div class="col-sm-2">
                            <span id="load" style="display: none"></span>
                        </div>
                        <div class="col-auto input-group-sm">
                            <i class="fas fa-chart-area me-1"></i>Filtro:
                        </div>
                        <div class="col-sm-2 date w-25"  id="data" data-date="{{date("m/Y")}}" data-date-format="mm/yyyy">
                            <label for="data" class="visually-hidden">Ano</label>
                            <input type="text" class="form-control input-group-sm" id="data" placeholder="Ano" aria-label="Ano" name="data">
                            <span class="add-on"><i class="icon-th"></i></span>
                        </div>

                        <div class="col-sm-2">
                            <button class="btn bgBtn btn-enviar" type="button">Filtrar</button>
                            <button class="btn bgBtn btn-limpar" type="button">Limpar</button>

                        </div>

                    </div>
                </div>

                <div class="card-body">
                    <div class="row">
                        <div class="row card-body">
                            <div class="col-xl-6">
                                <div class="card mb-5">
                                    <div class="card-header">
                                        <i class="fas fa-table me-1"></i>+ Vendidos por Categoria
                                    </div>
                                    <div class="card-body">
                                        <table id="table" class="table compact table-striped table-bordered table-hover">
                                            <thead class="text-center">
                                                <tr>
                                                    <th>#</th>
                                                    <th data-sortable="true">Produto</th>
                                                    <th data-sortable="true">Código</th>
                                                    <th data-sortable="true">Total</th>
                                                    <th data-sortable="true">Qtd</th>
                                                    <th data-sortable="true">Categoria</th>
                                                    <!--th data-sortable="true">Data</th>
                                                    <th data-sortable="true">DataSort</th-->
                                                </tr>
                                            </thead>
                                            <tbody class="text-center"></tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                            <div class="col-xl-6">
                                <div class="card mb-4">
                                    <div class="card-header">
                                        <i class="fas fa-chart-bar me-1"></i>Receitas x Despesas
                                    </div>
                                    <div class="card-body">
                                        <canvas id="myChartRD" width="100%" height="40"></canvas>
                                    </div>
                                </div>
                            </div>
                            <div class="col-xl-6">
                                <div class="card mb-4">
                                    <div class="card-header">
                                        <i class="fas fa-chart-bar me-1"></i>Mensal
                                    </div>
                                    <div class="card-body">
                                        <canvas id="myChartMes" width="100%" height="40"></canvas>
                                    </div>
                                </div>
                            </div>
                            <div class="col-xl-6">
                                <div class="card mb-4">
                                    <div class="card-header">
                                        <i class="fas fa-chart-bar me-1"></i>Cartões
                                    </div>
                                    <div class="card-body">
                                        <canvas id="myChartCard" width="100%" height="40"></canvas>
                                    </div>
                                </div>
                            </div>

                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!--div class="form-row">
        <div class="col-md-12 mb-2">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="">Dashbord</a></li>
                    <li class="breadcrumb-item {{ Route::current()->getName() === 'fluxo.index' ? 'active' : '' }}" aria-current="page">Fluxo</li>
                </ol>
            </nav>
        </div>
    </div>

    <div class="container caja">
        <div id="retorno_fluxo"></div>
        @csrf

        <table id="table" class="table table-striped table-condensed table-hover" style="width:100%">
            <thead class="text-center">
            <tr>
                <th>id</th>
                <th>Valor Caixa</th>
                <th>Valor Sangria</th>
                <th>Descrição</th>
                <th>Loja</th>
                <th>Total Dia</th>
                <th data-sortable="true">Data</th>
                <th>Atualização</th>
                <th width="10px">Ação</th>
            </tr>
            </thead>
            <tbody class="text-center">
            </tbody>
        </table>

    </div-->



    </body>
@endsection
@push("scripts")
    <script src="{{URL::asset('assets/dashboard/js/Chart.min.js')}}"></script>
    <script src="{{URL::asset('assets/jquery/jquery.dataTables.min.js')}}"></script>
    <script src="{{URL::asset('assets/bootstrap/js/dataTables.bootstrap4.min.js')}}"></script>
    <script src="{{URL::asset('assets/bootstrap/js/bootstrap-datepicker.min.js')}}"></script>
    <script src="{{URL::asset('assets/bootstrap/js/bootstrap-datepicker.pt-BR.min.js')}}" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
    <script src="{{URL::asset('js/estoque.js')}}"></script>
@endpush
@push("styles")
    <link rel="stylesheet"  type="text/css" href="{{URL::asset('assets/bootstrap/css/bootstrap-datepicker.min.css')}}">
    <link rel="stylesheet"  type="text/css" href="{{URL::asset('assets/datatables/DataTables-1.10.18/css/dataTables.bootstrap4.css')}}">
    <link rel="stylesheet"  type="text/css" href="{{URL::asset('assets/datatables/dataTableRender.css')}}">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
@endpush
