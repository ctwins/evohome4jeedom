<?php
if (!isConnect('admin')) {
	throw new Exception('{{401 - Accès non autorisé}}');
}
$plugin = plugin::byId('evohome');
sendVarToJS('eqType', $plugin->getId());
$eqLogics = eqLogic::byType($plugin->getId());
?>

<div class="row row-overflow">
	<div class="col-lg-2 col-md-3 col-sm-4">
		<div class="bs-sidebar">
			<ul id="ul_eqLogic" class="nav nav-list bs-sidenav">
				<a class="btn btn-default eqLogicAction" style="width:100%;margin-top:5px;margin-bottom:5px;" data-action="add">
					<i class="fa fa-plus-circle"></i> {{Ajouter}}
				</a>
				<li class="filter" style="margin-bottom:5px;">
					<input class="filter form-control input-sm" placeholder="{{Rechercher}}" style="width:100%"/>
				</li>
				<?php foreach ($eqLogics as $eqLogic) {
					echo '<li class="cursor li_eqLogic" data-eqLogic_id="' . $eqLogic->getId() . '"';
					if ( !$eqLogic->getIsEnable() ) echo 'style="' . jeedom::getConfiguration('eqLogic:style:noactive') . '"';
					echo '><a>' . $eqLogic->getHumanName(true) . '</a>';
					echo '</li>';
				} ?>
			</ul>
		</div>
	</div>

	<div class="col-lg-10 col-md-9 col-sm-8 eqLogicThumbnailDisplay" style="border-left:solid 1px #EEE;padding-left:25px;">
		<legend>{{Installation}}</legend>
		<legend><i class="fa fa-cog"></i>  {{Gestion}}</legend>
		<div class="eqLogicThumbnailContainer">
			<div class="cursor eqLogicAction" data-action="add" style="text-align:center;background-color:#ffffff;height:120px;margin-bottom:10px;padding:5px;border-radius:2px;width:160px;margin-left:10px;" >
				<i class="fa fa-plus-circle" style="font-size:6em;color:#94ca02;"></i>
				<br>
				<span style="font-size:1.1em;position:relative;top:23px;word-break:break-all;white-space:pre-wrap;word-wrap:break-word;color:#94ca02">{{Ajouter}}</span>
			</div>
			<div class="cursor eqLogicAction" data-action="gotoPluginConf" style="text-align:center;background-color:#ffffff;height:120px;margin-bottom:10px;padding:5px;border-radius:2px;width:160px;margin-left:10px;">
				<i class="fa fa-wrench" style="font-size:6em;color:#767676;"></i>
				<br>
				<span style="font-size:1.1em;position:relative;top:15px;word-break:break-all;white-space:pre-wrap;word-wrap:break-word;color:#767676">{{Configuration}}</span>
			</div>
		</div>
		<br/>
		<legend><i class="fa fa-table"></i>  {{Composants}}</legend>
		<div class="eqLogicThumbnailContainer">
			<?php
			foreach ($eqLogics as $eqLogic) {
				$opacity = ($eqLogic->getIsEnable() && $eqLogic->getIsVisible()) ? '' : jeedom::getConfiguration('eqLogic:style:noactive');
				echo '<div class="eqLogicDisplayCard cursor" data-eqLogic_id="' . $eqLogic->getId() . '" style="text-align:center;background-color:#ffffff;height:200px;margin-bottom:10px;padding:5px;border-radius:2px;width:160px;margin-left:10px;' . $opacity . '" >';
				//echo '<img src="' . $plugin->getPathImgIcon() . '" height="105" width="95" />';
				$zoneId = $eqLogic->getConfiguration(evohome::CONF_ZONE_ID);
				echo '<br/><img src="plugins/evohome/img/' . ($zoneId == evohome::ID_CONSOLE ? 'console-small.png' : ($zoneId == evohome::ID_NO_ZONE ? 'men_at_work.png' : 'hr92-small.png')) . '" />';
				echo "<br>";
				echo '<span style="font-size:1.1em;position:relative;top:15px;word-break:break-all;white-space:pre-wrap;word-wrap:break-word;">' . $eqLogic->getHumanName(true,true) . '</span>';
				echo '</div>';
			}
			?>
		</div>
	</div>

	<div class="col-lg-10 col-md-9 col-sm-8 eqLogic" style="border-left:solid 1px #EEE;padding-left:25px;display:none;">
		<a class="btn btn-success eqLogicAction pull-right" data-action="save"><i class="fa fa-check-circle"></i> {{Sauvegarder}}</a>
		<a class="btn btn-danger eqLogicAction pull-right" data-action="remove"><i class="fa fa-minus-circle"></i> {{Supprimer}}</a>
		<a class="btn btn-default eqLogicAction pull-right" data-action="configure"><i class="fa fa-cogs"></i> {{Configuration avancée}}</a>
		<ul class="nav nav-tabs" role="tablist">
			<li role="presentation"><a href="#" class="eqLogicAction" aria-controls="home" role="tab" data-toggle="tab" data-action="returnToThumbnailDisplay"><i class="fa fa-arrow-circle-left"></i></a></li>
			<li role="presentation" class="active"><a href="#eqlogictab" aria-controls="home" role="tab" data-toggle="tab"><i class="fa fa-tachometer"></i> {{Equipement}}</a></li>
			<li role="presentation"><a href="#commandtab" aria-controls="profile" role="tab" data-toggle="tab"><i class="fa fa-list-alt"></i> {{Commandes}}</a></li>
		</ul>
		<div class="tab-content" style="height:calc(100% - 50px);overflow:auto;overflow-x:hidden;">
			<div role="tabpanel" class="tab-pane active" id="eqlogictab">
				<div class="row">
					<div class="col-sm-6">
						<br/>
						<form class="form-horizontal">
							<fieldset>
								<legend>
									<i class="fa fa-arrow-circle-left eqLogicAction cursor" data-action="returnToThumbnailDisplay"></i> {{General}}
									<!-- <i class="fa fa-cogs eqLogicAction pull-right cursor expertModeVisible" data-action="configure"></i> -->
									<!-- <a class="btn btn-xs btn-default pull-right eqLogicAction" data-action="copy"><i class="fa fa-files-o"></i> Dupliquer</a> -->
								</legend>
								<div class="form-group">
									<label class="col-sm-2 control-label">{{Nom Equipement}}</label>
									<div class="col-sm-8">
										<input type="text" class="eqLogicAttr form-control" data-l1key="id" style="display:none;" />
										<input type="text" class="eqLogicAttr form-control" data-l1key="name" placeholder="{{Nom Equipement Evohome}}"/>
									</div>
								</div>
								<div class="form-group">
									<label class="col-sm-2 control-label" >{{Objet Parent}}</label>
									<div class="col-sm-8">
										<select id="sel_object" class="eqLogicAttr form-control" data-l1key="object_id">
											<option value="">{{Aucune}}</option>
											<?php
											foreach (object::all() as $object) {
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
									<label class="col-sm-3 control-label">{{Zone}}</label>
									<div class="col-sm-8">
										<select class="eqLogicAttr form-control" data-l1key="configuration" data-l2key="zoneId" placeholder="param1">
											<option value="-2">{{Aucune}}</option>
											<option value="-1">{{Console}}</option>
											<?php
											$zones = evohome::getInformationsAllZonesE2();
											if ( $zones != null ) {
												foreach ($zones['zones'] as $zone) {
													echo '<option value="' . $zone['zoneId'] . '">' . $zone['name'] . ' (' . ($zone['temperature'] == null ? '{{indisponible}}' : $zone['temperature']) . ')</option>';
												}
											}
											?>
										</select>
									</div>
								</div>
								<center>
									<img src="core/img/no_image.gif" data-original=".png" id="img_device" class="img-responsive" style="max-height:200px;">
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
					<tbody>
					</tbody>
				</table>
			</div>
		</div>
	</div>
</div>

<script>
<?php 
echo "var _msgShow = '{{Afficher}}';\n";
echo "var _msgHistorize = '{{Historiser}}';\n";
?>
</script>
<?php include_file('desktop', 'evohome', 'js', 'evohome');
include_file('core', 'plugin.template', 'js');
?>
