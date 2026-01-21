<?php
namespace Opencart\System\Library\Asaas;
class AsaasApi {
    private $api_key;
    private $base_url;
    private $token;
    private $origin;

    public function __construct($api_key, $sandbox = true) {
        $this->api_key = $api_key;
        $this->base_url = $sandbox ? 'https://sandbox.asaas.com/api/v3/' : 'https://www.asaas.com/api/v3/';
        $this->token = $sandbox ?  base64_decode('JGFzYWFzX2hvbW9sb2dfb3JpZ2luX2NoYW5uZWxfa2V5X05UaG1OemxpWVdSaE1tVTFPRFZoWm1KbE1qazVNMlJsWXpnd05qTmxaR1U2T2pnM09HUTBaV1V4TFRBek1XRXRORGxoWkMwNU5qZzNMVE5tT1dWaE5HSTNZek5tTnpvNmIyTnJhR1U1T0RVeE0yVTBMVGc0WlRRdE5HWmtaaTA1TldKbExXRmxaRGMwT1RZMFpEVmxPUT09') : base64_decode('JGFzYWFzX3Byb2Rfb3JpZ2luX2NoYW5uZWxfa2V5X05UaG1OemxpWVdSaE1tVTFPRFZoWm1KbE1qazVNMlJsWXpnd05qTmxaR1U2T2pjd01XUXdOR1ExTFRFd1l6TXRORGcwTmkwNFpHVmxMVFEyTm1GalptSXhNekZpTVRvNmIyTnJhRE5tWkRBeVltVmhMV1ZqWXpjdE5HUTROQzFoTURFMkxXRTBOemMxTVRaak1ESTNaZz09');
        $this->origin = base64_decode('T1BFTkNBUlRfTUFTVEVS');
    }

    private function request($method, $endpoint, $data = []) {
        $soap_do = curl_init($this->base_url . $endpoint);
        curl_setopt($soap_do, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($soap_do, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Origin: ' . $this->origin,
            'Origin-Channel-Access-Token: ' . $this->token,
            'User-Agent: ' . base64_decode('TWFzdGVyLzEuMC4wLjAgKFBsYXRhZm9ybWEgb3BlbmNhcnQuY29tIC0gREVWIE9wZW5jYXIgTWFzdGVyKQ=='),
            'access_token: ' . $this->api_key
        ]);
        if ($method !== 'GET') {
            curl_setopt($soap_do, CURLOPT_CUSTOMREQUEST, $method);
            curl_setopt($soap_do, CURLOPT_POSTFIELDS, json_encode($data));
        }
        $response = curl_exec($soap_do);
        curl_close($soap_do);
        return json_decode($response, true);
    }

    public function createCustomer($data) {
        return $this->request('POST', 'customers', $data);
    }

    public function createPayment($data) {
        return $this->request('POST', 'payments', $data);
    }

    public function getPayment($id) {
        return $this->request('GET', 'payments/' . $id);
    }

    public function getCustomer($email) {
        return $this->request('GET', 'customers?email=' . $email);
    }

    public function onlyNumbe($numeber) {
        return preg_replace("/[^0-9]/", '', $numeber);
    }
}