if(typeof window._msgAdjustConsigneUnavailable=='undefined'){window._msgAdjustConsigneUnavailable=null;window.canReset=[];window.adjAvailable=[];window.msgEnforceConsigne=[];window.spScheduled=[];window.spScheduledUntil=[];window.spCurrent=[];window.adjRunning=[];window.adjustLow=[];window.adjustHigh=[];window.adjustStep=[];window.adjustPrepared=[];window.cmdSetConsigneId=[];window.initialSrc=[];window.initialTitle=[];window.C2BG={'25':'#f21f1f','22':'#ff5b1a','19':'#fa9e2d','16':'#2e9985'};window.thTimeout=[];window.settingTempOpened=false;window.adjustHMByChoice=null;}
function initVarsTH(_locId,_id,_zoneId){$('.showSavedScheduleCaption'+_id).css('color',getScheduleListId(_locId)==0?'gray':'white');$('.showCurrentScheduleC'+_id).on('click',function(){_showScheduleTH(_locId,_id,_zoneId,'C');});$('.showCurrentScheduleS'+_id).on('click',function(){if(getScheduleListId(_locId)>0)_showScheduleTH(_locId,_id,_zoneId,'S');});evoRegisterHover(_id,'TH');}
function getScheduleListId(locId){return typeof genConsoleId=='undefined'||genConsoleId[locId]=='undefined'||$('#scheduleList'+genConsoleId[locId]).length==0?0:$('#scheduleList'+genConsoleId[locId]).value();}
function _showScheduleTH(locId,id,zoneId,type){if($('.showCurrentSchedule'+type+id).css("background-color")!="rgb(16, 208, 16)"){showScheduleTH(locId,id,zoneId,type);}else{$('#md_modal').dialog('close');}}
function showScheduleTH(locId,id,zoneId,type,mode){__generalLastMode=mode==null?__generalLastMode:mode;$('#md_modal').dialog('close');$('#md_modal').dialog({title:"",height:'auto',width:(__generalLastMode==_showHorizontal?Math.min(1000,jQuery(window).width()-16):700),position:{my:"center top",at:"center top+50",of:window}});var fileId=type=='C'?0:$('#scheduleList'+genConsoleId[locId]+' option:selected').value();$('#md_modal').load('index.php?v=d&plugin=evohome&modal=schedule'+__generalLastMode+'&id='+id+'&'+_argZoneId+'='+zoneId+'&'+_argFileId+'='+fileId+'&edit=0&mode='+__generalLastMode+'&typeSchedule='+type).dialog('open');$('.showCurrentSchedule'+type+id).css("background-color","rgb(16, 208, 16)");}
function initVarsAC(_taskIsRunning,pAdjAvailable,pMsgAdjustConsigneUnavailable,pMsgEnforceConsigne,zoneId,pCurrentConsigne,pCurrentConsigneUntil,pConsigne,pAdjustLow,pAdjustHigh,pAdjustStep,pCmdSetConsigneId,pCanReset){if(pCmdSetConsigneId.substr(0,1)=="#"){$('.zidAdjustReset'+zoneId).hide();$('#zidAdjustPlus'+zoneId).hide();$('#zidAdjustMinus'+zoneId).hide();$('#zidAdjustLowest'+zoneId).hide();$('#zidAdjust'+zoneId).hide();}else{$('.zidAdjustReset'+zoneId).on('click',function(){resetConsigne(zoneId);});$('#zidAdjustMinus'+zoneId).on('click',function(){adjust(zoneId,-1);});$('#zidAdjustPlus'+zoneId).on('click',function(){adjust(zoneId,1);});$('#zidAdjustLowest'+zoneId).on('click',function(){adjustLowest(zoneId);});$('#zidAdjust'+zoneId).on('click',function(){adjustPanel(zoneId);});_msgAdjustConsigneUnavailable=pMsgAdjustConsigneUnavailable;adjAvailable[zoneId]=pAdjAvailable=='true';msgEnforceConsigne[zoneId]=pMsgEnforceConsigne;spScheduled[zoneId]=parseFloat(pCurrentConsigne);spScheduledUntil[zoneId]=pCurrentConsigneUntil;spCurrent[zoneId]=parseFloat(pConsigne);adjustLow[zoneId]=parseFloat(pAdjustLow);adjustHigh[zoneId]=parseFloat(pAdjustHigh);adjustStep[zoneId]=parseFloat(pAdjustStep);cmdSetConsigneId[zoneId]=parseInt(pCmdSetConsigneId);initialSrc[zoneId]=$('#zidSpImg'+zoneId).attr('src');initialTitle[zoneId]=$('#zidSpDisplayImg'+zoneId).attr('title');refreshAdjButtons(zoneId,_apiAvailable&&adjAvailable[zoneId],pCanReset,pConsigne);adjRunning[zoneId]=false;}
if(_taskIsRunning)showSettingTemp(zoneId,0);}
function refreshAdjButtons(zoneId,pCanAdjust,pCanReset,pConsigne){if(!pCanAdjust)adjRunning[zoneId]=true;canReset[zoneId]=pCanReset;$('.zidAdjustResetIcon'+zoneId).css('color',_apiAvailable&&adjAvailable[zoneId]&&spScheduled[zoneId]!=0&&canReset[zoneId]?'white':'gray');var colorLow=!pCanAdjust||pConsigne==adjustLow[zoneId]?'gray':'white';var colorHigh=!pCanAdjust||pConsigne==adjustHigh[zoneId]?'gray':'white';$('#zidAdjustLowestIcon'+zoneId).css('color',colorLow);$('#zidAdjustMinusIcon'+zoneId).css('color',colorLow);$('#zidAdjustPlusIcon'+zoneId).css('color',colorHigh);$('#zidAdjustIcon'+zoneId).css('color',!pCanAdjust?'gray':'white');$('#zidAdjustLowestIcon').css('color',colorLow);$('#zidAdjustMinusIcon').css('color',colorLow);$('#zidAdjustPlusIcon').css('color',colorHigh);$('#zidAdjustHighestIcon').css('color',colorHigh);}
function checkAdjAvailability(zoneId){if(!checkApiAvailable())return false;if(!adjAvailable[zoneId]){myAlert(_msgAdjustConsigneUnavailable);return false;}
return!adjRunning[zoneId];}
function adjust(zoneId,step){if(!checkAdjAvailability(zoneId))return;var sp=parseFloat($('#zidConsigne'+zoneId)[0].innerHTML.replace('°',''));var nStep=step==-1&&sp>adjustLow[zoneId]?-adjustStep[zoneId]:step==1&&sp<adjustHigh[zoneId]?adjustStep[zoneId]:0;if(nStep!=0){prepareAdjust(zoneId,sp+nStep);}}
function adjustLowest(zoneId){if(!checkAdjAvailability(zoneId))return;prepareAdjust(zoneId,adjustLow[zoneId]);}
function adjustPanel(zoneId){if(!checkAdjAvailability(zoneId))return;settingTemp(zoneId,-1,-1);}
function resetConsigne(zoneId){if(!checkAdjAvailability(zoneId))return;if(canReset[zoneId]){prepareAdjust(zoneId,0);}}
function prepareAdjust(zoneId,sp){var spx=sp==0?spScheduled[zoneId]:sp;var sx=spx!=spCurrent[zoneId]?"...":"";$('#zidConsigne'+zoneId)[0].innerHTML=spx+"°"+sx;refreshAdjButtons(zoneId,true,sp!=0&&sp!=spScheduled[zoneId],spx);if(sp==0||sp==spScheduled[zoneId]){$('#zidSpDisplayImg'+zoneId).hide();$('#zidConsigneTypeUntil'+zoneId).hide();}else if(sp!=0&&sp!=spCurrent[zoneId]){$('#zidSpDisplayImg'+zoneId).hide();$('#zidConsigneTypeUntil'+zoneId).hide();}else{resetIconAndTitle(zoneId);}
adjustBackground(zoneId,spx);if(adjustPrepared[zoneId]!=null){clearTimeout(adjustPrepared[zoneId]);adjustPrepared[zoneId]=null;}
if(spx!=spCurrent[zoneId]){prepareSettingTemp(zoneId,sp,spx);}}
function prepareSettingTemp(zoneId,sp,spx){if(!settingTempOpened){settingTempOpened=true;adjustPrepared[zoneId]=setTimeout(function(){settingTemp(zoneId,sp,spx);},4000);}else{adjustPrepared[zoneId]=setTimeout(function(){prepareSettingTemp(zoneId,sp,spx);},500);}}
function settingTemp(zoneId,sp,spx){if(sp==0){settingTempOpened=false;adjustPrepared[zoneId]=null;return setTemp(zoneId,sp,spx,null);}
var hour=new Date().getHours();var mn=new Date().getMinutes()+4;if(mn>60){mn=0;hour++;if(hour==24)hour=0;}
if(mn%10!=0)mn+=10-mn%10;if(mn==60){mn=0;hour=hour==23?0:hour+1;}
hmFirst=toHHMM(hour,mn);var _options=[{text:"  de façon permanente",value:1}];if(hmFirst!=spScheduledUntil[zoneId]&&spScheduledUntil[zoneId]!="00:00"){_options.push({text:getMsg("  jusqu'à la fin de la programmation courante, soit {0}",spScheduledUntil[zoneId]),value:2});}
hmOptions="";do{hm=toHHMM(hour,mn);hmOptions+="<option "+(hm==spScheduledUntil[zoneId]?"style='background-color:green;' ":"")+"value='"+hm+"'>"+hm+"</option>";mn+=10;if(mn==60){mn=0;hour=hour==23?0:hour+1;}}while(hour!=0);_options.push({text:"  jusqu'à&nbsp;&nbsp;<select id='hourMinutes'>"+hmOptions+"</select>",value:3});_options.push({text:"  jusqu'à la fin de la journée",value:4});adjustHMByChoice=[null,spScheduledUntil[zoneId],"dummy","00:00"];if($('.bootbox-prompt').length!=0){$('.bootbox-prompt').remove();}
var dialog=bootbox.prompt({title:sp==-1?"Modification de la consigne":getMsg("La consigne de {0}° sera maintenue :",spx),inputType:'checkbox',inputOptions:_options,callback:function(choice){if(choice==null){if(sp!=-1){$('#zidConsigne'+zoneId)[0].innerHTML=spCurrent[zoneId]+"°";resetIconAndTitle(zoneId);adjustBackground(zoneId,spCurrent[zoneId]);}
refreshAdjButtons(zoneId,true,spCurrent[zoneId]!=spScheduled[zoneId],spCurrent[zoneId]);}else{var until=choice!=3?adjustHMByChoice[choice-1]:$('#hourMinutes').val();if(sp==-1){spx=sp=parseFloat($('#zidConsigne')[0].innerHTML.replace('°',''));}
setTemp(zoneId,sp,spx,until)}
adjustPrepared[zoneId]=null;settingTempOpened=false;}});dialog.init(function(){$('.bootbox-input-checkbox').attr('type','radio');$('.bootbox-input-checkbox').attr('name','idChoice');$('input[name="idChoice"]:first').prop('checked',true);$('input[name="idChoice"]').on('click',function(){adjustRightInfos(zoneId)});$('#hourMinutes').on('click',function(){$('input[name="idChoice"][value="3"]').prop('checked',true);adjustRightInfos(zoneId);});if(sp==-1){var adjBar='<div style="text-align:center;">';adjBar+='<a id="zidAdjustLowest" class="btn btn-sm TH2d5v2">';adjBar+='<span id="zidAdjustLowestIcon" style="font-weight:100;">min</span>';adjBar+='</a><a id="zidAdjustMinus" class="btn btn-sm TH2d4v2">';adjBar+='<span id="zidAdjustMinusIcon" class="fa fa-minus-circle"/>';adjBar+='</a><div id="zidBackConsigne" style="display:inline-block;vertical-align:top;height:39px;width:140px;text-align:center">';adjBar+='<span id="zidConsigne" style="cursor:default;color:white;font-size:22px;font-family:\'Open Sans\',sans-serif;line-height:40px;">'+spCurrent[zoneId]+'°</span>';adjBar+='<img id="zidSpImg" class="TH2d3s2i" style="margin-left:4px;display:none;" src="dummy"/>';adjBar+='<span id="zidConsigneTypeUntil" style="color:white;margin-left:6px;"></span>';adjBar+='</div><a id="zidAdjustPlus" class="btn btn-sm TH2d4v2">';adjBar+='<span id="zidAdjustPlusIcon" class="fa fa-plus-circle"/>';adjBar+='</a><a id="zidAdjustHighest" class="btn btn-sm TH2d5v2">';adjBar+='<span id="zidAdjustHighestIcon" style="font-weight:100;">max</span>';adjBar+='</a>';adjBar+='</div>';$(adjBar).insertBefore(".bootbox-form:first-child");adjustBackground(0,spCurrent[zoneId]);adjustRightInfos(zoneId);adjustOK(zoneId,spCurrent[zoneId]);$('#zidAdjustLowest').on('click',function(){adjustXestV2(zoneId,-1);});$('#zidAdjustMinus').on('click',function(){adjustV2(zoneId,-1);});$('#zidAdjustPlus').on('click',function(){adjustV2(zoneId,1);});$('#zidAdjustHighest').on('click',function(){adjustXestV2(zoneId,1);});}});}
function adjustV2(zoneId,step){var sp=parseFloat($('#zidConsigne')[0].innerHTML.replace('°',''));var nStep=step==-1&&sp>adjustLow[zoneId]?-adjustStep[zoneId]:step==1&&sp<adjustHigh[zoneId]?adjustStep[zoneId]:0;if(nStep!=0){prepareAdjustV2(zoneId,sp+nStep);}}
function adjustXestV2(zoneId,mode){prepareAdjustV2(zoneId,mode==-1?adjustLow[zoneId]:adjustHigh[zoneId]);}
function prepareAdjustV2(zoneId,sp){$('#zidConsigne')[0].innerHTML=sp+"°";refreshAdjButtons(zoneId,true,sp!=spScheduled[zoneId],sp);adjustBackground(0,sp);adjustRightInfos(zoneId);adjustOK(zoneId,sp);}
function adjustOK(zoneId,sp){$('button[data-bb-handler="confirm"]').attr("disabled",sp==spCurrent[zoneId]?"true":null);}
function adjustRightInfos(zoneId){var choice=$('input[name="idChoice"]:checked')[0].value;var sp=parseFloat($('#zidConsigne')[0].innerHTML.replace('°',''));var until='';if(sp==spCurrent[zoneId]){$('#zidSpImg').hide();}else{var img=choice=='1'?'override-active.png':'temp-override.svg';$('#zidSpImg').attr('src','plugins/evohome/img/'+img);$('#zidSpImg').show();until=choice!='3'?adjustHMByChoice[choice-1]:$('#hourMinutes').val();}
$('#zidConsigneTypeUntil').html(until);}
function toHHMM(h,m){return(h<10?"0":"")+h+":"+(m==0?"00":m);}
function resetIconAndTitle(zoneId){if(initialTitle[zoneId]=='')$('#zidSpDisplayImg'+zoneId).hide();else $('#zidSpDisplayImg'+zoneId).show();$('#zidConsigneTypeUntil'+zoneId).show();}
function setTemp(zoneId,sp,spx,until){consignesData='manuel#'+zoneId+'#'+sp+'#'+spx+'#'+(until==null?"null":until);jeedom.cmd.execute({id:cmdSetConsigneId[zoneId],notify:true,value:{'select':consignesData}});$('#zidConsigne'+zoneId)[0].innerHTML=spx;showSettingTemp(zoneId,spx);}
function showSettingTemp(zoneId,pConsigne){$('#zidSpDisplayImg'+zoneId).hide();$('#zidSetTempSpinner'+zoneId).show();$('#zidBackConsigne'+zoneId).css('background-color','gray');refreshAdjButtons(zoneId,false,false,pConsigne);}
function adjustBackground(zoneId,sp){var bg='#247eb2';for(ref in C2BG)if(sp>=parseInt(ref))bg=C2BG[ref];if(zoneId!=0){$('#zidBackConsigne'+zoneId).css('background-color',bg);}else{$('#zidBackConsigne').css('background-color',bg);}}