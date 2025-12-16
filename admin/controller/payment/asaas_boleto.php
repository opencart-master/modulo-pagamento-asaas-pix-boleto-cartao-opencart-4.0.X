<?php
namespace Opencart\Admin\Controller\Extension\Asaas\Payment;

class AsaasBoleto extends \Opencart\System\Engine\Controller {
	private $error = array();
		
	public function install(): void {
        $this->setUsergroupPermissions('extension/asaas/shipping/asaas_boleto');
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
		$this->load->language('extension/asaas/payment/asaas_boleto');

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
			'href' => $this->url->link('extension/asaas/payment/asaas_boleto', 'user_token=' . $this->session->data['user_token'])
		];

		$data['save'] = $this->url->link('extension/asaas/payment/asaas_boleto.save', 'user_token=' . $this->session->data['user_token']);
		$data['back'] = $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=payment');

		$data['payment_asaas_boleto_api_key'] = $this->config->get('payment_asaas_boleto_api_key');

		if (!empty($this->config->get('payment_asaas_boleto_api_key'))) {
            $data['show'] = true;
		} else {
		    $data['show'] = false;
		}

		$data['payment_asaas_boleto_order_status_id'] = $this->config->get('payment_asaas_boleto_order_status_id');

		$data['payment_asaas_boleto_order_status_id2'] = $this->config->get('payment_asaas_boleto_order_status_id2');

		$data['payment_asaas_boleto_order_status_id3'] = $this->config->get('payment_asaas_boleto_order_status_id3');

		$data['payment_asaas_boleto_order_status_id4'] = $this->config->get('payment_asaas_boleto_order_status_id4');

		$data['payment_asaas_boleto_order_status_id5'] = $this->config->get('payment_asaas_boleto_order_status_id5');

		$data['payment_asaas_boleto_mode'] = $this->config->get('payment_asaas_boleto_mode');

		$this->load->model('localisation/order_status');

		$data['order_statuses'] = $this->model_localisation_order_status->getOrderStatuses();

		$data['payment_asaas_boleto_status'] = $this->config->get('payment_asaas_boleto_status');

		$data['payment_asaas_wb'] = $this->config->get('payment_asaas_wb');

		$data['payment_asaas_boleto_sort_order'] = $this->config->get('payment_asaas_boleto_sort_order');

		$data['payment_asaas_boleto_doc'] = $this->config->get('payment_asaas_boleto_doc');

		$data['payment_asaas_boleto_doc1'] = $this->config->get('payment_asaas_boleto_doc1');

		$this->load->model('customer/custom_field');
		
        $data['custom_fields'] = $this->model_customer_custom_field->getCustomFields();

		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view('extension/asaas/payment/asaas_boleto', $data));
	}

	public function save(): void {
		$this->load->language('extension/asaas/payment/asaas_boleto');

		$json = [];

		if (!$this->user->hasPermission('modify', 'extension/asaas/payment/asaas_boleto')) {
			$json['error']['warning'] = $this->language->get('error_permission');
		}

		if (empty($this->request->post['payment_asaas_boleto_api_key'])) {
			$json['error']['key'] = $this->language->get('error_key');
		}

		if (!isset($this->request->post['payment_asaas_boleto_doc']) || $this->request->post['payment_asaas_boleto_doc'] == 0 ) {
			$json['error']['doc'] = $this->language->get('error_doc');
		}

		if (!$json) {
			$this->load->model('setting/setting');

			$this->model_setting_setting->editSetting('payment_asaas_boleto', $this->request->post);

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
}