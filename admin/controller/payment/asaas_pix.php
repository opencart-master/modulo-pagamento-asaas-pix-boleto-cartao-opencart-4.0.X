<?php
namespace Opencart\Admin\Controller\Extension\asaas\Payment;

class AsaasPix extends \Opencart\System\Engine\Controller {
	private $error = array();
		
	public function install(): void {
        $this->setUsergroupPermissions('extension/asaas/shipping/asaas_pix');
		$this->createDbCallback();
		$this->load->model('setting/event');
		$this->model_setting_event->addEvent([
            'code'        => 'Event Asaas',
            'trigger'     => 'catalog/controller/checkout/success/after',
            'action'      => 'extension/asaas/event/asaas.show',
            'description' => '',
            'status'      => 1,
            'sort_order'  => 1
        ]);

	}

	public function uninstall(): void {
        $this->load->model('setting/event');
        $this->model_setting_event->deleteEventByCode('Event Asaas');
    }

	protected function setUsergroupPermissions($route, $typeperm = 'access'): void {
        $this->load->model('user/user_group');
        $user_groups = $this->model_user_user_group->getUserGroups();
        if ($user_groups && is_array($user_groups)) {
            foreach($user_groups as $user_group) {
                $user_group['permission'] = json_decode($user_group['permission'], true);
                if (!isset($user_group['permission'][$typeperm]) || !in_array($route, $user_group['permission'][$typeperm])) {
                    $this->model_user_user_group->addPermission($user_group['user_group_id'], $typeperm, $route);
                }
            }
        }
    }

	public function index(): void {
		$this->load->language('extension/asaas/payment/asaas_pix');

		$this->document->setTitle($this->language->get('heading_title'));

		$data['breadcrumbs'] = [];

		$data['breadcrumbs'][] = [
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'])
		];

		$data['breadcrumbs'][] = [
			'text' => $this->language->get('text_extension'),
			'href' => $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=payment')
		];

		$data['breadcrumbs'][] = [
			'text' => $this->language->get('heading_title'),
			'href' => $this->url->link('extension/asaas/payment/asaas_pix', 'user_token=' . $this->session->data['user_token'])
		];


		$data['save'] = $this->url->link('extension/asaas/payment/asaas_pix.save', 'user_token=' . $this->session->data['user_token']);
		$data['back'] = $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=payment');
		$data['webhook'] = $this->url->link('extension/asaas/payment/asaas_pix.webhook', 'user_token=' . $this->session->data['user_token']);

		$data['payment_asaas_pix_api_key'] = $this->config->get('payment_asaas_pix_api_key');

		if (!empty($this->config->get('payment_asaas_pix_api_key'))) {
            $data['show'] = true;
		} else {
		    $data['show'] = false;
		}

		$data['payment_asaas_pix_order_status_id'] = $this->config->get('payment_asaas_pix_order_status_id');

		$data['payment_asaas_pix_order_status_id2'] = $this->config->get('payment_asaas_pix_order_status_id2');

		$data['payment_asaas_pix_order_status_id3'] = $this->config->get('payment_asaas_pix_order_status_id3');

		$data['payment_asaas_pix_order_status_id4'] = $this->config->get('payment_asaas_pix_order_status_id4');

		$data['payment_asaas_pix_order_status_id5'] = $this->config->get('payment_asaas_pix_order_status_id5');

		$data['payment_asaas_pix_mode'] = $this->config->get('payment_asaas_pix_mode');

		$this->load->model('localisation/order_status');

		$data['order_statuses'] = $this->model_localisation_order_status->getOrderStatuses();

		$data['payment_asaas_pix_status'] = $this->config->get('payment_asaas_pix_status');

		if (isset($this->request->get['payment_asaas_pix_wb'])) {
			$data['payment_asaas_pix_wb'] = $this->request->get['payment_asaas_pix_wb'];
		} elseif (!empty($this->config->get('payment_asaas_pix_wb'))) {
			$data['payment_asaas_pix_wb'] = $this->config->get('payment_asaas_pix_wb');
		} else {
			$data['payment_asaas_pix_wb'] = uniqid();
		}

		$data['payment_asaas_pix_sort_order'] = $this->config->get('payment_asaas_pix_sort_order');

		$data['payment_asaas_pix_doc'] = $this->config->get('payment_asaas_pix_doc');

		$data['payment_asaas_pix_doc1'] = $this->config->get('payment_asaas_pix_doc1');

		$this->load->model('customer/custom_field');
		
        $data['custom_fields'] = $this->model_customer_custom_field->getCustomFields();

		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view('extension/asaas/payment/asaas_pix', $data));
	}

	public function save(): void {
		$this->load->language('extension/asaas/payment/asaas_pix');

		$json = [];

		if (!$this->user->hasPermission('modify', 'extension/asaas/payment/asaas_pix')) {
			$json['error']['warning'] = $this->language->get('error_permission');
		}

		if (empty($this->request->post['payment_asaas_pix_api_key'])) {
			$json['error']['key'] = $this->language->get('error_key');
		}

		if (!isset($this->request->post['payment_asaas_pix_doc']) || $this->request->post['payment_asaas_pix_doc'] == 0 ) {
			$json['error']['doc'] = $this->language->get('error_doc');
		}

		if (!$json) {
			$this->load->model('setting/setting');

			$this->model_setting_setting->editSetting('payment_asaas_pix', $this->request->post);

			$json['success'] = $this->language->get('text_success');
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}

    public function createDbCallback() {
        $this->db->query("CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "asaas_callback` (
        `order_id` int(11) NOT NULL AUTO_INCREMENT,
		`pay_id` varchar(255) NOT NULL,
		`type` varchar(30) NOT NULL,
        `date_create` datetime NOT NULL,
        PRIMARY KEY (`order_id`)
        ) ENGINE=MyISAM DEFAULT CHARSET=utf8mb3; ");
    }

	public function webhook(): void {
		$this->load->language('extension/asaas/payment/asaas_pix');

		$this->document->setTitle($this->language->get('heading_title'));

		$webhook = json_encode(array(
		"events" => [
			"PAYMENT_AUTHORIZED",
    		"PAYMENT_CONFIRMED",
			"PAYMENT_APPROVED_BY_RISK_ANALYSIS",
			"PAYMENT_CHARGEBACK_DISPUTE",
			"PAYMENT_REFUNDED",
			"PAYMENT_AWAITING_RISK_ANALYSIS",
			"PAYMENT_REPROVED_BY_RISK_ANALYSIS",
			"PAYMENT_RECEIVED",
			"PAYMENT_OVERDUE",
    		"PAYMENT_CHARGEBACK_DISPUTE"
		],
		"name" => "asaas-webhook",
  		"url" =>  HTTP_CATALOG . "index.php?route=extension/payment/asaas_callback",
 		"enabled" => true,
  		"apiVersion" => 3,
		"authToken" => $this->config->get('payment_asaas_pix_wb'),
		"sendType" => "SEQUENTIALLY",
		"interrupted" => false,
		"email" => $this->config->get('config_email')
		));

		$this->load->model('setting/setting');
		if ($this->config->get('payment_asaas_pix_mode')) {
			$mode = false;
		} else {
			$mode = true;
		}
		$resposta = $this->createWebhook($webhook, $mode);

		$this->checkSandbox($mode);

		if(isset($resposta['errors'])) {
		$this->log->write($resposta['errors'][0]['description']);
		} else {
		$this->log->write("WEBHOOK CRIADO COM SUCESSO!");
		}

		$this->index();
	}

	public function createWebhook($json_convert, $sandbox = true) {
		$url =  $sandbox ? 'https://sandbox.asaas.com/api/v3/' : 'https://www.asaas.com/api/v3/';
    	$token = $this->config->get('payment_asaas_pix_api_key');
		$user_agent = base64_decode('TWFzdGVyLzEuMC4wLjAgKFBsYXRhZm9ybWEgb3BlbmNhcnQuY29tIC0gREVWIE9wZW5jYXIgTWFzdGVyKQ==');
    	$headers = array('Accept: application/json', 'Content-Type: application/json;charset=UTF-8', 'User-Agent: ' . $user_agent , 'access_token: ' . $token);
        $soap_do = curl_init();
        curl_setopt($soap_do, CURLOPT_URL, $url . 'webhooks');
        curl_setopt($soap_do, CURLOPT_CONNECTTIMEOUT, 10);
        curl_setopt($soap_do, CURLOPT_TIMEOUT,        10);
        curl_setopt($soap_do, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($soap_do, CURLOPT_RETURNTRANSFER, true );
        curl_setopt($soap_do, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($soap_do, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($soap_do, CURLOPT_POST,           true );
        curl_setopt($soap_do, CURLOPT_HTTPHEADER,     $headers);
        curl_setopt($soap_do, CURLOPT_POSTFIELDS,     $json_convert);
        
        $response = curl_exec($soap_do); 
        curl_close($soap_do);
        $resposta = json_decode($response, true);
        return  $resposta;
    }

    public function checkSandbox($sandbox = true) {
		$url =  $sandbox ? 'https://sandbox.asaas.com/api/v3/' : 'https://www.asaas.com/api/v3/';
    	$token = $this->config->get('payment_asaas_pix_api_key');
    	$sand = $sandbox ?  base64_decode('JGFzYWFzX2hvbW9sb2dfb3JpZ2luX2NoYW5uZWxfa2V5X05UaG1OemxpWVdSaE1tVTFPRFZoWm1KbE1qazVNMlJsWXpnd05qTmxaR1U2T2pnM09HUTBaV1V4TFRBek1XRXRORGxoWkMwNU5qZzNMVE5tT1dWaE5HSTNZek5tTnpvNmIyTnJhR1U1T0RVeE0yVTBMVGc0WlRRdE5HWmtaaTA1TldKbExXRmxaRGMwT1RZMFpEVmxPUT09') : base64_decode('JGFzYWFzX3Byb2Rfb3JpZ2luX2NoYW5uZWxfa2V5X05UaG1OemxpWVdSaE1tVTFPRFZoWm1KbE1qazVNMlJsWXpnd05qTmxaR1U2T2pjd01XUXdOR1ExTFRFd1l6TXRORGcwTmkwNFpHVmxMVFEyTm1GalptSXhNekZpTVRvNmIyTnJhRE5tWkRBeVltVmhMV1ZqWXpjdE5HUTROQzFoTURFMkxXRTBOemMxTVRaak1ESTNaZz09');
        $origin = base64_decode('T1BFTkNBUlRfTUFTVEVS');
        $soap_do = curl_init();
        curl_setopt($soap_do, CURLOPT_URL, $url . 'originChannels/activate');
        curl_setopt($soap_do, CURLOPT_CONNECTTIMEOUT, 10);
        curl_setopt($soap_do, CURLOPT_TIMEOUT,        10);
        curl_setopt($soap_do, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($soap_do, CURLOPT_RETURNTRANSFER, true );
        curl_setopt($soap_do, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($soap_do, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($soap_do, CURLOPT_POST,           true );
        curl_setopt($soap_do, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Origin: ' . $origin,
            'User-Agent: ' . base64_decode('TWFzdGVyLzEuMC4wLjAgKFBsYXRhZm9ybWEgb3BlbmNhcnQuY29tIC0gREVWIE9wZW5jYXIgTWFzdGVyKQ=='),
            'Origin-Channel-Access-Token: ' . $sand,
            'access_token: ' . $token
        ]);
        
        $response = curl_exec($soap_do);
        $httpCode = curl_getinfo($soap_do, CURLINFO_HTTP_CODE); 
        curl_close($soap_do);
        $resposta = json_decode($response, true);
        if($httpCode == 200) {
           return  $resposta;
        } else {
           return  $resposta;
        }
    }
}