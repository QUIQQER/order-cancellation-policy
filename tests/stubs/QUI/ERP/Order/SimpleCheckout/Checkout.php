<?php

namespace QUI\ERP\Order\SimpleCheckout;

if (!class_exists(Checkout::class)) {
    class Checkout
    {
        public function getOrder(): mixed
        {
            return null;
        }
    }
}
