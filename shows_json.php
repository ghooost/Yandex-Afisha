<?php
$js=join('',file('data/shows_work.json'));
$shows=json_decode($js,TRUE);

$js=join('',file('data/calendar_work.json'));
$data=json_decode($js,TRUE);

foreach($data as $year=>$ydata)
  foreach($ydata as $month=>$mdata)
    foreach($mdata as $day=>$ddata)
      foreach($ddata as $item)
        if(empty($shows[$item['show']])){
          $shows[$item['show']]=array("bgimg"=>"","color"=>"","url"=>"","css"=>"");
        }


echo json_encode($shows,JSON_UNESCAPED_UNICODE);

?>
