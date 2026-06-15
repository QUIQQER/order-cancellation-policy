define('package/quiqqer/order-cancellation-policy/bin/frontend/controls/CancellationForm', [
    'qui/QUI',
    'qui/controls/Control',
    'utils/Controls',
    'qui/controls/loader/Loader',
    'Ajax',
    'Locale',
    'package/quiqqer/controls/bin/site/Window'
], function (QUI, QUIControl, QUIControlUtils, QUILoader, Ajax, QUILocale, QUISiteWindow) {
    "use strict";

    const lg = 'quiqqer/order-cancellation-policy';

    return new Class({
        Extends: QUIControl,
        Type: 'package/quiqqer/order-cancellation-policy/bin/frontend/controls/CancellationForm',

        Binds: [
            '$onImport'
        ],

        initialize: function (options) {
            this.parent(options);

            this.Loader = new QUILoader();
            this.$captchaResponse = '';

            this.addEvents({
                onImport: this.$onImport
            });
        },

        $onImport: function () {
            const root = this.getElm();
            const container = root.querySelector('[data-name="cancellation-form"]') || root;
            const view = container.querySelector('[data-name="view"]');
            const form = container.querySelector('[data-name="form"]');
            const errorContainer = container.querySelector('[data-name="error"]');

            if (!form || !view) {
                return;
            }

            if (errorContainer) {
                errorContainer.hidden = true;
                errorContainer.textContent = '';
            }

            this.Loader.inject(root);

            form.addEventListener('submit', (event) => {
                event.preventDefault();
                this.submit();
            });

            this.registerPrivacyPopup();
            this.registerCaptchaHandling();
        },

        registerPrivacyPopup: function () {
            const root = this.getElm();
            const container = root.querySelector('[data-name="cancellation-form"]') || root;
            const privacyLink = container.querySelector('[data-name="privacy-link"]');
            const privacySiteId = container.dataset.privacySiteId;

            if (!privacyLink || !privacySiteId) {
                return;
            }

            privacyLink.addEventListener('click', function (event) {
                event.preventDefault();

                new QUISiteWindow({
                    closeButtonText: QUILocale.get('quiqqer/system', 'btn.close'),
                    showTitle: true,
                    project: container.dataset.projectName,
                    lang: container.dataset.projectLang,
                    id: privacySiteId
                }).open();
            });
        },

        registerCaptchaHandling: function () {
            const root = this.getElm();
            const container = root.querySelector('[data-name="cancellation-form"]') || root;
            const captchaElement = container.querySelector(
                'div[data-qui="package/quiqqer/captcha/bin/controls/CaptchaDisplay"]'
            );
            const captchaResponseInput = container.querySelector('[data-name="captcha-response"]');

            if (!captchaElement || !captchaResponseInput) {
                return;
            }

            QUIControlUtils.getControlByElement(captchaElement).then((CaptchaDisplay) => {
                CaptchaDisplay.getCaptchaControl().then((CaptchaControl) => {
                    CaptchaControl.addEvents({
                        onSuccess: (response) => {
                            this.$captchaResponse = response;
                            captchaResponseInput.value = response;
                        },
                        onExpired: () => {
                            this.$captchaResponse = '';
                            captchaResponseInput.value = '';
                        }
                    });
                });
            });
        },

        submit: function () {
            const root = this.getElm();
            const container = root.querySelector('[data-name="cancellation-form"]') || root;
            const view = container.querySelector('[data-name="view"]');
            const form = container.querySelector('[data-name="form"]');
            const errorContainer = container.querySelector('[data-name="error"]');

            if (!form || !view || !errorContainer) {
                return;
            }

            const formData = new FormData(form);

            this.Loader.show();
            errorContainer.hidden = true;
            errorContainer.textContent = '';

            Ajax.post(
                'package_quiqqer_order-cancellation-policy_ajax_frontend_submit',
                (result) => {
                    this.Loader.hide();

                    if (typeOf(result) === 'object' && result.html) {
                        view.innerHTML = result.html;
                    }

                    const successContainer = container.querySelector('[data-name="success"]');

                    if (!successContainer) {
                        return;
                    }

                    successContainer.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                },
                {
                    'package': 'quiqqer/order-cancellation-policy',
                    project: JSON.encode(QUIQQER_PROJECT),
                    siteId: QUIQQER_SITE.id,
                    firstName: formData.get('firstName') || '',
                    lastName: formData.get('lastName') || '',
                    email: formData.get('email') || '',
                    phone: formData.get('phone') || '',
                    orderNo: formData.get('orderNo') || '',
                    orderDate: formData.get('orderDate') || '',
                    message: formData.get('message') || '',
                    privacyPolicyAccepted: formData.get('privacyPolicyAccepted') ? 1 : 0,
                    captchaResponse: formData.get('captchaResponse') || this.$captchaResponse || '',
                    showError: false,
                    onError: (Exception) => {
                        this.Loader.hide();
                        errorContainer.hidden = false;
                        errorContainer.textContent = Exception.getMessage
                            ? Exception.getMessage()
                            : QUILocale.get(lg, 'control.cancellationForm.error.server');
                        errorContainer.scrollIntoView({
                            behavior: 'smooth',
                            block: 'nearest'
                        });
                    }
                }
            );
        }
    });
});
