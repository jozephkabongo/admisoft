<?php

    namespace NokySQL;

    use PDO;
    use NokySQL\Exceptions\DatabaseException;
    use NokySQL\Exceptions\QueryException;

    class Database {
        private PDO $pdo;
        private string $driver;
        private array $queuedQueries = [];

        public function __construct(string $driver, array $config) {
            $this->driver = strtolower(string: $driver);
            $this->validateConfig(driver: $driver, config: $config);
            $this->pdo = $this->createConnection(config: $config);
        }

        private function validateConfig(string $driver, array $config): void {
            $required = match ($driver) {
                'sqlite' => ['database'],
                'mysql', 'pgsql' => ['host', 'database', 'user', 'password'],
                default => throw new DatabaseException(message: "Unsupported driver: $driver")
            };

            foreach ($required as $key) {
                if (!isset($config[$key])) {
                    throw new DatabaseException(message: "Missing config key: $key");
                }
            }
        }

        private function createConnection(array $config): PDO {
            try {
                $dsn = $this->buildDsn(config: $config);
                $options = [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false
                ];
                return new PDO(dsn: $dsn, username: $config['user'] ?? null, password: $config['password'] ?? null, options: $options);
            } catch (\PDOException $e) {
                throw new DatabaseException(message: "Connection failed: " . $e->getMessage());
            }
        }

        private function buildDsn(array $config): string {
            return match($this->driver) {
                'mysql'  => "mysql:host={$config['host']};dbname={$config['database']};charset=utf8mb4",
                'pgsql'  => "pgsql:host={$config['host']};dbname={$config['database']}",
                'sqlite' => "sqlite:{$config['database']}",
                default  => throw new DatabaseException(message: "Unsupported driver: {$this->driver}")
            };
        }

        public function query(string $sql, array $params = []): \PDOStatement {
            try {
                $stmt = $this->pdo->prepare(query: $sql);
                $stmt->execute(params: $params);
                return $stmt;
            } catch (\PDOException $e) {
                throw new QueryException(message: $e->getMessage(), sql: $sql, params: $params);
            }
        }

        public function lastInsertId(?string $sequence = null): bool|string { 
            try {
                return $this->pdo->lastInsertId(name: $sequence);
            } catch (\PDOException $e) {
                throw new DatabaseException(message: "Failed to get last insert ID: " . $e->getMessage());
            }
        }

        public function lastInsertRow(string $table, string $idColumn = 'id'): ?array { 
            try {
                $lastId = $this->lastInsertId();
                return $this->select($table)->where(condition: "$idColumn = ?", params: [$lastId])->first();
            } catch (QueryException $e) {
                error_log(message: 'Failed to get last insert row: ' . $e->getMessage());
                return null;
            }
        }

        public function insertFromSelect(string $targetTable, array $targetColumns, QueryBuilder $selectQuery): bool { 
            $columns = implode(separator: ', ', array: $targetColumns);
            [$sql, $params] = $selectQuery->toSql();

            $insertSql = sprintf("INSERT INTO %s (%s) %s ", $targetTable, $columns, preg_replace(pattern: '/^\s*SELECT\s+/i', replacement: '', subject: $sql));
            try {
                return (bool) $this->query($insertSql, $params);
            } catch (\PDOException $e) {
                throw new QueryException(message: $e->getMessage(), sql: $insertSql, params: $params);
            }
        }
        
        // Parallel queries
        public function queue(QueryBuilder $query): self {
            [$sql, $params] = $query->toSql();
            $this->queuedQueries[] = compact(var_name: 'sql', var_names: 'params');
            return $this;
        }

        public function addToQueue(QueryBuilder $query): void {
            $this->queuedQueries[] = $query;
        }
    
        public function executeParallel(): array {
            $results = [];
            foreach ($this->queuedQueries as $query) {
                $results[] = $query->execute();
            }
            $this->queuedQueries = [];
            return $results;
        }

        // Transactions
        public function beginTransaction(): bool {
            if (!$this->inTransaction()) {
                return $this->pdo->beginTransaction();
            }
            return false;
        }

        public function commit(): bool {
            if ($this->inTransaction()) {
                return $this->pdo->commit();
            }
            return false;
        }

        public function rollBack(): bool {
            if ($this->inTransaction()) {
                return $this->pdo->rollBack();
            }
            return false;
        }

        public function inTransaction(): bool {
            return $this->pdo->inTransaction();
        }

        public function autoTransaction(callable $callback): mixed {
            $this->beginTransaction();
            try {
                $result = $callback($this);
                $this->commit();
                return $result;
            } catch (QueryException $qe) {
                $this->rollBack();
                throw $qe;
            }
        }

        // Query Builder
        public function select(string $table): QueryBuilder {
            return new QueryBuilder(db: $this, table: $table, type: 'SELECT');
        }

        public function insert(string $table): QueryBuilder {
            return new QueryBuilder(db: $this, table: $table, type: 'INSERT');
        }

        public function update(string $table): QueryBuilder {
            return new QueryBuilder(db: $this, table: $table, type: 'UPDATE');
        }

        public function delete(string $table): QueryBuilder {
            return new QueryBuilder(db: $this, table: $table, type: 'DELETE');
        }

        // Schema Builder
        public function schema(): SchemaBuilder {
            return new SchemaBuilder(db: $this);
        }

        public function getDriver(): string {
            return $this->driver;
        }

        public function getPdo(): PDO {
            return $this->pdo;
        }
    }