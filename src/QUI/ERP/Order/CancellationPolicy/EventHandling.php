<?php

/**
 * This file contains QUI\ERP\Order\CancellationPolicy\EventHandling
 */

namespace QUI\ERP\Order\CancellationPolicy;

use QUI;
use QUI\ERP\Order\Controls\AbstractOrderingStep;

/**
 * Class EventHandling
 *
 * @package QUI\ERP\Order\CancellationPolicy
 */
class EventHandling
{
    public static function onQuiqqerOrderOrderProcessCheckoutOutput(AbstractOrderingStep $Step, &$text): void
    {
        $Project = QUI::getRewrite()->getProject();

        if ($Step->getAttribute('Project')) {
            $Project = $Step->getAttribute('Project');
        }

        $cancellationText = self::getText($Step->getOrder(), $Project);

        if (!empty($cancellationText)) {
            $text = $cancellationText;
        }
    }

    public static function onQuiqqerOrderSimpleCheckoutOutput(
        QUI\ERP\Order\SimpleCheckout\Checkout $Checkout,
        &$text
    ): void {
        try {
            $Project = QUI::getRewrite()->getProject();
            $cancellationText = self::getText($Checkout->getOrder(), $Project);

            if (!empty($cancellationText)) {
                $text = $cancellationText;
            }
        } catch (QUI\Exception $exception) {
            QUI\System\Log::addError($exception->getMessage());
        }
    }

    protected static function getText($Order, QUI\Projects\Project $Project): ?string
    {
        $OrderProcessCheckout = new QUI\ERP\Order\Controls\OrderProcess\Checkout([
            'Project' => $Project
        ]);

        /* @var $Step QUI\ERP\Order\Controls\OrderProcess\Checkout */
        $Address = $Order->getInvoiceAddress();
        $Customer = $Order->getCustomer();

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
}
