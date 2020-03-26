require([
    'jquery',
    'jquery/ui',
    'jquery/validate',
    'mage/translate'
], function ($) {
    'use strict';
    $.validator.addMethod(
        "validate-decimal",
        function (value, element) {
            var validator = this;
            var validationMsg = '';
            var result = true;
            var discount = 'discountPercent';
            var handling = 'hndlngFee';
            var hoat     = 'holdAtTerminalFee';

            //check if length is greater then return Length Error
            if (value.length > 7) {
                validationMsg = "Please enter less than or equal to 7 characters.";
                result = false;
            }

            if (result) {
                if ((element.id).indexOf(discount) != -1) {
                    validationMsg = "Promotional discount format should be like 10 or 60.5, negative value greater than 100 is not allowed. Only 2 digits are allowed after decimal point.";

                    //If value is -ve it should not be more than 100
                    if (value < 0 && value < -100) {
                        result = false;
                    }
                } else if ((element.id).indexOf(handling) != -1) {
                    validationMsg = "Handling fee format should be like 100.20 or 10 and only 2 digits are allowed after decimal point.";
                } else if ((element.id).indexOf(hoat) != -1) {
                    validationMsg = "Hold At Terminal fee format should be like 100.20 or 10 and only 2 digits are allowed after decimal point.";
                }
            }
            if (result) {
                result = validateDecimal(value);
            }
            validator.returnMsg = $.mage.__(validationMsg);
            return result;
        },
        function () {
            return this.returnMsg;
        }
    );

    function validateDecimal(value)
    {
        var pattern=/^\-?\d*(\.\d{0,2})?$/;
        var regex = new RegExp(pattern, 'g');
        return regex.test(value)
    }
});