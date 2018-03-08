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

$("#table_cmd").sortable({axis: "y", cursor: "move", items: ".cmd", placeholder: "ui-state-highlight", tolerance: "intersect", forcePlaceholderSize: true});

$('.eqLogicAttr[data-l1key=configuration][data-l2key=zoneId]').on('change', function() {
	var imgName = 'evohome.png';
	if ($('.li_eqLogic.active').attr('data-eqlogic_id') != '') {
		imgName = $(this).value() == -1 ? 'console.png' : 'hr92.png';
	}
	$('#img_device').attr('src','plugins/evohome/img/' + imgName);
});

/*
 * Fonction pour l'ajout de commande, appellé automatiquement par plugin.evohome
 */
function addCmdToTable(_cmd) {
	if (!isset(_cmd)) {
		var _cmd = {configuration: {}};
	}
	if (!isset(_cmd.configuration)) {
		_cmd.configuration = {};
	}
	var tr = '<tr class="cmd" data-cmd_id="' + init(_cmd.id) + '">';
	// ID
	tr += '<td>';
	tr += '<span class="cmdAttr" data-l1key="id" ></span>';
	tr += '</td>';
	// NOM
	tr += '<td>';
	tr += '<span class="cmdAttr" data-l1key="name" style="width : 140px;">{{Nom}}</span>';
	tr += '</td>';
	// TYPE
	tr += '<td>';
	tr += '<span class="cmdAttr" data-l1key="type">' + init(_cmd.type) + '</span>';
	tr += '<br/><span class="cmdAttr" data-l1key="subType">' + init(_cmd.subType) + '</span>';
	tr += '</td>';

	// AFFICHER/HISTORISER
	tr += '<td style="width: 200px;">';
	if ( _cmd.configuration['canBeVisible'] == '1' ) {
		tr += '<span><input type="checkbox" class="cmdAttr" data-size="mini" data-l1key="isVisible" checked/> {{Afficher}}</span><br/>';
	}
	if ( _cmd.configuration['canBeHistorize'] == '1' ) {
		tr += '<span><input type="checkbox" class="cmdAttr" data-l1key="isHistorized"/> {{Historiser}}</span><br/>';
	}
	tr += '</td>';

	// ACTIONS
	tr += '<td>';
	if (is_numeric(_cmd.id)) {
		tr += '<a class="btn btn-default btn-xs cmdAction" data-action="configure"><i class="fa fa-cogs"></i></a> ';
		tr += '<a class="btn btn-default btn-xs cmdAction" data-action="test"><i class="fa fa-rss"></i> {{Tester}}</a>';
	}
	//tr += '<i class="fa fa-minus-circle pull-right cmdAction cursor" data-action="remove"></i>';
	tr += '</td>';

	tr += '</tr>';

	$('#table_cmd tbody').append(tr);
	$('#table_cmd tbody tr:last').setValues(_cmd, '.cmdAttr');
	if (isset(_cmd.type)) {
		$('#table_cmd tbody tr:last .cmdAttr[data-l1key=type]').value(init(_cmd.type));
	}

	jeedom.cmd.changeType($('#table_cmd tbody tr:last'), init(_cmd.subType));
}
