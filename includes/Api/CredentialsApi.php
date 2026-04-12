<?php
namespace AllI1D\C411\Api;

class CredentialsApi {

    private $route_namespace;

    private $current_namespace = 'credentials';

    public function __construct(string $route_namespace) {
        $this->route_namespace = $route_namespace;
        add_action('rest_api_init', [$this, 'register_routes']);
    }

    public function get_namespace(): string {
        return $this->route_namespace.'/'.$this->current_namespace;
    }

    public function check_permissions() :bool {
        return current_user_can('alli1d_admin');
    }

    public function get_routes():array {
        return [
            'credentials' => rest_url($this->get_namespace()),
        ];
    }

    public function register_routes() {
        register_rest_route(
            $this->route_namespace,
            $this->current_namespace,
            [
                'methods' => 'POST',
                'callback' => [$this, 'set_credentials'],
                'permission_callback' => [$this, 'check_permissions'],
            ]
        );
    }

    public function set_credentials($request) {
		$c411_api_key = $request->get_param('c411_api_key');
        if (empty($c411_api_key)) {
            return new \WP_REST_Response(['status' => 'You need to specify an API key'], 400);
        }
        update_option('alli1d_c411_api_key', $c411_api_key);
        return new \WP_REST_Response(['status' => 'success'], 200);
    }
}