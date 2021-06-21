const fedexLtDsFormId = "#fedexLt-ds-form";
let fedexLtDsEditFormData = '';
require(
    [
        'jquery',
        'Magento_Ui/js/modal/modal',
        'Magento_Ui/js/modal/confirm',
        'domReady!',
    ],
    function ($, modal, confirmation) {
        const addDsModal = $('#fedexLt-ds-modal');
        const options = {
            type: 'popup',
            modalClass: 'fedexLt-add-ds-modal',
            responsive: true,
            innerScroll: true,
            title: 'Drop Ship',
            closeText: 'Close',
            focus: fedexLtDsFormId + ' #fedexLt-ds-nickname',
            buttons: [{
                text: $.mage.__('Save'),
                class: 'en-btn save-ds-ds',
                click: function (data) {
                    var $this = this;
                    var form_data = fedexLtGetFormData($, fedexLtDsFormId);
                    var ajaxUrl = fedexLtDsAjaxUrl + 'SaveDropship/';
                    if ($(fedexLtDsFormId).valid() && fedexLtDsZipMilesValid()) {
                        //If form data is unchanged then close the modal and show updated message
                        if (fedexLtDsEditFormData !== '' && fedexLtDsEditFormData === form_data) {
                            fedexLtResponseMessage('fedexLt-ds-msg', 'success', 'Drop ship updated successfully.');
                            addDsModal.modal('closeModal');
                        } else {
                            $.ajax({
                                url: ajaxUrl,
                                type: 'POST',
                                data: form_data,
                                showLoader: true,
                                success: function (data) {
                                    if (fedexLtDropshipSaveResSettings(data)) {
                                        addDsModal.modal('closeModal');
                                    }
                                },
                                error: function (result) {
                                    console.log('no response !');
                                }
                            });
                        }
                    }
                }
            }],
            keyEventHandlers: {
                tabKey: function () {
                    return;
                },
                /**
                 * Escape key press handler,
                 * close modal window
                 */
                escapeKey: function () {
                    if (this.options.isOpen && this.modal.find(document.activeElement).length ||
                        this.options.isOpen && this.modal[0] === document.activeElement) {
                        this.closeModal();
                    }
                }
            },
            closed: function () {
                fedexLtModalClose(fedexLtDsFormId, '#ds-', $);
            }
        };
        $('body').on('click', '.fedexLt-del-ds', function (event) {
            event.preventDefault();
            confirmation({
                title: 'Fedex LTL Freight Quotes',
                content: 'Warning! If you delete this location, Drop ship location settings will be disabled against products.',
                actions: {
                    always: function () {
                    },
                    confirm: function () {
                        var dataset = event.currentTarget.dataset;
                        fedexLtDeleteDropship(dataset.id, fedexLtDsAjaxUrl);
                    },
                    cancel: function () {
                    }
                }
            });
            return false;
        });
        //Add DS
        $('#fedexLt-add-ds-btn').on('click', function () {
            const popup = modal(options, addDsModal);
            addDsModal.modal('openModal');
        });
        //Edit WH
        $('body').on('click', '.fedexLt-edit-ds', function () {
            var dsId = $(this).data("id");
            if (typeof dsId !== 'undefined') {
                fedexLtEditDropship(dsId, fedexLtDsAjaxUrl);
                setTimeout(function () {
                    const popup = modal(options, addDsModal);
                    addDsModal.modal('openModal');
                }, 500);
            }
        });

        //Add required to Local Delivery Fee if Local Delivery is enabled
        $(fedexLtDsFormId + ' #ds-enable-local-delivery').on('change', function () {
            if ($(this).is(':checked')) {
                $(fedexLtDsFormId + ' #ds-ld-fee').addClass('required');
            } else {
                $(fedexLtDsFormId + ' #ds-ld-fee').removeClass('required');
            }
        });

        //Get data of Zip Code
        $(fedexLtDsFormId + ' #fedexLt-ds-zip').on('change', function () {
            var ajaxUrl = fedexLtAjaxUrl + 'FedExLTLPkgOriginAddress/';
            $(fedexLtDsFormId + ' #ds-city').val('');
            $(fedexLtDsFormId + ' #ds-state').val('');
            $(fedexLtDsFormId + ' #ds-country').val('');
            fedexLtGetAddressFromZip(ajaxUrl, this, fedexLtGetDsAddressResSettings);
            $(fedexLtDsFormId).validation('clearError');
        });
    }
);

/**
 * Set Address from zipCode
 * @param {type} data
 * @returns {Boolean}
 */
function fedexLtGetDsAddressResSettings(data)
{
    let id = fedexLtDsFormId;
    if (data.country === 'US' || data.country === 'CA') {
        var oldNick = jQuery('#fedexLt-ds-nickname').val();
        var newNick = '';
        var zip = jQuery('#fedexLt-ds-zip').val();
        if (data.postcode_localities === 1) {
            jQuery(id + ' .city-select').show();
            jQuery(id + ' #ds-actname').replaceWith(data.city_option);
            jQuery(id + ' .city-multiselect').replaceWith(data.city_option);
            jQuery(id).on('change', '.city-multiselect', function () {
                var city = jQuery(this).val();
                jQuery(id + ' #ds-city').val(city);
                jQuery(id + ' #fedexLt-ds-nickname').val(fedexLtSetDsNickname(oldNick, zip, city));
            });
            jQuery(id + " #ds-city").val(data.first_city);
            jQuery(id + ' #ds-state').val(data.state);
            jQuery(id + ' #ds-country').val(data.country);
            jQuery(id + ' .city-input').hide();
            newNick = fedexLtSetDsNickname(oldNick, zip, data.first_city);
        } else {
            jQuery(id + ' .city-input').show();
            jQuery(id + ' #wh-multi-city').removeAttr('value');
            jQuery(id + ' .city-select').hide();
            jQuery(id + ' #ds-city').val(data.city);
            jQuery(id + ' #ds-state').val(data.state);
            jQuery(id + ' #ds-country').val(data.country);
            newNick = fedexLtSetDsNickname(oldNick, zip, data.city);
        }
        jQuery(id + ' #fedexLt-ds-nickname').val(newNick);
    } else if (data.msg) {
        fedexLtResponseMessage('fedexLt-ds-modal-msg', 'error', data.msg);
    }
    return true;
}


function fedexLtDsZipMilesValid()
{
    let id = fedexLtDsFormId;
    var enable_instore_pickup = jQuery(id + " #ds-enable-instore-pickup").is(':checked');
    var enable_local_delivery = jQuery(id + " #ds-enable-local-delivery").is(':checked');
    if (enable_instore_pickup || enable_local_delivery) {
        var instore_within_miles = jQuery(id + " #ds-within-miles").val();
        var instore_postal_code = jQuery(id + " #ds-postcode-match").val();
        var ld_within_miles = jQuery(id + " #ds-ld-within-miles").val();
        var ld_postal_code = jQuery(id + " #ds-ld-postcode-match").val();

        switch (true) {
            case (enable_instore_pickup && (instore_within_miles.length == 0 && instore_postal_code.length == 0)):
                jQuery(id + ' .ds-instore-miles-postal-err').show('slow');
                fedexLtScrollHideMsg(2, '', id + ' #ds-is-heading-left', '.ds-instore-miles-postal-err');
                return false;

            case (enable_local_delivery && (ld_within_miles.length == 0 && ld_postal_code.length == 0)):
                jQuery(id + ' .ds-local-miles-postals-err').show('slow');
                fedexLtScrollHideMsg(2, '', id + ' #ds-ld-heading-left', '.ds-local-miles-postals-err');
                return false;
        }
    }
    return true;
}


function fedexLtDropshipSaveResSettings(data)
{
    if (data.insert_qry == 1) {
        jQuery('#append-dropship tr:last').after(
            '<tr id="row_' + data.id + '" data-id="' + data.id + '">' +
            '<td>' + data.nickname + '</td>' +
            fedexLtGetRowData(data, 'ds') + '</tr>'
        );
    } else if (data.update_qry == 1) {
        jQuery('tr[id=row_' + data.id + ']').html('<td>' + data.nickname + '</td>' + fedexLtGetRowData(data, 'ds'));
    } else {
        fedexLtResponseMessage('fedexLt-ds-modal-msg', 'error', data.msg);
        return false;
    }
    fedexLtResponseMessage('fedexLt-ds-msg', 'success', data.msg);
    return true;
}

function fedexLtEditDropship(dataId, ajaxUrl)
{
    ajaxUrl = ajaxUrl + 'EditDropship/';
    const parameters = {
        'action': 'edit_dropship',
        'edit_id': dataId
    };

    fedexLtAjaxRequest(parameters, ajaxUrl, fedexLtDropshipEditResSettings);
    return false;
}

function fedexLtDropshipEditResSettings(data)
{
    let id = fedexLtDsFormId;
    if (data[0]) {
        jQuery(id + ' #ds-edit-form-id').val(data[0].warehouse_id);
        jQuery(id + ' #fedexLt-ds-zip').val(data[0].zip);
        jQuery(id + ' #fedexLt-ds-nickname').val(data[0].nickname);
        jQuery(id + ' .city-select').hide();
        jQuery(id + ' .city-input').show();
        jQuery(id + ' #ds-city').val(data[0].city);
        jQuery(id + ' #ds-state').val(data[0].state);
        jQuery(id + ' #ds-country').val(data[0].country);

        if (fedexLtAdvancePlan) {
            // Load instore pickup and local delivery data
            if ((data[0].in_store != null && data[0].in_store != 'null')
                || (data[0].local_delivery != null && data[0].local_delivery != 'null')) {
                fedexLtSetInspAndLdData(data[0], '#ds-');
                //fedexLtSetInspAndLdData(data[0], '#ds-');
            }
        }

        fedexLtDsEditFormData = fedexLtGetFormData(jQuery, fedexLtDsFormId);
    }
    return true;
}

function fedexLtDeleteDropship(deleteid, ajaxUrl)
{
    ajaxUrl = ajaxUrl + 'DeleteDropship/';
    let parameters = {
        'action': 'delete_dropship',
        'delete_id': deleteid
    };
    fedexLtAjaxRequest(parameters, ajaxUrl, fedexLtDropshipDeleteResSettings);
    return false;
}

function fedexLtDropshipDeleteResSettings(data)
{
    if (data.qryResp == 1) {
        jQuery('#row_' + data.deleteID).remove();
    }
    fedexLtResponseMessage('fedexLt-ds-msg', 'success', data.msg);
    return true;
}

function fedexLtSetDsNickname(oldNick, zip, city)
{
    let nickName = '';
    let curNick = 'DS_' + zip + '_' + city;
    let pattern = /DS_[0-9 a-z A-Z]+_[a-z A-Z]*/;
    let regex = new RegExp(pattern, 'g');
    if (oldNick !== '') {
        nickName = regex.test(oldNick) ? curNick : oldNick;
    }
    return nickName;
}
