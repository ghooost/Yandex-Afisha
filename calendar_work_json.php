<?php
$js=join('',file('data/calendar_work.json'));
$data=json_decode($js,TRUE);

$y=date('Y');
$m=date('n');
$d=date('j');

$shows=array();
foreach($data as $year=>$ydata){
    if($year<$y) continue;
    foreach($ydata as $month=>$mdata){
      if($year==$y && $month<$m) continue;
      foreach($mdata as $day=>$ddata){

//        echo $year,'-',$y,' ',$month,'-',$m,' ',$day,'-',$d,"\r\n";

        if($year==$y && $month==$m && $day<$d) continue;
        foreach($ddata as $item)
          if($item['yandexid'])
            $shows[$item['yandexid']]=$item;
      }
    }
}


$calendar=array();

function readCat($dir,$parentDir=''){
  global $calendar,$shows;
  if (is_dir($dir)) {
      if ($dh = opendir($dir)) {
          while (($file = readdir($dh)) !== false) {
            if($file!='.' && $file!='..') readCat($dir.'/'.$file,$dir);
          };
          closedir($dh);
      }
  } else {
    if(preg_match('/(\d+)-[0]*(\d+)-[0]*(\d+)\.txt/',$dir,$vars)){
      $body=join("",file($dir));
      $time='';

      if(preg_match('/data-session-id\=\"(\w+)\"/',$body,$v)){
        $yandexid=$v[1];
        if(!empty($shows[$yandexid])){
          $time=$shows[$yandexid]['time'];
        }
      } else {
        $yandexid='';
      }


      $calendar[$vars[1]][$vars[2]][$vars[3]][]=array(
        'show'=>preg_replace('/shows\//','',$parentDir),
        'date'=>$vars[1].'-'.$vars[2].'-'.$vars[3],
        'time'=>$time,
        'yandexid'=>$yandexid
      );
    } else
    if(preg_match('/(\d+)-[0]*(\d+)-[0]*(\d+)\,\s(\d+)-(\d+)\.txt/',$dir,$vars)){
      $body=join("",file($dir));
      $time=$vars[4].':'.$vars[5];

      if(preg_match('/data-session-id\=\"(\w+)\"/',$body,$v)){
        $yandexid=$v[1];
        if(!empty($shows[$yandexid]) && !$time){
          $time=$shows[$yandexid]['time'];
        }
      } else {
        $yandexid='';
      }


      $calendar[$vars[1]][$vars[2]][$vars[3]][]=array(
        'show'=>preg_replace('/shows\//','',$parentDir),
        'date'=>$vars[1].'-'.$vars[2].'-'.$vars[3],
        'time'=>$time,
        'yandexid'=>$yandexid
      );
    }
  }
}

readCat('shows');

echo json_encode($calendar,JSON_UNESCAPED_UNICODE);
?>
