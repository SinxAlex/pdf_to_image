<?php

namespace PdfToImage;

class MysqlDatabase
{
    private $connection;
    private $last_query;
    private $magic_quotes_active;
    private $real_escape_string_exists;

    // Конструктор класса - автоматически создает подключение
    public function __construct($host, $username, $password, $database) {
        $this->open_connection($host, $username, $password, $database);

        $this->magic_quotes_active = get_magic_quotes_gpc();
        $this->real_escape_string_exists = function_exists("mysqli_real_escape_string");
    }

    // Метод для открытия соединения с базой данных
    public function open_connection($host, $username, $password, $database) {
        $this->connection = mysqli_connect($host, $username, $password, $database);

        if (!$this->connection) {
            die("Ошибка подключения к базе данных: " . mysqli_connect_error());
        }

        // Установка кодировки UTF-8
        mysqli_set_charset($this->connection, "utf8");
    }

    // Метод для закрытия соединения
    public function close_connection() {
        if (isset($this->connection)) {
            mysqli_close($this->connection);
            unset($this->connection);
        }
    }

    // Метод для выполнения SQL запроса
    public function query($sql) {
        $this->last_query = $sql;
        $result = mysqli_query($this->connection, $sql);
        $this->confirm_query($result);
        return $result;
    }

    // Вспомогательный метод для проверки запроса
    private function confirm_query($result) {
        if (!$result) {
            $output = "Ошибка в SQL запросе: " . mysqli_error($this->connection) . "<br><br>";
            $output .= "Последний SQL запрос: " . $this->last_query;
            die($output);
        }
    }

    // Экранирование специальных символов в строке для использования в SQL запросе
    public function escape_value($value) {
        if ($this->real_escape_string_exists) {
            if ($this->magic_quotes_active) {
                $value = stripslashes($value);
            }
            $value = mysqli_real_escape_string($this->connection, $value);
        } else {
            if (!$this->magic_quotes_active) {
                $value = addslashes($value);
            }
        }
        return $value;
    }

    // Метод для получения массива значений из результата запроса
    public function fetch_array($result_set) {
        return mysqli_fetch_array($result_set);
    }

    // Метод для получения количества строк в результате запроса
    public function num_rows($result_set) {
        return mysqli_num_rows($result_set);
    }

    // Метод для получения ID последней вставленной записи
    public function insert_id() {
        return mysqli_insert_id($this->connection);
    }

    // Метод для получения количества затронутых строк
    public function affected_rows() {
        return mysqli_affected_rows($this->connection);
    }

    // Деструктор класса - автоматически закрывает соединение
    public function __destruct() {
        $this->close_connection();
    }
}