<?php

declare(strict_types=1);

namespace App\Command;

use App\Infrastructure\Database\DatabaseConnection;
use PDO;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Throwable;

/**
 * Commande conservatrice : sans --apply, lecture seule et aucun changement.
 * La migration initiale refuse une base preexistante non geree par l'application.
 */
#[AsCommand(name: 'app:db:schema', description: 'Vérifier ou initialiser le schéma MariaDB dédié')]
final class DatabaseSchemaCommand extends Command
{
    private const VERSION = '001_identity_progress';

    public function __construct(private readonly DatabaseConnection $connection)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption('apply', null, InputOption::VALUE_NONE,
            'Créer explicitement les tables initiales après vérification de la base vide.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        try {
            $db = $this->connection->connect();
            $name = $db->query('SELECT DATABASE()')->fetchColumn();
            if (!is_string($name) || $name === '') {
                $io->error('Aucune base sélectionnée.');

                return Command::FAILURE;
            }
            $io->writeln('Base MariaDB sélectionnée : ' . $name);

            $tables = $db->query(
                "SELECT TABLE_NAME FROM information_schema.TABLES
                 WHERE TABLE_SCHEMA = DATABASE() AND TABLE_TYPE = 'BASE TABLE'"
            )->fetchAll(PDO::FETCH_COLUMN);
            if (!is_array($tables)) {
                $io->error('Inventaire des tables impossible.');

                return Command::FAILURE;
            }
            $hasMigrations = in_array('lp_schema_migration', $tables, true);
            $applied = false;

            if ($hasMigrations) {
                $query = $db->prepare('SELECT COUNT(*) FROM lp_schema_migration WHERE version = :version');
                $query->execute(['version' => self::VERSION]);
                $applied = (int) $query->fetchColumn() > 0;
            }

            if ($applied) {
                $io->success('Schéma ' . self::VERSION . ' déjà appliqué. Rien à modifier.');

                return Command::SUCCESS;
            }

            $io->warning('Schéma ' . self::VERSION . ' non appliqué.');
            if (!$input->getOption('apply')) {
                $io->note('Mode lecture seule. Utiliser --apply uniquement après sauvegarde et vérification.');

                return Command::SUCCESS;
            }

            if ($tables !== [] && !($tables === ['lp_schema_migration'])) {
                $io->error('La base contient déjà des tables sans migration validée. Opération arrêtée pour protéger les données.');

                return Command::FAILURE;
            }

            $sqlFile = dirname(__DIR__, 2) . '/database/migrations/001_identity_progress.sql';
            $sql = file_get_contents($sqlFile);
            if (!is_string($sql) || $sql === '') {
                $io->error('Fichier de migration introuvable ou vide.');

                return Command::FAILURE;
            }

            $db->exec("CREATE TABLE IF NOT EXISTS lp_schema_migration (
                version VARCHAR(100) NOT NULL PRIMARY KEY,
                applied_at DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

            // Les DDL MariaDB sont auto-valides. Si une instruction echoue,
            // ne pas marquer la migration comme terminee et demander une inspection.
            $statements = preg_split('/;\s*(?:\r?\n|$)/', $sql);
            if (!is_array($statements)) {
                throw new \RuntimeException('Lecture du SQL impossible.');
            }
            foreach ($statements as $statement) {
                if (trim($statement) !== '') {
                    $db->exec($statement);
                }
            }

            $insert = $db->prepare('INSERT INTO lp_schema_migration (version) VALUES (:version)');
            $insert->execute(['version' => self::VERSION]);

            $io->success('Schéma ' . self::VERSION . ' créé. Aucun compte utilisateur n’a été généré.');

            return Command::SUCCESS;
        } catch (Throwable $exception) {
            $io->error('Schéma MariaDB non validé : ' . $this->safeError($exception));

            return Command::FAILURE;
        }
    }

    private function safeError(Throwable $exception): string
    {
        // Ne pas retransmettre les erreurs SQL/connexion brutes,
        // susceptibles de contenir des informations d'infrastructure.
        if ($exception instanceof \RuntimeException
            && (str_starts_with($exception->getMessage(), 'Configuration MariaDB manquante')
                || str_starts_with($exception->getMessage(), 'Extension PHP pdo_mysql absente'))) {
            return $exception->getMessage();
        }

        return 'vérifier le compte MariaDB, les permissions et l’état des tables.';
    }
}
