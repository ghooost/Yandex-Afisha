var showvtix=function(){
	var _inited=false;
	var _uniq=0;
	var _vtixShowsData={};
  var _inIndex={};
	var _inticketsShowNames={};
	var _MODE_CALENDAR=1;
	var _MODE_LIST=2;
	var _CALENDAR_WIDTH=870;
	var _st=0;
	function _init(o){
		_uniq++;
		o.setAttribute('data-showvtix',_uniq);
		var a=o.attributes;
		if(!a['data-mobile']) o.setAttribute('data-mobile','short');
		if(a['data-imgs']&&a['data-imgs'].value){
			var a1=a['data-imgs'].value.split('#v#');
			for(var cnt=0,m=a1.length;cnt<m;cnt++){
				var a2=a1[cnt].split('#f#');
				var showid='s'+a2[0];
				if(!_vtixShowsData[showid]) _vtixShowsData[showid]={};
				_vtixShowsData[showid]['image']=a2[1];
        var showid2='s'+a2[2];
				if(!_vtixShowsData[showid2]) _vtixShowsData[showid2]={};
				_vtixShowsData[showid2]['image']=a2[1];
			};
		};


		if(a['data-colors']&&a['data-colors'].value){
			var a1=a['data-colors'].value.split('#v#');
			for(var cnt=0,m=a1.length;cnt<m;cnt++){
				var a2=a1[cnt].split('#f#');
				var showid='s'+a2[0];
				if(!_vtixShowsData[showid]) _vtixShowsData[showid]={};
				_vtixShowsData[showid]['color']=a2[1];
			};
		}

    if(window.inTicketsIndex)
      _inIndex=inTicketsIndex;

		if(window.inTicketsIndexData)
			for(var f in window.inTicketsIndexData)
				_inIndex[f]=window.inTicketsIndexData[f];



    for(var o in _vtixShowsData){

      // try {
        var k1=o.replace(/\u0451/m,'е');
        if(k1!=o){
          _vtixShowsData[k1]=_vtixShowsData[o];
        };
      // } catch(e){}
    };

    //console.log(_vtixShowsData);

		_onResize(o);
		url='gate.php?moduid='+a['data-moduid'].value+'&mode=newtix&uid='+a['data-uid'].value+"&showvtix="+_uniq+"&sendingcatch="+Math.random();
		if(a['data-intix'] && a['data-intix'].value)
			url+='&intix='+a['data-intix'].value;

		var s=document.createElement('script');
		s.setAttribute("type","text/javascript");
		s.setAttribute('src',url);
		document.getElementsByTagName("head")[0].appendChild(s);
    //console.log(url);


		if(!_inited){
			snarklib.loadcss(snarkJSHome+'defclscss/blocks/loadform.css',true);
			window.addEventListener('resize',_onResize,false);
			_inited=true;
		};
		return true;
	}

	function _onResize(){
		var list=document.querySelectorAll('[data-module="showvtix"]');
		for(var cnt=0,m=list.length;cnt<m;cnt++) _doResize(list[cnt]);
	}

	function _doResize(o){
		var mode='';
		if(window.innerWidth<_CALENDAR_WIDTH){
			mode=_MODE_LIST;
		} else {
			mode=_MODE_CALENDAR;
		};
		if(o.vtixmode!=mode){
			o.vtixmode=mode;
			if(o.vtixmonths) _reDraw(o);
		}
	}

	function _onCalendar(data,showvtix){

//console.log(data);

    var blockedEvents={};
		console.log(window.vtixBlockedEvents);
    if(window.vtixBlockedEvents){
      for(var cnt=0,m=window.vtixBlockedEvents.length;cnt<m;cnt++){
        blockedEvents[window.vtixBlockedEvents[cnt]]=true;
      };
    };
    for(var cnt=0,m=data.length;cnt<m;cnt++){
      if(blockedEvents[data[cnt].id] && data[cnt].ticket_set.state!='SOLD_OUT'){
        data[cnt].ticket_set={state:'BLOCKED'};
      };
    };

		var months={};
		var events=[];
		for(var cnt=0,m=data.length;cnt<m;cnt++){
			if(data[cnt].ticket_set&& (data[cnt].ticket_set.state=='SALE' || data[cnt].ticket_set.state=='SOLD_OUT')){
				var t=new Date(data[cnt].begin_time+'Z');
				data[cnt].t=t;
				var yid=t.getUTCMonth()+'_'+t.getUTCFullYear();
				var loch=t.getUTCHours();
				var locm=t.getUTCMinutes();
				if(!months[yid]) months[yid]={m:t.getUTCMonth(),y:t.getUTCFullYear(),items:{}};
				var did='e'+t.getUTCDate();
				if(!months[yid].items[did]) months[yid].items[did]=[];

        months[yid].items[did].push(data[cnt]);
			}
		}
    if(window.vtixSpecialDates)
    for(var cnt=0,m=vtixSpecialDates.length;cnt<m;cnt++){
				var t=new Date(vtixSpecialDates[cnt].begin_time+'Z');
        vtixSpecialDates[cnt].show={
          id:'special',
          name:vtixSpecialDates[cnt].name
        };
        vtixSpecialDates[cnt].t=t;
				var yid=t.getUTCMonth()+'_'+t.getUTCFullYear();
				var loch=t.getUTCHours();
				var locm=t.getUTCMinutes();
				if(!months[yid]) months[yid]={m:t.getUTCMonth(),y:t.getUTCFullYear(),items:{}};
				var did='e'+t.getUTCDate();
				if(!months[yid].items[did]) months[yid].items[did]=[];

        months[yid].items[did].push(vtixSpecialDates[cnt]);
		}
		var o=document.querySelector('[data-showvtix="'+showvtix+'"]');
		if(!o) return;
		o.vtixmonths=months;
		_reDraw(o);
	}

	function _reDraw(o){
		if(o.vtixmode==_MODE_CALENDAR){
			_prepareShow(o);
			for(var yid in o.vtixmonths){
				_showMonth(o,yid);
				break;
			};
		} else {
			_showList(o);
		}

		var mes=document.createElement('div');
		mes.className="sales_counter";
		var number=o.attributes['data-num'].value;
		var i=number%10;
		var str=(i>1 && i<5)?'человека':'человек';
		mes.innerHTML='<a><span class="s1">Сейчас билеты покупают</span><span class="s2">'+number+'</span><span class="s3">'+str+'</span></a>';
		o.appendChild(mes);
	}

	function _buildTime(d){
		var h=d.getUTCHours();
		var m=d.getUTCMinutes();
		if(m.toString().length<2) m='0'+m;
		return h+":"+m;
	}

  function _isTheSameShow(arr){
    if(arr.length<2) return true;
    var name=arr[0].show.name;
    for(var cnt=0,m=arr.length;cnt<m;cnt++)
      if(arr[cnt].show.name!=name){
        return false;
      };
    return true;
  }

	function _showWeek(mon,date,shows,img,defName){

		var tr=document.createElement('div');
		tr.className='tr';
		var startSpace=date.getUTCDay()-1;
		if(startSpace==-1) startSpace=6;
		var cmonth=date.getUTCMonth();
		date.setUTCDate(date.getUTCDate()-startSpace);
		var mcnt=0;
		do{
			var td=document.createElement('div');
			tr.appendChild(td);
			var buf='';
			var buf='<span class="date">'+date.getUTCDate()+'</span>';
      var color='';
      var showurl='';
			if(date.getUTCMonth()==cmonth){
				var logDate=date.getUTCDate()+"."+cmonth+"."+date.getUTCFullYear();
				//console.log(logDate);
				td.className='td day';
				var buf1='';
				var d_id='e'+date.getUTCDate();

				if(shows.items[d_id]){
          var showid='s'+shows.items[d_id][0].show.id;
					if(shows.items[d_id][0].show.id==0) showid='s'+shows.items[d_id][0].show.name.toLowerCase().replace(/[\s\-\"\']/g,'_');



          if(shows.items[d_id][0].event_id && _inIndex[shows.items[d_id][0].event_id] && showid!='sspecial'){

            showurl=_inIndex[shows.items[d_id][0].event_id]['u'];
            img=_inIndex[shows.items[d_id][0].event_id]['i'];
						color=_inIndex[shows.items[d_id][0].event_id]['c'];

          } else if(showid=='sspecial'){
            img='';
            color='';
            showurl='';
            buf+='<div style="position:absolute;top:1px;left:1px;bottom:1px;right:1px;background-color:'+shows.items[d_id][0].color+'"></div>';
          } else {
            if(_vtixShowsData[showid]&&_vtixShowsData[showid]['image']) img=_vtixShowsData[showid]['image'];
            if(_vtixShowsData[showid]&&_vtixShowsData[showid]['color']) color=_vtixShowsData[showid]['color'];
            showurl='';
          }

					if(!img && _vtixShowsData[showid]&&_vtixShowsData[showid]['image']) img=_vtixShowsData[showid]['image'];
					if(!color && _vtixShowsData[showid]&&_vtixShowsData[showid]['color']) color=_vtixShowsData[showid]['color'];


          //console.log(showid,_vtixShowsData[showid]);


					if(img){
						if(window.vtixSpecialLinks && window.vtixSpecialLinks[showid]){
							buf+='<a class="showbg" style="background-image:url('+img+');" href="'+vtixSpecialLinks[showid]+'"></a>';
            } else if(showurl){
              buf+='<a class="showbg" style="background-image:url('+img+');" href="/'+showurl+'"></a>';
						} else {
							buf+='<div class="showbg" style="background-image:url('+img+')"></div>';
						};
					}
					buf+='<div class="showcolor" style="background-color:'+color+'"></div>';
					for(var cnt=0,m=shows.items[d_id].length;cnt<m;cnt++){
						var idata=_inIndex[shows.items[d_id][cnt].event_id];

if(!idata) console.log("Check", shows.items[d_id][cnt]);

						var time=_buildTime(shows.items[d_id][cnt].t);
						var name=shows.items[d_id][cnt].show.name?shows.items[d_id][cnt].show.name:defName;
						if(idata && idata.n) name=idata.n;

						var uname=name;
						var utime=time;

						if(shows.items[d_id][cnt].event_id && _inIndex[shows.items[d_id][cnt].event_id] && _inIndex[shows.items[d_id][cnt].event_id]['u']){
							uname='<a href="/'+_inIndex[shows.items[d_id][cnt].event_id]['u']+'">'+name+'</a>';
							utime='<a href="/'+_inIndex[shows.items[d_id][cnt].event_id]['u']+'">'+time+'</a>';
						}

						//console.log(shows.items[d_id][cnt]);
						if(shows.items[d_id][cnt].show.id==0 || shows.items[d_id][cnt].show.id=='special'){
							//buf1+='<div class="show"><span class="showtime">'+time+'</span><span class="showname">'+name+'</span><a xonclick="showvtix.showFrame('+shows.items[d_id][cnt].id+');return(false)"  href="https://iframeab-pre0010.intickets.ru/node/'+shows.items[d_id][cnt].id+'" target="_buy" class="buy abiframelnk">Купить билеты</a></div>';
							//buf1+='<div class="show"><span class="showtime">'+time+'</span><span class="showname">'+name+'</span><a href="https://iframeab-pre0010.intickets.ru/node/'+shows.items[d_id][cnt].id+'" class="abiframelnk buy">Купить билеты</a></div>';
							if(window.vtixSpecialLinks && window.vtixSpecialLinks[showid]){
								buf1+='<div class="show"><a href="'+vtixSpecialLinks[showid]+'" style="text-decoration:none"><span class="showtime">'+time+'</span><span class="showname">'+name+'</span></a><a href="https://iframeab-pre0010.intickets.ru/node/'+shows.items[d_id][cnt].id+'" class="abiframelnk buy" data-id="'+shows.items[d_id][cnt].id+'" data-name="'+name+'" data-date="'+logDate+'" onclick="calendarLayerLogCaller(this)">Купить билеты</a></div>';
							} else {
                if(shows.items[d_id][cnt].ticket_set.state=='SOLD_OUT'){
                  buf1+='<div class="show"><span class="showtime">'+utime+'</span><span class="showname">'+uname+'</span><a class="abiframelnk buy block" data-id="'+shows.items[d_id][cnt].id+'" data-name="'+name+'" data-date="'+logDate+'">Билеты проданы</a></div>';
                } else if(shows.items[d_id][cnt].ticket_set.state=='SPECIAL'){
                    buf1+='<div class="show"><span class="showtime"></span><span class="showname">'+shows.items[d_id][cnt].name+'</span><a class="abiframelnk buy block" data-date="'+logDate+'">Билеты проданы</a></div>';
								} else if(idata && idata.turl){
                  buf1+='<div class="show"><span class="showtime">'+utime+'</span><span class="showname">'+uname+'</span><a href="'+idata.turl+'" class="abiframelnk buy" data-id="'+shows.items[d_id][cnt].id+'" data-name="'+name+'" data-date="'+logDate+'" onclick="calendarLayerLogCaller(this)">Купить билеты</a></div>';
                } else {
                  buf1+='<div class="show"><span class="showtime">'+utime+'</span><span class="showname">'+uname+'</span><a href="https://iframeab-pre0010.intickets.ru/node/'+shows.items[d_id][cnt].id+'" class="abiframelnk buy" data-id="'+shows.items[d_id][cnt].id+'" data-name="'+name+'" data-date="'+logDate+'" onclick="calendarLayerLogCaller(this)">Купить билеты</a></div>';
                };
							};
						} else {
							buf1+='<div class="show"><span class="showtime">'+utime+'</span><span class="showname">'+uname+'</span><a href="/tickets#tickets/'+shows.items[d_id][cnt].id+'" class="buy">Купить билеты</a></div>';
						};

					}
				};
				if(buf1) buf+='<div class="shows">'+buf1+'</div>';
			} else {
				td.className='td space';
			}
			td.innerHTML=buf;
			date.setUTCDate(date.getUTCDate()+1);mcnt++;
		} while(mcnt<7);
		mon.appendChild(tr);
    var list=mon.querySelectorAll('a.buy.block');
    for(var cnt=0,m=list.length;cnt<m;cnt++)
      list[cnt].addEventListener('click',_blockEvent,true);
		return date;
	}

  function _blockEvent(e){
    e.cancelBubble = true;
    e.preventDefault();
    return false;
  }

	function _prepareShow(o){
		o.innerHTML='';
		var h2=document.createElement('div');
		h2.className='months';
		o.appendChild(h2);
		var mon=document.createElement('div');
		mon.className='month';
		o.appendChild(mon);
		for(var yid in o.vtixmonths){
			var data=o.vtixmonths[yid];
			var a=document.createElement('a');
			var s=document.createElement('span');
			s.className='mname';
			s.innerHTML=sysMonths[data.m];
			a.appendChild(s);
			var s=document.createElement('span');
			s.className='yname';
			s.innerHTML=data.y;
			a.appendChild(s);
			a.addEventListener('click',function(){_showMonth(o,this.attributes['data-yid'].value);},false);
			h2.appendChild(a);
			a.setAttribute('data-yid',yid);
		};
	}

	function _showMonth(o,id){
		var data=o.vtixmonths[id];
		var links=o.querySelectorAll('.months a');
		for(var cnt=0,m=links.length;cnt<m;cnt++)
			if(links[cnt].attributes['data-yid'].value==id){
				links[cnt].setAttribute('data-sel',1);
			} else {
				links[cnt].removeAttribute('data-sel');
			};
		var mon=o.querySelector('.month');
		mon.innerHTML='';
		var img=o.attributes['data-img']?o.attributes['data-img'].value:'';
		var ld=data.m+1;
		ld=(ld>=10)?ld:'0'+ld;
		var date=new Date(data.y+'-'+ld+'-01T00:00:00Z');
		var icnt=0;
		var defName=o.attributes['data-name']?o.attributes['data-name'].value:'';
		while(date.getUTCMonth()==data.m){
			date=_showWeek(mon,date,data,img,defName);
			icnt++;
			if(icnt>40) break;
		}
	}

	function _listMonth(o,id){
		var data=o.vtixmonths[id];
		var h=document.createElement('h2');
		o.appendChild(h);
		h.innerHTML=sysMonths[data.m];
		var defName=o.attributes['data-name']?o.attributes['data-name'].value:'';
		var buf='';
		for(var day in data.items){
			var items=data.items[day];
			for(var cnt=0,m=items.length;cnt<m;cnt++){
				var evt=items[cnt];
				var idata=_inIndex[evt.event_id];
if(!idata) console.log("Check", evt);

				//console.log(evt);
				var day=evt.t.getUTCDate();
				var logDate=evt.t.getUTCDate()+"."+evt.t.getUTCMonth()+"."+evt.t.getUTCFullYear();
				var time=_buildTime(evt.t);
				var name=evt.show.name?evt.show.name:defName;
				buf+='<div class="show">';


				if(window.vtixSpecialLinks && window.vtixSpecialLinks[evt.id]){
					buf+='	<a href="'+vtixSpecialLinks[evt.id]+'" class="showcol1">';
					buf+='		<span class="showdate">'+day+'</span>';
					buf+='		<span class="showtime">'+time+'</span>';
					buf+='	</a>';
					buf+='	<a href="'+vtixSpecialLinks[evt.id]+'" class="showcol2" style="text-decoration:none">';
					buf+='		<span class="showname">'+name+'</span>';
					buf+='	</a>';

				} else {
					buf+='	<span class="showcol1">';
					buf+='		<span class="showdate">'+day+'</span>';
					buf+='		<span class="showtime">'+time+'</span>';
					buf+='	</span>';
					buf+='	<span class="showcol2">';
					buf+='		<span class="showname">'+name+'</span>';
					buf+='	</span>';
				};
				buf+='	<span class="showcol3">';

				var iturl="";

				if(window.vtixSpecialLinks && window.vtixSpecialLinks[evt.id]){
					buf+='<a href="https://iframeab-pre0010.intickets.ru/node/'+evt.id+'" class="buy abiframelnk" data-name="'+name+'" data-date="'+logDate+'" data-id="'+evt.id+'" onclick="calendarLayerLogCaller(this)"><span class="longdisponly">Купить </span>билеты</a>';
				} else {
					if(evt.ticket_set.state=='SOLD_OUT'){
						buf+='<a class="buy" data-name="'+name+'" data-date="'+logDate+'" data-id="'+evt.id+'" ><span class="longdisponly">Билеты </span>проданы</a>';
					} else if(evt.ticket_set.state=='SPECIAL'){
						buf+='<a class="buy" data-name="'+name+'" data-date="'+logDate+'" data-id="'+evt.id+'" ><span class="longdisponly">Билеты </span>проданы</a>';
					} else if(idata && idata.turl){
						buf+='<a href="'+idata.turl+'" class="buy abiframelnk" data-name="'+name+'" data-date="'+logDate+'" data-id="'+evt.id+'" onclick="calendarLayerLogCaller(this)"><span class="longdisponly">Купить </span>билеты</a>';
					} else {
						buf+='<a href="https://iframeab-pre0010.intickets.ru/node/'+evt.id+'" class="buy abiframelnk" data-name="'+name+'" data-date="'+logDate+'" data-id="'+evt.id+'" onclick="calendarLayerLogCaller(this)"><span class="longdisponly">Купить </span>билеты</a>';
					};
				};

        //buf+='<a href="https://iframeab-pre0010.intickets.ru/node/'+evt.id+'" class="buy abiframelnk" data-name="'+name+'" data-date="'+logDate+'" data-id="'+evt.id+'" onclick="calendarLayerLogCaller(this)"><span class="longdisponly">Купить </span>билеты</a>';

				buf+='	</span>';
				buf+='</div>';
			}
		}
		var d0=document.createElement('div');
		d0.className='showprelist';
		o.appendChild(d0);
		var d=document.createElement('div');
		d.className='showlist';
		d0.appendChild(d);
		d.innerHTML=buf;
	}

	function _showList(o){
		o.innerHTML='';
		var nMon=0;
		for(var yid in o.vtixmonths) nMon++;
		for(var yid in o.vtixmonths){
			_listMonth(o,yid);
			if(o.attributes['data-mobile']&&o.attributes['data-mobile'].value=='short') break;
		};
		if(o.attributes['data-mobile']&&o.attributes['data-mobile'].value=='short'&&nMon>1){
			o.innerHTML+='<a class="showvtixmore" onclick="showvtix.reShow(this);return(false)">Показать все даты</a>';
		}
	}

	function _reShow(obj){
		var o=snarklib.seekParent('showvtix',obj);
		if(o){
			o.removeAttribute('data-mobile');
			_showList(o);
		};
	}


	function _showFrame(id){

			_createScreen();

			var scr=document.querySelector('.loadformcontainer');
			var i=document.createElement('iframe');
			i.style.width="100%";
			i.style.height="100%";
			i.src="https://iframeab-pre0010.intickets.ru/node/"+id;
			scr.appendChild(i);
			scr.setAttribute("data-golive",1);
			scr.style.height="100vh";
			//document.body.style.overflow='hidden';
			_st=document.body.scrollTop;
			document.querySelector('.heighter').style.display="none";
			scr.style.overflow='hidden';
	}

    function _createScreen(){
	var d=document.createElement('div');
	d.className='loadformscreen deffont';
	//d.setAttribute('data-color',1);
	d.setAttribute('data-can-scroll',1);
	d.setAttribute('data-golive',1);

	document.body.appendChild(d);

	var d0=document.createElement('div');
	d0.className='loadformpretable';
	d.appendChild(d0);

	var d1=document.createElement('div');
	d1.className='loadformtable';
	d0.appendChild(d1);

	var d2=document.createElement('div');
	d2.className='tr';
	d1.appendChild(d2);

	var d3=document.createElement('div');
	d3.className='td';
	d2.appendChild(d3);

	var d4=document.createElement('div');
	d4.className='loadformcontainer';
	d3.appendChild(d4);

	var a=document.createElement('A');
	a.className='close';
	a.addEventListener('click',_hideShow,false);

	a.style.top="0px";
	a.style.right="auto";
	a.style.left="0px";
	a.style.height="43px";

	d.appendChild(a);

	var i=document.createElement('i');
	i.className='icon-close-loc';
	a.appendChild(i);
	i.style.color="#ffffff";
	i.style.lineHeight="43px";
    }

    function _hideShow(){
	document.querySelector('.heighter').style.display="block";
	document.body.scrollTo(0,_st);

	var scr=document.querySelector('.loadformscreen');
	scr.removeAttribute("data-golive");
	setTimeout(_hideShow2,500);
	return false;
    }

    function _hideShow2(){
	var scr=document.querySelector('.loadformscreen');
	scr.parentNode.removeChild(scr);
	//document.body.style.overflow='auto';

//	if(window.longread) longread.unblock();
    }


	return {
		init:_init,
		onCalendar:_onCalendar,
		reShow:_reShow,
		showFrame:_showFrame
	}
}();

if(snarklib)snarklib.onModuleLoaded('showvtix');
