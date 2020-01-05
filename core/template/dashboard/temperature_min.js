if (typeof window._msgAdjustConsigneUnavailable == 'undefined') {
    window._msgAdjustConsigneUnavailable = null;
    window.canReset = [];
    window.widgetTitle = [];
    window.adjAvailable = [];
    window.msgEnforceConsigne = [];
    window.spScheduled = [];
    window.spScheduledUntil = [];
    window.spCurrent = [];
    window.adjRunning = [];
    window.adjustLow = [];
    window.adjustHigh = [];
    window.adjustStep = [];
    window.adjustPrepared = [];
    window.cmdSetConsigneId = [];
    window.initialSrc = [];
    window.initialTitle = [];
    window.C2BG = {
        '25': '#f21f1f',
        '22': '#ff5b1a',
        '19': '#fa9e2d',
        '16': '#2e9985'
    };
    window.thTimeout = [];
    window.settingTempOpened = false;
    window.adjustHMByChoice = null;
    window.isAdjustV1 = null;
    window.lblAdjTHTitle1 = null;
    window.lblAdjTHTitle2 = null;
    window.lblUntilCurrentSchedule = null;
    window.lblAdjTHPermanent = null;
    window.lblAdjTHUntil = null;
    window.lblAdjTHUntilEndOfDay = null;
}

function initVarsTH(_locId, _id, _zoneId) {
    setColor('.showSavedScheduleCaption' + _id, getScheduleListId(_locId) == 0 ? 'gray' : 'white');
    $('.showCurrentScheduleC' + _zoneId).on('click', function() {
        _showScheduleTH(_locId, _id, _zoneId, 'C');
    });
    $('.showCurrentScheduleS' + _zoneId).on('click', function() {
        if (getScheduleListId(_locId) > 0) _showScheduleTH(_locId, _id, _zoneId, 'S');
    });
    evoRegisterHover(_id, 'TH');
}

function getScheduleListId(locId) {
    return typeof genConsoleId == 'undefined' || genConsoleId[locId] == 'undefined' || $('#scheduleList' + genConsoleId[locId]).length == 0 ? 0 : $('#scheduleList' + genConsoleId[locId]).value();
}

function _showScheduleTH(locId, id, zoneId, type) {
    if (getBgColor('.showCurrentSchedule' + type + zoneId) != "rgb(16, 208, 16)") {
        showScheduleTH(locId, id, zoneId, type);
    } else {
        $('#md_modal').dialog('close');
    }
}

function showScheduleTH(locId, id, zoneId, type, mode) {
    __generalLastMode = mode == null ? __generalLastMode : mode;
    $('#md_modal').dialog('close');
    $('#md_modal').dialog({
        title: "",
        height: 'auto',
        width: (__generalLastMode == _showHorizontal ? Math.min(1000, jQuery(window).width() - 16) : 700),
        position: {
            my: "center top",
            at: "center top+50",
            of: window
        }
    });
    var fileId = type == 'C' ? 0 : $('#scheduleList' + genConsoleId[locId] + ' option:selected').value();
    $('#md_modal').load('index.php?v=d&plugin=evohome&modal=schedule' + __generalLastMode + '&id=' + id + '&' + _argZoneId + '=' + zoneId + '&' + _argFileId + '=' + fileId + '&edit=0&mode=' + __generalLastMode + '&typeSchedule=' + type).dialog('open');
    setBgColor('.showCurrentSchedule' + type + zoneId, "rgb(16, 208, 16)");
}

function initVarsAC(_taskIsRunning, pAdjAvailable, pMsgAdjustConsigneUnavailable, pMsgEnforceConsigne, zoneId, pCurrentConsigne, pCurrentConsigneUntil, pConsigne, pAdjustLow, pAdjustHigh, pAdjustStep, pCmdSetConsigneId, pCanReset, pIsAdjustV1, pWidgetTitle, pLblAdjTHTitle1, pLblAdjTHTitle2, pLblAdjTHUntilCurrentSchedule, pLblAdjTHPermanent, pLblAdjTHUntil, pLblAdjTHUntilEndOfDay) {
    if (pCmdSetConsigneId.substr(0, 1) == "#") {
        if (pIsAdjustV1) {
            $('.zidAdjustV1Reset' + zoneId).hide();
            $('.zidAdjustV1Plus' + zoneId).hide();
            $('.zidAdjustV1Minus' + zoneId).hide();
            $('.zidAdjustV1Lowest' + zoneId).hide();
        } else {
            $('.zidAdjustV2Reset' + zoneId).hide();
            $('.zidAdjustV2' + zoneId).hide();
        }
    } else {
        if (pIsAdjustV1) {
            $('.zidAdjustV1Reset' + zoneId).on('click', function() {
                resetConsigneVx(zoneId);
            });
            $('.zidAdjustV1Minus' + zoneId).on('click', function() {
                adjustV1(zoneId, -1);
            });
            $('.zidAdjustV1Plus' + zoneId).on('click', function() {
                adjustV1(zoneId, 1);
            });
            $('.zidAdjustV1Lowest' + zoneId).on('click', function() {
                adjustV1Lowest(zoneId);
            });
        } else {
            $('.zidAdjustV2Reset' + zoneId).on('click', function() {
                resetConsigneVx(zoneId);
            });
            $('.zidAdjustV2' + zoneId).on('click', function() {
                adjustPanelV2(zoneId);
            });
        }
        _msgAdjustConsigneUnavailable = pMsgAdjustConsigneUnavailable;
        widgetTitle[zoneId] = pWidgetTitle;
        adjAvailable[zoneId] = pAdjAvailable == 'true';
        msgEnforceConsigne[zoneId] = pMsgEnforceConsigne;
        spScheduled[zoneId] = parseFloat(pCurrentConsigne);
        spScheduledUntil[zoneId] = pCurrentConsigneUntil;
        spCurrent[zoneId] = parseFloat(pConsigne);
        adjustLow[zoneId] = parseFloat(pAdjustLow);
        adjustHigh[zoneId] = parseFloat(pAdjustHigh);
        adjustStep[zoneId] = parseFloat(pAdjustStep);
        cmdSetConsigneId[zoneId] = parseInt(pCmdSetConsigneId);
        initialSrc[zoneId] = $('.zidSpImgVx' + zoneId).attr('src');
        initialTitle[zoneId] = $('.zidSpDisplayImgVx' + zoneId).attr('title');
        refreshAdjButtonsVx(zoneId, _apiAvailable && adjAvailable[zoneId], pCanReset, pConsigne);
        adjRunning[zoneId] = false;
    }
    window.isAdjustV1 = pIsAdjustV1;
    window.lblAdjTHTitle1 = pLblAdjTHTitle1;
    window.lblAdjTHTitle2 = pLblAdjTHTitle2;
    window.lblAdjTHUntilCurrentSchedule = pLblAdjTHUntilCurrentSchedule;
    window.lblAdjTHPermanent = pLblAdjTHPermanent;
    window.lblAdjTHUntil = pLblAdjTHUntil;
    window.lblAdjTHUntilEndOfDay = pLblAdjTHUntilEndOfDay;
    if (_taskIsRunning) showSettingTempVx(zoneId, 0);
}

function refreshAdjButtonsVx(zoneId, pCanAdjust, pCanReset, pConsigne) {
    if (!pCanAdjust) adjRunning[zoneId] = true;
    canReset[zoneId] = true;
    var resetIconColor = _apiAvailable && adjAvailable[zoneId] && spScheduled[zoneId] != 0 ? 'white' : 'gray';
    var colorLow = !pCanAdjust || pConsigne == adjustLow[zoneId] ? 'gray' : 'white';
    var colorHigh = !pCanAdjust || pConsigne == adjustHigh[zoneId] ? 'gray' : 'white';
    if (window.isAdjustV1) {
        setColor('.zidAdjustV1ResetIcon' + zoneId, resetIconColor);
        setColor('.zidAdjustV1LowestIcon' + zoneId, colorLow);
        setColor('.zidAdjustV1MinusIcon' + zoneId, colorLow);
        setColor('.zidAdjustV1PlusIcon' + zoneId, colorHigh);
    } else {
        setColor('.zidAdjustV2ResetIcon' + zoneId, resetIconColor);
    }
}

function checkAdjAvailabilityVx(zoneId) {
    if (!checkApiAvailable()) return false;
    if (!adjAvailable[zoneId]) {
        myAlert(_msgAdjustConsigneUnavailable);
        return false;
    }
    return !adjRunning[zoneId];
}

function adjustV1(zoneId, step) {
    if (!checkAdjAvailabilityVx(zoneId)) return;
    var sp = parseFloat($('.zidConsigneVx' + zoneId)[0].innerHTML.replace('°', ''));
    var nStep = step == -1 && sp > adjustLow[zoneId] ? -adjustStep[zoneId] : step == 1 && sp < adjustHigh[zoneId] ? adjustStep[zoneId] : 0;
    if (nStep != 0) {
        prepareAdjustV1(zoneId, Math.round(100 * (sp + nStep), 2) / 100);
    }
}

function adjustV1Lowest(zoneId) {
    if (!checkAdjAvailabilityVx(zoneId)) return;
    prepareAdjustV1(zoneId, adjustLow[zoneId]);
}

function adjustPanelV2(zoneId) {
    if (!checkAdjAvailabilityVx(zoneId)) return;
    settingTempVx(zoneId, -1, -1);
}

function resetConsigneVx(zoneId) {
    if (!checkAdjAvailabilityVx(zoneId)) return;
    if (canReset[zoneId]) prepareAdjustV1(zoneId, 0);
}

function prepareAdjustV1(zoneId, sp) {
    var spx = sp == 0 ? spScheduled[zoneId] : sp;
    var sx = spx != spCurrent[zoneId] ? "..." : "";
    $('.zidConsigneVx' + zoneId)[0].innerHTML = spx + "°" + sx;
    refreshAdjButtonsVx(zoneId, true, sp != 0 && sp != spScheduled[zoneId], spx);
    if (sp == 0 || sp == spScheduled[zoneId]) {
        $('.zidSpDisplayImgVx' + zoneId).hide();
        $('.zidConsigneTypeUntilVx' + zoneId).hide();
    } else if (sp != 0 && sp != spCurrent[zoneId]) {
        $('.zidSpDisplayImgVx' + zoneId).hide();
        $('.zidConsigneTypeUntilVx' + zoneId).hide();
    } else {
        resetIconAndTitleVx(zoneId);
    }
    adjustBackgroundVx(zoneId, spx);
    if (adjustPrepared[zoneId] != null) {
        clearTimeout(adjustPrepared[zoneId]);
        settingTempOpened = false;
        adjustPrepared[zoneId] = null;
    }
    if (spx != spCurrent[zoneId]) prepareSettingTempV1(zoneId, sp, spx);
}

function prepareSettingTempV1(zoneId, sp, spx) {
    if (!settingTempOpened) {
        settingTempOpened = true;
        adjustPrepared[zoneId] = setTimeout(function() {
            settingTempVx(zoneId, sp, spx);
        }, 4000);
    } else {
        adjustPrepared[zoneId] = setTimeout(function() {
            prepareSettingTempV1(zoneId, sp, spx);
        }, 500);
    }
}

function settingTempVx(zoneId, sp, spx) {
    if (window.isAdjustV1 || sp == 0) {
        settingTempOpened = false;
        adjustPrepared[zoneId] = null;
        return setTempVx(zoneId, sp, spx, null);
    }
    var hour = new Date().getHours();
    var mn = new Date().getMinutes() + 4;
    if (mn > 60) {
        mn = 0;
        hour++;
        if (hour == 24) hour = 0;
    }
    if (mn % 10 != 0) mn += 10 - mn % 10;
    if (mn == 60) {
        mn = 0;
        hour = hour == 23 ? 0 : hour + 1;
    }
    var hmFirst = toHHMM(hour, mn);
    var _options = [{
        text: "&nbsp;&nbsp;" + window.lblAdjTHPermanent,
        value: 1
    }];
    if (hmFirst != spScheduledUntil[zoneId] && spScheduledUntil[zoneId] != "00:00") {
        _options.push({
            text: "&nbsp;&nbsp;" + getMsg(window.lblAdjTHUntilCurrentSchedule, spScheduledUntil[zoneId]),
            value: 2
        });
    }
    var hmOptions = "";
    do {
        var hm = toHHMM(hour, mn);
        hmOptions += "<option " + (hm == spScheduledUntil[zoneId] ? "style='background-color:green" + _imp + ";' " : "") + "value='" + hm + "'>" + hm + "</option>";
        mn += 10;
        if (mn == 60) {
            mn = 0;
            hour = hour == 23 ? 0 : hour + 1;
        }
    } while (hour != 0);
    _options.push({
        text: "&nbsp;&nbsp;" + window.lblAdjTHUntil + "&nbsp;&nbsp;<select class='hourMinutes'>" + hmOptions + "</select>",
        value: 3
    });
    _options.push({
        text: "&nbsp;&nbsp;" + window.lblAdjTHUntilEndOfDay,
        value: 4
    });
    window.adjustHMByChoice = [null, spScheduledUntil[zoneId], "dummy", "00:00"];
    if ($('.bootbox-prompt').length != 0) {
        $('.bootbox-prompt').remove();
    }
    var dialog = bootbox.prompt({
        title: getMsg(window.lblAdjTHTitle1, window.widgetTitle[zoneId]),
        inputType: 'checkbox',
        inputOptions: _options,
        callback: function(choice) {
            if (choice == null) {
                refreshAdjButtonsVx(zoneId, true, spCurrent[zoneId] != spScheduled[zoneId], spCurrent[zoneId]);
            } else {
                var until = choice != 3 ? window.adjustHMByChoice[choice - 1] : $('.hourMinutes').val();
                spx = sp = parseFloat($('.zidConsigneV2')[0].innerHTML.replace('°', ''));
                setTempVx(zoneId, sp, spx, until);
            }
            adjustPrepared[zoneId] = null;
            settingTempOpened = false;
        }
    });
    dialog.init(function() {
        $('.bootbox-input-checkbox').attr('type', 'radio');
        $('.bootbox-input-checkbox').attr('name', 'idChoice');
        $('input[name="idChoice"]:first').prop('checked', true);
        $('input[name="idChoice"]').on('click', function() {
            adjustRightInfosV2(zoneId)
        });
        $('.hourMinutes').on('click', function() {
            $('input[name="idChoice"][value="3"]').prop('checked', true);
            adjustRightInfosV2(zoneId);
        });
        if (sp == -1) {
            var adjBar = '<div style="text-align:center;">';
            adjBar += '<a class="zidAdjustV2Lowest btn btn-sm TH2d5v2" style="background-color:' + evoCmdBackgroundColor + ' !important;">';
            adjBar += '<span class="zidAdjustV2LowestIcon" style="font-weight:100;">min</span>';
            adjBar += '<span style="display:block;line-height:6px;font-size:11px;">(' + adjustLow[zoneId] + '°)</span>';
            adjBar += '</a><a class="zidAdjustV2Minus btn btn-sm TH2d4v2" style="background-color:' + evoCmdBackgroundColor + ' !important;">';
            adjBar += '<span class="zidAdjustV2MinusIcon fa fa-minus-circle"/>';
            adjBar += '</a><div class="zidBackConsigneV2" style="display:inline-block;vertical-align:top;height:39px;width:140px;text-align:center">';
            adjBar += '<span class="zidConsigneV2" style="cursor:default;color:white;font-size:22px;font-family:\'Open Sans\',sans-serif;line-height:40px;">' + spCurrent[zoneId] + '°</span>';
            adjBar += '<img id="zidSpImgV2" class="TH2d3s2i" style="margin-left:4px;display:none;" src="dummy"/>';
            adjBar += '<span class="zidConsigneV2TypeUntil" style="color:white;margin-left:6px;"></span>';
            adjBar += '</div><a class="zidAdjustV2Plus btn btn-sm TH2d4v2" style="background-color:' + evoCmdBackgroundColor + ' !important;">';
            adjBar += '<span class="zidAdjustV2PlusIcon fa fa-plus-circle"/>';
            adjBar += '</a><a class="zidAdjustV2Highest btn btn-sm TH2d5v2" style="background-color:' + evoCmdBackgroundColor + ' !important;">';
            adjBar += '<span class="zidAdjustV2HighestIcon" style="font-weight:100;">max</span>';
            adjBar += '<span style="display:block;line-height:6px;font-size:11px;">(' + adjustHigh[zoneId] + '°)</span>';
            adjBar += '</a>';
            adjBar += '</div>';
            $(adjBar).insertBefore(".bootbox-form:first-child");
            adjustBackgroundVx(0, spCurrent[zoneId]);
            adjustRightInfosV2(zoneId);
            adjustOKV2(zoneId, spCurrent[zoneId]);
            $('.zidAdjustV2Lowest').on('click', function() {
                adjustXestV2(zoneId, -1);
            });
            $('.zidAdjustV2Minus').on('click', function() {
                adjustV2(zoneId, -1);
            });
            $('.zidAdjustV2Plus').on('click', function() {
                adjustV2(zoneId, 1);
            });
            $('.zidAdjustV2Highest').on('click', function() {
                adjustXestV2(zoneId, 1);
            });
        }
    });
}

function adjustV2(zoneId, step) {
    var sp = parseFloat($('.zidConsigneV2')[0].innerHTML.replace('°', ''));
    var nStep = step == -1 && sp > adjustLow[zoneId] ? -adjustStep[zoneId] : step == 1 && sp < adjustHigh[zoneId] ? adjustStep[zoneId] : 0;
    if (nStep != 0) {
        prepareAdjustV2(zoneId, Math.round(100 * (sp + nStep), 2) / 100);
    }
}

function adjustXestV2(zoneId, mode) {
    prepareAdjustV2(zoneId, mode == -1 ? adjustLow[zoneId] : adjustHigh[zoneId]);
}

function prepareAdjustV2(zoneId, sp) {
    $('.zidConsigneV2')[0].innerHTML = sp + "°";
    refreshAdjButtonsVx(zoneId, true, sp != spScheduled[zoneId], sp);
    adjustBackgroundVx(0, sp);
    adjustRightInfosV2(zoneId);
    adjustOKV2(zoneId, sp);
}

function adjustOKV2(zoneId, sp) {}

function adjustRightInfosV2(zoneId) {
    var choice = $('input[name="idChoice"]:checked')[0].value;
    var sp = parseFloat($('.zidConsigneV2')[0].innerHTML.replace('°', ''));
    var until = '';
    if (sp == spCurrent[zoneId]) {
        $('.zidSpImgV2').hide();
    } else {
        var img = choice == '1' ? 'override-active.png' : 'temp-override.svg';
        $('.zidSpImgV2').attr('src', 'plugins/evohome/img/' + img);
        $('.zidSpImgV2').show();
        until = choice != '3' ? window.adjustHMByChoice[choice - 1] : $('.hourMinutes').val();
    }
    $('.zidConsigneV2TypeUntil').html(until);
}

function toHHMM(h, m) {
    return (h < 10 ? "0" : "") + h + ":" + (m == 0 ? "00" : m);
}

function resetIconAndTitleVx(zoneId) {
    if (initialTitle[zoneId] == '') $('.zidSpDisplayImgVx' + zoneId).hide();
    else $('.zidSpDisplayImgVx' + zoneId).show();
    $('.zidConsigneTypeUntilVx' + zoneId).show();
}

function setTempVx(zoneId, sp, spx, until) {
    consignesData = 'manuel#' + zoneId + '#' + sp + '#' + spx + '#' + (until == null ? "null" : until);
    alert(consignesData);
    jeedom.cmd.execute({
        id: cmdSetConsigneId[zoneId],
        notify: true,
        value: {
            'selectEvoHome': consignesData
        }
    });
    $('.zidConsigneVx' + zoneId)[0].innerHTML = spx;
    showSettingTempVx(zoneId, spx);
}

function showSettingTempVx(zoneId, pConsigne) {
    $('.zidSpDisplayImgVx' + zoneId).hide();
    $('.zidSetTempSpinnerVx' + zoneId).show();
    setBgColor('.zidBackConsigneVx' + zoneId, 'gray');
    refreshAdjButtonsVx(zoneId, false, false, pConsigne);
}

function adjustBackgroundVx(zoneId, sp) {
    var bg = '#247eb2';
    for (ref in C2BG)
        if (sp >= parseInt(ref)) bg = C2BG[ref];
    if (zoneId != 0) {
        setBgColor('.zidBackConsigneVx' + zoneId, bg);
    } else {
        setBgColor('.zidBackConsigneV2', bg);
    }
}