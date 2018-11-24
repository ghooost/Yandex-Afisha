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


$arr=file('shows_source.txt');

foreach($arr as $cnt=>$str){
  if(preg_match('/(\d+)-(\d+)-(\d+)[\D]+(\d+:\d+)[\W]+([\w\W]+)\s+(Nj[\w\W]+)$/uU',$str,$vars)){
    $year=$vars[1];
    $month=preg_replace('/^0+/','',$vars[2]);
    $day=preg_replace('/^0+/','',$vars[3]);
    $time=preg_replace('/^0+/','',$vars[4]);
    $yandexid=$vars[6];
    $show=trim($vars[5]);

    if(empty($shows[$show])){
      die("\r\nThere is no show with $show in the shows_work.json\r\n");
    };

    $calendar[$year][$month][$day][]=array(
      'show'=>$show,
      'date'=>$year.'-'.$month.'-'.$day,
      'time'=>$time,
      'yandexid'=>$yandexid
    );
  } else {
    echo "Line doens't match: ".$str."<br>\r\n";
  }
}


echo "<h2>Calendar compiled</h2><xmp>".print_r($calendar,true)."</xmp>\r\n";

renderCalendar($calendar, $shows);

foreach($shows as $k=>$v)
  renderCalendar($calendar, $shows,$k,$v['file']);
  //if($v['noticed'])

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
  $c_d=date('j');
  ksort($calendar);
  foreach($calendar as $year=>$ydata){
    if($year<$c_y) continue;

    ksort($ydata);

    foreach($ydata as $month=>$mdata){
      if($year==$c_y && $month<$c_m) continue;
      if($year==$c_y && $month==$c_m){
          $hasShows=false;
          foreach($mdata as $k=>$v)
            if($k>=$c_d){
              $hasShows=true;
              break;
            };
          if(!$hasShows) continue;
      }

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
    (function() {
      var d=new Date();
      var dataMon=d.getFullYear()+'-'+(d.getMonth()+1);
      var dataDay=d.getDate();
      var dataHour=d.getHours();
      var list=document.querySelectorAll('.cal_month[data-month="'+dataMon+'"] .cal_d_own');
      for(var cnt=0,m=list.length;cnt<m;cnt++){
        var day=parseInt(list[cnt].getAttribute('data-day'));
        if(day && day<dataDay){
          shoutDate(list[cnt],day);
        } else if(day && day==dataDay){
          var evnts=list[cnt].querySelectorAll('.cal_show');
          var nEvnts=0;
          for(var cnt1=0,m1=evnts.length;cnt1<m1;cnt1++){
            var hour=parseInt(evnts[cnt1].getAttribute('data-hour'));
            if(hour && hour<dataHour){
              evnts[cnt1].parentNode.removeChild(evnts[cnt1]);
            } else {
              nEvnts++;
            }
          }
          if(!nEvnts){
            shoutDate(list[cnt],day);
          }
        }
      }

      function shoutDate(obj,day){
        obj.className='cal_d_empty';
        obj.innerHTML='<div class="cal_date_date">'+day+'</div>';
      }

    })();


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

<ul class="cal_month cal_m_{$nmonth}" data-month="{$year}-{$month}">$out</ul>
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
        $timeParts=explode(':',$show['time']);
        $buttons.=<<<EOT
<div class="cal_show" data-hour="{$timeParts[0]}">
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
<li class="{$myclass}{$css}" data-day="$d"><div class="cal_date_date">$d</div>{$note}{$buttons}</li>
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
