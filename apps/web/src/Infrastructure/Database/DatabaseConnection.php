<?php

declare(strict_types=1);

namespace App\Infrastructure\Database;

use InvalidArgumentException;
use PDO;
use PDOException;
use RuntimeException;

/**
 * Connexion MariaDB : valeurs exclusivement dans l'environnement local.
 * Aucune connexion n'est ouverte lors de la construction de ce service.
 */
final class DatabaseConnection
{
    public function connect(): PDO
    {
        $host = $this->required('LOGIQUE_DB_HOST');
        $database = $this->required('LOGIQUE_DB_NAME');
        $user = $this->required('LOGIQUE_DB_USER');
        $password = $this->required('LOGIQUE_DB_PASSWORD');
        $port = $this->setting('LOGIQUE_DB_PORT') ?? '3306';

        if (!preg_match('/^[a-zA-Z0-9_.-]+$/D', $host)
            || !preg_match('/^[a-zA-Z0-9_]+$/D', $database)
            || !preg_match('/^[0-9]{1,5}$/D', $port)
            || (int) $port < 1 || (int) $port > 65535) {
            throw new InvalidArgumentException('Paramètres de connexion MariaDB invalides.');
        }

        if (!extension_loaded('pdo_mysql')) {
            throw new RuntimeException('Extension PHP pdo_mysql absente.');
        }

        $dsn = sprintf('mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4', $host, (int) $port, $database);

        try {
            return new PDO($dsn, $user, $password, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::ATTR_TIMEOUT => 5,
            ]);
        } catch (PDOException) {
            // Eviter d'afficher les parametres prives du serveur dans l'erreur.
            throw new RuntimeException('Connexion MariaDB impossible (hôte, accès ou autorisations à vérifier).');
        }
    }

    private function required(string $name): string
    {
        $value = $this->setting($name);
        if ($value === null || $value === '') {
            throw new RuntimeException("Configuration MariaDB manquante : $name.");
        }

        return $value;
    }

    private function setting(string $name): ?string
    {
        $value = $_SERVER[$name] ?? $_ENV[$name] ?? getenv($name);

        return is_string($value) ? $value : null;
    }
}
