<?php
namespace Tranzzo\Api;

class ResponseParams
{
	private $data = [];

	/*
	Response params
	*/

	const POS_ID = 'pos_id';//Merchant's identifier
	const MODE = 'mode';
	const METHOD = 'method';

	//provider_order_id - order_id in store
	const PROVIDER_ORDER_ID = 'provider_order_id';

	const OPERATION_ID = 'operation_id';//Unique Tranzzo capture identifier

	const PAYMENT_ID = 'payment_id';//Tranzzo payment identifier of primary operation

	const ORDER_ID = 'order_id';//Merchant's order_id of primary operation

	const TRANSACTION_ID = 'transaction_id';//Unique Tranzzo transaction identifier

	const AMOUNT = 'amount';

	const CURRENCY = 'currency';

	const STATUS = 'status';//Transaction status

	const STATUS_CODE = 'status_code';//Tranzzo payment status code

	const STATUS_DESCRIPTION = 'status_description';

	const CREATED_AT = 'created_at';//Timestamp when transaction was created

	const PROCESSING_TIME = 'processing_time';//Timestamp when transaction was updated last time

	const MESSAGE = 'message';//failed response

	const PAYLOAD = 'payload';

	const BILL_ORDER_ID = 'billing_order_id';

	// old version params
	const CODE = 'code';
	const RESPONSE_CODE = 'response_code';
	const RESPONSE_DESCRIPTION = 'response_description';


	public function __construct($data = [])
	{
        $this->data = $data;
    }

	private function get($key = null)
	{
		return isset($this->data[$key]) ? $this->data[$key] : null;
	}

	public function getPosId()
	{
		return $this->get(self::POS_ID);
	}

	public function getMode()
	{
		return $this->get(self::MODE);
	}

	public function getMethod()
	{
		return $this->get(self::METHOD);
	}

	public function getProvOrderId()
	{
		return $this->get(self::PROVIDER_ORDER_ID);
	}

	public function getOperationId()
	{
		return $this->get(self::OPERATION_ID);
	}

	public function getPaymentId()
	{
		return $this->get(self::PAYMENT_ID);
	}

	public function getOrderId()
	{
		return $this->get(self::ORDER_ID);
	}

	public function getTransactionId()
	{
		return $this->get(self::TRANSACTION_ID);
	}

	public function getAmount()
	{
		return $this->get(self::AMOUNT);
	}

	public function getCurrency()
	{
		return $this->get(self::CURRENCY);
	}

	public function getStatus()
	{
		return $this->get(self::STATUS);
	}

	public function getStatusCode()
	{
		return $this->get(self::STATUS_CODE);
	}

	public function getStatusDescription()
	{
		return $this->get(self::STATUS_DESCRIPTION);
	}

	public function getCreatedAt()
	{
		return $this->get(self::CREATED_AT);
	}

	public function getProcessingTime()
	{
		return $this->get(self::PROCESSING_TIME);
	}

	public function getResponseCode()
	{
		return $this->get(self::RESPONSE_CODE);
	}

	public function getResponseDescription()
	{
		return $this->get(self::RESPONSE_DESCRIPTION);
	}

	public function getMessage()
	{
		return $this->get(self::MESSAGE);
	}

	public function getPayload()
	{
		return is_null($this->get(self::PAYLOAD))? null : Payment::parsePayload($this->get(self::PAYLOAD));
	}

	public function getData()
	{
		return $this->data;
	}

	public function setData($data = [])
	{
		$this->data = $data;
	}

	public function reset()
	{
		$this->data = [];
	}

    public function getBillOrderId()
    {
        return $this->get(self::BILL_ORDER_ID);
    }
}