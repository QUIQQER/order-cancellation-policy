<?php

namespace QUI\ERP\Order\CancellationPolicy;

use QUI;

use function htmlspecialchars;
use function nl2br;

class RequestMailer
{
    /**
     * @param array{
     *     firstName: string,
     *     lastName: string,
     *     email: string,
     *     phone: string,
     *     orderNo: string,
     *     orderDate: string,
     *     message: string,
     *     privacyPolicyAccepted: bool,
     *     captchaResponse: string
     * } $data
     * @throws QUI\Exception
     */
    public static function send(array $data, ?QUI\Projects\Site $Site = null): void
    {
        $recipient = CancellationFormHelper::getMailRecipient();

        if (!QUI\Utils\Security\Orthos::checkMailSyntax($recipient)) {
            throw new QUI\Exception(
                QUI::getLocale()->get(
                    'quiqqer/order-cancellation-policy',
                    'control.cancellationForm.error.server'
                )
            );
        }

        $subject = QUI::getLocale()->get(
            'quiqqer/order-cancellation-policy',
            'control.cancellationForm.mail.subject'
        );

        if ($Site) {
            $subject .= ' | ' . $Site->getAttribute('title');
        }

        $Mailer = QUI::getMailManager()->getMailer();
        $Mailer->addRecipient($recipient);
        $Mailer->addReplyTo((string)$data['email']);
        $Mailer->setSubject($subject);
        $Mailer->setBody(self::buildBody($data));

        try {
            $Mailer->send();
        } catch (\Exception $Exception) {
            QUI\System\Log::writeException($Exception);

            throw new QUI\Exception(
                QUI::getLocale()->get(
                    'quiqqer/order-cancellation-policy',
                    'control.cancellationForm.error.server'
                )
            );
        }
    }

    /**
     * @param array{
     *     firstName: string,
     *     lastName: string,
     *     email: string,
     *     phone: string,
     *     orderNo: string,
     *     orderDate: string,
     *     message: string,
     *     privacyPolicyAccepted: bool,
     *     captchaResponse: string
     * } $data
     */
    protected static function buildBody(array $data): string
    {
        $rows = [
            'firstName' => 'control.cancellationForm.mail.label.firstName',
            'lastName' => 'control.cancellationForm.mail.label.lastName',
            'email' => 'control.cancellationForm.mail.label.email',
            'phone' => 'control.cancellationForm.mail.label.phone',
            'orderNo' => 'control.cancellationForm.mail.label.orderNo',
            'orderDate' => 'control.cancellationForm.mail.label.orderDate'
        ];

        $body = '';

        foreach ($rows as $field => $localeKey) {
            $value = $data[$field];

            if ($value === '') {
                continue;
            }

            $body .= '<p><strong>';
            $body .= QUI::getLocale()->get('quiqqer/order-cancellation-policy', $localeKey);
            $body .= ':</strong> ';
            $body .= htmlspecialchars($value, ENT_QUOTES);
            $body .= '</p>';
        }

        $body .= '<p><strong>';
        $body .= QUI::getLocale()->get(
            'quiqqer/order-cancellation-policy',
            'control.cancellationForm.mail.privacyPolicyAccepted'
        );
        $body .= '</strong></p>';

        $body .= '<p><strong>';
        $body .= QUI::getLocale()->get(
            'quiqqer/order-cancellation-policy',
            'control.cancellationForm.mail.label.message'
        );
        $body .= ':</strong></p>';
        $body .= '<div style="white-space: pre-line;">';
        $body .= nl2br(htmlspecialchars((string)$data['message'], ENT_QUOTES));
        $body .= '</div>';

        return $body;
    }
}
