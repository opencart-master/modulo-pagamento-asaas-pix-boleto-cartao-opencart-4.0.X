<?php
namespace Opencart\Catalog\Controller\Extension\Asaas\Payment;

class AsaasCartao  extends \Opencart\System\Engine\Controller {

	public function index(): string {
		$this->load->language('extension/asaas/payment/asaas_cartao');
		$this->load->model('checkout/order');
		$order_info = $this->model_checkout_order->getOrder($this->session->data['order_id']);
		$custom = json_decode($order_info['custom_field'],true);
		$custom2 = $order_info['payment_custom_field'];
        
		$parcela_total = $this->config->get('payment_asaas_cartao_parc');
		$parcela_sem_juros = $this->config->get('payment_asaas_cartao_parc1');
		$juros = $this->config->get('payment_asaas_cartao_juros');

		$data['vtotal'] = $this->currency->format($order_info['total'], $this->session->data['currency']);

		$data['language'] = $this->config->get('config_language');

		$data['parc'] = $this->calcularParcelamento($order_info['total'], $parcela_total, $parcela_sem_juros, $juros);
		$data['modo'] = $this->config->get('payment_asaas_cartao_mode');
		
		return $this->load->view('extension/asaas/payment/asaas_cartao', $data);
	}

	public function confirm(): void {
		$this->load->language('extension/asaas/payment/asaas_cartao');
		$json = [];
		
		if (isset($this->session->data['order_id'])) {
			$order_id = $this->session->data['order_id'];
		} else {
			$order_id = 0;
		}
		
		$keys = [
			'card_name',
			'card_number',
			'card_expire',
			'card_expire1',
			'card_cvv',
			'installments'
		];

		foreach ($keys as $key) {
			if (!isset($this->request->post[$key])) {
				$this->request->post[$key] = '';
			}
		}
		
		if ($order_id) {
			$this->load->model('checkout/order');

			$order_info = $this->model_checkout_order->getOrder($this->session->data['order_id']);

			if (!$order_info) {
				$json['redirect'] = $this->url->link('checkout/failure', 'language=' . $this->config->get('config_language'), true);

				unset($this->session->data['order_id']);
			}
		} else {
			$json['error']['warning'] = $this->language->get('error_order');
		}
		
		if (!$this->config->get('payment_asaas_cartao_status') || !isset($this->session->data['payment_method']) || $this->session->data['payment_method']['code'] != 'asaas_cartao.asaas_cartao') {
			$json['error']['warning'] = $this->language->get('error_payment_method');
		}
		
		if (!$this->request->post['card_name'] || strlen($this->request->post['card_name']) < 5) {
			$json['error']['card_name'] = $this->language->get('error_1');
		}
		
		if (!preg_match('/[0-9\s]{8,19}/', $this->request->post['card_number'])) {
			$json['error']['card_number'] = $this->language->get('error_2');
		}
		
		if (!$this->request->post['card_expire'] || strlen($this->request->post['card_expire']) < 2) {
			$json['error']['card_expire'] = $this->language->get('error_3');
		}
		
		if (!$this->request->post['card_expire1'] || strlen($this->request->post['card_expire1']) < 4) {
			$json['error']['card_expire1'] = $this->language->get('error_4');
		}
		
		if (strlen($this->request->post['card_cvv']) != 3) {
			$json['error']['card_cvv'] = $this->language->get('error_5');
		}
		
		if (!$this->request->post['installments'] || $this->request->post['installments'] == '') {
			$json['error']['installments'] = $this->language->get('error_6');
		}


        if (!$json) {
			$this->load->model('checkout/order');
			require_once DIR_EXTENSION . 'asaas/system/library/asaas/asaas_api.php';
			$order_info = $this->model_checkout_order->getOrder($this->session->data['order_id']);
			$custom = json_decode($order_info['custom_field'],true);
			$custom2 = $order_info['payment_custom_field'];

			if ($this->config->get('payment_asaas_cartao_mode')) {
			$mode = false;
		    } else {
			$mode = true;
		    }

			$asaas = new \Opencart\System\Library\Asaas\AsaasApi($this->config->get('payment_asaas_cartao_api_key'), $mode);

			$getcustomer = $asaas->getCustomer($order_info['email']);
			
			$doc = 0;
			$numero = '';

            if(isset($custom[$this->config->get('payment_asaas_cartao_doc')])) {
		    $doc =  $custom[$this->config->get('payment_asaas_cartao_doc')];
		    }
		    
		    if(isset($custom[$this->config->get('payment_asaas_cartao_doc1')])) {
		    $doc =  $custom[$this->config->get('payment_asaas_cartao_doc1')];
		    }
			
		    if(isset($custom2[$this->config->get('payment_asaas_cartao_number')])) {
		    $numero = $custom2[$this->config->get('payment_asaas_cartao_number')];
		    }

			if ($getcustomer['totalCount']) {
				$cid = $getcustomer['data'][0]['id'];
			} else {
				$customer = $asaas->createCustomer([
				"name" => $order_info['payment_firstname'] . ' ' . $order_info['payment_lastname'],
				"cpfCnpj" => $asaas->onlyNumbe($doc),
				"phone" => $asaas->onlyNumbe($order_info['telephone']),
				"mobilePhone" => $asaas->onlyNumbe($order_info['telephone']),
				"notificationDisabled" => true,
				"email" => $order_info['email']
				]);
				$cid = $customer['id'];
			}

			$vezes = explode(":", $this->request->post['installments']);

			if ($vezes[0] == 1) {
			$payment = $asaas->createPayment([
			"billingType" =>  "CREDIT_CARD",
			"customer" => $cid,
			"value" => $order_info['total'],
			"dueDate" => date('Y-m-d'),
			"description" => "Pedido " . $order_info['order_id'],
			"externalReference"	=> $order_info['order_id'],
			"installmentCount" => 1,
			"totalValue" =>  $order_info['total'],
			"installmentValue" => $order_info['total'],
			"creditCard" =>  array(
				"holderName" =>  $this->request->post['card_name'],
				"number" =>  $asaas->onlyNumbe($this->request->post['card_number']),
				"expiryMonth" => $this->request->post['card_expire'],
				"expiryYear" =>  $this->request->post['card_expire1'],
				"ccv" =>  $this->request->post['card_cvv']
			),
			"creditCardHolderInfo" =>  array(
				"name" =>  $order_info['payment_firstname'] . ' ' . $order_info['payment_lastname'],
				"email" =>  $order_info['email'],
				"cpfCnpj" =>  $asaas->onlyNumbe($doc),
				"postalCode" => $asaas->onlyNumbe($order_info['payment_postcode']),
				"addressNumber" =>  $numero,
				"phone" =>  $asaas->onlyNumbe($order_info['telephone']),
			),
			"remoteIp" =>  $this->request->server['REMOTE_ADDR'],
			"daysAfterDueDateToRegistrationCancellation" =>  1
			//"callback" => array("successUrl" => HTTPS_SERVER . "index.php?route=extension/payment/asaas_cartao/callback")
			]);

			} else if ($vezes[0] > 1 && $vezes[0] <= (int)$this->config->get('payment_asaas_cartao_parc1')) {
			$payment = $asaas->createPayment([
			"billingType" =>  "CREDIT_CARD",
			"customer" => $cid,
			"value" => $order_info['total'],
			"dueDate" => date('Y-m-d'),
			"description" => "Pedido " . $order_info['order_id'],
			"externalReference"	=> $order_info['order_id'],
			"installmentCount" =>  $vezes[0],
			"totalValue" =>  $order_info['total'],
			"creditCard" =>  array(
				"holderName" =>  $this->request->post['card_name'],
				"number" =>  $asaas->onlyNumbe($this->request->post['card_number']),
				"expiryMonth" => $this->request->post['card_expire'],
				"expiryYear" =>  $this->request->post['card_expire1'],
				"ccv" =>  $this->request->post['card_cvv']
			),
			"creditCardHolderInfo" =>  array(
				"name" =>  $order_info['payment_firstname'] . ' ' . $order_info['payment_lastname'],
				"email" =>  $order_info['email'],
				"cpfCnpj" =>  $asaas->onlyNumbe($doc),
				"postalCode" => $asaas->onlyNumbe($order_info['payment_postcode']),
				"addressNumber" =>  $numero,
				"phone" =>  $asaas->onlyNumbe($order_info['telephone']),
			),
			"remoteIp" =>  $this->request->server['REMOTE_ADDR'],
			"daysAfterDueDateToRegistrationCancellation" =>  1
			//"callback" => array("successUrl" => HTTPS_SERVER . "index.php?route=extension/payment/asaas_cartao/callback")
			]);
            
			} else {
			$payment = $asaas->createPayment([
			"billingType" =>  "CREDIT_CARD",
			"customer" => $cid,
			"value" => $order_info['total'],
			"dueDate" => date('Y-m-d'),
			"description" => "Pedido " . $order_info['order_id'],
			"externalReference"	=> $order_info['order_id'],
			"installmentCount" =>  $vezes[0],
			"totalValue" =>  ($vezes[0] * $vezes[1]),
			"installmentValue" => $vezes[1],
			"creditCard" =>  array(
				"holderName" =>  $this->request->post['card_name'],
				"number" =>  $asaas->onlyNumbe($this->request->post['card_number']),
				"expiryMonth" => $this->request->post['card_expire'],
				"expiryYear" =>  $this->request->post['card_expire1'],
				"ccv" =>  $this->request->post['card_cvv']
			),
			"creditCardHolderInfo" =>  array(
				"name" =>  $order_info['payment_firstname'] . ' ' . $order_info['payment_lastname'],
				"email" =>  $order_info['email'],
				"cpfCnpj" =>  $asaas->onlyNumbe($doc),
				"postalCode" => $asaas->onlyNumbe($order_info['payment_postcode']),
				"addressNumber" =>  $numero,
				"phone" =>  $asaas->onlyNumbe($order_info['telephone']),
			),
			"remoteIp" =>  $this->request->server['REMOTE_ADDR'],
			"daysAfterDueDateToRegistrationCancellation" =>  1
			//"callback" => array("successUrl" => HTTPS_SERVER . "index.php?route=extension/payment/asaas_cartao/callback")
			]);
			}

            $comment = "";

			if (isset($payment['id'])) {
			$this->cadId($payment['id'], $order_info['order_id']);
		    $comment .= "Pagamento ID: " . $payment['id'] . "\n";
		    $comment .= "Cartão: " . $payment['creditCard']['creditCardBrand'] . "\n";
			$comment .= "Parcelas: " . $vezes[0] . "X \n";
			$this->model_checkout_order->addHistory($this->session->data['order_id'], $this->config->get('payment_asaas_cartao_order_status_id'), $comment, true);
		    $json['redirect'] = $this->url->link('checkout/success', 'language=' . $this->config->get('config_language'), true);
			} 

			if (isset($payment['errors'])) {
			$this->log->write('Pedido Nº ' . $order_info['order_id'] . ' - ' . json_encode($payment['errors']));
            $json['warning'] = $payment['errors'][0]['description'];
			}
	
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));	
	}

	private function cadId($id, $order_id) {
		$order_query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "asaas_callback` WHERE order_id = '" . (int)$order_id . "'");
		if ($order_query->num_rows) {
		} else {
    	$this->db->query("INSERT INTO `" . DB_PREFIX . "asaas_callback` SET order_id = '" . (int)$order_id . "', pay_id = '" . $this->db->escape($id) . "', type = 'CARTAO', date_create = NOW()");
		}
	}

	public function calcularParcelamento($valorTotal, $maxParcelas, $parcelasSemJuros, $taxaJuros) {
    	$parcelas = [];

		for ($i = 1; $i <= $maxParcelas; $i++) {
			if ($i <= $parcelasSemJuros) {
				$valorParcela = $valorTotal / $i;
				$valorTotalParcela = $valorTotal;
				$temJuros = false;
			} else {
				$taxa = $taxaJuros / 100;
				$valorParcela = ($valorTotal * $taxa) / (1 - pow(1 + $taxa, -$i));
				$valorTotalParcela = $valorParcela * $i;
				$temJuros = true;
			}

			$parcelas[] = [
				'parcelas' => $i,
				'valor_parcela' => $this->currency->format(round($valorParcela, 2), $this->session->data['currency']),
				'preco' => round($valorParcela, 2),
				'valor_total' => round($valorTotalParcela, 2),
				'tem_juros' => $temJuros
			];
		}

		return $parcelas;
	}
}