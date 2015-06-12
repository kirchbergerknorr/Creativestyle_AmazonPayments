/**
 * This file is part of the official Amazon Payments Advanced extension
 * for Magento (c) creativestyle GmbH <amazon@creativestyle.de>
 * All rights reserved
 *
 * Reuse or modification of this source code is not allowed
 * without written permission from creativestyle GmbH
 *
 * @category   Creativestyle
 * @package    Creativestyle_AmazonPayments
 * @copyright  Copyright (c) 2014 creativestyle GmbH
 * @author     Marek Zabrowarny / creativestyle GmbH <amazon@creativestyle.de>
 */

if (!window.Review)
    var Review = function() {};

var APA = {

    submitAllowed: false,
    paymentSelected: false,
    reloaded: false,

    Widgets: {
        ShippingMethod: Class.create({
            initialize: function(options) {
                this.layer = $(APA.layers.shippingMethod);
                Object.extend(this, options);
                Event.stopObserving(this.layer, 'widget:update').observe('widget:update', this.onUpdate.bindAsEventListener(this));
            },
            update: function(html) {
                try {
                    Element.update(this.layer, html);
                    if (!this.layer.down('input[type=radio]:checked')) {
                        var firstShippingMethod = this.layer.down('input[type=radio]');
                        if (firstShippingMethod) {
                            firstShippingMethod.checked = true;
                        };
                    }
                    Event.stopObserving(this.layer, 'change').observe('change', this.onShippingMethodSelect.bindAsEventListener(this));
                    Event.stopObserving(this.layer, 'widget:checked').observe('widget:checked', this.onShippingMethodSelect.bindAsEventListener(this));
                    Event.fire(this.layer, 'widget:update');
                } catch(err) {
                    this.onError(err);
                }
            }
        }),
        Review: Class.create({
            initialize: function(options) {
                this.layer = $(APA.layers.review);
                Object.extend(this, options);
            },
            update: function(html) {
                try {
                    Element.update(this.layer, html);
                } catch(err) {
                    this.onError(err);
                }
            }
        })
    },

    setLoadWaiting: function() {
        for (var i = 0; i < arguments.length; i++) {
            if ($(arguments[i])) {
                var container = $(arguments[i]).up('li');
                container.addClassName('loading');
            }
        }
        return this;
    },

    unsetLoadWaiting: function() {
        for (var i = 0; i < arguments.length; i++) {
            if ($(arguments[i])) {
                var container = $(arguments[i]).up('li');
                container.removeClassName('loading');
            }
        }
        return this;
    },

    setOrderSaveWaiting: function() {
        if ($(APA.layers.review)) {
            var loader = $(APA.layers.review).down('.please-wait');
            if (loader) {
                Element.show(loader);
            }
        }
        this.showOverlay();
        this.disableSubmit();
        return this;
    },

    unsetOrderSaveWaiting: function() {
        if ($(APA.layers.review)) {
            var loader = $(APA.layers.review).down('.please-wait');
            if (loader) {
                Element.hide(loader);
            }
        }
        this.hideOverlay();
        return this;
    },

    showOverlay: function() {
        if ($('checkoutSteps')) {
            $('checkoutSteps').insert({
                top: new Element('div', {'class': 'amazon-widget-overlay'})
            });
        }
        return this;
    },

    hideOverlay: function() {
        if ($('checkoutSteps') && $('checkoutSteps').down('.amazon-widget-overlay')) {
            $('checkoutSteps').down('.amazon-widget-overlay').remove();
        }
        this.toggleSubmit();
        return this;
    },

    initCheckout: function() {
        this.disableSubmit().scaffoldPaymentWidget();
        if (this.virtual) {
            return this.setLoadWaiting(APA.layers.wallet).renderWalletWidget().allowSubmit(true);
        }
        if (!this.orderReferenceId) {
            return this.setLoadWaiting(APA.layers.shippingMethod, APA.layers.wallet, APA.layers.review).renderAddressBookWidget();
        }
        return this.setLoadWaiting(APA.layers.shippingMethod, APA.layers.wallet, APA.layers.review).renderAddressBookWidget().renderWalletWidget();
    },

    scaffoldPaymentWidget: function() {
        if (typeof APA.design.wallet.size != 'undefined' && !APA.design.responsive) {
            if (APA.design.wallet.size.width) {
                $(APA.layers.wallet).up().setStyle({width: APA.design.wallet.size.width});
            }
            if (APA.design.wallet.size.height) {
                $(APA.layers.wallet).up().setStyle({height: APA.design.wallet.size.height});
            }
        }
        return this;
    },

    renderButtonWidget: function(tooltipContent) {
        if (APA.urls.login != null) {
            $$(APA.layers.payButtons).each(function(button) {
                new OffAmazonPayments.Button(button.identify(), APA.sellerId, {
                    type: button.buttonType || APA.design.payButton.type || 'PwA',
                    size: button.buttonSize || APA.design.payButton.size,
                    color: button.buttonColor || APA.design.payButton.color,
                    authorization: function() {
                        amazon.Login.authorize({
                            scope: 'profile payments:widget payments:shipping_address',
                            popup: APA.popup
                        }, APA.urls.pay);
                    },
                    onError: APA.amazonErrorCallback
                });
            });
            $$(APA.layers.loginButtons).each(function(button) {
                new OffAmazonPayments.Button(button.identify(), APA.sellerId, {
                    type: button.buttonType || APA.design.loginButton.type || 'LwA',
                    size: button.buttonSize || APA.design.loginButton.size,
                    color: button.buttonColor || APA.design.loginButton.color,
                    authorization: function() {
                        amazon.Login.authorize({
                            scope: 'profile payments:widget payments:shipping_address',
                            popup: APA.popup
                        }, APA.urls.login);
                    },
                    onError: APA.amazonErrorCallback
                });
            });
        } else {
            $$(APA.layers.payButtons).each(function(button) {
                new OffAmazonPayments.Widgets.Button({
                    sellerId: APA.sellerId,
                    useAmazonAddressBook: !APA.virtual,
                    onSignIn: APA.signInCallback,
                    onError: APA.amazonErrorCallback
                }).bind(button.identify());
            });
        }

        // add tooltips
        if (tooltipContent && typeof Tooltip != 'undefined' && !APA.isMobileDevice()) {
            var tooltipButtons = $$(APA.layers.payButtons + ',' + APA.layers.loginButtons).findAll(function(button) {
                return button.hasClassName('with-tooltip');
            });
            if (tooltipButtons.length) {
                var tooltip = document.createElement('div');
                tooltip.setAttribute('id', 'pay-with-amazon-tooltip');
                tooltip.addClassName('pay-with-amazon-tooltip');
                tooltip.setStyle({display: 'none', zIndex: 10});
                tooltip.update(tooltipContent);
                document.body.appendChild(tooltip);
                tooltipButtons.each(function(button) {
                    var buttonImg = button.down('img');
                    if (buttonImg) {
                        new Tooltip(buttonImg, tooltip);
                    }
                });
            }
        }
    },

    signInCallback: function(orderReference) {
        var id = document.createElement('input');
        id.setAttribute('type', 'hidden');
        id.setAttribute('name', 'orderReferenceId');
        id.setAttribute('value', orderReference.getAmazonOrderReferenceId());
        var form = document.createElement('form');
        form.setAttribute('method', 'post');
        form.setAttribute('action', APA.urls.checkout);
        form.appendChild(id);
        document.body.appendChild(form);
        form.submit();
        return;
    },

    renderAddressBookWidget: function() {
        APA.setLoadWaiting(APA.layers.shippingMethod, APA.layers.review);
        new OffAmazonPayments.Widgets.AddressBook({
            sellerId: APA.sellerId,
            onOrderReferenceCreate: APA.orderReferenceCreateCallback,
            amazonOrderReferenceId: APA.orderReferenceId,
            design: (APA.design.responsive ? {designMode: 'responsive'} : APA.design.addressBook),
            onAddressSelect: APA.addressSelectCallback,
            onError: APA.amazonErrorCallback,
        }).bind(APA.layers.addressBook);
        return this;
    },

    renderReadOnlyAddressBookWidget: function() {
        new OffAmazonPayments.Widgets.AddressBook({
            sellerId: APA.sellerId,
            amazonOrderReferenceId: APA.orderReferenceId,
            design: (APA.design.responsive ? {designMode: 'responsive'} : APA.design.addressBook),
            onError: APA.amazonErrorCallback,
            displayMode: 'Read'
        }).bind(APA.layers.addressBook);
        return this;
    },

    orderReferenceCreateCallback: function(orderReference) {
        if (!APA.orderReferenceId) {
            APA.orderReferenceId = orderReference.getAmazonOrderReferenceId();
            if (!APA.virtual) {
                APA.renderWalletWidget();
            }
        }
    },

    addressSelectCallback: function() {
        APA.selectPayment(false);
        APA.setLoadWaiting(APA.layers.shippingMethod, APA.layers.review);
        new Ajax.Request(APA.urls.saveShipping, {
            method: 'post',
            parameters: {orderReferenceId: APA.orderReferenceId},
            evalScripts: true,
            onSuccess: APA.successCallback,
            onFailure: APA.ajaxFailureCallback
        });
    },

    renderShippingMethodWidget: function(html) {
        APA.setLoadWaiting(APA.layers.review);
        new APA.Widgets.ShippingMethod({
            onShippingMethodSelect: APA.shippingMethodSelectCallback,
            onUpdate: APA.shippingMethodUpdateCallback,
            onError: APA.magentoErrorCallback
        }).update(html);
        return this;
    },

    shippingMethodSelectCallback: function(event) {
        APA.setLoadWaiting(APA.layers.review);
        new Ajax.Request(APA.urls.saveShippingMethod, {
            method: 'post',
            parameters: Form.serialize($('co-shipping-method-form')),
            evalScripts: true,
            onSuccess: APA.successCallback,
            onFailure: APA.ajaxFailureCallback
        });
    },

    shippingMethodUpdateCallback: function(event) {
        var element = Event.element(event);
        Event.fire(element, 'widget:checked');
    },

    renderWalletWidget: function() {
        new OffAmazonPayments.Widgets.Wallet({
            sellerId: APA.sellerId,
            onOrderReferenceCreate: APA.orderReferenceCreateCallback,
            amazonOrderReferenceId: APA.orderReferenceId,
            design: (APA.design.responsive ? {designMode: 'responsive'} : APA.design.wallet),
            onPaymentSelect: APA.paymentSelectCallback,
            onError: APA.amazonErrorCallback
        }).bind(APA.layers.wallet);
        return this;
    },

    paymentSelectCallback: function() {
        APA.selectPayment(true);
    },

    reloadWallet: function() {
        this.renderReadOnlyAddressBookWidget().renderWalletWidget();
        return this;
    },

    renderReviewWidget: function(html) {
        new APA.Widgets.Review({
            onError: APA.magentoErrorCallback
        }).update(html);
        this.toggleSubmit();
        return this;
    },

    successCallback: function(transport) {
        response = eval('(' + transport.responseText + ')');
        if (response.error) {
            APA.magentoErrorCallback(response.error_messages);
        };

        if (response.render_widget) {
            $H(response.render_widget).each(function(pair) {
                APA['render' + pair.key.capitalize().camelize() + 'Widget'](pair.value);
                if (pair.value) {
                    APA.unsetLoadWaiting(APA.layers[pair.key.camelize()]);
                }
            });
        };

        if (response.allow_submit) {
            APA.allowSubmit(true);
        } else {
            APA.allowSubmit(false);
        }
    },

    saveOrderCallback: function(transport) {
        response = eval('(' + transport.responseText + ')');
        if (response.success) {
            window.location = APA.urls.success;
        };
        if (response.redirect) {
            window.location = response.redirect;
        }
        if (response.error) {
            APA.unsetOrderSaveWaiting();
            APA.magentoErrorCallback(response.error_messages);
            if (response.reload) {
                APA.reloaded = false;
                APA.orderReferenceId = null;
                APA.initCheckout();
            };
            if (response.reload_wallet) {
                APA.reloaded = true;
                APA.disableSubmit().reloadWallet();
            };
        };
    },

    ajaxFailureCallback: function() {
        window.location.href = APA.urls.failure;
    },

    amazonErrorCallback: function(error) {
        if (!APA.live) {
            console.trace();
            alert(error.getErrorMessage());
        }
        var redirectErrors = ['BuyerNotAssociated', 'BuyerSessionExpired', 'StaleOrderReference', 'InvalidOrderReferenceId'];
        if (redirectErrors.any(function(errorCode) { return errorCode == error.getErrorCode() })) {
            window.location.href = APA.urls.failure;
        }
    },

    magentoErrorCallback: function(error) {
        if (!APA.live) {
            console.trace();
        }
        if (typeof(error) == 'object') {
            error = error.join("\n");
        }
        if (error) {
            alert(error);
        }
    },

    allowSubmit: function(allowed) {
        this.submitAllowed = allowed;
        return this.toggleSubmit();
    },

    selectPayment: function(selected) {
        this.paymentSelected = selected;
        return this.toggleSubmit();
    },

    toggleSubmit: function() {
        if (this.submitAllowed && this.paymentSelected) {
            return this.enableSubmit();
        }
        return this.disableSubmit();
    },

    disableSubmit: function() {
        var button = $('amazonpayments-checkout-place-order-button');
        if (button) {
            button.disabled = true;
            var buttonContainer = button.up('div.buttons-set');
            if (buttonContainer) {
                buttonContainer.addClassName('disabled');
            }
        }
        return this;
    },

    enableSubmit: function() {
        if (this.submitAllowed && this.paymentSelected) {
            var button = $('amazonpayments-checkout-place-order-button');
            if (button) {
                button.disabled = false;
                var buttonContainer = button.up('div.buttons-set');
                if (buttonContainer) {
                    buttonContainer.removeClassName('disabled');
                }
            }
        }
        return this;
    },

    saveOrder: function() {
        APA.setOrderSaveWaiting();
        new Ajax.Request(APA.urls.saveOrder, {
            method: 'post',
            parameters: APA.getSaveOrderParams(),
            onSuccess: APA.saveOrderCallback,
            onFailure: APA.ajaxFailureCallback
        });
    },

    getSaveOrderParams: function() {
        var params = '';
        if (APA.virtual) {
            params += Object.toQueryString({orderReferenceId: APA.orderReferenceId});
            if ($('checkout-agreements')) {
                params += '&' + Form.serialize($('checkout-agreements'));
            }
        } else {
            params = Form.serialize($('co-shipping-method-form'));
            if ($('checkout-agreements')) {
                params += '&' + Form.serialize($('checkout-agreements'));
            }
        }
        if (APA.reloaded) {
            params += '&' + Object.toQueryString({reloaded: 1});
        }
        params.save = true;
        return params;
    },

    isMobileDevice: function() {
        return /(android|bb\d+|meego).+mobile|avantgo|bada\/|blackberry|blazer|compal|elaine|fennec|hiptop|iemobile|ip(hone|od)|iris|kindle|lge |maemo|midp|mmp|mobile.+firefox|netfront|opera m(ob|in)i|palm( os)?|phone|p(ixi|re)\/|plucker|pocket|psp|series(4|6)0|symbian|treo|up\.(browser|link)|vodafone|wap|windows ce|xda|xiino|android|ipad|playbook|silk/i.test(navigator.userAgent || navigator.vendor || window.opera) || /1207|6310|6590|3gso|4thp|50[1-6]i|770s|802s|a wa|abac|ac(er|oo|s\-)|ai(ko|rn)|al(av|ca|co)|amoi|an(ex|ny|yw)|aptu|ar(ch|go)|as(te|us)|attw|au(di|\-m|r |s )|avan|be(ck|ll|nq)|bi(lb|rd)|bl(ac|az)|br(e|v)w|bumb|bw\-(n|u)|c55\/|capi|ccwa|cdm\-|cell|chtm|cldc|cmd\-|co(mp|nd)|craw|da(it|ll|ng)|dbte|dc\-s|devi|dica|dmob|do(c|p)o|ds(12|\-d)|el(49|ai)|em(l2|ul)|er(ic|k0)|esl8|ez([4-7]0|os|wa|ze)|fetc|fly(\-|_)|g1 u|g560|gene|gf\-5|g\-mo|go(\.w|od)|gr(ad|un)|haie|hcit|hd\-(m|p|t)|hei\-|hi(pt|ta)|hp( i|ip)|hs\-c|ht(c(\-| |_|a|g|p|s|t)|tp)|hu(aw|tc)|i\-(20|go|ma)|i230|iac( |\-|\/)|ibro|idea|ig01|ikom|im1k|inno|ipaq|iris|ja(t|v)a|jbro|jemu|jigs|kddi|keji|kgt( |\/)|klon|kpt |kwc\-|kyo(c|k)|le(no|xi)|lg( g|\/(k|l|u)|50|54|\-[a-w])|libw|lynx|m1\-w|m3ga|m50\/|ma(te|ui|xo)|mc(01|21|ca)|m\-cr|me(rc|ri)|mi(o8|oa|ts)|mmef|mo(01|02|bi|de|do|t(\-| |o|v)|zz)|mt(50|p1|v )|mwbp|mywa|n10[0-2]|n20[2-3]|n30(0|2)|n50(0|2|5)|n7(0(0|1)|10)|ne((c|m)\-|on|tf|wf|wg|wt)|nok(6|i)|nzph|o2im|op(ti|wv)|oran|owg1|p800|pan(a|d|t)|pdxg|pg(13|\-([1-8]|c))|phil|pire|pl(ay|uc)|pn\-2|po(ck|rt|se)|prox|psio|pt\-g|qa\-a|qc(07|12|21|32|60|\-[2-7]|i\-)|qtek|r380|r600|raks|rim9|ro(ve|zo)|s55\/|sa(ge|ma|mm|ms|ny|va)|sc(01|h\-|oo|p\-)|sdk\/|se(c(\-|0|1)|47|mc|nd|ri)|sgh\-|shar|sie(\-|m)|sk\-0|sl(45|id)|sm(al|ar|b3|it|t5)|so(ft|ny)|sp(01|h\-|v\-|v )|sy(01|mb)|t2(18|50)|t6(00|10|18)|ta(gt|lk)|tcl\-|tdg\-|tel(i|m)|tim\-|t\-mo|to(pl|sh)|ts(70|m\-|m3|m5)|tx\-9|up(\.b|g1|si)|utst|v400|v750|veri|vi(rg|te)|vk(40|5[0-3]|\-v)|vm40|voda|vulc|vx(52|53|60|61|70|80|81|83|85|98)|w3c(\-| )|webc|whit|wi(g |nc|nw)|wmlb|wonu|x700|yas\-|your|zeto|zte\-/i.test((navigator.userAgent || navigator.vendor || window.opera).substr(0,4));
    },

    initialize: function(sellerId, orderReferenceId, live, virtual, urls, layers, design) {
        return Object.extend(APA, {
            sellerId: sellerId,
            orderReferenceId: orderReferenceId,
            live: live,
            virtual: virtual,
            urls: Object.extend({
                login: null,
                pay: null,
                checkout: null,
                saveShipping: null,
                saveShippingMethod: null,
                saveOrder: null,
                success: null,
                failure: null
            }, urls),
            layers: Object.extend({
                payButtons: '.payButtonWidget',
                loginButtons: '.loginButtonWidget',
                addressBook: 'addressBookWidgetDiv',
                wallet: 'walletWidgetDiv',
                shippingMethod: 'shippingMethodWidgetDiv',
                review: 'reviewWidgetDiv'
            }, layers),
            design: Object.extend({
                addressBook: {
                    size: {
                        width: '440px',
                        height: '260px'
                    }
                },
                wallet: {
                    size: {
                        width: '440px',
                        height: '260px'
                    }
                },
                payButton: {
                    type: 'PwA'
                },
                loginButton: {
                    type: 'LwA'
                }
            }, design)
        });
    },

    setup: function(sellerId, options) {
        return Object.extend(APA, {
            sellerId:         sellerId,
            orderReferenceId: typeof options.orderReferenceId == 'undefined' ? null : options.orderReferenceId,
            live:             typeof options.live == 'undefined' ? true : options.live,
            popup:            typeof options.popup == 'undefined' ? true : options.popup,
            virtual:          typeof options.virtual == 'undefined' ? false : options.virtual,
            layers: Object.extend({
                payButtons:         '.payButtonWidget',
                loginButtons:       '.loginButtonWidget',
                addressBook:        'addressBookWidgetDiv',
                wallet:             'walletWidgetDiv',
                shippingMethod:     'shippingMethodWidgetDiv',
                review:             'reviewWidgetDiv'
            }, options.layers),
            urls: Object.extend({
                login:              null,
                pay:                null,
                checkout:           null,
                saveShipping:       null,
                saveShippingMethod: null,
                saveOrder:          null,
                success:            null,
                failure:            null
            }, options.urls),
            design: Object.extend({
                responsive:         true,
                addressBook:        {size: {width: '440px', height: '260px'}},
                wallet:             {size: {width: '440px', height: '260px'}},
                payButton:          {type: 'PwA'},
                loginButton:        {type: 'LwA'}
            }, options.design)
        });
    }

};
