<?php

namespace Store\Dependency\Academe\AuthorizeNet\Response\Collections;

/**
 * Collection of response messages, with an overall result code.
 */
use Store\Dependency\Academe\AuthorizeNet\Request\Model\HostedPaymentSetting;
use Store\Dependency\Academe\AuthorizeNet\Response\Model\SplitTenderPayment;
use Store\Dependency\Academe\AuthorizeNet\Response\HasDataTrait;
use Store\Dependency\Academe\AuthorizeNet\AbstractCollection;
class SplitTenderPayments extends AbstractCollection
{
    use HasDataTrait;
    public function __construct(array $data = [])
    {
        $this->setData($data);
        // An array of splitTenderPayment records.
        foreach ($this->getDataValue('splitTenderPayment') as $splitTenderPayment_data) {
            $this->push(new SplitTenderPayment($splitTenderPayment_data));
        }
    }
    protected function hasExpectedStrictType($item)
    {
        // Make sure the item is the correct type, and is not empty.
        return $item instanceof SplitTenderPayment;
    }
}
