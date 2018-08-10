<?php
/**
 * Created by PhpStorm.
 * User: Wisdom Emenike
 * Date: 7/31/2018
 * Time: 2:35 PM
 * Git: https://github.com/iamwizzdom/cache-autoload
 */

class CacheAutoload
{

    private static $loader = [];

    private static $except = [
        'assets',
        'tmp',
        'uploads',
        'template'
    ];

    private static $suffix = [
        '.php',
        '.class.php'
    ];

    private static $root_dir = APP_PATH;

    private static function setLoader(string $key, string $value){
        self::$loader[$key] = $value;
        self::storeLoader();
    }

    private static function getLoader()
    {
        $loader = [];

        if (empty(self::$loader)) {
            if (file_exists("loader.json")) {
                $loader_file = @fopen("loader.json", "r") or die("Unable to open <b>loader.json</b> file!");
                $loader_json_file = @fread($loader_file, filesize("loader.json"));
                if (strlen(trim($loader_json_file)) > 0) {
                    $loader = json_decode($loader_json_file, true);
                }
                @fclose($loader_file);
            }
            return $loader;
        } else {
            return self::$loader;
        }

    }

    private static function storeLoader()
    {
        $loader = @fopen("loader.json", "w") or die("Unable to open <b>loader.json</b> file!");
        @fwrite($loader, json_encode(self::getLoader(), JSON_PRETTY_PRINT));
        @fclose($loader);
    }

    public static function init()
    {

        spl_autoload_register(function ($name) {

            self::$loader = self::getLoader();

            $hash = hash("SHA1", $name);

            if (array_key_exists($hash, self::$loader)) {

                $path_arr = explode("/", self::$loader[$hash]);
                foreach (self::$except as $exc) {
                    if (in_array($exc, $path_arr)) {
                        return false;
                    }
                }
                require self::$loader[$hash];

            } else {

                self::findFile(self::$root_dir, $name, self::$except);

            }
            return true;
        });

    }

    private static function findFile($dir, $fileName, $except = [])
    {
        $glob = glob($dir . "/*");
        foreach ($glob as $path) {

            $dir_name = explode("/", str_replace("\\", "/", $path));

            if (in_array($dir_name[(count($dir_name) - 1)], $except)) {
                continue;
            }

            if (is_dir($path)) {

                $class = str_replace("\\", "/", $fileName);
                $class = explode("/", $class);
                $filePath = ""; $count = 0; $suffix_size = count(self::$suffix);
                while (empty($filePath) && $count < $suffix_size) {
                    $file = $path . "/" . $class[(count($class) - 1)] . self::$suffix[$count];
                    if (is_file($file)) {
                        $filePath = $file;
                    }
                    $count++;
                }

                if (!empty($filePath)) {
                    $scan = self::scanFile($filePath, $fileName);
                    if ($scan === true) {
                        self::setLoader(hash("SHA1", $fileName), $filePath);
                        require $filePath;
                        break;
                    } else {
                        self::findFile($path, $fileName, $except);
                    }
                } else {
                    self::findFile($path, $fileName, $except);
                }

            }
        }

    }

    private static function scanFile(string $filePath, string $class_name)
    {
        $namespace = self::getNamespace($filePath);
        $className = self::getClassName($filePath);
        if ($namespace !== false) {
            if ($class_name == ($namespace . "\\" . $className)) {
                return true;
            } else {
                return false;
            }
        } else {
            if ($class_name == $className) {
                return true;
            } else {
                return false;
            }
        }
    }

    private static function getNamespace($filePath)
    {
        $src = file_get_contents($filePath);
        $tokens = token_get_all($src);
        $count = count($tokens);
        $i = 0;
        $namespace = '';
        $namespace_ok = false;
        while ($i < $count) {
            $token = $tokens[$i];
            if (is_array($token) && $token[0] === T_NAMESPACE) {
                // Found namespace declaration
                while (++$i < $count) {
                    if ($tokens[$i] === ';') {
                        $namespace_ok = true;
                        $namespace = trim($namespace);
                        break;
                    }
                    $namespace .= is_array($tokens[$i]) ? $tokens[$i][1] : $tokens[$i];
                }
                break;
            }
            $i++;
        }
        if (!$namespace_ok) {
            return false;
        } else {
            return $namespace;
        }
    }

    private static function getClassName($filePath)
    {
        $php_code = file_get_contents($filePath);

        $classes = array();
        $tokens = token_get_all($php_code);
        $count = count($tokens);
        for ($i = 2; $i < $count; $i++) {
            if ($tokens[$i - 2][0] == T_CLASS
                && $tokens[$i - 1][0] == T_WHITESPACE
                && $tokens[$i][0] == T_STRING
            ) {

                $class_name = $tokens[$i][1];
                $classes[] = $class_name;
            }
        }

        if (!array_key_exists(0, $classes)) {
            return '';
        }
        return $classes[0];
    }

}

CacheAutoload::init();