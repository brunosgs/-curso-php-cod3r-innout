<?php

class Model
{
    protected static $tableName = '';
    protected static $columns = [];
    protected $values = [];

    // Construtor
    function __construct($arr)
    {
        $this->loadFromArray($arr);
    }

    // Métodos mágicos get/set
    public function __get($key)
    {
        return isset($this->values[$key]) ? $this->values[$key] : null;
    }

    public function __set($key, $value)
    {
        $this->values[$key] = $value;
    }

    public function loadFromArray($arr)
    {
        if ($arr) {
            foreach ($arr as $key => $value) {
                $this->$key = $value;
            }
        }
    }

    public static function get($filters = [], $columns = '*')
    {
        $objects = [];
        $result = static::getResultSetFromSelect($filters, $columns);

        if ($result) {
            $class = get_called_class(); // Obtém o nome da classe em que o método estático é chamado.

            while ($row = $result->fetch_assoc()) {
                array_push($objects, new $class($row));
            }
        }

        return $objects;
    }

    // Localiza o usuário no banco de dados
    public static function getOne($filters = [], $columns = '*')
    {
        $class = get_called_class(); // Obtém o nome da classe em que o método estático é chamado.
        $result = static::getResultSetFromSelect($filters, $columns);

        return $result ? new $class($result->fetch_assoc()) : null;
    }

    // Gera o select conforme os parametros passados
    public static function getResultSetFromSelect($filters = [], $columns = '*')
    {
        $sql = "select $columns from " . static::$tableName . static::getFilters($filters);
        $result = Database::getResultFromQuery($sql);

        if ($result->num_rows === 0) {
            return null;
        } else {
            return $result;
        }
    }

    // Salva no banco de dados
    public function insert()
    {
        $sql = "insert into " . static::$tableName . " (" . implode(", ", static::$columns) . ") values (";

        foreach (static::$columns as $col) {
            $sql .= static::getFormatedValue($this->$col) . ", ";
        }

        $sql[strlen($sql) - 2] = ')';
        $id = Database::executeSQL($sql);
        $this->$id = $id;
    }

    //Atualiza os dados
    public function update()
    {
        $sql = "update " . static::$tableName . " set ";

        foreach (static::$columns as $col) {
            $sql .= "${col} = " . static::getFormatedValue($this->$col) . ",";
        }

        $sql[strlen($sql) - 1] = ' ';
        $sql .= "where id = {$this->id}";

        Database::executeSQL($sql);
    }

    // Filtro para o SQL
    private static function getFilters($filters)
    {
        $sql = '';

        if (count($filters) > 0) {
            $sql .= " where 1 = 1";

            foreach ($filters as $column => $value) {
                if ($column == 'raw') {
                    $sql .= " and {$value}";
                } else {
                    $sql .= " and $column = " . static::getFormatedValue($value);
                }
            }
        }

        return $sql;
    }

    private static function getFormatedValue($value)
    {
        if (is_null($value)) {
            return 'null';
        } elseif (gettype($value) === 'string') {
            return "'$value'";
        } else {
            return $value;
        }
    }
}
