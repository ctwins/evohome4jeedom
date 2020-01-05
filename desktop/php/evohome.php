<?php
if (!isConnect('admin')) {
	throw new Exception('{{401 - Accès non autorisé}}');
}
$plugin = plugin::byId('evohome');
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
				$opacity = ($eqLogic->getIsEnable() && $eqLogic->getIsVisible()) ? '' : 'disableCard';
				echo '<div class="eqLogicDisplayCard cursor '.$opacity.'" data-eqLogic_id="' . $eqLogic->getId() . '">';
				$typeEqu = $eqLogic->getConfiguration(evohome::CONF_TYPE_EQU);
				$modelType = $eqLogic->getConfiguration(evohome::CONF_MODEL_TYPE);
				$zoneId = $eqLogic->getLogicalId();
				if ( $zoneId == evohome::ID_NO_ZONE ) $img = 'men_at_work.png';
				else if ( $typeEqu == evohome::TYPE_EQU_CONSOLE || $zoneId == evohome::OLD_ID_CONSOLE) $img = $modelType == evohome::MODEL_TYPE_ROUND_WIRELESS ? 'round-console-small.png' : 'console-small.png';
				else $img = $modelType == evohome::MODEL_TYPE_ROUND_WIRELESS ? 'round-th-small.png' : 'hr92-small.png';
				echo '<br/><img src="plugins/evohome/img/' . $img . '" style="width:unset !important;min-height:unset !important;" /><br>';
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
										<input type="text" class="eqLogicAttr form-control" data-l1key="id" style="display:none;" />
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
									foreach (jeedom::getConfiguration('eqLogic:category') as $key => $value) {
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
										<script>var oldLocId = -2;</script>
										<select class="locationId eqLogicAttr form-control" data-l1key="configuration" data-l2key="locationId">
											<option value="-2">{{Aucun}}</option>
											<?php
											$locations = evohome::listLocations();
											if ( is_array($locations) ) {
												foreach ($locations as $location) {
													echo '<option value="' . $location['locationId'] . '">' . $location['name'] . '</option>';
												}
											} else {
												echo '<option value="-1">{{Défaut}}</option>';
											}
											?>
										</select>
									</div>
								</div>
								<div class="form-group">
									<label class="col-sm-3 control-label">{{Zone}}</label>
									<div class="col-sm-8">
										<!-- <input type="hidden" class="zoneId eqLogicAttr" data-l1key="configuration" data-l2key="zoneId"/> -->
										<input type="hidden" class="zoneId eqLogicAttr" data-l1key="logicalId" />
										<select class="zoneIdTmp form-control">
											<option value="-2">{{Aucune}}</option>
										</select>
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
var modelType = null;
var settingZoneId = false;

function cantEnter(id) {
	return $('.locationId').value() == null || $('.locationId').value() == -2 || settingZoneId || id == null || id == '';
}

$('.zoneId').on('change', function() {
	var id = $(this).value();
	//console.log("change zoneId with '"+id+"'");
	if ( cantEnter(id) ) {
		//console.log("-> nothing to do");
		return ;
	}
	loadImage(id);

	settingZoneId = true;
	//console.log("set zoneIdTmp with "+id);
	$('.zoneIdTmp').value(id);
	settingZoneId = false;
});

$('.zoneIdTmp').on('change', function() {
	var id = $(this).value();
	//console.log("change zoneIdTmp with '"+id+"'");
	if ( cantEnter(id) ) {
		//console.log("-> nothing to do");
		return ;
	}
	loadImage(id);

	settingZoneId = true;
	//console.log("set zoneId with "+id);
	$('.zoneId').value(id);
	settingZoneId = false;
});

$('.locationId').on('change', function() { loadZones(); });
function loadZones() {
	var locId = $('.locationId').value();
	if ( locId == null || locId == -2 ) return;
	//console.log("change locationId with " + locId);
	var zoneIdTmp = $('.zoneIdTmp')[0];
	$.ajax({
		type:"POST",
		url:"plugins/evohome/core/ajax/evohome.ajax.php",
		data:{action:"listLocations"},
		dataType:'json',
		error:function(request, status, error) {
			handleAjaxError(request, status, error);
		},
		success:function(data) {
			if (data.state != 'ok') {
				$('#div_alert').showAlert({message:data.result, level:'danger'});
			} else if ( is_array(data.result.loc) ) {
				zoneIdTmp.options.length = 1;
				var idSelect = 0;
				var zoneId = $('.zoneId').value();
				modelType = null;
				data.result.loc.forEach(function(loc,idx) {
					if ( loc.locationId == locId ) {
						zoneIdTmp.options[1] = new Option('{{Console}}', locId);
						if ( zoneId == locId || zoneId == <?php echo evohome::OLD_ID_CONSOLE;?> ) {
							idSelect = 1;
						}
						modelType = loc.modelType;
						loc.zones.forEach(function(zone,idx) {
							var txt = zone.name;
							zoneIdTmp.options[zoneIdTmp.options.length] = new Option(txt, zone.id);
							if ( zone.id == zoneId ) {
								idSelect = zoneIdTmp.options.length - 1;
							}
						});
					}
				});
				zoneIdTmp.options[idSelect].selected = true;
				loadImage($('.zoneIdTmp').value());
			}
		}
	});
}

function loadImage(idZone) {
	//console.log("loadImage : idZone='"+idZone+"', locId='"+$('.locationId').value()+"'");
	var imgName = 'plugins/evohome/evohome.png';
	if ($('.li_eqLogic.active').attr('data-eqlogic_id') != '') {
		if ( idZone == null || idZone == -2 ) imgName = 'core/img/no_image.gif';
		else {
			imgName = 'plugins/evohome/img/';
			if ( idZone == $('.locationId').value() || idZone == <?php echo evohome::OLD_ID_CONSOLE;?> )
				imgName += modelType == 'RoundWireless' ? 'round-console.png' : 'console.png';
			else
				imgName += modelType == 'RoundWireless' ? 'round-th.png' : 'hr92.png';
		}
	}
	//console.log("imgName="+imgName);
	$('.img_device').attr('src',imgName);
}

<?php 
echo "var _msgShow = '{{Afficher}}';\n";
echo "var _msgHistorize = '{{Historiser}}';\n";
?>
</script>
<?php include_file('desktop', 'evohome', 'js', 'evohome');
include_file('core', 'plugin.template', 'js');
?>
