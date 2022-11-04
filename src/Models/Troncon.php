<?php

namespace Rockndonuts\Hackqc\Models;

class Troncon extends DB
{
    public const TABLE_NAME = "troncons";

    /**
     * Finds all troncons with its borough
     * @param array|null $fields
     * @return bool|array
     */
    public function findAllWithBoroughs(?array $fields = null): bool|array
    {
        $table = self::TABLE_NAME;
        $boroughsTable = Borough::TABLE_NAME;

        $selectString = " t.* ";
        if (!empty($fields)) {
            $selectString = "";
            foreach ($fields as $field) {
                $selectString .= " t.$field,";
            }
            $selectString = rtrim($selectString, ",");
        }

        $query = <<<SQL
            SELECT $selectString, b.name FROM $table t
            LEFT JOIN $boroughsTable b ON t.borough_id = b.id
        SQL;


        return $this->executeQuery($query);
    }

}