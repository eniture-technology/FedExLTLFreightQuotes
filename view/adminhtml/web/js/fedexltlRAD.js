/**
 * Document load function
 * @type type
 */

require(['jquery', 'domReady!'], function ($) {
    if ($("#suspend-rad-use").length > 0 && $("#suspend-rad-use").is(":disabled") == false) {
        disablealwaysresidentialFedexLtl();
        if (($('#suspend-rad-use:checkbox:checked').length) > 0) {
            $("#fedexLtlQuoteSetting_third_residentialDlvry").prop({disabled: false});
            $("#fedexLtlQuoteSetting_third_RADforLiftgate").val('no');
            $("#fedexLtlQuoteSetting_third_RADforLiftgate").prop({disabled: true});
        } else {
            $("#fedexLtlQuoteSetting_third_residentialDlvry").prop({disabled: true});
            $("#fedexLtlQuoteSetting_third_RADforLiftgate").prop({disabled: false});
        }
    } else if ($("#suspend-rad-use").length > 0 && $("#suspend-rad-use").is(":disabled") == true) {
        $("#fedexLtlQuoteSetting_third_residentialDlvry").prop({disabled: false});
        $("#fedexLtlQuoteSetting_third_RADforLiftgate").prop({disabled: true});
    }

    /**
     * windows onload
     */
    $(window).on('load', function () {
        if ($("#suspend-rad-use").length > 0 && $("#suspend-rad-use").is(":disabled") == false) {
            if (!isdisabled) {
                if (($('#suspend-rad-use:checkbox:checked').length) > 0) {
                    $("#fedexLtlQuoteSetting_third_residentialDlvry").prop({disabled: false});
                    $("#fedexLtlQuoteSetting_third_RADforLiftgate").val('no');
                    $("#fedexLtlQuoteSetting_third_RADforLiftgate").prop({disabled: true});
                } else {
                    $("#fedexLtlQuoteSetting_third_residentialDlvry").prop({disabled: true});
                    $("#fedexLtlQuoteSetting_third_RADforLiftgate").prop({disabled: false});
                }
            }
        } else if ($("#suspend-rad-use").length > 0 && $("#suspend-rad-use").is(":disabled") == true) {
            $("#fedexLtlQuoteSetting_third_residentialDlvry").prop({disabled: false});
            $("#fedexLtlQuoteSetting_third_RADforLiftgate").prop({disabled: true});
        }
    });
});

/**
 *
 * @return {undefined}
 */
function disablealwaysresidentialFedexLtl() {
    jQuery("#suspend-rad-use").on('click', function () {
        if (this.checked) {
            jQuery("#fedexLtlQuoteSetting_third_residentialDlvry").prop({disabled: false});
            jQuery("#fedexLtlQuoteSetting_third_RADforLiftgate").prop({disabled: true});
        } else {
            jQuery("#fedexLtlQuoteSetting_third_residentialDlvry").prop({disabled: true});
            jQuery("#fedexLtlQuoteSetting_third_RADforLiftgate").prop({disabled: false});
        }
    });
}
