<?php
namespace Opencart\Catalog\Model\Extension\Asaas\Payment;

class AsaasCartao extends \Opencart\System\Engine\Model {

	public function getMethods(array $address = []): array {
		$this->load->language('extension/asaas/payment/asaas_cartao');


		$status = $this->config->get('payment_asaas_cartao_status');

		$method_data = [];

		if ($status) {
			$option_data['asaas_cartao'] = [
				'code' => 'asaas_cartao.asaas_cartao',
				'name' => $this->language->get('heading_title')
			];

			$method_data = [
				'code'       => 'asaas_cartao',
				'name'       => $this->language->get('heading_title'),
				'option'     => $option_data,
				'sort_order' => $this->config->get('payment_asaas_cartao_sort_order')
			];
		}

		return $method_data;
	}
}