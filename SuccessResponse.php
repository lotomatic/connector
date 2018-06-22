<?php

class SuccessResponse {

    public $balance;
    public $transaction_id;
    public $transactions = null;
    public $error = false;

    /**
     * SuccessResponse constructor.
     *
     * @param double $balance
     * @param string $transactionId {optional}
     */
    public function __construct($balance, $transactionId = null) {
        $this->balance = (int)$balance;
        $this->transaction_id = (string)$transactionId;
    }

}
