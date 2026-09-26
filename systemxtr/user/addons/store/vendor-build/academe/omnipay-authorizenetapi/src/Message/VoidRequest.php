<?php

namespace Store\Dependency\Omnipay\AuthorizeNetApi\Message;

use Store\Dependency\Academe\AuthorizeNet\Amount\MoneyPhp;
use Store\Dependency\Academe\AuthorizeNet\Amount\Amount;
use Store\Dependency\Academe\AuthorizeNet\AmountInterface;
use Store\Dependency\Academe\AuthorizeNet\Request\Transaction\VoidTransaction;
use Store\Dependency\Academe\AuthorizeNet\Request\Model\Order;
class VoidRequest extends AbstractRequest
{
    /**
     * Return the complete message object.
     */
    public function getData()
    {
        // Identify the original transaction being voided.
        $refTransId = $this->getTransactionReference();
        $transaction = new VoidTransaction($refTransId);
        return $transaction;
    }
    /**
     * Accept a transaction and sends it as a request.
     *
     * @param $data TransactionRequestInterface
     * @returns CaptureResponse
     */
    public function sendData($data)
    {
        $response_data = $this->sendTransaction($data);
        return new Response($this, $response_data);
    }
}
