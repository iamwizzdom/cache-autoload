# cache-autoload
cache-autoload is a high speed performance autoloader for lazy programmers. 
cache-autoload searches your project and includes the necessary classes and interfaces needed for your script to run. 
The cool thing is that cache-autoload can location your files even when they are not structured, 
which means you can create your files anywhere in your project and cache-autoload will still find it for you.


#use

```
<?php

define('APP_ROOT', 'Your project root folder');

require "app.autoload.php";

?>

```
