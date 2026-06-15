<?php

namespace QUI\ERP\Order\SimpleCheckout;

if (!class_exists(Checkout::class)) {
    class Checkout
    {
        public function getOrder(): ?\QUI\ERP\Order\AbstractOrder
        {
            return null;
        }
    }
}
