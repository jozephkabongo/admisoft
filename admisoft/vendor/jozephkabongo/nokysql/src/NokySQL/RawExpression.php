<?php
    namespace NokySQL;

    class RawExpression {
        private string $expression;
        private array $bindings;

        public function __construct(string $expression, array $bindings = []) {
            $this->expression = $expression;
            $this->bindings = $bindings;
        } 

        public function getExpression(): string {
            return $this->expression;
        }

        public function getBindings(): array {
            return $this->bindings;
        }
    }

    function raw(string $expr, array $bindings): RawExpression {
        return new RawExpression(expression: $expr, bindings: $bindings);
    }