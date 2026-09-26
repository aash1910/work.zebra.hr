<?php

namespace Store\Update;

use Store\Update;

class Update5002
{
    // Deactivate all old payment gateways that are no longer supported
    public function up()
    {
        $unsupportedGateways = [
            "AuthorizeNet_AIM",
            "AuthorizeNet_SIM",
            "Buckaroo",
            "Buckaroo_Ideal",
            "Buckaroo_PayPal",
            "CardSave",
            "Coinbase",
            "Eway_Rapid",
            "GoCardless",
            "Migs_ThreeParty",
            "Migs_TwoParty",
            "MultiSafepay",
            "Netaxept",
            "PayFast",
            "Payflow_Pro",
            "PaymentExpress_PxPay",
            "PaymentExpress_PxPost",
            "Pin",
            "SecurePay_DirectPost",
            "Stripe",
            "TargetPay_Directebanking",
            "TargetPay_Ideal",
            "TargetPay_Mrcash",
            "TwoCheckout",
            "WorldPay",
            "Mollie_Ideal",
            "NetBanx",
            "NetBanx_Hosted",
            "FirstData_Connect",
            "FirstData_Global",
            "FirstData_Telecheck",
            "Ogone_Ecommerce",
            "Sisow",
            "Barclaycardepdq_Ecommerce"
        ];

        $sql = 'UPDATE ' . ee()->db->protect_identifiers('store_payment_methods', true) .
            'SET enabled = 0
            WHERE class in ("' . implode('","', $unsupportedGateways) . '")';
        ee()->db->query($sql);
    }
}
