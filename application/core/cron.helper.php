<?php

    define('LOCK_FILE',  APPPATH . 'core/crons/file.lock');
    
    class cronHelper {

        private static $pid;

        function __construct() {}

        function __clone() {}

        private static function isrunning() {
            $lock_file = LOCK_FILE;

            if (file_exists($lock_file)) {
                return TRUE;
            } else {
                return FALSE;
            }
        }

        public static function lock() {            

            $lock_file = LOCK_FILE;

            if (self::isrunning()) {                
                return FALSE;
            }
                
            self::$pid = getmypid();
            file_put_contents($lock_file, self::$pid);
            
            return self::$pid;
        }

        public static function unlock() {

            $lock_file = LOCK_FILE;

            if (file_exists($lock_file))
                unlink($lock_file);
            
            return TRUE;
        }

    }

?>
