if (typeof window._apiAvailable == 'undefined') {
    window._apiAvailable = false;
    window.__generalLastMode = null;
    window.evoCmdBackgroundColor = null;
    window.tlrTimeout = [];
    window._msgApiUnavailable = null;
    window._showHorizontal = null;
    window._argLocId = null;
    window._argFileId = null;
    window._argZoneId = null;
    window._evoBackgroundColor = null;
    window._imp = 'important';
    window._imp2 = ' !important';
}

function evoShowMsgInfo(evoMsgInfo) {
    if (evoMsgInfo != '') {
        var level = 'danger';
        if (evoMsgInfo.substring(0, 1) == '1') {
            evoMsgInfo = evoMsgInfo.substring(1);
            level = 'success';
        }
        $('#div_alert').showAlert({
            message: evoMsgInfo,
            level: level
        });
    }
}

function evoRegisterHover(_id, _part) {
    $('.hover' + _part + 'ShowA' + _id).off().on('click', function() {
        evoFlipHover(_id, _part);
    });
    $('.hover' + _part + 'ShowB' + _id).off().on('click', function() {
        evoHideHover(_id, _part);
    });
}

function evoFlipHover(_id, part) {
    if ($('.hover' + part + 'ShowB' + _id).is(":visible")) {
        hideHover(_id, part);
    } else {
        $('.hover' + part + 'ShowB' + _id).show();
        tlrTimeout[_id + part] = setTimeout(function() {
            $('.hover' + part + 'ShowB' + _id).hide();
        }, 4000);
    }
}

function evoHideHover(_id, part) {
    $('.hover' + part + 'ShowB' + _id).hide();
    clearTimeout(tlrTimeout[_id + part]);
}

function initVarsCommon(pEvoBackgroundColor, pEvoCmdBackgroundColor, pApiAvailable, pMsgApiUnavailable, pEvoDefaultShowingScheduleMode, pShowHorizontal, pArgLocId, pArgZoneId, pArgFileId) {
    _apiAvailable = pApiAvailable;
    _msgApiUnavailable = pMsgApiUnavailable;
    _showHorizontal = pShowHorizontal;
    _argLocId = pArgLocId;
    _argZoneId = pArgZoneId;
    _argFileId = pArgFileId;
    _evoBackgroundColor = pEvoBackgroundColor;
    if (window.__generalLastMode == null) {
        window.__generalLastMode = pEvoDefaultShowingScheduleMode;
        window.evoCmdBackgroundColor = pEvoCmdBackgroundColor;
        $('head').append('<link rel="stylesheet" type="text/css" href="plugins/evohome/core/template/dashboard/dashboard.css">');
        var _style = "<style>" + ".hoverTLRShow { display:none;padding:2px;position:absolute;left:50px;cursor:default;z-index:10;font-size:12px;white-space:nowrap;" + "background-color:" + pEvoBackgroundColor + ";color:black;border:solid 1px black; }" + ".hoverTH .hoverTHShow { display:none;z-index:10;position:absolute;left:50px;top:-10px;width:320px;" + "text-align:left;font-size:12px;background-color:" + pEvoBackgroundColor + "; }" + ".TH2d1 { border-radius:3px;background-color:" + pEvoCmdBackgroundColor + "; }" + ".TH2d2 { border-radius:3px;background-color:" + pEvoCmdBackgroundColor + "; }" + ".btn.TH2d4 { display:block;padding-left:4px;padding-right:4px;padding-top:0px;padding-bottom:0px;" + "height:15px;min-width:18px !important;font-size:8px;line-height:15px;border-radius:2px;background-color:" + pEvoCmdBackgroundColor + " !important; }" + ".btn.TH2d5 { display:block;padding-left:2px;padding-right:2px;padding-top:0px;padding-bottom:0px;" + "height:15px;min-width:18px !important;font-size:8px;line-height:15px;border-radius:2px;background-color:" + pEvoCmdBackgroundColor + " !important; }" + "</style>";
        $('head').append(_style);
    }
}

function checkApiAvailable() {
    if (!_apiAvailable) {
        myAlert(_msgApiUnavailable);
        return false;
    }
    return true;
}
$('#md_modal').on('dialogclose', function() {
    restoreCmdBgdColor();
});

function restoreCmdBgdColor() {
    setBgColor('.showCS', evoCmdBackgroundColor);
}

function waitingMessage(text) {
    bootbox.dialog({
        message: "<div class='text-center'>" + text + "...</div>",
        closeButton: false
    });
    $('#md_modal').dialog('close');
    $('#jqueryLoadingDiv').show();
}

function myAlert(text) {
    bootbox.alert({
        message: text,
        closeButton: false
    });
}

function myConfirm(text) {
    return confirm(text);
}

function getMsg(txt, args) {
    if (!is_array(args)) return txt.replace("{0}", args);
    for (var i = 0; i < args.length; i++) txt = txt.replace("{" + i + "}", args[i]);
    return txt;
}

function setColor(objName, color) {
    $(objName).each(function() {
        this.style.removeProperty('color');
        this.style.setProperty('color', color, window._imp);
    });
}

function getBgColor(objName) {
    var color = $(objName).css('background-color');
    return color;
}

function setBgColor(objName, color) {
    $(objName).each(function() {
        this.style.removeProperty('background-color');
        this.style.setProperty('background-color', color, window._imp);
    });
}