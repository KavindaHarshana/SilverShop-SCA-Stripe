<?php

namespace Kavinda\SilverShop\Extension;

use SilverShop\Cart\ShoppingCart;
use SilverShop\Checkout\OrderProcessor;
use SilverStripe\Core\Config\Configurable;
use SilverStripe\ORM\DataExtension;
use SilverStripe\ORM\FieldType\DBDatetime;

class SilverShopSCAPaymentExtension extends DataExtension
{
    private static $db = [
        'StripeIntent' => 'Text',
        'StripPaymentIntentID' => 'Text',
        'RenewSuccessUrl' => 'Text'
    ];

    private static $api_key = '';
    private static $publishable_key = '';

    use Configurable;

    public function onPaymentCaptured($response = null)
    {
        $order = $this->Cart();
        
        $order->Placed = DBDatetime::now()->Rfc2822();;
        
        if ($order && $order->exists()) {
            OrderProcessor::create($order)->completePayment();
        }

        $order->Status = 'Paid';
        $order->write();
    }

    public function getIdentifierToken()
    {
        return md5(implode('///', [
            $this->owner->Created,
            $this->owner->ID
        ]));
    }

    public function Cart()
    {
        $order = ShoppingCart::curr();
        if (!$order || !$order->Items() || !$order->Items()->exists()) {
            return false;
        }

        return $order;
    }
}
