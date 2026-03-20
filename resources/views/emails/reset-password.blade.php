<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name') }} - Recuperaci&oacute;n de Contrase&ntilde;a</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #ffffff;
            color: #191731;
            line-height: 1.6;
        }
        .container {
            max-width: 600px;
            margin: 40px auto;
            background-color: #ffffff;
            border: 1px solid #e5e5e5;
            border-radius: 0;
        }
        .header {
            background: #191731;
            padding: 40px 30px;
            text-align: center;
            border-bottom: 1px solid #e5e5e5;
        }
        .header img {
            max-height: 40px;
            width: auto;
            min-width: 150px;
            margin-bottom: 20px;
        }
        .header h1 {
            margin: 0;
            font-size: 24px;
            font-weight: 300;
            color: #EBDFC7;
            letter-spacing: 0.5px;
        }
        .content {
            padding: 40px 30px;
        }
        .greeting {
            font-size: 16px;
            color: #191731;
            margin-bottom: 20px;
            font-weight: 300;
        }
        .message {
            font-size: 14px;
            margin-bottom: 30px;
            color: #191731;
            font-weight: 300;
            line-height: 1.8;
        }
        .details-box {
            background-color: #ffffff;
            border: 1px solid #e5e5e5;
            border-radius: 8px;
            padding: 24px;
            margin: 30px 0;
        }
        .details-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 16px;
            padding-bottom: 16px;
            border-bottom: 1px solid #f0f0f0;
        }
        .details-row:last-child {
            border-bottom: none;
            margin-bottom: 0;
            padding-bottom: 0;
        }
        .details-label {
            font-weight: 300;
            color: #191731;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .details-value {
            color: #191731;
            text-align: right;
            font-weight: 400;
        }
        .icon {
            width: 16px;
            height: 16px;
            display: inline-block;
            vertical-align: middle;
        }
        .cta-button {
            display: inline-block;
            background: #191731;
            color: #EBDFC7;
            padding: 14px 28px;
            text-decoration: none;
            font-weight: 400;
            font-size: 14px;
            margin: 20px 0;
            letter-spacing: 0.5px;
            transition: all 0.3s ease;
            border: none;
            border-radius: 8px;
        }
        .cta-button:hover {
            background: #0D0F1A;
            transform: translateY(-1px);
        }
        ul {
            padding-left: 20px;
            margin: 10px 0;
        }
        ul li {
            margin-bottom: 8px;
            font-weight: 300;
        }
        .footer {
            background-color: #191731;
            padding: 30px;
            text-align: center;
            border-top: 1px solid #e5e5e5;
            color: #EBDFC7;
            font-size: 12px;
            font-weight: 300;
        }
        .footer p {
            margin: 8px 0;
        }
        .divider {
            height: 1px;
            background-color: #e5e5e5;
            margin: 40px 0;
        }
        strong {
            font-weight: 500;
        }
        @media (max-width: 600px) {
            .container {
                margin: 20px 10px;
            }
            .header, .content {
                padding: 30px 20px;
            }
            .details-row {
                flex-direction: column;
                text-align: left;
            }
            .details-value {
                text-align: left;
                margin-top: 8px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <img src="{{ url('/images/logo_white.svg') }}" alt="Logo">
            <h1>Recuperaci&oacute;n de Contrase&ntilde;a</h1>
        </div>

        <!-- Content -->
        <div class="content">
            <div class="greeting">
                Hola,
            </div>

            <div class="message">
                <p>Hemos recibido una solicitud para restablecer la contrase&ntilde;a de tu cuenta en <strong>{{ config('app.name') }}</strong>.</p>

                <p>Haz clic en el bot&oacute;n de abajo para crear una nueva contrase&ntilde;a de forma segura.</p>
            </div>

            <div style="text-align: center; margin: 40px 0;">
                <a href="{{ $resetUrl }}" class="cta-button">
                    Restablecer Contrase&ntilde;a
                </a>
            </div>

            <div class="details-box">
                <div class="details-row">
                    <span class="details-label">
                        <svg class="icon" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M12 8V12L15 15M21 12C21 16.9706 16.9706 21 12 21C7.02944 21 3 16.9706 3 12C3 7.02944 7.02944 3 12 3C16.9706 3 21 7.02944 21 12Z" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                        Vigencia del Enlace
                    </span>
                    <span class="details-value"><strong>{{ $expirationMinutes }} minutos</strong></span>
                </div>
                <div class="details-row">
                    <span class="details-label">
                        <svg class="icon" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M12 15V17M12 9V13M12 21C16.9706 21 21 16.9706 21 12C21 7.02944 16.9706 3 12 3C7.02944 3 3 7.02944 3 12C3 16.9706 7.02944 21 12 21Z" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                        Seguridad
                    </span>
                    <span class="details-value">Enlace de un solo uso</span>
                </div>
            </div>

            <div class="message">
                <p><strong>Importante:</strong></p>
                <ul>
                    <li>Si no solicitaste este cambio, ignora este correo de forma segura</li>
                    <li>Tu contrase&ntilde;a actual no ser&aacute; modificada</li>
                    <li>No compartas este enlace con nadie</li>
                    <li>El enlace expirar&aacute; autom&aacute;ticamente despu&eacute;s de usarlo</li>
                </ul>
            </div>

            <div class="divider"></div>

            <div class="message">
                <p><strong>Recuerda:</strong> Si tienes problemas para restablecer tu contrase&ntilde;a, contacta al equipo de soporte t&eacute;cnico.</p>
            </div>
        </div>

        <!-- Footer -->
        <div class="footer">
            <p><strong>{{ config('app.name') }}</strong></p>
            <p>Este correo fue enviado autom&aacute;ticamente.</p>
            <p>Por favor, no respondas a este correo.</p>
            <p style="margin-top: 20px;">
                &copy; {{ date('Y') }} {{ config('app.name') }}. Todos los derechos reservados.
            </p>
        </div>
    </div>
</body>
</html>
