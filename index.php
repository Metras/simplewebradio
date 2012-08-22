<?php
/**
 * @author     Sergey Kuznetsov <clk824@gmail.com>
 * @license    LICENSE
 */
 
require "config.php";
require_once "MP3/Id.php";

function convert_to_string($string) {
  global $local_enc;
  return mb_convert_encoding($string, "UTF-8", $local_enc);
}

function convert_to_url($string) {
  $string = explode("/", $string);

  foreach ($string as &$s) {
    $s = rawurlencode(convert_to_string($s));
  }

  return implode("/", $string);
}

function album_art($file) {
  global $fallback_album_art;

  $pathinfo = pathinfo($file);
  $ret = "";
  $template = "%s.jpg,%s.png,%s.bmp,%s.JPG,%s.PNG,%s.BMP";
  $mask = "";

  if ($file && $pathinfo["dirname"]) {
    chdir($pathinfo["dirname"]);

    foreach (explode(",", $template) as $file) {
      $file = sprintf($file, $pathinfo["filename"]);
      if (file_exists($file)) {
        $ret = convert_to_url($pathinfo["dirname"]."/$file");
        break;
      }
    }

    if (!$ret) {
      $mask = sprintf($template, "*", "*", "*", "*", "*", "*");

      foreach (glob("{". strtolower($mask) . "," . strtoupper($mask) . "}", GLOB_BRACE) as $file) {
        $ret = convert_to_url($pathinfo["dirname"]."/$file");
        break;
      }
    }
  }

  return $ret ? $ret : $fallback_album_art;
}

$playlist = unserialize(file_get_contents("$music_dir/playlist"));
$track = $music_dir . $playlist[time() % sizeof($playlist)];
$track_url = convert_to_url($track);

if (!file_exists($track)) { die("error on $track"); }

$mp3 = new MP3_Id();
$mp3->read($track);
$artist = convert_to_string($mp3->getTag('artists'));
$title = convert_to_string($mp3->getTag('name'));
$artist = $artist ? $artist : "NA";
$title = $title ? $title : "NA";
$art = album_art($track);

?><!DOCTYPE html>
<html class="no-js">
  <head>
    <meta charset="utf-8">
    <title><?php echo "$artist / $title"; ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <script src="assets/js/modernizr.min.js"></script>
    <script src="assets/js/jquery.min.js"></script>
    <script type="text/javascript" src="assets/js/soundmanager2-nodebug-jsmin.js"></script>
    <script>
      soundManager.flashVersion = 9;
      soundManager.useFlashBlock = false;
      soundManager.url = 'assets/swf/';
      soundManager.preferFlash = false;
      soundManager.onready(function() {
        window.sound = soundManager.createSound({
          id: "sound",
          url: "<?php echo $track_url; ?>",
          autoLoad: true,
          autoPlay: true,
          onfailure: function() { window.location.reload(); },
          onfinish: function() { window.location.reload(); },
          onload: function() {
            if (this.readyState == 2) { window.location.reload(); }
            else {
              $(".loading").remove();
              $(".cover").html("<img src='<?php echo $art; ?>'>");
            }
          },
        });
        screen_update();
      });

      function screen_update() {
        title = "<?php echo "$artist / $title"; ?> ";
        position = 0;
        if (sound.duration) {
          position = Math.floor((sound.position / sound.duration) * 100);
        }

        $("title").text(title);
        $(".controls .position").text(position + "%");

        setTimeout(screen_update, 500);
      }

      function click_play_pause() {
        if (sound.paused) {
          sound.resume();
        }
        else {
          sound.pause();
        }

        $(".controls .play-pause").html(sound.paused ? "<i class=\"icon-play\"></i>" : "<i class=\"icon-pause\"></i>");
      }
    </script>

    <link href="assets/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/css/bootstrap-responsive.min.css" rel="stylesheet">
    <style type="text/css">
      body { margin:20px auto; }
      #sm2-container { position:absolute; left:0px; top:0px; width:0px; height:0px; overflow:hidden; }
      .controls { margin-bottom:20px; opacity: 0.2; }
      .controls:hover { opacity:1; }
      .controls .position { font-size:2.8em; line-height:1em; }
      .song .meta .cover img { width:100%; }
      .song .meta { margin-bottom:20px; font-size:1.5em; line-height:1em; letter-spacing:-1px; text-align:center; font-weight:bold; }
      .song .meta .title { margin-bottom:20px; }
    </style>
  </head>

  <body>
    <div class="container">
      <div class="controls row">
        <div class="span12">
          <button class="play-pause btn btn-large" type="submit" onclick="click_play_pause();"><i class="icon-pause"></i></button>
          <button class="next btn btn-large" type="submit" onclick="window.location.reload();"><i class="icon-fast-forward"></i></button>
          <span class="position pull-right"></span>
        </div>
      </div>
      <div class="song row">
        <div class="meta span2 offset5">
          <div class="artist"><?php echo $artist; ?></div>
          <div class="title"><?php echo $title; ?></div>
          <div class="loading"><img src="assets/img/indicator.gif"></div>
          <div class="cover"></div>
        </div>
      </div>
    </div>
  </body>
</html>