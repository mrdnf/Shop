<?php
declare(strict_types=1);

namespace App\Core;

use PDO;
use PDOStatement;

/**
 * Thin PDO wrapper. Values are always bound; identifiers are validated.
 */
final class Database
{
    private PDO $pdo;

    public function __construct(
        string $host,
        int $port,
        string $user,
        string $pass,
        public readonly ?string $name = null
    ) {
        $dsn = "mysql:host={$host};port={$port};charset=utf8mb4" . ($name ? ";dbname={$name}" : '');
        $this->pdo = new PDO($dsn, $user, $pass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
        $this->pdo->exec("SET time_zone = '+00:00', sql_mode = 'STRICT_TRANS_TABLES,NO_ENGINE_SUBSTITUTION'");
    }

    public static function ident(string $name): string
    {
        if (!preg_match('/^[A-Za-z0-9_-]{1,64}$/', $name)) {
            throw new \InvalidArgumentException("Invalid identifier: {$name}");
        }
        return "`{$name}`";
    }

    public function pdo(): PDO
    {
        return $this->pdo;
    }

    public function query(string $sql, array $params = []): PDOStatement
    {
        $stmt = $this->pdo->prepare($sql);
        foreach ($params as $key => $value) {
            $param = is_int($key) ? $key + 1 : (str_starts_with($key, ':') ? $key : ':' . $key);
            $type = match (true) {
                is_int($value) => PDO::PARAM_INT,
                is_bool($value) => PDO::PARAM_BOOL,
                $value === null => PDO::PARAM_NULL,
                default => PDO::PARAM_STR,
            };
            $stmt->bindValue($param, $value, $type);
        }
        $stmt->execute();
        return $stmt;
    }

    public function fetch(string $sql, array $params = []): ?array
    {
        $row = $this->query($sql, $params)->fetch();
        return $row === false ? null : $row;
    }

    public function fetchAll(string $sql, array $params = []): array
    {
        return $this->query($sql, $params)->fetchAll();
    }

    public function value(string $sql, array $params = []): mixed
    {
        $value = $this->query($sql, $params)->fetchColumn();
        return $value === false ? null : $value;
    }

    public function insert(string $table, array $data): int
    {
        $cols = array_map([self::class, 'ident'], array_keys($data));
        $marks = implode(',', array_fill(0, count($data), '?'));
        $this->query('INSERT INTO ' . self::ident($table) . ' (' . implode(',', $cols) . ") VALUES ({$marks})", array_values($data));
        return (int) $this->pdo->lastInsertId();
    }

    public function update(string $table, array $data, string $where, array $params = []): int
    {
        $sets = [];
        foreach (array_keys($data) as $col) {
            $sets[] = self::ident($col) . ' = ?';
        }
        $stmt = $this->query(
            'UPDATE ' . self::ident($table) . ' SET ' . implode(', ', $sets) . " WHERE {$where}",
            array_merge(array_values($data), array_values($params))
        );
        return $stmt->rowCount();
    }

    public function delete(string $table, string $where, array $params = []): int
    {
        return $this->query('DELETE FROM ' . self::ident($table) . " WHERE {$where}", array_values($params))->rowCount();
    }

    public function exec(string $sql): void
    {
        $this->pdo->exec($sql);
    }

    public function transaction(callable $callback): mixed
    {
        $this->pdo->beginTransaction();
        try {
            $result = $callback($this);
            $this->pdo->commit();
            return $result;
        } catch (\Throwable $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $e;
        }
    }

    /**
     * Paginate a SELECT statement.
     */
    public function paginate(string $sql, array $params = [], int $perPage = 20, ?int $page = null): array
    {
        $page = max(1, $page ?? (int) ($_GET['page'] ?? 1));
        $total = (int) $this->value("SELECT COUNT(*) FROM ({$sql}) AS _count", $params);
        $pages = max(1, (int) ceil($total / $perPage));
        $page = min($page, $pages);
        $offset = ($page - 1) * $perPage;
        $items = $this->fetchAll("{$sql} LIMIT {$perPage} OFFSET {$offset}", $params);
        return compact('items', 'total', 'page', 'pages', 'perPage');
    }
}
