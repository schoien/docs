<?php

$settings_json = file_get_contents("settings.json");
$settings = json_decode($settings_json, true);

$mainDir = $settings["list_dir"];
$grepDir = $settings["dependency_search_dir"];
$desc_pre = $settings["description_prefix"];
$f_settings = $settings["file_types"];

chdir('../');

require_once 'vendor/autoload.php';
$loader = new \Twig\Loader\FilesystemLoader('docs/');
$twig = new \Twig\Environment($loader, array('debug' => true));

if (isset($_GET['getinfo'])) {
  $srcFile = $_GET['getinfo'];
  $fileInfo = getFileinfo($srcFile);
  
  $content['deskr'] = $fileInfo[0];
  $content['usedin'] = $fileInfo[1];
  $content['dependencies'] = $fileInfo[2];
  
  $template = $twig->load('macros.twig');
  echo $template->renderBlock('fildetaljer', $content);

} else {
  $fileList = dirToArray($mainDir);

  $template = 'docs.twig';
  $content['workDir'] = getcwd();
  $content['mainDir'] = $mainDir;
  $content['filelist'] = $fileList;

  echo $twig->render($template, $content);
}

function getFileinfo($srcFile){
  global $mainDir, $grepDir, $f_settings, $desc_pre;

  $fileType = end(explode('.', $srcFile));
  $filename = end(explode('/', $srcFile));
  $settings = "";
  if ( array_key_exists( $fileType , $f_settings )){
    $settings = $f_settings[$fileType];
  }
  
  if ( $settings ){
    $cs = escapeshellcmd ($settings['comment_start']);
    $ce = escapeshellcmd ($settings['comment_end']);
    //$ie = escapeshellcmd ($settings['includes_end']);
    $regexDesk = "/".$cs.$desc_pre."(.*?)".$ce."/su";

    $content = file_get_contents($srcFile);
    preg_match( $regexDesk, $content, $descr_serch);
    $descr = $descr_serch[1];
    $dependency = array();
    foreach( $settings['includes'] as $is ){
      $regexDep = "/".$is."(.*?)$/m";
      preg_match_all( $regexDep, $content, $dep_serch);
      if ( is_array($dep_serch[0]) ){
        $dependency = array_merge($dependency, $dep_serch[0]);
      }else{
        array_push($dependency, $dep_serch[0]);
      }
    }
  }else{
    $descr = 'File type not recognized. Add filetype too docs/settings.json to read content';
  }
  if ( $grepDir == "" || $grepDir == "./" ){
    exec("grep -r -l " . escapeshellarg($filename) . " ".getcwd() . "/*", $fileList);
  }else{
    exec("grep -r -l " . escapeshellarg($filename) . " ".getcwd() . DIRECTORY_SEPARATOR . $grepDir."/*", $fileList);
  }
  $fileList = str_replace(getcwd(), '', $fileList);
  $fileInfo = array($descr, $fileList, $dependency);
  return $fileInfo;
}

function dirToArray($dir) {
  $result = array();
  $cdir = scandir($dir);
  foreach ($cdir as $key => $value) {
      if (!in_array($value,array(".","..")))  {
          if (is_dir($dir . DIRECTORY_SEPARATOR . $value)){
            $result[$value] = dirToArray($dir . DIRECTORY_SEPARATOR . $value);
          } else {
            $result[$value] = $dir . DIRECTORY_SEPARATOR . $value;
          }
      }
  }
  return $result;
}
?>