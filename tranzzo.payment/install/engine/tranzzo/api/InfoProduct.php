<?php
namespace Tranzzo\Api;

class InfoProduct
{
    private $item = array();

    public function setProductId($value = '')
    {
        $this->item['id'] = strval($value);
    }

    public function setProductName($value = '')
    {
        $this->item['name'] = strval($value);
    }
    public function setCurrency($value = '')
    {
        $this->item['currency'] = strval($value);
    }
    public function setAmount($value = '')
    {
        $this->item['amount'] = Payment::amountToDouble($value);
    }

    public function setQuantity($value = '')
    {
        $this->item['qty'] = (int)$value;
    }

    public function setProductURL($value = '')
    {
        if(strpos($value, 'http') !== false)
            $this->item['url'] = $value;
    }

    public function get()
    {
        return $this->item;
    }

    public function reset()
    {
        $this->item = array();
    }
}