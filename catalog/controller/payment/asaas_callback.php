<?php
namespace Opencart\Catalog\Controller\Extension\Asaas\Payment;

class AsaasCallback  extends \Opencart\System\Engine\Controller {
    
    public function index(): void {
	 $input = file_get_contents('php://input');
        $payload = json_decode($input, true);
        
		$tokenseguro = "";
		foreach (getallheaders() as $name => $value) {
			if ($name == 'Asaas-Access-Token') {
				$tokenseguro =  $value;
			}
        }

        if ($payload && isset($payload['event']) && isset($payload['payment'])) {
        //CARD    
        if ($payload['payment']['billingType'] == 'CREDIT_CARD') {

            $externalRef = $payload['payment']['externalReference'];
			$ptransaction = $payload['payment']['creditCard']['creditCardBrand'];
			$pdata = $payload['payment']['clientPaymentDate'];
            $status = $payload['payment']['status'];
            $parcela = $payload['payment']['installmentNumber'];
            
            $valor = $this->currency->format($payload['payment']['value'], $this->session->data['currency']);
			
            if ($externalRef) {
                $this->load->model('checkout/order');
                $order_id = (int)$externalRef;
			    $order_info = $this->model_checkout_order->getOrder($order_id);

                switch (strtoupper($status)) {
                    case 'CONFIRMED':
                    case 'PAID':
					//case 'RECEIVED':
					case 'RECEIVED_IN_CASH':
                        $ord_status = $this->config->get('payment_asaas_cartao_order_status_id2');
                        $comment  = "Retorno Automatico API" . "\n";
		    	        $comment .= "Bandeira: " . $ptransaction . "\n";
				        $comment .= "Pago em: " . $pdata . "\n";
                        $comment .= "Parcela: " . $parcela .  "X \n";
		    	        $comment .= "Valor Pago: ".  $valor . "\n";
                       
                        break;
                    case 'CANCELLED':
                    case 'EXPIRED':
					case 'OVERDUE':
                        $ord_status = $this->config->get('payment_asaas_cartao_order_status_id3');
                        $comment = "Cancelado API";
                        break;
					case 'REFUNDED':
                        $ord_status = $this->config->get('payment_asaas_cartao_order_status_id4');
                        $comment = "Reembolso API";
                        break;
                    default:
                        // outros eventos
                        break;
                }
                 /*alternativa  
                 if($parcela == 1) {
                 $this->model_checkout_order->addOrderHistory($order_id, $ord_status, $comment, true);
                 } else {
                 $this->model_checkout_order->addOrderHistory($order_id, $ord_status, $comment, false);   
                 }
                 */ 
                if ($ord_status != $order_info['order_status_id']) {
                    $this->model_checkout_order->addHistory($order_id, $ord_status, $comment, true);
                }
            }
        }
        //CARD
        
        //BOLETO
        	if ($payload['payment']['billingType'] == 'BOLETO') {
            $externalRef = $payload['payment']['externalReference'];
			$ptransaction = $payload['payment']['nossoNumero'];
			$pdata = $payload['payment']['paymentDate'];
            $status = $payload['payment']['status'];
            $valor = $this->currency->format($payload['payment']['value'], $this->session->data['currency']);
			
            if ($externalRef) {
                $this->load->model('checkout/order');
                $order_id = (int)$externalRef;
                $order_info = $this->model_checkout_order->getOrder($order_id);		

                switch (strtoupper($status)) {
                    case 'CONFIRMED':
                    case 'PAID':
					case 'RECEIVED':
					case 'RECEIVED_IN_CASH':
                        $ord_status = $this->config->get('payment_asaas_boleto_order_status_id2');
                        $comment  = "Retorno Automatico API" . "\n";
		    	        $comment .= "Nosso Número: " . $ptransaction . "\n";
				        $comment .= "Pago em: " . $pdata . "\n";
		    	        $comment .= "Valor Pago: ".  $valor . "\n";
                       
                        break;
                    case 'CANCELLED':
                    case 'EXPIRED':
					case 'OVERDUE':
                        $ord_status = $this->config->get('payment_asaas_boleto_order_status_id3');
                        $comment = "Cancelado API";
                        break;
					case 'REFUNDED':
                        $ord_status = $this->config->get('payment_asaas_boleto_order_status_id4');
                        $comment = "Reembolso API";
                        break;
                    default:
                        // outros eventos
                        break;
                }

                if ($ord_status != $order_info['order_status_id']) {
                    $this->model_checkout_order->addHistory($order_id, $ord_status, $comment, true);
                }
            }
        }
        //BOLETO
        
        //PIX
            if ($payload['payment']['billingType'] == 'PIX') {

            $externalRef = $payload['payment']['externalReference'];
			$ptransaction = $payload['payment']['pixTransaction'];
			$pdata = $payload['payment']['paymentDate'];
            $status = $payload['payment']['status'];
            $valor = $this->currency->format($payload['payment']['value'], $this->session->data['currency']);
			
            if ($externalRef) {
                $this->load->model('checkout/order');
                $order_id = (int)$externalRef;
                $order_info = $this->model_checkout_order->getOrder($order_id);

                switch (strtoupper($status)) {
                    case 'CONFIRMED':
                    case 'PAID':
					case 'RECEIVED':
					case 'RECEIVED_IN_CASH':
                        $ord_status = $this->config->get('payment_asaas_pix_order_status_id2');
                        $comment  = "Retorno Automatico API" . "\n";
		    	        $comment .= "PIX ID: " . $ptransaction . "\n";
				        $comment .= "Pago em: " . $pdata . "\n";
		    	        $comment .= "Valor Pago: ".  $valor . "\n";
                       
                        break;
                    case 'CANCELLED':
                    case 'EXPIRED':
					case 'OVERDUE':
                        $ord_status = $this->config->get('payment_asaas_pix_order_status_id3');
                        $comment = "Cancelado API";
                        break;
					case 'REFUNDED':
                        $ord_status = $this->config->get('payment_asaas_pix_order_status_id4');
                        $comment = "Reembolso API";
                        break;
                    default:
                        // outros eventos
                        break;
                }

                if ($ord_status != $order_info['order_status_id']) {
                    $this->model_checkout_order->addHistory($order_id, $ord_status, $comment, true);
                }
            }
        }
        //PIX
	}

	}

}