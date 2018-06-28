<?php

class LotomaticConnector
{
    protected $merchantID = null;

    protected $merchantKey = null;

    protected $gateURL = null;

    public function __construct($merchantID, $merchantKey, $host)
    {
        $this->setMerchantID($merchantID);
        $this->setMerchantKey($merchantKey);
        $this->setHost($host);
    }

    private function setMerchantID($merchantID)
    {
        if (empty($merchantID)) {
            throw new Exception("LotomaticConnector: MerchantID is empty");
        }
        $this->merchantID = $merchantID;
    }

    private function setMerchantKey($merchantKey)
    {
        if (empty($merchantKey)) {
            throw new Exception("LotomaticConnector: MerchantKey is empty");
        }
        $this->merchantKey = $merchantKey;
    }

    private function setHost($host)
    {
        if (empty($host)) {
            throw new Exception("LotomaticConnector: Host is empty");
        }
        $this->gateURL = $host;
    }

    private function parseHeaders($header_text)
    {
        $headers = array();

        foreach (explode("\r\n", $header_text) as $i => $line)
        {
            if ($i === 0)
                $headers['http_code'] = $line;
            else {
                list ($key, $value) = explode(': ', $line);
                $headers[$key] = $value;
            }
        }

        return $headers;
    }

    private function getURL($uri)
    {
        return $this->gateURL . '/' . $uri;
    }

    private function makeSign($data, $timestamp)
    {
        return hash_hmac('sha1', $data . $timestamp, $this->merchantKey);
    }

    private function sendRequest($uri, $params = null)
    {
        $url = $this->getUrl($uri);
        $data = json_encode($params);
        $timestamp = time();

        $client = curl_init($url);
        curl_setopt_array($client, array(
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HEADER => true,
            CURLOPT_POST => true,
            CURLOPT_VERBOSE => false,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'X-Merchant-Id: ' . $this->merchantID,
                'X-Timestamp: ' . $timestamp,
                'X-Sign: ' . $this->makeSign($data, $timestamp)
            ],
            CURLOPT_POSTFIELDS => $data,
        ));

        $response = curl_exec($client);
        if ($response === false) {
            curl_close($client);
            throw new Exception("LotomaticConnector: Connection error");
        }

        list($header_text, $body) = explode("\r\n\r\n", $response, 2);

        $responseCode = curl_getinfo($client, CURLINFO_RESPONSE_CODE);
        if ($responseCode != 200) {
            curl_close($client);
            throw new Exception("LotomaticConnector: Server returned error code " . $responseCode);
        }

        curl_close($client);

        if (empty($body)) {
            throw new Exception("LotomaticConnector: Empty response");
        }

        $headers = $this->parseHeaders($header_text);
        if (empty($headers["X-Timestamp"]) or empty($headers["X-Merchant-Id"]) or empty($headers["X-Sign"])) {
            throw new Exception("LotomaticConnector: Required headers absent in response");
        }

        if ($this->merchantID !== $headers["X-Merchant-Id"]) {
            throw new Exception("LotomaticConnector: Invalid merchantID in response");
        }

        $sign = $this->makeSign($body, $headers["X-Timestamp"]);
        if ($sign !== $headers["X-Sign"]) {
            throw new Exception("LotomaticConnector: Invalid signature in response");
        }

        $decodedBody = json_decode($body, true);
        if (is_null($decodedBody)) {
            throw new Exception("LotomaticConnector: JSON parser error: " . $body);
        }

        return $decodedBody;
    }

    public function getGames()
    {
        return $this->sendRequest("games");
    }

    public function gameInit($game_uuid, $player_id, $player_name, $return_url = "", $language = "en", $email = "")
    {
        return $this->sendRequest("games/init", [
            "game_uuid" => (int)$game_uuid,
            "player_id" => (int)$player_id,
            "player_name" => $player_name,
            "return_url" => $return_url,
            "language" => $language,
            "email" => $email
        ]);
    }

    public function gameInitDemo($game_uuid, $player_id, $player_name, $return_url = "", $language = "en")
    {
        return $this->sendRequest("games/init-demo", [
            "game_uuid" => (int)$game_uuid,
            "player_id" => (int)$player_id,
            "player_name" => (string)$player_name,
            "return_url" => $return_url,
            "language" => $language
        ]);
    }

    public function balanceUpdate($session, $balance)
    {
        return $this->sendRequest("balance-update", [
            "balance" => (int)$balance,
            "session" => $session
        ]);
    }
}