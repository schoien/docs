<?php

$mainDir = 'design';
chdir('../');

require_once '../vendor/autoload.php';
$loader = new \Twig\Loader\FilesystemLoader('docs/');
$twig = new \Twig\Environment($loader, array('debug' => true));



if (isset($_GET['getinfo'])) {
  $srcFile = $_GET['getinfo'];
  $fileInfo = getFileinfo($srcFile);
  
  $content['deskr'] = $fileInfo[0];
  $content['usedin'] = $fileInfo[1];
  
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
  global $mainDir;
  $fileType = end(explode('.', $srcFile));
  $filename = end(explode('/', $srcFile));
  $cf = false;  

  if ($fileType == 'twig' || $fileType == 'comp'){
    $regexDesk = "/{#DESK(.*?)#}/su";
    $cf = true;
  }elseif ($fileType == 'php' || $fileType == 'less' || $fileType == 'css' || $fileType == 'js' ){
    $regexDesk = "/\/\*DESK(.*?)\*\//su";
    $cf = true;
  }else{
    $descr = 'Denne filtypen blir ikke sjekket for beskrivelse'; 
  }
  if($cf){
    $content = file_get_contents($srcFile);
    preg_match( $regexDesk, $content, $result);
    $descr = $result[1];
  }
  exec("grep -r -l " . escapeshellarg($filename) . " ".getcwd()."/*", $fileList);
  $fileList = str_replace(getcwd(), '', $fileList);
  $fileInfo = array($descr, $fileList);
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