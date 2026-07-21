<?php

namespace QUI\ERP\Order\CancellationPolicy;

use QUI;
use QUI\ERP\Utils\Sites as ERPSites;

use function date;
use function class_exists;
use function is_string;
use function rawurlencode;
use function str_contains;
use function strtotime;
use function trim;

class CancellationFormHelper
{
    /**
     * @return array<string, mixed>
     */
    public static function getSettings(): array
    {
        try {
            return QUI::getPackage('quiqqer/order-cancellation-policy')->getConfig()?->toArray() ?? [];
        } catch (\Exception $Exception) {
            QUI\System\Log::writeException($Exception);
        }

        return [];
    }

    public static function getSetting(string $name): mixed
    {
        try {
            return QUI::getPackage('quiqqer/order-cancellation-policy')->getConfig()?->get('frontend', $name);
        } catch (\Exception $Exception) {
            QUI\System\Log::writeException($Exception);
        }

        return null;
    }

    public static function getIntroText(?QUI\Projects\Project $Project = null): string
    {
        $introText = trim((string)QUI::getLocale()->get(
            'quiqqer/order-cancellation-policy',
            'frontend.cancellationForm.introText'
        ));

        if (
            $introText !== ''
            && !self::isMissingLocalePlaceholder(
                $introText,
                'quiqqer/order-cancellation-policy',
                'frontend.cancellationForm.introText'
            )
        ) {
            return $introText;
        }

        return QUI::getLocale()->get(
            'quiqqer/order-cancellation-policy',
            'control.cancellationForm.intro.default'
        );
    }

    public static function getSuccessText(?QUI\Projects\Project $Project = null): string
    {
        $successText = self::getConfiguredSuccessText();

        if ($successText !== '') {
            return $successText;
        }

        return QUI::getLocale()->get(
            'quiqqer/order-cancellation-policy',
            'control.cancellationForm.success'
        );
    }

    public static function getConfiguredSuccessText(): string
    {
        $successText = trim((string)QUI::getLocale()->get(
            'quiqqer/order-cancellation-policy',
            'frontend.cancellationForm.successText'
        ));

        if (
            $successText === ''
            || self::isMissingLocalePlaceholder(
                $successText,
                'quiqqer/order-cancellation-policy',
                'frontend.cancellationForm.successText'
            )
        ) {
            return '';
        }

        return $successText;
    }

    public static function isCaptchaEnabled(): bool
    {
        return (bool)self::getSetting('useCaptcha');
    }

    public static function isCaptchaAvailable(): bool
    {
        if (
            !QUI::getPackageManager()->isInstalled('quiqqer/captcha')
            || !class_exists('QUI\Captcha\Controls\CaptchaDisplay')
            || !class_exists('QUI\Captcha\Handler')
        ) {
            return false;
        }

        return true;
    }

    public static function logMissingCaptcha(): void
    {
        QUI\System\Log::addWarning(
            'CancellationForm: CAPTCHA is enabled in package settings but quiqqer/captcha is not available.'
        );
    }

    public static function validateCaptcha(string $captchaResponse): bool
    {
        if (!self::isCaptchaEnabled()) {
            return true;
        }

        if (!self::isCaptchaAvailable()) {
            self::logMissingCaptcha();
            return true;
        }

        return QUI\Captcha\Handler::isResponseValid($captchaResponse);
    }

    public static function getCaptchaDisplay(): ?QUI\Control
    {
        if (!self::isCaptchaEnabled()) {
            return null;
        }

        if (!self::isCaptchaAvailable()) {
            self::logMissingCaptcha();
            return null;
        }

        return new QUI\Captcha\Controls\CaptchaDisplay();
    }

    public static function getMailRecipient(): string
    {
        $mailTo = trim((string)self::getSetting('mailTo'));

        if (QUI\Utils\Security\Orthos::checkMailSyntax($mailTo)) {
            return $mailTo;
        }

        return trim((string)QUI::conf('mail', 'admin_mail'));
    }

    public static function getPrivacyPolicySite(
        ?QUI\Projects\Project $Project = null
    ): ?QUI\Projects\Site {
        try {
            $PrivacyPolicySite = ERPSites::getPrivacyPolicy();

            if ($PrivacyPolicySite instanceof QUI\Projects\Site) {
                return $PrivacyPolicySite;
            }
        } catch (\Exception $Exception) {
            QUI\System\Log::writeDebugException($Exception);
        }

        try {
            if ($Project === null) {
                $Project = QUI::getRewrite()->getProject();
            }

            if (!$Project) {
                return null;
            }

            $result = $Project->getSites([
                'where' => [
                    'type' => 'quiqqer/sitetypes:types/privacypolicy'
                ],
                'limit' => 1
            ]);

            if (isset($result[0]) && $result[0] instanceof QUI\Projects\Site) {
                return $result[0];
            }
        } catch (\Exception $Exception) {
            QUI\System\Log::writeException($Exception);
        }

        return null;
    }

    public static function getCancellationFormSite(
        ?QUI\Projects\Project $Project = null
    ): ?QUI\Projects\Site {
        try {
            if ($Project === null) {
                $Project = QUI::getRewrite()->getProject();
            }

            if (!$Project) {
                return null;
            }

            $result = $Project->getSites([
                'where' => [
                    'type' => 'quiqqer/order-cancellation-policy:types/cancellationForm'
                ],
                'limit' => 1
            ]);

            if (isset($result[0]) && $result[0] instanceof QUI\Projects\Site) {
                return $result[0];
            }
        } catch (\Exception $Exception) {
            QUI\System\Log::writeException($Exception);
        }

        return null;
    }

    public static function getPrefilledOrderNoFromRequest(): string
    {
        if (!isset($_GET['orderNo']) || !is_string($_GET['orderNo'])) {
            return '';
        }

        return trim($_GET['orderNo']);
    }

    public static function getPrefilledOrderDateFromRequest(): string
    {
        if (!isset($_GET['orderDate']) || !is_string($_GET['orderDate'])) {
            return '';
        }

        return self::normalizeOrderDateForRequest($_GET['orderDate']);
    }

    /**
     * @return array<string, string>
     */
    public static function getPrefilledUserData(): array
    {
        $result = [
            'firstName' => '',
            'lastName' => '',
            'email' => '',
            'phone' => ''
        ];

        try {
            $User = QUI::getUserBySession();

            if (QUI::getUsers()->isNobodyUser($User)) {
                return $result;
            }

            $Address = $User->getStandardAddress();

            if ($Address) {
                $result['firstName'] = trim((string)$Address->getAttribute('firstname'));
                $result['lastName'] = trim((string)$Address->getAttribute('lastname'));
                $result['phone'] = trim((string)$Address->getPhone());

                $mailList = $Address->getMailList();

                if (!empty($mailList[0]) && is_string($mailList[0])) {
                    $result['email'] = trim($mailList[0]);
                }
            }

            if ($result['firstName'] === '') {
                $result['firstName'] = trim((string)$User->getAttribute('firstname'));
            }

            if ($result['lastName'] === '') {
                $result['lastName'] = trim((string)$User->getAttribute('lastname'));
            }

            if ($result['email'] === '') {
                $result['email'] = trim((string)$User->getAttribute('email'));
            }
        } catch (\Exception $Exception) {
            QUI\System\Log::writeDebugException($Exception);
        }

        return $result;
    }

    public static function normalizeOrderDateForRequest(string $orderDate): string
    {
        $orderDate = trim($orderDate);

        if ($orderDate === '') {
            return '';
        }

        $timestamp = strtotime($orderDate);

        if (!$timestamp) {
            return '';
        }

        return date('Y-m-d', $timestamp);
    }

    public static function buildCancellationFormUrl(
        string $baseUrl,
        string $orderNo,
        string $orderDate = ''
    ): string {
        $url = $baseUrl;
        $url .= str_contains($url, '?') ? '&' : '?';
        $url .= 'orderNo=' . rawurlencode($orderNo);

        $normalizedOrderDate = self::normalizeOrderDateForRequest($orderDate);

        if ($normalizedOrderDate !== '') {
            $url .= '&orderDate=' . rawurlencode($normalizedOrderDate);
        }

        return $url;
    }

    public static function getProjectFromAjax(?string $project): ?QUI\Projects\Project
    {
        if (is_string($project) && $project !== '') {
            try {
                return QUI::getProjectManager()->decode($project);
            } catch (\Exception $Exception) {
                QUI\System\Log::writeException($Exception);
            }
        }

        try {
            return QUI::getRewrite()->getProject();
        } catch (\Exception $Exception) {
            QUI\System\Log::writeException($Exception);
        }

        return null;
    }

    public static function getSiteFromAjax(
        ?QUI\Projects\Project $Project,
        int|string|null $siteId
    ): ?QUI\Projects\Site {
        if ($Project && !empty($siteId)) {
            try {
                return $Project->get((int)$siteId);
            } catch (\Exception $Exception) {
                QUI\System\Log::writeException($Exception);
            }
        }

        try {
            $Site = QUI::getRewrite()->getSite();

            if ($Site instanceof QUI\Projects\Site) {
                return $Site;
            }
        } catch (\Exception $Exception) {
            QUI\System\Log::writeException($Exception);
        }

        return null;
    }

    protected static function isMissingLocalePlaceholder(
        string $value,
        string $group,
        string $var
    ): bool {
        return trim($value) === '[' . $group . '] ' . $var;
    }
}
