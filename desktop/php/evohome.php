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
		<legend><i class="fas fa-cog"></i> {{Gestion}}</legend>
		<div class="eqLogicThumbnailContainer">
			<div class="cursor eqLogicAction" style="background-color:var(--bg-modal-color) !important;" data-action="gotoPluginConf">
				<i class="fas fa-wrench"></i>
				<br>
				<span>{{Configuration}}</span>
			</div>
		</div>
		<br/>
		<legend><i class="fas fa-table"></i> {{Composants}}</legend>
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
									<i class="fa fa-info-circle"></i> {{Informations}}
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
								<div style="text-align:center;">
									<img src="core/img/no_image.gif" data-original=".png" class="img_device img-responsive" style="display:unset;margin-top:20px;max-height:200px;">
								</div>
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
</script>
<?php
include_file('desktop', 'honeywell', 'js', honeywell::PLUGIN_NAME);
include_file('core', 'plugin.template', 'js');
?>
