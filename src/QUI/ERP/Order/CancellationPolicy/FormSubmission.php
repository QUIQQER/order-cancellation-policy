<?php

namespace QUI\ERP\Order\CancellationPolicy;

use DateTime;
use QUI;

use function array_key_exists;
use function mb_strlen;
use function trim;

class FormSubmission
{
    /**
     * @var array<string, int>
     */
    protected const FIELD_LENGTHS = [
        'firstName' => 255,
        'lastName' => 255,
        'email' => 255,
        'phone' => 255,
        'orderNo' => 255,
        'message' => 5000
    ];

    /**
     * @param array<string, mixed> $data
     * @throws QUI\Database\Exception
     * @throws QUI\Exception
     */
    public static function submit(
        array $data,
        ?QUI\Projects\Project $Project = null,
        ?QUI\Projects\Site $Site = null
    ): void {
        $normalizedData = self::normalize($data);

        self::validateRequiredField('firstName', $normalizedData['firstName']);
        self::validateRequiredField('lastName', $normalizedData['lastName']);
        self::validateRequiredField('email', $normalizedData['email']);
        self::validateRequiredField('orderNo', $normalizedData['orderNo']);
        self::validateRequiredField('message', $normalizedData['message']);
        self::validateFieldLengths($normalizedData);

        if (!QUI\Utils\Security\Orthos::checkMailSyntax($normalizedData['email'])) {
            throw new QUI\Exception(
                QUI::getLocale()->get(
                    'quiqqer/order-cancellation-policy',
                    'control.cancellationForm.error.invalidEmail'
                )
            );
        }

        if ($normalizedData['orderDate'] !== '' && !self::isValidDate($normalizedData['orderDate'])) {
            throw new QUI\Exception(
                QUI::getLocale()->get(
                    'quiqqer/order-cancellation-policy',
                    'control.cancellationForm.error.invalidOrderDate'
                )
            );
        }

        if (!$normalizedData['privacyPolicyAccepted']) {
            throw new QUI\Exception(
                QUI::getLocale()->get(
                    'quiqqer/order-cancellation-policy',
                    'control.cancellationForm.error.privacyPolicyRequired'
                )
            );
        }

        if (!CancellationFormHelper::validateCaptcha($normalizedData['captchaResponse'])) {
            throw new QUI\Exception(
                QUI::getLocale()->get(
                    'quiqqer/order-cancellation-policy',
                    'control.cancellationForm.error.captcha'
                )
            );
        }

        RequestRepository::create($normalizedData, $Project, $Site);
        RequestMailer::send($normalizedData, $Site);
    }

    /**
     * @param array<string, mixed> $data
     * @return array{
     *     firstName: string,
     *     lastName: string,
     *     email: string,
     *     phone: string,
     *     orderNo: string,
     *     orderDate: string,
     *     message: string,
     *     privacyPolicyAccepted: bool,
     *     captchaResponse: string
     * }
     */
    protected static function normalize(array $data): array
    {
        return [
            'firstName' => trim((string)($data['firstName'] ?? '')),
            'lastName' => trim((string)($data['lastName'] ?? '')),
            'email' => trim((string)($data['email'] ?? '')),
            'phone' => trim((string)($data['phone'] ?? '')),
            'orderNo' => trim((string)($data['orderNo'] ?? '')),
            'orderDate' => trim((string)($data['orderDate'] ?? '')),
            'message' => trim((string)($data['message'] ?? '')),
            'privacyPolicyAccepted' => !empty($data['privacyPolicyAccepted']),
            'captchaResponse' => trim((string)($data['captchaResponse'] ?? ''))
        ];
    }

    /**
     * @throws QUI\Exception
     */
    protected static function validateRequiredField(string $fieldName, string $value): void
    {
        if ($value !== '') {
            return;
        }

        throw new QUI\Exception(
            QUI::getLocale()->get(
                'quiqqer/order-cancellation-policy',
                'control.cancellationForm.error.requiredField',
                [
                    'field' => self::getFieldLabel($fieldName)
                ]
            )
        );
    }

    /**
     * @param array<string, scalar|null> $data
     * @throws QUI\Exception
     */
    protected static function validateFieldLengths(array $data): void
    {
        foreach (self::FIELD_LENGTHS as $fieldName => $maxLength) {
            $value = (string)($data[$fieldName] ?? '');

            if (mb_strlen($value) <= $maxLength) {
                continue;
            }

            throw new QUI\Exception(
                QUI::getLocale()->get(
                    'quiqqer/order-cancellation-policy',
                    'control.cancellationForm.error.fieldTooLong',
                    [
                        'field' => self::getFieldLabel($fieldName),
                        'max' => $maxLength
                    ]
                )
            );
        }
    }

    protected static function isValidDate(string $value): bool
    {
        $Date = DateTime::createFromFormat('Y-m-d', $value);

        return $Date instanceof DateTime && $Date->format('Y-m-d') === $value;
    }

    protected static function getFieldLabel(string $fieldName): string
    {
        $map = [
            'firstName' => 'control.cancellationForm.firstName',
            'lastName' => 'control.cancellationForm.lastName',
            'email' => 'control.cancellationForm.email',
            'phone' => 'control.cancellationForm.phone',
            'orderNo' => 'control.cancellationForm.orderNo',
            'orderDate' => 'control.cancellationForm.orderDate',
            'message' => 'control.cancellationForm.message'
        ];

        if (!array_key_exists($fieldName, $map)) {
            return $fieldName;
        }

        return QUI::getLocale()->get(
            'quiqqer/order-cancellation-policy',
            $map[$fieldName]
        );
    }
}
