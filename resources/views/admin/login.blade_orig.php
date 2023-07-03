<!DOCTYPE html>
<html lang="pt-br">
    <head>
        <meta charset="UTF-8">

        <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/css/bootstrap.min.css" rel="stylesheet" id="bootstrap-css">
        <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/js/bootstrap.bundle.min.js"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.2.1/jquery.min.js"></script>
        <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css" rel="stylesheet" id="bootstrap-css">
        <script src="https://www.google.com/recaptcha/api.js"></script>

        <style>

        </style>
        <script>
            function onSubmit() {
                document.getElementById("login-form").submit();
            }
        </script>
    </head>
    <section class="">
        <div class="container">
            <div class="row justify-content-center">
                <!-- For Demo Purpose -->
                <div class="col-md-5 pr-lg-15 mb-5 mb-md-0">
                    <img src="https://mdbcdn.b-cdn.net/img/Photos/new-templates/bootstrap-login-form/draw2.webp"
                         class="img-fluid mb-3 d-none d-md-block">
                </div>
                <div class="col-md-8 col-lg-20">
                        <div class="row justify-content-center">
                            <form role="form" action="{{route('admin.login.do')}}" method="post" id="login-form" name="login-form">
                            @csrf
                                <!-- Email Address -->
                                <div class="input-group col-lg-14 mb-4">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text bg-white px-4 border-md border-right-0">
                                            <i class="fa fa-envelope text-muted"></i>
                                        </span>
                                    </div>
                                    <input id="email" type="email" name="email" placeholder="Email Address" class="form-control bg-white border-left-0 border-md">
                                </div>

                                <!-- Password -->
                                <div class="input-group col-lg-14 mb-4">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text bg-white px-4 border-md border-right-0">
                                            <i class="fa fa-lock text-muted"></i>
                                        </span>
                                    </div>
                                    <input id="password" type="password" name="password" placeholder="Password" class="form-control bg-white border-left-0 border-md">
                                </div>
                                <!-- Submit Button -->
                                <div class="form-group col-lg-14 mb-4">
                                    <button class="btn btn-primary btn-block py-2 g-recaptcha" data-sitekey="{{ env('DATA_SITEKEY') }} " data-callback='onSubmit'>
                                        <span class="font-weight-bold"><i class="fa fa-sign-in"></i> Login</span>
                                    </button>

                                </div>
                                <!-- Divider Text -->
                                <div class="form-group col-lg-8 mx-auto d-flex align-items-center my-4">
                                    @if($errors->all())
                                        @foreach($errors->all() as $error)
                                            <div class="alert alert-danger" role="alert">
                                                {{ $error }}
                                            </div>
                                        @endforeach
                                    @endif
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</html>
