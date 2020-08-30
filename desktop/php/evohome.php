<?php
if (!isConnect('admin')) {
	throw new Exception('{{401 - Accès non autorisé}}');
}
$plugin = plugin::byId(honeywell::PLUGIN_NAME);
sendVarToJS('eqType', $plugin->getId());
$eqLogics = eqLogic::byType($plugin->getId());
?>

<div class="row row-overflow">
	<div class="col-xs-12 eqLogicThumbnailDisplay">
		<legend><i class="fas fa-cog"></i>  {{Gestion}}</legend>
		<div class="eqLogicThumbnailContainer">
			<div class="cursor eqLogicAction" style="background-color:var(--bg-modal-color) !important;" data-action="gotoPluginConf">
				<i class="fas fa-wrench"></i>
				<br>
				<span>{{Configuration}}</span>
			</div>
		</div>
		<br/>
		<legend><i class="fas fa-table"></i>  {{Composants}}</legend>
		<input class="form-control" placeholder="{{Rechercher}}" id="in_searchEqlogic" />

		<div class="eqLogicThumbnailContainer">
			<?php
			foreach ($eqLogics as $eqLogic) {
				$hnwSystem = $eqLogic->getConfiguration(honeywell::CONF_HNW_SYSTEM);
				if ( $hnwSystem == '' ) $hnwSystem = honeywell::SYSTEM_EVOHOME;
				$opacity = ($eqLogic->getIsEnable() && $eqLogic->getIsVisible()) ? '' : 'disableCard';
				echo '<div class="eqLogicDisplayCard cursor '.$opacity.'" data-eqLogic_id="' . $eqLogic->getId() . '">';
				$typeEqu = $eqLogic->getConfiguration(honeywell::CONF_TYPE_EQU);
				$modelType = $eqLogic->getConfiguration(honeywell::CONF_MODEL_TYPE);
				$zoneId = $eqLogic->getLogicalId();
				if ( $zoneId == honeywell::ID_NO_ZONE ) {
					$img = 'men_at_work.png';
				} else if ( $typeEqu == honeywell::TYPE_EQU_CONSOLE /*|| $zoneId == honeywell::OLD_ID_CONSOLE*/ ) {
					if ( $hnwSystem == honeywell::SYSTEM_EVOHOME ) {
						$img = $modelType == evohome::MODEL_TYPE_HEATING_ZONE ? 'console-small.png' : 'round-console-small.png';
					} else {
						$img = 'tx-console.png';
					}
				} else {
					if ( $hnwSystem == honeywell::SYSTEM_EVOHOME ) {
						$img = $modelType == evohome::MODEL_TYPE_HEATING_ZONE ? 'hr92-small.png' : 'round-th-small.png';
					} else {
						$img = 't6-th.png';
					}
				}
				echo '<br/><img src="plugins/'.honeywell::PLUGIN_NAME.'/img/' . $img . '" style="width:unset !important;min-height:unset !important;" /><br>';
				echo '<span class="name" style="position:relative;white-space:pre-wrap;word-wrap:break-word;">' . $eqLogic->getHumanName(true,true) .  '</span>';
				echo '</div>';
			}
			?>
		</div>
	</div>

	<div class="col-xs-12 eqLogic" style="display: none;">
		<div class="input-group pull-right" style="display:inline-flex">
			<span class="input-group-btn">
				<a class="btn btn-default btn-sm eqLogicAction roundedLeft" data-action="configure"><i class="fa fa-cogs"></i> {{Configuration avancée}}</a>
				<a class="btn btn-sm btn-success eqLogicAction" data-action="save"><i class="fas fa-check-circle"></i> {{Sauvegarder}}</a>
				<a class="btn btn-danger btn-sm eqLogicAction roundedRight" data-action="remove"><i class="fas fa-minus-circle"></i> {{Supprimer}}</a>
			</span>
		</div>

		<ul class="nav nav-tabs" role="tablist">
			<li role="presentation"><a href="#" class="eqLogicAction" aria-controls="home" role="tab" data-toggle="tab" data-action="returnToThumbnailDisplay"><i class="fa fa-arrow-circle-left"></i></a></li>
			<li role="presentation" class="active"><a href="#eqlogictab" aria-controls="home" role="tab" data-toggle="tab"><i class="fas fa-tachometer-alt"></i> {{Equipement}}</a></li>
			<li role="presentation"><a href="#commandtab" aria-controls="profile" role="tab" data-toggle="tab"><i class="fa fa-list-alt"></i> {{Commandes}}</a></li>
		</ul>

		<div class="tab-content" style="height:calc(100% - 50px);overflow:auto;overflow-x:hidden;">
			<div role="tabpanel" class="tab-pane active" id="eqlogictab">
				<div class="row">
					<div class="col-sm-6">
						<br/>
						<form class="form-horizontal">
							<fieldset>
								<legend>{{General}}</legend>
								<div class="form-group">
									<label class="col-sm-2 control-label">{{Nom Equipement}}</label>
									<div class="col-sm-8">
										<input id="equId" type="text" class="eqLogicAttr form-control" data-l1key="id" style="display:none;" />
										<input type="text" class="eqLogicAttr form-control" data-l1key="name" placeholder="{{Nom Equipement Evohome}}"/>
									</div>
								</div>
								<div class="form-group">
									<label class="col-sm-2 control-label">{{Objet Parent}}</label>
									<div class="col-sm-8">
										<select id="sel_object" class="eqLogicAttr form-control" data-l1key="object_id">
											<option value="">{{Aucune}}</option>
											<?php
											foreach (jeeObject::all() as $object) {
												echo '<option value="' . $object->getId() . '">' . $object->getName() . '</option>';
											}
											?>
										</select>
									</div>
								</div>
								<div class="form-group">
									<label class="col-sm-2 control-label">{{Catégorie}}</label>
									<div class="col-sm-8">
									<?php
									foreach (jeedom::getConfiguration('eqLogic:category') as $key=>$value) {
										echo '<label class="checkbox-inline">';
										echo '<input type="checkbox" class="eqLogicAttr" data-l1key="category" data-l2key="' . $key . '" />' . $value['name'];
										echo '</label>';
									}
									?>
									</div>
								</div>
								<div class="form-group">
									<label class="col-sm-2 control-label"></label>
									<div class="col-sm-9">
										<label class="checkbox-inline"><input type="checkbox" class="eqLogicAttr" data-l1key="isEnable" checked/>{{Activer}}</label>
										<label class="checkbox-inline"><input type="checkbox" class="eqLogicAttr" data-l1key="isVisible" checked/>{{Visible}}</label>
									</div>
								</div>
							</fieldset>
						</form>
					</div>
					<div class="col-sm-6">
						<form class="form-horizontal">
							<fieldset>
								<legend>
									<i class="fa fa-info-circle"></i>  {{Informations}}
								</legend>
								<div class="form-group">
									<label class="col-sm-3 control-label">{{Localisation}}</label>
									<div class="col-sm-8">
										<input type="hidden" class="hnwSystem eqLogicAttr" data-l1key="configuration" data-l2key="hnwSystem"/>
										<input type="hidden" class="locationId eqLogicAttr" data-l1key="configuration" data-l2key="locationId" />
										<select class="locationIdList form-control"></select>
									</div>
								</div>
								<div class="form-group">
									<label class="col-sm-3 control-label">{{Zone}}</label>
									<div class="col-sm-8">
										<input type="hidden" class="zoneId eqLogicAttr" data-l1key="logicalId" />
										<select class="zoneIdList form-control"></select>
									</div>
								</div>
								<center>
									<img src="core/img/no_image.gif" data-original=".png" class="img_device img-responsive" style="margin-top:20px;max-height:200px;">
								</center>
							</fieldset>
						</form>
					</div>
				</div>
			</div>
			<div role="tabpanel" class="tab-pane" id="commandtab">
				<br/><br/>
				<table id="table_cmd" class="table table-bordered table-condensed">
					<thead>
						<tr>
							<th style="width:70px;">Id</th>
							<th>{{Nom}}</th>
							<th>{{Type}}</th>
							<th id="_idSH">-</th>
							<th>{{Action}}</th>
						</tr>
					</thead>
					<tbody></tbody>
				</table>
			</div>
		</div>
	</div>
</div>

<script>
var PluginPath = 'plugins/<?php echo honeywell::PLUGIN_NAME?>';
var SystemEvohome = '<?php echo honeywell::SYSTEM_EVOHOME?>';
var SystemLyric = '<?php echo honeywell::SYSTEM_LYRIC?>';
var ModelTypeRound = '<?php echo evohome::MODEL_TYPE_ROUND_WIRELESS?>';
var modelType = null;
var settingLocationId = false;
var settingZoneId = false;
$('.hnwSystem').on('change', function() {
	log('hnwSystem.onChange('+$('.hnwSystem').value()+')');
	if ( $('#equId').value() == '' ) return;
	changeSystem($('.hnwSystem').value());
});
function changeSystem(hnwSystem) {
	log('changeSystem C='+hnwSystem);
	var locationIdList = $('.locationIdList')[0];
	locationIdList.options.length = 0;
	locationIdList.options[0] = new Option('{{lecture...}}', -2);
	locationIdList.options[0].selected = true;
	$.ajax({
		type:'POST',
		url:PluginPath+'/core/ajax/honeywell.ajax.php',
		data:{action:'ajaxListLocations', system:hnwSystem},
		dataType:'json',
		error:function(request, status, error) {
			handleAjaxError(request, status, error);
		},
		success:function(data) {
			if (data.state != 'ok') {
				$('#div_alert').showAlert({message:data.result, level:'danger'});
			} else if ( is_array(data.result.loc) ) {
				modelType = null;
				var idSelect = 0;
				var locationId = $('.locationId').value();
				if ( data.result.loc.length == 0 ) {
					locationIdList.options[1] = new Option('{{Défaut}}', -1);
				} else {
					data.result.loc.forEach(function(loc,idx) {
						modelType = loc.modelType;
						if ( loc.locationId == locationId ) {
							idSelect = locationIdList.options.length;
						}
						locationIdList.options[locationIdList.options.length] = new Option(loc.name, loc.locationId);
					});
				}
				locationIdList.options[idSelect].selected = true;
				$('.locationIdList').change();
				locationIdList.options[0].text = '{{Aucun}}';
			}
		}
	});
}
function cantEnterLocation(id) {
	var ret = $('.hnwSystem').value() == null ||  $('.hnwSystem').value() == '' || id == null || id == '' || id == -2 || settingLocationId;
	log('cantEnterLocation='+ret);
	return ret;
}
$('.locationId').on('change', function() {
	var id = $(this).value();
	log('locationId.change('+id+')');
	if ( cantEnterLocation(id) ) return ;
	changeLocation();

	settingLocationId = true;
	$('.locationIdList').value(id);
	settingLocationId = false;
});
$('.locationIdList').on('change', function() {
	var id = $(this).value();
	log('locationIdList.change('+id+')');
	if ( cantEnterLocation(id) ) return ;
	changeLocation();

	settingLocationId = true;
	$('.locationId').value(id);
	settingLocationId = false;
});
function changeLocation() {
	var locId = $('.locationId').value();
	//log('a '+locId);
	if ( locId == null || locId == -2 ) return;
	var zoneIdList = $('.zoneIdList')[0];
	var hnwSystem = $('.hnwSystem').value();
	//log('b '+hnwSystem);
	zoneIdList.options.length = 0;
	zoneIdList.options[0] = new Option('{{lecture...}}', -100);
	zoneIdList.options[0].selected = true;
	$.ajax({
		type:'POST',
		url:PluginPath+'/core/ajax/honeywell.ajax.php',
		data:{action:'ajaxListLocations', system:hnwSystem},
		dataType:'json',
		error:function(request, status, error) {
			handleAjaxError(request, status, error);
		},
		success:function(data) {
			if (data.state != 'ok') {
				$('#div_alert').showAlert({message:data.result, level:'danger'});
			} else if ( is_array(data.result.loc) ) {
				var idSelect = 0;
				var zoneId = $('.zoneId').value();
				modelType = null;
				data.result.loc.forEach(function(loc,idx) {
					if ( loc.locationId == locId ) {
						zoneIdList.options[1] = new Option('{{Console}}', locId);
						if ( zoneId == locId ) {
							idSelect = 1;
						}
						modelType = loc.modelType;
						if ( hnwSystem != 'LYRIC' ) {
							loc.zones.forEach(function(zone,idx) {
								if ( zone.id == zoneId ) {
									idSelect = zoneIdList.options.length;
								}
								zoneIdList.options[zoneIdList.options.length] = new Option(zone.name, zone.id);
							});
						} else {
							loc.devices.forEach(function(zone,idx) {
								zoneIdList.options[zoneIdList.options.length] = new Option(zone.name+' ('+zone.deviceModel+')', zone.deviceID);
								if ( zone.deviceID == zoneId ) {
									idSelect = zoneIdList.options.length - 1;
								}
							});
						}
					}
				});
				zoneIdList.options[idSelect].selected = true;
				changeZone($('.zoneIdList').value());
				zoneIdList.options[0] = new Option('{{Aucune}}', -2);
			}
		}
	});
}
function cantEnterZone(id) {
	var ret = $('.locationId').value() == null || $('.locationId').value() == -2 || settingZoneId || id == null || id == '' || id == -100;
	log('cantEnterZone='+ret);
	return ret;
}
$('.zoneId').on('change', function() {
	var id = $(this).value();
	log('zoneId.change('+id+')');
	if ( cantEnterZone(id) ) return ;
	changeZone(id);

	settingZoneId = true;
	$('.zoneIdList').value(id);
	settingZoneId = false;
});
$('.zoneIdList').on('change', function() {
	var id = $(this).value();
	log('zoneIdList.change('+id+')');
	if ( cantEnterZone(id) ) return ;
	changeZone(id);

	settingZoneId = true;
	$('.zoneId').value(id);
	settingZoneId = false;
});
function changeZone(idZone) {
	var imgName = null;
	if ( idZone != null && idZone != -2 ) {
		var hnwSystem = $('.hnwSystem').value();
		if ( idZone == $('.locationId').value() /*|| idZone == <?php echo evohome::OLD_ID_CONSOLE;?>*/ ) {
			if ( hnwSystem == SystemEvohome ) {
				imgName = modelType != ModelTypeRound ? 'console.png' : 'round-console.png';
			} else if ( hnwSystem == SystemLyric ) {
				imgName = 'tx-console.png';
			}
		} else {
			if ( hnwSystem == SystemEvohome ) {
				imgName = modelType != ModelTypeRound ? 'hr92.png' : 'round-th.png';
			} else if ( hnwSystem == SystemLyric ) {
				imgName = 't6-th.png';
			}
		}
	}
	if ( imgName != null ) $('.img_device').attr('src',PluginPath+'/img/'+imgName);
}
$('.fa-arrow-circle-left').on('click', function() { $('.img_device').attr('src','core/img/no_image.gif'); });
function log(msg) {
	//console.log("T"+new Date().getSeconds()+" - "+msg);
}
</script>
<?php include_file('desktop', 'honeywell', 'js', honeywell::PLUGIN_NAME);
include_file('core', 'plugin.template', 'js');
?>
