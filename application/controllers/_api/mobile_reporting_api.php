<?php
/**
 * Created by PhpStorm.
 * User: EtechProgrammer
 * Date: 5/5/2017
 * Time: 12:34 PM
 */
//namespace controllers\_api;

class Mobile_Reporting_Api extends Master_dashboard_controller
{

    /** @var  Dashboard4_model */
    public $Dashboard4_model;

    public function __construct()
    {

        parent::__construct();

        $this->load->model('Dashboard4_model', "Dashboard4_model");

        $this->authorize();

        header('Content-type: application/json');

        $this->postpone_logout();
    }

    public function home()
    {

        $start = date("Y-m-d");
        $end = date("Y-m-d");

        $sales_mobile_cache = $this->Dashboard4_model->get_sales_mobile_cache();
        $idle_apps_one_hour = $this->Dashboard4_model->idle_apps_one_hour($start, $end);

        $data['apps'] = $this->get_apps_section_data($sales_mobile_cache);
        $data['billed_funded'] = $this->get_billed_funded_section_data($sales_mobile_cache);
        $data['idle_apps'] = count($idle_apps_one_hour);
        $data['calls'] = $this->get_calls_section_data();
        $data['marlin'] = $this->get_marlin_section_data();

        echo json_encode($data);
    }

    /**
     * Calculates the ratio of month to day
     *
     * Month to day panel can be green or red
     * Since month is not completed we have to calculate a ratio to divide the cached average by to see
     * if we are ON PACE to exceed (green) or be below (the average)
     *
     * @return float|int
     */
    protected function get_mtd_ratio()
    {

        $day_number = date('j');
        $days_in_month = date('t');
        $mtd_ratio = 0;

        if ($day_number > 1) {

            $day_number--; //last completed day
            $mtd_ratio = (float)$day_number / $days_in_month;

        }

        return $mtd_ratio;
    }

    /**
     * @param $sales_mobile_cache
     * @return array
     */
    protected function get_apps_section_data($sales_mobile_cache)
    {

        $start = date("Y-m-d");
        $end = date("Y-m-d");

        $data['mtd_ratio'] = $this->get_mtd_ratio();

        $day_all = $this->Dashboard4_model->get_apps_count($start, $end)['count'];
        $day_marlin = $this->Dashboard4_model->get_apps_count($start, $end, true)['count'];
        $day_approved = $this->Dashboard4_model->get_apps_count_any_lender($start, $end, 40205)['count'];

        $start = date("Y-m-01");
        $end = date("Y-m-d");

        $mtd_all = $this->Dashboard4_model->get_apps_count($start, $end)['count'];
        $mtd_marlin = $this->Dashboard4_model->get_apps_count($start, $end, true)['count'];
        $mtd_approved = $this->Dashboard4_model->get_apps_count_any_lender($start, $end, 40205)['count'];

        $day_all_class = ((float)$day_all > (float)$sales_mobile_cache['avg_apps_day']) ? 'green' : 'red';
        $day_marlin_class = ((float)$day_marlin > (float)$sales_mobile_cache['avg_apps_day_marlin']) ? 'green' : 'red';

        $mtd_all_class = ((float)$mtd_all > (float)$sales_mobile_cache['avg_apps_month'] * $data['mtd_ratio'])
            ? 'green' : 'red';

        $mtd_marlin_class = ((float)$mtd_marlin > (float)$sales_mobile_cache['avg_apps_month_marlin'] * $data['mtd_ratio'])
            ? 'green' : 'red';


        // APPS
        $apps = [
            'day' => [
                'all' => [
                    'value' => $day_all,
                    'class' => $day_all_class,
                ],
                'marlin' => [
                    'value' => $day_marlin,
                    'class' => $day_marlin_class,
                ],
                'approved' => [
                    'value' => $day_approved,
                    'class' => 'gray',
                ],
            ],
            'mtd' => [
                'all' => [
                    'value' => number_format($mtd_all),
                    'class' => $mtd_all_class,
                ],
                'marlin' => [
                    'value' => $mtd_marlin,
                    'class' => $mtd_marlin_class,
                ],
                'approved' => [
                    'value' => $mtd_approved,
                    'class' => 'gray',
                ],
            ]
        ];

        return $apps;
    }

    /**
     * @param $sales_mobile_cache
     * @return array
     */
    protected function get_billed_funded_section_data($sales_mobile_cache)
    {

        // get data for one day
        $start = date("Y-m-d");
        $end = date("Y-m-d");

        $data['mtd_ratio'] = $this->get_mtd_ratio();

        $day_total = 0;
        $dashboard4_model = $this->Dashboard4_model;

        $marlin_data = $dashboard4_model->get_fundedcount_billedtotal_any_company($start, $end, 40148)[0];
        $day_marlin = $marlin_data['total'];
        $day_marlin_count = $marlin_data['count'];

        $approved_data = $dashboard4_model->get_fundedcount_billedtotal_any_company($start, $end, 40205)[0];
        $day_approved = $approved_data['total'];
        $day_approved_count = $approved_data['count'];

        $speedway_data = $dashboard4_model->get_fundedcount_billedtotal_any_company($start, $end, 40217)[0];
        $day_speedway = $speedway_data['total'];
        $day_speedway_count = $speedway_data['count'];

        $day_marlin_refi = $dashboard4_model->get_funded_count_billed_total_refi($start, $end, 40148)[0]['total'];
        $day_approved_refi = $dashboard4_model->get_funded_count_billed_total_refi($start, $end, 40205)[0]['total'];
        $day_speedway_refi = $dashboard4_model->get_funded_count_billed_total_refi($start, $end, 40217)[0]['total'];

        $refi_data = $dashboard4_model->get_funded_count_billed_total_refi($start, $end)[0];
        $day_refi = $refi_data['total'];
        $day_refi_count = $refi_data['count'];

        $day_total += $day_marlin;
        $day_total += $day_approved;
        $day_total += $day_speedway;
        $day_total += $day_refi;

        $start = date("Y-m-01");
        $end = date("Y-m-d");

        $mtd_total = 0;

        $mtd_marlin = $dashboard4_model->get_fundedcount_billedtotal_any_company($start, $end, 40148, false)[0]['total'];
        $mtd_approved = $dashboard4_model->get_fundedcount_billedtotal_any_company($start, $end, 40205, false)[0]['total'];
        $mtd_speedway = $dashboard4_model->get_fundedcount_billedtotal_any_company($start, $end, 40217, false)[0]['total'];

        $mtd_refi = $dashboard4_model->get_funded_count_billed_total_refi($start, $end)[0]['total'];

        $mtd_total += $mtd_marlin;
        $mtd_total += $mtd_approved;
        $mtd_total += $mtd_speedway;

        $day_total_class = ((float)$day_total > (float)$sales_mobile_cache['avg_billed_day']) ? 'green' : 'red';
        $day_marlin_class = ((float)$day_marlin > (float)$sales_mobile_cache['avg_funded_day_marlin']) ? 'green' : 'red';

        $mtd_total_class = ((float)$mtd_total > (float)$sales_mobile_cache['avg_billed_month'] * $data['mtd_ratio'])
            ? 'green' : 'red';

        $mtd_marlin_class = ((float)$mtd_marlin > (float)$sales_mobile_cache['avg_funded_month_marlin'] * $data['mtd_ratio'])
            ? 'green' : 'red';

        // BILLED | FUNDED
        $billed_funded = [
            'day' => [
                'refi' => [
                    'value' => number_format($day_refi, 0),
                    'count' => $day_refi_count,
                    'class' => 'gray',
                ],
                'marlin' => [
                    'value' => number_format($day_marlin, 0),
                    'refi' => number_format($day_marlin_refi, 0),
                    'count' => $day_marlin_count,
                    'class' => $day_marlin_class,
                ],
                'approved' => [
                    'value' => number_format($day_approved, 0),
                    'refi' => number_format($day_approved_refi, 0),
                    'count' => $day_approved_count,
                    'class' => 'gray',
                ],
                'speedway' => [
                    'value' => number_format($day_speedway, 0),
                    'refi' => number_format($day_speedway_refi, 0),
                    'count' => $day_speedway_count,
                    'class' => 'gray',
                ],
                'total' => [
                    'value' => number_format($day_total, 0),
                    'class' => $day_total_class,
                ],

            ],
            'mtd' => [
                'refi' => [
                    'value' => number_format($mtd_refi, 0),
                    'class' => 'gray',
                ],
                'marlin' => [
                    'value' => number_format($mtd_marlin, 0),
                    'class' => $mtd_marlin_class,
                ],
                'approved' => [
                    'value' => number_format($mtd_approved, 0),
                    'class' => 'gray',
                ],
                'speedway' => [
                    'value' => number_format($mtd_speedway, 0),
                    'class' => 'gray',
                ],
                'total' => [
                    'value' => number_format($mtd_total, 0),
                    'class' => $mtd_total_class,
                ],
            ]
        ];

        return $billed_funded;
    }


    /**
     * @return array
     */
    protected function get_calls_section_data()
    {

        // get data for one day
        $start = date("Y-m-d");
        $end = date("Y-m-d");

        $calls = $this->Dashboard4_model->calls_waiting_sec_mobile($start, $end);
        $calls_average_wait_day = $calls[0]['average_wait'];

        $calls = $this->Dashboard4_model->calls_missed_mobile($start, $end);

        $missed_ratio_day = $calls[0]['missed_ratio'];

        $start = date("Y-m-01");
        $end = date("Y-m-d");

        $calls_m = $this->Dashboard4_model->calls_waiting_sec_mobile($start, $end);
        $await_time_mtd = $calls_m[0]['average_wait'];

        $calls = $this->Dashboard4_model->calls_missed_mobile($start, $end);
        $missed_ratio_mtd = $calls[0]['missed_ratio'];

        // CALLS
        $calls = [
            'day' => [
                'avg_wait' => $calls_average_wait_day,
                'missed' => $missed_ratio_day,
            ],
            'mtd' => [
                'avg_wait' => $await_time_mtd,
                'missed' => $missed_ratio_mtd,
            ]
        ];

        return $calls;
    }

    /**
     * @return array
     */
    protected function get_marlin_section_data()
    {

        // get data for one day
        $start = date("Y-m-d");
        $end = date("Y-m-d");

        $marlin_payments = Autopal_api::getPayments($start, $end, 1);
        $marlin_payments_day = number_format($marlin_payments["dataset"][0]["pmt_total"], 0);
        $marlin_payments_day_count = $marlin_payments["dataset"][0]["total"];

        $start = date("Y-m-01");
        $end = date("Y-m-d");

        $marlin_payments = Autopal_api::getPayments($start, $end, 1);
        $marlin_payments_mtd = number_format($marlin_payments["dataset"][0]["pmt_total"], 0);
        $marlin_payments_mtd_count = $marlin_payments["dataset"][0]["total"];

        // MARLIN
        $data = [
            'day' => [
                'amount' => $marlin_payments_day,
                'payments' => $marlin_payments_day_count,
            ],
            'mtd' => [
                'amount' => $marlin_payments_mtd,
                'payments' => $marlin_payments_mtd_count,
            ]
        ];

        return $data;
    }
}