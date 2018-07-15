<?php
$js=join('',file('data/calendar_work.json'));
$calendar=json_decode($js,TRUE);

$js=join('',file('data/shows_work.json'));
$shows=json_decode($js,TRUE);

$month_radios=[];
$month_labels=[];
$month_renders=[];

$months=array('Январь','Февраль','Март','Апрель','Май','Июнь','Июль','Август','Сентябрь','Октябрь','Ноябрь','Декабрь');

$custom_css=array();

$cnt=1;
$c_y=date('Y');
$c_m=date('n');
foreach($calendar as $year=>$ydata){
  if($year<$c_y) continue;
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

    $month_renders[]=renderMonth($year,$month,$mdata,$cnt);

    $cnt++;
  }
}

$radios=join("",$month_radios);
$labels=join("",$month_labels);
$renders=join("",$month_renders);

$styles=renderCustomStyles($shows);

echo <<<EOT
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


function renderMonth($year,$month,$mdata,$nmonth){
  global $months;

  $t=mktime(12,0,0,$month,1,$year);
  $w=date('N',$t);

  $out="";
  for($cnt=1;$cnt<$w;$cnt++)
    $out.=renderOtherDate($t-24*3600*($w-$cnt));


  $ndays=date('t',$t);
  for($cnt=1;$cnt<=$ndays;$cnt++)
    $out.=renderOwnDate($cnt,$mdata[$cnt]);


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

function renderOwnDate($d,$dateshows){
  global $shows,$custom_css;
  $buttons="";
  $bgclass=null;
  $myclass="cal_d_empty";
  if($dateshows){
    $myclass="cal_d_own";
    usort($dateshows,'cmp');
    foreach($dateshows as $show){
      if(!empty($shows[$show['show']]) && !$bgclass){
        $bgclass=$shows[$show['show']];
      };

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
<li class="{$myclass}{$css}"><div class="cal_date_date">$d</div>$buttons</li>
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
