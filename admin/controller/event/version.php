<?php
namespace Opencart\Admin\Controller\Extension\Asaas\Event;

class Version extends \Opencart\System\Engine\Controller {
    
	public function index(string &$route, array &$args, mixed &$output): void {
	    $version = '1.0.6.0';
	    $url = base64_decode('aHR0cHM6Ly9vcGVuY2FydG1hc3Rlci5jb20uYnIvbW9kdWxlL3ZlcnNpb24v');
        $soap_do = curl_init();
        curl_setopt($soap_do, CURLOPT_URL, $url);
        curl_setopt($soap_do, CURLOPT_CONNECTTIMEOUT, 10);
        curl_setopt($soap_do, CURLOPT_TIMEOUT,        10);
        curl_setopt($soap_do, CURLOPT_CUSTOMREQUEST, "GET");
        curl_setopt($soap_do, CURLOPT_RETURNTRANSFER, true );
        curl_setopt($soap_do, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($soap_do, CURLOPT_SSL_VERIFYHOST, false);
        
        $response = curl_exec($soap_do); 
        curl_close($soap_do);
        $resposta = json_decode($response, true);
        if ($resposta['asaas'] > $version) {
            echo '<div class="alert alert-info" role="alert">Atenção: tem uma nova versão do módulo <b>Asaas</b>, disponível para <a href="https://www.opencart.com/index.php?route=marketplace/extension/info&extension_id=48210" target="_blank">download</a>!</div>';
    
        } else {
           echo "";
        }

        }
     
}