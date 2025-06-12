<?php

namespace IareCrm\Traits;

defined('ABSPATH') || exit;

trait Singleton {
    private static $instances = [];

    public static function get_instance() {
        $called_class = get_called_class();
        
        if (!isset(self::$instances[$called_class])) {
            self::$instances[$called_class] = new $called_class();
        }
        
        return self::$instances[$called_class];
    }

    private function __clone() {}

    public function __wakeup() {
        throw new \Exception("Cannot unserialize singleton");
    }
} 