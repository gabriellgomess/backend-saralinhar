<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>{{ $application->test?->name ?? 'Mapeamento Comportamental' }} — Sara Linhar</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: Arial, Helvetica, sans-serif; background-color: #f4f6f9; color: #333; line-height: 1.6; }
        .wrapper { max-width: 600px; margin: 32px auto; background: #fff; border-radius: 8px; overflow: hidden; border: 1px solid #e4e6ea; }

        /* Header */
        .header { background-color: #003366; padding: 24px 32px; }
        .header-title { color: #fff; font-size: 18px; font-weight: bold; letter-spacing: 0.3px; }
        .header-sub   { color: rgba(255,255,255,0.65); font-size: 11px; margin-top: 3px; }

        /* Accent bar */
        .accent-bar { height: 4px; background-color: #e67e22; }

        /* Body */
        .body { padding: 32px; }
        .greeting { font-size: 17px; font-weight: bold; color: #003366; margin-bottom: 16px; }
        p { font-size: 14px; color: #444; margin-bottom: 12px; }

        /* Info box */
        .info-box { background: #f8f9fb; border-left: 4px solid #e67e22; border-radius: 0 6px 6px 0; padding: 14px 16px; margin: 20px 0; }
        .info-box p { margin-bottom: 6px; font-size: 13px; }
        .info-box p:last-child { margin-bottom: 0; }
        .info-label { font-weight: bold; color: #003366; }

        /* Button */
        .btn-wrap { text-align: center; margin: 28px 0; }
        .btn {
            display: inline-block;
            padding: 14px 36px;
            background-color: #e67e22;
            color: #fff !important;
            text-decoration: none;
            border-radius: 8px;
            font-weight: bold;
            font-size: 15px;
            letter-spacing: 0.3px;
        }

        /* Link fallback */
        .link-fallback { font-size: 12px; color: #888; text-align: center; word-break: break-all; }
        .link-fallback a { color: #003366; }

        /* Tips */
        .tips { background: #f8f9fb; border-radius: 6px; padding: 16px 20px; margin: 20px 0; }
        .tips-title { font-size: 12px; font-weight: bold; color: #003366; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 10px; }
        .tips ul { padding-left: 18px; }
        .tips ul li { font-size: 13px; color: #555; margin-bottom: 6px; }

        /* Footer */
        .footer { background: #f4f6f9; padding: 20px 32px; text-align: center; border-top: 1px solid #e4e6ea; }
        .footer p { font-size: 11px; color: #999; margin-bottom: 4px; }
    </style>
</head>
<body>
<div class="wrapper">

    <div class="header">
        <div class="header-title">Sara Linhar Consultoria</div>
        <div class="header-sub">Desenvolvimento Humano e Organizacional &amp; Recrutamento e Seleção</div>
    </div>
    <div class="accent-bar"></div>

    <div class="body">
        <p class="greeting">
            Olá, {{ $application->respondent_name ?? 'Candidato(a)' }}!
        </p>

        <p>
            Você foi convidado(a) a realizar o instrumento
            <strong>{{ $application->test?->name ?? 'Mapeamento Comportamental' }}</strong>
            como parte do processo conduzido pela <strong>Sara Linhar Consultoria</strong>.
        </p>

        <div class="info-box">
            @if($application->test?->description)
                <p><span class="info-label">Sobre o instrumento:</span> {{ $application->test->description }}</p>
            @endif
            @if(!empty($application->metadata['job_title']))
                <p><span class="info-label">Vaga:</span> {{ $application->metadata['job_title'] }}</p>
            @endif
            @if($application->expires_at)
                <p><span class="info-label">Disponível até:</span> {{ \Carbon\Carbon::parse($application->expires_at)->format('d/m/Y \à\s H:i') }}</p>
            @endif
        </div>

        <div class="btn-wrap">
            <a href="{{ $url }}" class="btn">Iniciar mapeamento →</a>
        </div>

        <p class="link-fallback">
            Ou acesse pelo link: <a href="{{ $url }}">{{ $url }}</a>
        </p>

        <div class="tips">
            <div class="tips-title">Antes de começar</div>
            <ul>
                <li>Reserve um momento tranquilo, sem interrupções.</li>
                <li>Não há respostas certas ou erradas — responda com sinceridade.</li>
                <li>O mapeamento é baseado na sua percepção sobre si mesmo(a) no contexto de trabalho.</li>
            </ul>
        </div>
    </div>

    <div class="footer">
        <p>Este é um e-mail automático. Por favor, não responda a esta mensagem.</p>
        <p>&copy; {{ date('Y') }} Sara Linhar Consultoria em Desenvolvimento Humano e Organizacional.</p>
    </div>

</div>
</body>
</html>
