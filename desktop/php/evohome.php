<?php
if (!isConnect('admin')) {
	throw new Exception('{{_noAccess}}');
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
					<i class="fa fa-plus-circle"></i> {{_add}}
				</a>
				<li class="filter" style="margin-bottom: 5px;">
					<input class="filter form-control input-sm" placeholder="{{_search}}" style="width: 100%"/>
				</li>
				<?php
				foreach ($eqLogics as $eqLogic) {
					$opacity = ($eqLogic->getIsEnable()) ? '' : jeedom::getConfiguration('eqLogic:style:noactive');
					echo '<li class="cursor li_eqLogic" data-eqLogic_id="' . $eqLogic->getId() . '" style="' . $opacity .'"><a>' . $eqLogic->getHumanName(true) . '</a></li>';
				}
				?>
			</ul>
		</div>
	</div>

	<div class="col-lg-10 col-md-9 col-sm-8 eqLogicThumbnailDisplay" style="border-left: solid 1px #EEE; padding-left: 25px;">
		<legend>{{_installation}}</legend>
		<legend><i class="fa fa-cog"></i>  {{_manage}}</legend>
		<div class="eqLogicThumbnailContainer">
			<div class="cursor eqLogicAction" data-action="add" style="text-align: center; background-color : #ffffff; height : 120px;margin-bottom : 10px;padding : 5px;border-radius: 2px;width : 160px;margin-left : 10px;" >
				<i class="fa fa-plus-circle" style="font-size : 6em;color:#94ca02;"></i>
				<br>
				<span style="font-size : 1.1em;position:relative; top : 23px;word-break: break-all;white-space: pre-wrap;word-wrap: break-word;color:#94ca02">{{_add}}</span>
			</div>
			<div class="cursor eqLogicAction" data-action="gotoPluginConf" style="text-align: center; background-color : #ffffff; height : 120px;margin-bottom : 10px;padding : 5px;border-radius: 2px;width : 160px;margin-left : 10px;">
				<i class="fa fa-wrench" style="font-size : 6em;color:#767676;"></i>
				<br>
				<span style="font-size : 1.1em;position:relative; top : 15px;word-break: break-all;white-space: pre-wrap;word-wrap: break-word;color:#767676">{{_configuration}}</span>
			</div>
		</div>
		<legend><i class="fa fa-table"></i>  {{_components}}</legend>
		<div class="eqLogicThumbnailContainer">
			<?php
			foreach ($eqLogics as $eqLogic) {
				$opacity = ($eqLogic->getIsEnable()) ? '' : jeedom::getConfiguration('eqLogic:style:noactive');
				echo '<div class="eqLogicDisplayCard cursor" data-eqLogic_id="' . $eqLogic->getId() . '" style="text-align: center; background-color : #ffffff; height : 200px;margin-bottom : 10px;padding : 5px;border-radius: 2px;width : 160px;margin-left : 10px;' . $opacity . '" >';
				echo '<img src="' . $plugin->getPathImgIcon() . '" height="105" width="95" />';
				echo "<br>";
				echo '<span style="font-size : 1.1em;position:relative; top : 15px;word-break: break-all;white-space: pre-wrap;word-wrap: break-word;">' . $eqLogic->getHumanName(true, true) . '</span>';
				echo '</div>';
			}
			?>
		</div>
	</div>

	<div class="col-lg-10 col-md-9 col-sm-8 eqLogic" style="border-left: solid 1px #EEE; padding-left: 25px;display: none;">
		<a class="btn btn-success eqLogicAction pull-right" data-action="save"><i class="fa fa-check-circle"></i> {{_save}}</a>
		<a class="btn btn-danger eqLogicAction pull-right" data-action="remove"><i class="fa fa-minus-circle"></i> {{_delete}}</a>
		<a class="btn btn-default eqLogicAction pull-right" data-action="configure"><i class="fa fa-cogs"></i> {{_advConf}}</a>
		<ul class="nav nav-tabs" role="tablist">
			<li role="presentation"><a href="#" class="eqLogicAction" aria-controls="home" role="tab" data-toggle="tab" data-action="returnToThumbnailDisplay"><i class="fa fa-arrow-circle-left"></i></a></li>
			<li role="presentation" class="active"><a href="#eqlogictab" aria-controls="home" role="tab" data-toggle="tab"><i class="fa fa-tachometer"></i> {{_equipment}}</a></li>
			<li role="presentation"><a href="#commandtab" aria-controls="profile" role="tab" data-toggle="tab"><i class="fa fa-list-alt"></i> {{_commands}}</a></li>
		</ul>
		<div class="tab-content" style="height:calc(100% - 50px);overflow:auto;overflow-x: hidden;">
			<div role="tabpanel" class="tab-pane active" id="eqlogictab">
				<div class="row">
					<div class="col-sm-6">
						<br/>
						<form class="form-horizontal">
							<fieldset>
								<legend>
									<i class="fa fa-arrow-circle-left eqLogicAction cursor" data-action="returnToThumbnailDisplay"></i> {{_general}}
									<!-- <i class="fa fa-cogs eqLogicAction pull-right cursor expertModeVisible" data-action="configure"></i> -->
									<!-- <a class="btn btn-xs btn-default pull-right eqLogicAction" data-action="copy"><i class="fa fa-files-o"></i> Dupliquer</a> -->
								</legend>
								<div class="form-group">
									<label class="col-sm-2 control-label">{{_equipmentName}}</label>
									<div class="col-sm-8">
										<input type="text" class="eqLogicAttr form-control" data-l1key="id" style="display : none;" />
										<input type="text" class="eqLogicAttr form-control" data-l1key="name" placeholder="{{_equipmentNameEvohome}}"/>
									</div>
								</div>
								<div class="form-group">
									<label class="col-sm-2 control-label" >{{_parentObject}}</label>
									<div class="col-sm-8">
										<select id="sel_object" class="eqLogicAttr form-control" data-l1key="object_id">
											<option value="">{{_noParent}}</option>
											<?php
											foreach (object::all() as $object) {
												echo '<option value="' . $object->getId() . '">' . $object->getName() . '</option>';
											}
											?>
										</select>
									</div>
								</div>
								<div class="form-group">
									<label class="col-sm-2 control-label">{{_categorie}}</label>
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
										<label class="checkbox-inline"><input type="checkbox" class="eqLogicAttr" data-l1key="isEnable" checked/>{{_activate}}</label>
										<label class="checkbox-inline"><input type="checkbox" class="eqLogicAttr" data-l1key="isVisible" checked/>{{_visible}}</label>
									</div>
								</div>
							</fieldset>
						</form>
					</div>
					<div class="col-sm-6">
						<form class="form-horizontal">
							<fieldset>
								<legend>
									<i class="fa fa-info-circle"></i>  {{_informations}}
								</legend>
								<div class="form-group">
									<label class="col-sm-3 control-label">{{_zone}}</label>
									<div class="col-sm-8">
										<select class="eqLogicAttr form-control" data-l1key="configuration" data-l2key="zoneId" placeholder="param1">
											<option value="-2">{{_noZone}}</option>
											<option value="-1">{{_console}}</option>
											<?php
											$zones = evohome::getInformationsAllZonesE2();
											if ( $zones != null ) {
												foreach ($zones['zones'] as $zone) {
													echo '<option value="' . $zone['id'] . '">' . $zone['name'] . ' (' . $zone['temperature'] . ')</option>';
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
							<th style="width : 70px;">Id</th>
							<th>{{_name}}</th>
							<th>{{_type}}</th>
							<th>{{_display}}/{{_historize}}</th>
							<th>{{_action}}</th>
						</tr>
					</thead>
					<tbody>
					</tbody>
				</table>
			</div>
		</div>
	</div>
</div>

<?php include_file('desktop', 'evohome', 'js', 'evohome');?>
<?php include_file('core', 'plugin.template', 'js');?>
