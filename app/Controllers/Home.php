<?php

namespace App\Controllers;

class Home extends BaseController
{
    private $db;

    public function __construct()
    {
        // $this->db = \Config\Database::connect();
    }

    public function index()
    {

        // check login
        $log_id = $this->session->get('plx_id');




        $data['title'] = app_name;
        $data['page_active'] = 'home';
        return view('home/land', $data);
    }

    public function services()
    {

        // check login
        $log_id = $this->session->get('plx_id');

        $role_id = $this->Crud->read_field('id', $log_id, 'user', 'role_id');
        $role = strtolower($this->Crud->read_field('id', $role_id, 'access_role', 'name'));
        $role_c = $this->Crud->module($role_id, 'dashboard', 'create');
        $role_r = $this->Crud->module($role_id, 'dashboard', 'read');
        $role_u = $this->Crud->module($role_id, 'dashboard', 'update');
        $role_d = $this->Crud->module($role_id, 'dashboard', 'delete');


        $data['log_id'] = $log_id;
        $data['role'] = $role;
        $data['role_c'] = $role_c;



        $data['title'] = 'Services - ' . app_name;
        $data['page_active'] = 'home/services';
        return view('home/services', $data);
    }

    public function contact()
    {

        // check login
        $log_id = $this->session->get('plx_id');

        $role_id = $this->Crud->read_field('id', $log_id, 'user', 'role_id');
        $role = strtolower($this->Crud->read_field('id', $role_id, 'access_role', 'name'));
        $role_c = $this->Crud->module($role_id, 'dashboard', 'create');
        $role_r = $this->Crud->module($role_id, 'dashboard', 'read');
        $role_u = $this->Crud->module($role_id, 'dashboard', 'update');
        $role_d = $this->Crud->module($role_id, 'dashboard', 'delete');


        $data['log_id'] = $log_id;
        $data['role'] = $role;
        $data['role_c'] = $role_c;



        $data['title'] = 'Contact - ' . app_name;
        $data['page_active'] = 'home/contact';
        return view('home/contact', $data);
    }


    public function book($param1 = '', $param2 = '', $param3 = '')
    {

        // check login
        $log_id = $this->session->get('plx_id');

        $role_id = $this->Crud->read_field('id', $log_id, 'user', 'role_id');
        $role = strtolower($this->Crud->read_field('id', $role_id, 'access_role', 'name'));
        $role_c = $this->Crud->module($role_id, 'dashboard', 'create');
        $role_r = $this->Crud->module($role_id, 'dashboard', 'read');
        $role_u = $this->Crud->module($role_id, 'dashboard', 'update');
        $role_d = $this->Crud->module($role_id, 'dashboard', 'delete');


        $data['log_id'] = $log_id;
        $data['role'] = $role;
        $data['role_c'] = $role_c;

        $db = db_connect();
        if ($param1 == 'process') {
            if ($this->request->getMethod() == 'post') {
                $service_id = $this->request->getVar('service');
                $date = $this->request->getVar('date');
                $time = $this->request->getVar('time');
                $name = $this->request->getVar('name');
                $email = $this->request->getVar('email');
                $phone = $this->request->getVar('phone');
                $msg = $this->request->getVar('msg');

                // 🔑 sanitize/fix time (remove + from "3:00+PM")
                $time = str_replace('+', ' ', $time);

                // validate required fields
                if (empty($service_id) || empty($date) || empty($time) || empty($name) || empty($email)) {
                    return $this->response->setJSON([
                        'status' => false,
                        'msg' => 'Required fields missing'
                    ]);
                }

                // ✅ Check if user already exists
                $user = $db->table('sb_user')
                    ->where('email', $email)
                    ->get()->getRow();

                if ($user) {
                    $user_id = $user->id;
                } else {
                    // create new user
                    $userData = [
                        'fullname' => $name,
                        'email' => $email,
                        'phone' => $phone,
                        'role_id' => $this->Crud->read_field('name', 'Customer', 'access_role', 'id'),
                        'activate' => 1,
                        'is_customer' => 1,
                        'reg_date' => date(fdate),
                    ];
                    $db->table('sb_user')->insert($userData);
                    $user_id = $db->insertID();
                }

                // ✅ check if slot is already booked for this service/date/time
                $exists = $db->table('sb_appointments')
                    ->where('service_id', $service_id)
                    ->where('appointment_date', $date)
                    ->where('appointment_time', $time)
                    ->countAllResults();

                if ($exists > 0) {
                    return $this->response->setJSON([
                        'status' => false,
                        'msg' => 'This time slot is already booked'
                    ]);
                }

                // ✅ insert appointment
                $ins_data = [
                    'user_id' => $user_id,
                    'stylist_id' => 0, // optional if you assign stylists
                    'service_id' => $service_id,
                    'appointment_date' => $date,
                    'appointment_time' => $time,
                    'status' => 'pending',
                    'notes' => $msg,
                    'created_at' => date('Y-m-d H:i:s')
                ];

                $save = $this->Crud->create('sb_appointments', $ins_data);

                if ($save > 0) {
                    // prepare details
                    $subject = "New Appointment Booked";
                    $content = "A new appointment has been booked:<br>
                        Service ID: {$this->Crud->read_field('id', $service_id, 'services', 'name')}<br>
                        Date: {$date}<br>
                        Time: {$time}<br>
                        Name: {$name}<br>
                        Email: {$email}<br>
                        Phone: {$phone}<br>
                        Notes: {$msg}";

                    // 1. Email to admin
                    $adminEmail = $this->Crud->read_field('name', 'app_email', 'setting', 'value'); // 🔑 fetch from sb_setting
                    $admin_id = $this->Crud->read_field('email', $adminEmail, 'user', 'id');
                    $this->Crud->send_email($adminEmail, $subject, $content);

                    // 2. Internal notify to admin (e.g., user_id = 1 for superadmin)
                    $this->Crud->notify($user_id, $admin_id, "New appointment booked by {$name}", 'appointment', $save);

                    return $this->response->setJSON([
                        'status' => true,
                        'msg' => 'Appointment booked successfully',
                        'user_id' => $user_id,
                        'appointment_id' => $save
                    ]);
                } else {
                    return $this->response->setJSON([
                        'status' => false,
                        'msg' => 'Failed to book appointment, please try again'
                    ]);
                }
            }
        }



        $data['title'] = 'Book Now - ' . app_name;
        $data['page_active'] = 'home/book';
        return view('home/book', $data);
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
