if (typeof window.genConsoleId == 'undefined') {
    window.genConsoleId = [];
    window.lastFileId = [];
    window.isCurrent = [];
    window._isAdmin = null;
    window._argCodeMode = null;
    window._setModeConfirm = null;
    window._setModeInfoList = null;
    window._cmdSetModeId = [];
    window._cmdSaveId = [];
}

function initVarsConsole(_locId, _id, pIsAdmin, scheduleFileId, pCanRestoreCurrent, taskIsRunning, pTitleSetMode, pCodesAllowed, pEtatCode, pDisplaySetModeConsole, pCmdSetModeId, pArgCodeMode, pSetModeConfirm, pSetModeInfoList, pCmdSaveId, pSaveAs, pSaveInfoListMessage, pSaveReplace, pSaveName, pSaveRemark, pArgFileName, pArgFileRem, pCmdRestoreId, pRestoreConfirm, pRestoreInfoList, pCmdDeleteId, pDeleteConfirm, pDeleteInfoList) {
    genConsoleId[_locId] = _id;
    lastFileId[_id] = -1;
    isCurrent[_id] = false;
    _isAdmin = pIsAdmin;
    _cmdSetModeId[_id] = pCmdSetModeId;
    _argCodeMode = pArgCodeMode;
    _setModeConfirm = pSetModeConfirm;
    _setModeInfoList = pSetModeInfoList;
    _cmdSaveId[_id] = pCmdSaveId;
    $('#md_modal').on('dialogclose', function() {
        lastFileId[_id] = -1;
    });
    $('#scheduleList' + _id).on('change', function() {
        checkConsole(_id, scheduleFileId, pCanRestoreCurrent, taskIsRunning);
    });
    checkConsole(_id, scheduleFileId, pCanRestoreCurrent, taskIsRunning);
    $('.statScope' + _id).on('change', function() {
        var _data = {
            action: 'setStatScope',
            statScope: $('.statScope' + _id).value()
        };
        _data[_argLocId] = _locId;
        $.ajax({
            type: 'POST',
            url: 'plugins/evohome/core/ajax/evohome.ajax.php',
            dataType: 'json',
            data: _data,
            error: function(request, status, error) {
                handleAjaxError(request, status, error);
            }
        });
    });
    $('.showCurrentSchedule' + _id).off().on('click', function() {
        if (lastFileId[_id] != 0) showScheduleCO(_id, 'C', 0);
        else $('#md_modal').dialog('close');
    });
    $('.showSavedSchedule' + _id).off().on('click', function() {
        var fileId = $('#scheduleList' + _id).value();
        if (fileId != 0) {
            if (lastFileId[_id] != fileId) showScheduleCO(_id, 'S', fileId);
            else $('#md_modal').dialog('close');
        }
    });
    if (pDisplaySetModeConsole == '1') {
        $('.setMode' + _id).replaceWith(getModesTable(_id, 1, pCodesAllowed, pEtatCode));
    } else {
        $('.setMode' + _id).on('click', function() {
            if (!checkApiAvailable()) return;
            var dialog = bootbox.prompt({
                title: pTitleSetMode,
                value: '',
                callback: function() {}
            });
            dialog.init(function() {
                $('.bootbox-input-text').replaceWith(getModesTable(_id, 2, pCodesAllowed, pEtatCode) + '<br/>');
                $('.btn-primary').replaceWith('');
            });
        });
        if (!_apiAvailable) setBgColor('.setMode' + _id, 'gray');
    }
    $('.saveSchedule' + _id).on('click', function() {
        if (!_isAdmin) return;
        var selectedFileId = $('#scheduleList' + _id).value();
        var dialog = bootbox.prompt({
            title: pSaveAs,
            value: selectedFileId == 0 ? '' : $('#scheduleList' + _id + ' option:selected').text(),
            callback: function(fileName) {
                if (fileName != null && fileName != '') {
                    var updateFileId = 0;
                    $('#scheduleList' + _id + ' option').each(function() {
                        if (this.text == fileName) updateFileId = this.value;
                    });
                    var comm = $('#idComment').value();
                    if (updateFileId != 0) {
                        bootbox.confirm(getMsg(pSaveReplace, fileName), function(ret2) {
                            if (ret2) save(_id, pSaveInfoListMessage, pArgFileName, fileName, pArgFileRem, comm, updateFileId);
                        });
                    } else {
                        save(_id, pSaveInfoListMessage, pArgFileName, fileName, pArgFileRem, comm, 0);
                    }
                }
            }
        });
        dialog.init(function() {
            if ($(".bootbox-input-text")[0].previousSibling != '<span>' + pSaveName + '</span>') {
                $('<span>' + pSaveName + '</span>').insertBefore(".bootbox-input-text");
                $('<br/><br/><span>' + pSaveRemark + '</span><br/><textarea style="height:80px;width:100%;" id="idComment" />').insertAfter(".bootbox-input-text");
            }
            if (selectedFileId > 0) loadCommentary(_locId, selectedFileId);
        });
        $('.bootbox-input-text').on('input', function() {
            var newFileName = this.value;
            $('#scheduleList' + _id + ' option').each(function() {
                if (this.text == newFileName) loadCommentary(_locId, this.value);
            });
        });
    });
    $('.restoreSchedule' + _id).on('click', function() {
        if ((!isCurrent[_id] || pCanRestoreCurrent == '1') && _isAdmin) {
            if (!_apiAvailable) {
                myAlert("Fonction indisponible");
                return;
            }
            var sl = document.getElementById('scheduleList' + _id);
            bootbox.confirm(getMsg(pRestoreConfirm, sl.options[sl.selectedIndex].text), function(result) {
                if (result) {
                    waitingMessage(getMsg(pRestoreInfoList, sl.options[sl.selectedIndex].text));
                    var _value = {};
                    _value[_argFileId] = $('#scheduleList' + _id).value();
                    jeedom.cmd.execute({
                        id: pCmdRestoreId,
                        notify: true,
                        value: _value
                    });
                }
            });
        }
    });
    $('.removeSchedule' + _id).on('click', function() {
        if (!isCurrent[_id] && _isAdmin) {
            var sl = document.getElementById('scheduleList' + _id);
            bootbox.confirm(getMsg(pDeleteConfirm, sl.options[sl.selectedIndex].text), function(result) {
                if (result) {
                    waitingMessage(getMsg(pDeleteInfoList, sl.options[sl.selectedIndex].text));
                    var _value = {};
                    _value[_argFileId] = sl.options[sl.selectedIndex].value;
                    jeedom.cmd.execute({
                        id: pCmdDeleteId,
                        notify: true,
                        value: _value
                    });
                }
            });
        }
    });
    if (!taskIsRunning) $('#jqueryLoadingDiv').hide();
    else $('#jqueryLoadingDiv').show();
    $('.modal-backdrop').remove();
    $('.bootbox').remove();
}

function checkConsole(id, scheduleFileId, canRestoreCurrent, taskIsRunning) {
    var fileIdSelected = $('#scheduleList' + id).value();
    isCurrent[id] = scheduleFileId == fileIdSelected;
    if (fileIdSelected == 0) $('#scheduleList' + id).prop('disabled', 'disabled');
    setColor('.showSavedScheduleCaption' + id, fileIdSelected == 0 ? 'gray' : 'white');
    if (!_isAdmin) setColor('.saveCaption' + id, 'gray');
    setColor('.restaureCaption' + id, (isCurrent[id] && canRestoreCurrent == '0') || !_isAdmin || !_apiAvailable ? 'gray' : 'white');
    setColor('.deleteCaption' + id, isCurrent[id] || !_isAdmin ? 'gray' : 'white');
}

function loadCommentary(locId, pFileId) {
    var _data = {
        action: 'getCommentary'
    };
    _data[_argLocId] = locId;
    _data[_argFileId] = pFileId;
    $.ajax({
        type: 'POST',
        url: 'plugins/evohome/core/ajax/evohome.ajax.php',
        data: _data,
        dataType: 'json',
        error: function(request, status, error) {
            handleAjaxError(request, status, error);
        },
        success: function(data) {
            if (data.state != 'ok') {
                $('#div_alert').showAlert({
                    message: data.result,
                    level: 'danger'
                });
            } else if (data.result.comment != null) {
                $('#idComment').value(decodeURIComponent(data.result.comment));
            } else {
                $('#idComment').value('');
            }
        }
    });
}

function showScheduleCO(id, type, fileId, mode, displayMode) {
    __generalLastMode = mode == null ? __generalLastMode : mode;
    $('#md_modal').dialog('close');
    $('#md_modal').dialog({
        title: ""
    });
    var cmd = 'index.php?v=d&plugin=evohome&modal=schedule' + __generalLastMode + '&id=' + id + '&' + _argZoneId + '=0&' + _argFileId + '=' + fileId + '&mode=' + __generalLastMode + '&typeSchedule=' + type;
    if (displayMode != null) cmd += '&displayMode=' + displayMode;
    $('#md_modal').load(cmd).dialog('open');
    setBgColor((fileId == 0 ? '.showCurrentSchedule' : '.showSavedSchedule') + id, "rgb(16, 208, 16)");
    lastFileId[id] = fileId;
}

function getModesTable(id, showType, codesAllowed, etatCode) {
    var buttons = '<div style="height:4px;"></div><div style="text-align:-webkit-center;"><table width="100%"';
    if (showType == 1) buttons += ' style="background-color:' + (_apiAvailable ? _evoBackgroundColor : 'gray' + window._imp2) + ';"';
    buttons += '><tr>';
    var _size = showType == 1 ? 24 : 36;
    var bgc = '';
    for (var i = 0; i < 6; i++) {
        if (codesAllowed.indexOf(evoModePairs[i][0]) != -1) {
            buttons += '<td align="center" width="' + (100 / 6) + '%"';
            if (etatCode != evoModePairs[i][0]) buttons += 'style="cursor:pointer;" onclick="setMode(' + id + ',' + showType + ',' + evoModePairs[i][0] + ',\'' + evoModePairs[i][2] + '\');"';
            bgc = etatCode == evoModePairs[i][0] ? 'green' : showType == 1 ? (_apiAvailable ? _evoBackgroundColor : 'gray') : 'lightgray';
            bgc += window._imp2;
            buttons += '><img style="height:' + _size + 'px;width:' + _size + 'px;background-color:' + bgc + ';" src="plugins/evohome/img/' + evoModePairs[i][1] + '.svg"';
            buttons += '/>';
            if (showType == 2) {
                buttons += '<br/><span style="';
                if (etatCode == evoModePairs[i][0]) buttons += 'background-color:green' + window._imp2 + ';color:white' + window._imp2 + ';';
                buttons += '">&nbsp;' + evoModePairs[i][2] + '&nbsp;</span>';
            }
            buttons += '</td>';
        }
    }
    buttons += '</tr></table></div><div style="height:2px;"></div>';
    return buttons;
}

function setMode(id, showType, mode, name) {
    if (!_apiAvailable) {
        myAlert("Fonction indisponible");
        return;
    }
    bootbox.confirm(getMsg(_setModeConfirm, name), function(result) {
        if (result) {
            if (showType == 2) $('.btn-default[data-bb-handler="cancel"]').click();
            waitingMessage(getMsg(_setModeInfoList, name));
            var _value = {};
            _value[_argCodeMode] = mode;
            jeedom.cmd.execute({
                id: _cmdSetModeId[id],
                notify: true,
                value: _value
            });
        }
    });
}

function save(id, message, pArgFileName, fileName, pArgFileRem, comm, fileId) {
    waitingMessage(getMsg(message, fileName));
    var _value = {};
    _value[_argFileId] = fileId;
    _value[pArgFileName] = fileName;
    _value[pArgFileRem] = encodeURIComponent(comm);
    jeedom.cmd.execute({
        id: _cmdSaveId[id],
        notify: false,
        value: _value
    });
}