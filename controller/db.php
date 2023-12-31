<?php

class DB {
    // database read and write connection variables
    private static $writeDBConnection;
    private static $readDBConnection;
    //function to write to the database 
    public static function connectWriteDB(){
        if(self::$writeDBConnection === null) {
            self::$writeDBConnection = new PDO('mysql:host=localhost;dbname=tasks_db;charset=utf8', 'root', '');
            self::$writeDBConnection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            self::$writeDBConnection->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
        }

        return self::$writeDBConnection;
    }

     //function to read to the database 
     public static function connectReadDB(){
        if(self::$readDBConnection === null) {
            self::$readDBConnection = new PDO('mysql:host=localhost;dbname=tasks_db;charset=utf8', 'root', '');
            self::$readDBConnection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            self::$readDBConnection->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
        }

        return self::$readDBConnection;
    }
}