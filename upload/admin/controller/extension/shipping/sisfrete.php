<?php
class ControllerExtensionShippingSisfrete extends Controller {
	private $error = array();

	public function index() {
		$this->load->language('extension/shipping/sisfrete');

		$this->document->setTitle($this->language->get('heading_title'));

		$this->load->model('setting/setting');

		if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {
			$this->model_setting_setting->editSetting('shipping_sisfrete', $this->request->post);

			$this->session->data['success'] = $this->language->get('text_success');

			$this->response->redirect($this->url->link('marketplace/extension', 'user_token='. $this->session->data['user_token'] .'&type=shipping', true));
		}

		if (isset($this->error['warning']))
			$data['error_warning'] = $this->error['warning'];
		else
			$data['error_warning'] = '';

		if (isset($this->error['token']))
			$data['error_token'] = $this->error['token'];
		else
			$data['error_token'] = '';

		if (isset($this->error['title']))
			$data['error_title'] = $this->error['title'];
		else
			$data['error_title'] = '';

		if (isset($this->error['deadline']))
			$data['error_deadline'] = $this->error['deadline'];
		else
			$data['error_deadline'] = '';

		if (isset($this->error['postcode']))
			$data['error_postcode'] = $this->error['postcode'];
		else
			$data['error_postcode'] = '';

		$data['breadcrumbs'] = [];

		$data['breadcrumbs'][] = [
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
		];

		$data['breadcrumbs'][] = [
			'text' => $this->language->get('text_extension'),
			'href' => $this->url->link('marketplace/extension', 'user_token='. $this->session->data['user_token'] .'&type=shipping', true)
		];

		$data['breadcrumbs'][] = [
			'text' => $this->language->get('heading_title'),
			'href' => $this->url->link('extension/shipping/sisfrete', 'user_token='. $this->session->data['user_token'], true)
		];

		$data['action'] = $this->url->link('extension/shipping/sisfrete', 'user_token='. $this->session->data['user_token'], true);
		$data['cancel'] = $this->url->link('marketplace/extension', 'user_token='. $this->session->data['user_token'] .'&type=shipping', true);

		if (isset($this->request->post['shipping_sisfrete_status']))
			$data['shipping_sisfrete_status'] = $this->request->post['shipping_sisfrete_status'];
		else
			$data['shipping_sisfrete_status'] = $this->config->get('shipping_sisfrete_status');

		if (isset($this->request->post['shipping_sisfrete_token']))
			$data['shipping_sisfrete_token'] = $this->request->post['shipping_sisfrete_token'];
		else
			$data['shipping_sisfrete_token'] = $this->config->get('shipping_sisfrete_token');

		if (isset($this->request->post['shipping_sisfrete_type']))
			$data['shipping_sisfrete_type'] = $this->request->post['shipping_sisfrete_type'];
		else
			$data['shipping_sisfrete_type'] = $this->config->get('shipping_sisfrete_type');

		if (isset($this->request->post['shipping_sisfrete_title']))
			$data['shipping_sisfrete_title'] = $this->request->post['shipping_sisfrete_title'];
		else
			$data['shipping_sisfrete_title'] = $this->config->get('shipping_sisfrete_title');

		if (isset($this->request->post['shipping_sisfrete_deadline']))
			$data['shipping_sisfrete_deadline'] = $this->request->post['shipping_sisfrete_deadline'];
		else
			$data['shipping_sisfrete_deadline'] = $this->config->get('shipping_sisfrete_deadline');

		if (isset($this->request->post['shipping_sisfrete_postcode']))
			$data['shipping_sisfrete_postcode'] = $this->request->post['shipping_sisfrete_postcode'];
		else
			$data['shipping_sisfrete_postcode'] = $this->config->get('shipping_sisfrete_postcode');

		if (isset($this->request->post['shipping_sisfrete_daysextra']))
			$data['shipping_sisfrete_daysextra'] = $this->request->post['shipping_sisfrete_daysextra'];
		else
			$data['shipping_sisfrete_daysextra'] = $this->config->get('shipping_sisfrete_daysextra');

		if (isset($this->request->post['shipping_sisfrete_typecost']))
			$data['shipping_sisfrete_typecost'] = $this->request->post['shipping_sisfrete_typecost'];
		else
			$data['shipping_sisfrete_typecost'] = $this->config->get('shipping_sisfrete_typecost');

		if (isset($this->request->post['shipping_sisfrete_costextra']))
			$data['shipping_sisfrete_costextra'] = $this->request->post['shipping_sisfrete_costextra'];
		else
			$data['shipping_sisfrete_costextra'] = $this->config->get('shipping_sisfrete_costextra');

		$this->load->model('localisation/tax_class');
		$data['tax_classes'] = $this->model_localisation_tax_class->getTaxClasses();

		if (isset($this->request->post['shipping_sisfrete_tax_class_id']))
			$data['shipping_sisfrete_tax_class_id'] = $this->request->post['shipping_sisfrete_tax_class_id'];
		else
			$data['shipping_sisfrete_tax_class_id'] = $this->config->get('shipping_sisfrete_tax_class_id');

		$this->load->model('localisation/geo_zone');
		$data['geo_zones'] = $this->model_localisation_geo_zone->getGeoZones();

		if (isset($this->request->post['shipping_sisfrete_geo_zone_id']))
			$data['shipping_sisfrete_geo_zone_id'] = $this->request->post['shipping_sisfrete_geo_zone_id'];
		else
			$data['shipping_sisfrete_geo_zone_id'] = $this->config->get('shipping_sisfrete_geo_zone_id');

		if (isset($this->request->post['shipping_sisfrete_sort_order']))
			$data['shipping_sisfrete_sort_order'] = $this->request->post['shipping_sisfrete_sort_order'];
		else
			$data['shipping_sisfrete_sort_order'] = $this->config->get('shipping_sisfrete_sort_order');

		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view('extension/shipping/sisfrete', $data));
	}

	protected function validate() {
		if (!$this->user->hasPermission('modify', 'extension/shipping/sisfrete'))
			$this->error['warning'] = $this->language->get('error_permission');

		if (!isset($this->request->post['shipping_sisfrete_token']) || empty($this->request->post['shipping_sisfrete_token']))
			$this->error['token'] = $this->language->get('error_token');

		if (!isset($this->request->post['shipping_sisfrete_title']) || empty($this->request->post['shipping_sisfrete_title']))
			$this->error['title'] = $this->language->get('error_title');

		if (!isset($this->request->post['shipping_sisfrete_deadline']) || empty($this->request->post['shipping_sisfrete_deadline']))
			$this->error['deadline'] = $this->language->get('error_deadline');

		if (!isset($this->request->post['shipping_sisfrete_postcode']) || empty($this->request->post['shipping_sisfrete_postcode']) || !is_numeric($this->request->post['shipping_sisfrete_postcode']) || (utf8_strlen(trim($this->request->post['shipping_sisfrete_postcode'])) < 8) )
			$this->error['postcode'] = $this->language->get('error_postcode');

		if ($this->error && !isset($this->error['warning']))
			$this->error['warning'] = $this->language->get('error_warning');

		return !$this->error;
	}
}
