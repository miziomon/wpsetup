<?php

/**
 * Alternative and automatic install script for custom Wordpress Setup
 *
 * @author maurizio
 */
class Wpsetup {

    function __construct() {

        

        $this->downloadLatest();
        $this->extractZip();
        $this->structureSetup();

        $this->createConfig();
        $this->createLocalConfig();

        $this->installWordpress();

        //$this->cleanTemp();
    }

    /**
     * 
     */
    function installWordpress() {

        /*
        define('WP_UPLOAD_DIR', dirname("wp-config.php") . '/files');
        define('WP_CONTENT_DIR', dirname("wp-config.php") . '/assets');
        define('WP_CONTENT_URL', 'http://' . $_SERVER['HTTP_HOST'] . '/assets');

        define('WP_SITEURL', 'http://' . $_SERVER['SERVER_NAME'] . '/app/');
        define('WP_HOME', 'http://' . $_SERVER['SERVER_NAME']);
         * 
         */


        define('WP_INSTALLING', true);
        define('ABSPATH', dirname("wp-config.php") . "/app/");
        require_once 'wp-config.php';
        require_once ABSPATH . '/wp-settings.php';
        require_once ABSPATH . '/wp-admin/includes/upgrade.php';
        require_once ABSPATH . '/wp-includes/wp-db.php';

        $_SERVER['SERVER_PROTOCOL'] = 'HTTP/1.1';
        $PHP_SELF = $GLOBALS['PHP_SELF'] = $_SERVER['PHP_SELF'] = '/index.php';

        $title = "wpsetup";
        $password = substr(md5(rand(1000, 9999)), -12);
        $user = "admin";
        $email = "maurizio@mavida.com";

        echo "Installazione \n";
        wp_install($title, $user, $email, true, '', $password);
    }

    /**
     * Create local configuration
     */
    function createLocalConfig() {

        $wp_config = "<?php
            //Local Configuration
             
            define('DB_NAME', 'wpsetup');
            define('DB_USER', 'root');
            define('DB_PASSWORD', '');
            define('DB_HOST', 'localhost');
            define('DB_CHARSET', 'utf8');
            define('DB_COLLATE', '');
            ";

        file_put_contents("wp-config-local.php", str_replace(' ', '', $wp_config));
    }

    /**
     * Generate wp-config from custom wp-config-sample.php
     */
    function createConfig() {

        $salt = file_get_contents('https://api.wordpress.org/secret-key/1.1/salt/');
        $secret_keys = explode("\n", $salt);

        //$config_file = file( dirname(__FILE__) . '/wp-config-sample.php');
        $config_file = file( "phar://wpsetup.phar/wp-config-sample.php");
        

        
        //define('DB_NAME', "");
        //define('DB_USER', "");
        //define('DB_PASSWORD', "");
        //define('DB_HOST', "");
        //define('WPLANG', "it_IT");

        $config['DB_NAME'] = "";
        $config['DB_USER'] = "";
        $config['DB_PASSWORD'] = "";
        $config['DB_HOST'] = "";
        $config['WPLANG'] = "it_IT";
        
        
        $prefix = substr(md5(rand(1000, 9999)), -5) . "_";

        $key = 0;
        // Not a PHP5-style by-reference foreach, as this file must be parseable by PHP4.
        foreach ($config_file as $line_num => $line) {
            if ('$table_prefix  =' == substr($line, 0, 16)) {
                $config_file[$line_num] = '$table_prefix  = \'' . addcslashes($prefix, "\\'") . "';\r\n";
                continue;
            }

            if (!preg_match('/^define\(\'([A-Z_]+)\',([ ]+)/', $line, $match))
                continue;

            $constant = $match[1];
            $padding = $match[2];

            switch ($constant) {
                case 'DB_NAME' :
                case 'DB_USER' :
                case 'DB_PASSWORD' :
                case 'DB_HOST' :
                case 'WPLANG' :
                    //$config_file[$line_num] = "define('" . $constant . "'," . $padding . "'" . addcslashes(constant($constant), "\\'") . "');\r\n";
                    $config_file[$line_num] = "define('" . $constant . "'," . $padding . "'" . addcslashes( $config[$constant], "\\'") . "');\r\n";                    
                    
                    break;

                case 'AUTH_KEY' :
                case 'SECURE_AUTH_KEY' :
                case 'LOGGED_IN_KEY' :
                case 'NONCE_KEY' :
                case 'AUTH_SALT' :
                case 'SECURE_AUTH_SALT' :
                case 'LOGGED_IN_SALT' :
                case 'NONCE_SALT' :
                    //$config_file[$line_num] = "define('" . $constant . "'," . $padding . "'" . $secret_keys[$key++] . "');\r\n";
                    $config_file[$line_num] = $secret_keys[$key++] . "\r\n";
                    break;
            }
        }

        $wp_config = implode("", $config_file);
        file_put_contents("wp-config.php", $wp_config);
    }

    /**
     * 
     */
    function downloadLatest($to = "latest.zip") {

        $from = "http://wordpress.org/latest.zip";

        file_put_contents($to, file_get_contents($from));
    }

    /**
     * 
     */
    function extractZip($to = "latest.zip") {



        $zip = new ZipArchive;
        $res = $zip->open($to);
        $zip->extractTo(getcwd());
        $zip->close();
    }

    /**
     * 
     */
    function structureSetup() {


        rename("wordpress", "app");
        mkdir("files");
        mkdir("assets");
        $this->recursiveCopy( getcwd() . "/app/wp-content", getcwd() . "/assets");

        $index = "<?php define('WP_USE_THEMES', true); require( 'app/wp-blog-header.php');";

        file_put_contents("index.php", $index);
    }

    /**
     * 
     */
    function cleanTemp() {

        @unlink("latest.zip");
    }

    /**
     * function utility to directory copy
     * @param type $src
     * @param type $dst
     */
    function recursiveCopy($src, $dst) {
        $dir = opendir($src);
        @mkdir($dst);
        while (false !== ( $file = readdir($dir))) {
            if (( $file != '.' ) && ( $file != '..' )) {
                if (is_dir($src . '/' . $file)) {
                    $this->recursiveCopy($src . '/' . $file, $dst . '/' . $file);
                } else {
                    copy($src . '/' . $file, $dst . '/' . $file);
                }
            }
        }
        closedir($dir);
    }

}

// end class

new wpsetup();

echo "<pre>";