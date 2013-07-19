<?php

namespace MarkLogic\WordPressSearch;

require(dirname(dirname(__FILE__)) 
    . DIRECTORY_SEPARATOR . 'vendor' 
    . DIRECTORY_SEPARATOR . 'autoload.php');

spl_autoload_register(function($class){
    $path = str_replace('MarkLogic\\WordPressSearch\\', '', $class);
    $path = str_replace(array('_', "\\"), DIRECTORY_SEPARATOR, $path);

    if (file_exists($path . '.php')) {
        require_once($path . '.php');
    }
});

try {
    Api::reloadAll();
} catch (\Exception $e) {
    exit($e->getMessage());
}
    
echo "Success";


