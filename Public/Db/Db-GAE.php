<?php
use google\appengine\api\taskqueue\PushTask;
use google\appengine\api\taskqueue\PushQueue;

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
            if (strpos($_SERVER['SERVER_SOFTWARE'], 'Development', 0) === 0) {
                // Dev environment
                //error_log('3. Creating new PDO DEV instance', 0);
                $dsn = getenv('MYSQL_DEV_DSN');
                $user = getenv('MYSQL_DEV_USERNAME');
                $password = getenv('MYSQL_DEV_PASSWORD');
            } else {
                //error_log('3. Creating new PDO PROD instance',0);
                $dsn = getenv('MYSQL_DSN');
                $user = getenv('MYSQL_USERNAME');
                $password = getenv('MYSQL_PASSWORD');
            }
            self::$instance =  new PDO($dsn, $user, $password);
            self::$instance->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_WARNING);
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

    public static function triggerSendMail() {
        $task = new PushTask('/tasks/email');
        $queue = new PushQueue('email-queue');
        $queue->addTasks([$task]);
    }

} /*** end of class ***/