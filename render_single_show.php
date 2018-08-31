<?php
$js=join('',file('data/shows_work.json'));
$shows=json_decode($js,TRUE);

$options=getArgv(
  $argv,
  ['-s'=>'show','-o'=>'output'],
  ['show'=>'undefined','output'=>'data/shows_work.json'],
  <<<EOT
Single show calendar renderer.
Options:
-s show name   : set name of show
-o output file : set output file name (default is data/show_name.json)
EOT
);

print_r($options);


function getArgv($arr,$keys,$ret){
  $gotMM=false;
  $curKey="";
  foreach($arr as $v)
    if($v=='--'){
      $gotMM=true;
    } else if($gotMM){
      if(preg_match('/^-/',$v)){
        if(empty($keys[$v])){
          die("Unrecognized key ".$v);
        } else {
          $curKey=$v;
          $ret[$keys[$curKey]]=true;
        };
      } else {
        if(!$curKey){
          die("Unrecognized param ".$v);
        } else {
          if($ret[$keys[$curKey]]===true){
              $ret[$keys[$curKey]]=$v;
          } else {
            $ret[$keys[$curKey]].=" ".$v;
          };
        }
      }
    }
  return $ret;
}
?>
