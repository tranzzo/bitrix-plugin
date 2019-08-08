<?php
namespace Tranzzo\Api;

class InfoProduct
{
    private $item = [];

    public function setProductId($value = '')
    {
        $this->item['id'] = strval($value);
    }

    public function setProductName($value = '')
    {
        $this->item['name'] = strval($value);
    }

    public function setProductCategory($value = '')
    {
        $this->item['category'] = strval($value);
    }

    public function setProductDescription($value = '')
    {
        $this->item['description'] = strval($value);
    }

    public function setCurrency($value = '')
    {
        $this->item['currency'] = strval($value);
    }

    public function setAmount($value = 0.0)
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

	/**
     * Either VAT(value-added tax) or NET
     * @param bool|true $isVAT
     */
    public function setPriceType($isVAT = true)
    {
        $this->item['price_type'] = $isVAT ? 'VAT' : 'NET';
    }

	/**
     * VAT - Value Added Tax
     * @param float $value
     */
    public function setVat($value = 0.0)
    {
        $this->item['vat'] = Payment::amountToDouble($value);
    }

	/**
     * Field for custom data. Max 4000 symbols.
     * @param string $value
     */
    public function setPayload($value = '')
    {
        if(!empty($value)) {
            $str = strval($value);
            $this->item['payload'] = (strlen($value) > 4000) ? substr($value, 0, 3999) : $str;
        }
    }

    public function get()
    {
        return $this->item;
    }

    public function reset()
    {
        $this->item = [];
    }
}