<?php
/**
 * @author     Sergey Kuznetsov <clk824@gmail.com>
 * @license    LICENSE
 */

if ($_SERVER["REQUEST_METHOD"]) { header("HTTP/1.0 403 Forbidden"); die("Forbidden"); }

require "config.php";

function globr($mask, $dir = "") {
  $files = array();

  if ($dir) {
    chdir($dir);
  }

  foreach (glob("{". strtolower($mask) . "," . strtoupper($mask) . "}", GLOB_BRACE) as $file) {
    array_push($files, $file);
  }

  foreach (glob("*", GLOB_ONLYDIR) as $subdir) {
    $files = array_merge($files, globr($mask, "$subdir"));
    chdir("..");
  }

  foreach ($files as &$file) {
    $file = "$dir/$file";
  }

  return $files;
}

chdir($music_dir);
$dir = getcwd();
$files = globr("*.mp3");
shuffle($files);
print_r($files);

file_put_contents("$dir/playlist", serialize($files));