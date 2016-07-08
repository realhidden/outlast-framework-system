/**
 * Popup campaign object
 **/
define('system/js/ui/popup-campaign', ["../ofw-jquery"], function() {

    return function PopupCampaign(options){
        /** Options **/
        var defaultOptions = {
            url: null,     // The controller that displays the popup HTML (in ofw.alert)
            selector: null,       // OR you can use this instead of url to simple removeClass('hide') on the popup campaign div
            timeDelay: 1000,                   // 25 seconds delay before the popup is shown. You should use localStorage for the start time, so that the timer is preserved across page views.
            cookieName: 'popupcampaign',        // Optional, defaults to 'popupcampaign' - The name of the cookie (or localstorage key?) that stores the number of times this user has seen this item. Only needed if you have several per page.
            cookieExpiryDays: 90,               // Optional, defaults to 90 - The number of days after which the cookie / localstorage expires.
            showCount: 1,                       // Optional, defaults to 1 - The number of times this visitor sees the popup campaign before it is no longer shown again (until the cookie expires).
            showAgainAfterDays: 3,              // Optional, defaults to 3 - The number of days after which a visitor should again see the popup (only relevant if showCount > 1)
            openButton: null,      // Optional, defaults to null - If set, a click event will be added to this selector that triggers openPopup()
            closeButton: null      // Optional, defaults to null - If set, a click event will be added to this selector that triggers closePopup()

        };

        var myOptions = {};

        /** Private properties **/
        var closeCount = 0;
        var maxCloseCount = 0;
        var popupEnabled = true;


        /** Private API **/

        /**
         * Object init
         */
        var init = function(){
            // Merge default options
            myOptions = $.extend(true, {}, defaultOptions, options);

            if(checkCookie(myOptions.cookieName+'_closecount')){
                closeCount = checkCookie(myOptions.cookieName+'_closecount');
            }

            // if openButton is set, open popup on button click
            if(myOptions.openButton != null){
                $(myOptions.openButton).click(function(e){
                   createPopup();
                });
            }
            // if openButton is null, open popup after seconds defined in timeDelay parameter
            else{
                setTimeout(function(){
                    createPopup();
                }, myOptions.timeDelay);
            }
        };

        /**
         * Create and open popup
         * @returns {*}
         */
        var createPopup = function(){
            console.log('Createpopup running');
            // check cookies, localstorage, showCount
            if(checkPopup() || !popupEnabled) return;
            // popup campaign called without a controller
            if(myOptions.url == null && myOptions.selector == null){
                return console.error('Popup campaign called without url and selector. Check the documentation and define the url or selector parameter.');
            }

            // a controller was defined
            if(myOptions.url != null){
                ofw.ajax.alert(myOptions.url, function(){
                    onPopupClose();
                });
            }

            // a selector was defined
            if(myOptions.url == null && myOptions.selector != null){
                $(myOptions.selector).removeClass('hide');
            }
        };

        /**
         * Check cookie and localstorage
         * @returns boolean true if everything is OK
         */
        var checkPopup = function(){
            var cookieSet = checkCookie(myOptions.cookieName);
            var showCountCookieCheck = checkCookie(myOptions.cookieName + '_closecount');
            var localStorageSet = checkLocalStorage(myOptions.cookieName);
            var showCountStorageSet = checkLocalStorage(myOptions.cookieName + '_closecount');
            if(cookieSet){
                if(showCountCookieCheck < myOptions.showCount) return false;
                return true;
            }
            else if(localStorageSet){
                if(showCountStorageSet < myOptions.showCount) return false;
                return true;
            }
            return false;
        };

        /**
         * Check cookie
         * @param name String cookie name
         * @returns {boolean}
         */
        var checkCookie = function(name){
            // get all cookies
            var cookies = document.cookie.split(';');
            for(var i = 0; i <cookies.length; i++) {
                var c = cookies[i];
                while (c.charAt(0)==' ') {
                    c = c.substring(1);
                }
                if (c.indexOf(name) == 0) {
                    var cookieData = c.split('=');
                    // cookie found
                    if(cookieData[0] == name) {
                        // return cookie value
                        return cookieData[1];
                    }
                }
            }
            return false;
        };

        /**
         * Check localstorage
         */
        var checkLocalStorage = function(name){
            if(window.localStorage){
                var now = new Date();
                var item = localStorage.getItem(name);

                // item not found
                if(typeof(item) == 'undefined' || item == null){
                    return false;
                }
                // item found, but time has expired
                if(item < (now.getTime()/1000) && name == myOptions.cookieName){
                    // delete item and return false
                    deleteLocalStorage();
                    return false;
                }
                // item found with valid time
                return item;
            }
            return false;
        };

        /**
         * Delete item from localstorage
         */
        var deleteLocalStorage = function(){
            if(window.localStorage){
                if(localStorage.getItem(myOptions.cookieName)){
                    localStorage.removeItem(myOptions.cookieName);
                }

                if(localStorage.getItem(myOptions.cookieName+'_closecount')){
                    localStorage.removeItem(myOptions.cookieName+'_closecount');
                }
            }
        };

        /**
         * Delete cookie
         */
        var deleteCookie = function(){
           document.cookie = myOptions.cookieName + '=;expires=Thu, 01 Jan 1970 00:00:01 GMT;';
           document.cookie = myOptions.cookieName + '_closecount=;expires=Thu, 01 Jan 1970 00:00:01 GMT;';
        };

        /**
         * Close popup
         */
        var closePopup = function(){
          if(myOptions.selector){
              $(myOptions.selector).addClass('hide');
              onPopupClose();
          }
        };

        /**
         * Save a popup name to cookie and localstorage
         */
        var onPopupClose = function(){
            var expiry_date = new Date();
            expiry_date = Math.round((expiry_date.getTime()/1000) + myOptions.cookieExpiryDays*24*60*60);
            // increment closecount
            closeCount++;
            // set cookie
            document.cookie = myOptions.cookieName+"="+myOptions.cookieName+"_closed; expires="+expiry_date+"; path=/";
            document.cookie = myOptions.cookieName+"_closecount="+closeCount+"; expires="+expiry_date+"; path=/";
            if(window.localStorage){
                localStorage.setItem(myOptions.cookieName, expiry_date);
                localStorage.setItem(myOptions.cookieName+'_closecount', closeCount);
            }

        };

        /** Public API **/
        var api = {

            /**
             * Public initialization method.
             **/
            start: function(){
                init();
            },
            /**
             * Enable popup campaigns
             */
            enable: function(){
                popupEnabled = true;
            },
            /**
             * Disable popup campaigns
             */
            disable: function(){
                popupEnabled = false;
            },
            /**
             * Set how many times the visitor should see the popup
             * @param number int
             */
            setCloseCount: function(number){
                closeCount = number;
                document.cookie = myOptions.cookieName+"_closecount="+closeCount+"; expires="+0+"; path=/";
                if(window.localStorage){
                    localStorage.setItem(myOptions.cookieName+'_closecount', closeCount);
                }
            },
            /**
             * Reset campaign
             */
            reset: function(){
                deleteLocalStorage();
                deleteCookie();
            },
            /**
             * Close popup
             */
            closePopup: function(){
                closePopup();
            },
            /**
             * Open popup
             */
            openPopup: function(){
                createPopup();
            }

        };

        // Return my external API
        return api;
    };

});
