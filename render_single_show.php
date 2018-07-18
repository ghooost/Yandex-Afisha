<?php

$options=getArgv(
  $argv,
  ['-s'=>'show','-o'=>'output'],
  ['show'=>'','output'=>'data/shows_work.json']
);

print_r($options);


function getArgv($arr,$keys,$ret){
  $gotMM=false;
  $curKey="";
  foreach($arr as $v)
    if($v=='--'){
      $gotMM=true;
    } else if($gotMM){
      if(!$curKey){
        if(empty($keys[$v])){
          die("Unrecognized key ".$v);
        } else {
          $curKey=$v;
        }
      } else {
        $ret[$keys[$curKey]]=$v;
        $curKey="";
      };
    }
  return $ret;
}
?>
