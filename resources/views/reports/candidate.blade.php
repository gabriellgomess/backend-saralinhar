<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Parecer Sara Linhar - {{ $report->candidate_name }}</title>
    <style>
        @page {
            margin: 40px 50px 60px 50px;
        }
        body {
            font-family: 'Helvetica', 'Arial', sans-serif;
            color: #334155; /* Slate 700 */
            line-height: 1.5;
            margin: 0;
            padding: 0;
            font-size: 11px;
            background-color: #ffffff;
        }
        
        /* --- HEADER --- */
        .header-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 25px;
        }
        .header-table td {
            vertical-align: middle;
            border: none;
            padding: 0;
        }
        .logo-cell {
            text-align: left;
            width: 50%;
        }
        .logo-cell img {
            height: 42px;
            width: auto;
        }
        .title-cell {
            text-align: right;
            width: 50%;
        }
        .title-main {
            font-size: 15px;
            font-weight: bold;
            color: #0f172a; /* Slate 900 */
            margin: 0;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .title-sub {
            font-size: 10px;
            color: #64748b; /* Slate 500 */
            margin: 2px 0 0 0;
            font-weight: bold;
            text-transform: uppercase;
        }

        /* --- METADATA TABLE --- */
        .meta-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
            background-color: #f8fafc; /* Slate 50 */
            border: 1px solid #e2e8f0; /* Slate 200 */
            border-radius: 6px;
        }
        .meta-table td {
            border: 1px solid #e2e8f0;
            padding: 8px 12px;
            font-size: 11px;
            color: #334155;
        }
        .meta-label {
            font-weight: bold;
            color: #1e293b; /* Slate 800 */
            background-color: #f1f5f9; /* Slate 100 */
            width: 18%;
        }
        
        /* --- BLOCK SECTIONS --- */
        .block-title {
            background-color: #f1f5f9; /* Slate 100 */
            color: #0f172a; /* Slate 900 */
            border-left: 4px solid #004d99; /* Brand Blue */
            padding: 6px 12px;
            font-weight: bold;
            font-size: 11px;
            text-transform: uppercase;
            margin-top: 25px;
            margin-bottom: 12px;
            letter-spacing: 0.5px;
        }
        
        /* --- DATA TABLES --- */
        .data-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
        }
        .data-table td {
            border: 1px solid #e2e8f0;
            padding: 8px 12px;
            vertical-align: top;
            color: #334155;
            font-size: 11px;
        }
        .label {
            font-weight: bold;
            color: #1e293b;
            background-color: #f8fafc;
            width: 25%;
        }
        .value {
            width: 75%;
            text-align: justify;
        }

        /* --- EXPERIENCES --- */
        .experience-header {
            background-color: #004d99;
            color: #ffffff;
            font-weight: bold;
            text-align: left;
            padding: 5px 12px;
            font-size: 10px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border-radius: 4px 4px 0 0;
        }
        .experience-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 18px;
            border-left: 1px solid #e2e8f0;
            border-right: 1px solid #e2e8f0;
            border-bottom: 1px solid #e2e8f0;
        }
        .experience-table td {
            border-bottom: 1px solid #f1f5f9;
            padding: 8px 12px;
            vertical-align: top;
            font-size: 11px;
        }
        .experience-table tr:last-child td {
            border-bottom: none;
        }
        .experience-label {
            font-weight: bold;
            color: #475569; /* Slate 600 */
            width: 25%;
            background-color: #f8fafc;
        }
        .experience-value {
            color: #1e293b;
            width: 75%;
            text-align: justify;
        }

        /* --- STATUS BOX --- */
        .status-badge {
            margin-top: 30px;
            padding: 12px;
            text-align: center;
            font-weight: bold;
            font-size: 12px;
            letter-spacing: 0.5px;
            border-radius: 6px;
            text-transform: uppercase;
        }
        
        /* --- FOOTER --- */
        .footer {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            text-align: center;
            font-size: 9px;
            color: #94a3b8; /* Slate 400 */
            border-top: 1px solid #e2e8f0;
            padding-top: 6px;
        }
        .no-break {
            page-break-inside: avoid;
        }
        .page-break {
            page-break-before: always;
        }
    </style>
</head>
<body>

    @if(!empty($report->sara_data))
        @php
            $saraData = $report->sara_data;
            $personalInfo = $saraData['personal_info'] ?? [];
            $experiences = $saraData['experiences'] ?? [];
            $educationDev = $saraData['education_development'] ?? [];
            $evaluation = $saraData['professional_personal_evaluation'] ?? [];
            $financial = $saraData['financial'] ?? [];
            $logistics = $saraData['logistics_availability'] ?? [];
            $interviewer = $saraData['interviewer_evaluation'] ?? [];
        @endphp

        {{-- HEADER COM LOGO --}}
        <table class="header-table">
            <tr>
                <td class="logo-cell">
                    @if(file_exists(public_path('img/logo_horizontal.png')))
                        <img src="{{ public_path('img/logo_horizontal.png') }}" alt="Sara Linhar DHO RH">
                    @else
                        <span style="font-size: 18px; font-weight: bold; color: #004d99;">SARA LINHAR</span>
                    @endif
                </td>
                <td class="title-cell">
                    <h1 class="title-main">Formulário de Entrevista</h1>
                    <div class="title-sub">Sara Linhar DHO & RH</div>
                </td>
            </tr>
        </table>

        {{-- METADATA --}}
        <table class="meta-table">
            <tr>
                <td class="meta-label">Vaga:</td>
                <td style="width: 32%;">{{ $report->job ? $report->job->title : 'N/A' }}</td>
                <td class="meta-label">Data Entrevista:</td>
                <td style="width: 32%;">{{ $report->interview_date->format('d/m/Y') }}</td>
            </tr>
            <tr>
                <td class="meta-label">Cliente:</td>
                <td colspan="3">{{ $report->client ? $report->client->name : 'N/A' }}</td>
            </tr>
            <tr>
                <td class="meta-label">Entrevistador:</td>
                <td colspan="3">{{ $report->interviewer_name }}</td>
            </tr>
        </table>

        @php
            $hasName = !empty(trim($personalInfo['name'] ?? $report->candidate_name ?? ''));
            $hasAge = !empty(trim($personalInfo['age'] ?? '')) && trim($personalInfo['age']) !== 'Não informado';
            $hasMarital = !empty(trim($personalInfo['marital_status'] ?? '')) && trim($personalInfo['marital_status']) !== 'Não informado';
            $hasCity = !empty(trim($personalInfo['city'] ?? '')) && trim($personalInfo['city']) !== 'Não informado';
            $hasNeighborhood = !empty(trim($personalInfo['neighborhood'] ?? '')) && trim($personalInfo['neighborhood']) !== 'Não informado';
            $hasChildren = !empty(trim($personalInfo['children'] ?? '')) && trim($personalInfo['children']) !== 'Não informado';
            $hasFamily = !empty(trim($personalInfo['family_base'] ?? '')) && trim($personalInfo['family_base']) !== 'Não informado';
            $hasHobbies = !empty(trim($personalInfo['hobbies'] ?? '')) && trim($personalInfo['hobbies']) !== 'Não informado';
            
            $hasBlock1 = $hasName || $hasAge || $hasMarital || $hasCity || $hasNeighborhood || $hasChildren || $hasFamily || $hasHobbies;
        @endphp

        @if($hasBlock1)
        {{-- BLOCO 1 --}}
        <div class="block-title">BLOCO 1 – Informações Pessoais e Contexto de Vida</div>
        <table class="data-table">
            @if($hasName)
            <tr>
                <td class="label">Nome:</td>
                <td class="value" style="font-weight: bold; color: #0f172a;">{{ $personalInfo['name'] ?? $report->candidate_name }}</td>
            </tr>
            @endif
            @if($hasAge)
            <tr>
                <td class="label">Idade:</td>
                <td class="value">{{ $personalInfo['age'] }}</td>
            </tr>
            @endif
            @if($hasMarital)
            <tr>
                <td class="label">Estado civil:</td>
                <td class="value">{{ $personalInfo['marital_status'] }}</td>
            </tr>
            @endif
            @if($hasCity)
            <tr>
                <td class="label">Cidade:</td>
                <td class="value">{{ $personalInfo['city'] }}</td>
            </tr>
            @endif
            @if($hasNeighborhood)
            <tr>
                <td class="label">Bairro:</td>
                <td class="value">{{ $personalInfo['neighborhood'] }}</td>
            </tr>
            @endif
            @if($hasChildren)
            <tr>
                <td class="label">Possui filhos (idades):</td>
                <td class="value">{{ $personalInfo['children'] }}</td>
            </tr>
            @endif
            @if($hasFamily)
            <tr>
                <td class="label">Base familiar (com quem reside):</td>
                <td class="value">{{ $personalInfo['family_base'] }}</td>
            </tr>
            @endif
            @if($hasHobbies)
            <tr>
                <td class="label">Hobbies / interesses:</td>
                <td class="value">{{ $personalInfo['hobbies'] }}</td>
            </tr>
            @endif
        </table>
        @endif

        @php
            $hasExperiences = false;
            if (!empty($experiences) && is_array($experiences)) {
                foreach($experiences as $exp) {
                    if (!empty(trim($exp['company'] ?? '')) || !empty(trim($exp['role'] ?? ''))) {
                        $hasExperiences = true;
                        break;
                    }
                }
            }
            $hasExtras = !empty(trim($saraData['experience_extras'] ?? ''));
            $hasBlock2 = $hasExperiences || $hasExtras;
        @endphp

        @if($hasBlock2)
        {{-- BLOCO 2 --}}
        <div class="block-title">BLOCO 2 – Momento Profissional e Experiências</div>
        
        @if($hasExperiences)
            @foreach($experiences as $index => $exp)
                @php
                    $expCompany = trim($exp['company'] ?? '');
                    $expRole = trim($exp['role'] ?? '');
                    $expPeriod = trim($exp['period'] ?? '');
                    $expActivities = trim($exp['activities'] ?? '');
                    $expExit = trim($exp['exit_reason'] ?? '');
                    $expSalary = trim($exp['salary_benefits'] ?? '');
                    
                    $hasThisExp = !empty($expCompany) || !empty($expRole) || !empty($expPeriod) || !empty($expActivities) || !empty($expExit) || !empty($expSalary);
                @endphp
                @if($hasThisExp)
                <div class="no-break">
                    <div class="experience-header">Experiência - {{ $index + 1 }}</div>
                    <table class="experience-table">
                        @if(!empty($expCompany) && $expCompany !== 'Não informado')
                        <tr>
                            <td class="experience-label">Empresa:</td>
                            <td class="experience-value" style="font-weight: bold; color: #0f172a;">{{ $exp['company'] }}</td>
                        </tr>
                        @endif
                        @if(!empty($expRole) && $expRole !== 'Não informado')
                        <tr>
                            <td class="experience-label">Cargo:</td>
                            <td class="experience-value">{{ $exp['role'] }}</td>
                        </tr>
                        @endif
                        @if(!empty($expPeriod) && $expPeriod !== 'Não informado')
                        <tr>
                            <td class="experience-label">Período:</td>
                            <td class="experience-value">{{ $exp['period'] }}</td>
                        </tr>
                        @endif
                        @if(!empty($expActivities) && $expActivities !== 'Não informado')
                        <tr>
                            <td class="experience-label">Principais atividades:</td>
                            <td class="experience-value">{!! nl2br(e($exp['activities'])) !!}</td>
                        </tr>
                        @endif
                        @if(!empty($expExit) && $expExit !== 'Não informado')
                        <tr>
                            <td class="experience-label">Motivo da saída:</td>
                            <td class="experience-value">{{ $exp['exit_reason'] }}</td>
                        </tr>
                        @endif
                        @if(!empty($expSalary) && $expSalary !== 'Não informado')
                        <tr>
                            <td class="experience-label">Última Remuneração:</td>
                            <td class="experience-value">{{ $exp['salary_benefits'] }}</td>
                        </tr>
                        @endif
                    </table>
                </div>
                @endif
            @endforeach
        @endif

        @if($hasExtras)
            <div class="no-break" style="margin-top: 15px;">
                <div class="experience-header">Experiência - Extras</div>
                <table class="experience-table">
                    <tr>
                        <td class="experience-value" style="width: 100%;">{!! nl2br(e($saraData['experience_extras'])) !!}</td>
                    </tr>
                </table>
            </div>
        @endif
        @endif

           @php
            $hasEdu = !empty(trim($educationDev['education'] ?? '')) && trim($educationDev['education']) !== 'Não informado';
            $hasCourses = !empty(trim($educationDev['courses'] ?? '')) && trim($educationDev['courses']) !== 'Não informado';
            $hasCert = !empty(trim($educationDev['certifications'] ?? '')) && trim($educationDev['certifications']) !== 'Não informado';
            $hasTools = !empty(trim($educationDev['tools_systems'] ?? '')) && trim($educationDev['tools_systems']) !== 'Não informado';
            $hasBlock3 = $hasEdu || $hasCourses || $hasCert || $hasTools;

            $hasDesc = !empty(trim($evaluation['professional_self_description'] ?? '')) && trim($evaluation['professional_self_description']) !== 'Não informado';
            $hasStrengths = !empty(trim($evaluation['strengths_text'] ?? '')) && trim($evaluation['strengths_text']) !== 'Não informado';
            $hasChallenge = !empty(trim($evaluation['overcome_challenge'] ?? '')) && trim($evaluation['overcome_challenge']) !== 'Não informado';
            $hasDev = !empty(trim($evaluation['development_text'] ?? '')) && trim($evaluation['development_text']) !== 'Não informado';
            $hasIdeal = !empty(trim($evaluation['ideal_work_environment'] ?? '')) && trim($evaluation['ideal_work_environment']) !== 'Não informado';
            $hasBlock4 = $hasDesc || $hasStrengths || $hasChallenge || $hasDev || $hasIdeal;

            $hasSalary = !empty(trim($financial['salary_expectation'] ?? '')) && trim($financial['salary_expectation']) !== 'Não informado';
            $hasModel = !empty(trim($financial['pj_or_clt'] ?? '')) && trim($financial['pj_or_clt']) !== 'Não informado';
            $hasBenefits = !empty(trim($financial['benefits_expectation'] ?? '')) && trim($financial['benefits_expectation']) !== 'Não informado';
            $hasBlock5 = $hasSalary || $hasModel || $hasBenefits;

            $hasCommute = !empty(trim($logistics['commute'] ?? '')) && trim($logistics['commute']) !== 'Não informado';
            $hasSchedule = !empty(trim($logistics['schedule_availability'] ?? '')) && trim($logistics['schedule_availability']) !== 'Não informado';
            $hasStart = !empty(trim($logistics['start_availability'] ?? '')) && trim($logistics['start_availability']) !== 'Não informado';
            $hasBlock6 = $hasCommute || $hasSchedule || $hasStart;

            $hasComm = !empty(trim($interviewer['interviewer_communication'] ?? '')) && trim($interviewer['interviewer_communication']) !== 'Não informado';
            $hasPosture = !empty(trim($interviewer['interviewer_posture'] ?? '')) && trim($interviewer['interviewer_posture']) !== 'Não informado';
            $hasBlock7 = $hasComm || $hasPosture;
        @endphp

        @if($hasBlock3 || $hasBlock4 || $hasBlock5 || $hasBlock6 || $hasBlock7)
        <div class="page-break"></div>

        {{-- RE-HEADER PARA PÁGINA 2 --}}
        <table class="header-table">
            <tr>
                <td class="logo-cell">
                    @if(file_exists(public_path('img/logo_horizontal.png')))
                        <img src="{{ public_path('img/logo_horizontal.png') }}" alt="Sara Linhar DHO RH">
                    @else
                        <span style="font-size: 18px; font-weight: bold; color: #004d99;">SARA LINHAR</span>
                    @endif
                </td>
                <td class="title-cell">
                    <h1 class="title-main">Formulário de Entrevista</h1>
                    <div class="title-sub">Sara Linhar DHO & RH</div>
                </td>
            </tr>
        </table>
        @endif

        {{-- BLOCO 3 --}}
        @if($hasBlock3)
        <div class="block-title">BLOCO 3 – Formação e Desenvolvimento</div>
        <table class="data-table">
            @if($hasEdu)
            <tr>
                <td class="label">Escolaridade:</td>
                <td class="value">{{ $educationDev['education'] }}</td>
            </tr>
            @endif
            @if($hasCourses)
            <tr>
                <td class="label">Cursos:</td>
                <td class="value">{!! nl2br(e($educationDev['courses'])) !!}</td>
            </tr>
            @endif
            @if($hasCert)
            <tr>
                <td class="label">Certificações:</td>
                <td class="value">{!! nl2br(e($educationDev['certifications'])) !!}</td>
            </tr>
            @endif
            @if($hasTools)
            <tr>
                <td class="label">Ferramentas / sistemas:</td>
                <td class="value">{!! nl2br(e($educationDev['tools_systems'])) !!}</td>
            </tr>
            @endif
        </table>
        @endif

        {{-- BLOCO 4 --}}
        @if($hasBlock4)
        <div class="block-title">BLOCO 4 – Avaliação Profissional e Pessoal</div>
        <table class="data-table">
            @if($hasDesc)
            <tr>
                <td class="label">Como se descreve:</td>
                <td class="value">{!! nl2br(e($evaluation['professional_self_description'])) !!}</td>
            </tr>
            @endif
            @if($hasStrengths)
            <tr>
                <td class="label">Pontos fortes:</td>
                <td class="value">{!! nl2br(e($evaluation['strengths_text'])) !!}</td>
            </tr>
            @endif
            @if($hasChallenge)
            <tr>
                <td class="label">Desafio superado:</td>
                <td class="value">{!! nl2br(e($evaluation['overcome_challenge'])) !!}</td>
            </tr>
            @endif
            @if($hasDev)
            <tr>
                <td class="label">Pontos a desenvolver:</td>
                <td class="value">{!! nl2br(e($evaluation['development_text'])) !!}</td>
            </tr>
            @endif
            @if($hasIdeal)
            <tr>
                <td class="label">Ambiente ideal:</td>
                <td class="value">{!! nl2br(e($evaluation['ideal_work_environment'])) !!}</td>
            </tr>
            @endif
        </table>
        @endif

        {{-- BLOCO 5 --}}
        @if($hasBlock5)
        <div class="block-title">BLOCO 5 – Financeiro</div>
        <table class="data-table">
            @if($hasSalary)
            <tr>
                <td class="label">Pretensão salarial:</td>
                <td class="value">{{ $financial['salary_expectation'] }}</td>
            </tr>
            @endif
            @if($hasModel)
            <tr>
                <td class="label">PJ ou CLT:</td>
                <td class="value">{{ $financial['pj_or_clt'] }}</td>
            </tr>
            @endif
            @if($hasBenefits)
            <tr>
                <td class="label">Pretensão de benefícios:</td>
                <td class="value">{{ $financial['benefits_expectation'] }}</td>
            </tr>
            @endif
        </table>
        @endif

        {{-- BLOCO 6 --}}
        @if($hasBlock6)
        <div class="block-title">BLOCO 6 – Logística e Disponibilidade</div>
        <table class="data-table">
            @if($hasCommute)
            <tr>
                <td class="label">Deslocamento:</td>
                <td class="value">{{ $logistics['commute'] }}</td>
            </tr>
            @endif
            @if($hasSchedule)
            <tr>
                <td class="label">Disponibilidade horários:</td>
                <td class="value">{{ $logistics['schedule_availability'] }}</td>
            </tr>
            @endif
            @if($hasStart)
            <tr>
                <td class="label">Disponibilidade Início:</td>
                <td class="value">{{ $logistics['start_availability'] }}</td>
            </tr>
            @endif
        </table>
        @endif

        {{-- BLOCO 7 --}}
        @if($hasBlock7)
        <div class="block-title">BLOCO 7 – Avaliação do Entrevistador</div>
        <table class="data-table">
            @if($hasComm)
            <tr>
                <td class="label">Comunicação/Clareza:</td>
                <td class="value">{!! nl2br(e($interviewer['interviewer_communication'])) !!}</td>
            </tr>
            @endif
            @if($hasPosture)
            <tr>
                <td class="label">Postura/Engajamento:</td>
                <td class="value">{!! nl2br(e($interviewer['interviewer_posture'])) !!}</td>
            </tr>
            @endif
        </table>
        @endif

        @if(!empty(trim($report->summary ?? '')) && trim($report->summary) !== 'Não informado')
            <div class="block-title">Resumo Profissional Geral</div>
            <div style="padding: 10px 12px; text-align: justify; font-size: 11px; border: 1px solid #e2e8f0; margin-bottom: 15px; border-radius: 4px; line-height: 1.5;">
                {!! nl2br(e($report->summary)) !!}
            </div>
        @endif

        @php
            $statusLabel = [
                'recommended' => 'RECOMENDADO',
                'recommended_with_reservations' => 'RECOMENDADO COM RESSALVAS',
                'not_recommended' => 'NÃO RECOMENDADO'
            ];
            $statusColors = [
                'recommended' => '#16a34a', /* Emerald 600 */
                'recommended_with_reservations' => '#ca8a04', /* Yellow 600 */
                'not_recommended' => '#dc2626' /* Red 600 */
            ];
            $statusBg = [
                'recommended' => '#f0fdf4', /* Emerald 50 */
                'recommended_with_reservations' => '#fef9c3', /* Yellow 50 */
                'not_recommended' => '#fef2f2' /* Red 50 */
            ];
            $statusBorders = [
                'recommended' => '#bbf7d0', /* Emerald 200 */
                'recommended_with_reservations' => '#fef08a', /* Yellow 200 */
                'not_recommended' => '#fecaca' /* Red 200 */
            ];
            $color = $statusColors[$report->status] ?? '#475569';
            $bg = $statusBg[$report->status] ?? '#f8fafc';
            $borderColor = $statusBorders[$report->status] ?? '#cbd5e1';
        @endphp

        <div class="no-break status-badge" style="border: 1px solid {{ $borderColor }}; color: {{ $color }}; background-color: {{ $bg }};">
            AVALIAÇÃO FINAL: {{ $statusLabel[$report->status] ?? 'EM ANÁLISE' }}
        </div>

    @else
        {{-- FALLBACK PARA PARECERES LEGADOS --}}
        <table class="header-table">
            <tr>
                <td class="logo-cell">
                    @if(file_exists(public_path('img/logo_horizontal.png')))
                        <img src="{{ public_path('img/logo_horizontal.png') }}" alt="Sara Linhar DHO RH">
                    @else
                        <span style="font-size: 18px; font-weight: bold; color: #004d99;">SARA LINHAR</span>
                    @endif
                </td>
                <td class="title-cell">
                    <h1 class="title-main">Formulário de Entrevista</h1>
                    <div class="title-sub">Sara Linhar DHO & RH</div>
                </td>
            </tr>
        </table>

        <table class="meta-table">
            <tr>
                <td class="meta-label">Candidato:</td>
                <td>{{ $report->candidate_name }}</td>
                <td class="meta-label">Data:</td>
                <td>{{ $report->interview_date->format('d/m/Y') }}</td>
            </tr>
            <tr>
                <td class="meta-label">Vaga:</td>
                <td>{{ $report->job ? $report->job->title : 'N/A' }}</td>
                <td class="meta-label">Cliente:</td>
                <td>{{ $report->client ? $report->client->name : 'N/A' }}</td>
            </tr>
            <tr>
                <td class="meta-label">Entrevistador:</td>
                <td colspan="3">{{ $report->interviewer_name }}</td>
            </tr>
        </table>

        <div class="block-title">Resumo Profissional</div>
        <div style="padding: 10px 12px; text-align: justify; font-size: 11px; border: 1px solid #e2e8f0; margin-bottom: 15px; border-radius: 4px;">
            {{ $report->summary }}
        </div>

        <div class="block-title">Competências Técnicas</div>
        <div style="padding: 10px 12px; border: 1px solid #e2e8f0; margin-bottom: 15px; border-radius: 4px;">
            @if(is_array($report->technical_skills) && count($report->technical_skills) > 0)
                <ul style="margin: 0; padding-left: 20px;">
                    @foreach($report->technical_skills as $skill)
                        <li>{{ $skill }}</li>
                    @endforeach
                </ul>
            @else
                <p style="margin: 0; color: #64748b;">{{ is_string($report->technical_skills) ? $report->technical_skills : 'Nenhuma competência registrada.' }}</p>
            @endif
        </div>

        <div class="block-title">Postura Comportamental</div>
        <div style="padding: 10px 12px; text-align: justify; font-size: 11px; border: 1px solid #e2e8f0; margin-bottom: 15px; border-radius: 4px;">
            {{ $report->behavioral_posture }}
        </div>

        <div class="block-title">Pontos Fortes</div>
        <div style="padding: 10px 12px; border: 1px solid #e2e8f0; margin-bottom: 15px; border-radius: 4px;">
            @if(is_array($report->strengths) && count($report->strengths) > 0)
                <ul style="margin: 0; padding-left: 20px;">
                    @foreach($report->strengths as $strength)
                        <li>{{ $strength }}</li>
                    @endforeach
                </ul>
            @else
                <p style="margin: 0; color: #64748b;">{{ is_string($report->strengths) ? $report->strengths : 'Nenhum ponto forte registrado.' }}</p>
            @endif
        </div>

        <div class="block-title">Pontos a Desenvolver</div>
        <div style="padding: 10px 12px; border: 1px solid #e2e8f0; margin-bottom: 15px; border-radius: 4px;">
            @if(is_array($report->development_points) && count($report->development_points) > 0)
                <ul style="margin: 0; padding-left: 20px;">
                    @foreach($report->development_points as $point)
                        <li>{{ $point }}</li>
                    @endforeach
                </ul>
            @else
                <p style="margin: 0; color: #64748b;">{{ is_string($report->development_points) ? $report->development_points : 'Nenhum ponto a desenvolver registrado.' }}</p>
            @endif
        </div>

        <div class="block-title">Parecer Final</div>
        <div style="padding: 10px 12px; text-align: justify; font-size: 11px; border: 1px solid #e2e8f0; margin-bottom: 15px; border-radius: 4px;">
            {{ $report->final_opinion }}
        </div>

        @php
            $statusLabel = [
                'recommended' => 'RECOMENDADO',
                'recommended_with_reservations' => 'RECOMENDADO COM RESSALVAS',
                'not_recommended' => 'NÃO RECOMENDADO'
            ];
            $statusColors = [
                'recommended' => '#16a34a',
                'recommended_with_reservations' => '#ca8a04',
                'not_recommended' => '#dc2626'
            ];
            $statusBg = [
                'recommended' => '#f0fdf4',
                'recommended_with_reservations' => '#fef9c3',
                'not_recommended' => '#fef2f2'
            ];
            $statusBorders = [
                'recommended' => '#bbf7d0',
                'recommended_with_reservations' => '#fef08a',
                'not_recommended' => '#fecaca'
            ];
            $color = $statusColors[$report->status] ?? '#475569';
            $bg = $statusBg[$report->status] ?? '#f8fafc';
            $borderColor = $statusBorders[$report->status] ?? '#cbd5e1';
        @endphp

        <div class="no-break status-badge" style="border: 1px solid {{ $borderColor }}; color: {{ $color }}; background-color: {{ $bg }};">
            AVALIAÇÃO FINAL: {{ $statusLabel[$report->status] ?? 'EM ANÁLISE' }}
        </div>
    @endif

    <div class="footer">
        Documento gerado eletronicamente em {{ date('d/m/Y H:i') }}. Sara Linhar DHO & RH.
    </div>

</body>
</html>
