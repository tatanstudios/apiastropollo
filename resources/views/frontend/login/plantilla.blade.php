<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('titulo', 'Panel') - Astro Pollo</title>
    <link href="{{ asset('images/icono-sistema.png') }}" rel="icon">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script defer src="https://use.fontawesome.com/releases/v5.0.6/js/all.js"></script>
    <link href="{{ asset('css/toastr.min.css') }}" rel="stylesheet">
    <link href="{{ asset('css/sweetalert2.min.css') }}" rel="stylesheet">

    <style>
        :root {
            --rojo: #c51818;
            --rojo-oscuro: #9e1212;
            --amarillo: #f5b800;
            --texto: #1f1f29;
            --gris: #6b6b78;
            --borde: #e3e3ea;
            --fondo-input: #f6f6f9;
        }

        * { box-sizing: border-box; }

        html, body { height: 100%; margin: 0; }

        body {
            font-family: 'Poppins', system-ui, -apple-system, 'Segoe UI', Roboto, sans-serif;
            color: var(--texto);
            background: #111 url('{{ asset('images/fondo1.jpg') }}') center / cover no-repeat fixed;
        }

        /* ---------- estructura: imagen a la izquierda, formulario a la derecha ---------- */
        /* alto de la ventana (no 100%): SweetAlert pone height:auto en html/body al abrir una alerta */
        .acceso {
            min-height: 100vh;
            min-height: 100dvh;
            display: grid;
            grid-template-columns: 1.1fr 1fr;
        }

        .acceso__marca {
            position: relative;
            display: flex;
            flex-direction: column;
            justify-content: flex-end;
            padding: 56px;
            color: #fff;
            background: linear-gradient(160deg, rgba(197, 24, 24, .55) 0%, rgba(17, 17, 17, .85) 70%);
        }

        .acceso__marca h1 {
            margin: 0 0 12px;
            font-size: 44px;
            font-weight: 700;
            line-height: 1.1;
            letter-spacing: -.5px;
        }

        .acceso__marca h1 span { color: var(--amarillo); }

        .acceso__marca p {
            margin: 0;
            max-width: 420px;
            font-size: 16px;
            line-height: 1.6;
            color: rgba(255, 255, 255, .85);
        }

        .acceso__panel {
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 40px 24px;
            background:
                radial-gradient(circle at 85% 10%, rgba(197, 24, 24, .07) 0, transparent 45%),
                radial-gradient(circle at 10% 90%, rgba(245, 184, 0, .08) 0, transparent 40%),
                #f3f3f7;
        }

        /* el formulario va dentro de una tarjeta blanca */
        .tarjeta {
            width: 100%;
            max-width: 440px;
            padding: 44px 40px 32px;
            background: #fff;
            border: 1px solid #ececf2;
            border-radius: 24px;
            box-shadow: 0 24px 60px rgba(31, 31, 41, .10), 0 4px 12px rgba(31, 31, 41, .05);
            border-top: 5px solid var(--rojo);
        }

        /* ---------- encabezado (centrado) ---------- */
        .logo {
            display: block;
            width: 84px;
            height: 84px;
            border-radius: 50%;
            box-shadow: 0 8px 24px rgba(197, 24, 24, .25);
            margin: 0 auto 24px;
        }

        .icono-paso {
            width: 64px;
            height: 64px;
            border-radius: 18px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            font-size: 26px;
            color: var(--rojo);
            background: #fdeaea;
        }

        .tarjeta h2 {
            margin: 0 0 6px;
            font-size: 26px;
            font-weight: 700;
            text-align: center;
        }

        .tarjeta .subtitulo {
            margin: 0 0 28px;
            text-align: center;
            font-size: 15px;
            line-height: 1.5;
            color: var(--gris);
        }

        /* ---------- campos ---------- */
        .campo { margin-bottom: 18px; }

        .campo label {
            display: block;
            margin-bottom: 8px;
            font-size: 14px;
            font-weight: 500;
        }

        .campo__control {
            position: relative;
            display: flex;
            align-items: center;
        }

        .campo__icono {
            position: absolute;
            left: 16px;
            font-size: 15px;
            color: #a3a3b0;
            pointer-events: none;
            transition: color .15s;
        }

        .campo input {
            width: 100%;
            height: 52px;
            padding: 0 48px 0 46px;
            font: inherit;
            font-size: 15px;
            color: var(--texto);
            background: var(--fondo-input);
            border: 1.5px solid transparent;
            border-radius: 12px;
            outline: none;
            transition: border-color .15s, background .15s, box-shadow .15s;
        }

        .campo input::placeholder { color: #a3a3b0; }

        .campo input:focus {
            background: #fff;
            border-color: var(--rojo);
            box-shadow: 0 0 0 4px rgba(197, 24, 24, .12);
        }

        .campo__control:focus-within .campo__icono { color: var(--rojo); }

        .ver-clave {
            position: absolute;
            right: 8px;
            width: 36px;
            height: 36px;
            border: 0;
            border-radius: 8px;
            color: #a3a3b0;
            background: transparent;
            cursor: pointer;
        }

        .ver-clave:hover { color: var(--texto); background: #ececf2; }

        .enlace-fila {
            display: flex;
            justify-content: flex-end;
            margin: -4px 0 24px;
        }

        a.enlace {
            font-size: 14px;
            font-weight: 500;
            color: var(--rojo);
            text-decoration: none;
        }

        a.enlace:hover { color: var(--rojo-oscuro); text-decoration: underline; }

        /* ---------- boton ---------- */
        .boton {
            width: 100%;
            height: 52px;
            border: 0;
            border-radius: 12px;
            font: inherit;
            font-size: 15px;
            font-weight: 600;
            letter-spacing: .3px;
            color: #fff;
            background: var(--rojo);
            box-shadow: 0 10px 24px rgba(197, 24, 24, .28);
            cursor: pointer;
            transition: background .15s, transform .1s, box-shadow .15s;
        }

        a.boton {
            display: flex;
            align-items: center;
            justify-content: center;
            text-decoration: none;
        }

        .boton:hover { background: var(--rojo-oscuro); color: #fff; }
        .boton:active { transform: translateY(1px); box-shadow: 0 4px 12px rgba(197, 24, 24, .28); }
        .boton:focus-visible { outline: 3px solid rgba(197, 24, 24, .35); outline-offset: 2px; }

        .volver {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 8px;
            margin-top: 22px;
        }

        .pie {
            margin: 32px 0 0;
            font-size: 12px;
            text-align: center;
            color: #a3a3b0;
        }

        /* ---------- pantallas pequenas: tarjeta sobre la imagen ---------- */
        @media (max-width: 900px) {
            .acceso { grid-template-columns: 1fr; }
            .acceso__marca { display: none; }
            .acceso__panel {
                min-height: 100vh;
                min-height: 100dvh;
                background: rgba(17, 17, 17, .55);
            }
            .tarjeta {
                padding: 32px 24px 24px;
                border-radius: 20px;
                box-shadow: 0 20px 50px rgba(0, 0, 0, .35);
            }
        }
    </style>
</head>
<body>

<main class="acceso">
    <section class="acceso__marca" aria-hidden="true">
        <h1>Astro <span>Pollo</span></h1>
    </section>

    <section class="acceso__panel">
        <div class="tarjeta">
            @yield('contenido')

            <p class="pie">&copy; {{ date('Y') }} Astro Pollo El Salvador</p>
        </div>
    </section>
</main>

<script src="{{ asset('js/jquery.min.js') }}"></script>
<script src="{{ asset('js/toastr.min.js') }}"></script>
<script src="{{ asset('js/axios.min.js') }}"></script>
<script src="{{ asset('js/sweetalert2.all.min.js') }}"></script>
<script src="{{ asset('js/alertaPersonalizada.js') }}"></script>
<script>
    // mostrar / ocultar contrasena
    document.querySelectorAll('.ver-clave').forEach(function (boton) {
        boton.addEventListener('click', function () {
            var input = document.getElementById(boton.dataset.para);
            var visible = input.type === 'text';
            input.type = visible ? 'password' : 'text';
            boton.setAttribute('aria-label', visible ? 'Mostrar contraseña' : 'Ocultar contraseña');
            boton.innerHTML = visible ? '<i class="fas fa-eye"></i>' : '<i class="fas fa-eye-slash"></i>';
        });
    });

    // Enter en cualquier campo ejecuta la accion principal del formulario
    document.querySelectorAll('form[data-accion]').forEach(function (form) {
        form.addEventListener('submit', function (e) {
            e.preventDefault();
            window[form.dataset.accion]();
        });
    });
</script>
@yield('scripts')
</body>
</html>
