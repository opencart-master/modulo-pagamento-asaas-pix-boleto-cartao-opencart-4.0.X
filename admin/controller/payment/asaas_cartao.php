<?php
namespace Opencart\Admin\Controller\Extension\asaas\Payment;

class AsaasCartao extends \Opencart\System\Engine\Controller {
	private $error = array();
		
	public function install(): void {
        $this->setUsergroupPermissions('extension/asaas/shipping/asaas_cartao');
		$this->createDbCallback();
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
		$this->load->language('extension/asaas/payment/asaas_cartao');

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
			'href' => $this->url->link('extension/asaas/payment/asaas_cartao', 'user_token=' . $this->session->data['user_token'])
		];

		$data['save'] = $this->url->link('extension/asaas/payment/asaas_cartao.save', 'user_token=' . $this->session->data['user_token']);
		$data['back'] = $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=payment');

		$data['payment_asaas_cartao_api_key'] = $this->config->get('payment_asaas_cartao_api_key');

		if (!empty($this->config->get('payment_asaas_cartao_api_key'))) {
            $data['show'] = true;
		} else {
		    $data['show'] = false;
		}

		$data['payment_asaas_cartao_order_status_id'] = $this->config->get('payment_asaas_cartao_order_status_id');

		$data['payment_asaas_cartao_order_status_id2'] = $this->config->get('payment_asaas_cartao_order_status_id2');

		$data['payment_asaas_cartao_order_status_id3'] = $this->config->get('payment_asaas_cartao_order_status_id3');

		$data['payment_asaas_cartao_order_status_id4'] = $this->config->get('payment_asaas_cartao_order_status_id4');

		$data['payment_asaas_cartao_order_status_id5'] = $this->config->get('payment_asaas_cartao_order_status_id5');

		$data['payment_asaas_cartao_mode'] = $this->config->get('payment_asaas_cartao_mode');

		$this->load->model('localisation/order_status');

		$data['order_statuses'] = $this->model_localisation_order_status->getOrderStatuses();

		$data['payment_asaas_cartao_status'] = $this->config->get('payment_asaas_cartao_status');

		$data['payment_asaas_cartao_wb'] = $this->config->get('payment_asaas_cartao_wb');

		$data['payment_asaas_cartao_sort_order'] = $this->config->get('payment_asaas_cartao_sort_order');

		if (isset($this->request->get['payment_asaas_cartao_parc'])) {
			$data['payment_asaas_cartao_parc'] = $this->request->get['payment_asaas_cartao_parc'];
		} elseif (!empty($this->config->get('payment_asaas_cartao_parc'))) {
			$data['payment_asaas_cartao_parc'] = $this->config->get('payment_asaas_cartao_parc');
		} else {
			$data['payment_asaas_cartao_parc'] = 12;
		}

		if (isset($this->request->get['payment_asaas_cartao_parc1'])) {
			$data['payment_asaas_cartao_parc1'] = $this->request->get['payment_asaas_cartao_parc1'];
		} elseif (!empty($this->config->get('payment_asaas_cartao1_parc1'))) {
			$data['payment_asaas_cartao_parc1'] = $this->config->get('payment_asaas_cartao_parc1');
		} else {
			$data['payment_asaas_cartao_parc1'] = 12;
		}

		if (isset($this->request->get['payment_asaas_cartao_juros'])) {
			$data['payment_asaas_cartao_juros'] = $this->request->get['payment_asaas_cartao_juros'];
		} elseif (!empty($this->config->get('payment_asaas_cartao1_parc1'))) {
			$data['payment_asaas_cartao_juros'] = $this->config->get('payment_asaas_cartao_juros');
		} else {
			$data['payment_asaas_cartao_juros'] = 3.99;
		}

		$data['payment_asaas_cartao_doc'] = $this->config->get('payment_asaas_cartao_doc');

		$data['payment_asaas_cartao_doc1'] = $this->config->get('payment_asaas_cartao_doc1');

		$data['payment_asaas_cartao_number'] = $this->config->get('payment_asaas_cartao_number');

		$this->load->model('customer/custom_field');
		
        $data['custom_fields'] = $this->model_customer_custom_field->getCustomFields();

		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view('extension/asaas/payment/asaas_cartao', $data));
	}

	public function save(): void {
		$this->load->language('extension/asaas/payment/asaas_cartao');

		$json = [];

		if (!$this->user->hasPermission('modify', 'extension/asaas/payment/asaas_cartao')) {
			$json['error']['warning'] = $this->language->get('error_permission');
		}

		if (empty($this->request->post['payment_asaas_cartao_api_key'])) {
			$json['error']['key'] = $this->language->get('error_key');
		}

		if (!isset($this->request->post['payment_asaas_cartao_doc']) || $this->request->post['payment_asaas_cartao_doc'] == 0 ) {
			$json['error']['doc'] = $this->language->get('error_doc');
		}

		if (!isset($this->request->post['payment_asaas_cartao_number']) || $this->request->post['payment_asaas_cartao_number'] == 0 ) {
			$json['error']['number'] = $this->language->get('error_number');
		}

		if (!$json) {
			$this->load->model('setting/setting');

			$this->model_setting_setting->editSetting('payment_asaas_cartao', $this->request->post);

			$this->checkSandbox(false);

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

	public function checkSandbox($sandbox = true) {
		$url =  $sandbox ? 'https://sandbox.asaas.com/api/v3/' : 'https://www.asaas.com/api/v3/';
    	$token = $this->config->get('payment_asaas_cartao_api_key');
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