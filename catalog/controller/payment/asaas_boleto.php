<?php
namespace Opencart\Catalog\Controller\Extension\Asaas\Payment;

class AsaasBoleto extends \Opencart\System\Engine\Controller {
	public function index(): string {
		$this->load->language('extension/asaas/payment/asaas_boleto');

		$data['modo'] = $this->config->get('payment_asaas_boleto_mode');
		
		$data['language'] = $this->config->get('config_language');

		return $this->load->view('extension/asaas/payment/asaas_boleto', $data);
	}

	public function confirm(): void {
		$this->load->language('extension/asaas/payment/asaas_boleto');
		require_once DIR_EXTENSION . 'asaas/system/library/asaas/asaas_api.php';

		$json = [];
        
		if (!isset($this->session->data['order_id'])) {
			$json['error'] = $this->language->get('error_order');
		}

		if (!isset($this->session->data['payment_method']) || $this->session->data['payment_method']['code'] != 'asaas_boleto.asaas_boleto') {
			$json['error'] = $this->language->get('error_payment_method');
		}

		$this->load->model('checkout/order');
		$order_info = $this->model_checkout_order->getOrder($this->session->data['order_id']);
		$custom = json_decode($order_info['custom_field'],true);

		if ($this->config->get('payment_asaas_boleto_mode')) {
			$mode = false;
		} else {
			$mode = true;
		}
		
		$asaas = new \Opencart\System\Library\Asaas\AsaasApi($this->config->get('payment_asaas_boleto_api_key'), $mode);

		$getcustomer = $asaas->getCustomer($order_info['email']);
		
            if(isset($custom[$this->config->get('payment_asaas_boleto_doc')])) {
		        $doc =  $custom[$this->config->get('payment_asaas_boleto_doc')];
		    }
		    
            if(isset($custom[$this->config->get('payment_asaas_boleto_doc1')])) {
		        $doc =  $custom[$this->config->get('payment_asaas_boleto_doc1')];
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

				if(isset($customer['id'])){
				$cid = $customer['id'];
				} else {
				$cid = 0;	
				}

				if (isset($customer['errors'])) {
				$this->log->write('Pedido Nº ' . $order_info['order_id'] . ' - ERROR: ' . $customer['errors'][0]['description']);
            	$json['error'] = $customer['errors'][0]['description'];
				}
			}

			$payment = $asaas->createPayment([
			"customer" => $cid,
			"billingType" => "BOLETO",
			"value" => $order_info['total'],
			"dueDate" => date('Y-m-d', strtotime('+3 days')),
			"description" => "Pedido " . $order_info['order_id'],
			"externalReference"	=> $order_info['order_id'],
			//"callback" => array("successUrl" => HTTPS_SERVER . "index.php?route=checkout/success")
			]);

            $comment = "";

		if (isset($payment['errors'])) {
			$this->log->write('Pedido Nº ' . $order_info['order_id'] . ' - ERROR: ' . $payment['errors'][0]['description']);
            $json['error'] = $payment['errors'][0]['description'];
		}
            
		if (!$json) { 
			if (isset($payment['id'])) {
			$this->cadId($payment['id'], $order_info['order_id']);
			$res_api = $this->getPay($payment['id'], $mode);
			$this->log->write(json_encode($res_api));
			$barcode = $res_api['bankSlip']['barCode'];
		    $exp = $res_api['pix']['expirationDate'];
		    $comment .= "Pagamento ID: " . $payment['id'] . "\n";
		    $comment .= "Link do CÓDIGO DE BARRAS : <a href='" . $payment['bankSlipUrl'] . "' class='label label-info' target='_blank'> VER 2ª via boleto </a> \n\n";
			$comment .= '<div class="panel-body"><label class="col-sm-2 control-label" for="copia"><b>Código de Barras:</b></label><div class="input-group"><input type="text" class="form-control" value="' . $barcode .'" name="copia" id="copia" class="form-control" /><span class="input-group-btn"><input type="button" value="Copiar Código de Barras" id="button-copiar" data-loading-text="Copiando"  class="btn btn-primary" /></span></div></div>'. "\n";
	  		$comment .= "<b>Expiração:</b> " . $exp . "\n";
			$comment .= '<script type="text/javascript">';
			$comment .= '$(document).ready(function() {';
			$comment .= '$("#button-copiar").on("click", function() {';
    		$comment .= '$("#copia").select();';
    		$comment .= 'try {';
      		$comment .= 'var sucesso = document.execCommand("copy");';
      		$comment .= 'if (sucesso) {';
        	$comment .= 'alert("Pix copiado para a área de transferência!");';
      		$comment .= '} else {';
        	$comment .= 'alert("Não foi possível copiar o Pix.");';
    		$comment .= '} } catch (err) {';
      		$comment .= 'console.error("Falha ao copiar o Pix: ", err);';
      		$comment .= 'alert("Não foi possível copiar o Pix.");';
    		$comment .= '} }); });';
			$comment .= '</script>';
			$this->model_checkout_order->addHistory($this->session->data['order_id'], $this->config->get('payment_asaas_boleto_order_status_id'), $comment, true);
		    $json['redirect'] =  $this->url->link('checkout/success', 'language=' . $this->config->get('config_language'), true);
			} else {
			$json['redirect2'] = $this->url->link('checkout/failure', 'language=' . $this->config->get('config_language'), true);
			}
		}
		
		if ($json) {
		    $json['redirect2'] = $this->url->link('checkout/failure', 'language=' . $this->config->get('config_language'), true);
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));	
	}

	public function getPay($id, $sandbox = true) {
            $url =  $sandbox ? 'https://sandbox.asaas.com/api/v3/' : 'https://www.asaas.com/api/v3/';
            $token = $this->config->get('payment_asaas_boleto_api_key');
            $user_agent = base64_decode('TWFzdGVyLzEuMC4wLjAgKFBsYXRhZm9ybWEgb3BlbmNhcnQuY29tIC0gREVWIE9wZW5jYXIgTWFzdGVyKQ==');
    	      $headers = array('Accept: application/json', 'Content-Type: application/json;charset=UTF-8', 'User-Agent: ' . $user_agent , 'access_token: ' . $token);
            $soap_do = curl_init();
            curl_setopt($soap_do, CURLOPT_URL, $url . 'payments/'. $id . '/billingInfo');
            curl_setopt($soap_do, CURLOPT_CONNECTTIMEOUT, 10);
            curl_setopt($soap_do, CURLOPT_TIMEOUT,        10);
            curl_setopt($soap_do, CURLOPT_CUSTOMREQUEST, "GET");
            curl_setopt($soap_do, CURLOPT_RETURNTRANSFER, true );
            curl_setopt($soap_do, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($soap_do, CURLOPT_SSL_VERIFYHOST, false);
            curl_setopt($soap_do, CURLOPT_HTTPHEADER,     $headers);
            
            $response = curl_exec($soap_do); 
            curl_close($soap_do);
			
            $resposta = json_decode($response, true);
            return  $resposta;
    }

	public function cadId($id, $order_id) {
		$order_query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "asaas_callback` WHERE order_id = '" . (int)$order_id . "'");
		if ($order_query->num_rows) {
		} else {
    		$this->db->query("INSERT INTO `" . DB_PREFIX . "asaas_callback` SET order_id = '" . (int)$order_id . "', pay_id = '" . $this->db->escape($id) . "', type = 'BOLETO', date_create = NOW()");
		}
	}
}