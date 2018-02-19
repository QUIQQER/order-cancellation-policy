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
    /**
     * @param AbstractOrderingStep $Step
     * @param $text
     */
    public static function onQuiqqerOrderOrderProcessCheckoutOutput(AbstractOrderingStep $Step, &$text)
    {
        /* @var $Step QUI\ERP\Order\Controls\OrderProcess\Checkout */
        $Address = $Step->getOrder()->getInvoiceAddress();

        try {
            $Country = $Address->getCountry();
        } catch (QUI\Exception $exception) {
            return;
        }

        $Area = QUI\ERP\Areas\Utils::getAreaByCountry($Country);

        if (!$Area) {
            return;
        }

        if (!OCP::hasAreaCancellationPolicy($Area)) {
            return;
        }

        $text = QUI::getLocale()->get(
            'quiqqer/order-cancellation-policy',
            'ordering.step.checkout.checkoutAcceptText',
            [
                'terms_and_conditions' => $Step->getLinkOf('terms_and_conditions'),
                'revocation'           => $Step->getLinkOf('revocation')
            ]
        );
    }
}