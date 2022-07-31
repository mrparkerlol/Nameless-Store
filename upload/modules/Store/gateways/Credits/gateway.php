<?php
/**
 * Credits_Gateway class
 *
 * @package Modules\Store
 * @author Partydragen
 * @version 2.0.0-pr13
 * @license MIT
 */
class Credits_Gateway extends GatewayBase {

    public function __construct() {
        $name = 'Store Credits';
        $settings = ROOT_PATH . '/modules/Store/gateways/Credits/gateway_settings/settings.php';

        parent::__construct($name, $settings);
    }

    public function onCheckoutPageLoad(TemplateBase $template, Customer $customer): void {
        if (!$customer->exists()) {
            $this->setEnabled(false);
            return;
        }

        $this->setDisplayname(
            Store::getLanguage()->get('general', 'pay_with_credits', [
                'currency_symbol' => Store::getCurrencySymbol(),
                'currency' => Store::getCurrency(),
                'credits' => $customer->getCredits()
            ])
        );
    }

    public function processOrder(Order $order): void {
        $customer = $order->customer();

        if ($customer->exists() && $customer->getCredits() >= $order->getAmount()->getTotal()) {
            $customer->removeCents(Store::toCents($amount_to_pay));

            $payment = new Payment();
            $payment->handlePaymentEvent('COMPLETED', [
                'order_id' => $order->data()->id,
                'gateway_id' => $this->getId(),
                'amount' => $amount_to_pay,
                'transaction' => 'Credits',
                'currency' => $currency,
                'fee' => 0
            ]);

            $shopping_cart = new ShoppingCart();
            $shopping_cart->clear();
            Redirect::to(URL::build(Store::getStorePath() . '/checkout/', 'do=complete'));
        } else {
            $this->addError('You don\'t have enough credits to complete this order!');
        }
    }

    public function handleReturn(): bool {
        return false;
    }

    public function handleListener(): void {

    }
}

$gateway = new Credits_Gateway();