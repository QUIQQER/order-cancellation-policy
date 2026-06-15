<?php

/**
 * This file contains QUI\ERP\Order\CancellationPolicy\EventHandling
 */

namespace QUI\ERP\Order\CancellationPolicy;

use QUI;
use QUI\ERP\Order\AbstractOrder;
use QUI\ERP\Order\Controls\AbstractOrderingStep;
use QUI\ERP\Order\OrderView;
use QUI\Smarty\Collector;

/**
 * Class EventHandling
 *
 * @package QUI\ERP\Order\CancellationPolicy
 */
class EventHandling
{
    public static function onQuiqqerOrderOrderProcessCheckoutOutput(AbstractOrderingStep $Step, string &$text): void
    {
        $Project = QUI::getRewrite()->getProject();

        $ProjectFromAttribute = $Step->getAttribute('Project');

        if ($ProjectFromAttribute instanceof QUI\Projects\Project) {
            $Project = $ProjectFromAttribute;
        }

        if (!$Project) {
            return;
        }

        $cancellationText = self::getText($Step->getOrder(), $Project);

        if (!empty($cancellationText)) {
            $text = $cancellationText;
        }
    }

    public static function onQuiqqerOrderSimpleCheckoutOutput(
        QUI\ERP\Order\SimpleCheckout\Checkout $Checkout,
        string &$text
    ): void {
        try {
            $Project = QUI::getRewrite()->getProject();

            if (!$Project) {
                return;
            }

            $cancellationText = self::getText($Checkout->getOrder(), $Project);

            if (!empty($cancellationText)) {
                $text = $cancellationText;
            }
        } catch (QUI\Exception $exception) {
            QUI\System\Log::addError($exception->getMessage());
        }
    }

    public static function templateFrontendUserOrderFooterEnd(
        Collector $Collector,
        OrderView $Order
    ): void {
        $html = self::getFrontendUserOrderFooterHtml($Order);

        if ($html !== '') {
            $Collector->append($html);
        }
    }

    protected static function getText(?AbstractOrder $Order, QUI\Projects\Project $Project): ?string
    {
        $OrderProcessCheckout = new QUI\ERP\Order\Controls\OrderProcess\Checkout([
            'Project' => $Project
        ]);

        /* @var $Step QUI\ERP\Order\Controls\OrderProcess\Checkout */
        if ($Order) {
            $Address = $Order->getInvoiceAddress();
            $Customer = $Order->getCustomer();
        } else {
            $Address = QUI::getUserBySession()->getStandardAddress();
            $Customer = QUI::getUserBySession();
        }

        if (!$Address || !$Customer) {
            return null;
        }

        try {
            $User = QUI::getUsers()->get($Customer->getUUID());

            if ($User->isCompany()) {
                return null;
            }
        } catch (QUI\Exception $Exception) {
            QUI\System\Log::writeDebugException($Exception);
        }

        try {
            $Country = $Address->getCountry();
        } catch (QUI\Exception) {
            return null;
        }

        $Area = QUI\ERP\Areas\Utils::getAreaByCountry($Country);

        if (!$Area) {
            return null;
        }

        if (!OCP::hasAreaCancellationPolicy($Area)) {
            return null;
        }

        return QUI::getLocale()->get(
            'quiqqer/order-cancellation-policy',
            'ordering.step.checkout.checkoutAcceptText',
            [
                'terms_and_conditions' => $OrderProcessCheckout->getLinkOf('terms_and_conditions'),
                'revocation' => $OrderProcessCheckout->getLinkOf('revocation')
            ]
        );
    }

    protected static function getFrontendUserOrderFooterHtml(OrderView $Order): string
    {
        try {
            $Project = QUI::getRewrite()->getProject();
        } catch (QUI\Exception) {
            return '';
        }

        try {
            $User = QUI::getUsers()->get($Order->getCustomer()->getUUID());

            if ($User->isCompany()) {
                return '';
            }
        } catch (QUI\Exception $Exception) {
            QUI\System\Log::writeDebugException($Exception);
        }

        try {
            $Country = $Order->getInvoiceAddress()->getCountry();
        } catch (QUI\Exception) {
            return '';
        }

        $Area = QUI\ERP\Areas\Utils::getAreaByCountry($Country);

        if (!$Area || !OCP::hasAreaCancellationPolicy($Area)) {
            return '';
        }

        $CancellationFormSite = CancellationFormHelper::getCancellationFormSite($Project);

        if (!$CancellationFormSite) {
            return '';
        }

        $label = QUI::getLocale()->get(
            'quiqqer/order-cancellation-policy',
            'frontendUsers.order.footer.cancellationForm.label'
        );
        $linkText = QUI::getLocale()->get(
            'quiqqer/order-cancellation-policy',
            'frontendUsers.order.footer.cancellationForm.link'
        );
        $orderData = $Order->toArray();
        $url = CancellationFormHelper::buildCancellationFormUrl(
            $CancellationFormSite->getUrlRewritten(),
            $Order->getPrefixedId(),
            (string)($orderData['cDate'] ?? '')
        );

        return '<div class="quiqqer-order-profile-orders-order__group '
            . 'quiqqer-order-profile-orders-order-footer-cancellationForm">'
            . '<span class="quiqqer-order-profile-orders-order__label">'
            . $label
            . ':</span>'
            . '<div class="quiqqer-order-profile-orders-order__value">'
            . '<a href="' . $url . '">' . $linkText . '</a>'
            . '</div>'
            . '</div>';
    }
}
