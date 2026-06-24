<?php

// Script para corrigir nomes de arquivos corrompidos por encoding (Double UTF-8)
// Caminhos a serem verificados
$basePath = __DIR__ . '/storage/app/public';
$folders = ['resumes', 'parecers', 'discs', 'clients', 'temp_resumes'];

echo "Iniciando a correcao de nomes de arquivos...\n";

foreach ($folders as $folder) {
    $dirPath = $basePath . '/' . $folder;
    if (!is_dir($dirPath)) {
        echo "Pasta nao encontrada: $dirPath\n";
        continue;
    }

    echo "\nVerificando a pasta: $folder\n";
    $files = scandir($dirPath);
    $count = 0;

    foreach ($files as $file) {
        if ($file === '.' || $file === '..') {
            continue;
        }

        // Verifica se o nome do arquivo contem caracteres típicos de double UTF-8 (como Ã)
        // O caractere Ã em UTF-8 é codificado como os bytes 0xC3 0x83
        if (strpos($file, 'Ã') !== false || strpos($file, 'Â') !== false || preg_match('/[\x{00C3}\x{00C2}]/u', $file)) {
            $oldPath = $dirPath . '/' . $file;
            
            // Decodifica de UTF-8 para ISO-8859-1 para reverter o double encoding
            $newFile = utf8_decode($file);
            
            // Se a decodificação resultou em algo diferente e válido
            if ($newFile && $newFile !== $file) {
                $newPath = $dirPath . '/' . $newFile;
                
                if (rename($oldPath, $newPath)) {
                    echo "Renomeado: '$file' -> '$newFile'\n";
                    $count++;
                } else {
                    echo "Erro ao renomear: '$file'\n";
                }
            }
        }
    }
    echo "Total renomeado em $folder: $count arquivos.\n";
}

echo "\nProcesso concluido!\n";
