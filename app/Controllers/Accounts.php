<?php

namespace App\Controllers;

class Accounts extends BaseController {
	private $db;

    public function __construct() {
		$this->db = \Config\Database::connect();
	}

    public function index() {
        return $this->parents();
    }

    //// PARENTS
    public function parents($param1='', $param2='', $param3='') {
        // check login
        $log_id = $this->session->get('plx_id');
        if(empty($log_id)) return redirect()->to(site_url('auth'));

        $role_id = $this->Crud->read_field('id', $log_id, 'user', 'role_id');
        $role = strtolower($this->Crud->read_field('id', $role_id, 'access_role', 'name'));
        $role_c = $this->Crud->module($role_id, 'accounts/parents', 'create');
        $role_r = $this->Crud->module($role_id, 'accounts/parents', 'read');
        $role_u = $this->Crud->module($role_id, 'accounts/parents', 'update');
        $role_d = $this->Crud->module($role_id, 'accounts/parents', 'delete');
        if($role_r == 0){
            return redirect()->to(site_url('profile'));	
        }

        $data['log_id'] = $log_id;
        $data['role'] = $role;
        $data['role_c'] = $role_c;
		$data['is_church_admin'] = $this->Crud->read_field('id', $log_id, 'user', 'is_church_admin');
        $data['is_ministry_admin'] = $this->Crud->read_field('id', $log_id, 'user', 'is_ministry_admin');
        $table = 'user';

		$form_link = site_url('accounts/parents/');
		if($param1){$form_link .= $param1.'/';}
		if($param2){$form_link .= $param2.'/';}
		if($param3){$form_link .= $param3.'/';}
		
		// pass parameters to view
		$data['param1'] = $param1;
		$data['param2'] = $param2;
		$data['param3'] = $param3;
		$data['form_link'] = rtrim($form_link, '/');
		
		// manage record
		if($param1 == 'manage') {
			// prepare for delete
			if($param2 == 'delete') {
				if($param3) {
					$edit = $this->Crud->read_single('id', $param3, $table);
					if(!empty($edit)) {
						foreach($edit as $e) {
							$data['d_id'] = $e->id;
						}
					}

					if($this->request->getMethod() == 'post'){
						$del_id = $this->request->getVar('d_parent_id');
						if($this->Crud->deletes('id', $del_id, $table) > 0) {
							if($this->Crud->check('parent_id', $del_id,  'child') > 0){
								$child = $this->Crud->read_single('parent_id',$del_id,  'child');
								if(!empty($child)){
									foreach($child as $c){
										 $this->Crud->deletes('id', $c->id, 'child');
									}
								}
							}
							echo $this->Crud->msg('success', 'Record Deleted');
							echo '<script>location.reload(false);</script>';
						} else {
							echo $this->Crud->msg('danger', 'Please try later');
						}	
						exit;	
					}
				}
			} else {
				// prepare for edit
				if($param2 == 'edit') {
					if($param3) {
						$edit = $this->Crud->read_single('id', $param3, $table);
						if(!empty($edit)) {
							foreach($edit as $e) {
								$data['e_id'] = $e->id;
								$data['e_fullname'] = $e->fullname;
								$data['e_phone'] = $e->phone;
								$data['e_email'] = $e->email;
								$data['e_is_manager'] = $e->is_manager;
								$data['e_is_marketer'] = $e->is_marketer;
								$data['e_marketer_id'] = $e->marketer_id;
								$data['e_manager_id'] = $e->manager_id;
								$data['e_pin'] = $e->pin;
								$data['e_ban'] = $e->ban;
							}
						}
					}
				}

				if($this->request->getMethod() == 'post'){
					$user_id = $this->request->getVar('user_id');
					$fullname = $this->request->getVar('fullname');
					$email = $this->request->getVar('email');
					$phone = $this->request->getVar('phone');
					$pin = $this->request->getVar('pin');
					$ban = $this->request->getVar('ban');
					$password = $this->request->getVar('password');
					$role_id = $this->request->getVar('role_id');
					$manager_id = $this->request->getVar('manager_id');
					$market_id = $this->request->getVar('market_id');

					
					if($role_id > 0){
						if(!empty($manager_id)){
							$ins_data['is_marketer'] = 1;
							$ins_data['is_manager'] = 0;
							$ins_data['manager_id'] = $manager_id;
						} else {
							$ins_data['is_marketer'] = 0;
							$ins_data['is_manager'] = 1;
							$ins_data['manager_id'] = 0;

						}
					}

					if($role == 'church administrator'){
						$ins_data['admin_id'] = $log_id;
						$ins_data['cadmin_id'] = $log_id;
					}
					if($role == 'ministry administrator'){
						$ins_data['admin_id'] = $log_id;
						$ins_data['madmin_id'] = $log_id;
					}

					
					// echo $pin;die;
					$ins_data['fullname'] = $fullname;
					$ins_data['email'] = $email;
					$ins_data['phone'] = $phone;
					$ins_data['pin'] = $pin;
					$ins_data['ban'] = $ban;
					
					if (!empty($password)) {
						$ins_data['password'] = password_hash($password, PASSWORD_BCRYPT);
					}
					
					$role_id = $this->Crud->read_field('name', 'User', 'access_role', 'id');
				
					// do create or update
					if ($user_id) {
						$is_marketer = $this->Crud->read_field('id', $market_id, 'user', 'is_marketer');
						$is_manager = $this->Crud->read_field('id', $market_id, 'user', 'is_manager');

						if($is_marketer){
							$sales_id = $this->Crud->read_field('id', $market_id, 'user', 'manager_id');
							$ins_data['manager_id'] = $sales_id;
							$ins_data['marketer_id'] = $market_id;
							$in_data['manager_id'] = $sales_id;
							$in_data['marketer_id'] = $market_id;
						}

						if($is_manager){
							$ins_data['manager_id'] = $market_id;
							$in_data['manager_id'] = $market_id;
						}
						$child = $this->Crud->read_single('parent_id',$user_id,  'child');
						if(!empty($child)){
							foreach($child as $c){
								 $this->Crud->updates('id', $c->id, 'child', $in_data);
							}
						}
						$upd_rec = $this->Crud->updates('id', $user_id, $table, $ins_data);
						if ($upd_rec > 0) {
							///// store activities
							$code = $this->Crud->read_field('id', $user_id, $table, 'fullname');
							$by = $this->Crud->read_field('id', $log_id, 'user', 'fullname');
							$action = $by.' updated Parent '.$code.' Record';
							$this->Crud->activity('account', $user_id, $action);

							echo $this->Crud->msg('success', 'Record Updated');
							echo '<script>location.reload(false);</script>';
						} else {
							echo $this->Crud->msg('info', 'No Changes');
						}
					} else {
						if($this->Crud->check('email', $email, 'user') > 0){
							echo $this->Crud->msg('danger', 'Email Already Taken');
						} elseif($this->Crud->check('phone', $phone, 'user') > 0){
							echo $this->Crud->msg('danger', 'Phone Number Already Taken');
						} else {
							// Read logged-in user's church info
							$church_type   = $this->Crud->read_field('id', $log_id, 'user', 'church_type');
							$regional_id   = (int) $this->Crud->read_field('id', $log_id, 'user', 'regional_id');
							$zonal_id      = (int) $this->Crud->read_field('id', $log_id, 'user', 'zonal_id');
							$group_id      = (int) $this->Crud->read_field('id', $log_id, 'user', 'group_id');
							$church_id     = (int) $this->Crud->read_field('id', $log_id, 'user', 'church_id');
							$ministry_id   = (int) $this->Crud->read_field('id', $log_id, 'user', 'ministry_id');
							
							// Add to insert data
							$ins_data['church_type']   = $church_type;
							$ins_data['regional_id']   = $regional_id;
							$ins_data['zonal_id']      = $zonal_id;
							$ins_data['group_id']      = $group_id;
							$ins_data['church_id']     = $church_id;
							$ins_data['ministry_id']   = $ministry_id;
							
							$is_marketer = $this->Crud->read_field('id', $log_id, 'user', 'is_marketer');
							$is_manager = $this->Crud->read_field('id', $log_id, 'user', 'is_manager');
							if($is_marketer == 1){$ins_data['marketer_id'] = $log_id;} else {$ins_data['marketer_id'] = 0;}
							if($is_manager == 1){$ins_data['manager_id'] = $log_id;} else {$ins_data['manager_id'] = 0;}
							$ins_data['reg_date'] = date(fdate);
							$ins_data['role_id'] = $role_id;

							$user_id = $this->Crud->create('user', $ins_data);
							if($user_id > 0) {
								///// store activities
								$action = $fullname.' created an Account';
								$this->Crud->activity('account', $user_id, $action);

								echo $this->Crud->msg('success', 'Record Created');
								echo '<script>location.reload(false);</script>';
							} else {
								echo $this->Crud->msg('info', 'No Changes');
							}
						}
					}
					die;	
				}
			}
		}


        // record listing
		if($param1 == 'load') {
			$limit = $param2;
			$offset = $param3;

			$rec_limit = 50;
			$item = '';
			$counts = 0;

			if(empty($limit)) {$limit = $rec_limit;}
			if(empty($offset)) {$offset = 0;}
			
			if(!empty($this->request->getPost('ban'))) { $ban = $this->request->getPost('ban'); } else { $ban = ''; }
			$search = $this->request->getPost('search');
			if (!empty($this->request->getPost('start_date'))) {$start_date = $this->request->getPost('start_date');} else {$start_date = '';}
			if (!empty($this->request->getPost('end_date'))) {$end_date = $this->request->getPost('end_date');} else {$end_date = '';}
			$log_id = $this->session->get('plx_id');
			if(!$log_id) {
				$item = '<div class="text-center text-muted">Session Timeout! - Please login again</div>';
			} else {
				$all_rec = $this->Crud->filter_parent('', '', $log_id, $search, $ban, $start_date, $end_date);
				if(!empty($all_rec)) { $counts = count($all_rec); } else { $counts = 0; }
				$query = $this->Crud->filter_parent($limit, $offset, $log_id, $search, $ban, $start_date, $end_date);

				if(!empty($query)) {
					foreach($query as $q) {
						$id = $q->id;
						$fullname = $q->fullname;
						$email = $q->email;
						$phone = $q->phone;
						$ban = $q->ban;
						$marketer_id = $q->marketer_id;
						$manager_id = $q->manager_id;
						$sub_id = $this->Crud->read_field('user_id', $id, 'sub', 'sub_id');
						$subscription = $this->Crud->read_field('id', $sub_id, 'subscription', 'name');
						$reg_date = date('M d, Y h:i A', strtotime($q->reg_date));

						$s_date =  $this->Crud->read_field('user_id', $id, 'sub', 'start_date');
						$e_date =  $this->Crud->read_field('user_id', $id, 'sub', 'end_date');
						
						$link='';
						if($marketer_id) {
							$marketer = $this->Crud->read_field('id', $marketer_id, 'user', 'fullname');
							$link .= '<div class="text-muted font-size-12">MARKETER: '.ucwords($marketer).'</div>';
						}

						if($manager_id) {
							$manager = $this->Crud->read_field('id', $manager_id, 'user', 'fullname');
							$link .= '<div class="text-muted font-size-12">MANAGER: '.ucwords($manager).'</div>';
						}
						
						$start_date = date('M d, Y', strtotime($s_date));
						$end_date = date('M d, Y', strtotime($e_date));

						// count children
						$children = $this->db->table('child')->where('parent_id', $q->id)->countAllResults();

						if(empty($ban) && $ban == 0){
							$b = '<span class="text-success font-size-12">ACCOUNT ACTIVE</span>';
						} else {
							$b = '<span class="text-danger font-size-12">ACCOUNT BANNED</span>';
						}
						
						// add manage buttons
						if($role_u != 1) {
							$all_btn = '';
						} else {
							if($role == 'marketer'){
								$all_btn = '
									<div class="textright">
										<a href="javascript:;" class="text-info pop m-b-5 m-r-5" pageTitle="Reset '.$fullname.' Details" pageName="'.base_url('accounts/parents/manage/edit/'.$id).'" pageSize="modal-lg">
											<i class="anticon anticon-rollback"></i> EDIT
										</a>
										
										<a href="javascript:;" class="text-success pop m-b-5 m-l-5" pageTitle="View '.$fullname.' Children" pageName="'.base_url('accounts/parents/manage/view/'.$id).'" pageSize="modal-lg">
											<i class="anticon anticon-eye"></i> VIEW
										</a>
										
									</div>
								';
							} else {

								$all_btn = '
									<div class="textright">
										<a href="javascript:;" class="text-info pop m-b-5 m-r-5" pageTitle="Reset '.$fullname.' Details" pageName="'.base_url('accounts/parents/manage/edit/'.$id).'" pageSize="modal-lg">
											<i class="anticon anticon-rollback"></i> EDIT
										</a>
										<a href="javascript:;" class="text-danger pop m-b-5 m-l-5  m-r-5" pageTitle="Delete '.$fullname.' Record" pageName="'.base_url('accounts/parents/manage/delete/'.$id).'" pageSize="modal-sm">
											<i class="anticon anticon-delete"></i> DELETE
										</a>
										<a href="javascript:;" class="text-success pop m-b-5 m-l-5" pageTitle="View '.$fullname.' Children" pageName="'.base_url('accounts/parents/manage/view/'.$id).'" pageSize="modal-lg">
											<i class="anticon anticon-eye"></i> VIEW
										</a>
										
									</div>
								';
								
							}
							
						}
						
						$sub = '';
						if(!empty($sub_id)){
							$sub = '
							<div class="text-muted font-size-12">'.strtoupper($subscription).'</div>
								<div class="font-size-14">
									<span class="text-success font-size-12">'.$start_date.'</span> 
									<i class="anticon anticon-arrow-right"></i> 
									<span class="text-danger font-size-12">'.$end_date.'</span>
								</div>
							';
						}

						$item .= '
							<li class="list-group-item">
								<div class="row p-t-10">
									<div class="col-12 col-md-6 m-b-10">
										<div class="single">
											<div class="text-muted font-size-12">'.$reg_date.'</div>
											<b class="font-size-16 text-primary">'.ucwords($fullname).'</b>
											<div class="small text-muted">'.number_format($children).' Children</div>
											<div class="small text-muted">'.$phone.'</div>
											<div class="small text-email">'.$email.'</div>
											'.$b.'
										</div>
									</div>
									<div class="col-12 col-md-3 m-b-5">
										'.$sub.' '.$link.'
									</div>
									<div class="col-12 col-md-3">
										<b class="font-size-12">'.$all_btn.'</b>
									</div>
								</div>
							</li>
						';
					}
				}
			}
			
			if(empty($item)) {
				$resp['item'] = '
					<div class="text-center text-muted">
						<br/><br/><br/><br/>
						<i class="anticon anticon-team" style="font-size:150px;"></i><br/><br/>No Parents Returned
					</div>
				';
			} else {
				$resp['item'] = $item;
			}

			$resp['count'] = $counts;

			$more_record = $counts - ($offset + $rec_limit);
			$resp['left'] = $more_record;

			if($counts > ($offset + $rec_limit)) { // for load more records
				$resp['limit'] = $rec_limit;
				$resp['offset'] = $offset + $limit;
			} else {
				$resp['limit'] = 0;
				$resp['offset'] = 0;
			}

			echo json_encode($resp);
			die;
		}

        if($param1 == 'manage') { // view for form data posting
			return view('account/parents_form', $data);
		} else { // view for main page
            $data['title'] = 'Parents | '.app_name;
            $data['page_active'] = 'accounts/parents';
            return view('account/parents', $data);
        }
    }

	//// CHILDREN
    public function children($param1='', $param2='', $param3='') {
        // check login
        $log_id = $this->session->get('plx_id');
        if(empty($log_id)) return redirect()->to(site_url('auth'));

        $role_id = $this->Crud->read_field('id', $log_id, 'user', 'role_id');
        $role = strtolower($this->Crud->read_field('id', $role_id, 'access_role', 'name'));
        $role_c = $this->Crud->module($role_id, 'accounts/children', 'create');
        $role_r = $this->Crud->module($role_id, 'accounts/children', 'read');
        $role_u = $this->Crud->module($role_id, 'accounts/children', 'update');
        $role_d = $this->Crud->module($role_id, 'accounts/children', 'delete');
        if($role_r == 0){
            return redirect()->to(site_url('profile'));	
        }

        $data['log_id'] = $log_id;
        $data['role'] = $role;
        $data['role_c'] = $role_c;

        $table = 'child';

		$form_link = site_url('accounts/children/');
		if($param1){$form_link .= $param1.'/';}
		if($param2){$form_link .= $param2.'/';}
		if($param3){$form_link .= $param3.'/';}
		
		// pass parameters to view
		$data['param1'] = $param1;
		$data['param2'] = $param2;
		$data['param3'] = $param3;
		$data['form_link'] = rtrim($form_link, '/');
		
		// manage record
		if($param1 == 'manage') {
			// prepare for delete
			if($param2 == 'delete') {
				if($param3) {
					$edit = $this->Crud->read_single('id', $param3, $table);
					if(!empty($edit)) {
						foreach($edit as $e) {
							$data['d_id'] = $e->id;
						}
					}

					if($this->request->getMethod() == 'post'){
						$del_id = $this->request->getVar('d_child_id');
						///// store activities
						$code = $this->Crud->read_field('id', $del_id, $table, 'name');
						$by = $this->Crud->read_field('id', $log_id, 'user', 'fullname');
						$action = $by.' deleted Child '.$code.' Record';

						if($this->Crud->deletes('id', $del_id, $table) > 0) {
							
							$this->Crud->activity('account', $del_id, $action);
							echo $this->Crud->msg('success', 'Record Deleted');
							echo '<script>location.reload(false);</script>';
						} else {
							echo $this->Crud->msg('danger', 'Please try later');
						}
						exit;	
					}
				}
			} else {
				// prepare for edit
				if($param2 == 'edit') {
					if($param3) {
						$edit = $this->Crud->read_single('id', $param3, $table);
						if(!empty($edit)) {
							foreach($edit as $e) {
								$data['e_id'] = $e->id;
								$data['e_name'] = $e->name;
								$data['e_parent_id'] = $e->parent_id;
								$data['e_age_id'] = $e->age_id;
							}
						}
					}
				}

				if($this->request->getMethod() == 'post'){
					$child_id = $this->request->getVar('child_id');
					$name = $this->request->getVar('name');
					$parent_id = $this->request->getVar('parent_id');
					$age_id = $this->request->getVar('age_id');
					

					$ins_data['name'] = $name;
					$ins_data['parent_id'] = $parent_id;
					$ins_data['marketer_id'] = $this->Crud->read_field('id', $parent_id, 'user', 'marketer_id');
					$ins_data['manager_id'] = $this->Crud->read_field('id', $parent_id, 'user', 'manager_id');
					$ins_data['age_id'] = $age_id;
					
					
					// do create or update
					if($child_id) {
						$upd_rec = $this->Crud->updates('id', $child_id, $table, $ins_data);
						if($upd_rec > 0) {
							///// store activities
							$code = $this->Crud->read_field('id', $child_id, $table, 'name');
							$by = $this->Crud->read_field('id', $log_id, 'user', 'fullname');
							$action = $by.' updated Child '.$code.' Record';
							$this->Crud->activity('account', $child_id, $action);

							echo $this->Crud->msg('success', 'Record Updated');
							echo '<script>location.reload(false);</script>';
						} else {
							echo $this->Crud->msg('info', 'No Changes');	
						}
						
					} else {
						if($this->Crud->check2('name', $name, 'parent_id', $parent_id, $table) > 0) {
							echo $this->Crud->msg('warning', 'Child`s name already exist for this Parent');
						} else {
							if($this->Crud->check('parent_id', $parent_id, $table) > 3){
								echo $this->Crud->msg('danger', 'Maximum number of Children per Parent is 3');
							} else {
								$ins_data['reg_date'] = date(fdate);
								$ins_rec = $this->Crud->create($table, $ins_data);
								if($ins_rec > 0) {
									///// store activities
									$code = $this->Crud->read_field('id', $ins_rec, $table, 'name');
									$by = $this->Crud->read_field('id', $log_id, 'user', 'fullname');
									$action = $by.' created Child '.$code.' Record';
									$this->Crud->activity('account', $ins_rec, $action);

									echo $this->Crud->msg('success', 'Record Created');
									echo '<script>location.reload(false);</script>';
								} else {
									echo $this->Crud->msg('danger', 'Please try later');	
								}	
							}
							
						}
					}

					die;	
				}
			}
		}

        // record listing
		if($param1 == 'load') {
			$limit = $param2;
			$offset = $param3;

			$rec_limit = 50;
			$item = '';
			$counts = 0;

			if(empty($limit)) {$limit = $rec_limit;}
			if(empty($offset)) {$offset = 0;}
			
			if(!empty($this->request->getPost('age_id'))) { $ageID = $this->request->getPost('age_id'); } else { $ageID = ''; }
			if(!empty($this->request->getPost('parent_id'))) { $parentID = $this->request->getPost('parent_id'); } else { $parentID = ''; }
			if (!empty($this->request->getPost('start_date'))) {$start_date = $this->request->getPost('start_date');} else {$start_date = '';}
			if (!empty($this->request->getPost('end_date'))) {$end_date = $this->request->getPost('end_date');} else {$end_date = '';}
			
			$search = $this->request->getPost('search');

			$log_id = $this->session->get('plx_id');
			if(!$log_id) {
				$item = '<div class="text-center text-muted">Session Timeout! - Please login again</div>';
			} else {
				$all_rec = $this->Crud->filter_children('', '', $log_id, $ageID, $parentID, $search, $start_date, $end_date);
				if(!empty($all_rec)) { $counts = count($all_rec); }
				$query = $this->Crud->filter_children($limit, $offset, $log_id, $ageID, $parentID, $search, $start_date, $end_date);

				if(!empty($query)) {
					foreach($query as $q) {
						$id = $q->id;
						$name = $q->name;
						$avatar = $q->avatar;
						$age = $this->Crud->read_field('id', $q->age_id, 'age', 'name');
						$parent = $this->Crud->read_field('id', $q->parent_id, 'user', 'fullname');
						$reg_date = date('M d, Y h:i A', strtotime($q->reg_date));

						// count children
						// $children = $this->db->table('child')->where('parent_id', $q->id)->countAllResults();
						
						if(empty($avatar)){
							$avatar = 'assets/images/avatar.png';
						}
						// add manage buttons
						$all_btn = '';
						if($role_u != 1) {
							$all_btn = '';
						} else {
							$all_btn = '
								<div class="text-right">
									<a href="javascript:;" class="text-danger pop" pageTitle="Delete '.$name.' Details" pageName="'.base_url('accounts/children/manage/delete/'.$id).'" pageSize="modal-sm">
										<i class="anticon anticon-delete"></i> DELETE
									</a>
									<a href="javascript:;" class="text-primary pop" pageTitle="Edit '.$name.' Details" pageName="'.base_url('accounts/children/manage/edit/'.$id).'" pageSize="modal-md">
										<i class="anticon anticon-edit"></i> EDIT
									</a>
								</div>
							';
						}

						$item .= '
							<li class="list-group-item">
								<div class="row p-t-10">
									<div class="col-2 col-md-1">
										<img alt="" src="'.site_url($avatar).'" class="p-1 avatar" />
									</div>
									<div class="col-10 col-md-5 m-b-10">
										<div class="single">
											<div class="text-muted font-size-12">'.$reg_date.'</div>
											<b class="font-size-16 text-primary">'.$name.'</b>
											<div class="small text-muted">'.$parent.'</div>
										</div>
									</div>
									<div class="col-7 col-md-4 m-b-5">
										<div class="text-muted font-size-12">AGE</div>
										<div class="font-size-14">
											'.$age.'
										</div>
									</div>
									<div class="col-5 col-md-2">
										<b class="font-size-14">'.$all_btn.'</b>
									</div>
								</div>
							</li>
						';
					}
				}
			}
			
			if(empty($item)) {
				$resp['item'] = '
					<div class="text-center text-muted">
						<br/><br/><br/><br/>
						<i class="anticon anticon-team" style="font-size:150px;"></i><br/><br/>No Children Returned
					</div>
				';
			} else {
				$resp['item'] = $item;
			}

			$resp['count'] = $counts;

			$more_record = $counts - ($offset + $rec_limit);
			$resp['left'] = $more_record;

			if($counts > ($offset + $rec_limit)) { // for load more records
				$resp['limit'] = $rec_limit;
				$resp['offset'] = $offset + $limit;
			} else {
				$resp['limit'] = 0;
				$resp['offset'] = 0;
			}

			echo json_encode($resp);
			die;
		}
		$ministry_id = $this->Crud->read_field('id', $log_id, 'user', 'ministry_id');

		if($role == 'marketer'){
			$data['parents'] = $this->Crud->read2_order('marketer_id', $log_id, 'role_id', 3, 'user', 'fullname', 'ASC');
		}elseif($role == 'ministry administrator'){
			$data['parents'] = $this->Crud->read2_order('ministry_id', $ministry_id, 'role_id', 3, 'user', 'fullname', 'ASC');
		} elseif($role == 'church administrator'){
			$data['parents'] = $this->Crud->read2_order('admin_id', $log_id, 'role_id', 3, 'user', 'fullname', 'ASC');
		} else {

			$data['parents'] = $this->Crud->read_single_order('role_id', 3, 'user', 'fullname', 'ASC');
		}

		$data['ages'] = $this->Crud->read_order('age', 'id', 'ASC');

        if($param1 == 'manage') { // view for form data posting
			return view('account/children_form', $data);
		} else { // view for main page
            $data['title'] = 'Children | '.app_name;
            $data['page_active'] = 'accounts/children';
            return view('account/children', $data);
        }
    }
	public function referral($referral_id = ''){
		// Start session if not already started
		if (session_status() == PHP_SESSION_NONE) {
			session_start();
		}
	
		// Validate referral ID
		if (empty($referral_id)) {
			return redirect()->to('https://pcdl4kids.com/web/')->with('error', 'Invalid referral link.');
		}
	
		// Save the referral ID in session
		session()->set('referral_id', $referral_id);
	
		// Redirect to the desired URL (e.g., signup page)
		return redirect()->to('https://pcdl4kids.com/web/')->with('success', 'Referral link applied successfully!');
	}
	
	public function church($param1 = '', $param2 = '', $param3 = '')
	{
		// check session login
		if ($this->session->get('plx_id') == '') {
			$request_uri = uri_string();
			$this->session->set('plx_redirect', $request_uri);
			return redirect()->to(site_url('auth'));
		}
		

		$mod = 'accounts/church';
		$log_id = $this->session->get('plx_id');
		$role_id = $this->Crud->read_field('id', $log_id, 'user', 'role_id');
		
		$role = strtolower($this->Crud->read_field('id', $role_id, 'access_role', 'name'));
		$role_c = $this->Crud->module($role_id, $mod, 'create');
		$role_r = $this->Crud->module($role_id, $mod, 'read');
		$role_u = $this->Crud->module($role_id, $mod, 'update');
		$role_d = $this->Crud->module($role_id, $mod, 'delete');
		if ($role_r == 0) {
			return redirect()->to(site_url('dashboard'));
		}

		$is_church_admin = $this->Crud->read_field('id', $log_id, 'user', 'is_church_admin');
		$church_type = $this->Crud->read_field('id', $log_id, 'user', 'church_type');
		$user_regional_id = $this->Crud->read_field('id', $log_id, 'user', 'regional_id');
		$user_zonal_id = $this->Crud->read_field('id', $log_id, 'user', 'zonal_id');
		$user_group_id = $this->Crud->read_field('id', $log_id, 'user', 'group_id');
		$user_church_id = $this->Crud->read_field('id', $log_id, 'user', 'church_id');
		if ($is_church_admin == 1 && strtolower($church_type) === 'assembly') {
			return redirect()->to(site_url('dashboard'));
        }
		$data['is_church_admin'] = $is_church_admin;
		$data['church_type'] = $church_type;
		$data['user_regional_id'] = $user_regional_id;
		$data['user_zonal_id'] = $user_zonal_id;
		$data['user_group_id'] = $user_group_id;
		$data['user_church_id'] = $user_church_id;
		$data['log_id'] = $log_id;
		$data['role'] = $role;
		$data['role_c'] = $role_c;


		$table = 'church';
		$form_link = site_url($mod);
		if ($param1) {
			$form_link .= '/' . $param1;
		}
		if ($param2) {
			$form_link .= '/' . $param2 . '/';
		}
		if ($param3) {
			$form_link .= $param3;
		}

		// pass parameters to view
		$data['param1'] = $param1;
		$data['param2'] = $param2;
		$data['param3'] = $param3;
		$data['form_link'] = rtrim($form_link, '/');
		$data['current_language'] = $this->session->get('current_language');

		// manage record
		if ($param1 == 'manage') {
			// prepare for delete
			if ($param2 == 'delete') {
				if ($param3) {
					$edit = $this->Crud->read_single('id', $param3, $table);
					if (!empty($edit)) {
						foreach ($edit as $e) {
							$data['d_id'] = $e->id;
						}
					}

					if ($this->request->getMethod() == 'post') {
						$del_id = $this->request->getVar('d_dept_id');
						///// store activities
						$by = $this->Crud->read_field('id', $log_id, 'user', 'firstname');
						$code = $this->Crud->read_field('id', $del_id, 'church', 'name');
						$action = $by . ' deleted Church (' . $code . ') Record';

						if ($this->Crud->deletes('id', $del_id, $table) > 0) {

							$this->Crud->activity('user', $del_id, $action);
							echo $this->Crud->msg('success', 'Church Deleted');
							echo '<script>location.reload(false);</script>';
						} else {
							echo $this->Crud->msg('danger', 'Please try later');
						}
						exit;
					}
				}
			}  elseif($param2 == 'upload'){ 
				if($param3 == 'download'){
					// Define the path to the file
					$filePath = FCPATH . 'assets/church_template.xlsx';

					// Check if the file exists
					if (file_exists($filePath)) {
						// Set custom file name for download
						$downloadFileName = 'Church_List_' . date('Ymd_His') . '.xlsx';
					
						// Serve the file for download with the custom name
						return $this->response->download($filePath, null)
							->setFileName($downloadFileName)
							->setContentType('application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
					} else {
						// Return a 404 error if the file is not found
						return $this->response
							->setStatusCode(404)
							->setBody('File not found.');
					}
				}
				if($this->request->getMethod() == 'post'){
					$file = $this->request->getFile('csv_file');
					$church_type = $this->request->getPost('church_type');
					$ministry_id = $this->request->getPost('ministry_id');
					$regional_id = $this->request->getPost('regional_id');
					$zonal_id = $this->request->getPost('zonal_id');
					$group_id = $this->request->getPost('group_id');
					$records = $this->Crud->processFile($file);
					// $record = json_decode($records);

					
						// print_r($records);
						// die;
					if ($file->isValid()) {
						$filePath = FCPATH . 'assets/uploads/' . $file->getName();
						$file->move(FCPATH . 'assets/uploads/', $file->getName());
						// echo $filePath;
						// print_r($records);
						// die;
						// Read the file and insert records into the database
						if (!empty($records)) {
							$member_email = [];
							$success = 0;
							$failed = 0;
							$exist = 0;
						
							foreach ($records as $dt => $val) {
								$church = $val['church'];
							
								$ins_data['name'] = $church;
								$ins_data['type'] = $church_type;
								$ins_data['regional_id'] = $regional_id;
								$ins_data['zonal_id'] = $zonal_id;
								$ins_data['group_id'] = $group_id;
								$ins_data['ministry_id'] = $ministry_id;
					
								// print_r($ins_data).'<br>' ;

								$church_check = $this->Crud->read_field3('type', $church_type, 'ministry_id', $ministry_id, 'name', $church, 'church', 'id');
								// echo $email_check.' ';
								if($church_check){
									$ins_rec = $this->Crud->updates('id', $church_check, $table, $ins_data);
									if($ins_rec > 0)$exist++;
								} else {
									
									$ins_data['reg_date'] = date(fdate);
									$ins_rec = $this->Crud->create($table, $ins_data);
									if($ins_rec > 0)$success++;
								}
								if ($ins_rec <= 0) {
									$failed++;
								}
							}
							// die;
							$msg = '';
							if ($success > 0) $msg .= $success . ' Church List(s) Uploaded Successfully<br> ';
							if ($exist > 0) $msg .= $exist . ' Church List(s) Already Exist<br> ';
							if ($failed > 0) $msg .= $failed . ' Church List(s) not Uploaded';
						
							echo $this->Crud->msg('info', $msg);
							if ($success > 0) {
								// **Delete the file after successful processing**
								if (file_exists($filePath)) {
									unlink($filePath); // Delete file
								}
								echo '<script>
									setTimeout(function() {
										window.location.replace("' . site_url('accounts/church') . '");
									}, 2000);
								</script>';

							}
						
							
						} else {
							echo $this->Crud->msg('danger', 'Error Uploading Church List! Check Excel File');
							if (file_exists($filePath)) {
								unlink($filePath); // Delete file
							}
						}
						
					}
					
					die;
				}
			} else {
				// prepare for edit
				if ($param2 == 'edit') {
					if ($param3) {
						$edit = $this->Crud->read_single('id', $param3, $table);
						if (!empty($edit)) {
							foreach ($edit as $e) {
								$data['e_id'] = $e->id;
								$data['e_name'] = $e->name;
								$data['e_email'] = $e->email;
								$data['e_phone'] = $e->phone;
								$data['e_address'] = $e->address;
								$data['e_church_type'] = $e->type;
								$data['e_regional_id'] = $e->regional_id;
								$data['e_zonal_id'] = $e->zonal_id;
								$data['e_group_id'] = $e->group_id;
								$data['e_country_id'] = $e->country_id;
								$data['e_state_id'] = $e->state_id;
								$data['e_city_id'] = $e->city_id;
								$data['e_ministry_id'] = $e->ministry_id;
							}
						}
					}
				}

				if ($this->request->getMethod() == 'post') {
					$church_id = $this->request->getVar('church_id');
					$name = $this->request->getVar('name');
					$email = $this->request->getVar('email');
					$phone = $this->request->getVar('phone');
					$address = $this->request->getVar('address');
					$church_type = $this->request->getVar('church_type');
					$regional_id = $this->request->getVar('regional_id');
					$zonal_id = $this->request->getVar('zonal_id');
					$group_id = $this->request->getVar('group_id');
					$lga_id = $this->request->getVar('lga_id');
					$state_id = $this->request->getVar('state_id');
					$country_id = $this->request->getVar('country_id');
					$ministry_id = $this->request->getVar('ministry_id');

					if (empty($church_type)) {
						echo $this->Crud->msg('warning', 'Please select Church Type');
						die;
					}

					if($church_type != 'regional'){
						if ($church_type == 'zonal' && empty($regional_id)) {
							echo $this->Crud->msg('warning', 'Please select Regional Church');
							die;
						}

						if ($church_type == 'group' && empty($zonal_id)) {
							echo $this->Crud->msg('warning', 'Please select Zonal Church');
							die;
						}
						if ($church_type == 'assembly' && empty($group_id)) {
							echo $this->Crud->msg('warning', 'Please select Group Church');
							die;
						}
					}


					$ins_data['name'] = $name;
					$ins_data['email'] = $email;
					$ins_data['address'] = $address;
					$ins_data['phone'] = $phone;
					$ins_data['type'] = $church_type;
					$ins_data['state_id'] = $state_id;
					$ins_data['city_id'] = $lga_id;
					$ins_data['regional_id'] = $regional_id;
					$ins_data['zonal_id'] = $zonal_id;
					$ins_data['group_id'] = $group_id;
					$ins_data['country_id'] = $country_id;
					$ins_data['ministry_id'] = $ministry_id;
					
					
					// do create or update
					if ($church_id) {
						$upd_rec = $this->Crud->updates('id', $church_id, $table, $ins_data);
						if ($upd_rec > 0) {
							///// store activities
							$by = $this->Crud->read_field('id', $log_id, 'user', 'fullname');
							$code = $this->Crud->read_field('id', $church_id, 'church', 'name');
							$action = $by . ' updated Church (' . $code . ') Record';
							$this->Crud->activity('church', $church_id, $action);

							echo $this->Crud->msg('success', 'Church Updated');
							echo '<script>location.reload(false);</script>';
						} else {
							echo $this->Crud->msg('info', 'No Changes');
						}
					} else {
						if ($this->Crud->check('name', $name, $table) > 0) {
							echo $this->Crud->msg('warning', 'Church Already Exist');
						} else {

							// ✅ Generate unique code
							$base_code = strtoupper(substr($name, 0, 3)) . rand(100, 999);
							$code = $base_code;
							$attempts = 0;
					
							// 🔁 Check uniqueness of code
							while ($this->Crud->check('code', $code, $table) > 0 && $attempts < 5) {
								$code = strtoupper(substr($name, 0, 3)) . rand(100, 999);
								$attempts++;
							}
					
							if ($this->Crud->check('code', $code, $table) > 0) {
								echo $this->Crud->msg('danger', 'Failed to generate unique church code. Try again.');
								die;
							}
					
							// ✅ Add to insert data
							$ins_data['code'] = $code;
							$ins_data['reg_date'] = date(fdate);
							$ins_rec = $this->Crud->create($table, $ins_data);
							if ($ins_rec > 0) {
								///// store activities
								$by = $this->Crud->read_field('id', $log_id, 'user', 'fullname');
								$code = $this->Crud->read_field('id', $ins_rec, 'church', 'name');
								$action = $by . ' created Church (' . $code . ') Record';
								$this->Crud->activity('church', $ins_rec, $action);

								echo $this->Crud->msg('success', 'Church Created');
								echo '<script>location.reload(false);</script>';
							} else {
								echo $this->Crud->msg('danger', 'Please try later');
							}
						}
					}

					die;
				}
			}
		}

		// record listing
		if ($param1 == 'load') {
			$limit = $param2;
			$offset = $param3;

			$rec_limit = 50;
			$item = '';
			if (empty($limit)) {
				$limit = $rec_limit;
			}
			if (empty($offset)) {
				$offset = 0;
			}

			$search = $this->request->getPost('search');
			$category = $this->request->getPost('category');

			$items = '
				
			';
			$a = 1;

			//echo $status;
			$log_id = $this->session->get('plx_id');
			if (!$log_id) {
				$item = '<div class="text-center text-muted"> Session Timeout! - Please login again </div>';
			} else {
				$all_rec = $this->Crud->filter_church('', '', $log_id, $search, $category);
				// $all_rec = json_decode($all_rec);
				if (!empty($all_rec)) {
					$counts = count($all_rec);
				} else {
					$counts = 0;
				}

				$query = $this->Crud->filter_church($limit, $offset, $log_id, $search, $category);
				$data['count'] = $counts;


				if (!empty($query)) {
					foreach ($query as $q) {
						$id = $q->id;
						$name = $q->name;
						$email = $q->email;
						$phone = $q->phone;
						$logo = $q->logo;
						$address = $q->address;
						$church_type = $q->type;
						$regional_id = $q->regional_id;
						$zonal_id = $q->zonal_id;
						$group_id = $q->group_id;
						$reg_date = date('d/m/Y h:iA', strtotime($q->reg_date));
						$ministry = $this->Crud->read_field('id', $q->ministry_id, 'children_ministry', 'name');
						$city = $this->Crud->read_field('id', $q->city_id, 'city', 'name');
						$state = $this->Crud->read_field('id', $q->state_id, 'state', 'name');
						$country = $this->Crud->read_field('id', $q->country_id, 'country', 'name');
						$mins = '';

						if (empty($q->code)) {
							$name = $q->name ?? '';
							$attempts = 0;
							$new_code = '';
						
							if (!empty($name)) {
								// 🔤 Get first letters of each word
								$prefix = '';
								foreach (explode(' ', $name) as $word) {
									$prefix .= strtoupper(substr($word, 0, 1));
								}
						
								// 🔁 Generate and check for uniqueness
								do {
									$new_code = $prefix . rand(100, 999);
									$attempts++;
								} while ($this->Crud->check('code', $new_code, 'church') > 0);
						
								// ✅ Save if unique
								if ($this->Crud->check('code', $new_code, 'church') === 0) {
									$this->Crud->updates('id', $q->id, 'church', ['code' => $new_code]);
								}
							}
						}
						
						

						if($church_type == 'assembly')$church_type = 'church assembly';
						if (!empty($regional_id))
							$mins .= ' ' . ucwords(strtolower($this->Crud->read_field('id', $regional_id, 'church', 'name'))) . ' Region';
						if (!empty($zonal_id))
							$mins .= '<br>&#8594; ' . ucwords(strtolower($this->Crud->read_field('id', $zonal_id, 'church', 'name'))) . ' Zone';
						if (!empty($group_id))
							$mins .= '<br>&#8594; ' . ucwords(strtolower($this->Crud->read_field('id', $group_id, 'church', 'name') )). ' Group';

						if(empty($mins))$mins = '-';
						
						// add manage buttons
						if ($role_u != 1) {
							$all_btn = '';
						} else {
							$all_btn = '
								<div class="textright">
									<a href="javascript:;" class="text-info pop m-b-5  m-l-5 m-r-5" pageTitle="Edit '.$name.' Details" pageName="'.site_url('accounts/church/manage/edit/'.$id).'" pageSize="modal-lg">
										<i class="anticon anticon-rollback"></i> EDIT
									</a>
									
									<a href="javascript:;" class="text-danger pop m-b-5 m-l-5  m-r-5" pageTitle="Delete ' . $name . '" pageName="' . site_url($mod . '/manage/delete/' . $id) . '">
										<i class="anticon anticon-delete"></i>Delete
									</a>

									
									<a href="javascript:;" onclick="church_admin(\'' . addslashes(ucwords($name)) . '\', ' . (int) $id . ');" class=text-primary  m-b-5 m-l-5  m-r-5" >
										<i class="anticon anticon-user-add"></i>Admin
									</a>
								</div>

							';
						}

						$item .= '
							<tr>
								<td>'.ucwords($ministry).'</td>
								<td>
									<div class="user-card">
										<div class="user-name">            
											<span class="tb-lead"><b>' . ucwords($name) . '</b></span>  <br> <span class="small text-primary">'.ucwords($church_type).'</span>         
										</div>    
									</div>  
								</td>
								<td>
									<span class="">' . $email . '</span><br>
									<span class="">' . $phone . '</span>
								</td>
								<td><span class="">' . $address . '</span><br>
								<span class="text-info">'.$city.' '.$state.' ' . $country . '</span></td>
								<td><span class="">' . $mins . '</span></td>
								<td>
									' . $all_btn . '
								</td>
							</tr>
							
						';
						$a++;
					}
				}

			}

			if (empty($item)) {
				$resp['item'] = $items . '
					<tr><td colspan="8"><div class="text-center text-muted">
						<br/><br/><br/>
						<i class="anticon anticon-home" style="font-size:150px;"></i><br/><br/>No Church Returned
					</div></td></tr>
				';
			} else {
				$resp['item'] = $items . $item;
				if ($offset >= 25) {
					$resp['item'] = $item;
				}

			}
			$resp['count'] = $counts;

			$more_record = $counts - ($offset + $rec_limit);
			$resp['left'] = $more_record;

			if ($counts > ($offset + $rec_limit)) { // for load more records
				$resp['limit'] = $rec_limit;
				$resp['offset'] = $offset + $limit;
			} else {
				$resp['limit'] = 0;
				$resp['offset'] = 0;
			}

			echo json_encode($resp);
			die;
		}

		$ministry_id = $this->Crud->read_field('id', $log_id, 'user', 'ministry_id');
		$data['ministry_code'] = $this->Crud->read_field('id', $ministry_id, 'children_ministry', 'code');
		$data['children_ministries'] = $this->Crud->read_order('children_ministry', 'name', 'asc');
		

		if ($param1 == 'manage') { // view for form data posting
			return view('account/church_form', $data);
		} else { // view for main page

			$data['title'] = 'Church - ' . app_name;
			$data['page_active'] = 'accounts/church';
			return view('account/church', $data);
		}
	}

	
	public function marketing($param1 = '', $param2 = '', $param3 = '')
	{
		// check session login
		if ($this->session->get('plx_id') == '') {
			$request_uri = uri_string();
			$this->session->set('plx_redirect', $request_uri);
			return redirect()->to(site_url('auth'));
		}

		$mod = 'accounts/marketing';
		$log_id = $this->session->get('plx_id');
		$role_id = $this->Crud->read_field('id', $log_id, 'user', 'role_id');
		
		$role = strtolower($this->Crud->read_field('id', $role_id, 'access_role', 'name'));
		$role_c = $this->Crud->module($role_id, $mod, 'create');
		$role_r = $this->Crud->module($role_id, $mod, 'read');
		$role_u = $this->Crud->module($role_id, $mod, 'update');
		$role_d = $this->Crud->module($role_id, $mod, 'delete');
		if ($role_r == 0) {
			return redirect()->to(site_url('dashboard'));
		}
		$data['log_id'] = $log_id;
		$data['role'] = $role;
		$data['role_c'] = $role_c;


		$table = 'user';
		$form_link = site_url($mod);
		if ($param1) {
			$form_link .= '/' . $param1;
		}
		if ($param2) {
			$form_link .= '/' . $param2 . '/';
		}
		if ($param3) {
			$form_link .= $param3;
		}

		// pass parameters to view
		$data['param1'] = $param1;
		$data['param2'] = $param2;
		$data['param3'] = $param3;
		$data['form_link'] = $form_link;
		$data['current_language'] = $this->session->get('current_language');

		// manage record
		if ($param1 == 'manage') {
			// prepare for delete
			if ($param2 == 'delete') {
				if ($param3) {
					$edit = $this->Crud->read_single('id', $param3, $table);
					if (!empty($edit)) {
						foreach ($edit as $e) {
							$data['d_id'] = $e->id;
						}
					}

					if ($this->request->getMethod() == 'post') {
						$del_id = $this->request->getVar('d_marketing_id');
						///// store activities
						$by = $this->Crud->read_field('id', $log_id, 'user', 'fullname');
						$code = $this->Crud->read_field('id', $del_id, 'user', 'fullname');
						$action = $by . ' deleted Marketing (' . $code . ') Record';

						if ($this->Crud->deletes('id', $del_id, $table) > 0) {

							$this->Crud->activity('user', $del_id, $action);
							echo $this->Crud->msg('success', 'Record Deleted');
							echo '<script>location.reload(false);</script>';
						} else {
							echo $this->Crud->msg('danger', 'Please try later');
						}
						exit;
					}
				}
			} else {
				// prepare for edit
				if ($param2 == 'edit') {
					if ($param3) {
						$edit = $this->Crud->read_single('id', $param3, $table);
						if (!empty($edit)) {
							foreach ($edit as $e) {
								$data['e_id'] = $e->id;
								$data['e_fullname'] = $e->fullname;
								$data['e_email'] = $e->email;
								$data['e_phone'] = $e->phone;
								if($e->is_manager > 0){
									$role_name = $this->Crud->read_field('name', 'Sales Manager', 'access_role', 'id');
									
									$data['e_role_id'] = $role_name;
								}
								if($e->is_marketer > 0){
									$role_name = $this->Crud->read_field('name', 'Marketer', 'access_role', 'id');
									
									$data['e_role_id'] = $role_name;
								}
								$data['e_manager_id'] = $e->manager_id;
								$data['e_activate'] = $e->ban;
								$data['e_country_id'] = $e->country_id;
								$data['e_state_id'] = $e->state_id;
								$data['e_city_id'] = $e->city_id;
							}
						}
					}
				}

				if ($this->request->getMethod() == 'post') {
					$marketing_id = $this->request->getVar('marketing_id');
					$fullname = $this->request->getVar('fullname');
					$email = $this->request->getVar('email');
					$phone = $this->request->getVar('phone');
					$address = $this->request->getVar('address');
					$role_id = $this->request->getVar('role_id');
					$manager_id = $this->request->getVar('manager_id');
					$lga_id = $this->request->getVar('lga_id');
					$state_id = $this->request->getVar('state_id');
					$password = $this->request->getVar('password');
					$country_id = $this->request->getVar('country_id');
					$activate = $this->request->getVar('activate');

					if (empty($role_id)) {
						echo $this->Crud->msg('warning', 'Please select Account Type');
						die;
					}
					
					if(!empty($manager_id)){
						$ins_data['is_marketer'] = 1;
						$ins_data['is_manager'] = 0;
						$ins_data['manager_id'] = $manager_id;
					} else {
						$ins_data['is_marketer'] = 0;
						$ins_data['is_manager'] = 1;
						$ins_data['manager_id'] = 0;

					}

					$rolz = $this->Crud->read_field('id', $role_id, 'access_role', 'name');
					
					$ins_data['fullname'] = $fullname;
					$ins_data['email'] = $email;
					$ins_data['phone'] = $phone;
					$ins_data['role_id'] = $role_id;
					$ins_data['state_id'] = $state_id;
					$ins_data['city_id'] = $lga_id;
					$ins_data['country_id'] = $country_id;
					$ins_data['ban'] = $activate;
					if($password)$ins_data['password'] = md5($password);
					
					// do create or update
					if ($marketing_id) {
						$upd_rec = $this->Crud->updates('id', $marketing_id, $table, $ins_data);
						if ($upd_rec > 0) {
							///// store activities
							$by = $this->Crud->read_field('id', $log_id, 'user', 'fullname');
							$code = $this->Crud->read_field('id', $marketing_id, 'user', 'fullname');
							$action = $by . ' updated '.$rolz.' (' . $code . ') Record';
							$this->Crud->activity('user', $marketing_id, $action);

							echo $this->Crud->msg('success', $rolz.' Record Updated');
							echo '<script>location.reload(false);</script>';
						} else {
							echo $this->Crud->msg('info', 'No Changes');
						}
					} else {
						if ($this->Crud->check('email', $email, $table) > 0) {
							echo $this->Crud->msg('warning', $rolz.' Record Already Exist');
						} else {

							$ins_data['reg_date'] = date(fdate);
							$ins_rec = $this->Crud->create($table, $ins_data);
							if ($ins_rec > 0) {
								///// store activities
								$by = $this->Crud->read_field('id', $log_id, 'user', 'fullname');
								$code = $this->Crud->read_field('id', $ins_rec, 'user', 'fullname');
								$action = $by . ' created  '.$rolz.' (' . $code . ') Record';
								$this->Crud->activity('user', $ins_rec, $action);

								echo $this->Crud->msg('success', $rolz.' Record Created');
								echo '<script>location.reload(false);</script>';
							} else {
								echo $this->Crud->msg('danger', 'Please try later');
							}
						}
					}

					die;
				}
			}
		}

		// record listing
		if ($param1 == 'load') {
			$limit = $param2;
			$offset = $param3;

			$rec_limit = 50;
			$item = '';
			if (empty($limit)) {
				$limit = $rec_limit;
			}
			if (empty($offset)) {
				$offset = 0;
			}

			$search = $this->request->getPost('search');
			$category = $this->request->getPost('category');

			$items = '
				
			';
			$a = 1;

			//echo $status;
			$log_id = $this->session->get('plx_id');
			if (!$log_id) {
				$item = '<div class="text-center text-muted"> Session Timeout! - Please login again </div>';
			} else {
				$all_rec = $this->Crud->filter_marketing('', '', $log_id, $search, $category);
				// $all_rec = json_decode($all_rec);
				if (!empty($all_rec)) {
					$counts = count($all_rec);
				} else {
					$counts = 0;
				}

				$query = $this->Crud->filter_marketing($limit, $offset, $log_id, $search, $category);
				$data['count'] = $counts;


				if (!empty($query)) {
					foreach ($query as $q) {
						$id = $q->id;
						$fullname = $q->fullname;
						$email = $q->email;
						$phone = $q->phone;
						$is_marketer = $q->is_marketer;
						$is_manager = $q->is_manager;
						$manager_id = $q->manager_id;
						$activate = $q->ban;
						$u_role = $this->Crud->read_field('id', $q->role_id, 'access_role', 'name');
						$city = $this->Crud->read_field('id', $q->city_id, 'city', 'name');
						$state = $this->Crud->read_field('id', $q->state_id, 'state', 'name');
						$reg_date = date('M d, Y h:ia', strtotime($q->reg_date));

						$referral = '';

						$market = '';
						if($is_manager > 0){
							$market .= '<span class="text-primary">Sales Manager</span>';
						}
						if($is_marketer > 0){
							$market .= '<span class="text-primary">Marketer</span><br>&#8594; ' . ucwords(strtolower($this->Crud->read_field('id', $manager_id, 'user', 'fullname'))) . '';
						}

						$approved = '';
						if ($activate == 0) {
							$a_color = 'success';
							$approve_text = 'Account Activated';
							$approved = '<span class="text-success"><i class="anticon anticon-check-circle"></i>'.$approve_text.'</span> ';
						} else {
							$a_color = 'danger';
							$approve_text = 'Account Deactivated';
							$approved = '<span class="text-danger"><i class="anticon anticon-close-circle"></i>'.$approve_text.'</span> ';
						}

						$all_btn = '';
						// add manage buttons
						if($role_d){
							$all_btn .= '<a href="javascript:;" class="text-danger pop m-r-5 m-l-5 m-b-5" pageTitle="Delete ' . $fullname . '" pageName="' . site_url($mod . '/manage/delete/' . $id) . '"><i class="anticon anticon-delete"></i><span>Delete</span></a>
							
						';
						}

						if($role_u){
							$all_btn .= '<a href="javascript:;" class="text-primary pop m-r-5 m-l-5 m-b-5" pageTitle="Edit ' . $fullname . '" pageName="' . site_url($mod . '/manage/edit/' . $id) . '"><i class="anticon anticon-edit"></i><span>Edit</span></a>
							
						';
						}
						
						$item .= '
							<tr>
								<td>
									<div class="user-card">
										
										<div class="user-info">
											<span class="tb-lead"><b>' . ucwords($fullname) . ' </b><span class="dot dot-' . $a_color . ' ms-1"></span></span>
											<br>
										</div>
									</div>
								</td>
								<td><span class=" ">' . $email . '<br>'.$phone.'</span></td>
								<td><span class=" ">' . $city . '<br>'.$state.'</span></td>
								<td><span class=" ">' . $market . '</span></td>
								<td><span class=" ">' . $approved . '</span></td>
								<td>
									' . $all_btn . '
								</td>
							</tr>
							
						';
						$a++;
					}
				}

			}

			if (empty($item)) {
				$resp['item'] = $items . '
					<tr><td colspan="8"><div class="text-center text-muted">
						<br/><br/><br/>
						<i class="anticon anticon-team" style="font-size:150px;"></i><br/><br/>No Marketing Record Returned
					</div></td></tr>
				';
			} else {
				$resp['item'] = $items . $item;
				if ($offset >= 25) {
					$resp['item'] = $item;
				}

			}
			$resp['count'] = $counts;

			$more_record = $counts - ($offset + $rec_limit);
			$resp['left'] = $more_record;

			if ($counts > ($offset + $rec_limit)) { // for load more records
				$resp['limit'] = $rec_limit;
				$resp['offset'] = $offset + $limit;
			} else {
				$resp['limit'] = 0;
				$resp['offset'] = 0;
			}

			echo json_encode($resp);
			die;
		}

		if ($param1 == 'manage') { // view for form data posting
			return view('account/marketing_form', $data);
		} else { // view for main page

			$data['title'] = 'Marketing - ' . app_name;
			$data['page_active'] = 'accounts/marketing';
			return view('account/marketing', $data);
		}
	}


	public function administrator($param1 = '', $param2 = '', $param3 = '')
	{
		// check session login
		if ($this->session->get('plx_id') == '') {
			$request_uri = uri_string();
			$this->session->set('plx_redirect', $request_uri);
			return redirect()->to(site_url('auth'));
		}

		$mod = 'accounts/administrator';
		$switch_id = $this->session->get('switch_church_id');

		$log_id = $this->session->get('plx_id');
		$role_id = $this->Crud->read_field('id', $log_id, 'user', 'role_id');
		if (!empty($switch_id)) {
			$church_type = $this->Crud->read_field('id', $switch_id, 'church', 'type');
			if ($church_type == 'region') {
				$role_id = $this->Crud->read_field('name', 'Regional Manager', 'access_role', 'id');
			}
			if ($church_type == 'zone') {
				$role_id = $this->Crud->read_field('name', 'Zonal Manager', 'access_role', 'id');
			}
			if ($church_type == 'group') {
				$role_id = $this->Crud->read_field('name', 'Group Manager', 'access_role', 'id');
			}
			if ($church_type == 'church') {
				$role_id = $this->Crud->read_field('name', 'Church Leader', 'access_role', 'id');
			}
		}
		$role = strtolower($this->Crud->read_field('id', $role_id, 'access_role', 'name'));
		$role_c = $this->Crud->module($role_id, $mod, 'create');
		$role_r = $this->Crud->module($role_id, $mod, 'read');
		$role_u = $this->Crud->module($role_id, $mod, 'update');
		$role_d = $this->Crud->module($role_id, $mod, 'delete');
		if ($role_r == 0) {
			// return redirect()->to(site_url('dashboard'));	
		}
		$data['log_id'] = $log_id;
		$data['role'] = $role;
		$data['role_c'] = $role_c;

		$data['current_language'] = $this->session->get('current_language');
		$table = 'user';
		$form_link = site_url($mod);
		if ($param1) {
			$form_link .= '/' . $param1;
		}
		if ($param2) {
			$form_link .= '/' . $param2 . '/';
		}
		if ($param3) {
			$form_link .= $param3;
		}

		// pass parameters to view
		$data['param1'] = $param1;
		$data['param2'] = $param2;
		$data['param3'] = $param3;
		$data['form_link'] = $form_link;

		$church_id = $this->session->get('church_id');
		// manage record
		if ($param1 == 'manage') {
			// prepare for delete
			if ($param2 == 'delete') {
				if ($param3) {
					$edit = $this->Crud->read_single('id', $param3, $table);
					if (!empty($edit)) {
						foreach ($edit as $e) {
							$data['d_id'] = $e->id;
						}
					}

					if ($_POST) {
						$del_id = $this->request->getPost('d_user_id');
						$code = $this->Crud->read_field('id', $del_id, 'user', 'firstname');
						if ($this->Crud->deletes('id', $del_id, $table) > 0) {
							echo $this->Crud->msg('success', 'Record Deleted');

							///// store activities
							$by = $this->Crud->read_field('id', $log_id, 'user', 'firstname');
							$action = $by . ' deleted Administrator (' . $code . ')';
							$this->Crud->activity('user', $del_id, $action);
							echo '<script>
								load_admin("","",' . $church_id . ');
								$("#modal").modal("hide");
							</script>';
						} else {
							echo $this->Crud->msg('danger', 'Please try later');
						}
						exit;
					}
				}
			} elseif ($param2 == 'admin_send') {
				if ($param3) {
					$admin_id = $param3;
					if ($admin_id) {
						$fullname = $this->Crud->read_field('id', $admin_id, 'user', 'fullname');
						$role_id = $this->Crud->read_field('id', $admin_id, 'user', 'role_id');
						$roles = $this->Crud->read_field('id', $role_id, 'access_role', 'name');
						$email = $this->Crud->read_field('id', $admin_id, 'user', 'email');
						$phone = $this->Crud->read_field('id', $admin_id, 'user', 'phone');
						$church_id = $this->Crud->read_field('id', $admin_id, 'user', 'church_id');
						
						$name = ucwords($fullname );
						$body = '
							Dear ' . $name . ', <br><br>
								<p>A ' . ucwords($roles) . ' account has been created for you on the ' . htmlspecialchars(app_name) . ' platform.</p>
    							Below are your Account Details:<br><br>

								Website: ' . site_url() . '
								Email: ' . $email . '<br>
								Phone: ' . $phone . '<br>
								
								<p>To ensure the security of your account, please set your password by clicking the link below:</p>
    

								<p>This link will direct you to a secure page where you can choose your own password. If you encounter any issues or have questions, please feel free to contact our support team.</p>
								<p><strong>Important:</strong> Do not disclose your login credentials to anyone to avoid unauthorized access.</p>
								<p>Welcome aboard, and we look forward to your participation!</p>
								<p>Best regards,<br>
								
						';
						if ($this->request->getMethod() == 'post') {
							$head = 'Welcome to '.app_name.' - Set Your Password';
							$email_status = $this->Crud->send_email($email, $head, $body);
							if ($email_status > 0) {
								echo $this->Crud->msg('success', 'Login Credential Sent to Email Successfully');
								echo '<script>
										load_admin("","",' . $church_id . ');
										$("#modal").modal("hide");
									</script>';
							} else {
								echo $this->Crud->msg('danger', 'Error Sending Email');
							}
							die;
						}

					}

				}
			} else {
				// prepare for edit
				if ($param2 == 'edit') {
					if ($param3) {
						$edit = $this->Crud->read_single('id', $param3, $table);
						if (!empty($edit)) {
							foreach ($edit as $e) {
								$data['e_id'] = $e->id;
								$data['e_fullname'] = $e->fullname;
								$data['e_phone'] = $e->phone;
								$data['e_activate'] = $e->ban;
								$data['e_email'] = $e->email;
								$data['e_role_id'] = $e->role_id;
							}
						}
					}
				}

				if ($this->request->getMethod() == 'post') {
					$user_id = $this->request->getPost('user_id');
					$fullname = $this->request->getPost('fullname');
					$phone = $this->request->getPost('phone');
					$email = $this->request->getPost('email');
					$activate = $this->request->getPost('activate');
					$password = $this->request->getPost('password');


					$church_type = $this->Crud->read_field('id', $church_id, 'church', 'type');
					$regional_id = $this->Crud->read_field('id', $church_id, 'church', 'regional_id');
					$zonal_id = $this->Crud->read_field('id', $church_id, 'church', 'zonal_id');
					$group_id = $this->Crud->read_field('id', $church_id, 'church', 'group_id');
					$state_id = $this->Crud->read_field('id', $church_id, 'church', 'state_id');
					$country_id = $this->Crud->read_field('id', $church_id, 'church', 'country_id');
					$city_id = $this->Crud->read_field('id', $church_id, 'church', 'city_id');
					
					$urole_id = $this->Crud->read_field('name', 'Church Administrator', 'access_role', 'id');

					$ins_data['fullname'] = $fullname;
					$ins_data['email'] = $email;
					$ins_data['phone'] = $phone;
					$ins_data['ban'] = $activate;
					$ins_data['role_id'] = $urole_id;
					if ($password) {
						$ins_data['password'] = md5($password);
					}

					// do create or update
					if ($user_id) {
						$upd_rec = $this->Crud->updates('id', $user_id, $table, $ins_data);
						if ($upd_rec > 0) {
							echo $this->Crud->msg('success', 'Record Updated');

							///// store activities
							$by = $this->Crud->read_field('id', $log_id, 'user', 'fullname');
							$code = $this->Crud->read_field('id', $user_id, 'user', 'fullname');
							$action = $by . ' updated Church Administrator (' . $code . ') Record';
							$this->Crud->activity('user', $user_id, $action);
							echo '<script>
									load_admin("","",' . $church_id . ');
									$("#modal").modal("hide");
								</script>';
						} else {
							echo $this->Crud->msg('info', 'No Changes');
						}
					} else {
						if ($this->Crud->check('email', $email, $table) > 0 || $this->Crud->check('phone', $phone, $table) > 0) {
							echo $this->Crud->msg('warning', ('Email and/or Phone Already Exist'));
						} else {
							$ins_data['country_id'] = $country_id;
							$ins_data['state_id'] = $state_id;
							$ins_data['city_id'] = $city_id;
							$ins_data['regional_id'] = $regional_id;
							$ins_data['zonal_id'] = $zonal_id;
							$ins_data['group_id'] = $group_id;
							$ins_data['church_id'] = $church_id;
							$ins_data['church_type'] = $church_type;
							$ins_data['is_church_admin'] = 1;
							$ins_data['reg_date'] = date(fdate);

							$ins_rec = $this->Crud->create($table, $ins_data);
							if ($ins_rec > 0) {
								echo $this->Crud->msg('success', 'Record Created');
								
								///// store activities
								$by = $this->Crud->read_field('id', $log_id, 'user', 'fullname');
								$code = $this->Crud->read_field('id', $ins_rec, 'user', 'fullname');
								$action = $by . ' created Administrator (' . $code . ')';
								$this->Crud->activity('user', $ins_rec, $action);

								echo '<script>
									load_admin("","",' . $church_id . ');
									$("#modal").modal("hide");
								</script>';
							} else {
								echo $this->Crud->msg('danger', 'Please try later');
							}
						}
					}
					exit;
				}
			}
		}


		// record listing
		if ($param1 == 'load') {
			$limit = $param2;
			$offset = $param3;

			$rec_limit = 50;
			$item = '';
			if (empty($limit)) {
				$limit = $rec_limit;
			}
			if (empty($offset)) {
				$offset = 0;
			}


			if (!empty($this->request->getPost('status'))) {
				$status = $this->request->getPost('status');
			} else {
				$status = '';
			}
			$search = $this->request->getPost('search');
			$church_id = $this->request->getPost('id');
			$this->session->set('church_id', $church_id);

			if (empty($ref_status))
				$ref_status = 0;
			$items = '
					
			';
			$a = 1;

			//echo $status;
			$log_id = $this->session->get('plx_id');
			if (!$log_id) {
				$item = '<div class="text-center text-muted">Session Timeout! - Please login again</div>';
			} else {
				
				$all_rec = $this->Crud->filter_church_admin('', '', $log_id, $status, $search, $church_id);
				// $all_rec = json_decode($all_rec);
				if (!empty($all_rec)) {
					$counts = count($all_rec);
				} else {
					$counts = 0;
				}

				$query = $this->Crud->filter_church_admin($limit, $offset, $log_id, $status, $search, $church_id);
				$data['count'] = $counts;


				if (!empty($query)) {
					foreach ($query as $q) {
						$id = $q->id;
						$fullname = $q->fullname;
						$email = $q->email;
						$phone = $q->phone;
						$activate = $q->ban;
						$u_role = $this->Crud->read_field('id', $q->role_id, 'access_role', 'name');
						$reg_date = date('M d, Y h:ia', strtotime($q->reg_date));

						$referral = '';

						$approved = '';
						if ($activate == 0) {
							$a_color = 'success';
							$approve_text = 'Account Activated';
							$approved = '<span class="text-success"><i class="anticon anticon-check-circle"></i>'.$approve_text.'</span> ';
						} else {
							$a_color = 'danger';
							$approve_text = 'Account Deactivated';
							$approved = '<span class="text-danger"><i class="anticon anticon-close-circle"></i>'.$approve_text.'</span> ';
						}

						// add manage buttons

						
						$all_btn = '
							<a href="javascript:;" class="text-primary pop m-r-5 m-l-5 m-b-5" pageTitle="Edit ' . $fullname . '" pageName="' . site_url($mod . '/manage/edit/' . $id) . '"><i class="anticon anticon-edit"></i><span>Edit</span></a>
							<a href="javascript:;" class="text-danger pop m-r-5 m-l-5 m-b-5" pageTitle="Delete ' . $fullname . '" pageName="' . site_url($mod . '/manage/delete/' . $id) . '"><i class="anticon anticon-delete"></i><span>Delete</span></a>
							<a href="javascript:;" pageTitle="Send Login" id="send_btn"  class="text-success pop m-r-5 m-l-5 m-b-5" pageName="' . site_url($mod . '/manage/admin_send/' . $id) . '"><i class="anticon anticon-share-alt"></i> <span>Send Login</span></a>
							
						';

						



						$item .= '
							<tr>
								<td>
									<div class="user-card">
										
										<div class="user-info">
											<span class="tb-lead"><b>' . ucwords($fullname) . ' </b><span class="dot dot-' . $a_color . ' ms-1"></span></span>
											<br>
											
										</div>
									</div>
								</td>
								<td><span class=" ">' . $email . '</span></td>
								<td><span class=" ">' . $phone . '</span></td>
								<td><span class=" ">' . $approved . '</span></td>
								<td><span class="tb-amount ">' . $reg_date . ' </span></td>
								<td>
									' . $all_btn . '
								</td>
							</tr>
							
						';
						$a++;
					}
				}

			}

			if (empty($item)) {
				$resp['item'] = $items . '
					<tr><td colspan="8"><div class="text-center text-muted">
						<br/><br/><br/>
						<i class="anticon anticon-user" style="font-size:150px;"></i><br/><br/>No Church Admin Returned
					</div></td></tr>
				';
			} else {
				$resp['item'] = $items . $item;
				if ($offset >= 25) {
					$resp['item'] = $item;
				}

			}

			$resp['count'] = $counts;

			$more_record = $counts - ($offset + $rec_limit);
			$resp['left'] = $more_record;

			if ($counts > ($offset + $rec_limit)) { // for load more records
				$resp['limit'] = $rec_limit;
				$resp['offset'] = $offset + $limit;
			} else {
				$resp['limit'] = 0;
				$resp['offset'] = 0;
			}

			echo json_encode($resp);
			die;
		}

		if ($param1 == 'manage') { // view for form data posting
			return view('account/admin_form', $data);
		}

	}

	public function pastor($param1 = '', $param2 = '', $param3 = '')
	{
		// check session login
		if ($this->session->get('plx_id') == '') {
			$request_uri = uri_string();
			$this->session->set('plx_redirect', $request_uri);
			return redirect()->to(site_url('auth'));
		}

		$mod = 'church/pastor';
		$switch_id = $this->session->get('switch_church_id');

		$log_id = $this->session->get('plx_id');
		$role_id = $this->Crud->read_field('id', $log_id, 'user', 'role_id');
		if (!empty($switch_id)) {
			$church_type = $this->Crud->read_field('id', $switch_id, 'church', 'type');
			if ($church_type == 'region') {
				$role_id = $this->Crud->read_field('name', 'Regional Manager', 'access_role', 'id');
			}
			if ($church_type == 'zone') {
				$role_id = $this->Crud->read_field('name', 'Zonal Manager', 'access_role', 'id');
			}
			if ($church_type == 'group') {
				$role_id = $this->Crud->read_field('name', 'Group Manager', 'access_role', 'id');
			}
			if ($church_type == 'church') {
				$role_id = $this->Crud->read_field('name', 'Church Leader', 'access_role', 'id');
			}
		}
		$role = strtolower($this->Crud->read_field('id', $role_id, 'access_role', 'name'));
		$role_c = $this->Crud->module($role_id, $mod, 'create');
		$role_r = $this->Crud->module($role_id, $mod, 'read');
		$role_u = $this->Crud->module($role_id, $mod, 'update');
		$role_d = $this->Crud->module($role_id, $mod, 'delete');
		if ($role_r == 0) {
			// return redirect()->to(site_url('dashboard'));	
		}
		$data['log_id'] = $log_id;
		$data['role'] = $role;
		$data['role_c'] = $role_c;

		$data['current_language'] = $this->session->get('current_language');
		$table = 'user';
		$form_link = site_url($mod);
		if ($param1) {
			$form_link .= '/' . $param1;
		}
		if ($param2) {
			$form_link .= '/' . $param2 . '/';
		}
		if ($param3) {
			$form_link .= $param3;
		}

		// pass parameters to view
		$data['param1'] = $param1;
		$data['param2'] = $param2;
		$data['param3'] = $param3;
		$data['form_link'] = $form_link;

		$church_id = $this->session->get('church_id');
		// manage record
		if ($param1 == 'manage') {
			// prepare for delete
			if ($param2 == 'delete') {
				if ($param3) {
					$edit = $this->Crud->read_single('id', $param3, $table);
					if (!empty($edit)) {
						foreach ($edit as $e) {
							$data['d_id'] = $e->id;
						}
					}

					if ($_POST) {
						$del_id = $this->request->getPost('d_user_id');
						$code = $this->Crud->read_field('id', $del_id, 'user', 'firstname');
						if ($this->Crud->deletes('id', $del_id, $table) > 0) {
							echo $this->Crud->msg('success', 'Record Deleted');

							///// store activities
							$by = $this->Crud->read_field('id', $log_id, 'user', 'firstname');
							$action = $by . ' deleted Pastor (' . $code . ')';
							$this->Crud->activity('user', $del_id, $action);
							echo '<script>
								load_pastor("","",' . $church_id . ');
								$("#modal").modal("hide");
							</script>';
						} else {
							echo $this->Crud->msg('danger', 'Please try later');
						}
						exit;
					}
				}
			} elseif ($param2 == 'admin_send') {
				if ($param3) {
					$admin_id = $param3;
					if ($admin_id) {
						$surname = $this->Crud->read_field('id', $admin_id, 'user', 'surname');
						$firstname = $this->Crud->read_field('id', $admin_id, 'user', 'firstname');
						$role_id = $this->Crud->read_field('id', $admin_id, 'user', 'role_id');
						$roles = $this->Crud->read_field('id', $role_id, 'access_role', 'name');
						$othername = $this->Crud->read_field('id', $admin_id, 'user', 'othername');
						$user_no = $this->Crud->read_field('id', $admin_id, 'user', 'user_no');
						$email = $this->Crud->read_field('id', $admin_id, 'user', 'email');
						$phone = $this->Crud->read_field('id', $admin_id, 'user', 'phone');
						$ministry_id = $this->Crud->read_field('id', $admin_id, 'user', 'ministry_id');
						$church_id = $this->Crud->read_field('id', $admin_id, 'user', 'church_id');
						$ministry = $this->Crud->read_field('id', $ministry_id, 'ministry', 'name');

						$name = ucwords($firstname . ' ' . $othername . ' ' . $surname);
						$reset_link = site_url('auth/email_verify?uid=' . $user_no);
						$link = '<p><a href="' . htmlspecialchars($reset_link) . '">Set Your Password</a></p>';
						$body = '
							Dear ' . $name . ', <br><br>
								<p>A ' . ucwords($roles) . ' account has been created for you on the ' . htmlspecialchars(ucwords($ministry)) . ' within the ' . htmlspecialchars(app_name) . ' platform.</p>
    							Below are your Account Details:<br><br>

								Website: ' . site_url() . '
								Membership ID: ' . $user_no . '<br>
								Email: ' . $email . '<br>
								Phone: ' . $phone . '<br>
								
								<p>To ensure the security of your account, please set your password by clicking the link below:</p>
    

								' . $link . '

								<p>This link will direct you to a secure page where you can choose your own password. If you encounter any issues or have questions, please feel free to contact our support team.</p>
								<p><strong>Important:</strong> Do not disclose your login credentials to anyone to avoid unauthorized access.</p>
								<p>Welcome aboard, and we look forward to your participation!</p>
								<p>Best regards,<br>
								
						';
						if ($this->request->getMethod() == 'post') {
							$head = 'Welcome to ' . $ministry . ' - Set Your Password';
							$email_status = $this->Crud->send_email($email, $head, $body);
							if ($email_status > 0) {
								echo $this->Crud->msg('success', 'Login Credential Sent to Email Successfully');
								echo '<script>
										load_pastor("","",' . $church_id . ');
										$("#modal").modal("hide");
									</script>';
							} else {
								echo $this->Crud->msg('danger', 'Error Sending Email');
							}
							die;
						}

					}

				}
			} else {
				// prepare for edit
				if ($param2 == 'edit') {
					if ($param3) {
						$edit = $this->Crud->read_single('id', $param3, $table);
						if (!empty($edit)) {
							foreach ($edit as $e) {
								$data['e_id'] = $e->id;
								$data['e_surname'] = $e->surname;
								$data['e_firstname'] = $e->firstname;
								$data['e_phone'] = $e->phone;
								$data['e_address'] = $e->address;
								$data['e_activate'] = $e->activate;
								$data['e_title'] = $e->title;
								$data['e_email'] = $e->email;
								$data['e_role_id'] = $e->role_id;
							}
						}
					}
				}

				if ($this->request->getMethod() == 'post') {
					$user_id = $this->request->getPost('user_id');
					$surname = $this->request->getPost('surname');
					$firstname = $this->request->getPost('firstname');
					$phone = $this->request->getPost('phone');
					$email = $this->request->getPost('email');
					$title = $this->request->getPost('title');
					$address = $this->request->getPost('address');
					$activate = $this->request->getPost('activate');
					$urole_id = $this->request->getPost('role_id');
					$password = $this->request->getPost('password');


					$church_type = $this->Crud->read_field('id', $church_id, 'church', 'type');
					$ministry_id = $this->Crud->read_field('id', $church_id, 'church', 'ministry_id');
					$member_role = $this->Crud->read_field('name', 'Pastor', 'access_role', 'id');
					$urole = $this->Crud->read_field('id', $urole_id, 'access_role', 'name');
					if ($urole == 'Pastor-in-Charge') {
						$rolesa = $this->Crud->read2('role_id', $urole_id, 'church_id', $church_id, 'user');
						if (!empty($rolesa)) {
							foreach ($rolesa as $r) {
								$this->Crud->updates('id', $r->id, 'user', array('role_id' => $member_role));
							}
						}

					}


					if (empty($title) || $title == ' ') {
						echo $this->Crud->msg('danger', 'Select Title');
						die;
					}

					$ins_data['surname'] = $surname;
					$ins_data['firstname'] = $firstname;
					$ins_data['email'] = $email;
					$ins_data['phone'] = $phone;
					$ins_data['activate'] = $activate;
					$ins_data['title'] = $title;
					$ins_data['address'] = $address;
					$ins_data['role_id'] = $urole_id;
					if ($password) {
						$ins_data['password'] = md5($password);
					}

					// do create or update
					if ($user_id) {
						$upd_rec = $this->Crud->updates('id', $user_id, $table, $ins_data);
						if ($upd_rec > 0) {
							echo $this->Crud->msg('success', 'Pastor Record Updated');

							///// store activities
							$by = $this->Crud->read_field('id', $log_id, 'user', 'firstname');
							$code = $this->Crud->read_field('id', $user_id, 'user', 'firstname');
							$action = $by . ' updated Pastor (' . $code . ') Record';
							$this->Crud->activity('user', $user_id, $action);
							echo '<script>
									load_pastor("","",' . $church_id . ');
									$("#modal").modal("hide");
								</script>';
						} else {
							echo $this->Crud->msg('info', 'No Changes');
						}
					} else {
						if ($this->Crud->check('email', $email, $table) > 0 || $this->Crud->check('phone', $phone, $table) > 0) {
							echo $this->Crud->msg('warning', ('Email and/or Phone Already Exist'));
						} else {
							$ins_data['ministry_id'] = $ministry_id;
							$ins_data['church_id'] = $church_id;
							$ins_data['is_pastor'] = 1;
							$ins_data['church_type'] = $church_type;
							$ins_data['reg_date'] = date(fdate);

							$ins_rec = $this->Crud->create($table, $ins_data);
							if ($ins_rec > 0) {
								echo $this->Crud->msg('success', 'Pastor Record Created');
								$this->Crud->updates('id', $ins_rec, 'user', array('user_no' => 'CEAM-00' . $ins_rec));

								///// store activities
								$by = $this->Crud->read_field('id', $log_id, 'user', 'firstname');
								$code = $this->Crud->read_field('id', $ins_rec, 'user', 'firstname');
								$action = $by . ' created Pastor (' . $code . ')';
								$this->Crud->activity('user', $ins_rec, $action);

								echo '<script>
									load_pastor("","",' . $church_id . ');
									$("#modal").modal("hide");
								</script>';
							} else {
								echo $this->Crud->msg('danger', 'Please try later');
							}
						}
					}
					exit;
				}
			}
		}


		// record listing
		if ($param1 == 'load') {
			$limit = $param2;
			$offset = $param3;

			$rec_limit = 50;
			$item = '';
			if (empty($limit)) {
				$limit = $rec_limit;
			}
			if (empty($offset)) {
				$offset = 0;
			}


			if (!empty($this->request->getPost('status'))) {
				$status = $this->request->getPost('status');
			} else {
				$status = '';
			}
			$search = $this->request->getPost('search');
			$church_id = $this->request->getPost('id');
			$this->session->set('church_id', $church_id);

			if (empty($ref_status))
				$ref_status = 0;
			$items = '
					
			';
			$a = 1;

			//echo $status;
			$log_id = $this->session->get('plx_id');
			if (!$log_id) {
				$item = '<div class="text-center text-muted">Session Timeout! - Please login again</div>';
			} else {
				$role_ids = $this->Crud->read_field('name', 'Pastor', 'access_role', 'id');

				$all_rec = $this->Crud->filter_church_pastor('', '', $log_id, $status, $search, $church_id);
				// $all_rec = json_decode($all_rec);
				if (!empty($all_rec)) {
					$counts = count($all_rec);
				} else {
					$counts = 0;
				}

				$query = $this->Crud->filter_church_pastor($limit, $offset, $log_id, $status, $search, $church_id);
				$data['count'] = $counts;


				if (!empty($query)) {
					foreach ($query as $q) {
						$id = $q->id;
						$fullname = $q->firstname . ' ' . $q->surname;
						$email = $q->email;
						$phone = $q->phone;
						$address = $q->address;
						$img = $this->Crud->image($q->img_id, 'big');
						$activate = $q->activate;
						$u_role = $this->Crud->read_field('id', $q->role_id, 'access_role', 'name');
						$reg_date = date('M d, Y h:ia', strtotime($q->reg_date));

						$referral = '';

						$approved = '';
						if ($activate == 1) {
							$a_color = 'success';
							$approve_text = 'Account Activated';
							$approved = '<span class="text-primary"><i class="ri-check-circle-line"></i></span> ';
						} else {
							$a_color = 'danger';
							$approve_text = 'Account Deactivated';
							$approved = '<span class="text-danger"><i class="ri-check-circle-line"></i></span> ';
						}

						// add manage buttons
						if (!empty($switch_id)) {
							$all_btn = '
								<li><a href="javascript:;" pageTitle="Send Login" id="send_btn"  class="text-success pop" pageName="' . site_url($mod . '/manage/admin_send/' . $id) . '"><em class="icon ni ni-share"></em> <span>Send Login</span></a></li>
								
							';
						} else {
							$all_btn = '
								<li><a href="javascript:;" class="text-primary pop" pageTitle="Edit ' . $fullname . '" pageName="' . site_url($mod . '/manage/edit/' . $id) . '"><em class="icon ni ni-edit-alt"></em><span>Edit</span></a></li>
								<li><a href="javascript:;" class="text-danger pop" pageTitle="Delete ' . $fullname . '" pageName="' . site_url($mod . '/manage/delete/' . $id) . '"><em class="icon ni ni-trash-alt"></em><span>Delete</span></a></li>
								<li><a href="javascript:;" pageTitle="Send Login" id="send_btn"  class="text-success pop" pageName="' . site_url($mod . '/manage/admin_send/' . $id) . '"><em class="icon ni ni-share"></em> <span>Send Login</span></a></li>
								
							';

						}



						$item .= '
							<tr>
								<td>
									<div class="user-card">
										<div class="user-avatar ">
											<img alt="" src="' . site_url($img) . '" height="40px"/>
										</div>
										<div class="user-info">
											<span class="tb-lead"><b>' . ucwords($fullname) . '</b> <span class="dot dot-' . $a_color . ' ms-1"></span></span>
										</div>
									</div>
								</td>
								<td><span class=" ">' . $email . '</span></td>
								<td><span class=" ">' . $phone . '</span></td>
								<td><span class=" ">' . $u_role . '</span></td>
								<td><span class=" ">' . ucwords($address) . '</span></td>
								<td><span class="tb-amount ">' . $reg_date . ' </span></td>
								<td>
									<div class="drodown">
										<a href="#" class="dropdown-toggle btn btn-icon btn-trigger" data-bs-toggle="dropdown"><em class="icon ni ni-more-h"></em></a>
										<div class="dropdown-menu dropdown-menu-end">
											<ul class="link-list-opt no-bdr">
												' . $all_btn . '
											</ul>
										</div>
									</div>
								</td>
							</tr>
							
						';
						$a++;
					}
				}

			}

			if (empty($item)) {
				$resp['item'] = $items . '
					<tr><td colspan="8"><div class="text-center text-muted">
						<br/><br/><br/><br/>
						<i class="ni ni-users" style="font-size:150px;"></i><br/><br/>No Pastor Account Returned
					</div></td></tr>
				';
			} else {
				$resp['item'] = $items . $item;
				if ($offset >= 25) {
					$resp['item'] = $item;
				}

			}

			$resp['count'] = $counts;

			$more_record = $counts - ($offset + $rec_limit);
			$resp['left'] = $more_record;

			if ($counts > ($offset + $rec_limit)) { // for load more records
				$resp['limit'] = $rec_limit;
				$resp['offset'] = $offset + $limit;
			} else {
				$resp['limit'] = 0;
				$resp['offset'] = 0;
			}

			echo json_encode($resp);
			die;
		}

		if ($param1 == 'manage') { // view for form data posting
			return view('church/pastor_form', $data);
		}

	}

	public function get_state($country_id) {
        $states = '';

		$state_id = $this->request->getGet('state_id');

		$all_states = $this->Crud->read_single_order('country_id', $country_id, 'state', 'name', 'asc');
		if(!empty($all_states)) {
			foreach($all_states as $as) {
				$s_sel = '';
				if(!empty($state_id)) if($state_id == $as->id) $s_sel = 'selected';
				$states .= '<option value="'.$as->id.'" '.$s_sel.'>'.$as->name.'</option>';
			}
		}

		echo $states;
		die;
	}

	public function get_city($state_id) {
        $states = '';
		$all_states = $this->Crud->read_single_order('state_id', $state_id, 'city', 'name', 'asc');
		if($state_id == 'all'){

			$all_states = $this->Crud->read_order('city', 'name', 'asc');
		
		}
		if(!empty($all_states)) {
			foreach($all_states as $as) {
				$s_sel = '';
				$states .= '<option value="'.$as->id.'" '.$s_sel.'>'.$as->name.'</option>';
			}
		}

		echo $states;
		die;
	}

	

	public function get_region(){
		$church = '';
		$regions = $this->Crud->read_single_order('type', 'regional', 'church', 'name', 'asc');
		$log_id = $this->session->get('plx_id');
       
		$user = $this->Crud->read_single('id', $log_id, 'user');
		$user = $user[0];
		$is_admin = (int) ($user->is_church_admin ?? 0);
		$church_type = strtolower($user->church_type ?? '');
		$church_id = $user->church_id ?? 0;
	
		// Default to all regional churches
		$regions = $this->Crud->read_single_order('type', 'regional', 'church', 'name', 'asc');

		if ($is_admin) {
			if ($church_type === 'regional') {
				// Return only own regional church
				$regions = [$this->Crud->read_single('id', $church_id, 'church')[0]];
			} else {
				// Fetch the regional church that owns this user's church
				$regional_id = $this->Crud->read_field('id', $church_id, 'church', 'regional_id');
				if ($regional_id) {
					$regions = [$this->Crud->read_single('id', $regional_id, 'church')[0]];
				} else {
					$regions = []; // nothing found
				}
			}
		}
		if(!empty($regions)){
			foreach ($regions as $region) {
				$s_sel = '';
				$church .= '<option value="'.$region->id.'" '.$s_sel.'>'.ucwords($region->name).'</option>';
			}
		}
		echo $church;
		die;
		
	}

	public function get_zone()
	{
		$church = '';
		$log_id = $this->session->get('plx_id');
		$regional_id_param = $this->request->getGet('regional_id');

		$user = $this->Crud->read_single('id', $log_id, 'user');
		$user = $user[0];

		$is_admin = (int) ($user->is_church_admin ?? 0);
		$church_type = strtolower($user->church_type ?? '');
		$church_id = (int) ($user->church_id ?? 0);

		$zones = [];

		if ($is_admin) {
			if ($church_type === 'zonal') {
				$zones = [$this->Crud->read_single('id', $church_id, 'church')[0]];
			} elseif ($church_type === 'group' || $church_type === 'assembly') {
				$zonal_id = $this->Crud->read_field('id', $church_id, 'church', 'zonal_id');
				$zones = $zonal_id ? [$this->Crud->read_single('id', $zonal_id, 'church')[0]] : [];
			} elseif ($church_type === 'regional') {
				$zones = $this->Crud->read2_order('regional_id', $church_id, 'type', 'zonal', 'church', 'name', 'asc');
			}
		} else {
			$zones = $this->Crud->read2_order('regional_id', $regional_id_param, 'type', 'zonal', 'church', 'name', 'asc');
		}

		foreach ((array) $zones as $zone) {
			$church .= '<option value="' . $zone->id . '">' . ucwords($zone->name) . '</option>';
		}

		echo $church;
		die;
	}



	public function get_group()
	{
		$church = '';
		$log_id = $this->session->get('plx_id');
		$zonal_id_param = $this->request->getGet('zonal_id');

		$user = $this->Crud->read_single('id', $log_id, 'user');
		$user = $user[0];

		$is_admin = (int) ($user->is_church_admin ?? 0);
		$church_type = strtolower($user->church_type ?? '');
		$church_id = (int) ($user->church_id ?? 0);

		$groups = [];

		if ($is_admin) {
			if ($church_type === 'group') {
				$groups = [$this->Crud->read_single('id', $church_id, 'church')[0]];
			} elseif ($church_type === 'assembly') {
				$group_id = $this->Crud->read_field('id', $church_id, 'church', 'group_id');
				$groups = $group_id ? [$this->Crud->read_single('id', $group_id, 'church')[0]] : [];
			} elseif ($church_type === 'zonal') {
				$groups = $this->Crud->read2_order('zonal_id', $church_id, 'type', 'group', 'church', 'name', 'asc');
			} elseif ($church_type === 'regional') {
				$zone_ids = $this->Crud->read_field_array('regional_id', $church_id, 'church', 'id', ['type' => 'zonal']);
				$groups = !empty($zone_ids)
					? db_connect()->table('church')->whereIn('zonal_id', $zone_ids)->where('type', 'group')->orderBy('name')->get()->getResult()
					: [];
			}
		} else {
			$groups = $this->Crud->read2_order('zonal_id', $zonal_id_param, 'type', 'group', 'church', 'name', 'asc');
		}

		foreach ((array) $groups as $group) {
			$church .= '<option value="' . $group->id . '">' . ucwords($group->name) . '</option>';
		}

		echo $church;
		die;
	}


	public function kyc($param1='', $param2='', $param3=''){
		
		if ($param1 == 'check_email') {
			$email    = $this->request->getPost('email');
			$password = $this->request->getPost('password');
		
			// First, check if the email exists
			$user = $this->Crud->read_single('email', $email, 'user');
		
			if ($user && count($user) > 0) {
				$u = $user[0];
		
				// ✅ Password must match before continuing
				if ($u->password === md5($password)) {
					// ⛔ Block if KYC already completed
					if ((int)$u->profile_status === 1) {
						return $this->response->setJSON([
							'exists' => false,
							'error' => 'KYC already completed for this user.',
							'redirect_url' => 'https://pcdl4kids.com/events'
						]);
					}
					
		
					// ✅ Get up to 3 children for this parent
					$children = $this->Crud->read_single_order('parent_id', $u->id, 'child', 'id', 'ASC', 3);
		
					$child_data = [
						'child_name1' => '',
						'child_age1' => '',
						'child_name2' => '',
						'child_age2' => '',
						'child_name3' => '',
						'child_age3' => ''
					];
		
					if (!empty($children)) {
						foreach ($children as $i => $child) {
							if ($i == 0) {
								$child_data['child_name1'] = $child->name;
								$child_data['child_age1'] = $child->age_id;
							} elseif ($i == 1) {
								$child_data['child_name2'] = $child->name;
								$child_data['child_age2'] = $child->age_id;
							} elseif ($i == 2) {
								$child_data['child_name3'] = $child->name;
								$child_data['child_age3'] = $child->age_id;
							}
						}
					}
		
					// ✅ Send data
					return $this->response->setJSON([
						'exists' => true,
						'data' => array_merge([
							'fullname' => $u->fullname,
							'email'    => $u->email,
							'phone'    => $u->phone
						], $child_data)
					]);
				} else {
					// ❌ Password does not match
					return $this->response->setJSON([
						'exists' => false,
						'error'  => 'Invalid authentication'
					]);
				}
			} else {
				// ❌ Email not found
				return $this->response->setJSON([
					'exists' => false,
					'error'  => 'Email not found'
				]);
			}
		}
		
		if($param1 == 'password'){
			if($param2 == 'send_reset_code'){
				$email = $this->request->getPost('email');
				$status = false;
				if($email) {
					$user_id = $this->Crud->read_field('email', $email, 'user', 'id');
					if(empty($user_id)) {
						$msg = 'Invalid Email!';
					} else {
						$code = substr(md5(time().rand()), 0, 6);
						if($this->Crud->updates('id', $user_id, 'user', array('reset_code'=>$code)) > 0) {
							$status = true;
							$msg = 'Reset Code Sent! Check your inbox/spam to get reset code';
							$this->session->set('pl_email', $email);
		
							$fullname = $this->Crud->read_field('id', $user_id, 'user', 'fullname');
		
							// email content
							$bcc = '';
							$subject = 'Reset Code';
							$body = 'You requested to reset your account password. Your secret code is '.$code.'. If you do not request this action, please ignore. Your account will be protected. Thank you.';
							$this->send_email($email, $subject, $body, $fullname);
						}
					}
				}
				return $this->response->setJSON(['status' => $status, 'msg'=>$msg]);
			}

			if($param2 == 'verify_reset_code'){
				$email = $this->request->getPost('email');
				$code = $this->request->getPost('code');
				// Validate code against DB
				$status = false;
				if($code){
					$check = $this->Crud->check2('email', $email, 'reset_code', $code, 'user');
					if($check == 0){
						$msg = 'Incorrect Reset Code!!';
					} else {
						$status = true;
						$msg = 'Reset Code Validated successfully, Reset Your Password!';
					}
				}

				return $this->response->setJSON(['status' => $status, 'msg'=>$msg]);
			}


			if($param2 == 'do_reset_password'){
				$email = $this->request->getPost('email');
				$code = $this->request->getPost('code');
				$password = $this->request->getPost('password');
				// Validate code, update password
				$status = false;
				$confirm = $this->Crud->read_field2('email', $email, 'reset_code', $code, 'user', 'id');
				if(!$confirm){
					$msg = 'Invalid Credential Passed';
				} else{
					$status = true;
					$this->Crud->updates('id', $confirm, 'user', ['password'=> md5($password)]);
					$msg = 'Password Reset Successful. Now Login!';
				}
				
				return $this->response->setJSON(['status' => true]);
			}
		}

		if($param1 == 'login'){
			if($param2 == 'kids'){
				$email = strtolower($this->request->getPost('email'));
				$password = $this->request->getPost('password');
				// First, check if the email exists
				$user = $this->Crud->read_single('email', $email, 'user');
			
				if ($user && count($user) > 0) {
					$u = $user[0];
			
					// ✅ Password must match before continuing
					if ($u->password === md5($password)) {
						// ⛔ Block if KYC already completed
						if ((int)$u->profile_status === 1) {
							return $this->response->setJSON([
								'exists' => false,
								'error' => 'KYC already completed for this user.',
								'redirect_url' => 'https://pcdl4kids.com/events'
							]);
						}
			
						// ✅ Get up to 3 children for this parent
						$children = $this->Crud->read_single_order('parent_id', $u->id, 'child', 'id', 'ASC', 3);
			
						$child_data = [
							'child_name1' => '',
							'child_age1' => '',
							'child_name2' => '',
							'child_age2' => '',
							'child_name3' => '',
							'child_age3' => ''
						];
			
						if (!empty($children)) {
							foreach ($children as $i => $child) {
								if ($i == 0) {
									$child_data['child_name1'] = $child->name;
									$child_data['child_age1'] = $child->age_id;
								} elseif ($i == 1) {
									$child_data['child_name2'] = $child->name;
									$child_data['child_age2'] = $child->age_id;
								} elseif ($i == 2) {
									$child_data['child_name3'] = $child->name;
									$child_data['child_age3'] = $child->age_id;
								}
							}
						}
			
						// ✅ Send data
						return $this->response->setJSON([
							'exists' => true,
							'data' => array_merge([
								'fullname' => $u->fullname,
								'email'    => $u->email,
								'phone'    => $u->phone
							], $child_data)
						]);
					} else {
						// ❌ Password does not match
						return $this->response->setJSON([
							'exists' => false,
							'error'  => 'Invalid authentication'
						]);
					}
				} else {
					// ❌ Email not found
					return $this->response->setJSON([
						'exists' => false,
						'error'  => 'Email not found! Click the New Registration Button to 
						Register '
					]);
				}
			}

			if($param2 == 'pcdl') {
				$email = strtolower($this->request->getPost('email'));
				$response = $this->Crud->fetchUserSubscription($email);
				// print_r($response);
				// If no response or API failed
				if (!$response || !isset($response['statusCode']) || $response['statusCode'] != 200) {
					return $this->response->setJSON([
						'status'  => false,
						'message' => 'Unable to verify your access. Please register.'
					]);
				}
			
				// If user does not have access
				if (
					!isset($response['pcdl4KidsAccess']) ||
					strtolower($response['pcdl4KidsAccess']) !== 'true'
				) {
					return $this->response->setJSON([
						'status'  => false,
						'message' => 'You do not have access to PCDL4KIDS. Please register.'
					]);
				}
			
				// ✅ User has access
				return $this->response->setJSON([
					'status' => true,
					'message' => 'Access verified',
					'data' => [
						'email' => $email
						// Add more data if API returns additional info
					]
				]);
			}
			
		}

		if($param1 == 'submit'){
			if ($this->request->getMethod() == 'post') {
				helper(['form', 'url']);

				$fullname      = trim($this->request->getPost('fullname'));
				$email         = trim($this->request->getPost('emailDisplay'));
				$password      = $this->request->getPost('password');
				$pin           = trim($this->request->getPost('pin'));
				$phone         = trim($this->request->getPost('phone'));
				$church_id     = (int)$this->request->getPost('church_id');
				$country_id    = (int)$this->request->getPost('country_id');
				$state_id      = (int)$this->request->getPost('state_id');
				$city_id       = (int)$this->request->getPost('city_id');
				$church_type = $this->Crud->read_field('id', $church_id, 'church', 'type');
				$regional_id = $this->Crud->read_field('id', $church_id, 'church', 'regional_id');
				$zonal_id = $this->Crud->read_field('id', $church_id, 'church', 'zonal_id');
				$group_id = $this->Crud->read_field('id', $church_id, 'church', 'group_id');
				$ministry_id = $this->Crud->read_field('id', $church_id, 'church', 'ministry_id');
				$children      = $this->request->getPost('children');
				$children_age  = $this->request->getPost('children_age');

				$Error = '';
				if (empty($fullname)) $Error .= 'Fullname is required<br>';
				if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) $Error .= 'Valid email is required<br>';
				if (empty($pin)) $Error .= 'Parent\'s pin is required<br>';
				if (empty($church_id)) $Error .= 'Zone/Region is required<br>';
				if (empty($country_id) || empty($state_id) || empty($city_id)) $Error .= 'Complete location is required<br>';
				if (empty($children) || !is_array($children) || count(array_filter($children)) == 0) $Error .= 'At least one child name is required<br>';

				if (!empty($Error)) {
					echo $this->Crud->msg('danger', $Error);
					return;
				}

				
				// Check if email exists
				$existing_user = $this->Crud->read_single('email', $email, 'user');
				$user_data = [
					'fullname'      => $fullname,
					'pin'           => $pin,
					'phone'         => $phone,
					'church_id'     => $church_id,
					'church_type'   => $church_type,
					'regional_id'   => $regional_id,
					'zonal_id'      => $zonal_id,
					'group_id'      => $group_id,
					'ministry_id'   => $ministry_id,
					'country_id'    => $country_id,
					'state_id'      => $state_id,
					'city_id'       => $city_id,
					'profile_status'=> 1
				];
				
				
					// print_r(json_encode($user_data));
					// die;
								
				if ($existing_user && count($existing_user) > 0) {
					$user = $existing_user[0];
					$user_id = $user->id;

					// Redirect if already submitted KYC
					if ((int)$user->profile_status === 1) {
						echo $this->Crud->msg('info', 'You have already submitted your KYC. Redirecting to events...');
						echo '<script>setTimeout(function(){ window.location.href = "https://pcdl4kids.com/events"; }, 2000);</script>';
						return;
					}

					// Password check before update
					if (!empty($password)) {
						if (md5($password) != $user->password) {
							echo $this->Crud->msg('danger', 'Incorrect password. You cannot update this profile.');
							return;
						}
					} else {
						echo $this->Crud->msg('danger', 'Password is required to update existing record.');
						return;
					}

					// ✅ Update user record
					$this->Crud->updates('id', $user_id, 'user', $user_data);

				} else {
					// Validate password for new users
					if (empty($password)) {
						echo $this->Crud->msg('danger', 'Password is required to create a new profile.');
						return;
					}

					// Extend user_data for new user registration
					$user_data['email']      = $email;
					$user_data['password']   = md5($password);
					$user_data['role_id']    = $this->Crud->read_field('name', 'User', 'access_role', 'id');
					$user_data['reg_date']   = date('Y-m-d H:i:s');

					// ✅ Create new user
					$user_id = $this->Crud->create('user', $user_data);
				}

				$parent_id = $user_id; // Use parent/user ID as stored in pl_child.parent_id

				if (!empty($children)) {
					foreach ($children as $index => $child_name) {
						$child_name = trim($child_name);
						if (!empty($child_name)) {
							$age_id = isset($children_age[$index]) ? (int)$children_age[$index] : 0;

							// Check if child already exists
							$exists = $this->Crud->check3('parent_id',$parent_id,'name',$child_name,
								'age_id', $age_id, 'child');

							if (!$exists) {
								$this->Crud->create('child', [
									'parent_id' => $parent_id,
									'name' => $child_name,
									'age_id' => $age_id,
									'reg_date' => date('Y-m-d H:i:s')
								]);
							}
						}
					}
				}


				echo $this->Crud->msg('success', 'KYC submitted successfully.');
				echo '<script>setTimeout(function(){ window.location.href = "https://pcdl4kids.com/events"; }, 2000);</script>';
				die;
			}
		}
		
		$data['title'] = 'KYC | '.app_name;
		return view('auth/kyc', $data);
	}

	public function church_admin($param1='', $param2='', $param3=''){
		
		
		if ($param1 == 'submit') {
			if ($this->request->getMethod() == 'post') {
				helper(['form', 'url']);
		
				$fullname  = trim($this->request->getPost('fullname'));
				$email     = trim($this->request->getPost('email'));
				$password  = $this->request->getPost('password');
				$phone     = trim($this->request->getPost('phone'));
				$church_id = (int)$this->request->getPost('church_id');
				$ministry_id = (int)$this->request->getPost('ministry_id');
		
				// 🔒 Optional Bot Protection - Hidden input or simple honeypot
				$honeypot = $this->request->getPost('website'); // Should be empty
				if (!empty($honeypot)) {
					echo $this->Crud->msg('danger', 'Bot detection triggered.');
					die;
				}
		
				// ✅ Validation
				$Error = '';
				if (empty($fullname)) $Error .= 'Full name is required<br>';
				if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) $Error .= 'Valid email is required<br>';
				if (empty($password)) $Error .= 'Password is required<br>';
				if (empty($church_id)) $Error .= 'Zone/Region is required<br>';
		
				if (!empty($Error)) {
					echo $this->Crud->msg('danger', $Error);
					return;
				}
		
				// 🚫 Check if already exists
				if ($this->Crud->check('email', $email, 'user') > 0) {
					echo $this->Crud->msg('warning', 'Email already exists.');
					return;
				}
		
				// ✅ Create admin user
				$admin_data = [
					'fullname'    => $fullname,
					'email'       => $email,
					'phone'       => $phone,
					'password'    => md5($password),
					'church_id'   => $church_id,
					'ministry_id'   => $ministry_id,
					'role_id'     => $this->Crud->read_field('name', 'Church Administrator', 'access_role', 'id'),
					'profile_status' => 0,
					'is_church_admin' => 1,
					'ban'         => 1, // Suspended until approved
					'reg_date'    => date('Y-m-d H:i:s')
				];
		
				$admin_id = $this->Crud->create('user', $admin_data);
		
				if ($admin_id > 0) {
					echo $this->Crud->msg('success', 'Admin registered successfully. Awaiting approval.');
					echo '<script>setTimeout(function(){ window.location.href = "' . site_url() . '"; }, 3000);</script>';
				
					// ✅ Notify System Admins
					$admins = $this->Crud->read_single('is_admin', 1, 'user');
					if (!empty($admins)) {
						foreach ($admins as $admin) {
							$admin_email = $admin->email;
							$admin_name  = $admin->fullname;
				
							// 📨 Send notification (basic mail, or call your Mail library/service)
							$subject = "New Church Admin Registration";
							$message = "Hello {$admin_name},<br><br>"
									 . "A new church admin has just registered:<br>"
									 . "<strong>Name:</strong> {$fullname}<br>"
									 . "<strong>Email:</strong> {$email}<br>"
									 . "<strong>Phone:</strong> {$phone}<br><br>"
									 . "Please review and approve via the admin dashboard.<br><br>"
									 . "Regards,<br>Your System";
							
									 
							// Replace with your actual mail sending logic
							$this->Crud->notify($admin_id, $admin->id, $message, 'admin_approval', $admin_id);
						}
					}
				}
				 else {
					echo $this->Crud->msg('danger', 'Registration failed. Try again later.');
				}
		
				die;
			}
		}
		
		
		$name = '';$ministry_id='';
		if(!empty($param1)){
			$name = $this->Crud->read_field('code', $param1, 'children_ministry', 'name');
			$ministry_id = $this->Crud->read_field('code', $param1, 'children_ministry', 'id');
		}
		$data['name'] = ucwords($name);
		$data['ministry_id'] = ($ministry_id);
		$data['title'] = 'Church Admin Register | '.app_name;
		return view('account/admin_register', $data);
	}

	public function parent_link($param1='', $param2='', $param3=''){
		
		if($param1 == 'submit'){
			if ($this->request->getMethod() == 'post') {
				helper(['form', 'url']);

				$fullname      = trim($this->request->getPost('fullname'));
				$email         = trim($this->request->getPost('emailDisplay'));
				$password      = $this->request->getPost('password');
				$register_type      = $this->request->getPost('register_type');
				$pin           = trim($this->request->getPost('pin'));
				$phone         = trim($this->request->getPost('phone'));
				$church_id     = (int)$this->request->getPost('church_id');
				$country_id    = (int)$this->request->getPost('country_id');
				$state_id      = (int)$this->request->getPost('state_id');
				$city_id       = (int)$this->request->getPost('city_id');
				$ministry_id       = (int)$this->request->getPost('ministry_id');
				$admin_id       = (int)$this->request->getPost('admin_id');
				$madmin_id       = (int)$this->request->getPost('madmin_id');
				$cadmin_id       = (int)$this->request->getPost('cadmin_id');
				$church_type = $this->Crud->read_field('id', $church_id, 'church', 'type');
				$regional_id = $this->Crud->read_field('id', $church_id, 'church', 'regional_id');
				$zonal_id = $this->Crud->read_field('id', $church_id, 'church', 'zonal_id');
				$group_id = $this->Crud->read_field('id', $church_id, 'church', 'group_id');
				$children      = $this->request->getPost('children');
				$children_age  = $this->request->getPost('children_age');


				$Error = '';
				if (empty($fullname)) $Error .= 'Fullname is required<br>';
				if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) $Error .= 'Valid email is required<br>';
				if (empty($pin)) $Error .= 'Parent\'s pin is required<br>';
				if (empty($church_id)) $Error .= 'Zone/Region is required<br>';
				if (empty($country_id) || empty($state_id) || empty($city_id)) $Error .= 'Complete location is required<br>';
				if (empty($children) || !is_array($children) || count(array_filter($children)) == 0) $Error .= 'At least one child name is required<br>';

				if (!empty($Error)) {
					echo $this->Crud->msg('danger', $Error);
					return;
				}

				
				// Check if email exists
				$existing_user = $this->Crud->read_single('email', $email, 'user');
				$user_data = [
					'fullname'      => $fullname,
					'pin'           => $pin,
					'phone'         => $phone,
					'church_id'     => $church_id,
					'church_type'   => $church_type,
					'regional_id'   => $regional_id,
					'zonal_id'      => $zonal_id,
					'group_id'      => $group_id,
					'admin_id'   	=> $admin_id,
					'madmin_id'   	=> $madmin_id,
					'cadmin_id'   	=> $cadmin_id,
					'ministry_id'   => $ministry_id,
					'country_id'    => $country_id,
					'state_id'      => $state_id,
					'city_id'       => $city_id,
					'profile_status'=> 1
				];
				

				// ✅ Add PCDL subscription if applicable
				if (strtolower($register_type) === 'pcdl') {
					$user_data['sub_id'] = 1;
					$user_data['start_date'] = date('Y-m-d');
					$user_data['end_date'] = date('Y-m-d', strtotime('+1 month'));
				}

				
					// print_r(json_encode($user_data));
					// die;
								
				if ($existing_user && count($existing_user) > 0) {
					$user = $existing_user[0];
					$user_id = $user->id;

					// Redirect if already submitted KYC
					if ((int)$user->profile_status === 1) {
						echo $this->Crud->msg('info', 'You have already Registered . Redirecting to events...');
						echo '<script>setTimeout(function(){ window.location.href = "https://pcdl4kids.com/events"; }, 2000);</script>';
						return;
					}

					// Password check before update
					if (!empty($password)) {
						if (md5($password) != $user->password) {
							echo $this->Crud->msg('danger', 'Incorrect password. You cannot update this profile.');
							return;
						}
					} else {
						echo $this->Crud->msg('danger', 'Password is required to update existing record.');
						return;
					}

					// ✅ Update user record
					$this->Crud->updates('id', $user_id, 'user', $user_data);

				} else {
					// Validate password for new users
					if (empty($password)) {
						echo $this->Crud->msg('danger', 'Password is required to create a new profile.');
						return;
					}

					// Extend user_data for new user registration
					$user_data['email']      = $email;
					$user_data['password']   = md5($password);
					$user_data['role_id']    = $this->Crud->read_field('name', 'User', 'access_role', 'id');
					$user_data['reg_date']   = date('Y-m-d H:i:s');

					// ✅ Create new user
					$user_id = $this->Crud->create('user', $user_data);
				}

				$parent_id = $user_id; // Use parent/user ID as stored in pl_child.parent_id

				if (!empty($children)) {
					foreach ($children as $index => $child_name) {
						$child_name = trim($child_name);
						if (!empty($child_name)) {
							$age_id = isset($children_age[$index]) ? (int)$children_age[$index] : 0;

							// Check if child already exists
							$exists = $this->Crud->check3('parent_id',$parent_id,'name',$child_name,
								'age_id', $age_id, 'child');

							if (!$exists) {
								$this->Crud->create('child', [
									'parent_id' => $parent_id,
									'name' => $child_name,
									'age_id' => $age_id,
									'reg_date' => date('Y-m-d H:i:s')
								]);
							}
						}
					}
				}


				echo $this->Crud->msg('success', 'Registration submitted successfully.');
				echo '<script>setTimeout(function(){ window.location.href = "https://pcdl4kids.com/events"; }, 2000);</script>';
				die;
			}
		}
		
		
		$name = '';
		$church_id = '';
		$admin_id = '';
		$church_code = '';
		$ministry_id = '';
		$madmin_id = '0';
		$cadmin_id = '0';
		$entity_type = ''; // 'church' or 'ministry'

		// Check if param1 has a dash
		if (!empty($param1) && strpos($param1, '-') !== false) {
			[$church_code, $admin_id] = explode('-', $param1);

			$name = $this->Crud->read_field('code', $church_code, 'church', 'name');
			$church_id = $this->Crud->read_field('code', $church_code, 'church', 'id');
			$ministry_id = $this->Crud->read_field('code', $church_code, 'church', 'ministry_id');
			$entity_type = 'church';
			$madmin_id = $this->Crud->read_field('id', $admin_id, 'user', 'madmin_id');
			if(empty($madmin_id))$madmin_id = $ministry_id;
			$cadmin_id = $admin_id;
		} else {
			// No dash — try church first
			$church_code = $param1;

			$name = $this->Crud->read_field('code', $church_code, 'church', 'name');
			$church_id = $this->Crud->read_field('code', $church_code, 'church', 'id');

			if (!empty($name)) {
				$ministry_id = $this->Crud->read_field('code', $church_code, 'church', 'ministry_id');
				$entity_type = 'church';
			} else {
				// Try as ministry code
				$name = $this->Crud->read_field('code', $church_code, 'children_ministry', 'name');
				$ministry_id = $this->Crud->read_field('code', $church_code, 'children_ministry', 'id');

				if (empty($name) || empty($ministry_id)) {
					// Invalid code — neither valid church nor ministry
					return redirect()->to(base_url('/'));
				}

				$madmin_id = $ministry_id;	

				$entity_type = 'ministry';
			}
		}

		// View Data
		$data = [
			'name' => $name,
			'church_id' => $church_id,
			'admin_id' => $admin_id,
			'madmin_id' => $madmin_id,
			'cadmin_id' => $cadmin_id,
			'church_code' => $church_code,
			'ministry_id' => $ministry_id,
			'entity_type' => $entity_type,
			'title' => 'Parent Register | '.app_name
		];


		
		return view('account/parent_register', $data);
	}


	private function send_email($email, $subject, $body, $name) {
		// $from = push_email;
		// $name = app_name;
		// $subhead = 'Notification';
		// $this->Crud->send_email($to, $from, $subject, $body, $name, $subhead);
		$em['from'] = 'PCDL4Kids <'.app_email.'>';
		$em['to'] = $name.' <'.$email.'>';
		$em['subject'] = $subject;
		$em['template'] = 'general';
		$em['t:variables'] = '{"name": "'.$name.'", "body": "'.$body.'"}';
		$this->Crud->mailgun($em);	
	}

	
}
