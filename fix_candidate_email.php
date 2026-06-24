<?php

use App\Models\Candidate;
use Illuminate\Support\Facades\DB;

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$candidate = Candidate::where('email', 'sara@saralinhar.com.br')->first();

if ($candidate && $candidate->id == 9) {
    // Altera o email para um temporário para desvincular da Sara
    $tempEmail = 'elisiane.temp.' . time() . '@example.com';
    $candidate->email = $tempEmail;
    $candidate->save();
    echo "Email do candidato Elisiane (ID 9) atualizado para: " . $tempEmail . "\n";
    echo "Agora o login da Sara não trará mais os dados da Elisiane.\n";
} else {
    echo "Candidato Elisiane não encontrado com o email da Sara ou ID incorreto.\n";
}
