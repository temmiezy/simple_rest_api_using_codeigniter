<?php
use Core\API_Controller;

/**
 * Created by PhpStorm.
 * User: Programmer
 * Date: 9/20/2017
 * Time: 3:06 PM
 */
class Organics extends API_Controller
{
    private static $marketing_api_key = '5aee93a7-456f-484c-a956-413719468cda';

    public function __construct()
    {
        parent::__construct();

        $sent_api_key = $this->input->get_post('api_key', true);
        if ($sent_api_key != self::$marketing_api_key) {
            echo "bad key";
            $this->responseError("Unauthorized");
            exit(1);

        }

    }

    public function daily($table_name)
    {
        $this->load->model('Marketing_dashboard_api');

        $last_max_data = $this->input->get_post('last_max_data');
        if($last_max_data == ""){
            echo "invalid data";
            $this->responseError("Invalid data");
            exit(1);
        }

        $marketing_api_data = array();

        if($table_name === "apps_organic"){
            $marketing_api_data = $this->Marketing_dashboard_api->getAppsOrganicData($last_max_data);
        }
        if($table_name === "ppc"){
            $marketing_api_data = $this->Marketing_dashboard_api->getPpcData($last_max_data);
        }
        if($table_name ==="kp_organic_apps"){
            $marketing_api_data = $this->Marketing_dashboard_api->getKpOrganicAppsData($last_max_data);
        }
        if($table_name === "kp_organic_calls"){
            $marketing_api_data = $this->Marketing_dashboard_api->getKpOrganicCallsData($last_max_data);
        }
        if($table_name === "kp_ppc_apps"){
            $marketing_api_data = $this->Marketing_dashboard_api->getKpPpcAppsData($last_max_data);
        }
        if($table_name === "local_calls"){
            $marketing_api_data = $this->Marketing_dashboard_api->getLocalCallsByState($last_max_data);
        }


        echo json_encode($marketing_api_data);

    }

}