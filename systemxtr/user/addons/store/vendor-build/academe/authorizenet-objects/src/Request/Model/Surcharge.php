<?php

namespace Store\Dependency\Academe\AuthorizeNet\Request\Model;

/**
 *
 */
use Store\Dependency\Academe\AuthorizeNet\TransactionRequestInterface;
use Store\Dependency\Academe\AuthorizeNet\PaymentInterface;
use Store\Dependency\Academe\AuthorizeNet\AmountInterface;
use Store\Dependency\Academe\AuthorizeNet\AbstractModel;
class Surcharge extends AbstractModel
{
    protected $amount;
    protected $description;
    public function __construct(AmountInterface $amount = null, $description = null)
    {
        parent::__construct();
        if ($amount !== null) {
            $this->setAmount($amount);
        }
        $this->setDescription($description);
    }
    public function hasAny()
    {
        return $this->hasAmount() || $this->hasDescription();
    }
    public function jsonSerialize()
    {
        $data = [];
        if ($this->hasAmount()) {
            $data['amount'] = $this->getAmount();
        }
        if ($this->hasDescription()) {
            $data['description'] = $this->getDescription();
        }
        return $data;
    }
    protected function setAmount(AmountInterface $value)
    {
        $this->amount = $value;
    }
    protected function setDescription($value)
    {
        $this->description = $value;
    }
}
