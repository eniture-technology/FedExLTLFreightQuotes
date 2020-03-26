/**
 * Document load function
 * @type type
 */

require([
    'jquery',
    'jquery/validate',
    'domReady!'
], function ($) {

    $('.bootstrap-tagsinput input').bind('keyup keydown',function(event) {
        fedexLtValidateAlphaNumOnly($, this);
    });

    $('#fedexLtlQuoteSetting_third span, #fedexltlconnsettings_first span').attr('data-config-scope', '');

    $.validator.addMethod(
        'validate-fedexLt-decimal-limit-2',
        function (value) {
            return !!(fedexLtValidateDecimal($, value, 2));
        },
        'Maximum 2 digits allowed after decimal point.'
    );
    $.validator.addMethod(
        'validate-fedexLt-decimal-limit-3',
        function (value) {
            return !!(fedexLtValidateDecimal($, value, 3));
        }, 'Maximum 3 digits allowed after decimal point.'
    );
    
    $('#fedexLtlQuoteSetting_third_hndlngFee').attr('title', 'Handling Fee / Markup');

    $('#fedexLtlQuoteSetting_third_liftGateDlvry').on('change', function () {
        fedexLtChangeLiftgateOption('#fedexLtlQuoteSetting_third_OfferLiftgateAsAnOption', this.value);
        $('#fedexLtlQuoteSetting_third_RADforLiftgate').val('no');
    });

    $('#fedexLtlQuoteSetting_third_OfferLiftgateAsAnOption').on('change', function () {
        fedexLtChangeLiftgateOption('#fedexLtlQuoteSetting_third_liftGateDlvry', this.value);
    });

    $('#fedexLtlQuoteSetting_third_RADforLiftgate').on('change', function () {
        fedexLtChangeLiftgateOption('#fedexLtlQuoteSetting_third_liftGateDlvry', (this.value == 'yes') ? '1' : '0');
    });
});

function fedexLtValidateAlphaNumOnly($, element){
    var value = $(element);
    value.val(value.val().replace(/[^a-z0-9]/g,''));
}

function fedexLtGetAddressFromZip(ajaxUrl, $this, callfunction) {
    const zipCode = $this.value;
    if (zipCode === '') {
        return false;
    }
    const parameters = {'origin_zip': zipCode};

    fedexLtAjaxRequest(parameters, ajaxUrl, callfunction);
}

function fedexLtCurrentPlanNote($, planMsg, carrierDiv) {
    let divAfter = '<div class="message message-notice notice fedexLt-plan-note"><div data-ui-id="messages-message-notice">' + planMsg + '</div></div>';
    fedexLtNotesToggleHandling($, divAfter, '.fedexLt-plan-note', carrierDiv);
}

function fedexLtNotesToggleHandling($, divAfter, className, carrierDiv) {
    setTimeout(function () {
        if ($(carrierDiv).attr('class') === 'open') {
            $(carrierDiv).after(divAfter);
        }
    }, 1000);
    $(carrierDiv).click(function () {
        if ($(carrierDiv).attr('class') === 'open') {
            $(carrierDiv).after(divAfter);
        } else if ($(className).length) {
            $(className).remove();
        }
    });
}

function fedexLtChangeLiftgateOption(selectId, optionVal) {
    if (optionVal == 1) {
        jQuery(selectId).val(0);
    }
}

/**
 * @param canAddWh
 */
function fedexLtAddWarehouseRestriction(canAddWh) {
    switch (canAddWh) {
        case 0:
            jQuery("#append-warehouse").find("tr").removeClass('inactiveLink');
            jQuery('.add-wh-btn').addClass('inactiveLink');
            if (jQuery(".required-plan-msg").length == 0) {
                jQuery('.add-wh-btn').after('<a href="https://eniture.com/magento2-fedex-ltl-freight/" target="_blank" class="required-plan-msg">Standard Plan required</a>');
            }
            jQuery("#append-warehouse").find("tr:gt(1)").addClass('inactiveLink');
            break;
        case 1:
            jQuery('#fedexLt-add-wh-btn').removeClass('inactiveLink');
            jQuery('.required-plan-msg').remove();
            jQuery("#append-warehouse").find("tr").removeClass('inactiveLink');
            break;
        default:
            break;
    }
}

/**
 * call for warehouse ajax requests
 * @param {array} parameters
 * @param {string} ajaxUrl
 * @param {string} responseFunction
 * @returns {function}
 */
function fedexLtAjaxRequest(parameters, ajaxUrl, responseFunction) {
    new Ajax.Request(ajaxUrl, {
        method: 'POST',
        parameters: parameters,
        onSuccess: function (response) {
            var json = response.responseText;
            var data = JSON.parse(json);
            var callbackRes = responseFunction(data);
            return callbackRes;
        }
    });
}

/**
 * Restrict Quote Settings Fields
 * @param {string} qRestriction
 */
function fedexLtPlanQuoteRestriction(qRestriction) {
    var quoteSecRowID = "#row_fedexLtlQuoteSetting_third_";
    var quoteSecID = "#fedexLtlQuoteSetting_third_";
    var parsedData = JSON.parse(qRestriction);

    if (parsedData['advance']) {
        jQuery('' + quoteSecRowID + 'HoldAtTerminal').before('<tr><td><label><span data-config-scope=""></span></label></td><td class="value"><a href="https://eniture.com/magento2-fedex-ltl-freight/" target="_blank" class="required-plan-msg adv-plan-err">Advance Plan required</a></td><td class=""></td></tr>');
        fedexLtDisabledFieldsLoop(parsedData['advance'], quoteSecID);
    }

}

function fedexLtDisabledFieldsLoop(dataArr, quoteSecID) {
    jQuery.each(dataArr, function (index, value) {
        jQuery(quoteSecID + value).attr('disabled', 'disabled');
    });
}


function fedexLtGetRowData(data, loc) {
    return '<td>' + data.origin_city + '</td>' +
        '<td>' + data.origin_state + '</td>' +
        '<td>' + data.origin_zip + '</td>' +
        '<td>' + data.origin_country + '</td>' +
        '<td><a href="javascript:;" data-id="' + data.id + '" title="Edit" class="fedexLt-edit-' + loc + '">Edit</a>' +
        ' | ' +
        '<a href="javascript:;" data-id="' + data.id + '" title="Delete" class="fedexLt-del-' + loc + '">Delete</a>' +
        '</td>';
}

//This function serialize complete form data
function fedexLtGetFormData($, formId) {
    // To initialize the Disabled inputs
    var disabled = $(formId).find(':input:disabled').removeAttr('disabled');
    var formData = $(formId).serialize();
    disabled.attr('disabled', 'disabled');
    var addData = '';
    $(formId + ' input[type=checkbox]').each(function () {
        if (!$(this).is(":checked")) {
            addData += '&' + $(this).attr('name') + '=';
        }
    });
    return formData + addData;
}


/*
* @identifierElem (will be the id or class name)
* @elemType (will be the type of identifier whether it an id or an class ) id = 1, class = 0
* @msgClass (magento style class) [success, error, info, warning]
* @msg (this will be the message which you want to print)
* */
function fedexLtResponseMessage(identifierId, msgClass, msg) {
    identifierId = '#' + identifierId;
    let finalClass = 'message message-';
    switch (msgClass) {
        case 'success':
            finalClass += 'success success';
            break;
        case 'info':
            finalClass += 'info info';
            break;
        case 'error':
            finalClass += 'error error';
            break;
        default:
            finalClass += 'warning warning';
            break;
    }
    jQuery(identifierId).addClass(finalClass);
    jQuery(identifierId).text(msg).show('slow');
    setTimeout(function () {
        jQuery(identifierId).hide();
        jQuery(identifierId).removeClass(finalClass);
    }, 5000);
}


function fedexLtModalClose(formId, ele, $) {
    $(formId).validation('clearError');
    $(formId).trigger("reset");
    $($(formId + " .bootstrap-tagsinput").find("span[data-role=remove]")).trigger("click");
    $(formId + ' ' + ele + 'ld-fee').removeClass('required');
    $(ele + 'edit-form-id').val('');
    $('.city-select').hide();
    $('.city-input').show();
}

function fedexLtSetInspAndLdData(data, eleid) {
    const inStore = JSON.parse(data.in_store);
    const localdel = JSON.parse(data.local_delivery);
    //Filling form data
    if (inStore != null && inStore != 'null') {
        inStore.enable_store_pickup == 1 ? jQuery(eleid + 'enable-instore-pickup').prop('checked', true) : '';
        jQuery(eleid + 'within-miles').val(inStore.miles_store_pickup);
        jQuery(eleid + 'postcode-match').tagsinput('add', inStore.match_postal_store_pickup);
        jQuery(eleid + 'checkout-descp').val(inStore.checkout_desc_store_pickup);
        if (inStore.suppress_other == 1) {
            jQuery(eleid + 'ld-sup-rates').prop('checked', true);
        }
    }

    if (localdel != null && localdel != 'null') {
        if (localdel.enable_local_delivery == 1) {
            jQuery(eleid + 'enable-local-delivery').prop('checked', true);
            jQuery(eleid + 'ld-fee').addClass('required');
        }
        jQuery(eleid + 'ld-within-miles').val(localdel.miles_local_delivery);
        jQuery(eleid + 'ld-postcode-match').tagsinput('add', localdel.match_postal_local_delivery);
        jQuery(eleid + 'ld-checkout-descp').val(localdel.checkout_desc_local_delivery);
        jQuery(eleid + 'ld-fee').val(localdel.fee_local_delivery);
        if (localdel.suppress_other == 1) {
            jQuery(eleid + 'ld-sup-rates').prop('checked', true);
        }
    }
}

/*
* Hide message
 */
function fedexLtScrollHideMsg(scrollType, scrollEle, scrollTo, hideEle) {

    if (scrollType == 1) {
        jQuery(scrollEle).animate({scrollTop: jQuery(scrollTo).offset().top - 170});
    } else if (scrollType == 2) {
        jQuery(scrollTo)[0].scrollIntoView({behavior: "smooth"});
    }
    setTimeout(function () {
        jQuery(hideEle).hide('slow');
    }, 5000);
}

function fedexLtValidateDecimal($, value, limit) {
    let pattern;
    switch (limit) {
        case 4:
            pattern = /^-?\d*(\.\d{0,4})?$/;
            break;
        case 3:
            pattern = /^-?\d*(\.\d{0,3})?$/;
            break;
        default:
            pattern = /^-?\d*(\.\d{0,2})?$/;
            break;
    }
    const regex = new RegExp(pattern, 'g');
    return regex.test(value);
}
