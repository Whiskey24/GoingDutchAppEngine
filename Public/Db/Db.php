<?php
class Db{
    // http://www.phpro.org/tutorials/Introduction-to-PHP-PDO.html#4

    /*** Declare instance ***/
    private static $instance = NULL;

    /**
     *
     * the constructor is set to private so
     * so nobody can create a new instance using new
     *
     */
    private function __construct() {
        /*** maybe set the db name here later ***/
    }

    /**
     *
     * Return DB instance or create initial connection
     *
     * @return object (PDO)
     *
     * @access public
     *
     */
    public static function getInstance() {

        if (!self::$instance)
        {
            $config = parse_ini_file('dbconfig.ini', true);
            //$a="mysql:host={$config['database']['host']};dbname={$config['database']['dbname']}";
            self::$instance = new PDO("mysql:host={$config['database']['host']};dbname={$config['database']['dbname']}", $config['database']['user'], $config['database']['pass'],  array(PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8"));
            self::$instance-> setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_WARNING );
        }
        return self::$instance;
    }


    /**
     *
     * Like the constructor, we make __clone private
     * so nobody can clone the instance
     *
     */
    private function __clone(){
    }

} /*** end of class ***/

/*
try    {
    // query the database
    $result = DB::getInstance()->query("SELECT * FROM animals");

    // loop over the results
    foreach($result as $row)
    {
        print $row['animal_type'] .' - '. $row['animal_name'] . '<br />';
    }
}
catch(PDOException $e)
{
    echo $e->getMessage();
}*/
