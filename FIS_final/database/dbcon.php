<?php
    class Database{
        private $servername = "localhost";
        private $username = "root";
        private $password = "";
        private $dbname = "final";

        protected $conn;

        public function connect(){
            $this->conn = new PDO("mysql:host=$this->servername;dbname=$this->dbname", $this->username, $this->password);
            return $this->conn;
        }

    }
?>