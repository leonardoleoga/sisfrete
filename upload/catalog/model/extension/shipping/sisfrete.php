<?php
class ModelExtensionShippingSisfrete extends Model {
	function getQuote($address) {
		$query = $this->db->query("SELECT * FROM ". DB_PREFIX ."zone_to_geo_zone WHERE geo_zone_id = '". (int)$this->config->get('shipping_sisfrete_geo_zone_id') ."' AND country_id = '". (int)$address['country_id'] ."' AND (zone_id = '". (int)$address['zone_id'] ."' OR zone_id = '0')");

		if (!$this->config->get('shipping_sisfrete_geo_zone_id'))
			$status = true;
		elseif ($query->num_rows)
			$status = true;
		else
			$status = false;

		$header = [
			'Accept: application/json',
			'Content-Type: application/json;charset=UTF-8',
			'Authorization: Bearer'. $this->config->get('shipping_sisfrete_token'),
			'Token:'. $this->config->get('shipping_sisfrete_token'),
			'User-Agent: PRAI Solutions (suporte@agenciaprai.com)'
		];

		if ($this->config->get('shipping_sisfrete_type') == '0')
			$url = 'https://cotar.sisfrete.com.br/cotacao/Integracao.php?';
		else
			$url = 'https://cotar.sandbox.sisfrete.com.br/cotacao/Integracao.php?';

		$postcode_origin  = preg_replace("/[^0-9]/", "", $this->config->get('shipping_sisfrete_postcode'));
		$postcode_destiny = preg_replace("/[^0-9]/", "", $address['postcode']);

		$items = [];

		foreach ($this->cart->getProducts() as $product_cart) {
			if ($product_cart['shipping']) {
				$items[] = [
					'sku' 			=> $product_cart['model'],
					'quantity' 	=> (int)$product_cart['quantity'],
					'origin'		=> $postcode_origin,
					'price' 		=> (float)($product_cart['price'] * $product_cart['quantity']),
					'dimensions'=> [
						'length' 	=> (float)$this->getCentimeterDimension($product_cart['length_class_id'], $product_cart['length']),
						'height' 	=> (float)$this->getCentimeterDimension($product_cart['length_class_id'], $product_cart['height']),
						'width' 	=> (float)$this->getCentimeterDimension($product_cart['length_class_id'], $product_cart['width']),
						'weight' 	=> (float)$this->getWeightInKilograms($product_cart['weight_class_id'], $product_cart['weight'])
					],
				];
			}
		}

		$products = [
			'destination' => $postcode_destiny,
			'items' 			=> $items
		];

		$json_products = json_encode($products);

		$quotation = $this->getQuotation($url, $json_products, $header);

		$quote_data  = [];
		$method_data = [];

		if ($quotation && $status) {
			foreach ($quotation as $value) {
				if (!empty($this->config->get('shipping_sisfrete_daysextra')) && $this->config->get('shipping_sisfrete_daysextra') > 0)
					$shipping_days = $value['promise'] + $this->config->get('shipping_sisfrete_daysextra');
				else
					$shipping_days = $value['promise'];

				if (!empty($this->config->get('shipping_sisfrete_costextra')) && (float)$this->config->get('shipping_sisfrete_costextra') > 0.00) {
					if ($this->config->get('shipping_sisfrete_typecost') == 'F')
						$cost = $value['price'] + $this->config->get('shipping_sisfrete_costextra');
					elseif ($this->config->get('shipping_sisfrete_typecost') == 'P')
						$cost = $value['price'] + ($value['price'] * ($this->config->get('shipping_sisfrete_costextra') / 100));
				} else {
					$cost = $value['price'];
				}

				$quote_data['sisfrete'.$value['service_id']] = [
					'code'         => 'sisfrete.sisfrete'.$value['service_id'],
					'title'        => $value['transportadora'] .' - '. str_replace('{prazo}', $shipping_days, $this->config->get('shipping_sisfrete_deadline')),
					'cost'         => $cost,
					'tax_class_id' => $this->config->get('shipping_sisfrete_tax_class_id'),
					'text'         => $this->currency->format($this->tax->calculate($cost, $this->config->get('shipping_sisfrete_tax_class_id'), $this->config->get('config_tax')), $this->session->data['currency'])
				]; 
			}
		}

		if ($quote_data) {
			$method_data = [
				'code'       => 'sisfrete',
				'title'      => $this->config->get('shipping_sisfrete_title'),
				'quote'      => $quote_data,
				'sort_order' => $this->config->get('shipping_sisfrete_sort_order'),
				'error'      => false
			];
		}

		return $method_data;
	}

	private function getCentimeterDimension($length_class_id, $dimension) {
		if (is_numeric($dimension)) {
			$dimension_query = $this->db->query("SELECT * FROM `". DB_PREFIX ."length_class_description` WHERE length_class_id = '". (int)$length_class_id ."' AND language_id = '". (int)$this->config->get('config_language_id') ."'");

			if ($dimension_query->row['unit'] == 'mm')
				return number_format(($dimension / 1000) * 100, 2, '.', '');
			elseif ($dimension_query->row['unit'] == 'in')
				return number_format($dimension * 2.54, 2, '.', '');
		}

		return number_format($dimension, 2, '.', '');
	}

	private function getWeightInKilograms($weight_class_id, $weight) {
		if (is_numeric($weight)) {
			$weight_query = $this->db->query("SELECT * FROM `". DB_PREFIX ."weight_class` wc LEFT JOIN `". DB_PREFIX ."weight_class_description` wcd ON (wc.weight_class_id = wcd.weight_class_id) WHERE wcd.language_id = '". (int)$this->config->get('config_language_id') ."' AND wc.weight_class_id =  '". (int)$weight_class_id ."'");

			if ($weight_query->row['unit'] == 'g')
				return ($weight / 1000);
		}

		return $weight;
	}

	public function getQuotation($url, $json_products, $header) {
		$curl = curl_init();

		curl_setopt($curl, CURLOPT_URL, 					 $url);
		curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 10);
		curl_setopt($curl, CURLOPT_TIMEOUT,        10);
		curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "POST");
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true );
		curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
		curl_setopt($curl, CURLOPT_POST,           true );
		curl_setopt($curl, CURLOPT_POSTFIELDS,		 $json_products);
		curl_setopt($curl, CURLOPT_HTTPHEADER,		 $header);

		$response = curl_exec($curl);
		$err = curl_error($curl);
		curl_close($curl);

		if ($err) {
			$this->log->write('Sisfrete - Erro: '. $err);
			return false;
    } else {
			$result = json_decode($response, true);

			if (isset($result['packages'][0]['quotations'])) {
				return $result['packages'][0]['quotations'];
			} else {
				$this->log->write('Sisfrete - Cotar Frete: '. $result);
				return false;
			}
		}
	}
}
