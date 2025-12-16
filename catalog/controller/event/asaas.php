<?php
namespace Opencart\Catalog\Controller\Extension\Asaas\Event;

class Asaas extends \Opencart\System\Engine\Controller {

	public function show(string &$route, array &$args, mixed &$output): void {

        $order_query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "order` WHERE `customer_id` = '" . (int)$this->customer->getId() . "' AND `customer_id` != '0' AND `order_status_id` > '0' ORDER BY order_id DESC LIMIT 1");

		if ($order_query->num_rows) {

          $metodo = json_decode($order_query->row['payment_method'], true);
		$link = $this->config->get('config_language') . '?route=account/order.info&customer_token='. $this->session->data['customer_token'] . '&order_id=' . $order_query->row['order_id'];

          $html =  "<script type='text/javascript'>";
          $html .= "setTimeout(function() {";
          $html .= "window.location.href = '" . $link . "';";
          $html .= " }, 3000);";
          $html .= "</script>";
         
          if($metodo['name'] == 'Pix' || $metodo['name'] == 'Boleto') {
          echo $html;
          }
          
   		}
     }
}