<?php
declare(strict_types=1);

namespace App\Services\Inventory\Providers;

use App\Core\Http;
use App\Services\Inventory\AbstractProvider;
use PDO;

/** Read stock straight from another system's database (read-only user recommended). */
final class DatabaseProvider extends AbstractProvider
{
    public static function label(): string
    {
        return 'External database';
    }

    public static function description(): string
    {
        return 'Run a read-only SQL query against your ERP / POS database (MySQL, MariaDB, PostgreSQL, SQL Server).';
    }

    public static function fields(): array
    {
        return [
            ['name' => 'driver', 'label' => 'Database type', 'type' => 'select', 'options' => ['mysql' => 'MySQL / MariaDB', 'pgsql' => 'PostgreSQL', 'sqlsrv' => 'SQL Server'], 'default' => 'mysql'],
            ['name' => 'host', 'label' => 'Host', 'type' => 'text', 'placeholder' => '10.0.0.5', 'required' => true],
            ['name' => 'port', 'label' => 'Port', 'type' => 'number', 'placeholder' => '3306'],
            ['name' => 'database', 'label' => 'Database name', 'type' => 'text', 'required' => true],
            ['name' => 'username', 'label' => 'Username', 'type' => 'text'],
            ['name' => 'password', 'label' => 'Password', 'type' => 'password', 'help' => 'Stored encrypted.'],
            ['name' => 'query', 'label' => 'Stock query', 'type' => 'textarea', 'default' => 'SELECT item_code AS sku, SUM(qty) AS quantity FROM stock GROUP BY item_code', 'help' => 'Must return the columns "sku" and "quantity".', 'required' => true],
            ['name' => 'single_query', 'label' => 'Single SKU query (optional)', 'type' => 'textarea', 'placeholder' => 'SELECT SUM(qty) AS quantity FROM stock WHERE item_code = :sku'],
        ];
    }

    private function connect(): PDO
    {
        $driver = (string) $this->cfg('driver', 'mysql');
        $host = (string) $this->cfg('host', '');
        $db = (string) $this->cfg('database', '');
        $port = (string) $this->cfg('port', '');
        if (!in_array($driver, PDO::getAvailableDrivers(), true)) {
            throw new \RuntimeException("PDO driver \"{$driver}\" is not installed on this server.");
        }
        if (Http::guardEnabled()) {
            $host = Http::assertPublicHost($host);
        }
        $dsn = match ($driver) {
            'sqlsrv' => "sqlsrv:Server={$host}" . ($port ? ",{$port}" : '') . ";Database={$db}",
            'pgsql' => "pgsql:host={$host};" . ($port ? "port={$port};" : '') . "dbname={$db}",
            default => "mysql:host={$host};" . ($port ? "port={$port};" : '') . "dbname={$db};charset=utf8mb4",
        };
        return new PDO($dsn, (string) $this->cfg('username', ''), (string) $this->cfg('password', ''), [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_TIMEOUT => 10,
        ]);
    }

    private static function assertReadOnly(string $sql): void
    {
        if (!preg_match('/^\s*(SELECT|WITH)\b/i', $sql) || str_contains($sql, ';')) {
            throw new \RuntimeException('Only a single SELECT query is allowed.');
        }
    }

    public function fetchAll(): array
    {
        $sql = (string) $this->cfg('query', '');
        self::assertReadOnly($sql);
        $out = [];
        foreach ($this->connect()->query($sql) as $row) {
            $row = array_change_key_case($row, CASE_LOWER);
            if (isset($row['sku']) && is_numeric($row['quantity'] ?? null)) {
                $out[(string) $row['sku']] = ($out[(string) $row['sku']] ?? 0) + (int) floor((float) $row['quantity']);
            }
        }
        return $out;
    }

    public function fetchOne(string $sku): ?int
    {
        $sql = (string) $this->cfg('single_query', '');
        if ($sql === '') {
            return parent::fetchOne($sku);
        }
        self::assertReadOnly($sql);
        $stmt = $this->connect()->prepare($sql);
        $stmt->execute(['sku' => $sku]);
        $qty = $stmt->fetchColumn();
        return is_numeric($qty) ? (int) floor((float) $qty) : null;
    }
}
