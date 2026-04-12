<?php
namespace AllI1D\C411;

use AllI1D\C411\Api\CredentialsApi;

class Api
{
    public static $instance = null;

    public static $route_namespace = 'c411/v1';

    public CredentialsApi $credentials_api;

    public static function get_instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function __construct() {
        $this->credentials_api = new CredentialsApi(self::$route_namespace);
    }

    public function get_data() {
        $data = [
            'routes' => $this->get_routes(),
        ];
        return $data;
    }

    public function get_routes() {
        $routes = [];
        $routes = array_merge($this->credentials_api->get_routes(), $routes);
        return $routes;
    }
}
