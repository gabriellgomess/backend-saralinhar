<?php

use App\Models\Candidate;

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$candidate = Candidate::where('email', 'sara@saralinhar.com.br')->first();

if ($candidate) {
    echo "Candidato encontrado:\n";
    echo "ID: " . $candidate->id . "\n";
    echo "Nome: " . $candidate->name . "\n";
    echo "Email: " . $candidate->email . "\n";
} else {
    echo "Nenhum candidato encontrado com este email.\n";
}
