<?php

class LotomaticReceiver {

    protected $merchantKey = null;
    protected $headers;
    protected $rawData;
    protected $data;

    public function __construct(Player $player, $merchantKey, array $headers, string $rawData) {
        $this->merchantKey = $merchantKey;


        $this->checkHeaders($headers);
        $this->checkXSign($headers, $rawData);

        $data = (array) json_decode($rawData, true);
        $this->checkData($data);

        $this->player = $player;
    }

    protected function checkHeaders($headers) {

        $requiredAuthHeaders = ['HTTP_X_MERCHANT_ID', 'HTTP_X_TIMESTAMP', 'HTTP_X_SIGN'];
        foreach ($requiredAuthHeaders as $headerName) {
            if (!isset($headers[$headerName])) {
                $errMessage = $headerName . ' header is missing';
                throw new \Exception($errMessage);
            }
        }

        if (preg_match('/\D+/', $headers['HTTP_X_TIMESTAMP'])) {
            throw new \Exception('X-Timestamp header isn\'t correct (pattern).');
        }

        $providerTime = $headers['HTTP_X_TIMESTAMP'];
        $time = time();
        if (abs($providerTime - $time) > 30000) {
            throw new \Exception('X-Timestamp header isn\'t correct (wrong time)');
        }
    }

    protected function checkXSign($headers, $rawData) {

        $xSign = $headers['HTTP_X_SIGN'];

        $hashString = $rawData . $headers['HTTP_X_TIMESTAMP'];

        $expectedSign = hash_hmac('sha1', $hashString, $this->merchantKey);

        if ($xSign !== $expectedSign) {
            throw new \Exception('X-Sign header is wrong');
        }
    }

    protected function checkData(array $data) {
        if (!isset($data['action'])) {
            throw new \Exception('Field \'action\' is missing');
        } elseif (!in_array($data['action'], ['balance', 'bet', 'win', 'refund', 'session-stop'])) {
            throw new \Exception('Action ' . $data['action'] . ' not found');
        }
    }

    public function sessionStop(array $data) {
        $this->player->sessionStop($data);
        return (object) [];
    }

    public function balance(array $data) {
        return new SuccessResponse($this->player->getBalance($data['player_id'], $data['currency']));
    }

    public function bet(array $data) {
        $transaction = $this->player->bet($data);
        $balance = $this->player->getBalance($data['player_id'], $data['currency']);
        return new SuccessResponse($balance, $transaction);
    }

    public function win(array $data) {
        $transactions = $this->player->win($data);
        $balance = $this->player->getBalance($data['player_id'], $data['currency']);
        $response = new SuccessResponse($balance);
        $response->transactions = $transactions;
        return $response;
    }

    public function refund(array $data) {
        $transactions = $this->player->refund($data);
        $balance = $this->player->getBalance($data['player_id'], $data['currency']);
        $response = new SuccessResponse($balance);
        $response->transactions = $transactions;
        return $response;
    }

}
