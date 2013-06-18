<?php

// Prevent access via command line interface
// if (PHP_SAPI === 'cli') {
//     exit('bootstrap.php must not be run via the command line interface');
// }
echo 'boohoo';
// Settings
$key    = 'XXX';
$secret = 'XXX';

$CRYPT_PRHASE = 'XXX';

$backup_source = 'testfiles';

// Set error reporting
error_reporting(-1);
ini_set('display_errors', 'On');
ini_set('html_errors', 'On');

/**
 * scans all files of specific folder into flat array recursively
 */
function scandirectory($dir="") {
    $items = glob($dir . '/*');

    for ($i = 0; $i < count($items); $i++) {
        if (is_dir($items[$i])) {
            $add = glob($items[$i] . '/*');
            $items = array_merge($items, $add);
        }
    }

    return $items;
}

// Register a simple autoload function
spl_autoload_register(function($class){
    $class = str_replace('\\', '/', $class);
    require_once('' . $class . '.php');
});

// Check whether to use HTTPS and set the callback URL
$protocol = (!empty($_SERVER['HTTPS'])) ? 'https' : 'http';
$callback = $protocol . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];

// // Instantiate the Encrypter, storage, Auth and Dropbox objects
$encrypter = new \Dropbox\OAuth\Storage\Encrypter($CRYPT_PRHASE);
$storage = new \Dropbox\OAuth\Storage\Session($encrypter);
$OAuth = new \Dropbox\OAuth\Consumer\Curl($key, $secret, $storage, $callback);
$dropbox = new \Dropbox\API($OAuth);

// // prepare file_paths
$tmp = scandirectory($backup_source);
$target_directory = 'backup_'.time().'/';

try {
    foreach ($tmp AS $k => $v) {
        $path_parts = pathinfo($v);
        $path = $target_directory.$path_parts['dirname'];

        if (is_dir($v)) {
            // create folder
            $create = $dropbox->create($path.'/'.basename($v));
        } else {
            // put file
            $put = $dropbox->putFile($v, basename($v), $path);
        }

        echo $v."<br>";
    }
} catch (\Dropbox\Exception\BadRequestException $e) {
    echo 'Invalid file extension'; // ignored thumbs.db and .ds_store
}
?>