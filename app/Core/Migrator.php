<?php
declare(strict_types=1);

namespace App\Core;

/**
 * Runs numbered *.sql files once per database and records them in `migrations`.
 * Used for the platform DB and for every shop DB (bin/migrate.php upgrades all shops).
 */
final class Migrator
{
    public static function run(Database $db, string $dir): array
    {
        $db->exec('CREATE TABLE IF NOT EXISTS `migrations` (
            `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            `migration` VARCHAR(190) NOT NULL UNIQUE,
            `ran_at` DATETIME NOT NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');

        $done = array_column($db->fetchAll('SELECT migration FROM migrations'), 'migration');
        $files = glob(rtrim($dir, '/') . '/*.sql') ?: [];
        sort($files);

        $ran = [];
        foreach ($files as $file) {
            $name = basename($file);
            if (in_array($name, $done, true)) {
                continue;
            }
            foreach (self::statements((string) file_get_contents($file)) as $statement) {
                $db->exec($statement);
            }
            $db->insert('migrations', ['migration' => $name, 'ran_at' => now()]);
            $ran[] = $name;
        }
        return $ran;
    }

    /** Split a SQL file on semicolons that end a line. Comments starting with -- are dropped. */
    private static function statements(string $sql): array
    {
        $sql = preg_replace('/^\s*--.*$/m', '', $sql) ?? $sql;
        $parts = preg_split('/;\s*(\r?\n|$)/', $sql) ?: [];
        return array_values(array_filter(array_map('trim', $parts), static fn ($s) => $s !== ''));
    }
}
