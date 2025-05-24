<?php

    namespace NokySQL;

    use InvalidArgumentException;
    use NokySQL\Exceptions\QueryException;
    use NokySQL\RawExpression;
    
    class QueryBuilder {
        private Database $db;
        private string $table;
        private string $type;
        private array $components = [
            'select'  => '*',
            'join'    => [],
            'where'   => [],
            'order'   => [],
            'groupBy' => [],
            'having'  => [],
            'data'    => [],
            'params'  => [],
            'set'     => [],
            'limit'   => null,
            'offset'  => null
        ];
        private bool $isQueued = false;

        public function __construct(Database $db, string $table, string $type) {
            $this->db = $db;
            $this->table = $table;
            $this->type = $type;
        }

        public function select(array $columns): self {
            $this->components['select'] = implode(separator: ', ', array: $columns);
            return $this;
        }

        public function join(string $table, string $condition, string $type = 'INNER'): self {
            $this->components['join'][] = "$type JOIN $table ON $condition";
            return $this;
        }

        public function where(string $condition, array $params = []): self {
            $this->components['where'][] = $condition;
            $this->components['params'] = array_merge($this->components['params'] ?? [], $params);
            return $this;
        }

        public function whereSymmetric(array $columns): self {
            if (count(value: $columns) !== 2) {
                throw new InvalidArgumentException(message: "whereSymmetric requires exactly 2 columns");
            }

            $keys = array_keys($columns);
            $values = array_values(array: $columns);

            $firstCondition  = "{$keys[0]} = ?" . " AND " . "{$keys[1]} = ?";
            $secondCondition = "{$keys[0]} = ?" . " AND " . "{$keys[1]} = ?";

            $this->components['where'][] = "($firstCondition OR $secondCondition)";
            $this->components['params'] = array_merge(
                $this->components['params'] ?? [],
                [$values[0], $values[1], $values[1], $values[0]]
            );
            return $this;
        }

        public function orderBy(string $column, string $direction = 'ASC'): self {
            $this->components['order'][] = "$column $direction";
            return $this;
        }

        public function groupBy($columns): self { 
            if (is_string(value: $columns)) {
                $this->components['groupBy'][] = $columns;
            } else {
                $this->components['groupBy'] = array_merge($this->components['groupBy'], $columns);
            }
            return $this;
        }

        public function limit(int $limit): self {
            $this->components['limit'] = $limit;
            return $this;
        }

        public function set(array $data): self {
            if (!isset($this->components['data'])) {
                $this->components['data'] = [];
            }
            $this->components['data'] = array_merge($this->components['data'], $data);
            return $this;
        }

        public function first(): ?array {  
            $results = $this->limit(1)->execute();
            return $results[0] ?? null;
        }

        public function avg(string $column, ?string $alias = null): self {  
            return $this->buildFunction(fn: 'AVG', columns: $column, alias: $alias);
        }

        public function stddev(string $column, ?string $alias = null): self {   
            return $this->buildFunction(fn: 'STDDEV', columns: $column, alias: $alias);
        }

        public function variance(string $column, ?string $alias = null): self { 
            return $this->buildFunction(fn: 'VARIANCE', columns: $column, alias: $alias);
        }

        public function sum(string $column, ?string $alias): self {  
            return $this->buildFunction(fn: 'SUM', columns: $column, alias: $alias);
        }

        public function min(string $column, ?string $alias): self { 
            return $this->buildFunction(fn: 'MIN', columns: $column, alias: $alias);
        }

        public function max(string $column, ?string $alias): self { 
            return $this->buildFunction(fn: 'MAX', columns: $column, alias: $alias);
        }

        public function concat(array $columns, ?string $alias): self {  
            return $this->buildFunction(fn: 'CONCAT', columns: $columns, alias: $alias, multiple: true);
        }

        public function substring(string $column, int $start, ?int $length = null, ?string $alias = null): self {   
            $params = [$start];
            if ($length = null)
                $params[] = $length;
            return $this->buildFunction(fn: 'SUBSTRING', columns: $column, alias: $alias, multiple: false, params: $params); 
        }

        public function length(string $column, ?string $alias = null): self {   
            return $this->buildFunction(fn: 'LENGTH', columns: $column, alias: $alias);
        }

        public function upper(string $column, ?string $alias = null): self {    
            return $this->buildFunction(fn: 'UPPER', columns: $column, alias: $alias);
        }

        public function lower(string $column, ?string $alias = null): self {    
            return $this->buildFunction(fn: 'LOWER', columns: $column, alias: $alias);
        }

        public function trim(string $column, ?string $alias = null): self { 
            return $this->buildFunction(fn: 'TRIM', columns: $column, alias: $alias);
        }

        public function round(string $column, int $decimals = 0, ?string $alias = null): self { 
            return $this->buildFunction(fn: 'ROUND', columns: $column, alias: $alias, multiple: false, params: [$decimals]);
        }

        public function floor(string $column, ?string $alias = null): self {    
            return $this->buildFunction(fn: 'FLOOR', columns: $column, alias: $alias);    
        }

        public function ceilling(string $column, ?string $alias = null): self {    
            return $this->buildFunction(fn: 'CEILING', columns: $column, alias: $alias);
        }

        public function abs(string $column, ?string $alias = null): self {  
            return $this->buildFunction(fn: 'ABS', columns: $column, alias: $alias);
        }

        public function now(?string $alias): self {     
            $expression = match ($this->db->getDriver()) {
                'mysql', 'pgsql' => 'NOW()',
                'sqlite' => "DATETIME('now')"
            };
            $this->components['select'][] = $expression . ($alias ? " AS " . $this->escapecolumn(column: $alias) : '');
            return $this;  
        }

        public function dateAdd(string $date, int $value, string $unit, ?string $alias = null): self {    
            $expression = match ($this->db->getDriver()) {
                'mysql'  => "DATE_ADD($date, INTERVAL $value $unit)",
                'pgsql'  => "$date + INTERVAL'$value $unit'",
                'sqlite' => "DATETIME($date, '+$value $unit')"
            };

            $this->components['select'][] = $expression . ($alias ? " AS " . $this->escapeColumn(column: $alias) : '');
            return $this;
        }

        public function dateDiff(string $startDate, string $endDate, string $unit, ?string $alias = null): self {   
            $expression = match ($this->db->getDriver()) {
                'mysql'  => "TIMESTAMPDIFF($unit, $startDate, $endDate)",
                'pgsql'  => "EXTRACT(EPOCH FROM ($endDate - $startDate)) / " . $this->dateDiffFactor(unit: $unit),
                'sqlite' => "JULIANDAY($endDate) - JULIANDAY($startDate)"
            };

            $this->components['select'][] = $expression . ($alias ? " AS " . $this->escapeColumn(column: $alias) : '');
            return $this;
        }

        public function coalesce(array $columns, ?string $alias = null): self { 
            return $this->buildFunction(fn: 'COALESCE', columns: $columns, alias: $alias, multiple: true);    
        }

        public function convert(string $column, string $type, ?string $alias = null): self {    
            $expression = match ($this->db->getDriver()) {
                'mysql'  => "CONVERT($column, $type)",
                'pgsql'  => "CAST($column AS $type)",
                'sqlite' => "CAST($column AS $type)"
            };
            
            $this->components['select'][] = $expression . ($alias ? " AS " . $this->escapeColumn(column: $alias) : '');
            return $this;
        }

        public function rowNumber(?string $alias = null, array $partitionBy = [], array $orderBy = []): self {  
            return $this->windowFunction(fn: 'ROW_NUMBER',alias: $alias, partitionBy: $partitionBy, orderBy: $orderBy);    
        }

        public function rank(?string $alias = null, array $partitionBy = [], array $orderBy = []): self {   
            return $this->windowFunction(fn: 'RANK', alias: $alias, partitionBy: $partitionBy, orderBy: $orderBy);
        }

        public function denseRank(?string $alias = null, array $partitionBy = [], array $orderBy = []): self {   
            return $this->windowFunction(fn: 'DENSE_RANK', alias: $alias, partitionBy: $partitionBy, orderBy: $orderBy);
        }

        private function escapeTable(string $table): string {    
            if (strpos(haystack: $table, needle: '.') !== false) {
                $parts = array_map(callback: [$this, 'escapeIdentifier'], array: explode(separator: '.', string: $table));
                return implode(separator: '.', array: $parts);
            } else {
                return $this->escapeIdentifier(identifier: $table);
            }
        }

        private function escapeIdentifier(string $identifier): string {     
            if ($identifier === '*') {
                return $identifier;
            }
            return match($this->db->getDriver()) {
                'mysql'  => '`' . str_replace(search: '`', replace: '``', subject: $identifier) . '`',
                'pgsql'  => '"' . str_replace(search: '"', replace: '""', subject: $identifier) . '"',
                'sqlite' => '[' . str_replace(search: ']', replace: ']]', subject: $identifier) . ']',
                default  => $identifier
            };
        }

        private function escapeColumn(string $column): string {     
            return $this->escapeIdentifier(identifier: $column);
        }

        private function buildFunction(string $fn, $columns, ?string $alias, bool $multiple = false, array $params = []): self {  
            $escaped = $multiple ? array_map(callback: [$this, 'escapeColumn'], array: (array)$columns) : $this->escapeColumn(column: $columns);
            $expression = $fn . '(' . implode(separator: ', ', array: array_merge((array)$escaped, $params)) . ')';
            if ($alias) {
                $expression .= " AS " . $this->escapeColumn(column: $alias);
            }
            $this->components['select'][] = $expression;
            return $this;
        }

        private function windowFunction(string $fn, ?string $alias, array $partitionBy = [], array $orderBy = []): self {   
            $over = [];

            if (!empty($partitionBy)) {
                $over[] = "PARTITION BY " . implode(separator: ', ', array: array_map(callback: [$this, 'escapeColumn'], array: $partitionBy));
            }

            if (!empty($orderBy)) {
                $over[] = "ORDER BY " . $this->buildOrderClause(orderBy: $orderBy);
            }

            $expression = "$fn() OVER(" . implode(separator: ' ', array: $over) . ")";

            if ($alias) {
                $expression .= " AS " . $this->escapeColumn(column: $alias);
            }

            $this->components['select'][] = $expression;
            return $this;
        }

        private function dateDiffFactor(string $unit): int {    
            return match(strtoupper(string: $unit)) {
                'SECOND' => 1,
                'MINUTE' => 60,
                'HOUR'   => 3600,
                'DAY'    => 86400,
                'WEEK'   => 604800,
                default  => 1
            };
        }

        private function buildOrderClause(array $orderBy): string {    
            $clauses = [];
            foreach ($orderBy as $column => $direction) {
                $dir = strtoupper(string: $direction) === 'DESC' ? 'DESC' : 'ASC';
                $clauses[] = $this->escapeColumn(column: $column) . ' ' . $dir;
            }
            return implode(separator: ', ', array: $clauses);
        }

        public function increment(string $column, $value = 1): self { 
            return $this->arithmeticUpdate(column: $column, operator: '+', value: $value);
        }

        public function decrement(string $column, $value = 1): self {   
            return $this->arithmeticUpdate(column: $column, operator: '-', value: $value);
        }

        public function multiply(string $column, $value): self {    
            return $this->arithmeticUpdate(column: $column, operator: '*', value: $value);
        }

        public function divide(string $column, $value = 1): self {  
            return $this->arithmeticUpdate(column: $column, operator: '/', value: $value);
        }

        private function arithmeticUpdate(string $column, string $operator, $value): self { 
            $this->set([
                $column => new RawExpression(expression: "{$this->escapeColumn(column: $column)}{$operator} ?", bindings: [$value])
            ]);
            return $this;
        }

        public function debug(): self {
            $method = 'build' . ucfirst(string: strtolower(string: $this->type));
            if (!method_exists(object_or_class: $this, method: $method)) {
                throw new \BadMethodCallException(message: "Can not debug unsupported query type");
            }
            [$sql, $params] = $this->$method();
            echo "=== DEBUG QUERY ===";
            echo "SQL: $sql \n";
            print_r(value: $params);
            echo "\n==================\n";
            return $this;
        }

        public function count(): int {
            $originalSelect = $this->components['select'];
            $this->components['select'] = "COUNT(*) as count";
            $result = $this->execute();
            $this->components['select'] = $originalSelect;
            return (int) ($result[0]['count'] ?? 0);
        }

        public function toSql(): array {
            $method = 'build' . ucfirst(string: strtolower(string: $this->type));
            if (!method_exists(object_or_class: $this, method: $method)) {
                throw new QueryException(message: "Unsupported query type", sql: '');
            }
            return $this->$method();
        }

        public function execute(): array|bool {
            $method = 'build' . ucfirst(string: strtolower(string: $this->type));
            if (!method_exists(object_or_class: $this, method: $method)) {
                throw new QueryException(message: "Unsupported query type: {$this->type}", sql: '');
            }

            [$sql, $params] = $this->$method();
            $stmt = $this->db->query(sql: $sql, params: $params);                            

            return $this->type === 'SELECT' ? $stmt->fetchAll() : true;
        }

        public function queue(): self { 
            $this->isQueued = true;
            $this->db->addToQueue(query: $this);
            return $this;
        }
    
        public function isQueued(): bool {
            return $this->isQueued;
        }

        public function offset(int $offset): self {
            $this->components['offset'] = $offset;
            return $this;
        }

        public function joinSub(QueryBuilder $subquery, string $alias, string $first, string $operator, string $second, string $type = 'INNER'): self { 
            [$subSql, $subParams] = $subquery->buildSelect();
            $this->components['join'][] = [
                'type'     => $type,
                'table'    => "({$subSql}) AS {$this->escapeTable(table: $alias)}",
                'first'    => $first,
                'operator' => $operator,
                'second'   => $second
            ];
            $this->components['params'] = array_merge($this->components['params'] ?? [], $subParams);
            return $this;
        }

        public function leftJoinSub(QueryBuilder $subquery, string $alias, string $first, string $operator, string $second): self {
            return $this->joinSub(subquery: $subquery, alias: $alias, first: $first, operator: $operator, second: $second, type: 'LEFT');
        }

        public function rightJoinSub(QueryBuilder $subquery, string $alias, string $first, string $operator, string $second): self {
            return $this->joinSub(subquery: $subquery, alias: $alias, first: $first, operator: $operator, second: $second, type: 'RIGHT');
        }
        
        private function buildOffset(): string {
            if ($this->components['offset'] === null) {
                return '';
            }
        
            return match($this->db->getDriver()) {
                'mysql', 'pgsql' => " OFFSET {$this->components['offset']}",
                'sqlite' => " LIMIT -1 OFFSET {$this->components['offset']}",
                default  => ''
            };
        }

        private function buildGroupBy(): string { 
            if (empty($this->components['groupBy'])) {
                return '';
            }
            return ' GROUP BY ' . implode(separator: ', ', array: $this->components['groupBy']);
        }

        private function buildHaving(): string { 
            if (empty($this->components['having'])) {
                return '';
            }
            return ' HAVING ' . $this->components['having'];
        }

        private function buildSelect(): array {
            $select = is_array(value: $this->components['select']) ? implode(separator: ', ', array: $this->components['select']) : $this->components['select'];
            $sql = "SELECT $select FROM {$this->table}";

            if (!empty($this->components['join'])) {
                foreach($this->components['join'] as $join) {
                    if (is_array(value: $join)) {
                        $sql .= " {$join['type']} JOIN {$join['table']} ON {$join['first']} {$join['operator']} {$join['second']}";
                    } else {
                        $sql .= " $join";
                    }
                }
            }

            if (!empty($this->components['where'])) {
                $sql .= ' WHERE ' . implode(separator: ' AND ', array: $this->components['where']);
            }

            if (!empty($this->components['order'])) {
                $sql .= ' ORDER BY ' . implode(separator: ', ', array: $this->components['order']);
            }

            if ($this->components['limit']) {
                $sql .= " LIMIT {$this->components['limit']}";
            }
            $sql .= $this->buildGroupBy(); 
            $sql .= $this->buildHaving(); 
            $sql .= $this->buildOffset();
            return [$sql, $this->components['params'] ?? []];
        }

        private function buildInsert(): array {
            $columns = implode(separator: ', ', array: array_keys($this->components['data']));
            $placeholders = implode(separator: ', ', array: array_fill(start_index: 0, count: count(value: $this->components['data']), value: '?'));
            $sql = "INSERT INTO {$this->table} ($columns) VALUES ($placeholders)";
            return [$sql, array_values(array: $this->components['data'])];
        }

        private function buildUpdate(): array {
            if (empty($this->components['data'])) {
                throw new QueryException(message: "Can not build UPDATE: no data provided", sql: '');
            }
            $setClauses = [];
            $params = [];
            foreach ($this->components['data'] as $column => $value) {
                if ($value instanceof RawExpression) {
                    $setClauses[] = "{$this->escapeColumn(column: $column)} = {$value->getExpression()}";
                    $params = array_merge($params, $value->getBindings());
                } else {
                    $setClauses[] = "{$this->escapeColumn(column: $column)} = ?";
                    $params[] = $value;
                }
            }

            $sql = "UPDATE {$this->escapeTable(table: $this->table)} SET " . implode(separator: ', ', array: $setClauses);

            if (!empty($this->components['where'])) {
                $sql .= " WHERE " . implode(separator: ' AND ', array: $this->components['where']);
                $params = array_merge($params, $this->components['params'] ?? []);
            }

            return [$sql, $params];
        }

        private function buildDelete(): array {
            $sql = "DELETE FROM {$this->table}";
            $params = [];

            if (!empty($this->components['where'])) {
                $sql .= ' WHERE ' . implode(separator: ' AND ', array: $this->components['where']);
                $params = $this->components['params'] ?? [];
            }

            return [$sql, $params];
        }

        private function buildJoin(): array {
            $joinSql = '';
            $joinParams = [];
            foreach($this->components['join'] as $join) {
                $joinClause = " {$join['type']} JOIN {$join['table']}";
                if (isset($join['first'])) {
                    $joinClause .= " ON {$this->escapeColumn(column: $join['first'])} {$join['operator']} {$this->escapeColumn(column: $join['second'])}";
                }
                $joinSql .= $joinClause;
            }
            return [$joinSql, $joinParams];
        }
    }