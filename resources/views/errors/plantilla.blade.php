<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title>@yield('codigo') · @yield('titulo') - Astro Pollo</title>
    <link href="{{ asset('images/icono-sistema.png') }}" rel="icon">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;800&display=swap" rel="stylesheet">

    {{-- Pagina autonoma: sin JS ni sesion, para que cargue aunque la aplicacion falle --}}
    <style>
        :root {
            --rojo: #c51818;
            --rojo-oscuro: #9e1212;
            --amarillo: #f5b800;
            --texto: #1f1f29;
            --gris: #6b6b78;
        }

        * { box-sizing: border-box; }

        html, body { margin: 0; min-height: 100%; }

        body {
            min-height: 100vh;
            min-height: 100dvh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px 16px;
            font-family: 'Poppins', system-ui, -apple-system, 'Segoe UI', Roboto, sans-serif;
            color: var(--texto);
            background:
                linear-gradient(160deg, rgba(197, 24, 24, .55) 0%, rgba(17, 17, 17, .88) 65%),
                #111 url('{{ asset('images/fondo1.jpg') }}') center / cover no-repeat fixed;
        }

        .tarjeta {
            width: 100%;
            max-width: 480px;
            padding: 40px 36px 28px;
            text-align: center;
            background: #fff;
            border-top: 5px solid var(--rojo);
            border-radius: 24px;
            box-shadow: 0 30px 70px rgba(0, 0, 0, .35);
        }

        .logo {
            display: block;
            width: 64px;
            height: 64px;
            margin: 0 auto 20px;
            border-radius: 50%;
            box-shadow: 0 6px 18px rgba(197, 24, 24, .25);
        }

        .icono {
            width: 72px;
            height: 72px;
            margin: 0 auto 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 20px;
            color: var(--rojo);
            background: #fdeaea;
        }

        .icono svg { width: 34px; height: 34px; }

        .codigo {
            margin: 4px 0 0;
            font-size: 76px;
            font-weight: 800;
            line-height: 1;
            letter-spacing: -2px;
            background: linear-gradient(135deg, var(--rojo) 30%, var(--amarillo));
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
        }

        h1 {
            margin: 12px 0 8px;
            font-size: 24px;
            font-weight: 600;
        }

        .mensaje {
            margin: 0 auto 28px;
            max-width: 360px;
            font-size: 15px;
            line-height: 1.6;
            color: var(--gris);
        }

        .acciones {
            display: flex;
            gap: 12px;
            justify-content: center;
            flex-wrap: wrap;
        }

        .boton {
            flex: 1 1 160px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            height: 48px;
            padding: 0 20px;
            border-radius: 12px;
            font: inherit;
            font-size: 15px;
            font-weight: 600;
            text-decoration: none;
            cursor: pointer;
            transition: background .15s, color .15s, transform .1s;
        }

        .boton:active { transform: translateY(1px); }
        .boton svg { width: 18px; height: 18px; }

        .boton--principal {
            color: #fff;
            background: var(--rojo);
            border: 0;
            box-shadow: 0 10px 24px rgba(197, 24, 24, .28);
        }

        .boton--principal:hover { background: var(--rojo-oscuro); }

        .boton--secundario {
            color: var(--texto);
            background: #f3f3f7;
            border: 1px solid #e3e3ea;
        }

        .boton--secundario:hover { background: #e9e9f0; }

        .pie {
            margin: 28px 0 0;
            font-size: 12px;
            color: #a3a3b0;
        }

        @media (max-width: 420px) {
            .tarjeta { padding: 32px 22px 24px; }
            .codigo { font-size: 64px; }
        }
    </style>
</head>
<body>

<main class="tarjeta">
    <img src="{{ asset('images/logo.png') }}" alt="Astro Pollo" class="logo">

    <div class="icono" aria-hidden="true">@yield('icono')</div>

    <p class="codigo">@yield('codigo')</p>
    <h1>@yield('titulo')</h1>
    <p class="mensaje">@yield('mensaje')</p>

    <div class="acciones">
        @hasSection('acciones')
            @yield('acciones')
        @else
            <a href="javascript:history.back()" class="boton boton--secundario">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 12H5M12 19l-7-7 7-7"/></svg>
                Volver
            </a>
            {{-- target _top: si el error sale dentro del panel (iframe), abre el inicio en toda la ventana --}}
            <a href="{{ url('/') }}" target="_top" class="boton boton--principal">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 10.5 12 3l9 7.5V20a1 1 0 0 1-1 1h-5v-6h-6v6H4a1 1 0 0 1-1-1z"/></svg>
                Ir al inicio
            </a>
        @endif
    </div>

    <p class="pie">&copy; {{ date('Y') }} Astro Pollo El Salvador</p>
</main>

</body>
</html>
