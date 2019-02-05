/**
 * WooCommerce eMailPlatform Plugin
 */
var WC_Emailplatform = function ($) {

    var $enabled;
    var $apiToken;
    var $apiUsername;
    var $accountLoadingIndicator;
    var $mainList;
    var $firstname;
    var $lastname;
    //var $displayOptIn;
    var $occurs;
    var $optInLabel;
    var $optInCheckboxDefault;
    var $optInCheckboxLocation;
    var $doubleOptIn;
    
    var $save_button;

    var namespace = 'wc_emailplatform';

    return {
        init: init,
        checkApiToken: checkApiToken
    };

    function init() {

        initHandles();
        initAccount();
        initHandlers();

    } //end function init

    function initHandles() {
        // Capture jQuery handles to elements
        $enabled = $('#' + namespace_prefixed('enabled'));
        $apiToken = $('#' + namespace_prefixed('api_token'));
        $apiUsername = $('#' + namespace_prefixed('api_username'));
        $mainList = $('#' + namespace_prefixed('list'));
        $firstname = $('#' + namespace_prefixed('firstname'));
        $lastname = $('#' + namespace_prefixed('lastname'));
        //$displayOptIn = $('#' + namespace_prefixed('display_opt_in'));
        $occurs = $('#' + namespace_prefixed('occurs'));
        $optInLabel = $('#' + namespace_prefixed('opt_in_label'));
        $optInCheckboxDefault = $('#' + namespace_prefixed('opt_in_checkbox_default_status'));
        $optInCheckboxLocation = $('#' + namespace_prefixed('opt_in_checkbox_display_location'));
        $doubleOptIn = $('#' + namespace_prefixed('double_opt_in'));
        
        $save_button = $('p.submit .woocommerce-save-button');
    }

    function initHandlers() {
        
        toggleAllSettings('hide');
        
        if($apiToken.val() != '' && $apiUsername.val() != ''){
            start_check($apiToken.val(), $apiUsername.val());
        }
        
        $apiUsername.change(function () {
            if($apiToken.val() != ''){
                checkApiToken($apiToken.val(), $apiUsername.val());
            }
        });
        
        $apiToken.change(function () {
            if($apiUsername.val() != ''){
                checkApiToken($apiToken.val(), $apiUsername.val());
            }
        });
        
        $mainList.change(function () {
            toggleAllSettings('after_list');
            $firstname.val('0');
            $lastname.val('0');
            saveChangeToContinue();
        });
        
        $apiToken.on('paste cut', function () {
            // Short pause to wait for paste to complete
            setTimeout(function () {
                $apiToken.change();
                $apiToken.blur();
            }, 100);
        });
        
        $apiUsername.on('paste cut', function () {
            // Short pause to wait for paste to complete
            setTimeout(function () {
                $apiUsername.change();
                $apiUsername.blur();
            }, 100);
        });
        

//        $optInLabel.closest('tr').hide();
//        $optInCheckboxDefault.closest('tr').hide();
//        $optInCheckboxLocation.closest('tr').hide();
//        $doubleOptIn.closest('tr').hide();
//        $displayOptIn.change(function () {
//            if ('' === $apiToken.val())
//                return;
//
//            switch ($displayOptIn.val()) {
//                case 'no':
//                    $optInLabel.closest('tr').fadeOut();
//                    $optInCheckboxDefault.closest('tr').fadeOut();
//                    $optInCheckboxLocation.closest('tr').fadeOut();
//                    $doubleOptIn.closest('tr').fadeIn();
//                    break;
//                case 'yes':
//                    $optInLabel.closest('tr').fadeIn();
//                    $optInCheckboxDefault.closest('tr').fadeIn();
//                    $optInCheckboxLocation.closest('tr').fadeIn();
//                    $doubleOptIn.closest('tr').fadeIn();
//                    break;
//            }
//        }).change();

    } //end function initHandlers

    function initAccount() {
        $accountLoadingIndicator = $('<div id="wc_emailplatform_loading_account" class="emailplatform-woocommerce-loading"><span id="emailplatform_woocommerce_account_indicator" class="emailplatform-woocommerce-loading-indicator"></span></div>');
        $accountLoadingIndicator2 = $('<div id="wc_emailplatform_loading_account" class="emailplatform-woocommerce-loading"><span id="emailplatform_woocommerce_account_indicator2" class="emailplatform-woocommerce-loading-indicator"></span></div>');
        $apiToken.after($accountLoadingIndicator.hide());
        $apiUsername.after($accountLoadingIndicator2.hide());

    } //end function initAccount

    function checkApiToken(apiToken, apiUsername) {

        /**
         * Check API Credentials
         **/
        
        $accountLoadingIndicator.show();
        $accountIndicator = $accountLoadingIndicator.children().first();
        $accountIndicator.removeClass('success').removeClass('error');
        $accountIndicator.addClass('loading');
        $accountIndicator.html('&nbsp;' + WC_Emailplatform_Messages.connecting_to_emailplatform);
        
        $accountLoadingIndicator2.show();
        $accountIndicator2 = $accountLoadingIndicator2.children().first();
        $accountIndicator2.removeClass('success').removeClass('error');
        $accountIndicator2.addClass('loading');
        $accountIndicator2.html('&nbsp;' + WC_Emailplatform_Messages.connecting_to_emailplatform);
        
        $.post(
                ajaxurl,
                {
                    'action': '' + namespace_prefixed('test_emailplatform'),
                    'data': {'api_token': apiToken, 'api_username': apiUsername}
                },
                function (response) {
                    console.log(response);
                    $accountIndicator.removeClass('loading');
                    $accountIndicator2.removeClass('loading');
                    var result = [];

                    try {
                        result = $.parseJSON(response);
                    } catch (err) {
                        console.error(err);
                        $accountIndicator.addClass('error');
                        $accountIndicator.html('&nbsp;' + WC_Emailplatform_Messages.error_loading_account);
                        
                        $accountIndicator2.addClass('error');
                        $accountIndicator2.html('&nbsp;' + WC_Emailplatform_Messages.error_loading_account);
                        
                        toggleAllSettings('hide');
                        
                        return;
                    }

                    if (result.error) {
                        $accountIndicator.addClass('error');
                        $accountIndicator.html(result.error);
                        
                        $accountIndicator2.addClass('error');
                        $accountIndicator2.html(result.error);
                        
                        toggleAllSettings('hide');
                        
                        return;
                    }

                    $accountIndicator.addClass('success');
                    $accountIndicator.html('');
                    
                    $accountIndicator2.addClass('success');
                    $accountIndicator2.html('');
                    
                    saveChangeToContinue();
                    
                }
        );


    } //end function checkApiToken
    
    function start_check(apiToken, apiUsername){
        
        $.post(
                ajaxurl,
                {
                    'action': '' + namespace_prefixed('test_emailplatform'),
                    'data': {'api_token': apiToken, 'api_username': apiUsername}
                },
                function (response) {
                    var result = [];

                    try {
                        result = $.parseJSON(response);
                    } catch (err) {
                        toggleAllSettings('hide');
                        return;
                    }

                    if (result.error) {
                        toggleAllSettings('hide');
                        return;
                    }

                    $mainList.closest('tr').show();
                    
                }
        );

        if($mainList.val() != 0){
            toggleAllSettings('show');
        }
        
    }
    
    function saveChangeToContinue(){
        $save_button.html('Save changes to continue');
        $save_button.css('background', 'green');
    }
    

    function toggleAllSettings(show_hide) {
        if (show_hide == 'show') {
            $enabled.closest('tr').show();
            $mainList.closest('tr').show();
            $firstname.closest('tr').show();
            $lastname.closest('tr').show();
            $occurs.closest('tr').show();
            $optInLabel.closest('tr').show();
            $optInCheckboxDefault.closest('tr').show();
            $optInCheckboxLocation.closest('tr').show();
            $doubleOptIn.closest('tr').show();
        } else if(show_hide == 'after_list') {
            
            $mainList.closest('tr').nextAll('tr').fadeOut();
            
        } else {
            $enabled.closest('tr').hide();
            $mainList.closest('tr').hide();
            $firstname.closest('tr').hide();
            $lastname.closest('tr').hide();
            $occurs.closest('tr').hide();
            $optInLabel.closest('tr').hide();
            $optInCheckboxDefault.closest('tr').hide();
            $optInCheckboxLocation.closest('tr').hide();
            $doubleOptIn.closest('tr').hide();
        }
    }

    function namespace_prefixed(suffix) {
        return namespace + '_' + suffix;
    }

}(jQuery);