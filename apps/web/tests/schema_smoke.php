<?php

declare(strict_types=1);

// Verification statique sans connexion a une base, pour les PR et avant
// la preparation de l'environnement MariaDB.
$project = dirname(__DIR__);
$sql = file_get_contents($project . '/database/migrations/001_identity_progress.sql');
$command = file_get_contents($project . '/src/Command/DatabaseSchemaCommand.php');
$config = file_get_contents($project . '/src/Infrastructure/Database/DatabaseConnection.php');

if (!is_string($sql) || !is_string($command) || !is_string($config)) {
    throw new RuntimeException('Fichier de persistance manquant.');
}

$tables = [
    'lp_account',
    'lp_learner',
    'lp_guardian_learner',
    'lp_attempt',
    'lp_competency_progress',
];
foreach ($tables as $table) {
    if (!str_contains($sql, 'CREATE TABLE ' . $table . ' (')) {
        throw new RuntimeException("Table absente : $table");
    }
}
foreach ([
    'UNIQUE KEY uniq_learner_attempt',
    'FOREIGN KEY',
    'CHECK (stars BETWEEN 0 AND 5)',
    'DEFAULT CHARSET=utf8mb4',
] as $invariant) {
    if (!str_contains($sql, $invariant)) {
        throw new RuntimeException("Contrainte absente : $invariant");
    }
}
if (preg_match('/\b(DROP\s+TABLE|TRUNCATE|DELETE\s+FROM)\b/i', $sql)) {
    throw new RuntimeException('Operation destructive interdite dans la migration initiale.');
}
if (!str_contains($command, "addOption('apply'")
    || !str_contains($command, 'Mode lecture seule')
    || !str_contains($config, 'PDO::ATTR_EMULATE_PREPARES => false')) {
    throw new RuntimeException('Securite de la commande de migration incomplete.');
}

echo "OK : structure MariaDB controlee ; 5 tables metier, garde-fous et mode lecture seule.\n";
