/* globals Chart:false, feather:false */
$(document).ready(function() {
    let urlApi = window.location.protocol +"//"+ window.location.hostname + "/api-loja/admin";
    let metodo = '', titulo = '', url,fila,id,nome,token,json;

    const swalWithBootstrapButtons = Swal.mixin({
        customClass: {
            confirmButton: 'btn btn-success',
            cancelButton: 'btn btn-danger'
        },
        buttonsStyling: false
    });

    const table = $('#table').DataTable({
        "createdRow": function (row, data) {
            if (data.status === "INATIVO") {
                $(row).addClass('red');
            }
        },
        "ajax": {
            "method": 'get',
            "url": urlApi + "/payment/create",
            "data": '',
            "dataSrc": ""
        },
        "columns": [
            {"data": "id"},
            {"data": "nome"},
            {"data": "created_at",
                render: function(data, type, row){
                    return moment(data).format("DD/MM/YYYY HH:mm");
                }
            },
            {"data": "updated_at",
                render: function(data, type, row){
                    return moment(data).format("DD/MM/YYYY HH:mm");
                }
            },
            {"defaultContent": "<div class='text-center'>" +
                    "<div class='btn-group'>" +
                    "<button class='btn btn-primary btn-sm btnEditar' " +
                    "data-bs-toggle=\"modal\" data-bs-target=\"#divModal\">" +
                    "<i class='material-icons'>edit</i></button>&nbsp;&nbsp;" +
                    "<button class='btn btn-danger btn-sm btnBorrar'><i class='material-icons'>delete</i></button>" +
                    "</div>" +
                    "</div>"}
        ],
        "order": [[0, "asc"]]
        , language: {
            "url": "//cdn.datatables.net/plug-ins/1.10.21/i18n/Portuguese-Brasil.json"
        },
    });

    /**
     * AÇÃO DE ABRIR O MODAL
     * Novo
     * */
    $('button[id="btnNuevo"]').on('click', function(event) {
		event.preventDefault();
        $("#form")[0].reset();

        $("#id").val('');
        $("#metodo").val('POST');
		this.blur(); // Manually remove focus from clicked link.
		$('#modal-title').html('<p><img src="../public/img/iconfinder_ecommerce-37_4707183.png"/>&nbsp;<strong>NOVA FORMA DE PAGAMENTO</strong></p>');
        $("#modal-title").addClass( "alert alert-secondary" );
    });

    /**
     * Editar
     * **/
    $(document).on("click", ".btnEditar", function(event){
        event.preventDefault();
        $('form[name="form"]')[0].reset();

        fila = $(this).closest("tr");
        id = parseInt(fila.find('td:eq(0)').text()); //capturo o ID
        nome = fila.find('td:eq(1)').text();
        //       status = fila.find('td:eq(2)').text() == 'ATIVO'? 1 : 0;

        $("#metodo").val('PUT');
        $("#id").val(id);
        $("#nome").val(nome);
       // $("#status").val(status);

        $('#modal-title').html('<p><img src="../public/img/iconfinder_ecommerce-37_4707183.png"/>&nbsp;<strong>EDITANDO FORMA DE PAGAMENTO</strong></p>');
    });

	/****
	 *
	 * SALVA FORMA
	 *
	 */

	 $('form[name="form"]').validate({
         errorClass: "my-error-class",
         validClass: "my-valid-class",
        rules: {
            nome: {
                required: true
			}
        },
        messages: {
            nome: {
                required: "Informe a Forma de Pagamento?"
            }
        }, submitHandler: function(form,e) {
            e.preventDefault();
             $("#modal-title").addClass( "alert alert-secondary" );
            metodo = $("#metodo").val();

             if(metodo === 'POST'){
                 url = urlApi + "/payment";
                 titulo = "NOVA FORMA DE PAGAMENTO";

             }else if(metodo === 'PUT'){
                 url = urlApi + "/payment/update";
                 titulo = "EDITANDO FORMA DE PAGAMENTO";
             }

    		$.ajax({
					url: url,
					type:metodo,
					data:$('form[name="form"]').serialize(),
					dataType:'json',
                beforeSend: function () {
                    $("#modal-title").removeClass( "alert alert-danger" );
                    $('#modal-title').html('<h4>Aguarde... <div class=\"spinner-border spinner-border-xs ms-auto\" role=\"status\" aria-hidden=\"true\"></div></h4>');
                    $("#modal-title").addClass( "alert alert-info" );
                },
                success: function(data) {
                    // console.log(data);
                    if(data.success) {
                        swalWithBootstrapButtons.fire({
                            title: titulo,
                            text: data.message,
                            icon: 'success',
                            showConfirmButton: false,
                            timer: 1500
                        });
                        table.ajax.reload(null, false);
                    }
                },
                error: function(data){
                    //console.log(data.responseText);
                    json = $.parseJSON(data.responseText);
                    $("#modal-title").addClass( "alert alert-danger" );
                    $('#modal-title').html('<p><i class="fas fa-exclamation-circle"></i>&nbsp;<strong>'+json.message+'</strong></p>');
                    Swal.fire(
                        'error!',
                        json.message,
                        'error'
                    )
                },
                complete:function(data){
                    //console.log(data.responseText);
                    json = $.parseJSON(data.responseText);
                    if(json.success) {
                        window.setTimeout(function () {
                            $('#divModal').modal('hide');
                        }, 1500);
                    }
                }
            });
         }
     });

    /**
     * Remover
     * **/
    $(document).on("click", ".btnBorrar", function(){
        fila = $(this).closest("tr");
        id = parseInt(fila.find('td:eq(0)').text()) ;
        nome = fila.find('td:eq(1)').text();
        token = $('form').find('input[name="_token"]').val();

        Swal.fire({
            title: 'Tem certeza?',
            text: "Está seguro de remover este registro: [ " + nome + " ] ?",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Sim, deletar!'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: urlApi + "/payment/" + id ,
                    data: {_token: token},
                    type: "DELETE",
                    datatype:"json",
                    beforeSend: function () {
                        swalWithBootstrapButtons.fire(
                            'Aguarde..',
                            '<div class=\"spinner-border spinner-border-sm ms-auto\" role=\"status\" aria-hidden=\"true\"></div>',
                            'info'
                        )
                    },
                    success: function(data) {
                        if(data.success) {
                            swalWithBootstrapButtons.fire({
                                title: 'Deletado!',
                                text: data.message,
                                icon: 'success',
                                showConfirmButton: false,
                                timer: 1500
                            });
                            table.ajax.reload(null, false);
                        }
                    },
                    error: function(data){
                        json = $.parseJSON(data.responseText);
                        Swal.fire(
                            'error!',
                            json.message,
                            'error'
                        )
                    }
                });
            }
        });
    });
});
