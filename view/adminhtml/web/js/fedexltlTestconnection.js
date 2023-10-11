require(['jquery', 'domReady!'], function ($) {
    $('#fedexLtTestConnBtn').click(function () {
        if ($('#config-edit-form').valid()) {
            let ajaxURL = $(this).attr('connAjaxUrl');
            fedexLTLTestConnectionAjaxCall($, ajaxURL);
        }
        return false;
    });

    let inputCommonId   = '#fedexltlconnsettings_first_';

    $(inputCommonId+ "fedexLtlBillingAddress").attr("placeholder", "Billing Address");
    $(inputCommonId+ "fedexLtlPhysicalAddress").attr("placeholder", "Physical Address");
    $(inputCommonId+ "fedexLtlBillingCity").attr("placeholder", "City");
    $(inputCommonId+ "fedexLtlPhysicalCity").attr("placeholder", "City");
    $(inputCommonId+ "fedexLtlBillingState").attr("placeholder", "State e.g.CA");
    $(inputCommonId+ "fedexLtlPhysicalState").attr("placeholder", "State e.g.CA");
    $(inputCommonId+ "fedexLtlBillingZip").attr("placeholder", "Zip Code");
    $(inputCommonId+ "fedexLtlPhysicalZip").attr("placeholder", "Zip Code");
    $(inputCommonId+ "fedexLtlBillingCountry").attr("placeholder", "Country e.g.US");
    $(inputCommonId+ "fedexLtlPhysicalCountry").attr("placeholder", "Country e.g.US");

    let address  = $(inputCommonId + "fedexLtlPhysicalAddress").val();
    let city     = $(inputCommonId + "fedexLtlPhysicalCity").val();
    let state    = $(inputCommonId + "fedexLtlPhysicalState").val();
    let zip      = $(inputCommonId + "fedexLtlPhysicalZip").val();
    let country  = $(inputCommonId + "fedexLtlPhysicalCountry").val();

    $(inputCommonId + "fedexLtlCopyBillAddress_copyBillAdd").change(function () {
        if (this.checked) {
            address = $(inputCommonId + "fedexLtlBillingAddress").val();
            city    = $(inputCommonId + "fedexLtlBillingCity").val();
            state   = $(inputCommonId + "fedexLtlBillingState").val();
            zip     = $(inputCommonId + "fedexLtlBillingZip").val();
            country = $(inputCommonId + "fedexLtlBillingCountry").val();
        }
        $(inputCommonId + "fedexLtlPhysicalAddress").val(address);
        $(inputCommonId + "fedexLtlPhysicalCity").val(city);
        $(inputCommonId + "fedexLtlPhysicalState").val(state);
        $(inputCommonId + "fedexLtlPhysicalZip").val(zip);
        $(inputCommonId + "fedexLtlPhysicalCountry").val(country);
    });

});

/**
 * Test connection ajax call
 * @param {type} ajaxURL
 * @returns {Success or Error}
 */
function fedexLTLTestConnectionAjaxCall($, ajaxURL) {
    let inputCommonId = '#fedexltlconnsettings_first_';

    let credentials = {
        AccountNumber               : $(inputCommonId + "fedexLtlAccountNumber").val(),
        MeterNumber                 : $(inputCommonId + "fedexLtlMeterNumber").val(),
        password                    : $(inputCommonId + "fedexLtlPassword").val(),
        key                         : $(inputCommonId + "fedexLtlAuthenticationKey").val(),
        shippingChargesAccount      : $(inputCommonId + "fedexLtlShipperAccountNumber").val(),
        billingLineAddress          : $(inputCommonId + "fedexLtlBillingAddress").val(),
        billingCountry              : $(inputCommonId + "fedexLtlBillingCountry").val(),
        billingCity                 : $(inputCommonId + "fedexLtlBillingCity").val(),
        billingState                : $(inputCommonId + "fedexLtlBillingState").val(),
        billingZip                  : $(inputCommonId + "fedexLtlBillingZip").val(),
        physicalAddress             : $(inputCommonId + "fedexLtlPhysicalAddress").val(),
        physicalCountry             : $(inputCommonId + "fedexLtlPhysicalCountry").val(),
        physicalCity                : $(inputCommonId + "fedexLtlPhysicalCity").val(),
        physicalStateOrProvinceCode : $(inputCommonId + "fedexLtlPhysicalState").val(),
        physicalPostalCode          : $(inputCommonId + "fedexLtlPhysicalZip").val(),
        thirdPartyAccount           : $(inputCommonId + "fedexLtlThirdPartyAccountNumber").val(),
        licence_key                 : $(inputCommonId + "fedexLtlLicenseKey").val(),
    };

    fedexLtAjaxRequest(credentials, ajaxURL, fedexLTLConnectSuccessFunction);
}

/**
 *
 * @param {type} data
 * @returns {undefined}
 */
function fedexLTLConnectSuccessFunction(data) {
    let styleClass = data.error ? 'error' : 'success';
    let message = data.success ? data.success : data.error;
    fedexLtResponseMessage('fedexLt-con-msg', styleClass, message);
}

/**
 * Plan Refresh ajax call
 * @param {object} $
 * @param {string} ajaxURL
 * @returns {function}
 */
function fedexLTLPlanRefresh(e){
    let ajaxURL = e.getAttribute('planRefAjaxUrl');
    let parameters = {};
    fedexLtAjaxRequest(parameters, ajaxURL, fedexLTLPlanRefreshResponse);
}

/**
 * Handle response
 * @param {object} data
 * @returns {void}
 */
function fedexLTLPlanRefreshResponse(data){}
