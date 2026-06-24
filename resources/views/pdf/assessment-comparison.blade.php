<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Relatório Comparativo — {{ $jobTitle ?? 'Candidatos' }}</title>
    <style>
        @page {
            margin: 0;
            size: A4 landscape;
        }
        body {
            font-family: 'Helvetica', 'Arial', sans-serif;
            color: #2d2d2d;
            font-size: 10px;
            line-height: 1.5;
            margin-top: 2.5cm;
            margin-bottom: 1.8cm;
            margin-left: 1.5cm;
            margin-right: 1.5cm;
        }

        /* ── Header fixo ── */
        header {
            position: fixed;
            top: 0; left: 0; right: 0;
            height: 2cm;
            background-color: #003366;
            color: #fff;
            padding: 0 1.5cm;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .header-left .title  { font-size: 15px; font-weight: bold; letter-spacing: 0.5px; }
        .header-left .sub    { font-size: 9px; opacity: .75; margin-top: 2px; }
        .header-right        { font-size: 9px; opacity: .65; text-align: right; }

        /* ── Footer fixo ── */
        footer {
            position: fixed;
            bottom: 0; left: 0; right: 0;
            height: 1.4cm;
            background-color: #f5f5f5;
            border-top: 1px solid #e0e0e0;
            padding: 0 1.5cm;
            display: flex;
            align-items: center;
            justify-content: space-between;
            font-size: 9px;
            color: #888;
        }

        /* ── Seção ── */
        .section { margin-bottom: 22px; }
        h2 {
            font-size: 11px;
            font-weight: bold;
            color: #003366;
            text-transform: uppercase;
            letter-spacing: .8px;
            border-bottom: 2px solid #e67e22;
            padding-bottom: 4px;
            margin-bottom: 12px;
        }

        /* ── Tabela de ranking ── */
        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 9.5px;
        }
        thead tr {
            background-color: #003366;
            color: #fff;
        }
        thead th {
            padding: 6px 8px;
            text-align: left;
            font-weight: bold;
            font-size: 9px;
            letter-spacing: .4px;
        }
        thead th.center { text-align: center; }
        tbody tr:nth-child(even) { background-color: #f8f9fb; }
        tbody tr:nth-child(odd)  { background-color: #ffffff; }
        tbody td {
            padding: 6px 8px;
            border-bottom: 1px solid #eee;
            vertical-align: middle;
        }
        tbody td.center { text-align: center; }

        /* ── Score badge ── */
        .score-badge {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 10px;
            font-weight: bold;
            font-size: 9.5px;
            background-color: #fff3e0;
            color: #e67e22;
        }
        .score-badge.alto    { background-color: #d1fae5; color: #065f46; }
        .score-badge.medio   { background-color: #fff3e0; color: #b45309; }
        .score-badge.baixo   { background-color: #fee2e2; color: #991b1b; }

        /* ── Rank badge ── */
        .rank-1 { font-size: 13px; }
        .rank-2 { font-size: 11px; }
        .rank-3 { font-size: 10px; }
        .rank-n { color: #888; }

        /* ── Barra de dimensão ── */
        .dim-bar-outer {
            background-color: #e8eef5;
            border-radius: 4px;
            height: 8px;
            width: 100%;
            overflow: hidden;
        }
        .dim-bar-inner {
            height: 100%;
            border-radius: 4px;
            background-color: #003366;
        }

        /* ── Disclaimer ── */
        .disclaimer {
            font-size: 8px;
            color: #aaa;
            border-top: 1px solid #eee;
            padding-top: 10px;
            margin-top: 24px;
            line-height: 1.5;
        }

        /* ── Medallha de posição ── */
        .medal { font-size: 13px; }
    </style>
</head>
<body>

<header>
    <div class="header-left">
        <div class="title">Relatório Comparativo de Candidatos</div>
        <div class="sub">
            {{ $testName }}
            @if($jobTitle) — Vaga: {{ $jobTitle }}@endif
            @if($client) — {{ $client->name }}@endif
        </div>
    </div>
    <div class="header-right">
        Sara Linhar Consultoria<br>
        Gerado em {{ now()->format('d/m/Y H:i') }}<br>
        {{ $candidates->count() }} candidato{{ $candidates->count() > 1 ? 's' : '' }}
    </div>
</header>

<footer>
    <span>Sara Linhar Consultoria em Desenvolvimento Humano e Organizacional</span>
    <span>Documento confidencial — uso interno</span>
</footer>

<!-- ── Ranking Geral ── -->
<div class="section">
    <h2>Ranking por Score Global</h2>
    <table>
        <thead>
            <tr>
                <th style="width:36px" class="center">#</th>
                <th>Candidato</th>
                <th>E-mail</th>
                <th class="center" style="width:70px">Score</th>
                <th class="center" style="width:80px">Classificação</th>
                <th class="center" style="width:60px">IQ</th>
                <th class="center" style="width:90px">Respondido em</th>
                @if(isset($candidates[0]['metadata']['job_title']))
                <th>Vaga</th>
                @endif
            </tr>
        </thead>
        <tbody>
            @foreach($candidates as $rank => $c)
            @php
                $app    = $c['app'];
                $result = $c['result'];
                $score  = $c['overall_score'];
                $cls    = $result?->classification ?? '—';
                $iq     = $result?->quality_index  ?? null;
                $medals = ['🥇','🥈','🥉'];
                $badgeCls = $score >= 75 ? 'alto' : ($score >= 50 ? 'medio' : 'baixo');
            @endphp
            <tr>
                <td class="center">
                    @if($rank < 3)
                        <span class="medal">{{ $medals[$rank] }}</span>
                    @else
                        <span class="rank-n">{{ $rank + 1 }}º</span>
                    @endif
                </td>
                <td><strong>{{ $app->respondent_name ?? $app->candidate?->name ?? '—' }}</strong></td>
                <td style="color:#555">{{ $app->respondent_email ?? $app->candidate?->email ?? '—' }}</td>
                <td class="center">
                    <span class="score-badge {{ $badgeCls }}">{{ $score }}</span>
                </td>
                <td class="center" style="color:#444">{{ $cls }}</td>
                <td class="center" style="color:#888">
                    {{ $iq !== null ? number_format($iq, 0) . '%' : '—' }}
                </td>
                <td class="center" style="color:#888">
                    {{ $app->completed_at ? \Carbon\Carbon::parse($app->completed_at)->format('d/m/Y') : '—' }}
                </td>
                @if(isset($candidates[0]['metadata']['job_title']))
                <td style="color:#666">{{ $app->metadata['job_title'] ?? '—' }}</td>
                @endif
            </tr>
            @endforeach
        </tbody>
    </table>
</div>

<!-- ── Comparação por Dimensão ── -->
<div class="section">
    <h2>Comparação por Dimensão</h2>
    <table>
        <thead>
            <tr>
                <th style="width:140px">Dimensão</th>
                @foreach($candidates as $c)
                <th class="center">{{ \Str::limit($c['app']->respondent_name ?? $c['app']->candidate?->name ?? '—', 18) }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @foreach($allDimensions as $dim)
            <tr>
                <td><strong>{{ $dim['name'] }}</strong></td>
                @foreach($candidates as $c)
                @php
                    $dimData = collect($c['dimensions'])->firstWhere('slug', $dim['slug']);
                    $val     = $dimData['score'] ?? null;
                    $pct     = $val !== null ? min(100, ($val / 100) * 100) : 0;
                @endphp
                <td class="center">
                    @if($val !== null)
                        <div style="font-weight:bold;margin-bottom:3px;color:#003366">{{ $val }}</div>
                        <div class="dim-bar-outer">
                            <div class="dim-bar-inner" style="width:{{ $pct }}%"></div>
                        </div>
                    @else
                        <span style="color:#ccc">—</span>
                    @endif
                </td>
                @endforeach
            </tr>
            @endforeach
        </tbody>
    </table>
</div>

@if($disclaimer)
<div class="disclaimer">
    <strong>Nota técnica:</strong> {{ $disclaimer }}
</div>
@endif

</body>
</html>
