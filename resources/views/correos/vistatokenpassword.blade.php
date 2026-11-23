<!DOCTYPE html>
<html lang="es" xmlns="http://www.w3.org/1999/xhtml" xmlns:v="urn:schemas-microsoft-com:vml" xmlns:o="urn:schemas-microsoft-com:office:office">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="x-apple-disable-message-reformatting">
    <meta name="color-scheme" content="light">
    <meta name="supported-color-schemes" content="light">
    <title>Recuperación de Contraseña - Astro Pollo</title>
    <!--[if mso]>
    <noscript>
        <xml>
            <o:OfficeDocumentSettings>
                <o:AllowPNG/>
                <o:PixelsPerInch>96</o:PixelsPerInch>
            </o:OfficeDocumentSettings>
        </xml>
    </noscript>
    <![endif]-->
    <style type="text/css">
        body { margin: 0; padding: 0; width: 100% !important; -webkit-text-size-adjust: 100%; -ms-text-size-adjust: 100%; }
        table, td { border-collapse: collapse; mso-table-lspace: 0pt; mso-table-rspace: 0pt; }
        img { border: 0; outline: none; text-decoration: none; -ms-interpolation-mode: bicubic; display: block; }
        a[x-apple-data-detectors] { color: inherit !important; text-decoration: none !important; }
        .btn:hover { background-color: #a31212 !important; }

        @media only screen and (max-width: 620px) {
            .container { width: 100% !important; }
            .px { padding-left: 24px !important; padding-right: 24px !important; }
            .title { font-size: 24px !important; line-height: 32px !important; }
            .btn-td a { display: block !important; }
        }
    </style>
</head>
<body style="margin:0; padding:0; background-color:#f4f4f6;">

<!-- Texto de vista previa (se muestra en la bandeja de entrada) -->
<div style="display:none; max-height:0; overflow:hidden; mso-hide:all; font-size:1px; line-height:1px; color:#f4f4f6;">
    Restablece tu contraseña del Panel de Administración. El enlace vence en 60 minutos.
</div>

<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color:#f4f4f6;">
    <tr>
        <td align="center" style="padding:32px 12px;">

            <table role="presentation" class="container" width="600" cellpadding="0" cellspacing="0" border="0" style="width:600px; max-width:600px; background-color:#ffffff; border-radius:16px; overflow:hidden; box-shadow:0 4px 24px rgba(0,0,0,0.06);">

                <!-- Franja superior de marca -->
                <tr>
                    <td style="height:6px; line-height:6px; font-size:0; background-color:#c51818;">&nbsp;</td>
                </tr>

                <!-- Logo -->
                <tr>
                    <td align="center" style="padding:36px 24px 8px;">
                        <img src="http://astropolloapp.com/storage/correoimg/image-4.png" width="120" alt="Astro Pollo" style="width:120px; max-width:120px; height:auto; border-radius:24px;">
                    </td>
                </tr>

                <!-- Icono + Título -->
                <tr>
                    <td align="center" class="px" style="padding:24px 48px 0;">
                        <table role="presentation" cellpadding="0" cellspacing="0" border="0">
                            <tr>
                                <td align="center" style="width:72px; height:72px; background-color:#c51818; border-radius:36px;">
                                    <img src="http://astropolloapp.com/storage/correoimg/image-3.png" width="40" alt="" style="width:40px; height:auto; margin:16px auto;">
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>
                <tr>
                    <td align="center" class="px" style="padding:20px 48px 0; font-family:'Segoe UI', Roboto, Helvetica, Arial, sans-serif;">
                        <h1 class="title" style="margin:0; font-size:28px; line-height:36px; font-weight:700; color:#1f1f29;">
                            Recuperación de Contraseña
                        </h1>
                    </td>
                </tr>

                <!-- Cuerpo -->
                <tr>
                    <td class="px" style="padding:28px 48px 0; font-family:'Segoe UI', Roboto, Helvetica, Arial, sans-serif; font-size:16px; line-height:26px; color:#4a4a57;">
                        <p style="margin:0 0 16px;">Hola <strong style="color:#1f1f29;">{{ $usuario }}</strong>,</p>
                        <p style="margin:0 0 16px;">
                            Recibimos una solicitud para restablecer la contraseña de tu cuenta en el
                            <strong style="color:#1f1f29;">Panel de Administración</strong> de Astro Pollo.
                        </p>
                        <p style="margin:0;">Haz clic en el botón para crear una nueva contraseña:</p>
                    </td>
                </tr>

                <!-- Botón -->
                <tr>
                    <td align="center" class="px btn-td" style="padding:32px 48px 8px;">
                        <!--[if mso]>
                        <v:roundrect xmlns:v="urn:schemas-microsoft-com:vml" xmlns:w="urn:schemas-microsoft-com:office:word" href="{{ $url }}" style="height:52px; v-text-anchor:middle; width:280px;" arcsize="20%" stroke="f" fillcolor="#c51818">
                            <w:anchorlock/>
                            <center style="color:#ffffff; font-family:Arial, sans-serif; font-size:16px; font-weight:bold;">Restablecer contraseña</center>
                        </v:roundrect>
                        <![endif]-->
                        <!--[if !mso]><!-->
                        <a href="{{ $url }}" target="_blank" class="btn"
                           style="display:inline-block; background-color:#c51818; color:#ffffff; font-family:'Segoe UI', Roboto, Helvetica, Arial, sans-serif; font-size:16px; font-weight:700; line-height:52px; text-align:center; text-decoration:none; padding:0 40px; border-radius:10px; mso-hide:all;">
                            Restablecer contraseña
                        </a>
                        <!--<![endif]-->
                    </td>
                </tr>

                <!-- Aviso de expiración -->
                <tr>
                    <td align="center" class="px" style="padding:20px 48px 0;">
                        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color:#fff6e5; border-left:4px solid #f5a623; border-radius:8px;">
                            <tr>
                                <td style="padding:14px 18px; font-family:'Segoe UI', Roboto, Helvetica, Arial, sans-serif; font-size:14px; line-height:22px; color:#7a5200;">
                                    &#9201;&nbsp; Por seguridad, este enlace es válido por <strong>60 minutos</strong> y solo puede usarse una vez.
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>

                <!-- Enlace alternativo -->
                <tr>
                    <td class="px" style="padding:28px 48px 0; font-family:'Segoe UI', Roboto, Helvetica, Arial, sans-serif; font-size:13px; line-height:20px; color:#8a8a96;">
                        <p style="margin:0 0 8px;">¿El botón no funciona? Copia y pega este enlace en tu navegador:</p>
                        <p style="margin:0; padding:12px 14px; background-color:#f4f4f6; border-radius:8px; word-break:break-all;">
                            <a href="{{ $url }}" target="_blank" style="color:#c51818; text-decoration:underline;">{{ $url }}</a>
                        </p>
                    </td>
                </tr>

                <!-- Separador + nota de seguridad -->
                <tr>
                    <td class="px" style="padding:32px 48px 40px;">
                        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
                            <tr><td style="border-top:1px solid #ececf0; font-size:0; line-height:0;">&nbsp;</td></tr>
                            <tr>
                                <td style="padding-top:24px; font-family:'Segoe UI', Roboto, Helvetica, Arial, sans-serif; font-size:14px; line-height:22px; color:#8a8a96;">
                                    Si no solicitaste este cambio, puedes ignorar este mensaje con tranquilidad: tu contraseña actual
                                    seguirá siendo la misma y nadie podrá modificarla sin acceso a este correo.
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>

                <!-- Footer -->
                <tr>
                    <td align="center" style="background-color:#c51818; padding:28px 24px;">
                        <table role="presentation" cellpadding="0" cellspacing="0" border="0">
                            <tr>
                                <td style="padding:0 8px;">
                                    <a href="https://www.facebook.com/AstroPolloElSalvador" target="_blank" title="Facebook">
                                        <img src="http://astropolloapp.com/storage/correoimg/image-2.png" width="32" alt="Facebook" style="width:32px; height:auto;">
                                    </a>
                                </td>
                                <td style="padding:0 8px;">
                                    <a href="https://www.instagram.com/astropollosv/" target="_blank" title="Instagram">
                                        <img src="http://astropolloapp.com/storage/correoimg/image-1.png" width="32" alt="Instagram" style="width:32px; height:auto;">
                                    </a>
                                </td>
                            </tr>
                        </table>
                        <p style="margin:16px 0 4px; font-family:'Segoe UI', Roboto, Helvetica, Arial, sans-serif; font-size:14px; font-weight:700; color:#ffffff;">
                            Astro Pollo El Salvador
                        </p>
                        <p style="margin:0; font-family:'Segoe UI', Roboto, Helvetica, Arial, sans-serif; font-size:12px; line-height:18px; color:#fbd5d5;">
                            Este es un correo automático, por favor no respondas a este mensaje.<br>
                            &copy; {{ date('Y') }} Astro Pollo. Todos los derechos reservados.
                        </p>
                    </td>
                </tr>

            </table>

        </td>
    </tr>
</table>

</body>
</html>
