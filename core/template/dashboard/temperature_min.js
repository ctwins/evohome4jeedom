function fHnwTemp(pMsgAdjustConsigneUnavailable,pIsAdjustV1,pLblAdjTHTitle1){this.settingTempOpened=false;this.adjustPrepared=[];this.msgAdjustConsigneUnavailable=pMsgAdjustConsigneUnavailable;this.isAdjustV1=pIsAdjustV1;this.lblAdjTHTitle1=pLblAdjTHTitle1;this.canReset=[];this.widgetTitle=[];this.adjAvailable=[];this.msgEnforceConsigne=[];this.spScheduled=[];this.spScheduledUntil=[];this.spCurrent=[];this.adjRunning=[];this.adjustLow=[];this.adjustHigh=[];this.adjustStep=[];this.cmdSetConsigneId=[];this.initialTitle=[];this.setThModes=[];}
function initVarsTH(_locId,_id,_zoneId){$('.showCurrentScheduleC'+_zoneId).on('click',function(){_showScheduleTH(_locId,_id,_zoneId,'C');});$('.showCurrentScheduleS'+_zoneId).on('click',function(){if(getScheduleListId(_locId)>0)_showScheduleTH(_locId,_id,_zoneId,'S');});evoRegisterHover(_id,'TH');setTimeout(function(){setColor('.showSavedScheduleCaption'+_id,getScheduleListId(_locId)==0?'gray':'white');},250);}
function getScheduleListId(locId){return typeof hnwConsole=='undefined'||typeof hnwConsole.genConsoleId=='undefined'||hnwConsole.genConsoleId[locId]=='undefined'||$('#scheduleList'+hnwConsole.genConsoleId[locId]).length==0?0:$('#scheduleList'+hnwConsole.genConsoleId[locId]).value();}
function _showScheduleTH(locId,id,zoneId,type){if(getBgColor('.showCurrentSchedule'+type+zoneId)!="rgb(16, 208, 16)"){showScheduleTH(locId,id,zoneId,type);}else{$('#md_modal').dialog('close');}}
function showScheduleTH(locId,id,zoneId,type,mode){hnwCommon.generalLastMode=mode==null?hnwCommon.generalLastMode:mode;$('#md_modal').dialog('close');$('#md_modal').dialog({title:"",height:'auto',width:(hnwCommon.generalLastMode==hnwCommon.showHorizontal?Math.min(1000,jQuery(window).width()-16):700),position:{my:"center top",at:"center top+50",of:window}});var fileId=type=='C'?0:$('#scheduleList'+hnwConsole.genConsoleId[locId]+' option:selected').value();$('#md_modal').load('index.php?v=d&plugin='+hnwCommon.pluginName+'&modal=schedule'+hnwCommon.generalLastMode+'&id='+id+'&'+hnwCommon.argZoneId+'='+zoneId+'&'+hnwCommon.argFileId+'='+fileId+'&edit=0&mode='+hnwCommon.generalLastMode+'&scheduleSource='+type).dialog('open');setBgColor('.showCurrentSchedule'+type+zoneId,"rgb(16, 208, 16)");}
function initVarsAC(_taskIsRunning,pAdjAvailable,pMsgAdjustConsigneUnavailable,pMsgEnforceConsigne,zoneId,pCurrentConsigne,pCurrentConsigneUntil,pConsigne,pAdjustLow,pAdjustHigh,pAdjustStep,pCmdSetConsigneId,pCanReset,pIsAdjustV1,pWidgetTitle,pLblAdjTHTitle1,pSetThModes){if(typeof hnwTemp=='undefined'){window.hnwTemp=new fHnwTemp(pMsgAdjustConsigneUnavailable,pIsAdjustV1,pLblAdjTHTitle1);}
if(pCmdSetConsigneId.substr(0,1)=="#"){if(pIsAdjustV1){$('.zidAdjustV1Reset'+zoneId).hide();$('.zidAdjustV1Plus'+zoneId).hide();$('.zidAdjustV1Minus'+zoneId).hide();$('.zidAdjustV1Lowest'+zoneId).hide();}else{$('.zidAdjustV2Reset'+zoneId).hide();$('.zidAdjustV2'+zoneId).hide();}}else{if(pIsAdjustV1){$('.zidAdjustV1Reset'+zoneId).on('click',function(){resetConsigneVx(zoneId);});$('.zidAdjustV1Minus'+zoneId).on('click',function(){adjustV1(zoneId,-1);});$('.zidAdjustV1Plus'+zoneId).on('click',function(){adjustV1(zoneId,1);});$('.zidAdjustV1Lowest'+zoneId).on('click',function(){adjustV1Lowest(zoneId);});}else{$('.zidAdjustV2Reset'+zoneId).on('click',function(){resetConsigneVx(zoneId);});$('.zidAdjustV2'+zoneId).on('click',function(){adjustPanelV2(zoneId);});}
hnwTemp.widgetTitle[zoneId]=pWidgetTitle;hnwTemp.adjAvailable[zoneId]=pAdjAvailable=='true';hnwTemp.msgEnforceConsigne[zoneId]=pMsgEnforceConsigne;hnwTemp.spScheduled[zoneId]=parseFloat(pCurrentConsigne);hnwTemp.spScheduledUntil[zoneId]=pCurrentConsigneUntil;hnwTemp.spCurrent[zoneId]=parseFloat(pConsigne);hnwTemp.adjustLow[zoneId]=parseFloat(pAdjustLow);hnwTemp.adjustHigh[zoneId]=parseFloat(pAdjustHigh);hnwTemp.adjustStep[zoneId]=parseFloat(pAdjustStep);hnwTemp.cmdSetConsigneId[zoneId]=parseInt(pCmdSetConsigneId);hnwTemp.initialTitle[zoneId]=$('.zidSpDisplayImgVx'+zoneId).attr('title');refreshAdjButtonsVx(zoneId,hnwCommon.apiAvailable&&hnwTemp.adjAvailable[zoneId],pCanReset,pConsigne);hnwTemp.adjRunning[zoneId]=false;}
hnwTemp.setThModes[zoneId]=pSetThModes;if(_taskIsRunning)showSettingTempVx(zoneId,0);}
function refreshAdjButtonsVx(zoneId,pCanAdjust,pCanReset,pConsigne){if(!pCanAdjust)hnwTemp.adjRunning[zoneId]=true;hnwTemp.canReset[zoneId]=true;var resetIconColor=hnwCommon.apiAvailable&&hnwTemp.adjAvailable[zoneId]&&hnwTemp.spScheduled[zoneId]!=0?'white':'gray';var colorLow=!pCanAdjust||pConsigne==hnwTemp.adjustLow[zoneId]?'gray':'white';var colorHigh=!pCanAdjust||pConsigne==hnwTemp.adjustHigh[zoneId]?'gray':'white';if(hnwTemp.isAdjustV1){setColor('.zidAdjustV1ResetIcon'+zoneId,resetIconColor);setColor('.zidAdjustV1LowestIcon'+zoneId,colorLow);setColor('.zidAdjustV1MinusIcon'+zoneId,colorLow);setColor('.zidAdjustV1PlusIcon'+zoneId,colorHigh);}else{setColor('.zidAdjustV2ResetIcon'+zoneId,resetIconColor);setColor('.zidAdjustV2Icon'+zoneId,hnwCommon.apiAvailable&&hnwTemp.adjAvailable[zoneId]?'white':'gray');}}
function checkAdjAvailabilityVx(zoneId){if(!checkApiAvailable())return false;if(!hnwTemp.adjAvailable[zoneId]){myAlert(hnwTemp.msgAdjustConsigneUnavailable);return false;}
return!hnwTemp.adjRunning[zoneId];}
function adjustV1(zoneId,step){if(!checkAdjAvailabilityVx(zoneId))return;var sp=parseFloat($('.zidConsigneVx'+zoneId)[0].innerHTML.replace('°',''));var nStep=step==-1&&sp>hnwTemp.adjustLow[zoneId]?-hnwTemp.adjustStep[zoneId]:step==1&&sp<hnwTemp.adjustHigh[zoneId]?hnwTemp.adjustStep[zoneId]:0;if(nStep!=0){prepareAdjustV1(zoneId,Math.round(100*(sp+nStep),2)/100);}}
function adjustV1Lowest(zoneId){if(!checkAdjAvailabilityVx(zoneId))return;prepareAdjustV1(zoneId,hnwTemp.adjustLow[zoneId]);}
function adjustPanelV2(zoneId){if(!checkAdjAvailabilityVx(zoneId))return;settingTempVx(zoneId,-1,-1);}
function resetConsigneVx(zoneId){if(!checkAdjAvailabilityVx(zoneId))return;if(hnwTemp.canReset[zoneId])prepareAdjustV1(zoneId,0);}
function prepareAdjustV1(zoneId,sp){var spx=sp==0?hnwTemp.spScheduled[zoneId]:sp;var sx=spx!=hnwTemp.spCurrent[zoneId]?"...":"";$('.zidConsigneVx'+zoneId)[0].innerHTML=spx+"°"+sx;refreshAdjButtonsVx(zoneId,true,sp!=0&&sp!=hnwTemp.spScheduled[zoneId],spx);if(sp==0||sp==hnwTemp.spScheduled[zoneId]){$('.zidSpDisplayImgVx'+zoneId).hide();$('.zidConsigneTypeUntilVx'+zoneId).hide();}else if(sp!=0&&sp!=hnwTemp.spCurrent[zoneId]){$('.zidSpDisplayImgVx'+zoneId).hide();$('.zidConsigneTypeUntilVx'+zoneId).hide();}else{resetIconAndTitleVx(zoneId);}
adjustBackgroundVx(zoneId,spx);if(hnwTemp.adjustPrepared[zoneId]!=null){clearTimeout(hnwTemp.adjustPrepared[zoneId]);hnwTemp.settingTempOpened=false;hnwTemp.adjustPrepared[zoneId]=null;}
if(spx!=hnwTemp.spCurrent[zoneId])prepareSettingTempV1(zoneId,sp,spx);}
function prepareSettingTempV1(zoneId,sp,spx){if(!hnwTemp.settingTempOpened){hnwTemp.settingTempOpened=true;hnwTemp.adjustPrepared[zoneId]=setTimeout(function(){settingTempVx(zoneId,sp,spx);},4000);}else{hnwTemp.adjustPrepared[zoneId]=setTimeout(function(){prepareSettingTempV1(zoneId,sp,spx);},500);}}
function settingTempVx(zoneId,sp,spx){if(hnwTemp.isAdjustV1||sp==0){hnwTemp.settingTempOpened=false;hnwTemp.adjustPrepared[zoneId]=null;return setTempVx(zoneId,sp,spx,null);}
var zSetThModes=hnwTemp.setThModes[zoneId];var hour=new Date().getHours();var mn=new Date().getMinutes()+4;if(mn>60){mn=0;hour++;if(hour==24)hour=0;}
if(mn%10!=0)mn+=10-mn%10;if(mn==60){mn=0;hour=hour==23?0:hour+1;}
var hmFirst=toHHMM(hour,mn);var _options=[];if(zSetThModes["STM_1"]){_options.push({text:"&nbsp;&nbsp;"+zSetThModes["STM_1"],value:"STM_1-null"});}
if(zSetThModes["STM_2"]&&hmFirst!=hnwTemp.spScheduledUntil[zoneId]&&hnwTemp.spScheduledUntil[zoneId]!="00:00"){_options.push({text:"&nbsp;&nbsp;"+getMsg(zSetThModes["STM_2"],hnwTemp.spScheduledUntil[zoneId]),value:"STM_2-"+hnwTemp.spScheduledUntil[zoneId]});}
if(zSetThModes["STM_3"]){var hmOptions="";do{var hm=toHHMM(hour,mn);hmOptions+="<option "+(hm==hnwTemp.spScheduledUntil[zoneId]?"style='background-color:green"+hnwCommon.imp+";' ":"")+"value='"+hm+"'>"+hm+"</option>";mn+=10;if(mn==60){mn=0;hour=hour==23?0:hour+1;}}while(hour!=0);_options.push({text:"&nbsp;&nbsp;"+zSetThModes["STM_3"]+"&nbsp;&nbsp;<select class='hourMinutes'>"+hmOptions+"</select>",value:"STM_3"});}
if(zSetThModes["STM_4"]){_options.push({text:"&nbsp;&nbsp;"+zSetThModes["STM_4"],value:"STM_4-00:00"});}
if(zSetThModes["STM_5"]){_options.push({text:"&nbsp;&nbsp;"+zSetThModes["STM_5"],value:"STM_5-99:99"});}
if($('.bootbox-prompt').length!=0){$('.bootbox-prompt').remove();}
var dialog=bootbox.prompt({title:getMsg(hnwTemp.lblAdjTHTitle1,hnwTemp.widgetTitle[zoneId]),inputType:'checkbox',inputOptions:_options,callback:function(choice){if(choice==null){refreshAdjButtonsVx(zoneId,true,hnwTemp.spCurrent[zoneId]!=hnwTemp.spScheduled[zoneId],hnwTemp.spCurrent[zoneId]);}else{choice=typeof choice=="object"?choice[0]:choice;var until=choice=="STM_3"?$('.hourMinutes').val():choice.split("-")[1];if(until=='null')until=null;spx=sp=parseFloat($('.zidConsigneV2')[0].innerHTML.replace('°',''));setTempVx(zoneId,sp,spx,until);}
hnwTemp.adjustPrepared[zoneId]=null;hnwTemp.settingTempOpened=false;}});dialog.init(function(){$('.bootbox-input-checkbox').attr('type','radio');$('.bootbox-input-checkbox').attr('name','idChoice');$('input[name="idChoice"]:first').prop('checked',true);$('input[name="idChoice"]').on('click',function(){adjustRightInfosV2(zoneId)});$('.hourMinutes').on('click',function(){$('input[name="idChoice"][value="3"]').prop('checked',true);adjustRightInfosV2(zoneId);});if(sp==-1){var adjBar='<div style="text-align:center;">';adjBar+='<a class="zidAdjustV2Lowest btn btn-sm TH2d5v2" style="background-color:'+hnwCommon.evoCmdBackgroundColor+' !important;">';adjBar+='<span class="zidAdjustV2LowestIcon" style="font-weight:100;">min</span>';adjBar+='<span style="display:block;line-height:6px;font-size:11px;">('+hnwTemp.adjustLow[zoneId]+'°)</span>';adjBar+='</a><a class="zidAdjustV2Minus btn btn-sm TH2d4v2" style="background-color:'+hnwCommon.evoCmdBackgroundColor+' !important;">';adjBar+='<span class="zidAdjustV2MinusIcon fa fa-minus-circle"/>';adjBar+='</a><div class="zidBackConsigneV2" style="display:inline-block;vertical-align:top;height:39px;width:140px;text-align:center">';adjBar+='<span class="zidConsigneV2" style="cursor:default;color:white;font-size:22px;font-family:\'Open Sans\',sans-serif;line-height:40px;">'+hnwTemp.spCurrent[zoneId]+'°</span>';adjBar+='<img class="TH2d3s2i zidSpImgV2" style="margin-left:4px;display:none;" src="plugins/'+hnwCommon.pluginName+'/img/empty.svg"/>';adjBar+='<span class="zidConsigneV2TypeUntil" style="color:white;margin-left:6px;"></span>';adjBar+='</div><a class="zidAdjustV2Plus btn btn-sm TH2d4v2" style="background-color:'+hnwCommon.evoCmdBackgroundColor+' !important;">';adjBar+='<span class="zidAdjustV2PlusIcon fa fa-plus-circle"/>';adjBar+='</a><a class="zidAdjustV2Highest btn btn-sm TH2d5v2" style="background-color:'+hnwCommon.evoCmdBackgroundColor+' !important;">';adjBar+='<span class="zidAdjustV2HighestIcon" style="font-weight:100;">max</span>';adjBar+='<span style="display:block;line-height:6px;font-size:11px;">('+hnwTemp.adjustHigh[zoneId]+'°)</span>';adjBar+='</a>';adjBar+='</div>';$(adjBar).insertBefore(".bootbox-form:first-child");adjustBackgroundVx(0,hnwTemp.spCurrent[zoneId]);adjustRightInfosV2(zoneId);adjustOKV2(zoneId,hnwTemp.spCurrent[zoneId]);$('.zidAdjustV2Lowest').on('click',function(){adjustXestV2(zoneId,-1);});$('.zidAdjustV2Minus').on('click',function(){adjustV2(zoneId,-1);});$('.zidAdjustV2Plus').on('click',function(){adjustV2(zoneId,1);});$('.zidAdjustV2Highest').on('click',function(){adjustXestV2(zoneId,1);});}});}
function adjustV2(zoneId,step){var sp=parseFloat($('.zidConsigneV2')[0].innerHTML.replace('°',''));var nStep=step==-1&&sp>hnwTemp.adjustLow[zoneId]?-hnwTemp.adjustStep[zoneId]:step==1&&sp<hnwTemp.adjustHigh[zoneId]?hnwTemp.adjustStep[zoneId]:0;if(nStep!=0){prepareAdjustV2(zoneId,Math.round(100*(sp+nStep),2)/100);}}
function adjustXestV2(zoneId,mode){prepareAdjustV2(zoneId,mode==-1?hnwTemp.adjustLow[zoneId]:hnwTemp.adjustHigh[zoneId]);}
function prepareAdjustV2(zoneId,sp){$('.zidConsigneV2')[0].innerHTML=sp+"°";refreshAdjButtonsVx(zoneId,true,sp!=hnwTemp.spScheduled[zoneId],sp);adjustBackgroundVx(0,sp);adjustRightInfosV2(zoneId);adjustOKV2(zoneId,sp);}
function adjustOKV2(zoneId,sp){}
function adjustRightInfosV2(zoneId){var choice=$('input[name="idChoice"]:checked')[0].value;var sp=parseFloat($('.zidConsigneV2')[0].innerHTML.replace('°',''));var until='';if(sp==hnwTemp.spCurrent[zoneId]){$('.zidSpImgV2').hide();}else{var img=choice=='STM_1-null'?'override-active.png':'temp-override.svg';$('.zidSpImgV2').attr('src','plugins/'+hnwCommon.pluginName+'/img/'+img);$('.zidSpImgV2').show();until=choice=='STM_3'?$('.hourMinutes').val():choice.split("-")[1];if(until=='99:99')until='';else if(until=='null')until='';}
$('.zidConsigneV2TypeUntil').html(until);}
function toHHMM(h,m){return(h<10?"0":"")+h+":"+(m==0?"00":m);}
function resetIconAndTitleVx(zoneId){if(hnwTemp.initialTitle[zoneId]=='')$('.zidSpDisplayImgVx'+zoneId).hide();else $('.zidSpDisplayImgVx'+zoneId).show();$('.zidConsigneTypeUntilVx'+zoneId).show();}
function setTempVx(zoneId,sp,spx,until){consignesData='manuel§'+zoneId+'§'+sp+'§'+spx+'§'+(until==null?"null":until);jeedom.cmd.execute({id:hnwTemp.cmdSetConsigneId[zoneId],notify:true,value:{'select':consignesData}});$('.zidConsigneVx'+zoneId)[0].innerHTML=spx;showSettingTempVx(zoneId,spx);}
function showSettingTempVx(zoneId,pConsigne){$('.zidSpDisplayImgVx'+zoneId).hide();$('.zidSetTempSpinnerVx'+zoneId).show();setBgColor('.zidBackConsigneVx'+zoneId,'gray');refreshAdjButtonsVx(zoneId,false,false,pConsigne);}
function adjustBackgroundVx(zoneId,sp){var bg=getBackColorForTemp(sp);if(zoneId!=0){setBgColor('.zidBackConsigneVx'+zoneId,bg);}else{setBgColor('.zidBackConsigneV2',bg);}}