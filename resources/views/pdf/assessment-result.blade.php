<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>{{ $test->name }} — {{ $application->respondent_name ?? 'Respondente' }}</title>
    <style>
        @page {
            margin: 0;
        }
        body {
            font-family: 'Helvetica', 'Arial', sans-serif;
            color: #2d2d2d;
            font-size: 11px;
            line-height: 1.6;
            margin-top: 2.8cm;
            margin-bottom: 1.8cm;
            margin-left: 2cm;
            margin-right: 2cm;
        }

        /* ── Header fixo ── */
        header {
            position: fixed;
            top: 0; left: 0; right: 0;
            height: 2cm;
            background-color: #003366;
            color: #fff;
            padding: 0 2cm;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .header-left .title  { font-size: 16px; font-weight: bold; letter-spacing: 0.5px; }
        .header-left .sub    { font-size: 9px; opacity: .75; margin-top: 2px; }
        .header-right        { font-size: 9px; opacity: .65; text-align: right; }

        /* ── Footer fixo ── */
        footer {
            position: fixed;
            bottom: 0; left: 0; right: 0;
            height: 1.4cm;
            background-color: #f5f5f5;
            border-top: 1px solid #e0e0e0;
            padding: 0 2cm;
            display: flex;
            align-items: center;
            justify-content: space-between;
            font-size: 9px;
            color: #888;
        }

        /* ── Seções ── */
        .section { margin-bottom: 22px; }

        h2 {
            font-size: 12px;
            font-weight: bold;
            color: #003366;
            text-transform: uppercase;
            letter-spacing: .8px;
            border-bottom: 2px solid #e67e22;
            padding-bottom: 4px;
            margin-bottom: 12px;
        }

        /* ── Card de cabeçalho do candidato ── */
        .candidate-card {
            background: #f8f9fb;
            border: 1px solid #e4e6ea;
            border-radius: 4px;
            padding: 14px 16px;
            margin-bottom: 20px;
        }
        .candidate-name  { font-size: 17px; font-weight: bold; color: #003366; }
        .candidate-meta  { font-size: 10px; color: #666; margin-top: 4px; }
        .candidate-score {
            float: right;
            text-align: center;
            background: #fff;
            border: 2px solid #e67e22;
            border-radius: 6px;
            padding: 8px 16px;
            margin-top: -4px;
        }
        .score-value { font-size: 28px; font-weight: bold; color: #e67e22; display: block; line-height: 1; }
        .score-label { font-size: 9px; color: #888; }

        .classify-badge {
            display: inline-block;
            margin-top: 8px;
            padding: 3px 10px;
            border-radius: 20px;
            font-size: 9px;
            font-weight: bold;
            background: #fff3e0;
            color: #a05a10;
            border: 1px solid #f5c98e;
        }
        .clearfix::after { content: ''; display: table; clear: both; }

        /* ── Barras de dimensão ── */
        .dim-row { margin-bottom: 10px; }
        .dim-header {
            display: table;
            width: 100%;
            margin-bottom: 3px;
        }
        .dim-name  { display: table-cell; font-size: 10px; color: #333; }
        .dim-score { display: table-cell; text-align: right; font-size: 10px; font-weight: bold; color: #003366; width: 40px; }
        .dim-classify { display: table-cell; text-align: right; font-size: 9px; color: #888; width: 140px; padding-left: 8px; }

        .bar-bg   { background: #e9ecef; border-radius: 3px; height: 8px; width: 100%; }
        .bar-fill { background: #e67e22; border-radius: 3px; height: 8px; }

        /* ── Qualidade ── */
        .quality-row { display: table; width: 100%; }
        .quality-label { display: table-cell; font-size: 10px; width: 160px; }
        .quality-bar-wrap { display: table-cell; vertical-align: middle; }
        .quality-val   { display: table-cell; text-align: right; width: 32px; font-size: 10px; font-weight: bold; }

        @php
            $qColor = $quality_index >= 80 ? '#16a34a' : ($quality_index >= 60 ? '#ca8a04' : '#dc2626');
        @endphp

        /* ── Narrativa IA ── */
        .narrative-block {
            margin-bottom: 8px;
            border-radius: 4px;
            overflow: hidden;
        }
        .narrative-label {
            font-size: 8px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: .8px;
            padding: 4px 10px;
        }
        .narrative-text {
            font-size: 10.5px;
            line-height: 1.7;
            color: #333;
            text-align: justify;
            padding: 8px 12px 10px 12px;
        }
        /* Cores por bloco */
        .nb-perfil    { border: 1px solid #bfdbfe; }
        .nb-perfil    .narrative-label { background: #dbeafe; color: #1d4ed8; }
        .nb-perfil    .narrative-text  { background: #eff6ff; }

        .nb-forca     { border: 1px solid #bbf7d0; }
        .nb-forca     .narrative-label { background: #dcfce7; color: #15803d; }
        .nb-forca     .narrative-text  { background: #f0fdf4; }

        .nb-opor      { border: 1px solid #fef08a; }
        .nb-opor      .narrative-label { background: #fefce8; color: #a16207; }
        .nb-opor      .narrative-text  { background: #fefce8; }

        .nb-rec       { border: 1px solid #fed7aa; }
        .nb-rec       .narrative-label { background: #fff7ed; color: #c2410c; }
        .nb-rec       .narrative-text  { background: #fff7ed; }

        /* Fallback texto corrido */
        .narrative {
            background: #fafafa;
            border-left: 3px solid #e67e22;
            padding: 12px 16px;
            font-size: 10.5px;
            line-height: 1.7;
            color: #333;
            text-align: justify;
            border-radius: 0 4px 4px 0;
        }

        /* ── Disclaimer ── */
        .disclaimer {
            background: #f8f8f8;
            border: 1px solid #ddd;
            border-radius: 4px;
            padding: 10px 14px;
            font-size: 8.5px;
            color: #777;
            line-height: 1.5;
        }

        .flag-warn {
            background: #fff7ed;
            border: 1px solid #fed7aa;
            border-radius: 4px;
            padding: 8px 12px;
            font-size: 9.5px;
            color: #92400e;
            margin-bottom: 14px;
        }

        .page-break { page-break-after: always; }
    </style>
</head>
<body>

<header>
    <div class="header-left">
        <div class="title">MAPEAMENTO COMPORTAMENTAL</div>
        <div class="sub">Sara Linhar Consultoria DHO & R&S</div>
    </div>
    <div class="header-right">
        Gerado em {{ now()->format('d/m/Y \à\s H:i') }}
    </div>
</header>

<footer>
    <span>Sara Linhar Consultoria em Desenvolvimento Humano e Organizacional</span>
    <span>Documento confidencial — uso interno</span>
</footer>

{{-- ── Dados do respondente + score geral ── --}}
<div class="section">
    <div class="candidate-card clearfix">
        <div class="candidate-score">
            <span class="score-value">{{ number_format($result->overall_score, 0) }}</span>
            <span class="score-label">/ 100</span>
            <br>
            <span class="classify-badge">{{ $result->classification }}</span>
        </div>

        <div class="candidate-name">{{ $application->respondent_name ?? 'Respondente não identificado' }}</div>
        <div class="candidate-meta">
            @if($application->respondent_email) {{ $application->respondent_email }}@endif
            @if($application->respondent_email && $jobTitle) &nbsp;·&nbsp; @endif
            @if($jobTitle) {{ $jobTitle }} @endif
        </div>
        <div class="candidate-meta" style="margin-top:4px;">
            Instrumento: <strong>{{ $test->name }}</strong>
            &nbsp;·&nbsp; Respondido em {{ \Carbon\Carbon::parse($result->calculated_at)->format('d/m/Y') }}
            @if($application->recruitment_client?->name)
                &nbsp;·&nbsp; Empresa: {{ $application->recruitment_client->name }}
            @endif
        </div>
    </div>
</div>

{{-- ── Alerta de qualidade baixa ── --}}
@if($quality_index < 70 || count($flags ?? []) > 0)
<div class="flag-warn">
    ⚠ Índice de qualidade baixo ({{ $quality_index }}/100).
    @if(in_array('time_very_low', $flags ?? []) || in_array('time_low', $flags ?? []))
        Tempo de resposta abaixo do esperado.
    @endif
    @if(in_array('repetition_very_high', $flags ?? []) || in_array('repetition_high', $flags ?? []))
        Padrão de respostas repetitivo detectado.
    @endif
    Os resultados devem ser interpretados com cautela.
</div>
@endif

{{-- ── Dimensões ── --}}
<div class="section">
    <h2>Resultados por Dimensão</h2>
    @foreach($ranked_dimensions as $slug => $dim)
    <div class="dim-row">
        <div class="dim-header">
            <span class="dim-name">{{ $dim['name'] }}</span>
            <span class="dim-score">{{ number_format($dim['score'], 0) }}</span>
            <span class="dim-classify">{{ $dim['classification'] }}</span>
        </div>
        <div class="bar-bg">
            <div class="bar-fill" style="width: {{ min(100, $dim['score']) }}%;"></div>
        </div>
    </div>
    @endforeach
</div>

{{-- ── Score geral + qualidade ── --}}
<div class="section">
    <h2>Índice de Qualidade do Preenchimento</h2>
    <div class="quality-row">
        <span class="quality-label">Qualidade de resposta</span>
        <span class="quality-bar-wrap">
            <div class="bar-bg">
                <div class="bar-fill" style="width: {{ $quality_index }}%; background: {{ $qColor }};"></div>
            </div>
        </span>
        <span class="quality-val" style="color: {{ $qColor }};">{{ $quality_index }}</span>
    </div>
</div>

{{-- ── Análise narrativa IA ── --}}
@if($result->ai_narrative)
<div class="page-break"></div>
<div class="section">
    <h2>Análise Consultiva</h2>

    @php
        $narrativeSections = [
            ['class' => 'nb-perfil', 'label' => 'Perfil Geral'],
            ['class' => 'nb-forca',  'label' => 'Pontos de Força'],
            ['class' => 'nb-opor',   'label' => 'Oportunidades de Desenvolvimento'],
            ['class' => 'nb-rec',    'label' => 'Recomendação Prática'],
        ];
        $paragraphs = array_values(array_filter(
            preg_split('/\n\s*\n/', $result->ai_narrative),
            fn($p) => trim($p) !== ''
        ));
        $structured = count($paragraphs) === 4;
    @endphp

    @if($structured)
        @foreach($paragraphs as $i => $para)
        <div class="narrative-block {{ $narrativeSections[$i]['class'] }}">
            <div class="narrative-label">{{ $narrativeSections[$i]['label'] }}</div>
            <div class="narrative-text">{{ trim($para) }}</div>
        </div>
        @endforeach
    @else
        <div class="narrative">
            {!! nl2br(e($result->ai_narrative)) !!}
        </div>
    @endif
</div>
@endif

{{-- ── Disclaimer ── --}}
<div class="section">
    <div class="disclaimer">
        <strong>AVISO LEGAL:</strong> {{ $test->disclaimer }}
    </div>
</div>

</body>
</html>
