<?php

class Database
{
    private $host;
    private $username;
    private $password;
    private $database;
    private $charset;
    private $connection;

    public function __construct()
    {
        $this->host = DB_HOST;
        $this->username = DB_USERNAME;
        $this->password = DB_PASSWORD;
        $this->database = DB_NAME;
        $this->charset = DB_CHARSET;
    }

    public function connect()
    {
        if ($this->connection instanceof mysqli) {
            return $this->connection;
        }

        mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

        try {
            $this->connection = new mysqli(
                $this->host,
                $this->username,
                $this->password,
                $this->database
            );
            $this->connection->set_charset($this->charset);
        } catch (mysqli_sql_exception $exception) {
            die('Database connection failed.');
        }

        return $this->connection;
    }

    public function close()
    {
        if ($this->connection instanceof mysqli) {
            $this->connection->close();
            $this->connection = null;
        }
    }
}
