<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Parecer Player - {{ $report->candidate_name }}</title>
    <style>
        @page {
            margin: 30px 40px 80px 40px;
        }
        body {
            font-family: 'Helvetica', 'Arial', sans-serif;
            color: #333;
            line-height: 1.5;
            margin: 0;
            padding: 0;
            font-size: 12px;
        }

        /* --- HEADER --- */
        .header {
            width: 100%;
            margin-bottom: 30px;
        }
        .header-table {
            width: 100%;
            border: none;
        }
        .header-table td {
            vertical-align: top;
            border: none;
            padding: 0;
        }
        .logo-cell {
            text-align: right;
            width: 160px;
        }
        .logo-cell img {
            width: 140px;
            height: auto;
        }

        /* --- SECTION TITLES --- */
        .title-entrevista {
            font-size: 14px;
            font-weight: bold;
            color: #333;
            margin-bottom: 10px;
        }
        .title-relatorio {
            font-size: 12px;
            font-weight: bold;
            color: #333;
            margin-bottom: 8px;
        }
        .field-label {
            font-weight: bold;
            color: #333;
        }
        .section-title-underline {
            font-weight: bold;
            text-decoration: underline;
            color: #333;
            margin-top: 20px;
            margin-bottom: 10px;
            font-size: 12px;
        }
        .section-title-bold {
            font-weight: bold;
            color: #333;
            margin-top: 25px;
            margin-bottom: 10px;
            font-size: 13px;
        }

        /* --- CONTENT --- */
        .content-block {
            text-align: justify;
            margin-bottom: 8px;
            font-size: 12px;
        }
        .field-line {
            margin-bottom: 5px;
            font-size: 12px;
        }

        /* --- WORK HISTORY --- */
        .work-entry {
            margin-bottom: 10px;
            font-size: 12px;
        }

        /* --- FIT BOX --- */
        .fit-box {
            margin-top: 15px;
            margin-bottom: 15px;
            font-size: 12px;
        }

        /* --- SIGNATURE --- */
        .signature {
            margin-top: 60px;
            font-size: 12px;
        }
        .signature .name {
            font-weight: normal;
            color: #333;
        }
        .signature .role {
            font-weight: bold;
            color: #333;
        }

        /* --- FOOTER --- */
        .footer {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            text-align: center;
            font-size: 9px;
            color: #555;
            border-top: 1px solid #999;
            padding-top: 8px;
        }
        .footer .company-name {
            font-weight: bold;
            color: #c0392b;
        }
        .footer .contact-line {
            color: #666;
            margin-top: 3px;
        }

        /* Page break control */
        .no-break {
            page-break-inside: avoid;
        }
    </style>
</head>
<body>
    {{-- HEADER com Logo --}}
    <div class="header">
        <table class="header-table">
            <tr>
                <td>&nbsp;</td>
                <td class="logo-cell">
                    <img src="{{ public_path('img/logo_player.png') }}" alt="Player Consultoria em RH">
                </td>
            </tr>
        </table>
    </div>

    {{-- TÍTULO --}}
    <div class="title-entrevista">Entrevista de Seleção</div>
    <div class="title-relatorio">Relatório de parecer da entrevista – {{ $report->interview_date->format('d/m/Y') }}.</div>

    {{-- DADOS DO CANDIDATO --}}
    <div class="field-line">
        <span class="field-label">Candidato (a):</span> {{ $report->candidate_name }}
    </div>
    <div class="field-line">
        <span class="field-label">Cargo:</span> {{ $report->job ? $report->job->title : 'N/A' }}
    </div>

    @php
        $hasAge = !empty(trim($playerData['age'] ?? '')) && trim($playerData['age']) !== 'Não informado';
        $hasChildren = !empty(trim($playerData['children'] ?? '')) && trim($playerData['children']) !== 'Não informado';
        $hasMarital = !empty(trim($playerData['marital_status'] ?? '')) && trim($playerData['marital_status']) !== 'Não informado';
        $hasAddress = !empty(trim($playerData['address'] ?? '')) && trim($playerData['address']) !== 'Não informado';
        $hasCommute = !empty(trim($playerData['commute'] ?? '')) && trim($playerData['commute']) !== 'Não informado';
        $hasEducation = !empty(trim($playerData['education'] ?? '')) && trim($playerData['education']) !== 'Não informado';
        $hasSalary = !empty(trim($playerData['salary_expectation'] ?? '')) && trim($playerData['salary_expectation']) !== 'Não informado';
        
        $hasDadosPessoais = $hasAge || $hasChildren || $hasMarital || $hasAddress || $hasCommute || $hasEducation || $hasSalary;
    @endphp

    @if($hasDadosPessoais)
    {{-- DADOS PESSOAIS --}}
    <div class="section-title-underline">Dados Pessoais:</div>

    @if($hasAge)
    <div class="field-line">Idade: {{ $playerData['age'] }}</div>
    @endif

    @if($hasChildren)
    <div class="field-line">Filhos: {{ $playerData['children'] }}</div>
    @endif

    @if($hasMarital)
    <div class="field-line">Estado civil: {{ $playerData['marital_status'] }}</div>
    @endif

    @if($hasAddress)
    <div class="field-line">Endereço - Bairro/cidade: {{ $playerData['address'] }}</div>
    @endif

    @if($hasCommute)
    <div class="field-line">Deslocamento: {{ $playerData['commute'] }}</div>
    @endif

    @if($hasEducation)
    <div class="field-line">Escolaridade:</div>
    <div class="field-line">{{ $playerData['education'] }}</div>
    @endif

    @if($hasSalary)
    <div class="field-line" style="margin-top: 5px;">
        <span class="field-label">Pretensão salarial:</span> {{ $playerData['salary_expectation'] }}
    </div>
    @endif
    @endif

    @php
        $hasExp = !empty(trim($playerData['professional_experience'] ?? '')) && trim($playerData['professional_experience']) !== 'Não informado';
        $hasHistory = !empty($playerData['work_history']) && is_array($playerData['work_history']) && count(array_filter($playerData['work_history'], function($w) {
            return !empty(trim($w['company'] ?? '')) || !empty(trim($w['role'] ?? '')) || !empty(trim($w['period'] ?? '')) || !empty(trim($w['activities'] ?? '')) || !empty(trim($w['exit_reason'] ?? ''));
        })) > 0;
        $hasPage2 = $hasExp || $hasHistory;
    @endphp

    @if($hasPage2)
    {{-- PÁGINA 2: EXPERIÊNCIAS PROFISSIONAIS --}}
    <div style="page-break-before: always;"></div>

    <div class="header">
        <table class="header-table">
            <tr>
                <td>&nbsp;</td>
                <td class="logo-cell">
                    <img src="{{ public_path('img/logo_player.png') }}" alt="Player Consultoria em RH">
                </td>
            </tr>
        </table>
    </div>

    {{-- EXPERIÊNCIA PROFISSIONAL --}}
    <div class="section-title-underline">Experiência Profissional:</div>

    @if($hasExp)
    <div class="content-block">
        {!! nl2br(e($playerData['professional_experience'])) !!}
    </div>
    @endif

    {{-- HISTÓRICO DE TRABALHO (máximo 3) --}}
    @if($hasHistory)
        @foreach(array_slice($playerData['work_history'], 0, 3) as $work)
            @php
                $workCompany = trim($work['company'] ?? '');
                $workRole = trim($work['role'] ?? '');
                $workPeriod = trim($work['period'] ?? '');
                $workAct = trim($work['activities'] ?? '');
                $workExit = trim($work['exit_reason'] ?? '');
                $hasThisWork = !empty($workCompany) || !empty($workRole) || !empty($workPeriod) || !empty($workAct) || !empty($workExit);
            @endphp
            @if($hasThisWork)
            <div class="work-entry no-break">
                @if(!empty($workCompany))
                <div class="field-line">
                    <span class="field-label">Empresa:</span> {{ $work['company'] }}{{ !empty($work['location']) ? ', situada em ' . $work['location'] : '' }}.
                </div>
                @endif
                @if(!empty($workPeriod))
                <div class="field-line">
                    <span class="field-label">Período:</span> {{ $work['period'] }}
                </div>
                @endif
                @if(!empty($workRole))
                <div class="field-line">
                    <span class="field-label">Cargo:</span> {{ $work['role'] }}
                </div>
                @endif
                @if(!empty($workAct))
                <div class="field-line">
                    <span class="field-label">Atividades desenvolvidas:</span> {{ $work['activities'] }}
                </div>
                @endif
                @if(!empty($workExit))
                <div class="field-line">
                    <span class="field-label">Motivo de saída:</span> {{ $work['exit_reason'] }}
                </div>
                @endif
            </div>
            @endif
        @endforeach
    @endif
    @endif

    @php
        $hasStrengths = !empty(trim($playerData['strengths_text'] ?? '')) && trim($playerData['strengths_text']) !== 'Não informado';
        $hasDev = !empty(trim($playerData['development_text'] ?? '')) && trim($playerData['development_text']) !== 'Não informado';
        $hasChallenge = !empty(trim($playerData['professional_challenge'] ?? '')) && trim($playerData['professional_challenge']) !== 'Não informado';
        $hasSummary = !empty(trim($playerData['final_summary'] ?? $report->final_opinion ?? ''));
        $hasConclusion = !empty(trim($playerData['final_conclusion'] ?? '')) && trim($playerData['final_conclusion']) !== 'Não informado';
        
        $hasPage3 = $hasStrengths || $hasDev || $hasChallenge || $hasSummary || $hasConclusion;
    @endphp

    @if($hasPage3)
    {{-- PÁGINA 3: PONTOS FORTES EM DIANTE --}}
    <div style="page-break-before: always;"></div>

    <div class="header">
        <table class="header-table">
            <tr>
                <td>&nbsp;</td>
                <td class="logo-cell">
                    <img src="{{ public_path('img/logo_player.png') }}" alt="Player Consultoria em RH">
                </td>
            </tr>
        </table>
    </div>

    {{-- PONTOS FORTES --}}
    @if($hasStrengths)
    <div class="field-line" style="margin-top: 10px;">
        <span class="field-label">Pontos fortes:</span> {{ $playerData['strengths_text'] }}
    </div>
    @endif

    {{-- PONTOS A DESENVOLVER --}}
    @if($hasDev)
    <div class="field-line">
        <span class="field-label">Pontos a desenvolver:</span> {{ $playerData['development_text'] }}
    </div>
    @endif

    {{-- DESAFIO PROFISSIONAL --}}
    @if($hasChallenge)
    <div class="field-line">
        <span class="field-label">Desafio profissional:</span> {{ $playerData['professional_challenge'] }}
    </div>
    @endif

    {{-- RESUMO FINAL --}}
    @if($hasSummary)
    <div class="section-title-bold">Resumo Final</div>
    <div class="content-block">
        @if(!empty($playerData['final_summary']))
            {!! nl2br(e($playerData['final_summary'])) !!}
        @else
            {!! nl2br(e($report->final_opinion)) !!}
        @endif
    </div>
    @endif

    {{-- CONCLUSÃO FINAL --}}
    @if($hasConclusion)
    <div class="content-block" style="margin-top: 10px;">
        {{ $playerData['final_conclusion'] }}
    </div>
    @endif
    @endif

    {{-- ASSINATURA --}}
    <div class="signature">
        <div class="name">{{ $report->interviewer_name }}</div>
        <div class="role">Recrutadora</div>
    </div>

    {{-- FOOTER --}}
    <div class="footer">
        <div><span class="company-name">Player</span>  &ndash; Consultoria em RH &amp; Desenvolvimento Organizacional</div>
        <div class="contact-line">
            evaniaribeiro.player@gmail.com  |  (51) 98480-8725  |  linkedin.com/in/evaniaribeiro
        </div>
    </div>
</body>
</html>
