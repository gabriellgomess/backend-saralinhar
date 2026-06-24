<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Resultado Teste Culture Fit - {{ $result->testee_name }}</title>
    <style>
        @page {
            margin: 0cm 0cm;
        }
        body {
            font-family: 'Helvetica', 'Arial', sans-serif;
            color: #333;
            line-height: 1.5;
            margin-top: 3cm;
            margin-bottom: 2cm;
            margin-left: 2cm;
            margin-right: 2cm;
            font-size: 12px;
        }

        header {
            position: fixed;
            top: 0cm;
            left: 0cm;
            right: 0cm;
            height: 2cm;
            background-color: #003366;
            color: white;
            padding: 0.5cm 2cm;
            display: flex;
            align-items: center;
        }

        footer {
            position: fixed;
            bottom: 0cm;
            left: 0cm;
            right: 0cm;
            height: 1.5cm;
            background-color: #f8f9fa;
            color: #666;
            text-align: center;
            line-height: 1.5cm;
            font-size: 10px;
            border-top: 1px solid #e0e0e0;
        }

        .header-content {
            width: 100%;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .header-title {
            font-size: 20px;
            font-weight: bold;
            text-transform: uppercase;
        }

        .header-subtitle {
            font-size: 12px;
            opacity: 0.9;
        }

        h1 {
            font-size: 24px;
            color: #003366;
            margin-bottom: 20px;
            border-bottom: 2px solid #e67e22;
            padding-bottom: 10px;
        }

        h2 {
            font-size: 18px;
            color: #003366;
            margin-top: 25px;
            margin-bottom: 15px;
            background-color: #f0f4f8;
            padding: 8px 12px;
            border-left: 4px solid #003366;
        }

        .info-grid {
            width: 100%;
            margin-bottom: 30px;
            border-collapse: collapse;
        }

        .info-grid td {
            padding: 8px;
            border-bottom: 1px solid #e0e0e0;
        }

        .info-label {
            font-weight: bold;
            color: #666;
            width: 140px;
        }

        .info-value {
            color: #333;
        }

        .scores-table {
            width: 100%;
            margin: 20px 0;
            border-collapse: collapse;
            text-align: center;
        }

        .scores-table th {
            background-color: #003366;
            color: white;
            padding: 10px;
            font-size: 14px;
        }

        .scores-table td {
            padding: 15px;
            border: 1px solid #e0e0e0;
            font-size: 14px;
        }

        .score-large {
            font-size: 20px;
            font-weight: bold;
            color: #003366;
            display: block;
        }

        .score-percent {
            font-size: 12px;
            color: #e67e22;
            font-weight: bold;
        }

        .dimension-desc {
            font-size: 10px;
            color: #666;
            margin-top: 5px;
        }

        .profile-box {
            background-color: #f9f9f9;
            border: 1px solid #e0e0e0;
            padding: 20px;
            border-radius: 5px;
            margin-bottom: 20px;
        }

        .profile-main {
            font-size: 16px;
            color: #003366;
            margin-bottom: 10px;
        }

        .text-content {
            text-align: justify;
            margin-bottom: 15px;
        }

        .list-clean {
            list-style: none;
            padding: 0;
        }

        .list-clean li {
            padding: 5px 0 5px 15px;
            position: relative;
        }

        .list-clean li::before {
            content: "•";
            color: #e67e22;
            font-weight: bold;
            position: absolute;
            left: 0;
        }

        .page-break {
            page-break-after: always;
        }
        
        .page-break-before {
            page-break-before: always;
        }

        .legend-box {
            font-size: 11px;
            color: #555;
            background-color: #fff;
            border: 1px solid #e0e0e0;
            padding: 10px;
            margin-bottom: 15px;
            border-radius: 4px;
        }

        .legend-blue {
            color: #003366;
            font-weight: bold;
        }

        .legend-orange {
            color: #e67e22;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <header>
        <table style="width: 100%; border: none;">
            <tr>
                <td style="width: 50%; text-align: left; vertical-align: middle; border: none;">
                    @php
                        $path = public_path('img/logo_horizontal_white.png');
                        $type = pathinfo($path, PATHINFO_EXTENSION);
                        $data = file_get_contents($path);
                        $base64 = 'data:image/' . $type . ';base64,' . base64_encode($data);
                    @endphp
                    <img src="{{ $base64 }}" alt="Sara Linhar" style="height: 70px;">
                </td>
                <td style="width: 50%; text-align: right; vertical-align: middle; border: none;">
                    <div class="header-subtitle" style="color: white;">Relatório de Adequação Cultural (Culture Fit)</div>
                </td>
            </tr>
        </table>
    </header>

    <footer>
        Sara Linhar - Desenvolvimento Humano e Organizacional | Relatório Gerado em {{ date('d/m/Y') }}
    </footer>

    <!-- Capa / Informações Iniciais -->
    <h1>Resultado da Avaliação</h1>

    <table class="info-grid">
        <tr>
            <td class="info-label">Nome:</td>
            <td class="info-value">{{ $result->testee_name ?? 'N/A' }}</td>
        </tr>
        <tr>
            <td class="info-label">E-mail:</td>
            <td class="info-value">{{ $result->testee_email ?? 'N/A' }}</td>
        </tr>
        @if($result->testee_cpf)
        <tr>
            <td class="info-label">CPF:</td>
            <td class="info-value">{{ $result->testee_cpf }}</td>
        </tr>
        @endif
        @if($result->testee_phone)
        <tr>
            <td class="info-label">Telefone:</td>
            <td class="info-value">{{ $result->testee_phone }}</td>
        </tr>
        @endif
        @if($result->testee_position)
        <tr>
            <td class="info-label">Cargo/Posição:</td>
            <td class="info-value">{{ $result->testee_position }}</td>
        </tr>
        @endif
        <tr>
            <td class="info-label">Data do Teste:</td>
            <td class="info-value">{{ $result->created_at->format('d/m/Y H:i') }}</td>
        </tr>
    </table>

    @if($result->cultural_profile)
    <h2>Perfil Cultural Identificado</h2>
    <div class="profile-box">
        <div class="profile-main">
            <strong>{{ $result->cultural_profile }}</strong>
        </div>
    </div>
    @endif

    <h2>Pontuação por Dimensão</h2>
    <div class="legend-box">
        <span class="legend-blue">Números Azuis:</span> Pontuação absoluta (intensidade da característica).<br>
        <span class="legend-orange">Números Laranjas:</span> Representatividade percentual no perfil total.
    </div>
    <table class="scores-table">
        <thead>
            <tr>
                <th>Autonomia</th>
                <th>Inovação</th>
                <th>Hierarquia</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>
                    <span class="score-large">{{ $result->score_autonomy ?? 0 }}</span>
                    <span class="score-percent">{{ $percentages['autonomy'] ?? 0 }}%</span>
                    <div class="dimension-desc">Independência e autogestão</div>
                </td>
                <td>
                    <span class="score-large">{{ $result->score_innovation ?? 0 }}</span>
                    <span class="score-percent">{{ $percentages['innovation'] ?? 0 }}%</span>
                    <div class="dimension-desc">Abertura a novas ideias</div>
                </td>
                <td>
                    <span class="score-large">{{ $result->score_hierarchy ?? 0 }}</span>
                    <span class="score-percent">{{ $percentages['hierarchy'] ?? 0 }}%</span>
                    <div class="dimension-desc">Respeito à estrutura</div>
                </td>
            </tr>
        </tbody>
    </table>

    <table class="scores-table">
        <thead>
            <tr>
                <th>Trabalho em Equipe</th>
                <th>Resultados</th>
                <th>Flexibilidade</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>
                    <span class="score-large">{{ $result->score_teamwork ?? 0 }}</span>
                    <span class="score-percent">{{ $percentages['teamwork'] ?? 0 }}%</span>
                    <div class="dimension-desc">Colaboração e cooperação</div>
                </td>
                <td>
                    <span class="score-large">{{ $result->score_results ?? 0 }}</span>
                    <span class="score-percent">{{ $percentages['results'] ?? 0 }}%</span>
                    <div class="dimension-desc">Foco em metas</div>
                </td>
                <td>
                    <span class="score-large">{{ $result->score_flexibility ?? 0 }}</span>
                    <span class="score-percent">{{ $percentages['flexibility'] ?? 0 }}%</span>
                    <div class="dimension-desc">Adaptabilidade</div>
                </td>
            </tr>
        </tbody>
    </table>

    @if($result->ai_analysis)
    <h2 class="page-break-before">Análise Cultural Detalhada</h2>
    <div class="text-content">
        {!! nl2br(e($result->ai_analysis)) !!}
    </div>
    @endif

    @if($result->strengths)
    <h2 class="page-break-before">Pontos Fortes Culturais</h2>
    <ul class="list-clean">
        @foreach(explode(';', $result->strengths) as $strength)
            @if(trim($strength))
            <li>{{ trim($strength) }}</li>
            @endif
        @endforeach
    </ul>
    @endif

    @if($result->challenges)
    <h2>Desafios de Adaptação</h2>
    <ul class="list-clean">
        @foreach(explode(';', $result->challenges) as $challenge)
            @if(trim($challenge))
            <li>{{ trim($challenge) }}</li>
            @endif
        @endforeach
    </ul>
    @endif

    @if($result->ideal_environments)
    <h2>Ambientes Organizacionais Ideais</h2>
    <ul class="list-clean">
        @foreach(explode(';', $result->ideal_environments) as $env)
            @if(trim($env))
            <li>{{ trim($env) }}</li>
            @endif
        @endforeach
    </ul>
    @endif


</body>
</html>
