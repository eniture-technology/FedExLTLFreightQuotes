var fedexLtWhFormId = "#fedexLt-wh-form";
var fedexLtWhEditFormData = '';
require(
    [
        'jquery',
        'Magento_Ui/js/modal/modal',
        'domReady!'
    ],
    function ($, modal) {

        let addWhModal = $('#fedexLt-wh-modal');
        let formId = fedexLtWhFormId;
        let options = {
            type: 'popup',
            modalClass: 'fedexLt-add-wh-modal',
            responsive: true,
            innerScroll: true,
            title: 'Warehouse',
            closeText: 'Close',
            focus: formId + ' #fedexLt-wh-zip',
            buttons: [{
                text: $.mage.__('Save'),
                class: 'en-btn save-wh-ds',
                click: function (data) {
                    var $this = this;
                    var formData = fedexLtGetFormData($, formId);
                    var ajaxUrl = fedexLtAjaxUrl + 'SaveWarehouse/';

                    if ($(formId).valid() && fedexLtZipMilesValid()) {
                        //If form data is unchanged then close the modal and show updated message
                        if (fedexLtWhEditFormData !== '' && fedexLtWhEditFormData === formData) {
                            fedexLtResponseMessage('fedexLt-wh-msg', 'success', 'Warehouse updated successfully.');
                            addWhModal.modal('closeModal');
                        } else {
                            $.ajax({
                                url: ajaxUrl,
                                type: 'POST',
                                data: formData,
                                showLoader: true,
                                success: function (data) {
                                    if (fedexLtWarehouseSaveResSettings(data)) {
                                        addWhModal.modal('closeModal');
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
                fedexLtModalClose(formId, '#', $);
            }
        };

        //Add WH
        $('#fedexLt-add-wh-btn').on('click', function () {
            var popup = modal(options, addWhModal);
            addWhModal.modal('openModal');
        });

        //Edit WH
        $('body').on('click', '.fedexLt-edit-wh', function () {
            var whId = $(this).data("id");
            if (typeof whId !== 'undefined') {
                fedexLtEditWarehouse(whId, fedexLtAjaxUrl);
                setTimeout(function () {
                    var popup = modal(options, addWhModal);
                    addWhModal.modal('openModal');
                }, 500);
            }
        });

        //Delete WH
        $('body').on('click', '.fedexLt-del-wh', function () {
            var whId = $(this).data("id");
            if (typeof whId !== 'undefined') {
                fedexLtDeleteWarehouse(whId, fedexLtAjaxUrl);
            }
        });

        //Add required to Local Delivery Fee if Local Delivery is enabled
        $(formId + ' #enable-local-delivery').on('change', function () {
            if ($(this).is(':checked')) {
                $(formId + ' #ld-fee').addClass('required');
            } else {
                $(formId + ' #ld-fee').removeClass('required');
            }
        });

        //Get data of Zip Code
        $(formId + ' #fedexLt-wh-zip').on('change', function () {
            console.log(fedexLtAjaxUrl);
            var ajaxUrl = fedexLtAjaxUrl + 'FedExLTLPkgOriginAddress/';
            $(formId + ' #wh-origin-city').val('');
            $(formId + ' #wh-origin-state').val('');
            $(formId + ' #wh-origin-country').val('');
            fedexLtGetAddressFromZip(ajaxUrl, this, fedexLtGetAddressResSettings);
            $(formId).validation('clearError');
        });
    }
);


function fedexLtGetAddressResSettings(data)
{
    let id = fedexLtWhFormId;
    if (data.country === 'US' || data.country === 'CA') {
        if (data.postcode_localities === 1) {
            jQuery(id + ' .city-select').show();
            jQuery(id + ' #actname').replaceWith(data.city_option);
            jQuery(id + ' .city-multiselect').replaceWith(data.city_option);
            jQuery(id).on('change', '.city-multiselect', function () {
                var city = jQuery(this).val();
                jQuery(id + ' #wh-origin-city').val(city);
            });
            jQuery(id + " #wh-origin-city").val(data.first_city);
            jQuery(id + " #wh-origin-state").val(data.state);
            jQuery(id + " #wh-origin-country").val(data.country);
            jQuery(id + ' .city-input').hide();
        } else {
            jQuery(id + ' .city-input').show();
            jQuery(id + ' #wh-multi-city').removeAttr('value');
            jQuery(id + ' .city-select').hide();
            jQuery(id + " #wh-origin-city").val(data.city);
            jQuery(id + " #wh-origin-state").val(data.state);
            jQuery(id + " #wh-origin-country").val(data.country);
        }
    } else if (data.msg) {
        fedexLtResponseMessage('fedexLt-wh-modal-msg', 'error', data.msg);
    }
    return true;
}


function fedexLtZipMilesValid()
{
    let id = fedexLtWhFormId;
    var enable_instore_pickup = jQuery(id + " #enable-instore-pickup").is(':checked');
    var enable_local_delivery = jQuery(id + " #enable-local-delivery").is(':checked');
    if (enable_instore_pickup || enable_local_delivery) {
        var instore_within_miles = jQuery(id + " #within-miles").val();
        var instore_postal_code = jQuery(id + " #postcode-match").val();
        var ld_within_miles = jQuery(id + " #ld-within-miles").val();
        var ld_postal_code = jQuery(id + " #ld-postcode-match").val();

        switch (true) {
            case (enable_instore_pickup && (instore_within_miles.length == 0 && instore_postal_code.length == 0)):
                jQuery(id + ' .wh-instore-miles-postal-err').show('slow');
                fedexLtScrollHideMsg(2, '', id + ' #wh-is-heading-left', '.wh-instore-miles-postal-err');
                return false;

            case (enable_local_delivery && (ld_within_miles.length == 0 && ld_postal_code.length == 0)):
                jQuery(id + ' .wh-local-miles-postals-err').show('slow');
                fedexLtScrollHideMsg(2, '', id + ' #wh-ld-heading-left', '.wh-local-miles-postals-err');
                return false;
        }
    }
    return true;
}

function fedexLtWarehouseSaveResSettings(data)
{
    if (data.insert_qry == 1) {
        jQuery('#append-warehouse tr:last').after(
            '<tr id="row_' + data.id + '" data-id="' + data.id + '">' + fedexLtGetRowData(data, 'wh') + '</tr>'
        );
    } else if (data.update_qry == 1) {
        jQuery('tr[id=row_' + data.id + ']').html(fedexLtGetRowData(data, 'wh'));
    } else {
        //to be changed
        fedexLtResponseMessage('fedexLt-wh-modal-msg', 'error', data.msg);
        return false;
    }
    fedexLtResponseMessage('fedexLt-wh-msg', 'success', data.msg);
    return true;
}

/**
 * Edit warehouse
 * @param {type} dataId
 * @param {type} ajaxUrl
 * @returns {Boolean}
 */
function fedexLtEditWarehouse(dataId, ajaxUrl)
{
    ajaxUrl = ajaxUrl + 'EditWarehouse/';
    let parameters = {
        'action': 'edit_warehouse',
        'edit_id': dataId
    };
    fedexLtAjaxRequest(parameters, ajaxUrl, fedexLtWarehouseEditResSettings);
}

function fedexLtWarehouseEditResSettings(data)
{
    if (data.error == 1) {
        fedexLtResponseMessage('fedexLt-wh-msg', 'error', data.msg);
        jQuery('#fedexLt-wh-modal').modal('closeModal');
        return false
    }
    let id = fedexLtWhFormId;
    if (data[0]) {
        jQuery(id + ' #edit-form-id').val(data[0].warehouse_id);
        jQuery(id + ' #fedexLt-wh-zip').val(data[0].zip);
        jQuery(id + ' .city-select').hide();
        jQuery(id + ' .city-input').show();
        jQuery(id + ' #wh-origin-city').val(data[0].city);
        jQuery(id + ' #wh-origin-state').val(data[0].state);
        jQuery(id + ' #wh-origin-country').val(data[0].country);

        if (fedexLtAdvancePlan) {
            // Load instorepikup and local delivery data
            if ((data[0].in_store != null && data[0].in_store != 'null')
                || (data[0].local_delivery != null && data[0].local_delivery != 'null')) {
                fedexLtSetInspAndLdData(data[0], '#');
            }
        }
        fedexLtWhEditFormData = fedexLtGetFormData(jQuery, fedexLtWhFormId);
    }
    return true;
}

/**
 * Delete selected Warehouse
 * @param {int} dataId
 * @param {string} ajaxUrl
 * @returns {boolean}
 */
function fedexLtDeleteWarehouse(dataId, ajaxUrl)
{
    ajaxUrl = ajaxUrl + 'DeleteWarehouse/';
    let parameters = {
        'action': 'delete_warehouse',
        'delete_id': dataId
    };
    fedexLtAjaxRequest(parameters, ajaxUrl, fedexLtWarehouseDeleteResSettings);
    return false;
}

function fedexLtWarehouseDeleteResSettings(data)
{
    if (data.qryResp == 1) {
        jQuery('#row_' + data.deleteID).remove();
        fedexLtAddWarehouseRestriction(data.canAddWh);
    }
    fedexLtResponseMessage('fedexLt-wh-msg', 'success', data.msg);
    //fedexLtScrollHideMsg(1, 'html,body', '.wh-text', '.fedexLt-wh-msg');
    return true;
}

