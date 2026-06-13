<!DOCTYPE html>
<html lang="en">
<head>
     <title>Account</title>
    <link rel="stylesheet" href="css/accountLogin.css" type="text/css">
    <link rel="stylesheet" href="{{ asset('css/accountLog-in.css') }}?v={{ time() }}">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
</head>
<body>
    <div class="container-fluid">
        <div class="mainBody" style="height: 100vh;">
             <div class="companyName">
                <img name="logoTop" src="/img/seatechLogo.png" alt="Logo" width="120" height="120" >
                <p>SEATECH MARITIME TRAINING AND ASSESSMENT CENTER INC,. LEGAZPI <br>
                    Queueing System
                </p>
            </div>
        <div class="bodyLogin">
            <div class="login-container">
                <form method="POST" action="<?php echo route('login'); ?>" class="login-form">
                    <?php echo csrf_field(); ?>

                    <h2>Login</h2>

                    <div class="input-group">
                        <label>Username</label>
                        <input type="text" name="name" required>
                    </div>

                    <div class="input-group">
                        <label>Password</label>
                        <input type="password" name="password" required>
                    </div>

                      @if ($errors->has('login'))
                            <div class="error-message" style="color:red; margin-bottom:10px;">
                                {{ $errors->first('login') }}
                            </div>
                        @endif

                    <button type="submit">LOG IN</button>
                </form>
            </div>
        </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js" integrity="sha384-FKyoEForCGlyvwx9Hj09JcYn3nv7wiPVlz7YYwJrWVcXK/BmnVDxM+D2scQbITxI" crossorigin="anonymous"></script>
</body>
</html>