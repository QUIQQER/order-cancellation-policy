<?php

use QUI\ERP\Order\CancellationPolicy\CancellationFormHelper;
use QUI\ERP\Order\CancellationPolicy\Controls\CancellationForm;
use QUI\ERP\Order\CancellationPolicy\FormSubmission;

QUI::getAjax()->registerFunction(
    'package_quiqqer_order-cancellation-policy_ajax_frontend_submit',
    function (
        $firstName,
        $lastName,
        $email,
        $orderNo,
        $message,
        $phone = '',
        $orderDate = '',
        $privacyPolicyAccepted = false,
        $captchaResponse = '',
        $project = null,
        $siteId = null
    ) {
        $Project = CancellationFormHelper::getProjectFromAjax($project);
        $Site = CancellationFormHelper::getSiteFromAjax($Project, $siteId);

        FormSubmission::submit(
            [
                'firstName' => $firstName,
                'lastName' => $lastName,
                'email' => $email,
                'orderNo' => $orderNo,
                'message' => $message,
                'phone' => $phone,
                'orderDate' => $orderDate,
                'privacyPolicyAccepted' => $privacyPolicyAccepted,
                'captchaResponse' => $captchaResponse
            ],
            $Project,
            $Site
        );

        $Control = new CancellationForm([
            'mode' => 'success'
        ]);

        return [
            'html' => $Control->renderContentOnly()
        ];
    },
    [
        'firstName',
        'lastName',
        'email',
        'orderNo',
        'message',
        'phone',
        'orderDate',
        'privacyPolicyAccepted',
        'captchaResponse',
        'project',
        'siteId'
    ]
);
