<?php

namespace Store\Dependency\Omnipay\Mollie\Message\Response;

use Store\Dependency\Omnipay\Common\ItemBag;
use Store\Dependency\Omnipay\Mollie\Item;
/**
 * @see https://docs.mollie.com/reference/v2/payments-api/get-order
 */
class FetchOrderResponse extends FetchTransactionResponse
{
    public function getLines()
    {
        if (isset($this->data['lines'])) {
            return $this->data['lines'];
        }
        return null;
    }
    public function getItems()
    {
        if (isset($this->data['lines'])) {
            $items = [];
            foreach ($this->data['lines'] as $line) {
                $items[] = new Item($line);
            }
            return new ItemBag($items);
        }
    }
}
