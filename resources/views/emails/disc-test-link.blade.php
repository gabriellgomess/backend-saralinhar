<!DOCTYPE html>
<html>
<head>
    <title>Convite para Teste DISC - Sara Linhar</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            color: #333;
            line-height: 1.6;
        }
        .container {
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
            border: 1px solid #e0e0e0;
            border-radius: 5px;
            background-color: #f9f9f9;
        }
        .header {
            text-align: center;
            margin-bottom: 20px;
        }
        .header img {
            height: 50px;
        }
        .content {
            background-color: #fff;
            padding: 20px;
            border-radius: 5px;
        }
        .button {
            display: inline-block;
            padding: 10px 20px;
            background-color: #003366;
            color: #fff;
            text-decoration: none;
            border-radius: 5px;
            margin-top: 20px;
            font-weight: bold;
        }
        .footer {
            margin-top: 20px;
            text-align: center;
            font-size: 12px;
            color: #666;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h2>Convite para Avaliação Comportamental</h2>
        </div>
        <div class="content">
            <p>Olá, <strong>{{ $token->testee_name ?? 'Candidato(a)' }}</strong>!</p>
            
            <p>Você foi convidado(a) para realizar o teste de perfil comportamental DISC da <strong>Sara Linhar - Consultoria em DHO</strong>.</p>
            
            @if($token->job_title)
            <p>Este teste faz parte do processo seletivo para a vaga de: <strong>{{ $token->job_title }}</strong>.</p>
            @endif

            <p>Para iniciar sua avaliação, clique no botão abaixo:</p>
            
            <div style="text-align: center;">
                <a href="{{ $url }}" class="button">INICIAR TESTE DISC</a>
            </div>
            
            <p style="margin-top: 20px;">Ou acesse através do link:</p>
            <p><a href="{{ $url }}">{{ $url }}</a></p>
            
            <p><strong>Importante:</strong></p>
            <ul>
                <li>Reserve cerca de 10-15 minutos para responder.</li>
                <li>Não existem respostas certas ou erradas.</li>
                <li>Responda com sinceridade, pensando em como você realmente age no ambiente de trabalho.</li>
            </ul>
        </div>
        <div class="footer">
            <p>Este é um e-mail automático. Por favor, não responda.</p>
            <p>&copy; {{ date('Y') }} Sara Linhar - Desenvolvimento Humano e Organizacional.</p>
        </div>
    </div>
</body>
</html>
