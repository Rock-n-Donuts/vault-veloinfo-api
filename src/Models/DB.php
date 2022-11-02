<?php

namespace Rockndonuts\Hackqc\Models;

use PDO;
use PDOException;

class DB
{
    private $dbHandle;

    public function __construct()
    {
        $host = $_ENV['DB_HOST'];
        $port = $_ENV['DB_PORT'];
        $pwd = $_ENV['DB_PWD'];
        $dbName = $_ENV['DB_NAME'];
        $dbUser = $_ENV['DB_USER'];
        try {
            $this->dbHandle = new PDO(
                'mysql:host=' . $host . ';dbname=' . $dbName . ';charset=utf8', $dbUser , $pwd
            );
        } catch (PDOException $e) {
            die("error, please try again");
        }
    }

    public function get(string $query, mixed $params): mixed
    {
        $statement = $this->dbHandle->prepare($query);
        $statement->execute();
        $results = $statement->fetchAll(PDO::FETCH_ASSOC);

    }

    public function executeQuery(string $query)
    {
        return $this->dbHandle->query($query)->fetchAll(PDO::FETCH_ASSOC);
    }

    public function insert(array $data, ?string $table = null): mixed
    {
        if (!$table) {
            $table = static::TABLE_NAME;
        }
        $fields = array_keys($data);
        $fieldString = implode(",", $fields);
        $placeHolder = str_repeat('?,', count($data) - 1) . '?';
        $query = <<<SQL
            INSERT INTO $table ($fieldString) VALUES ($placeHolder)
        SQL;

        $statement = $this->dbHandle->prepare($query);
        $statement->execute(array_values($data));

        return $this->dbHandle->lastInsertId();
    }

    public function findAll(?string $table = null): mixed
    {
        if (!$table) {
            $table = static::TABLE_NAME;
        }

        $query = <<<SQL
            SELECT * FROM $table
        SQL;

        $statement = $this->dbHandle->prepare($query);
        $statement->execute();
        return $statement->fetchAll( PDO::FETCH_ASSOC);
    }

    public function findBy(mixed $data, ?string $table = null): mixed
    {
        if (empty($data)) {
            return $this->findAll($table);
        }

        if (!$table) {
            $table = static::TABLE_NAME;
        }

        $fields = array_keys($data);
        $values = array_values($data);

        $whereString = "";
        foreach ($fields as $field) {
            $operator = "=";
            if ($field === "created_at") {
                $operator = ">";
            }
            $whereString .= " ". $field . " $operator ?";
        }

        $query = <<<SQL
            SELECT * FROM $table
            WHERE $whereString
        SQL;

        $statement = $this->dbHandle->prepare($query);
        $statement->execute($values);

        return $statement->fetchAll( PDO::FETCH_ASSOC);
    }

    public function update(int $objectId, array $fields, ?string $table = null, ?string $idKey = "id")
    {
        if (!$table) {
            $table = static::TABLE_NAME;
        }

        $updateString = "";
        foreach ($fields as $fieldName => $fieldValue) {
            $updateString .= " $fieldName = :$fieldName, ";
        }
        $updateString = rtrim($updateString, ', ');

        $query = <<<SQL
            UPDATE $table SET $updateString WHERE $idKey = $objectId
        SQL;

        return $this->dbHandle->prepare($query)->execute($fields);
    }

}