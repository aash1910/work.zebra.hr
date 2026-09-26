<?php

namespace Omnipay\Check;

use Omnipay\Manual\Gateway as ManualGateway;

/**
 * Check / Bank transfer gateway
 */
class Gateway extends ManualGateway
{
    public function getName()
    {
        return 'Check / Bank transfer';
    }

    public function getDefaultParameters()
    {
        return array(
            'description' => '',
            'testMode'    => false,
            'adjustment'  => ''
        );
    }

    public function getAdjustment()
    {
        return $this->getParameter('adjustment');
    }

    public function setAdjustment($value)
    {
        return $this->setParameter('adjustment', $value);
    }
}
