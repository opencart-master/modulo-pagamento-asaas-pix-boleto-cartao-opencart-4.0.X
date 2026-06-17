<?php
namespace Opencart\Admin\Controller\Extension\asaas\Payment;

class AsaasPix extends \Opencart\System\Engine\Controller {
	private $error = array();
		
	public function install(): void {
        $this->setUsergroupPermissions('extension/asaas/shipping/asaas_pix');
		$this->createDbCallback();
		require_once DIR_EXTENSION . 'asaas/system/library/asaas/asaas_api.php';
		$asaas = new \Opencart\System\Library\Asaas\AsaasApi($this->config->get('payment_asaas_pix_api_key'));
	    $check = $asaas->check();

		$this->load->model('setting/event');
		$this->model_setting_event->addEvent([
            'code'        => 'event_asaas',
            'trigger'     => 'catalog/controller/checkout/success/after',
            'action'      => 'extension/asaas/event/asaas.show',
            'description' => '',
            'status'      => 1,
            'sort_order'  => 1
        ]);

		$this->model_setting_event->addEvent([
            'code'        => 'asaas_version',
            'trigger'     => 'admin/controller/common/dashboard/after',
            'action'      => 'extension/asaas/event/version',
            'description' => '',
            'status'      => 1,
            'sort_order'  => 1
        ]);
	}

	public function uninstall(): void {
        $this->load->model('setting/event');
        $this->model_setting_event->deleteEventByCode('event_asaas');
		$this->model_setting_event->deleteEventByCode('asaas_version');
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

		$this->load->model('localisation/order_status');

		$data['order_statuses'] = $this->model_localisation_order_status->getOrderStatuses();

		$data['payment_asaas_pix_status'] = $this->config->get('payment_asaas_pix_status');

		if (isset($this->request->get['payment_asaas_pix_wb'])) {
			$data['payment_asaas_pix_wb'] = $this->request->get['payment_asaas_pix_wb'];
		} elseif (!empty($this->config->get('payment_asaas_pix_wb'))) {
			$data['payment_asaas_pix_wb'] = $this->config->get('payment_asaas_pix_wb');
		} else {
			$data['payment_asaas_pix_wb'] = md5(uniqid());
		}

		if (isset($this->request->get['payment_asaas_pix_venc'])) {
			$data['payment_asaas_pix_venc'] = $this->request->get['payment_asaas_pix_venc'];
		} elseif (!empty($this->config->get('payment_asaas_pix_venc'))) {
			$data['payment_asaas_pix_venc'] = $this->config->get('payment_asaas_pix_venc');
		} else {
			$data['payment_asaas_pix_venc'] = 1;
		}

		$data['payment_asaas_pix_sort_order'] = $this->config->get('payment_asaas_pix_sort_order');

		$data['payment_asaas_pix_doc'] = $this->config->get('payment_asaas_pix_doc');

		$data['payment_asaas_pix_doc1'] = $this->config->get('payment_asaas_pix_doc1');

		$this->load->model('customer/custom_field');
		
        $data['custom_fields'] = $this->model_customer_custom_field->getCustomFields();

		$data['success'] = isset($this->session->data['success']) ? $this->session->data['success'] : '';
		$data['error'] = isset($this->session->data['error']) ? $this->session->data['error'] : '';

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

		require_once DIR_EXTENSION . 'asaas/system/library/asaas/asaas_api.php';

		if (!$json) {
			$this->load->model('setting/setting');

			$this->model_setting_setting->editSetting('payment_asaas_pix', $this->request->post);

			$asaas = new \Opencart\System\Library\Asaas\AsaasApi($this->config->get('payment_asaas_pix_api_key'));
			$sandbox = $asaas->checkSandbox($this->config->get('payment_asaas_pix_api_key'));

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
		$asaas = new \Opencart\System\Library\Asaas\AsaasApi($this->config->get('payment_asaas_pix_api_key'));
		$this->load->language('extension/asaas/payment/asaas_pix');

		$this->document->setTitle($this->language->get('heading_title'));

		$webhook = array(
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
  		"url" =>  HTTP_CATALOG . $this->config->get('config_language') ."?route=extension/asaas/payment/asaas_callback",
 		"enabled" => true,
  		"apiVersion" => 3,
		"authToken" => $this->config->get('payment_asaas_pix_wb'),
		"sendType" => "SEQUENTIALLY",
		"interrupted" => false,
		"email" => $this->config->get('config_email')
		);

		$resposta = $asaas->createWebhooks($webhook);

		if(isset($resposta['errors'])) {
		$this->session->data['error'] = $resposta['errors'][0]['description'];
		} else {
		$this->session->data['success'] = "WEBHOOK CRIADO COM SUCESSO!";
		}

		$this->index();
	}

}