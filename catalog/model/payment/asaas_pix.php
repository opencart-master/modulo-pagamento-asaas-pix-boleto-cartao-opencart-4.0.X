<?php
namespace Opencart\Catalog\Model\Extension\Asaas\Payment;

class AsaasPix extends \Opencart\System\Engine\Model {

	public function getMethods(array $address = []): array {
		$this->load->language('extension/asaas/payment/asaas_pix');


		$status = $this->config->get('payment_asaas_pix_status');

		$method_data = [];

		if ($status) {
			$option_data['asaas_pix'] = [
				'code' => 'asaas_pix.asaas_pix',
				'name' => $this->language->get('heading_title')
			];

			$method_data = [
				'code'       => 'asaas_pix',
				'name'       => $this->language->get('heading_title'),
				'option'     => $option_data,
				'sort_order' => $this->config->get('payment_asaas_pix_sort_order')
			];
		}

		return $method_data;
	}
}