<?php

namespace Omnipay\CorvusPay\Message;

/**
 * Complete a purchase after the buyer is redirected back from CorvusPay.
 * Data comes from the query string / POST body of the return URL.
 */
class CompletePurchaseRequest extends PurchaseRequest
{
    public function getData()
    {
        // CorvusPay returns parameters on the success/cancel URL
        // (GET or POST depending on merchant portal setting).
        // Merge both so we cover either case.
        $data = array_merge(
            $this->httpRequest->query->all(),
            $this->httpRequest->request->all()
        );

        return $data;
    }

    public function sendData($data)
    {
        return $this->response = new CompletePurchaseResponse($this, $data);
    }
}
