<?php
/* This file is part of Jeedom.
 *
 * Jeedom is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Jeedom is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Jeedom. If not, see <http://www.gnu.org/licenses/>.
 */
require_once dirname(__FILE__) . '/../../../core/php/core.inc.php';
require_once dirname(__FILE__) . '/../core/class/lyric.php';
include_file('core', 'authentification', 'php');
if (!isConnect('admin')) {
	throw new Exception('{{401 - Accès non autorisé}}');
}
?>
<form class="form-horizontal">
	<fieldset>
		<div class="form-group">
			<label class="col-lg-3 control-label" style="font-size:15px;"><u>{{Système}}</u></label>
			<input type="hidden" class="configKey hnwSystem" data-l1key="hnwSystem" />
			<div class="col-lg-2" style="width:auto;">
				<label><input type="radio" id="sysChoice<?php echo honeywell::SYSTEM_EVOHOME;?>" name="sysChoice" value="<?php echo honeywell::SYSTEM_EVOHOME;?>" style="width:24px;vertical-align:middle;">&nbsp;&nbsp;Evohome</label>
			</div>
			<div class="col-lg-2" style="width:auto;">
				<label><input type="radio" id="sysChoice<?php echo honeywell::SYSTEM_LYRIC;?>" name="sysChoice" value="<?php echo honeywell::SYSTEM_LYRIC;?>" style="width:24px;vertical-align:middle;">&nbsp;&nbsp;Lyric T6/T6R</label>
			</div>
		</div>
		<div class="system<?php echo honeywell::SYSTEM_LYRIC;?> systems" style="display:none;">
			<div class="form-group">
				<div class="col-lg-3"></div>
				<div class="col-lg-2">
					<label>{{App Name}}</label><br/>
					<input type="text" class="configKey lyricAppName" data-l1key="<?php echo lyric::CFG_APP_NAME;?>" />
				</div>
				<div class="col-lg-3">
					<label>{{Consumer Key}}</label><br/>
					<input type="text" class="configKey lyricConsKey" style="width:260px;" data-l1key="<?php echo lyric::CFG_CONS_KEY;?>" />
				</div>
			</div>
			<div class="form-group">
				<div class="col-lg-3"></div>
				<div class="col-lg-2">
					<label>{{Secret Key}}</label><br/>
					<input type="password" class="configKey lyricSecretKey" data-l1key="<?php echo lyric::CFG_SECRET_KEY;?>" />
				</div>
				<div class="col-lg-4" style="padding-top:22px;">
					<a style="display:none;" target="_blank" id="callHnwlOAuth2"></a>
					<a class="btn btn-warning lyricToken" style="margin-left:32px;">{{Initialisation}}</a>
				</div>
			</div>
		</div>
		<div class="system<?php echo honeywell::SYSTEM_EVOHOME;?> systems" style="display:none;">
			<div class="form-group">
				<div class="col-lg-3"></div>
				<div class="col-lg-2">
					<label>{{Nom d'utilisateur}}</label>
					<br/>
					<input type="text" style="width:unset;" class="configKey form-control userName" data-l1key="<?php echo evohome::CFG_USER_NAME;?>" />
				</div>
				<div class="col-lg-2">
					<label>{{Mot de passe}}</label>
					<br/>
					<input type="password" style="width:unset;" class="configKey form-control password" data-l1key="<?php echo evohome::CFG_PASSWORD;?>" />
				</div>
			</div>
		</div>
		<div class="form-group" style="margin-bottom:30px;">
			<div class="col-lg-3"></div>
			<div class="col-lg-2">
				<label>{{Préfixe de nommage des thermostats}}</label>
				<br/>
				<input type="text" class="form-control thPrefix" style="width:80px;" value="TH" />
			</div>
			<div class="col-lg-3" style="margin-top:20px;margin-right:20px;">
				<?php if ( count(honeywell::getEquipments()) > 0 ) { ?>
					<input id="resizeWhenSynchronize" type="checkbox" style="width:24px;top: 4px!important;" class="resizeWhenSynchronize" />
					<label for="resizeWhenSynchronize" style="font-style:italic;">
					{{Redimensionner les widgets existants}}
					</label>
				<?php } ?>
				<br/>
				<a class="btn btn-warning btnSync">{{Synchroniser}}</a>
			</div>
		</div>

		<div class="form-group">
			<label class="col-lg-3 control-label" style="font-size:15px;"><u>{{Console}}</u></label>
		</div>
		<div class="form-group system<?php echo honeywell::SYSTEM_EVOHOME;?> systems" style="display:none;">
			<label class="col-lg-4 control-label" style="vertical-aglin:middle;">{{Modes de présence}}</label>
			<input type="hidden" class="configKey evoShowingModes" data-l1key="evoShowingModes" />
			<div class="col-lg-2" style="width:auto;">
				<label>
					<?php echo '<input type="radio" id="esm' . honeywell::CFG_SHOWING_MODE_CONSOLE . '" name="esm"
							value="' . honeywell::CFG_SHOWING_MODE_CONSOLE . '" style="width:24px;
    vertical-align:middle">'; ?>&nbsp;&nbsp;{{Intégré à la console}}
				</label>
			</div>
			<div class="col-lg-2" style="width:auto;">
				<label>
					<?php echo '<input type="radio" id="esm' . honeywell::CFG_SHOWING_MODE_POPUP . '" name="esm"
						value="' . honeywell::CFG_SHOWING_MODE_POPUP . '" style="width:24px;
    vertical-align:middle">'; ?>&nbsp;&nbsp;{{Par popup}}
				</label>
			</div>
		</div>
		<div class="form-group">
			<div class="col-lg-4">&nbsp;</div>
			<div class="col-lg-5">
				<label>
					<input class="configKey" type="checkbox" style="width:24px;" data-l1key="evoRefreshBeforeSave" />
					{{Forcer la lecture des données avant de sauvegarder la programmation}}
				</label>
			</div>
		</div>

		<div class="form-group">
			<label class="col-lg-3 control-label" style="font-size:15px;"><u>{{Thermostats}}</u></label>
		</div>
		<div class="form-group">
			<label class="col-lg-4 control-label" style="vertical-aglin:middle;">{{Couleur barres de titre}}</label>
			<!-- <input type="hidden" class="configKey evoBackColorTitleModes" data-l1key="evoBackColorTitleModes" /> -->
			<div class="col-lg-3">
				<select class="bctMode configKey form-control configuration" data-l1key="evoBackColorTitleModes">
					<option value="0">{{Inactif}}</option>
					<option value="1">{{Système (selon catégorie)}}</option>
					<option value="2">{{Système + 2 seuils :}} </option>
					<option value="3">{{Selon couleurs officielles (dégradés)}}</option>
				</select>
			</div>
			<div class="col-lg-3">
				{{orange si}} >=&nbsp;
				<input type="text" style="width:40px;text-align:center;" maxlength=4 class="bct2NA configKey form-control" data-l1key="<?php echo honeywell::CFG_BCT_2N_A;?>" />
				&nbsp;&nbsp;
				{{rouge si}} >=&nbsp;
				<input type="text" style="width:40px;text-align:center;" maxlength=4 class="bct2NB configKey form-control" data-l1key="<?php echo honeywell::CFG_BCT_2N_B;?>" />
			</div>
		</div>
		<div class="form-group">
			<label class="col-lg-4 control-label">{{Unité de température}}</label>
			<input type="hidden" class="configKey evoTempUnit" data-l1key="evoTempUnit" />
			<div class="col-lg-1" style="width:auto;">
				<label>
					<?php echo '<input type="radio" id="etu' . honeywell::CFG_UNIT_CELSIUS . '" name="etu"
							value="' . honeywell::CFG_UNIT_CELSIUS . '" style="width:24px;
    vertical-align:middle">'; ?>&nbsp;&nbsp;°C&nbsp;(Celsius)
				</label>
				<br/>
			</div>
			<div class="col-lg-2">
				<label>
					<?php echo '<input type="radio" id="etu' . honeywell::CFG_UNIT_FAHRENHEIT . '" name="etu"
							value="' . honeywell::CFG_UNIT_FAHRENHEIT . '" style="width:24px;
    vertical-align:middle">'; ?>&nbsp;&nbsp;°F&nbsp;(Fahrenheit)
				</label>
			</div>
		</div>
		<div class="form-group">
			<div class="col-lg-4"></div>
			<label class="col-lg-4" style="text-align:left;"><i>{{Attention : concerne l'affichage et le stockage historique}}</i></label>
		</div>
		<div class="form-group system<?php echo honeywell::SYSTEM_EVOHOME;?> systems" style="display:none;">
			<label class="col-lg-4 control-label">{{Précision}}</label>
			<div class="col-lg-3">
				<select class="configKey form-control configuration" data-l1key="evoDecimalsNumber">
					<option value="1">{{0.5 par défaut (X.82 > X.5) = Défaut Honeywell}}</option>
					<option value="2">{{0.5 arrondi (X.82 > X+1, X.44 > X.5)}}</option>
					<option value="3">{{0.05 arrondi (X.82 > X.80, X.44 > X.45)}}</option>
					<option value="4">{{0.01 = valeur native}}</option>
				</select>
			</div>
		</div>
		<div class="form-group">
			<label class="col-lg-4 control-label" style="vertical-aglin:middle;">{{Réglage des consignes}}</label>
			<input type="hidden" class="configKey evoHeatPointSettingModes" data-l1key="evoHeatPointSettingModes" />
			<div class="col-lg-2" style="width:auto;">
				<label>
					<?php echo '<input type="radio" id="hpsm' . honeywell::CFG_HP_SETTING_MODE_INTEGRATED . '" name="hpsm"
							value="' . honeywell::CFG_HP_SETTING_MODE_INTEGRATED . '" style="width:24px;
    vertical-align:middle">'; ?>&nbsp;&nbsp;{{Intégré au widget}}
				</label>
			</div>
			<div class="col-lg-2" style="width:auto;">
				<label>
					<?php echo '<input type="radio" id="hpsm' . honeywell::CFG_HP_SETTING_MODE_POPUP . '" name="hpsm"
							value="' . honeywell::CFG_HP_SETTING_MODE_POPUP . '" style="width:24px;
    vertical-align:middle">'; ?>&nbsp;&nbsp;{{Par popup}}
				</label>
			</div>
		</div>

		<div class="form-group">
			<label class="col-lg-3 control-label" style="font-size:15px;"><u>{{Programmes}}</u></label>
		</div>
		<div class="form-group">
			<label class="col-lg-4 control-label">{{Type d'affichage par défaut}}</label>
			<input type="hidden" class="configKey evoDefaultShowingScheduleMode" data-l1key="evoDefaultShowingScheduleMode" />
			<div class="col-lg-2" style="width:auto;">
				<label>
					<?php echo '<input type="radio" id="eshm' . honeywell::CFG_SCH_MODE_HORIZONTAL . '" name="eshm"
							value="' . honeywell::CFG_SCH_MODE_HORIZONTAL . '" style="width:24px;
    vertical-align:middle">'; ?>&nbsp;&nbsp;{{Horizontal}}
				</label>
			</div>
			<div class="col-lg-2" style="width:auto;">
				<label>
					<?php echo '<input type="radio" id="eshm' . honeywell::CFG_SCH_MODE_VERTICAL . '" name="eshm"
							value="' . honeywell::CFG_SCH_MODE_VERTICAL . '" style="width:24px;
    vertical-align:middle">'; ?>&nbsp;&nbsp;{{Vertical}}
				</label>
			</div>
		</div>
		<div class="form-group">
			<div class="col-lg-4">&nbsp;</div>
			<div class="col-lg-5">
				<label>
					<input class="configKey" type="checkbox" style="width:24px;" data-l1key="evoEditAvailable" />
					{{Mode édition disponible}}
				</label>
			</div>
		</div>

		<div class="form-group">
			<label class="col-lg-3 control-label" style="font-size:15px;"><u>{{Historique}}</u></label>
		</div>
		<div class="form-group">
			<label class="col-lg-4 control-label">{{Intervalle de mesure}}</label>
			<div class="col-lg-2">
				<select class="configKey form-control configuration" data-l1key="evoLoadingInterval">
					<option value="10">10mn</option>
					<option value="15">15mn</option>
					<option value="20">20mn</option>
					<option value="30">30mn</option>
				</select>
				<span><i>{{Ajuste la finesse et la charge mémoire de l'historique}}</i></span>
			</div>
			<span class="col-lg-4 control-label" style="text-align:left;">
				<label>
					<input class="configKey" type="checkbox" style="width:24px;" data-l1key="evoLoadingSync" />
					{{Synchronisation horloge (HH:MM, avec MM=intervalle*n)}}
				</label>
			</span>
		</div>
		<div class="form-group">
			<label class="col-lg-4 control-label">{{Durée de rétention}}</label>
			<div class="col-lg-2">
				<select class="configKey form-control configuration" data-l1key="evoHistoryRetention">
					<option value="">{{Jamais}}</option>
					<option value="-1 day">1 {{jour}}</option>
					<option value="-7 days">7 {{jours}}</option>
					<option value="-1 month">1 {{mois}}</option>
					<option value="-3 month">3 {{mois}}</option>
					<option value="-6 month">6 {{mois}}</option>
					<option value="-1 year">1 {{an}}</option>
				</select>
			</div>
			<span class="col-lg-4 control-label" style="text-align:left;"><i>{{Ajuste tous les équipements}}</i></span>
		</div>
	</fieldset>
</form>

<script>
var PluginName='<?php echo honeywell::PLUGIN_NAME?>';var PluginPath='/plugins/'+PluginName;var HnwDomain='<?php echo lyric::HNW_DOMAIN?>';var SYSTEM_LYRIC='<?php echo honeywell::SYSTEM_LYRIC?>';var esm=null;var ModePopup=null;setTimeout(function(){<?php
echo"var SYSTEM_EVOHOME = '".honeywell::SYSTEM_EVOHOME."';";echo"var UnitCelsius = '".honeywell::CFG_UNIT_CELSIUS."';";echo"var UnitFahrenheit = '".honeywell::CFG_UNIT_FAHRENHEIT."';";echo"var ShowHorizontal = '".honeywell::CFG_SCH_MODE_HORIZONTAL."';";echo"var ShowVertical = '".honeywell::CFG_SCH_MODE_VERTICAL."';";echo"var ModeConsole = '".honeywell::CFG_SHOWING_MODE_CONSOLE."';";echo"var ModePopup = '".honeywell::CFG_SHOWING_MODE_POPUP."';";echo"var HeatPointSettingModeConsole = '".honeywell::CFG_HP_SETTING_MODE_INTEGRATED."';";echo"var HeatPointSettingModePopup = '".honeywell::CFG_HP_SETTING_MODE_POPUP."';";$data=json_decode(file_get_contents(dirname(__FILE__)."/info.json"),true);echo"var version = '".(is_array($data)?$data["version"]:null)."';";?>var etu=$('.evoTempUnit').val();if(etu===''||etu==null){$('.userName').val('');$('.password').val('');}
if(etu!==UnitCelsius&&etu!==UnitFahrenheit){etu=UnitCelsius;$('.evoTempUnit').val(etu);}
document.getElementById('etu'+etu).checked=true;var eshm=$('.evoDefaultShowingScheduleMode').val();if(eshm!==ShowHorizontal&&eshm!==ShowVertical){eshm=ShowHorizontal;$('.evoDefaultShowingScheduleMode').val(eshm);}
document.getElementById('eshm'+eshm).checked=true;esm=$('.evoShowingModes').val();if(esm!==ModeConsole&&esm!==ModePopup){esm=ModeConsole;$('.evoShowingModes').val(esm);}
document.getElementById('esm'+esm).checked=true;var hpsm=$('.evoHeatPointSettingModes').val();if(hpsm!==HeatPointSettingModeConsole&&hpsm!==HeatPointSettingModePopup){hpsm=HeatPointSettingModeConsole;$('.evoHeatPointSettingModes').val(hpsm);}
document.getElementById('hpsm'+hpsm).checked=true;if($('.bct2NA').value()=='')$('.bct2NA').val(26);if($('.bct2NB').value()=='')$('.bct2NB').val(28);adjustBCTfield();var hnwSystem=$('.hnwSystem').val();if(hnwSystem=='')hnwSystem=SYSTEM_EVOHOME;document.getElementById('sysChoice'+hnwSystem).checked=true;showSystem(hnwSystem);if(version!=null)$('#span_plugin_install_date').html($('#span_plugin_install_date').html()+' ('+version+')');},250);$('input[name=sysChoice]').on('click',function(event){var hnwSystem=$('input[name=sysChoice]:checked').val();$('.hnwSystem').val(hnwSystem);showSystem(hnwSystem);});function showSystem(hnwSystem){$('.systems').hide();$('.system'+hnwSystem).show();if(hnwSystem==SYSTEM_LYRIC){$('.evoShowingModes').val(ModePopup);}else{$('.evoShowingModes').val(esm);}}
$('input[name=etu]').on('click',function(event){$('.evoTempUnit').val($('input[name=etu]:checked').val());});$('input[name=eshm]').on('click',function(event){$('.evoDefaultShowingScheduleMode').val($('input[name=eshm]:checked').val());});$('input[name=esm]').on('click',function(event){esm=$('input[name=esm]:checked').val();$('.evoShowingModes').val(esm);});$('input[name=hpsm]').on('click',function(event){$('.evoHeatPointSettingModes').val($('input[name=hpsm]:checked').val());});$('.bctMode').on('change',function(event){adjustBCTfield();});function adjustBCTfield(){$('.bct2NA').attr('disabled',$('.bctMode').value()!='2');$('.bct2NB').attr('disabled',$('.bctMode').value()!='2');}
$('.btnSync').on('click',function(){var hnwSystem=$('input[name=sysChoice]:checked').val();$('#bt_savePluginConfig').click();setTimeout(function(){var _thPrefix=$('.thPrefix').value().trim();if(_thPrefix!='')_thPrefix+=' ';$.ajax({type:'POST',url:PluginPath+'/core/ajax/honeywell.ajax.php',data:{action:'ajaxSynchronizeTH',system:hnwSystem,prefix:_thPrefix,resizeWhenSynchronize:$('.resizeWhenSynchronize').value()},dataType:'json',error:function(request,status,error){handleAjaxError(request,status,error);},success:function(data){if(data.state!='ok'){$('#div_alert').showAlert({message:data.result,level:'danger'});}else{$('#div_alert').showAlert({message:getMsg('{{Synchronisation effectuée : {0} comp. ajouté(s), {1} comp. modifié(s)}}',[data.result.added,data.result.modified]),level:'success'});if(data.result.added){document.location.href='/index.php?v=d&m='+PluginName+'&p='+PluginName;}}}})},1000);});$('.lyricToken').on('click',function(){var callbackUrl=document.location.protocol+'//'+document.location.host+PluginPath+'/core/php/lyric.callback.php';var consumerKey=$('.lyricConsKey').value();var randomKey=generateUUID();var params='?apikey='+consumerKey;params+='&app='+$('.lyricAppName').value();params+='&state='+randomKey;params+='&redirect_uri='+encodeURI(callbackUrl);var urlCode=HnwDomain+'oauth2/app/login'+params;$('#callHnwlOAuth2').attr('href',urlCode);document.getElementById('callHnwlOAuth2').click();var secretKey=$('.lyricSecretKey').value();$.ajax({type:'POST',url:PluginPath+'/core/ajax/honeywell.ajax.php',data:{action:'ajaxInitCallback',callbackUrl:callbackUrl,consumerKey:consumerKey,secretKey:secretKey,state:randomKey},dataType:'json',error:function(request,status,error){handleAjaxError(request,status,error);},success:function(data){}});});function getMsg(txt,args){if(!is_array(args)){return txt.replace("{0}",args);}
for(var i=0;i<args.length;i++){txt=txt.replace("{"+i+"}",args[i]);}
return txt;}
function generateUUID(){var d=new Date().getTime();var d2=((typeof performance!=='undefined')&&performance.now&&(performance.now()*1000))||0;return'xxxxxxxx-xxxx-xxxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g,function(c){var r=Math.random()*16;if(d2===0){r=(d+r)%16|0;d=Math.floor(d/16);}else{r=(d2+r)%16|0;d2=Math.floor(d2/16);}
return(c==='x'?r:(r&0x3|0x8)).toString(16);});}
</script>
