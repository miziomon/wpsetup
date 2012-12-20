<?php

$phar = new Phar( "../build/wpsetup.phar", 
            FilesystemIterator::CURRENT_AS_FILEINFO |  FilesystemIterator::KEY_AS_FILENAME, 
            "wpsetup.phar");

$phar["index.php"] = file_get_contents("../src/index.php");
$phar["wp-config-sample.php"] = file_get_contents("../src/wp-config-sample.php");
$phar->setStub($phar->createDefaultStub("index.php"));