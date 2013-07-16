<?php

namespace MarkLogic\WordPressSearch;

require(dirname(dirname(__FILE__)) 
    . DIRECTORY_SEPARATOR . 'vendor' 
    . DIRECTORY_SEPARATOR . 'autoload.php');

use MarkLogic\MLPHP;

try {
    $client = new MLPHP\RESTClient(
        $_POST['host'],
        $_POST['port'],
        '',
        'v1',
        $_POST['mluser'],
        $_POST['mlpassword'],
        "digest"
    );
    
    $search = new MLPHP\Search($client);
    $search->setDirectory('/');
    $results = $search->retrieve("")->getTotal();
} catch (\Exception $e) {
    exit($e->getMessage());
}
    
echo "Success (". $results . " documents directly under /)";

?>
