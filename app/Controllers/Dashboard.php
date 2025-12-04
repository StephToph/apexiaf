<?php

namespace App\Controllers;

class Dashboard extends BaseController
{
    private $db;

    public function __construct()
    {
        $this->db = \Config\Database::connect();
    }

    public function index()
    {
        $db = \Config\Database::connect();

        // check login
        $log_id = $this->session->get('plx_id');
        if (empty($log_id))
            return redirect()->to(site_url('auth'));

        $role_id = $this->Crud->read_field('id', $log_id, 'user', 'role_id');
        $role = strtolower($this->Crud->read_field('id', $role_id, 'access_role', 'name'));
        $role_c = $this->Crud->module($role_id, 'dashboard', 'create');
        $role_r = $this->Crud->module($role_id, 'dashboard', 'read');
        $role_u = $this->Crud->module($role_id, 'dashboard', 'update');
        $role_d = $this->Crud->module($role_id, 'dashboard', 'delete');
        if ($role_r == 0) {
            return redirect()->to(site_url('profile'));
        }

        $data['log_id'] = $log_id;
        $data['role'] = $role;
        $data['role_c'] = $role_c;


        $data['title'] = 'Dashboard | ' . app_name;
        $data['page_active'] = 'dashboard';
        return view('dashboard', $data);
    }



    public function analytics($param1 = '')
    {
        $log_id = $this->session->get('plx_id');

        $role_id = $this->Crud->read_field('id', $log_id, 'user', 'role_id');
        $role = strtolower($this->Crud->read_field('id', $role_id, 'access_role', 'name'));

        if ($param1 == 'coupon') {
            if (!empty($this->request->getPost('start_date'))) {
                $start_date = $this->request->getPost('start_date');
            } else {
                $start_date = '';
            }
            if (!empty($this->request->getPost('end_date'))) {
                $end_date = $this->request->getPost('end_date');
            } else {
                $end_date = '';
            }

            // Initialize arrays for data and categories
            $list = '';
            if ($role == 'developer' || $role == 'administrator') {
                $total_coupon = $this->Crud->check0('coupon');
                $used_coupon = $this->Crud->check('used', 1, 'coupon');
                $unused_coupon = $this->Crud->check('used', 0, 'coupon');
                $coupon_sales = 0;


                $couponz = $this->Crud->read_single('used', 1, 'coupon');
                if (!empty($couponz)) {
                    foreach ($couponz as $or) {

                        $amount = $this->Crud->read_field('id', $or->sub_id, 'subscription', 'amount');
                        $coupon_sales += (float) $amount; // Total revenue

                    }
                }

            }

            if ($role == 'marketer') {
                $total_coupon = $this->Crud->check('marketer_id', $log_id, 'coupon');
                $used_coupon = $this->Crud->check2('used', 1, 'marketer_id', $log_id, 'coupon');
                $unused_coupon = $this->Crud->check2('used', 0, 'marketer_id', $log_id, 'coupon');
                $coupon_sales = 0;


                $couponz = $this->Crud->read2('used', 1, 'marketer_id', $log_id, 'coupon');
                if (!empty($couponz)) {
                    foreach ($couponz as $or) {

                        $amount = $this->Crud->read_field('id', $or->sub_id, 'subscription', $or->amount);
                        $coupon_sales += (float) $amount; // Total revenue

                    }
                }

            }

            // Construct the response
            $response = [
                'total_coupon' => number_format($total_coupon),
                'used_coupon' => number_format($used_coupon),
                'unused_coupon' => number_format($unused_coupon),
                'coupon_sales' => '$' . number_format($coupon_sales, 2),
            ];


            // Return JSON response
            echo json_encode($response);
            die;
        }

        if ($param1 == 'metrics') {

            // === Account Metrics ===
            $customer = $this->db->table('sb_user')->where('is_customer', 1)->countAllResults();
            $staff    = $this->db->table('sb_user')->where('is_staff', 1)->countAllResults();
            $total_accounts = (int)$customer + (int)$staff;
        
            // === Booking Metrics ===
        
            // Total bookings
            $total_bookings = $this->db->table('sb_appointments')->countAllResults();
        
            // Upcoming appointments (future date + pending/confirmed)
            $upcoming_appointments = $this->db->table('sb_appointments')
                ->where('appointment_date >=', date('Y-m-d'))
                ->whereIn('status', ['pending', 'confirmed'])
                ->countAllResults();
        
            // Completed appointments
            $completed_appointments = $this->db->table('sb_appointments')
                ->where('status', 'completed')
                ->countAllResults();
        
            // Popular service (most booked)
            $popular_service = $this->db->table('sb_appointments a')
                ->select('sv.name as service_name, COUNT(*) as total')
                ->join('sb_services sv', 'sv.id = a.service_id', 'left')
                ->groupBy('a.service_id')
                ->orderBy('total', 'DESC')
                ->limit(1)
                ->get()
                ->getRow();
        
            // === Package response ===
            $data = [
                'total_accounts'        => $total_accounts,
                'customer'              => $customer,
                'staff'                 => $staff,
                'total_bookings'        => $total_bookings,
                'upcoming_appointments' => $upcoming_appointments,
                'completed_appointments'=> $completed_appointments,
                'popular_service_name'  => $popular_service ? $popular_service->service_name : null,
                'popular_service_count' => $popular_service ? $popular_service->total : 0
            ];
        
            return $this->response->setJSON($data);
        }
        
        
        
    }

    public function appointments_list()
    {
        $filter = $this->request->getPost('filter') ?? 'upcoming';
    
        $builder = $this->db->table('sb_appointments a')
            ->select('a.*, u.fullname as customer_name, sv.name as service_name')
            ->join('sb_user u', 'u.id = a.user_id')
            ->join('sb_services sv', 'sv.id = a.service_id');
    
        // Apply filters
        if ($filter === 'upcoming') {
            $builder->where('a.appointment_date >=', date('Y-m-d'))
                ->whereIn('a.status', ['pending', 'confirmed']);
        } elseif ($filter !== 'all') {
            $builder->where('a.status', $filter);
        }
    
        $builder->orderBy('a.appointment_date', 'ASC')
            ->orderBy('a.appointment_time', 'ASC');
    
        $appointments = $builder->get()->getResultArray();
    
        if (empty($appointments)) {
            return $this->response->setJSON([
                'status' => false,
                'message' => 'No appointments found',
                'data' => []
            ]);
        }
    
        return $this->response->setJSON([
            'status' => true,
            'message' => 'Appointments loaded',
            'data' => $appointments
        ]);
    }
    


    public function mail()
    {
        // $body['from'] = 'itcerebral@gmail.com';
        // $body['to'] = 'iyinusa@yahoo.co.uk';
        // $body['subject'] = 'Test Email';
        // $body['text'] = 'Sending test email via mailgun API';
        // echo $this->Crud->mailgun($body);
        $to = 'kennethjames23@yahoo.com, iyinusa@yahoo.co.uk,tofunmi015@gmail.com';
        $subject = 'Test Email';
        $body = 'Sending test email from local email server';
        echo $this->Crud->send_email($to, $subject, $body);

        $email_body = [
            'from' => 'admin@mg.pcdl4kids.com>', // Replace with your domain's default sender
            'to' => 'tofunmi015@gmail.com', // Assuming $par->email contains the recipient's email address
            'subject' => 'Important Notification', // Replace with an appropriate subject
            'html' => $body // Use 'html' or 'text' based on your content type
        ];

        echo $this->Crud->mailgun($email_body);
    }
}
