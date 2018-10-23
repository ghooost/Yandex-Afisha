<html>
<head>
 <meta name="robots" content="noindex" />
<body>
<?php
$calendar=array();

$js=join('',file('data/shows_work.json'));
$shows=json_decode($js,TRUE);

$js=join('',file('data/special.json'));
$special=json_decode($js,TRUE);

$data=downloadJson("https://api.tickets.yandex.ru/v1/export/service/103");

if($data['status']=='success'){
  //echo "\r\nData from Yandex<Br>\r\n";

  //get events
  $eventsIndex=array();
  foreach($data['result']['events'] as $events){
    $eventsArr=downloadJson($events['url']);
    foreach($eventsArr as $event){

      $found=FALSE;
      foreach($shows as $showName=>$showData){
        if(strpos($event['name'],$showName)!==FALSE){
          $event['name']=$showName;
          $eventsIndex[$event['id']]=$event;
          $found=TRUE;
          break;
        }
      };
      if(!$found){
        print_r($shows);
	      echo ".",$showName,".\r\n";
        die("\r\nThere is no show with $event[name] in the shows_work.json\r\n");
      }
    }
  }

  foreach($data['result']['sessions'] as $sessions){
    $sessionsArr=downloadJson($sessions['url']);
    foreach($sessionsArr as $session){
      if($eventsIndex[$session['eventId']]){
        $show=$eventsIndex[$session['eventId']]['name'];
        $shows[$show]['noticed']=TRUE;
        $yandexid=$session['key'];
        if(!empty($special[$yandexid])) continue;
        
        if(preg_match('/^(\d+)-0*(\d+)-0*(\d+)T(\d+:\d+)/',$session['dateTime'],$vars)){
          $year=$vars[1];
          $month=$vars[2];
          $day=$vars[3];
          $time=$vars[4];
          $calendar[$year][$month][$day][]=array(
            "show"=>$show,
            "date"=>$year.'-'.$month.'-'.$day,
            "time"=>$time,
            "yandexid"=>$yandexid
          );
        } else {
          die("Unrecognized time format ".$session['dateTime']."\r\n");
        }
      } else {
        die("Seems there is no show with ID ".$session['eventId']."\r\n");
      };
    }
  }
} else {
  die('There is no success in the start link download!\r\n');
}

echo "<h2>Calendar compiled</h2><xmp>".print_r($calendar,true)."</xmp>\r\n";

//echo json_encode($calendar,JSON_UNESCAPED_UNICODE);
//exit();
//CALENDAR is READY HERE!
//Let's render it!
//ALL SHOWS first:
renderCalendar($calendar, $shows);
foreach($shows as $k=>$v)
  if($v['noticed'])
    renderCalendar($calendar, $shows,$k,$v['file']);

echo "DONE!\r\n";


function downloadJson($url){
  $ch = curl_init();
  curl_setopt($ch, CURLOPT_URL, $url);
  curl_setopt($ch, CURLOPT_HEADER, 0);
  curl_setopt($ch, CURLOPT_HTTPGET, TRUE);
  curl_setopt($ch,CURLOPT_HTTPHEADER,
    array(
      'Accept-Language:RU',
      'WWW-Authenticate:ApiKey 3f702dec-2645-4812-9354-9fa66a5a6b88',
      'X-Tickets-Client:ClientKey c3c37120-1c4a-4cd0-88b4-492d9a3a9a0a',
      'User-Agent: Mozilla/5.0 (Macintosh; Intel Mac OS X 10_13_6) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/11.1.2 Safari/605.1.15'
    )
  );
  curl_setopt($ch,CURLOPT_RETURNTRANSFER,TRUE);
  $output=curl_exec($ch);
  echo "<h3>Yandex data: [secret URL removed]</h3><hr>\r\n\r\n".$output."\r\n\r\n<br>";
  curl_close($ch);
  return json_decode($output,TRUE);
}

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


function renderCalendar($calendar, $shows, $showToBuild="",$fname="all.html"){

  if($showToBuild){
    $newYears=array();
    foreach($calendar as $year=>$ydata){
      $newMonths=array();
      foreach($ydata as $month=>$mdata){
        $newDays=array();
        foreach($mdata as $day=>$dshows){
          $newShows=array();
          foreach($dshows as $show){
            if($show['show']==$showToBuild) $newShows[]=$show;
          };
          if(count($newShows)) $newDays[$day]=$newShows;
        };
        if(count($newDays)) $newMonths[$month]=$newDays;
      };
      if(count($newMonths)) $newYears[$year]=$newMonths;
    };
    $calendar=$newYears;
  }


  $month_radios=array();
  $month_labels=array();
  $month_renders=array();

  $months=array('Январь','Февраль','Март','Апрель','Май','Июнь','Июль','Август','Сентябрь','Октябрь','Ноябрь','Декабрь');

  $cnt=1;
  $c_y=date('Y');
  $c_m=date('n');
  ksort($calendar);
  foreach($calendar as $year=>$ydata){
    if($year<$c_y) continue;

    ksort($ydata);

    foreach($ydata as $month=>$mdata){
      if($year==$c_y && $month<$c_m) continue;
      $ch=($cnt==1)?"checked":"";
      $month_radios[]=<<<EOT
<input type="radio" name="c_sel" id="cal_r_$cnt" class="cal_radio" $ch>
EOT;

      $month_labels[]=<<<EOT
<li class="cal_label cal_l_$cnt">
  <label for="cal_r_$cnt">
    <span class="cal_bar_month">{$months[$month-1]}</span>
    <span class="cal_bar_year">$year</span>
  </label>
</li>
EOT;

      $month_renders[]=renderMonth($year,$month,$mdata,$cnt,$months,$shows);

      $cnt++;
    }
  }

  $radios=join("",$month_radios);
  $labels=join("",$month_labels);
  $renders=join("",$month_renders);

  $styles=renderCustomStyles($shows);

  $body=<<<EOT
<form class="cal_form">
$radios
<ul class="cal_labels">$labels</ul>
$renders
</form>
<style>
$styles
</style>
<!-- Шаблон кнопки -->
<script id="yandex-button" type="text/html">
    <b class="cal_date_button">Купить билет</b>
</script>

<!-- Подключение дилера -->

<script>
    /* Настройка */
    var dealerName = 'YandexTicketsDealer';
    var dealer = window[dealerName] = window[dealerName] || [];

    dealer.push(['setDefaultClientKey', 'c3c37120-1c4a-4cd0-88b4-492d9a3a9a0a']);
    dealer.push(['setYandexMetrika', 19609699]);

    /* Загрузка */
    (function () {
        var rnd = '?' + new Date().getTime() * Math.random();
        var script = document.createElement('script');
        var target = document.getElementsByTagName('script')[0];
        script.async = true;
        script.src = 'https://yastatic.net/ticketier-dealer/last/dealer.js' + rnd;
        target.parentNode.insertBefore(script, target);
    })();
</script>

EOT;
//  $fname='all';
  if($showToBuild && !$fname) $fname=$showToBuild;
  $f=fopen('output/'.$fname,'w');
  if($f){
    fputs($f,$body);
    fclose($f);
  }
}


function renderMonth($year,$month,$mdata,$nmonth,$months,$shows){
  global $special;

  $t=mktime(12,0,0,$month,1,$year);
  $w=date('N',$t);

  $out="";
  for($cnt=1;$cnt<$w;$cnt++)
    $out.=renderOtherDate($t-24*3600*($w-$cnt));


  $ndays=date('t',$t);
  for($cnt=1;$cnt<=$ndays;$cnt++)
    $out.=renderOwnDate($cnt,$mdata[$cnt],$shows);


  $t=mktime(12,0,0,$month,$ndays,$year);
  $w=date('N',$t);

  for($cnt=$w+1;$cnt<8;$cnt++)
    $out.=renderOtherDate($t+24*3600*($cnt-$w));

  return <<<EOT
<div class="cal_m_title cal_l_$nmonth">
    <label for="cal_r_$nmonth">
      <span class="cal_t_month">{$months[$month-1]}</span>
      <span class="cal_t_year">$year</span>
    </label>
</div>

<ul class="cal_month cal_m_{$nmonth}">$out</ul>
EOT;
}

function renderOtherDate($t){
  $d=date('j',$t);
  return <<<EOT
<li class="cal_d_other"><div class="cal_date_date">$d</div></li>
EOT;

}

function cmp($a,$b){
  if($a['time'] == $b['time']){
    return 0;
  };
  return ($a['time'] < $b['time']) ? -1 : 1;
}

function renderOwnDate($d,$dateshows,$shows){
  global $special;

  //print_r($dateshows);

  //print_r($special);

  $buttons="";
  $bgclass=null;
  $myclass="cal_d_empty";

  // if($dateshows){
  //   print_r($dateshows);
  //   exit();
  // }

  $note="";
  if($dateshows){
    $myclass="cal_d_own";
    usort($dateshows,'cmp');
    foreach($dateshows as $show){
      if(!empty($shows[$show['show']]) && !$bgclass){
        $bgclass=$shows[$show['show']];
      };

      if($special && $special[$show['date']]){
        if($special[$show['date']]['note']){
          $note='<div class="cal_show"><span class="cal_show_name">'.$special[$show['date']]['note'].'</span></div>';
        }
      }

      if($show['yandexid']){
        $buttons.=<<<EOT
<div class="cal_show">
<span class="cal_show_time">$show[time]</span><span class="cal_show_name">$show[show]</span>
<y:ticket data-session-id="$show[yandexid]" data-template="yandex-button"></y:ticket>
</div>
EOT;
      };
    };
  };
  if($bgclass){
    if($bgclass['color']) $buttons.=<<<EOT
  <div class="cal_bg_color"></div>
EOT;

    if($bgclass['bgimg']) $buttons.=<<<EOT
  <div class="cal_bg_img"></div>
EOT;

    if($bgclass['url']) $buttons.=<<<EOT
  <a class="cal_bg_url" href="$bgclass[url]"></a>
EOT;
    $css=" ".$bgclass['css'];
  } else {
    $css="";
  };

  return <<<EOT
<li class="{$myclass}{$css}"><div class="cal_date_date">$d</div>{$note}{$buttons}</li>
EOT;

}

function renderCustomStyles($shows){
  $ret="";
  foreach($shows as $k=>$show){
    if($show['bgimg'])
      $ret.=<<<EOT

.cal_d_own.{$show['css']} .cal_bg_img {
  background-image:url({$show['bgimg']});
}
EOT;
    if($show['color'])
      $ret.=<<<EOT

.cal_d_own.{$show['css']} .cal_bg_color {
  background-color:{$show['color']};
}
EOT;
  };
  return $ret;
}

?>
</body></html>
