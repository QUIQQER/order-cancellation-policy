<?php

namespace QUI\ERP\Order\CancellationPolicy\Controls;

use QUI;
use QUI\ERP\Order\CancellationPolicy\CancellationFormHelper;
use QUI\FrontendUsers\Handler as FrontendUsersHandler;

use function dirname;
use function preg_replace;
use function str_replace;

class CancellationForm extends QUI\Control
{
    /**
     * @param array<string, mixed> $attributes
     */
    public function __construct(array $attributes = [])
    {
        $this->setAttributes([
            'introText' => '',
            'successText' => '',
            'mode' => 'form',
            'renderOnlyContent' => false
        ]);

        parent::__construct($attributes);

        $this->setAttribute('cacheable', 0);
        $this->setJavaScriptControl(
            'package/quiqqer/order-cancellation-policy/bin/frontend/controls/CancellationForm'
        );
        $this->addCSSClass('quiqqer-order-cancellation-policy-cancellationForm');
        $this->addCSSFile(dirname(__FILE__) . '/CancellationForm.css');
    }

    public function getBody(): string
    {
        return $this->renderTemplate();
    }

    public function renderContentOnly(): string
    {
        $this->setAttribute('renderOnlyContent', true);

        return $this->renderTemplate();
    }

    protected function renderTemplate(): string
    {
        $Engine = QUI::getTemplateManager()->getEngine();
        $Project = QUI::getRewrite()->getProject();
        $PrivacyPolicySite = CancellationFormHelper::getPrivacyPolicySite($Project);
        $privacyPolicyLabel = QUI::getLocale()->get(
            'quiqqer/order-cancellation-policy',
            'control.cancellationForm.privacyPolicy.labelWithoutLink'
        );

        if ($PrivacyPolicySite) {
            $privacyPolicyLabel = QUI::getLocale()->get(
                'quiqqer/order-cancellation-policy',
                'control.cancellationForm.privacyPolicy.label',
                [
                    'privacyPolicyLink' => '<a href="' . $PrivacyPolicySite->getUrlRewrittenWithHost()
                        . '" data-name="privacy-link">' . $PrivacyPolicySite->getAttribute('title') . '</a>'
                ]
            );

            $privacyPolicyLabel = preg_replace('#\[([^\]]*)\]#', '$1', $privacyPolicyLabel) ?? $privacyPolicyLabel;
        }

        $introText = (string)$this->getAttribute('introText');
        $successText = (string)$this->getAttribute('successText');
        $prefilledOrderNo = CancellationFormHelper::getPrefilledOrderNoFromRequest();
        $prefilledOrderDate = CancellationFormHelper::getPrefilledOrderDateFromRequest();
        $prefilledUserData = CancellationFormHelper::getPrefilledUserData();
        $startSiteUrl = null;
        $profileSiteUrl = null;

        if ($introText === '') {
            $introText = CancellationFormHelper::getIntroText($Project);
        }

        if ($successText === '') {
            $successText = CancellationFormHelper::getSuccessText($Project);
        }

        try {
            if ($Project) {
                $StartSite = $Project->get(1);
                $startSiteUrl = $StartSite->getUrlRewrittenWithHost();
            }
        } catch (\Exception $Exception) {
            QUI\System\Log::writeDebugException($Exception);
        }

        try {
            if ($Project) {
                $ProfileSite = FrontendUsersHandler::getInstance()->getProfileSite($Project);

                if ($ProfileSite) {
                    $profileSiteUrl = $ProfileSite->getUrlRewrittenWithHost();
                }
            }
        } catch (\Exception $Exception) {
            QUI\System\Log::writeDebugException($Exception);
        }

        $Engine->assign([
            'this' => $this,
            'introText' => $introText,
            'successText' => $successText,
            'mode' => (string)$this->getAttribute('mode'),
            'renderOnlyContent' => (bool)$this->getAttribute('renderOnlyContent'),
            'successHeadline' => QUI::getLocale()->get(
                'quiqqer/order-cancellation-policy',
                'control.cancellationForm.success.headline'
            ),
            'successHint' => QUI::getLocale()->get(
                'quiqqer/order-cancellation-policy',
                'control.cancellationForm.success.hint'
            ),
            'prefilledFirstName' => $prefilledUserData['firstName'],
            'prefilledLastName' => $prefilledUserData['lastName'],
            'prefilledEmail' => $prefilledUserData['email'],
            'prefilledPhone' => $prefilledUserData['phone'],
            'prefilledOrderNo' => $prefilledOrderNo,
            'prefilledOrderDate' => $prefilledOrderDate,
            'orderNoReadonly' => $prefilledOrderNo !== '',
            'orderDateReadonly' => $prefilledOrderDate !== '',
            'privacyPolicyLabel' => str_replace(['[', ']'], '', $privacyPolicyLabel),
            'CaptchaDisplay' => CancellationFormHelper::getCaptchaDisplay(),
            'privacyPolicySiteId' => $PrivacyPolicySite?->getId(),
            'projectName' => $Project?->getName(),
            'projectLang' => $Project?->getLang(),
            'startSiteUrl' => $startSiteUrl,
            'profileSiteUrl' => $profileSiteUrl
        ]);

        return $Engine->fetch(dirname(__FILE__) . '/CancellationForm.html');
    }
}
