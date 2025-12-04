<?php

namespace App\Controllers;

class Api extends BaseController
{
	private $token;

	public function __construct()
	{
		header("Access-Control-Allow-Origin: *");
		header("Access-Control-Allow-Methods: GET, POST, OPTIONS, PUT, DELETE");
		header("Access-Control-Allow-Headers: Content-Type, Content-Length, Accept-Encoding, Authorization");
		header("Content-Type: application/json; charset=UTF-8");

		$this->token = 'EAACva1Mk73MBAPCKAhIAxF01sWkiFYAwcViL6MXEi';

		// check token
		$token = null;
		$headers = apache_request_headers();
		if (isset($headers['authorization']) || isset($headers['Authorization'])) {
			if (isset($headers['authorization']))
				$token = $headers['authorization'];
			if (isset($headers['Authorization']))
				$token = $headers['Authorization'];
			$token = explode(' ', $token)[1];
		}

		if ($this->token != $token) {
			echo json_encode(array('status' => false, 'msg' => 'Invalid Token'));
			die;
		}
	}

	public function index()
	{
	}

	// register
	public function register()
	{
		$status = false;
		$data = array();
		$msg = '';

		// collect call paramters
		$call = json_decode(file_get_contents("php://input"));
		$fullname = $call->fullname;
		$email = $call->email;
		$phone = $call->phone;
		$password = $call->password;
		if (!empty($call->pin)) {
			$pin = $call->pin;
		} else {
			$pin = 1111;
		}
		if (!empty($call->dob)) {
			$dob = $call->dob;
		} else {
			$dob = '';
		}

		if ($fullname && $email && $password) {
			// check if record exists
			if ($this->Crud->check('email', $email, 'user') > 0) {
				$msg = 'Email Taken! Please use another email.';
			} else {
				// Check if referral_id exists in session
				$referral_id = session()->get('referral_id') ?? 0;

				$role_id = $this->Crud->read_field('name', 'User', 'access_role', 'id');
				$ins['fullname'] = $fullname;
				$ins['email'] = $email;
				$ins['phone'] = $phone;
				$ins['password'] = md5($password);
				if (!empty($dob))
					$ins['dob'] = $dob;
				if (!empty($pin))
					$ins['pin'] = $pin;
				$ins['role_id'] = $role_id;
				$ins['marketer_id'] = $referral_id;
				$ins['reg_date'] = date(fdate);
				$user_id = $this->Crud->create('user', $ins);
				if ($user_id > 0) {
					$status = true;
					$msg = 'Successfully!';
					$data['id'] = $user_id;
				} else {
					$msg = 'Oops! Try later';
				}
			}
		}

		echo json_encode(array('status' => $status, 'msg' => $msg, 'data' => $data));
		die;
	}

	// login
	public function login()
	{
		$status = false;
		$data = array();
		$msg = '';

		// collect call paramters
		$call = json_decode(file_get_contents("php://input"));
		$email = $call->email;
		$password = md5($call->password);

		if ($email && $password) {
			$query = $this->Crud->read2('email', $email, 'password', $password, 'user');
			if (empty($query)) {
				$msg = 'Invalid Authentication!!';
			} else {
				$status = true;
				$msg = 'Login Successfully!';

				$id = $this->Crud->read_field('email', $email, 'user', 'id');
				$data = $this->user_data($id);
			}
		}

		echo json_encode(array('status' => $status, 'msg' => $msg, 'data' => $data));
		die;
	}

	// reset code
	public function reset_code()
	{
		$status = false;
		$data = array();
		$msg = '';

		// collect call paramters
		$call = json_decode(file_get_contents("php://input"));
		$email = $call->email;

		if ($email) {
			$user_id = $this->Crud->read_field('email', $email, 'user', 'id');
			if (empty($user_id)) {
				$msg = 'Invalid Email!';
			} else {
				$code = substr(md5(time() . rand()), 0, 6);
				if ($this->Crud->updates('id', $user_id, 'user', array('reset_code' => $code)) > 0) {
					$status = true;
					$msg = 'Code Sent!';
					$data['code'] = $code;

					$fullname = $this->Crud->read_field('id', $user_id, 'user', 'fullname');

					// email content
					$bcc = '';
					$subject = 'Reset Code';
					$body = 'You requested to reset your account password. Your secret code is ' . $code . '. If you do not request this action, please ignore. Your account will be protected. Thank you.';
					$this->send_email($email, $subject, $body, $fullname);
				}
			}
		}

		echo json_encode(array('status' => $status, 'msg' => $msg, 'data' => $data));
		die;
	}

	// reset password
	public function reset_password()
	{
		$status = false;
		$data = array();
		$msg = '';

		// collect call paramters
		$call = json_decode(file_get_contents("php://input"));
		$email = $call->email;
		$password = $call->password;

		if ($email && $password) {
			$user_id = $this->Crud->read_field('email', $email, 'user', 'id');
			if (empty($user_id)) {
				$msg = 'Invalid Email!';
			} else {
				if ($this->Crud->updates('id', $user_id, 'user', array('password' => md5($password))) > 0) {
					$status = true;
					$msg = 'Password Reset Successfully!';
				}
			}
		}

		echo json_encode(array('status' => $status, 'msg' => $msg, 'data' => $data));
		die;
	}

	// profile
	public function profile($id, $type = 'get')
	{
		$status = false;
		$data = array();
		$msg = '';

		/// GET
		if ($type == 'get') {
			$status = true;
			$data = $this->user_data($id);
			$msg = 'Successful';
		}

		/// UPDATE
		if ($type == 'update') {
			// collect call paramters
			$call = json_decode(file_get_contents("php://input"));
			$field = $call->field;
			$value = $call->value;

			if ($field == 'password')
				$value = md5($value);

			$process = true;

			// check email
			if ($field == 'email') {
				$old_email = $this->Crud->read_field('id', $id, 'user', 'email');
				if ($old_email != $value) {
					if ($this->Crud->check('email', $value, 'user') > 0) {
						$process = false;
						$msg = 'Email already exists!';
					}
				}
			}

			// process update
			if ($process == true) {
				$this->Crud->updates('id', $id, 'user', array($field => $value));
				$status = true;
				$msg = 'Successful!';
				$data = $this->user_data($id);
			}
		}

		echo json_encode(array('status' => $status, 'msg' => $msg, 'data' => $data));
		die;
	}

	// payment
	public function payment($mode)
	{
		$status = false;
		$data = array();
		$msg = '';

		/// COUPON
		if ($mode == 'coupon') {
			$call = json_decode(file_get_contents("php://input"));
			$user_id = $call->user_id;
			$sub_id = $call->sub_id;
			$coupon = $call->coupon;

			if (empty($coupon)) {
				$msg = 'No Coupon Code!';
			} else {
				// check coupon code status
				$coupon_id = '';
				$coup = $this->Crud->read_like('coupon', 'code', $coupon);

				if (!empty($coup)) {
					foreach ($coup as $c) {
						if ($c->used > 0)
							continue;
						$coupon_id = $c->id;
					}
					if (empty($coupon_id)) {
						$msg = 'Coupon Code Already Used!';
					} else {
						$sub = $this->subscribe($user_id, $sub_id, $coupon_id, 0);
						if (!empty($sub['id'])) {
							// update coupon
							$this->Crud->updates('id', $coupon_id, 'coupon', array('user_id' => $user_id, 'used' => 1, 'used_date' => date(fdate)));

							$status = true;
							$msg = 'Coupon Code Successfully Applied!';
							$data = $sub;
						}
					}
				} else {
					$msg = 'Invalid Coupon Code!';

				}
			}
		}

		/// CARD
		if ($mode == 'card') {
			$call = json_decode(file_get_contents("php://input"));
			$user_id = $call->user_id;
			$sub_id = $call->sub_id;
			if (!empty($call->curr)) {
				$curr = $call->curr;
			} else {
				$curr = 'USD';
			}
			if (!empty($call->pay_id)) {
				$pay_id = $call->pay_id;
			} else {
				$pay_id = '';
			}

			if ($curr == 'USD') {
				$pay_type = 'unified_payment';
				$amt = $this->Crud->read_field('id', $sub_id, 'subscription', 'amount');
			} else {
				$pay_type = 'nigerian';
				$amt = $this->Crud->read_field('id', $sub_id, 'subscription', 'naira');
			}

			if (!empty($user_id) && !empty($sub_id)) {
				if (empty($pay_id)) {
					// inititalize payment
					$pdata['currency'] = $curr;
					$pdata['amount'] = (float) $amt * 100;
					$pdata['description'] = $this->Crud->read_field('id', $sub_id, 'subscription', 'name') . ' by ' . $this->Crud->read_field('id', $user_id, 'user', 'fullname');
					$pdata['merchant_callback_url'] = site_url('payment/response');
					// $pdata['merchant_callback_url'] = "javascript:;";
					$pdata['metadata'] = array('sub_id' => $sub_id, 'user_id' => $user_id);
					$pdata['payment_type'] = $pay_type;

					$pdata['device_signature'] = md5(time() . rand());
					$pdata['email'] = $this->Crud->read_field('id', $user_id, 'user', 'email');
					$pdata['ip_address'] = $this->getIPAddress();

					$reply = $this->Crud->kpay_post('payment/initialize', json_encode($pdata));
					if (!empty($reply)) {
						$rep = json_decode($reply);
						if (!empty($rep->payment_id)) {
							$status = true;
							$msg = 'Payment Successfully Initiated!';
							$data = $rep->payment_id;
						} else {
							$msg = 'Payment Initializing Failed!';
						}
					} else {
						$msg = 'Payment Initializing Failed';
					}
				} else {
					// give value if payment is successful
					$reply = $this->Crud->kpay_get($pay_id);
					if (!empty($reply)) {
						$rep = json_decode($reply);
						if ($rep->status != 'SUCCESS') {
							$msg = 'Payment Not Successful!';
						} else {
							$sub = $this->subscribe($user_id, $sub_id, 0, 0, $reply);
							if (!empty($sub['id'])) {
								$status = true;
								$msg = 'Pay Successful! Subscription Applied!';
								$data = $sub;
							}
						}
					} else {
						$msg = 'Invalid Payment';
					}
				}
			}
		}

		echo json_encode(array('status' => $status, 'msg' => $msg, 'data' => $data));
		die;
	}

	// subscription
	public function subscription($type = 'get')
	{
		$status = false;
		$data = array();
		$msg = '';

		/// POST
		if ($type == 'post') {
			// collect call paramters
			$call = json_decode(file_get_contents("php://input"));
			$user_id = $call->user_id;
			$sub_id = $call->sub_id;
			$start_date = $call->start_date;
			$end_date = $call->end_date;
			if (!empty($call->pin))
				$pin = $call->pin;

			$ins['sub_id'] = $sub_id;
			if (!empty($pin))
				$ins['pin'] = $pin;
			$ins['start_date'] = date('Y-m-d', strtotime($start_date));
			$ins['end_date'] = date('Y-m-d', strtotime($end_date));

			$ins_id = $this->Crud->updates('id', $user_id, 'user', $ins);
			if ($ins_id > 0) {
				$status = true;
				$msg = 'Successful';
				$data = $this->user_data($user_id);
			} else {
				$msg = 'Oops! Try later';
			}
		}

		/// GET
		if ($type == 'get') {
			$query = $this->Crud->read_order('subscription', 'id', 'asc');
			if (!empty($query)) {
				$status = true;
				$msg = 'Successful';
				foreach ($query as $q) {
					$item = array();

					$item['id'] = $q->id;
					$item['name'] = $q->name;
					$item['amount'] = $q->amount;
					$item['naira'] = number_format($q->naira);

					$data[] = $item;
				}
			}
		}

		echo json_encode(array('status' => $status, 'msg' => $msg, 'data' => $data));
		die;
	}

	// child
	public function child($parent_id, $type = 'get')
	{
		$status = false;
		$data = array();
		$msg = '';

		/// POST 
		if ($type == 'post') {
			// collect call paramters
			$call = json_decode(file_get_contents("php://input"));

			$child1_id = $call->child1ID;
			$child1_name = $call->child1Name;
			$child1_age = $call->child1Age;
			$child1_image = $call->child1Image;

			$child2_id = $call->child2ID;
			$child2_name = $call->child2Name;
			$child2_age = $call->child2Age;
			$child2_image = $call->child2Image;

			$child3_id = $call->child3ID;
			$child3_name = $call->child3Name;
			$child3_age = $call->child3Age;
			$child3_image = $call->child3Image;

			// process child 1
			if ($child1_name && $child1_image) {
				$ins['parent_id'] = $parent_id;
				$ins['name'] = $child1_name;
				$ins['age_id'] = $this->Crud->read_field('name', $child1_age, 'age', 'id');
				$ins['avatar'] = $child1_image;
				if ($this->Crud->check2('parent_id', $parent_id, 'id', $child1_id, 'child') > 0) {
					// update
					if ($this->Crud->updates('id', $child1_id, 'child', $ins) > 0)
						$status = true;
				} else {
					if ($this->Crud->check('parent_id', $parent_id, 'child') > 3) {
						$status = false;
						$msg = 'Maximum number of Children per Parent is 3';
					} else {
						// insert
						$ins['reg_date'] = date(fdate);
						if ($this->Crud->create('child', $ins) > 0)
							$status = true;
					}

				}
			}

			// process child 2
			if ($child2_name && $child2_image) {
				$ins = array();
				$ins['parent_id'] = $parent_id;
				$ins['name'] = $child2_name;
				$ins['age_id'] = $this->Crud->read_field('name', $child2_age, 'age', 'id');
				$ins['avatar'] = $child2_image;
				if ($this->Crud->check2('parent_id', $parent_id, 'id', $child2_id, 'child') > 0) {
					// update
					if ($this->Crud->updates('id', $child2_id, 'child', $ins) > 0)
						$status = true;
				} else {
					if ($this->Crud->check('parent_id', $parent_id, 'child') > 3) {
						$status = false;
						$msg = 'Maximum number of Children per Parent is 3';
					} else {
						// insert
						$ins['reg_date'] = date(fdate);
						if ($this->Crud->create('child', $ins) > 0)
							$status = true;
					}
				}
			}

			// process child 3
			if ($child3_name && $child3_image) {
				$ins = array();
				$ins['parent_id'] = $parent_id;
				$ins['name'] = $child3_name;
				$ins['age_id'] = $this->Crud->read_field('name', $child3_age, 'age', 'id');
				$ins['avatar'] = $child3_image;
				if ($this->Crud->check2('parent_id', $parent_id, 'id', $child3_id, 'child') > 0) {
					// update
					if ($this->Crud->updates('id', $child3_id, 'child', $ins) > 0)
						$status = true;
				} else {
					if ($this->Crud->check('parent_id', $parent_id, 'child') > 3) {
						$status = false;
						$msg = 'Maximum number of Children per Parent is 3';
					} else {
						// insert
						$ins['reg_date'] = date(fdate);
						if ($this->Crud->create('child', $ins) > 0)
							$status = true;
					}
				}
			}
		}

		/// GET 
		if ($type == 'get') {
			// collect call paramters
			$child_id = $this->request->getGet('child_id');

			if ($child_id) {
				$query = $this->Crud->read_single('id', $child_id, 'child');
			} else {
				$query = $this->Crud->read_single_order('parent_id', $parent_id, 'child', 'id', 'asc');
			}
			if (!empty($query)) {
				$status = true;
				$msg = 'Successful';
				foreach ($query as $q) {
					$item = array();

					$item['id'] = $q->id;
					$item['name'] = $q->name;
					$item['age_id'] = $q->age_id;
					$item['age'] = $this->Crud->read_field('id', $q->age_id, 'age', 'name');
					$item['image'] = $q->avatar;
					$item['reg_date'] = date('M d, Y', strtotime($q->reg_date));

					$data[] = $item;
				}
			}
		}

		echo json_encode(array('status' => $status, 'msg' => $msg, 'data' => $data));
		die;
	}

	// search
	public function search()
	{
		$status = false;
		$data = array();
		$msg = '';

		// collect call paramters
		$search = $this->request->getGet('q');
		if (!empty($this->request->getGet('limit'))) {
			$limit = $this->request->getGet('limit');
		} else {
			$limit = 500;
		}

		if (empty($search)) {
			$msg = 'Search parameter is missing.';
		} else {
			$query = $this->Crud->filter_search($limit, '', $search);
			if (!empty($query)) {
				$status = true;
				$msg = 'Successful';
				foreach ($query as $q) {
					$item = array();

					$free = true;
					if ($q->free == 0)
						$free = false;

					$item['id'] = $q->id;
					$item['category_id'] = $q->category_id;
					$item['category'] = $q->category;
					$item['production_id'] = $q->production_id;
					$item['production'] = $q->production;
					$item['age_id'] = $q->age_id;
					$item['name'] = $q->title;
					$item['image'] = site_url($q->img);
					$item['url'] = site_url($q->url);
					$item['free'] = $free;

					if (!empty($q->url))
						$data[] = $item;
				}
			}
		}

		echo json_encode(array('status' => $status, 'msg' => $msg, 'data' => $data));
		die;
	}

	// category
	public function category($type = 'get', $id = '')
	{
		$status = false;
		$data = array();
		$msg = '';

		/// GET 
		if ($type == 'get') {
			// collect call paramters
			if (!empty($this->request->getGet('limit'))) {
				$limit = $this->request->getGet('limit');
			} else {
				$limit = 500;
			}

			// query
			$query = $this->Crud->read('category', $limit);
			if (!empty($query)) {
				$status = true;
				$msg = 'Successful';
				foreach ($query as $q) {
					$item = array();

					$item['id'] = $q->id;
					$item['name'] = $q->name;

					$data[] = $item;
				}
			}
		}

		/// VIDEOS 
		if ($type == 'videos') {
			// collect call paramters
			if (!empty($this->request->getGet('limit'))) {
				$limit = $this->request->getGet('limit');
			} else {
				$limit = 500;
			}

			// query
			$query = $this->Crud->read_single('category_id', $id, 'video', $limit);
			if (!empty($query)) {
				$status = true;
				$msg = 'Successful';
				foreach ($query as $q) {
					$item = array();

					$free = true;
					if ($q->free == 0)
						$free = false;

					$item['id'] = $q->id;
					$item['category_id'] = $id;
					$item['category'] = $this->Crud->read_field('id', $id, 'category', 'name');
					$item['production_id'] = $q->production_id;
					$item['production'] = $this->Crud->read_field('id', $q->production_id, 'production', 'name');
					$item['age_id'] = $q->age_id;
					$item['name'] = $q->title;
					$item['image'] = site_url($q->img);
					$item['url'] = site_url($q->url);
					$item['free'] = $free;

					if (!empty($q->url))
						$data[] = $item;
				}
			}
		}

		echo json_encode(array('status' => $status, 'msg' => $msg, 'data' => $data));
		die;
	}

	// production
	public function production($type = 'get')
	{
		$status = false;
		$data = array();
		$msg = '';

		/// GET 
		if ($type == 'get') {
			// collect call paramters
			$age_id = $this->request->getGet('age_id');
			if (!empty($this->request->getGet('limit'))) {
				$limit = $this->request->getGet('limit');
			} else {
				$limit = 500;
			}

			// query
			$query = $this->Crud->read('production', $limit);
			if (!empty($query)) {
				$status = true;
				$msg = 'Successful';
				foreach ($query as $q) {
					$item = array();

					$item['id'] = $q->id;
					$item['name'] = $q->name;
					$item['image'] = site_url($q->image);

					// load videos in production
					$videos = array();
					$query_videos = $this->Crud->read2('production_id', $q->id, 'language_id', 183, 'video');
					if (!empty($query_videos)) {
						foreach ($query_videos as $v) {
							if (!empty($age_id)) {
								if ($v->age_id != $age_id && $v->age_id != 0)
									continue;
							}

							$vItem = array();

							$free = true;
							if ($v->free == 0)
								$free = false;

							$vItem['id'] = $v->id;
							$vItem['category_id'] = $v->category_id;
							$vItem['category'] = $this->Crud->read_field('id', $v->category_id, 'category', 'name');
							$vItem['age_id'] = $v->age_id;
							$vItem['name'] = $v->title;
							$vItem['image'] = site_url($v->img);
							$vItem['url'] = site_url($v->url);
							$vItem['free'] = $free;

							if (!empty($v->url))
								$videos[] = $vItem;
						}
					}
					$item['videos'] = $videos;

					$data[] = $item;
				}
			}
		}

		/// GET VIDEOS
		if ($type == 'getVideos') {
			// collect call paramters
			$call = json_decode(file_get_contents("php://input"));
			$videoIds = $call->videoIds;
			if (!empty($this->request->getGet('age_id'))) {
				$age_id = $this->request->getGet('age_id');
			} else {
				$age_id = 0;
			}

			if (empty($videoIds)) {
				$msg = 'Please provide at least 1 Video ID, or multiple IDs separated by a comma (,)';
			} else {
				$ids = explode(',', $videoIds);
				if (!empty($ids)) {
					foreach ($ids as $id) {
						$item = array();

						if (!empty($age_id)) {
							$query = $this->Crud->read2('id', trim($id), 'age_id', $age_id, 'video');
						} else {
							$query = $this->Crud->read_single('id', trim($id), 'video');
						}
						if (!empty($query)) {
							$status = true;
							$msg = 'Successful';
							foreach ($query as $q) {
								$item['id'] = $q->id;
								$item['channel_id'] = $q->production_id;
								$item['channel'] = $this->Crud->read_field('id', $q->production_id, 'production', 'name');
								$item['name'] = $q->title;
								$item['image'] = site_url($q->img);
								$item['url'] = site_url($q->url);
							}
						}

						if (!empty($item))
							$data[] = $item;
					}
				}

				if (empty($data))
					$msg = 'No Video Found';
			}
		}

		echo json_encode(array('status' => $status, 'msg' => $msg, 'data' => $data));
		die;
	}

	// recent
	public function recent($type = 'get')
	{
		$status = false;
		$data = array();
		$msg = '';

		/// GET 
		if ($type == 'get') {
			// collect call paramters
			$user_id = $this->request->getGet('user_id');
			if (!empty($this->request->getGet('limit'))) {
				$limit = $this->request->getGet('limit');
			} else {
				$limit = 500;
			}

			// query
			$query = $this->Crud->read_single_order('user_id', $user_id, 'recent', 'views', 'desc', $limit);
			if (!empty($query)) {
				$status = true;
				$msg = 'Successful';
				foreach ($query as $q) {
					$item = array();

					$item['id'] = $q->id;
					$item['user_id'] = $q->user_id;
					$item['item'] = $q->item;
					$item['item_id'] = $q->item_id;

					$production_id = $this->Crud->read_field('id', $q->item_id, 'video', 'production_id');
					$video_title = $this->Crud->read_field('id', $q->item_id, 'video', 'title');
					$video_img = $this->Crud->read_field('id', $q->item_id, 'video', 'img');
					$video_url = $this->Crud->read_field('id', $q->item_id, 'video', 'url');
					$video_free = $this->Crud->read_field('id', $q->item_id, 'video', 'free');

					$free = true;
					if ($video_free == 0)
						$free = false;

					$item['production_id'] = $production_id;
					$item['production'] = $this->Crud->read_field('id', $production_id, 'production', 'name');
					$item['name'] = $video_title;
					$item['image'] = site_url($video_img);
					$item['url'] = site_url($video_url);
					$item['free'] = $free;

					if (!empty($video_url))
						$data[] = $item;
				}
			}
		}

		echo json_encode(array('status' => $status, 'msg' => $msg, 'data' => $data));
		die;
	}

	// favourite
	public function favourite($type = 'get')
	{
		$status = false;
		$data = array();
		$msg = '';

		/// GET 
		if ($type == 'get') {
			// collect call paramters
			$user_id = $this->request->getGet('user_id');

			// query
			$query = $this->Crud->read_single('user_id', $user_id, 'favourite');
			if (!empty($query)) {
				$status = true;
				$msg = 'Successful';
				foreach ($query as $q) {
					$item = array();

					$item['id'] = $q->id;
					$item['user_id'] = $q->user_id;
					$item['item'] = $q->item;
					$item['item_id'] = $q->item_id;

					$production_id = $this->Crud->read_field('id', $q->item_id, 'video', 'production_id');
					$video_title = $this->Crud->read_field('id', $q->item_id, 'video', 'title');
					$video_img = $this->Crud->read_field('id', $q->item_id, 'video', 'img');
					$video_url = $this->Crud->read_field('id', $q->item_id, 'video', 'url');
					$video_free = $this->Crud->read_field('id', $q->item_id, 'video', 'free');

					$free = true;
					if ($video_free == 0)
						$free = false;

					$item['production_id'] = $production_id;
					$item['production'] = $this->Crud->read_field('id', $production_id, 'production', 'name');
					$item['name'] = $video_title;
					$item['image'] = site_url($video_img);
					$item['url'] = site_url($video_url);
					$item['free'] = $free;

					if (!empty($video_url))
						$data[] = $item;
				}
			}
		}

		/// POST
		if ($type == 'post') {
			// collect call paramters
			$call = json_decode(file_get_contents("php://input"));
			$user_id = $call->user_id;
			$item = $call->item;
			$item_id = $call->item_id;

			$fav_id = $this->Crud->read_field3('item', $item, 'item_id', $item_id, 'user_id', $user_id, 'favourite', 'id');
			if (!empty($fav_id)) {
				$this->Crud->deletes('id', $fav_id, 'favourite');
			} else {
				$ins['user_id'] = $user_id;
				$ins['item'] = $item;
				$ins['item_id'] = $item_id;
				$ins['views'] = 0;
				$ins['reg_date'] = date(fdate);
				$ins_id = $this->Crud->create('favourite', $ins);
				if (!empty($ins_id)) {
					$status = true;
					$msg = 'Successful';
				} else {
					$status = false;
					$msg = 'Failed';
				}
			}
		}

		/// CHECK FAVOURITE
		if ($type == 'check') {
			// collect call paramters
			$user_id = $this->request->getGet('user_id');
			$item = $this->request->getGet('item');
			$item_id = $this->request->getGet('item_id');

			$fav_id = $this->Crud->read_field3('item', $item, 'item_id', $item_id, 'user_id', $user_id, 'favourite', 'id');
			if (!empty($fav_id)) {
				$status = true;
				$msg = 'Favourite';
			} else {
				$msg = 'Not Favourite';
			}
		}

		echo json_encode(array('status' => $status, 'msg' => $msg, 'data' => $data));
		die;
	}

	// release
	public function release($type = 'get')
	{
		$status = false;
		$data = array();
		$msg = '';

		/// GET 
		if ($type == 'get') {
			// collect call paramters
			$age_id = $this->request->getGet('age_id');
			$production_id = $this->request->getGet('production_id');
			if (!empty($this->request->getGet('limit'))) {
				$limit = $this->request->getGet('limit');
			} else {
				$limit = 500;
			}

			// query
			if (!empty($production_id)) {
				$query = $this->Crud->read2_order('production_id', $production_id, 'language_id', 183, 'video', 'id', 'desc', $limit);
			} else {
				$query = $this->Crud->read_single_order('language_id', 183, 'video', 'id', 'desc', $limit);
			}
			if (!empty($query)) {
				$status = true;
				$msg = 'Successful';
				foreach ($query as $q) {
					if (!empty($age_id)) {
						if ($q->age_id != $age_id && $q->age_id != 0)
							continue;
					}

					$item = array();

					$free = true;
					if ($q->free == 0)
						$free = false;

					$item['id'] = $q->id;
					$item['production_id'] = $q->production_id;
					$item['production'] = $this->Crud->read_field('id', $q->production_id, 'production', 'name');
					$item['category_id'] = $q->category_id;
					$item['category'] = $this->Crud->read_field('id', $q->category_id, 'category', 'name');
					$item['name'] = $q->title;
					$item['image'] = site_url($q->img);
					$item['url'] = site_url($q->url);
					$item['free'] = $free;

					if (!empty($q->url))
						$data[] = $item;
				}
			}
		}

		echo json_encode(array('status' => $status, 'msg' => $msg, 'data' => $data));
		die;
	}

	// trending
	public function trending($type = 'get')
	{
		$status = false;
		$data = array();
		$msg = '';

		/// GET 
		if ($type == 'get') {
			// collect call paramters
			$age_id = $this->request->getGet('age_id');
			if (!empty($this->request->getGet('limit'))) {
				$limit = $this->request->getGet('limit');
			} else {
				$limit = 500;
			}

			// query
			$query = $this->Crud->read_order('video', 'views', 'desc', $limit);
			if (!empty($query)) {
				$status = true;
				$msg = 'Successful';
				foreach ($query as $q) {
					if (!empty($age_id)) {
						if ($q->age_id != $age_id && $q->age_id != 0)
							continue;
					}

					$item = array();

					$free = true;
					if ($q->free == 0)
						$free = false;

					$item['id'] = $q->id;
					$item['production_id'] = $q->production_id;
					$item['production'] = $this->Crud->read_field('id', $q->production_id, 'production', 'name');
					$item['category_id'] = $q->category_id;
					$item['category'] = $this->Crud->read_field('id', $q->category_id, 'category', 'name');
					$item['name'] = $q->title;
					$item['image'] = site_url($q->img);
					$item['url'] = site_url($q->url);
					$item['free'] = $free;

					if (!empty($q->url))
						$data[] = $item;
				}
			}
		}

		echo json_encode(array('status' => $status, 'msg' => $msg, 'data' => $data));
		die;
	}

	// release
	public function game($type = 'get')
	{
		$status = false;
		$data = array();
		$msg = '';

		/// GET 
		if ($type == 'get') {
			// collect call paramters
			$age_id = $this->request->getGet('age_id');
			if (!empty($this->request->getGet('limit'))) {
				$limit = $this->request->getGet('limit');
			} else {
				$limit = 500;
			}

			// query
			$query = $this->Crud->read_order('game', 'id', 'desc', $limit);
			if (!empty($query)) {
				$status = true;
				$msg = 'Successful';
				foreach ($query as $q) {
					if (!empty($age_id)) {
						if ($q->age_id != $age_id && $q->age_id != 0)
							continue;
					}

					$item = array();

					$item['id'] = $q->id;
					$item['age_id'] = $q->age_id;
					$item['name'] = $q->title;
					$item['image'] = site_url($q->img);
					$item['url'] = $q->url;

					if (!empty($q->url))
						$data[] = $item;
				}
			}
		}

		echo json_encode(array('status' => $status, 'msg' => $msg, 'data' => $data));
		die;
	}

	// translation
	public function translation($type = 'get', $id = '')
	{
		$status = false;
		$data = array();
		$msg = '';

		/// GET 
		if ($type == 'get') {
			// collect call paramters
			if (!empty($this->request->getGet('limit'))) {
				$limit = $this->request->getGet('limit');
			} else {
				$limit = 50;
			}

			// query
			$query = $this->Crud->read_order('language', 'name', 'asc', $limit);
			if (!empty($query)) {
				$status = true;
				$msg = 'Successful';
				foreach ($query as $q) {
					$item = array();

					$item['id'] = $q->id;
					$item['name'] = $q->name;

					$data[] = $item;
				}
			}
		}

		/// VIDEOS 
		if ($type == 'videos') {
			// collect call paramters
			if (!empty($this->request->getGet('limit'))) {
				$limit = $this->request->getGet('limit');
			} else {
				$limit = 500;
			}

			// query
			$query = $this->Crud->read_single('language_id', $id, 'video', $limit);
			if (!empty($query)) {
				$status = true;
				$msg = 'Successful';
				foreach ($query as $q) {
					$item = array();

					$free = true;
					if ($q->free == 0)
						$free = false;

					$item['id'] = $q->id;
					$item['category_id'] = $id;
					$item['category'] = $this->Crud->read_field('id', $id, 'category', 'name');
					$item['production_id'] = $q->production_id;
					$item['production'] = $this->Crud->read_field('id', $q->production_id, 'production', 'name');
					$item['age_id'] = $q->age_id;
					$item['name'] = $q->title;
					$item['translation'] = $this->Crud->read_field('id', $q->language_id, 'language', 'name');
					$item['image'] = site_url($q->img);
					$item['url'] = site_url($q->url);
					$item['free'] = $free;

					if (!empty($q->url))
						$data[] = $item;
				}
			}
		}

		echo json_encode(array('status' => $status, 'msg' => $msg, 'data' => $data));
		die;
	}

	// update view
	public function view()
	{
		$type = $this->request->getGet('type');
		$id = $this->request->getGet('id');
		$user_id = $this->request->getGet('user_id');

		$views = $this->Crud->read_field('id', $id, $type, 'views');
		$views += 1;

		$this->Crud->updates('id', $id, $type, array('views' => $views));

		// add to recent
		if ($type == 'video') {
			$watch_ins['user_id'] = $user_id;
			$watch_ins['video_id'] = $id;
			$watch_ins['reg_date'] = date(fdate);
			$this->Crud->create('watch_history', $watch_ins);


			$r_id = $this->Crud->read_field3('item', $type, 'item_id', $id, 'user_id', $user_id, 'recent', 'id');
			if (empty($r_id)) {
				$ins['user_id'] = $user_id;
				$ins['item'] = $type;
				$ins['item_id'] = $id;
				$ins['views'] = 1;
				$ins['reg_date'] = date(fdate);
				$this->Crud->create('recent', $ins);
			} else {
				$r_views = $this->Crud->read_field('id', $r_id, 'recent', 'views');
				$r_views += 1;
				$this->Crud->updates('id', $r_id, 'recent', array('views' => $r_views));
			}


		}

		echo $views;
		die;
	}

	// Reels API
	public function reels($type = 'get')
	{
		$status = false;
		$msg = 'Invalid request';
		$data = [];

		/// GET reels (filter by language)
		if ($type == 'get') {
			$language_id = $this->request->getGet('language_id');
			$limit = $this->request->getGet('limit') ?? 100;

			$query = $this->Crud->read_order('pl_reel', 'views', 'desc', $limit);
			if (!empty($query)) {
				$status = true;
				$msg = 'Successful';

				foreach ($query as $q) {
					if (!empty($language_id) && $q->language_id != $language_id)
						continue;

					$item = [
						'id' => $q->id,
						'video_id' => $q->video_id,
						'title' => $q->title,
						'language_id' => $q->language_id,
						'language' => $this->Crud->read_field('id', $q->language_id, 'language', 'name'),
						'category_id' => $q->category_id,
						'category' => $this->Crud->read_field('id', $q->category_id, 'category', 'name'),
						'production_id' => $q->production_id,
						'production' => $this->Crud->read_field('id', $q->production_id, 'production', 'name'),
						'url' => site_url($q->reel_url),
						'likes' => $q->likes,
						'views' => $q->views,
						'created_at' => $q->reg_date
					];

					$data[] = $item;
				}
			}
		}

		/// LIKE reel
		elseif ($type == 'like') {
			$reel_id = $this->request->getPost('reel_id') ?? $this->request->getGet('reel_id');
			$user_id = $this->request->getPost('user_id') ?? $this->request->getGet('user_id');
		
			if ($reel_id && $user_id) {
				$check = $this->Crud->read2('reel_id', $reel_id, 'user_id', $user_id, 'pl_reel_engagement');
				if (!empty($check)) {
					$liked = $check[0]->liked ? 0 : 1;
					$this->Crud->updates('id', $check[0]->id, 'pl_reel_engagement', ['liked' => $liked]);
				} else {
					$this->Crud->create('pl_reel_engagement', [
						'reel_id' => $reel_id,
						'user_id' => $user_id,
						'liked' => 1,
						'viewed' => 0,
						'view_count' => 0,
						'last_viewed' => null
					]);
					$liked = 1;
				}
		
				$likes = $this->Crud->check('liked', 1, 'pl_reel_engagement', ['reel_id' => $reel_id]);
				$this->Crud->updates('id', $reel_id, 'pl_reel', ['likes' => $likes]);
		
				$status = true;
				$msg = $liked ? 'Reel Liked' : 'Reel Unliked';
				$data = ['reel_id' => $reel_id, 'likes' => $likes, 'liked' => $liked];
			} else {
				$msg = 'Missing parameters';
			}
		}
		
		/// PLAY reel → increment views
		elseif ($type == 'play') {
			$reel_id = $this->request->getPost('reel_id');
			$user_id = $this->request->getPost('user_id');

			if ($reel_id) {
				// increment reel views
				$currentViews = $this->Crud->read_field('id', $reel_id, 'pl_reel', 'views');
				$newViews = $currentViews + 1;
				$this->Crud->updates('id', $reel_id, 'pl_reel', ['views' => $newViews]);

				// track engagement if user is provided
				if (!empty($user_id)) {
					$check = $this->Crud->read2('reel_id', $reel_id, 'user_id', $user_id, 'pl_reel_engagement');
					if (!empty($check)) {
						$viewCount = $check[0]->view_count + 1;
						$this->Crud->updates('id', $check[0]->id, 'pl_reel_engagement', [
							'viewed' => 1,
							'view_count' => $viewCount,
							'last_viewed' => date('Y-m-d H:i:s')
						]);
					} else {
						$this->Crud->create('pl_reel_engagement', [
							'reel_id' => $reel_id,
							'user_id' => $user_id,
							'liked' => 0,
							'viewed' => 1,
							'view_count' => 1,
							'last_viewed' => date('Y-m-d H:i:s')
						]);
					}
				}

				$status = true;
				$msg = 'View recorded';
				$data = ['reel_id' => $reel_id, 'views' => $newViews];
			} else {
				$msg = 'Missing reel_id';
			}
		}

		/// ANALYTICS reel
		elseif ($type == 'analytics') {
			$reel_id = $this->request->getGet('reel_id');
			if ($reel_id) {
				$likes = $this->Crud->check('liked', 1, 'pl_reel_engagement', ['reel_id' => $reel_id]);
				$views = $this->Crud->read_field('id', $reel_id, 'pl_reel', 'views');

				$status = true;
				$msg = 'Analytics loaded';
				$data = [
					'reel_id' => $reel_id,
					'likes' => $likes,
					'views' => $views,
				];
			} else {
				$msg = 'Missing reel_id';
			}
		}

		echo json_encode(['status' => $status, 'msg' => $msg, 'data' => $data]);
		die;
	}


	//// others //////
	private function user_data($id)
	{
		$query = $this->Crud->read_single('id', $id, 'user');
		if (!empty($query)) {
			foreach ($query as $q) {
				// check sub
				if ($q->sub_id == 0) {
					$expired = true;
				} else {
					$expired = false;
					if (empty($q->end_date)) {
						if (!empty($q->start_date)) {
							$expired = true;
						}
					} else {
						if ($this->Crud->date_diff(date('Y-m-d'), date('Y-m-d', strtotime($q->end_date))) <= 0)
							$expired = true;
					}
				}

				$start_date = '';
				$end_date = '';
				if (!empty(($q->start_date)))
					$start_date = date('M d, Y', strtotime($q->start_date));
				if (!empty(($q->end_date)))
					$end_date = date('M d, Y', strtotime($q->end_date));

				$data['id'] = $q->id;
				$data['fullname'] = $q->fullname;
				$data['email'] = $q->email;
				$data['phone'] = $q->phone;
				$data['pin'] = $q->pin;
				$data['sub_id'] = $q->sub_id;
				$data['sub'] = $this->Crud->read_field('id', $q->sub_id, 'subscription', 'name');
				$data['start_date'] = $start_date;
				$data['end_date'] = $end_date;
				$data['expired'] = $expired;
				$data['reg_date'] = date('M d, Y h:i A', strtotime($q->reg_date));
				return $data;
			}
		} else {
			return false;
		}
	}

	private function subscribe($user_id, $sub_id, $coupon_id = 0, $trans_id = 0, $res = '')
	{
		$recurrent = $this->Crud->read_field('id', $sub_id, 'subscription', 'type');

		$start_date = date(fdate);

		// check for user next expiry
		$expiry = $this->Crud->read_field('id', $user_id, 'user', 'end_date');
		if (!empty($expiry)) {
			$days = $this->Crud->date_diff(date('Y-m-d'), $expiry);
			if ($days > 0)
				$start_date = date(fdate, strtotime($expiry));
		}

		// compute end date
		$end_date = date(fdate, strtotime($start_date . ' +1 ' . $recurrent));

		$ins['sub_id'] = $sub_id;
		$ins['user_id'] = $user_id;
		$ins['start_date'] = $start_date;
		$ins['end_date'] = $end_date;
		$ins['reg_date'] = date(fdate);
		$ins['coupon_id'] = $coupon_id;
		$ins['trans_id'] = $trans_id;
		if (!empty($res))
			$ins['response'] = $res;
		$ins_id = $this->Crud->create('sub', $ins);

		return array('id' => $ins_id, 'start' => $start_date, 'end' => $end_date);
	}

	private function notify($from, $to, $content, $item, $item_id)
	{
		$ins['from_id'] = $from;
		$ins['to_id'] = $to;
		$ins['content'] = $content;
		$ins['item'] = $item;
		$ins['item_id'] = $item_id;
		$ins['new'] = 1;
		$ins['reg_date'] = date(fdate);

		$this->Crud->create('notify', $ins);
	}

	private function send_email($email, $subject, $body, $name)
	{
		// $from = push_email;
		// $name = app_name;
		// $subhead = 'Notification';
		// $this->Crud->send_email($to, $from, $subject, $body, $name, $subhead);
		$em['from'] = 'PCDL4Kids <' . app_email . '>';
		$em['to'] = $name . ' <' . $email . '>';
		$em['subject'] = $subject;
		$em['template'] = 'general';
		$em['t:variables'] = '{"name": "' . $name . '", "body": "' . $body . '"}';
		$this->Crud->mailgun($em);
	}

	private function getIPAddress()
	{
		//whether ip is from the share internet  
		if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
			$ip = $_SERVER['HTTP_CLIENT_IP'];
		} //whether ip is from the proxy  
		else if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
			$ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
		} //whether ip is from the remote address  
		else {
			$ip = $_SERVER['REMOTE_ADDR'];
		}
		return $ip;
	}
}
