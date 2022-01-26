<?php

namespace Kavinda\SilverShop\Controller;

use Kavinda\SilverShop\Extension\SilverShopSCAGatewayInfoExtension;
use SilverShop\Checkout\Checkout;
use SilverShop\Extension\ShopConfigExtension;
use SilverShop\Page\CheckoutPage;
use SilverShop\Page\CheckoutPageController;
use SilverStripe\Control\Controller;
use SilverStripe\Control\Director;
use SilverStripe\Core\Config\Config;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\Form;
use SilverStripe\Forms\FormAction;
use SilverStripe\Forms\HiddenField;
use SilverStripe\Forms\LiteralField;
use SilverStripe\Omnipay\GatewayInfo;
use SilverStripe\Omnipay\Model\Payment;
use SilverStripe\ORM\ValidationResult;
use SilverStripe\View\Requirements;
use Stripe\Customer;
use Stripe\PaymentIntent;
use Stripe\Stripe;

class SilverShopSCACheckoutPageController extends CheckoutPageController
{
    private static $url_segment = 'checkout';

    private static $allowed_actions = array(
        'OrderForm',
        'payment',
        'PaymentForm',
    );

    public function PaymentForm()
    {
        Requirements::javascript('https://js.stripe.com/v3/');
        Requirements::javascript('kavinda/silvershop-sca-stripe:javascript/payment.js');

        if (!(bool)$this->Cart()) {
            return false;
        }

        $order = $this->Cart();

        if (!$order) {
            return false;
        }

        $gateway = Checkout::get($order)->getSelectedPaymentMethod(false);

        if ($gateway !== 'Stripe') {
            return false;
        }

        if (Config::inst()->get(SilverShopSCAGatewayInfoExtension::class, 'is_support_sca') !== true) {
            return false;
        }

        Stripe::setApiKey(GatewayInfo::getParameters($gateway)['apiKey']);
        /* @var $payment Payment */
        $payment = null;
        $intent = null;

        /* @var $intent PaymentIntent */

        if (!$this->getRequest()->isPOST()) {

            $customer = Customer::create([
                'email' => $order->Email,
                'name' => $order->FirstName . ' ' . $order->Surname,
                'description' => $this->getDescription($order)
            ]);

            $intent = PaymentIntent::create([
                'amount' => $order->TotalOutstanding(true) * 100,
                'currency' => ShopConfigExtension::config()->base_currency,
                'customer' => $customer->id,
                'description' => $this->getDescription($order),
            ]);

            $payment = Payment::create()->init(
                $gateway,
                $order->TotalOutstanding(true),
                ShopConfigExtension::config()->base_currency
            );
            $payment->write();

            $payment->StripeIntent = serialize($intent);
            $payment->StripPaymentIntentID = $intent->id;
            $payment->write();

        }

        $fields = FieldList::create([
            HiddenField::create('PaymentID', 'PaymentID')->setValue($payment ? $payment->ID : 0),
            HiddenField::create('PaymentMethod', ''),
            LiteralField::create('PaymentCard', <<<HTML
<div id="card-element">
<!-- Elements will create input elements here -->
</div>
<!-- We'll put the error messages in this element -->
<div id="card-errors" role="alert"></div>
HTML
            )
        ]);

        $actions = FieldList::create(
            FormAction::create('doPayment', 'Submit Payment')
        );
        $form = Form::create($this, 'PaymentForm', $fields, $actions);
        $form->addExtraClass('js-payment-form');
        $form->setAttribute('data-stripe', GatewayInfo::getParameters($gateway)['publishableKey']);
        if ($intent) {
            $form->setAttribute('data-secret', $intent->client_secret);
        }
        return $form;
    }

    public function doPayment($data, Form $form)
    {
        $order = $this->Cart();
        $payment = Payment::get()->byID($data['PaymentID']);

        if (empty($data['PaymentMethod'])) {
            $form->sessionMessage('Sorry, there was an error processing your payment. Please complete the payment form to try again.', ValidationResult::TYPE_ERROR);
            return $this->redirectBack();
        }
        $payment->Status = 'Captured';

        $payment->onPaymentCaptured();
        $payment->setSuccessUrl($this->generatePaymentSuccessURL($order));
        $payment->setFailureUrl($this->getCancelURL());
        $payment->write();

        if ($payment) {
                $order->Payments()->add($payment);
            }

        session_regenerate_id();
        return $this->redirect($payment->SuccessUrl);
    }

    public function getDescription($order)
    {
        $description = $order->FirstName . ' ' . $order->LastName . ' | ';
        if ($order->BillingAddress()->Company) {
            $description .= (string)$order->BillingAddress()->Company . ' | ';
        }
        $description .= $order->Email . ' | ' . $order->ID . ' ';

        return $description;
    }

    public function generatePaymentSuccessURL($order)
    {
        return Controller::join_links(
            Director::absoluteBaseURL(),
            CheckoutPage::find_link(),
            'order',
            $order->ID
        );
    }

    public function getCancelURL()
    {
        return Controller::join_links(
            Director::absoluteBaseURL(),
            CheckoutPage::singleton()->Link()
        );
    }


}
