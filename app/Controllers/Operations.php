<?php

namespace App\Controllers;

class Operations extends BaseController
{
	private $db;

	public function __construct()
	{
		$this->db = \Config\Database::connect();
	}

	//// PARENTS
	public function services($param1 = '', $param2 = '', $param3 = '')
	{
		// check login
		$log_id = $this->session->get('plx_id');
		if (empty($log_id))
			return redirect()->to(site_url('auth'));

		$role_id = $this->Crud->read_field('id', $log_id, 'user', 'role_id');
		$role = strtolower($this->Crud->read_field('id', $role_id, 'access_role', 'name'));
		$role_c = $this->Crud->module($role_id, 'operations/services', 'create');
		$role_r = $this->Crud->module($role_id, 'operations/services', 'read');
		$role_u = $this->Crud->module($role_id, 'operations/services', 'update');
		$role_d = $this->Crud->module($role_id, 'operations/services', 'delete');
		if ($role_r == 0) {
			return redirect()->to(site_url('profile'));
		}

		$data['log_id'] = $log_id;
		$data['role'] = $role;
		$data['role_c'] = $role_c;

		$table = 'services';

		$form_link = site_url('operations/services/');
		if ($param1) {
			$form_link .= $param1 . '/';
		}
		if ($param2) {
			$form_link .= $param2 . '/';
		}
		if ($param3) {
			$form_link .= $param3 . '/';
		}

		// pass parameters to view
		$data['param1'] = $param1;
		$data['param2'] = $param2;
		$data['param3'] = $param3;
		$data['form_link'] = rtrim($form_link, '/');

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
						$del_id = $this->request->getVar('service_id');
						if ($this->Crud->deletes('id', $del_id, $table) > 0) {
							echo $this->Crud->msg('success', 'Service Deleted');
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
								$data['e_name'] = $e->name;
								$data['e_description'] = $e->description;
								$data['e_image'] = $e->image;
							}
						}
					}
				}

				if ($this->request->getMethod() == 'post') {
					$service_id = $this->request->getVar('service_id');
					$name = $this->request->getVar('name');
					$description = $this->request->getVar('description');
					$image = $this->request->getVar('img'); // hidden field for old image

					//// Image upload
					if ($this->request->getFile('pics') && $this->request->getFile('pics')->isValid()) {
						$path = 'assets/back/images/services/';
						$file = $this->request->getFile('pics');
						$getImg = $this->Crud->img_upload($path, $file);

						if (!empty($getImg->path)) {
							$image = $getImg->path;
						}
					}

					if (!$name || !$description) {
						echo $this->Crud->msg('warning', 'Service Name and Description are required');
						die;
					}

					$ins_data = [
						'name' => $name,
						'description' => $description,
						'image' => $image
					];

					// do create or update
					if ($service_id) {
						$upd_rec = $this->Crud->updates('id', $service_id, $table, $ins_data);
						if ($upd_rec > 0) {
							echo $this->Crud->msg('success', 'Service Updated');
							echo '<script>location.reload(false);</script>';
						} else {
							echo $this->Crud->msg('info', 'No Changes');
						}
					} else {
						$ins_rec = $this->Crud->create($table, $ins_data);
						if ($ins_rec > 0) {
							echo $this->Crud->msg('success', 'Service Created');
							echo '<script>location.reload(false);</script>';
						} else {
							echo $this->Crud->msg('danger', 'Please try later');
						}
					}

					die;
				}
			}
		}





		// record listing
		if ($param1 == 'load') {
			$offset = (int) $this->request->getPost('offset') ?? 0;
			$limit = (int) $this->request->getPost('limit') ?? 10;

			$builder = $this->db->table('services');
			$builder->select('id, name, description, image'); // select only needed fields
			$builder->orderBy('id', 'DESC');
			$builder->limit($limit, $offset);
			$query = $builder->get();

			$services = $query->getResult();

			$html = '';
			if ($services) {
				foreach ($services as $service) {
					$image = !empty($service->image)
						? '<img src="' . site_url($service->image) . '" width="60" height="60" class="rounded"/>'
						: '<span class="text-muted">No Image</span>';

					$html .= '
                <tr>
                    <td width="100px">' . $image . '</td>
                    <td>' . esc($service->name) . '</td>
                    <td>' . esc($service->description) . '</td>
                    <td>
                        <a href="javascript:;" 
                           class="btn btn-sm btn-info m-1 pop"
                           pageTitle="Edit Service"
                           pageName="' . base_url('operations/services/manage/edit/' . $service->id) . '" 
                           pageSize="modal-md">
                            <i class="anticon anticon-edit"></i>
                        </a>
                        <a href="javascript:;" 
                           class="btn btn-sm btn-danger m-1 pop"  pageTitle="Delete Service"
                           pageName="' . base_url('operations/services/manage/delete/' . $service->id) . '" 
                           data-id="' . $service->id . '">
                            <i class="anticon anticon-delete"></i>
                        </a>
                    </td>
                </tr>';
				}
			}

			// Count total services to know if "Load More" should be shown
			$total = $this->db->table('services')->countAllResults();
			$hasMore = ($offset + $limit) < $total;

			return $this->response->setJSON([
				'item' => $html ?: '<tr><td colspan="4" class="text-center text-muted">No services found</td></tr>',
				'hasMore' => $hasMore
			]);
		}





		if ($param1 == 'manage' || $param1 == 'history') { // view for form data posting
			return view('operations/services_form', $data);
		} else { // view for main page
			$data['title'] = 'Services - ' . app_name;
			$data['page_active'] = 'operations/services';
			return view('operations/services', $data);
		}
	}


	public function appointment($param1 = '', $param2 = '', $param3 = '')
	{
		// check login
		$log_id = $this->session->get('plx_id');
		if (empty($log_id))
			return redirect()->to(site_url('auth'));

		$role_id = $this->Crud->read_field('id', $log_id, 'user', 'role_id');
		$role = strtolower($this->Crud->read_field('id', $role_id, 'access_role', 'name'));
		$role_c = $this->Crud->module($role_id, 'operations/appointment', 'create');
		$role_r = $this->Crud->module($role_id, 'operations/appointment', 'read');
		$role_u = $this->Crud->module($role_id, 'operations/appointment', 'update');
		$role_d = $this->Crud->module($role_id, 'operations/appointment', 'delete');
		if ($role_r == 0) {
			return redirect()->to(site_url('profile'));
		}

		$data['log_id'] = $log_id;
		$data['role'] = $role;
		$data['role_c'] = $role_c;

		$table = 'appointments';

		$form_link = site_url('operations/appointment/');
		if ($param1) {
			$form_link .= $param1 . '/';
		}
		if ($param2) {
			$form_link .= $param2 . '/';
		}
		if ($param3) {
			$form_link .= $param3 . '/';
		}

		// pass parameters to view
		$data['param1'] = $param1;
		$data['param2'] = $param2;
		$data['param3'] = $param3;
		$data['form_link'] = rtrim($form_link, '/');

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
						$del_id = $this->request->getVar('service_id');
						if ($this->Crud->deletes('id', $del_id, $table) > 0) {
							echo $this->Crud->msg('success', 'Service Deleted');
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
						$edit = $this->Crud->read_single('id', $param3, 'sb_appointments');
						if (!empty($edit)) {
							foreach ($edit as $e) {
								$data['e_id']        = $e->id;
								$data['e_status']    = $e->status;
								$data['e_notes']     = $e->notes;
								$data['e_date']      = $e->appointment_date;
								$data['e_time']      = $e->appointment_time;
								$data['e_user_id']   = $e->user_id;
								$data['e_service_id']= $e->service_id;
								$data['e_stylist_id']= $e->stylist_id;
							}
			
							// get related names
							$data['e_customer_name'] = $this->Crud->read_field('id', $data['e_user_id'], 'sb_user', 'fullname');
							$data['e_service_name']  = $this->Crud->read_field('id', $data['e_service_id'], 'sb_services', 'name');
							$data['e_stylist_name']  = $this->Crud->read_field('id', $data['e_stylist_id'], 'sb_user', 'fullname');
						}
					}
				}
			
				// prepare for view
				if ($param2 == 'view') {
					if ($param3) {
						$view = $this->Crud->read_single('id', $param3, 'sb_appointments');
						if (!empty($view)) {
							foreach ($view as $v) {
								$data['e_id']        = $v->id;
								$data['e_status']    = $v->status;
								$data['e_notes']     = $v->notes;
								$data['e_date']      = $v->appointment_date;
								$data['e_time']      = $v->appointment_time;
								$data['e_user_id']   = $v->user_id;
								$data['e_service_id']= $v->service_id;
								$data['e_stylist_id']= $v->stylist_id;
							}
			
							// get related names
							$data['e_customer_name'] = $this->Crud->read_field('id', $data['e_user_id'], 'sb_user', 'fullname');
							$data['e_service_name']  = $this->Crud->read_field('id', $data['e_service_id'], 'sb_services', 'name');
							$data['e_stylist_name']  = $this->Crud->read_field('id', $data['e_stylist_id'], 'sb_user', 'fullname');
						}
					}
				}
			
				// handle create/update (POST request)
				if ($this->request->getMethod() == 'post') {
					$appointment_id = $this->request->getVar('appointment_id');
					$status         = $this->request->getVar('status');
					
					$ins_data = [
						'status' => $status,
					];
			
					if ($appointment_id) {
						$upd_rec = $this->Crud->updates('id', $appointment_id, 'sb_appointments', $ins_data);
					
						if ($upd_rec > 0) {
							// ✅ Get user email
							$user_id = $this->Crud->read_field('id', $appointment_id, 'sb_appointments', 'user_id');
							$user_email = $this->Crud->read_field('id', $user_id, 'sb_user', 'email');
							$user_name  = $this->Crud->read_field('id', $user_id, 'sb_user', 'fullname');
					
							// ✅ Prepare email
							$subject = "Your Appointment Update";
							$message = "
								<h3>Hello {$user_name},</h3>
								<p>Your appointment has been updated.</p>
								<p><b>Status:</b> " . ucfirst($ins_data['status']) . "</p>
								<p><b>Notes:</b> " . nl2br(esc($ins_data['notes'] ?? 'N/A')) . "</p>
								<br>
								<p>Thank you,<br><b>" . app_name . " Team</b></p>
							";
					
							// ✅ Send email (using your Crud helper)
							$this->Crud->send_email($user_email, $subject, $message);
					
							echo $this->Crud->msg('success', 'Appointment Updated & Email Sent');
							echo '<script>location.reload(false);</script>';
						} else {
							echo $this->Crud->msg('info', 'No Changes');
						}
					}
					
			
					die;
				}
			}
			
		}


		// record listing
		if ($param1 == 'load') {
			$offset = (int) ($this->request->getPost('offset') ?? 0);
			$limit  = (int) ($this->request->getPost('limit') ?? 10);
		
			// ✅ Get filters from POST
			$status = trim($this->request->getPost('status') ?? 'all');
			$date   = trim($this->request->getPost('date') ?? '');
		
			// ✅ Use Crud model filter function
			$appointments = $this->Crud->filter_appointment($status, $date, $limit, $offset);
		
			$html = '';
			if ($appointments) {
				foreach ($appointments as $appointment) {
					// ✅ Status badge
					$statusBadge = '<span class="badge bg-secondary">'.esc(ucfirst($appointment->status)).'</span>';
					if ($appointment->status == 'confirmed') {
						$statusBadge = '<span class="badge bg-success">Confirmed</span>';
					} elseif ($appointment->status == 'pending') {
						$statusBadge = '<span class="badge bg-warning text-dark">Pending</span>';
					} elseif ($appointment->status == 'cancelled') {
						$statusBadge = '<span class="badge bg-danger">Cancelled</span>';
					} elseif ($appointment->status == 'completed') {
						$statusBadge = '<span class="badge bg-info">Completed</span>';
					}
			
					// ✅ Build row
					$html .= '
					<tr>
						<td>
							<i class="anticon anticon-calendar"></i> 
							' . date("d M Y", strtotime($appointment->appointment_date)) . '<br>
							<small class="text-muted"><i class="anticon anticon-clock-circle"></i> ' . date("h:i A", strtotime($appointment->appointment_time)) . '</small>
						</td>
						<td>' . esc($appointment->customer_name ?? 'N/A') . '</td>
						<td>' . esc($appointment->service_name ?? 'N/A') . '</td>
						<td>' . $statusBadge . '</td>
						<td class="text-center">
							<div class="d-flex justify-content-center gap-2">
								<a href="javascript:;" 
								class="btn btn-sm btn-info pop mx-1"
								pageTitle="Edit Appointment"
								pageName="' . base_url('operations/appointment/manage/edit/' . $appointment->id) . '" 
								pageSize="modal-md">
									<i class="anticon anticon-edit"></i>
								</a>
								<a href="javascript:;" 
								class="btn btn-sm btn-danger pop mx-1"
								pageTitle="Delete Appointment"
								pageName="' . base_url('operations/appointment/manage/delete/' . $appointment->id) . '" 
								data-id="' . $appointment->id . '">
									<i class="anticon anticon-delete"></i>
								</a>
								<a href="javascript:;" 
								class="btn btn-sm btn-success pop mx-1"
								pageTitle="View Appointment"
								pageName="' . base_url('operations/appointment/manage/view/' . $appointment->id) . '" 
								data-id="' . $appointment->id . '">
									<i class="anticon anticon-eye"></i>
								</a>
							</div>
						</td>

					</tr>';
				}
			}
			
		
			// ✅ Count total with same filters
			$total   = $this->Crud->filter_appointment($status, $date, 0, 0, true);
			$hasMore = ($offset + $limit) < $total;
		
			return $this->response->setJSON([
				'item'   => $html ?: '<tr><td colspan="6" class="text-center text-muted">No appointments found</td></tr>',
				'hasMore'=> $hasMore
			]);
		}
		
		
		if ($param1 == 'manage' || $param1 == 'history') { 
			return view('operations/appointment_form', $data);
		} else { 
			$data['title'] = 'Appointments - ' . app_name;
			$data['page_active'] = 'operations/appointment';
			return view('operations/appointment', $data);
		}
		
	}

	public function getSlots()
	{
		$date = $this->request->getPost('date'); // Y-m-d
		$dayName = strtolower(date('l', strtotime($date))); // e.g. monday

		// 1. Get working hours from settings
		$setting = $this->db->table('sb_setting')->where('name', 'working_hours')->get()->getRow();
		$hours = json_decode($setting->value, true);

		$dayHours = $hours[$dayName] ?? []; // e.g. ["09:00 AM", "05:00 PM"]

		$daySlots = [];

		// 2. Expand into 30-min intervals
		if (count($dayHours) >= 2) {
			$start = new \DateTime($dayHours[0]);
			$end = new \DateTime($dayHours[1]);

			while ($start < $end) {
				$daySlots[] = $start->format('h:i A');
				$start->modify('+30 minutes');
			}
		}

		// 3. Get booked appointments for this date
		$booked = $this->db->table('sb_appointments')
			->select('appointment_time')
			->where('appointment_date', $date)
			->get()->getResultArray();

		$bookedTimes = array_column($booked, 'appointment_time');

		// 4. Filter available slots
		$available = array_diff($daySlots, $bookedTimes);

		return $this->response->setJSON([
			'slots' => array_values($available)
		]);
	}



	public function getAvailability()
	{
		// Get working hours
		$setting = $this->db->table('sb_setting')->where('name', 'working_hours')->get()->getRow();
		$hours = json_decode($setting->value, true);

		$slotsPerDay = [];
		foreach ($hours as $day => $slots) {
			$slotsPerDay[$day] = count($slots);
		}

		// Count booked per appointment_date
		$appointments = $this->db->table('sb_appointments')
			->select('appointment_date, COUNT(*) as booked')
			->groupBy('appointment_date')
			->get()->getResultArray();

		$fullyBooked = [];
		$partiallyBooked = [];

		foreach ($appointments as $a) {
			$dayName = strtolower(date('l', strtotime($a['appointment_date'])));
			$totalSlots = $slotsPerDay[$dayName] ?? 0;

			if ($totalSlots > 0) {
				if ($a['booked'] >= $totalSlots) {
					$fullyBooked[] = $a['appointment_date'];
				} else {
					$partiallyBooked[] = $a['appointment_date'];
				}
			}
		}

		return $this->response->setJSON([
			'fullyBooked' => $fullyBooked,
			'partiallyBooked' => $partiallyBooked
		]);
	}



	/////// ACTIVITIES
	public function activity($param1 = '', $param2 = '', $param3 = '')
	{
		// check session login
		if ($this->session->get('plx_id') == '') {
			$request_uri = uri_string();
			$this->session->set('fls_redirect', $request_uri);
			return redirect()->to(site_url('auth'));
		}

		$mod = 'tools/activity';

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

		$table = 'activity';

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


		// record listing
		if ($param1 == 'load') {
			$limit = $param2;
			$offset = $param3;

			$count = 0;
			$rec_limit = 50;
			$item = '';

			if ($limit == '') {
				$limit = $rec_limit;
			}
			if ($offset == '') {
				$offset = 0;
			}

			$search = $this->request->getVar('search');
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

			if (!$log_id) {
				$item = '<div class="text-center text-muted">Session Timeout! - Please login again</div>';
			} else {
				$query = $this->Crud->filter_activity($limit, $offset, $log_id, $search, $start_date, $end_date);
				$all_rec = $this->Crud->filter_activity('', '', $log_id, $search, $start_date, $end_date);
				if (!empty($all_rec)) {
					$count = count($all_rec);
				} else {
					$count = 0;
				}

				if (!empty($query)) {
					foreach ($query as $q) {
						$id = $q->id;
						$type = $q->item;
						$type_id = $q->item_id;
						$action = $q->action;
						$reg_date = date('M d, Y h:i A', strtotime($q->reg_date));

						$timespan = $this->Crud->timespan(strtotime($q->reg_date));

						$icon = 'solution';
						if ($type == 'authentication')
							$icon = 'lock';
						if ($type == 'setup')
							$icon = 'tool';
						if ($type == 'account')
							$icon = 'team';
						if ($type == 'tools')
							$icon = 'team';
						if ($type == 'coupon')
							$icon = 'reconciliation';

						$item .= '
							<li class="list-group-item">
								<div class="row p-t-10 align-items-center">
									<div class="col-1 text-center">
										<i class="anticon anticon-' . $icon . ' text-muted" style="font-size:50px;"></i>
									</div>
									<div class="col-11">
										' . $action . ' <small>on ' . $reg_date . '</small>
										<div class="text-muted small text-right">' . $timespan . '</div>
									</div>
								</div>
							</li>
						';
					}
				}
			}
			if (empty($item)) {
				$resp['item'] = '
					<div class="text-center text-muted">
						<br/><br/><br/><br/>
						<i class="anticon anticon-solution" style="font-size:150px;"></i><br/><br/>No Activities Returned
					</div>
				';
			} else {
				$resp['item'] = $item;
			}

			$more_record = $count - ($offset + $rec_limit);
			$resp['left'] = $more_record;

			if ($count > ($offset + $rec_limit)) { // for load more records
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
			return view($mod . '_form', $data);
		} else { // view for main page

			$data['title'] = 'Activity | ' . app_name;
			$data['page_active'] = $mod;

			return view($mod, $data);
		}

	}

	//// ANNOUNCEMENT
	public function announcement($param1 = '', $param2 = '', $param3 = '')
	{
		// check login
		$log_id = $this->session->get('plx_id');
		if (empty($log_id))
			return redirect()->to(site_url('auth'));

		$role_id = $this->Crud->read_field('id', $log_id, 'user', 'role_id');
		$role = strtolower($this->Crud->read_field('id', $role_id, 'access_role', 'name'));
		$role_c = $this->Crud->module($role_id, 'tools/announcement', 'create');
		$role_r = $this->Crud->module($role_id, 'tools/announcement', 'read');
		$role_u = $this->Crud->module($role_id, 'tools/announcement', 'update');
		$role_d = $this->Crud->module($role_id, 'tools/announcement', 'delete');
		if ($role_r == 0) {
			return redirect()->to(site_url('profile'));
		}

		$data['log_id'] = $log_id;
		$data['role'] = $role;
		$data['role_c'] = $role_c;

		$table = 'announcement';

		$form_link = site_url('tools/announcement/');
		if ($param1) {
			$form_link .= $param1 . '/';
		}
		if ($param2) {
			$form_link .= $param2 . '/';
		}
		if ($param3) {
			$form_link .= $param3 . '/';
		}

		// pass parameters to view
		$data['param1'] = $param1;
		$data['param2'] = $param2;
		$data['param3'] = $param3;
		$data['form_link'] = rtrim($form_link, '/');

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
						$del_id = $this->request->getVar('d_announcement_id');
						if ($this->Crud->deletes('id', $del_id, $table) > 0) {
							echo $this->Crud->msg('success', 'Record Deleted');
							echo '<script>location.reload(false);</script>';
						} else {
							echo $this->Crud->msg('danger', 'Please try later');
						}
						exit;
					}
				}
			} elseif ($param2 == 'email') {
				if ($param3) {
					$edit = $this->Crud->read_single('id', $param3, $table);
					if (!empty($edit)) {
						foreach ($edit as $e) {
							$data['d_id'] = $e->id;
						}
					}

					if ($this->request->getMethod() == 'post') {
						$del_id = $this->request->getVar('d_announcement_id');
						$parent = json_decode($this->Crud->read_field('id', $del_id, $table, 'to_id'), true);
						// print_r($parent);
						$title = $this->Crud->read_field('id', $del_id, $table, 'title');
						$content = $this->Crud->read_field('id', $del_id, $table, 'content');
						$sent = 0;
						$failed = 0;

						if (!empty($parent)) {
							foreach ($parent as $par) {
								// echo $par.' ';
								$email = $this->Crud->read_field('id', $par, 'user', 'email');
								$email_body = [
									'from' => 'admin<noreply@mg.pcdl4kids.com>', // Replace with your domain's default sender
									'to' => $email, // Assuming $par->email contains the recipient's email address
									'subject' => 'Important Notification', // Replace with an appropriate subject
									'html' => $content // Use 'html' or 'text' based on your content type
								];
								if ($this->Crud->mailgun($email_body) > 0) {
									$sent++;
								} else {
									$failed++;
								}
							}
							if ($failed == count($parent)) {
								echo $this->Crud->msg('info', 'Email Failed to Send');
							} else {
								echo $this->Crud->msg('success', $sent . ' Email sent. <br>' . $failed . ' Failed to send.');
							}
						} else {
							echo $this->Crud->msg('danger', 'No Parent Found');
						}
						// if($this->Crud->deletes('id', $del_id, $table) > 0) {
						// 	echo $this->Crud->msg('success', 'Record Deleted');
						// 	echo '<script>location.reload(false);</script>';
						// } else {
						// 	echo $this->Crud->msg('danger', 'Please try later');
						// }
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
								$data['e_to_id'] = $e->to_id;
								$data['e_role_id'] = $e->role_id;
								$data['e_from_id'] = $e->from_id;
								$data['e_title'] = $e->title;
								$data['e_type'] = $e->type;
								$data['e_content'] = $e->content;
							}
						}
					}
				}

				if ($param2 == 'view') {
					if ($param3) {
						$edit = $this->Crud->read_single('id', $param3, $table);
						if (!empty($edit)) {
							foreach ($edit as $e) {
								$data['e_id'] = $e->id;
								$data['e_role_id'] = $e->role_id;
								$data['e_to_id'] = $e->to_id;
								$data['e_from_id'] = $e->from_id;
								$data['e_title'] = $e->title;
								$data['e_type'] = $e->type;
								$data['e_content'] = $e->content;
								$data['e_reg_date'] = date('M d, Y h:i A', strtotime($e->reg_date));
							}
						}
					}
				}

				if ($this->request->getMethod() == 'post') {
					$announcement_id = $this->request->getVar('announcement_id');
					$title = $this->request->getVar('title');
					$content = $this->request->getVar('content');
					$type = $this->request->getVar('type');
					$parent = $this->request->getVar('parent');

					if (!empty($parent) && $type == 1) {
						$p = json_encode($parent);
						$ins_data['to_id'] = $p;

					} else {
						$parent = array();
						$pa = $this->Crud->read_single('role_id', 3, 'user');
						foreach ($pa as $par) {
							$parent[] = $par->id;
						}
						$p = json_encode($parent);
						$ins_data['to_id'] = $p;
					}


					$ins_data['title'] = $title;
					$ins_data['type'] = $type;
					$ins_data['role_id'] = 3;
					$ins_data['content'] = $content;

					// do create or update
					if ($announcement_id) {
						$upd_rec = $this->Crud->updates('id', $announcement_id, $table, $ins_data);
						if ($upd_rec > 0) {
							foreach (json_decode($p) as $re => $val) {
								$in_data['from_id'] = $log_id;
								$in_data['to_id'] = $val;
								$in_data['content'] = $title;
								$in_data['item'] = 'announcement';
								$in_data['new'] = 1;
								$in_data['reg_date'] = date(fdate);
								$in_data['item_id'] = $announcement_id;
								$this->Crud->create('notify', $in_data);
							}
							///// store activities
							$code = $this->Crud->read_field('id', $announcement_id, $table, 'title');
							$by = $this->Crud->read_field('id', $log_id, 'user', 'fullname');
							$action = $by . ' updated Announcement ' . $code . ' Record';
							$this->Crud->activity('tools', $announcement_id, $action);

							echo $this->Crud->msg('success', 'Announcement Updated');
							echo '<script>location.reload(false);</script>';
						} else {
							echo $this->Crud->msg('info', 'No Changes');
						}
					} else {
						if ($this->Crud->check('id', $announcement_id, $table) > 0) {
							echo $this->Crud->msg('warning', 'Record Already Exist');
						} else {
							$ins_data['reg_date'] = date(fdate);
							$ins_data['from_id'] = $log_id;
							$ins_rec = $this->Crud->create($table, $ins_data);
							if ($ins_rec > 0) {
								echo $this->Crud->msg('success', 'Announcement Created');
								foreach (json_decode($p) as $re => $val) {
									$in_data['from_id'] = $log_id;
									$in_data['to_id'] = $val;
									$in_data['content'] = $content;
									$in_data['item'] = 'announcement';
									$in_data['new'] = 1;
									$in_data['reg_date'] = date(fdate);
									$in_data['item_id'] = $ins_rec;
									$this->Crud->create('notify', $in_data);
								}
								///// store activities
								$by = $this->Crud->read_field('id', $log_id, 'user', 'fullname');
								$code = $this->Crud->read_field('id', $ins_rec, 'announcement', 'title');
								$action = $by . ' created (' . $code . ') Announcement ';
								$this->Crud->activity('announcement', $ins_rec, $action);
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

			$rec_limit = 25;
			$item = '';
			$counts = 0;

			if (empty($limit)) {
				$limit = $rec_limit;
			}
			if (empty($offset)) {
				$offset = 0;
			}

			$search = $this->request->getPost('search');
			$log_id = $this->session->get('plx_id');
			if (!$log_id) {
				$item = '<div class="text-center text-muted">Session Timeout! - Please login again</div>';
			} else {
				$query = $this->Crud->filter_announcement($limit, $offset, $log_id, $search);
				$all_rec = $this->Crud->filter_announcement('', '', $log_id, $search);
				if (!empty($all_rec)) {
					$count = count($all_rec);
				} else {
					$count = 0;
				}

				if (!empty($query)) {
					foreach ($query as $q) {
						$id = $q->id;
						$title = $q->title;
						$content = $q->content;
						$to_id = $q->to_id;
						$team = $q->role_id;
						$type = $q->type;
						$user_i = $q->from_id;

						$reg_date = date('M d, Y h:i A', strtotime($q->reg_date));
						$user = $this->Crud->read_field('id', $user_i, 'user', 'fullname');

						$teams = '';
						if ($role == 'developer' || $role == 'administrator' || $user_i == $log_id) {
							if ($type == 0) {
								$teams .= '<span class="badge badge-pill badge-green mb-1">' . strtoupper('All parents') . '</span>';

							} else {
								$teams .= '<span class="badge badge-pill badge-green mb-1">' . strtoupper('Selected parents') . '</span>';

							}
						}
						// add manage buttons
						if ($role_u != 1) {
							$all_btn = '';
						} else {
							$all_btn = '
                                    <a href="javascript:;" class="text-primary pop mx-2" pageTitle="Manage ' . $title . '" pageName="' . base_url('tools/announcement/manage/edit/' . $id) . '" pageSize="modal-lg">
                                        <i class="anticon anticon-edit"></i> Edit
                                    </a> 
                                    <a href="javascript:;" class="text-success pop mx-2" pageTitle="View ' . $title . '" pageName="' . base_url('tools/announcement/manage/view/' . $id) . '" pageSize="modal-lg">
                                        <i class="anticon anticon-eye"></i> View
                                    </a>  
                                    <a href="javascript:;" class="text-info pop mx-2" pageTitle="View ' . $title . '" pageName="' . base_url('tools/announcement/manage/email/' . $id) . '" pageSize="modal-lg"><i class="anticon anticon-share-alt"></i>
                                        Send to Email
                                    </a>
                                    
                            ';


						}

						if ($role == 'developer' || $role == 'administrator') {
							$item .= '
                                <tr>
                                    <td>
                                        <div class="media align-items-center">
                                            <div class="m-l-10">
                                                <span class="text-muted small">' . $reg_date . '</span><br>
                                                <h5 class="m-b-0">' . ucwords($title) . '</h5>
                                            </div>
                                        </div>
                                    </td>
                                    <td >
                                        <div class="d-flex align-items-center">
                                            <div>
                                                ' . $teams . '
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            ' . $user . '
                                        </div>
                                    </td>
                                    <td  class="text-right">
                                        ' . $all_btn . '
                                    </td>
                                </tr>
                                
                            ';
						} else {
							if ($user_i == $log_id) {
								$item .= '
                                    <tr>
                                        <td>
                                            <div class="media align-items-center">
                                                <div class="m-l-10">
                                                    <span class="text-muted small">' . $reg_date . '</span><br>
                                                    <h5 class="m-b-0">' . ucwords($title) . '</h5>
                                                </div>
                                            </div>
                                        </td>
                                        <td >
                                            <div class="d-flex align-items-center">
                                                <div>
                                                    ' . $teams . '
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                ' . $user . '
                                            </div>
                                        </td>
                                        <td  class="text-right">
                                            ' . $all_btn . '
                                        </td>
                                    </tr>
                                    
                                ';
							} else {
								if (!empty($team)) {
									if (in_array($log_id, json_decode($to_id), true)) {
										$item .= '
                                            <tr>
                                                <td>
                                                    <div class="media align-items-center">
                                                        <div class="m-l-10">
                                                            <span class="text-muted small">' . $reg_date . '</span><br>
                                                            <h5 class="m-b-0">' . ucwords($title) . '</h5>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td >
                                                    <div class="d-flex align-items-center">
                                                        <div>
                                                            ' . $teams . '
                                                        </div>
                                                    </div>
                                                </td>
                                                <td>
                                                    <div class="d-flex align-items-center">
                                                        ' . $user . '
                                                    </div>
                                                </td>
                                                <td class="text-right">
                                                    ' . $all_btn . '
                                                </td>
                                            </tr>
                                            
                                        ';
									}
								}
							}
						}

					}
				}
			}
			if (empty($item)) {
				$resp['item'] = '
					<div class="text-center text-muted col-sm-12">
						<br/><br/><br/><br/>
						<i class="anticon anticon-notification" style="font-size:120px;"></i><br/><br/>No Announcements Returned
					</div>
				';
			} else {
				$resp['item'] = $item;
			}

			$more_record = $count - ($offset + $rec_limit);
			$resp['left'] = $more_record;

			if ($count > ($offset + $rec_limit)) { // for load more records
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
			return view('tools/announcement_form', $data);
		} else { // view for main page
			$data['title'] = 'Announcement | ' . app_name;
			$data['page_active'] = 'tools/announcement';
			return view('tools/announcement', $data);
		}
	}

	public function watch($param1 = '', $param2 = '', $param3 = '')
	{
		// check login
		$log_id = $this->session->get('plx_id');
		if (empty($log_id))
			return redirect()->to(site_url('auth'));

		$role_id = $this->Crud->read_field('id', $log_id, 'user', 'role_id');
		$role = strtolower($this->Crud->read_field('id', $role_id, 'access_role', 'name'));
		$role_c = $this->Crud->module($role_id, 'tools/watch', 'create');
		$role_r = $this->Crud->module($role_id, 'tools/watch', 'read');
		$role_u = $this->Crud->module($role_id, 'tools/watch', 'update');
		$role_d = $this->Crud->module($role_id, 'tools/watch', 'delete');
		if ($role_r == 0) {
			return redirect()->to(site_url('profile'));
		}

		$data['log_id'] = $log_id;
		$data['role'] = $role;
		$data['role_c'] = $role_c;

		$table = 'watch_history';

		$ministry_id = $this->Crud->read_field('id', $log_id, 'user', 'madmin_id');
		$church_id = $this->Crud->read_field('id', $log_id, 'user', 'cadmin_id');

		$form_link = site_url('tools/watch/');
		if ($param1) {
			$form_link .= $param1 . '/';
		}
		if ($param2) {
			$form_link .= $param2 . '/';
		}
		if ($param3) {
			$form_link .= $param3 . '/';
		}

		// pass parameters to view
		$data['param1'] = $param1;
		$data['param2'] = $param2;
		$data['param3'] = $param3;
		$data['form_link'] = rtrim($form_link, '/');

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
						$del_id = $this->request->getVar('d_announcement_id');
						if ($this->Crud->deletes('id', $del_id, $table) > 0) {
							echo $this->Crud->msg('success', 'Record Deleted');
							echo '<script>location.reload(false);</script>';
						} else {
							echo $this->Crud->msg('danger', 'Please try later');
						}
						exit;
					}
				}
			} elseif ($param2 == 'email') {
				if ($param3) {
					$edit = $this->Crud->read_single('id', $param3, $table);
					if (!empty($edit)) {
						foreach ($edit as $e) {
							$data['d_id'] = $e->id;
						}
					}

					if ($this->request->getMethod() == 'post') {
						$del_id = $this->request->getVar('d_announcement_id');
						$parent = json_decode($this->Crud->read_field('id', $del_id, $table, 'to_id'), true);
						// print_r($parent);
						$title = $this->Crud->read_field('id', $del_id, $table, 'title');
						$content = $this->Crud->read_field('id', $del_id, $table, 'content');
						$sent = 0;
						$failed = 0;

						if (!empty($parent)) {
							foreach ($parent as $par) {
								// echo $par.' ';
								$email = $this->Crud->read_field('id', $par, 'user', 'email');
								$email_body = [
									'from' => 'admin<noreply@mg.pcdl4kids.com>', // Replace with your domain's default sender
									'to' => $email, // Assuming $par->email contains the recipient's email address
									'subject' => 'Important Notification', // Replace with an appropriate subject
									'html' => $content // Use 'html' or 'text' based on your content type
								];
								if ($this->Crud->mailgun($email_body) > 0) {
									$sent++;
								} else {
									$failed++;
								}
							}
							if ($failed == count($parent)) {
								echo $this->Crud->msg('info', 'Email Failed to Send');
							} else {
								echo $this->Crud->msg('success', $sent . ' Email sent. <br>' . $failed . ' Failed to send.');
							}
						} else {
							echo $this->Crud->msg('danger', 'No Parent Found');
						}
						// if($this->Crud->deletes('id', $del_id, $table) > 0) {
						// 	echo $this->Crud->msg('success', 'Record Deleted');
						// 	echo '<script>location.reload(false);</script>';
						// } else {
						// 	echo $this->Crud->msg('danger', 'Please try later');
						// }
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
								$data['e_to_id'] = $e->to_id;
								$data['e_role_id'] = $e->role_id;
								$data['e_from_id'] = $e->from_id;
								$data['e_title'] = $e->title;
								$data['e_type'] = $e->type;
								$data['e_content'] = $e->content;
							}
						}
					}
				}

				if ($param2 == 'view') {
					if ($param3) {
						$edit = $this->Crud->read_single('id', $param3, $table);
						if (!empty($edit)) {
							foreach ($edit as $e) {
								$data['e_id'] = $e->id;
								$data['e_role_id'] = $e->role_id;
								$data['e_to_id'] = $e->to_id;
								$data['e_from_id'] = $e->from_id;
								$data['e_title'] = $e->title;
								$data['e_type'] = $e->type;
								$data['e_content'] = $e->content;
								$data['e_reg_date'] = date('M d, Y h:i A', strtotime($e->reg_date));
							}
						}
					}
				}

				if ($this->request->getMethod() == 'post') {
					$announcement_id = $this->request->getVar('announcement_id');
					$title = $this->request->getVar('title');
					$content = $this->request->getVar('content');
					$type = $this->request->getVar('type');
					$parent = $this->request->getVar('parent');

					if (!empty($parent) && $type == 1) {
						$p = json_encode($parent);
						$ins_data['to_id'] = $p;

					} else {
						$parent = array();
						$pa = $this->Crud->read_single('role_id', 3, 'user');
						foreach ($pa as $par) {
							$parent[] = $par->id;
						}
						$p = json_encode($parent);
						$ins_data['to_id'] = $p;
					}


					$ins_data['title'] = $title;
					$ins_data['type'] = $type;
					$ins_data['role_id'] = 3;
					$ins_data['content'] = $content;

					// do create or update
					if ($announcement_id) {
						$upd_rec = $this->Crud->updates('id', $announcement_id, $table, $ins_data);
						if ($upd_rec > 0) {
							foreach (json_decode($p) as $re => $val) {
								$in_data['from_id'] = $log_id;
								$in_data['to_id'] = $val;
								$in_data['content'] = $title;
								$in_data['item'] = 'announcement';
								$in_data['new'] = 1;
								$in_data['reg_date'] = date(fdate);
								$in_data['item_id'] = $announcement_id;
								$this->Crud->create('notify', $in_data);
							}
							///// store activities
							$code = $this->Crud->read_field('id', $announcement_id, $table, 'title');
							$by = $this->Crud->read_field('id', $log_id, 'user', 'fullname');
							$action = $by . ' updated Announcement ' . $code . ' Record';
							$this->Crud->activity('tools', $announcement_id, $action);

							echo $this->Crud->msg('success', 'Announcement Updated');
							echo '<script>location.reload(false);</script>';
						} else {
							echo $this->Crud->msg('info', 'No Changes');
						}
					} else {
						if ($this->Crud->check('id', $announcement_id, $table) > 0) {
							echo $this->Crud->msg('warning', 'Record Already Exist');
						} else {
							$ins_data['reg_date'] = date(fdate);
							$ins_data['from_id'] = $log_id;
							$ins_rec = $this->Crud->create($table, $ins_data);
							if ($ins_rec > 0) {
								echo $this->Crud->msg('success', 'Announcement Created');
								foreach (json_decode($p) as $re => $val) {
									$in_data['from_id'] = $log_id;
									$in_data['to_id'] = $val;
									$in_data['content'] = $content;
									$in_data['item'] = 'announcement';
									$in_data['new'] = 1;
									$in_data['reg_date'] = date(fdate);
									$in_data['item_id'] = $ins_rec;
									$this->Crud->create('notify', $in_data);
								}
								///// store activities
								$by = $this->Crud->read_field('id', $log_id, 'user', 'fullname');
								$code = $this->Crud->read_field('id', $ins_rec, 'announcement', 'title');
								$action = $by . ' created (' . $code . ') Announcement ';
								$this->Crud->activity('announcement', $ins_rec, $action);
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

		if ($param1 == 'analytic') {
			if ($param2 == 'video_analytics') {
				$range = $_POST['range'] ?? 'weekly';
				$start_date = $_POST['start_date'] ?? null;
				$end_date = $_POST['end_date'] ?? null;

				// Get date range
				$where = $this->buildDateFilter($range, $start_date, $end_date);
				$start = $where['start'];
				$end = $where['end'];

				// Get logged-in user scope
				$logged_user_id = $this->session->get('plx_id') ?? null;
				$logged_church_id = $church_id ?? null;
				$logged_ministry_id = $ministry_id ?? null;
				$is_super_admin = in_array($role, ['administrator', 'developer']);

				// Fetch all records in range
				$watchHistory = $this->Crud->date_range($start, 'reg_date', $end, 'reg_date', $table);

				// Scope filter
				$filtered = [];
				foreach ($watchHistory as $watch) {
					$user_church = $this->Crud->read_field('id', $watch->user_id, 'user', 'cadmin_id');
					$user_ministry = $this->Crud->read_field('id', $watch->user_id, 'user', 'madmin_id');

					if (
						$is_super_admin ||
						($logged_church_id && $user_church == $logged_church_id) ||
						($logged_ministry_id && $user_ministry == $logged_ministry_id)
					) {
						$filtered[] = $watch;
					}
				}

				// Total videos watched
				$total_videos_watched = count($filtered);

				// Unique users
				$unique_users_array = [];
				foreach ($filtered as $entry) {
					$unique_users_array[$entry->user_id] = true;
				}
				$unique_users = count($unique_users_array);

				// Most watched video logic
				$videoz = [];
				$most_viewed_count = 0;
				$video_title = '-';

				if (!empty($filtered)) {
					foreach ($filtered as $entry) {
						$video_id = $entry->video_id;
						$videoz[$video_id] = ($videoz[$video_id] ?? 0) + 1;
					}

					$most_viewed_video_id = array_search(max($videoz), $videoz);
					$most_viewed_count = $videoz[$most_viewed_video_id];
					$video_title_raw = $this->Crud->read_field('id', $most_viewed_video_id, 'video', 'title');
					$video_title = $video_title_raw ? "$video_title_raw ($most_viewed_count Views)" : "-";
				}

				echo json_encode([
					'total_videos_watched' => $total_videos_watched,
					'unique_users' => $unique_users,
					'most_watched_video' => $video_title
				]);
				die;
			}

			if ($param2 == 'video_leaderboard') {
				$range = $_POST['range'] ?? 'weekly';
				$start_date = $_POST['start_date'] ?? null;
				$end_date = $_POST['end_date'] ?? null;

				// Date filtering
				$where = $this->buildDateFilter($range, $start_date, $end_date);
				$start = $where['start'];
				$end = $where['end'];

				// Get logged-in user scope
				$logged_user_id = $this->session->get('plx_id') ?? null;
				$logged_church_id = $church_id ?? null;
				$logged_ministry_id = $ministry_id ?? null;
				$is_super_admin = in_array($role, ['administrator', 'developer']);

				// Fetch all watch records in range
				$rawHistory = $this->Crud->date_range($start, 'reg_date', $end, 'reg_date', 'watch_history');

				// Scope filter
				$filteredHistory = [];
				foreach ($rawHistory as $record) {
					$user_church = $this->Crud->read_field('id', $record->user_id, 'user', 'cadmin_id');
					$user_ministry = $this->Crud->read_field('id', $record->user_id, 'user', 'madmin_id');

					if (
						$is_super_admin ||
						($logged_church_id && $user_church == $logged_church_id) ||
						($logged_ministry_id && $user_ministry == $logged_ministry_id)
					) {
						$filteredHistory[] = $record;
					}
				}

				// === TOP USERS ===
				$user_watch_count = [];
				foreach ($filteredHistory as $entry) {
					$uid = $entry->user_id;
					$user_watch_count[$uid] = ($user_watch_count[$uid] ?? 0) + 1;
				}

				arsort($user_watch_count);
				$user_data = [];
				$counter = 0;

				foreach ($user_watch_count as $user_id => $videos_watched) {
					if ($counter >= 5)
						break;

					$user_data[] = [
						'user_name' => $this->Crud->read_field('id', $user_id, 'user', 'fullname'),
						'videos_watched' => $videos_watched,
						'total_watch_time' => '' // You can add this if tracked
					];
					$counter++;
				}

				// === TOP VIDEOS ===
				$video_watch_count = [];
				foreach ($filteredHistory as $entry) {
					$vid = $entry->video_id;
					$video_watch_count[$vid] = ($video_watch_count[$vid] ?? 0) + 1;
				}

				arsort($video_watch_count);
				$video_data = [];
				$counter = 0;

				foreach ($video_watch_count as $video_id => $views) {
					if ($counter >= 5)
						break;

					$video_data[] = [
						'video_title' => $this->Crud->read_field('id', $video_id, 'video', 'title'),
						'views' => $views
					];
					$counter++;
				}

				echo json_encode([
					'top_users' => $user_data,
					'top_videos' => $video_data
				]);
				die;
			}


			if ($param2 == 'video_watch_history') {
				$range = $_POST['range'] ?? 'daily';
				$start_date = $_POST['start_date'] ?? null;
				$end_date = $_POST['end_date'] ?? null;

				// Date range
				$where = $this->buildDateFilter($range, $start_date, $end_date);
				$start = $where['start'];
				$end = $where['end'];

				// Get logged-in user scope
				$logged_user_id = $this->session->get('plx_id') ?? null;
				$logged_church_id = $church_id ?? null;
				$logged_ministry_id = $ministry_id ?? null;
				$is_super_admin = in_array($role, ['administrator', 'developer']);

				// Fetch full history
				$rawWatchHistory = $this->Crud->date_range($start, 'reg_date', $end, 'reg_date', 'watch_history');

				// Filter history based on access
				$filteredHistory = [];
				foreach ($rawWatchHistory as $record) {
					$user_church = $this->Crud->read_field('id', $record->user_id, 'user', 'cadmin_id');
					$user_ministry = $this->Crud->read_field('id', $record->user_id, 'user', 'madmin_id');

					if (
						$is_super_admin ||
						($logged_church_id && $user_church == $logged_church_id) ||
						($logged_ministry_id && $user_ministry == $logged_ministry_id)
					) {
						$filteredHistory[] = $record;
					}
				}

				// Format for output
				$data = [];
				foreach ($filteredHistory as $history) {
					$data[] = [
						'user_name' => $this->Crud->read_field('id', $history->user_id, 'user', 'fullname'),
						'video_title' => $this->Crud->read_field('id', $history->video_id, 'video', 'title'),
						'watched_at' => date('d M, Y h:i:sA', strtotime($history->reg_date))
					];
				}

				echo json_encode($data);
				die;
			}
			die;
		}

		// record listing
		if ($param1 == 'load') {
			$limit = $param2;
			$offset = $param3;

			$rec_limit = 25;
			$item = '';
			$counts = 0;

			if (empty($limit)) {
				$limit = $rec_limit;
			}
			if (empty($offset)) {
				$offset = 0;
			}

			$search = $this->request->getPost('search');
			$log_id = $this->session->get('plx_id');
			if (!$log_id) {
				$item = '<div class="text-center text-muted">Session Timeout! - Please login again</div>';
			} else {
				$query = $this->Crud->filter_announcement($limit, $offset, $log_id, $search);
				$all_rec = $this->Crud->filter_announcement('', '', $log_id, $search);
				if (!empty($all_rec)) {
					$count = count($all_rec);
				} else {
					$count = 0;
				}

				if (!empty($query)) {
					foreach ($query as $q) {
						$id = $q->id;
						$title = $q->title;
						$content = $q->content;
						$to_id = $q->to_id;
						$team = $q->role_id;
						$type = $q->type;
						$user_i = $q->from_id;

						$reg_date = date('M d, Y h:i A', strtotime($q->reg_date));
						$user = $this->Crud->read_field('id', $user_i, 'user', 'fullname');

						$teams = '';
						if ($role == 'developer' || $role == 'administrator' || $user_i == $log_id) {
							if ($type == 0) {
								$teams .= '<span class="badge badge-pill badge-green mb-1">' . strtoupper('All parents') . '</span>';

							} else {
								$teams .= '<span class="badge badge-pill badge-green mb-1">' . strtoupper('Selected parents') . '</span>';

							}
						}
						// add manage buttons
						if ($role_u != 1) {
							$all_btn = '';
						} else {
							$all_btn = '
                                    <a href="javascript:;" class="text-primary pop mx-2" pageTitle="Manage ' . $title . '" pageName="' . base_url('tools/announcement/manage/edit/' . $id) . '" pageSize="modal-lg">
                                        <i class="anticon anticon-edit"></i> Edit
                                    </a> 
                                    <a href="javascript:;" class="text-success pop mx-2" pageTitle="View ' . $title . '" pageName="' . base_url('tools/announcement/manage/view/' . $id) . '" pageSize="modal-lg">
                                        <i class="anticon anticon-eye"></i> View
                                    </a>  
                                    <a href="javascript:;" class="text-info pop mx-2" pageTitle="View ' . $title . '" pageName="' . base_url('tools/announcement/manage/email/' . $id) . '" pageSize="modal-lg"><i class="anticon anticon-share-alt"></i>
                                        Send to Email
                                    </a>
                                    
                            ';


						}

						if ($role == 'developer' || $role == 'administrator') {
							$item .= '
                                <tr>
                                    <td>
                                        <div class="media align-items-center">
                                            <div class="m-l-10">
                                                <span class="text-muted small">' . $reg_date . '</span><br>
                                                <h5 class="m-b-0">' . ucwords($title) . '</h5>
                                            </div>
                                        </div>
                                    </td>
                                    <td >
                                        <div class="d-flex align-items-center">
                                            <div>
                                                ' . $teams . '
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            ' . $user . '
                                        </div>
                                    </td>
                                    <td  class="text-right">
                                        ' . $all_btn . '
                                    </td>
                                </tr>
                                
                            ';
						} else {
							if ($user_i == $log_id) {
								$item .= '
                                    <tr>
                                        <td>
                                            <div class="media align-items-center">
                                                <div class="m-l-10">
                                                    <span class="text-muted small">' . $reg_date . '</span><br>
                                                    <h5 class="m-b-0">' . ucwords($title) . '</h5>
                                                </div>
                                            </div>
                                        </td>
                                        <td >
                                            <div class="d-flex align-items-center">
                                                <div>
                                                    ' . $teams . '
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                ' . $user . '
                                            </div>
                                        </td>
                                        <td  class="text-right">
                                            ' . $all_btn . '
                                        </td>
                                    </tr>
                                    
                                ';
							} else {
								if (!empty($team)) {
									if (in_array($log_id, json_decode($to_id), true)) {
										$item .= '
                                            <tr>
                                                <td>
                                                    <div class="media align-items-center">
                                                        <div class="m-l-10">
                                                            <span class="text-muted small">' . $reg_date . '</span><br>
                                                            <h5 class="m-b-0">' . ucwords($title) . '</h5>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td >
                                                    <div class="d-flex align-items-center">
                                                        <div>
                                                            ' . $teams . '
                                                        </div>
                                                    </div>
                                                </td>
                                                <td>
                                                    <div class="d-flex align-items-center">
                                                        ' . $user . '
                                                    </div>
                                                </td>
                                                <td class="text-right">
                                                    ' . $all_btn . '
                                                </td>
                                            </tr>
                                            
                                        ';
									}
								}
							}
						}

					}
				}
			}
			if (empty($item)) {
				$resp['item'] = '
					<div class="text-center text-muted col-sm-12">
						<br/><br/><br/><br/>
						<i class="anticon anticon-notification" style="font-size:120px;"></i><br/><br/>No Announcements Returned
					</div>
				';
			} else {
				$resp['item'] = $item;
			}

			$more_record = $count - ($offset + $rec_limit);
			$resp['left'] = $more_record;

			if ($count > ($offset + $rec_limit)) { // for load more records
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
			return view('tools/watch_form', $data);
		} else { // view for main page

			$data['title'] = 'Video Watch History - ' . app_name;
			$data['page_active'] = 'tools/watch';
			return view('tools/watch', $data);
		}
	}


	private function buildDateFilter($range, $start_date = null, $end_date = null)
	{
		if ($range == "custom" && $start_date && $end_date) {
			return [
				"start" => $start_date . " 00:00:00",
				"end" => $end_date . " 23:59:59"
			];
		} elseif ($range == "weekly") {
			// Get start (Monday) and end (Sunday) of the current week
			$startOfWeek = date("Y-m-d", strtotime("monday this week")) . " 00:00:00";
			$endOfWeek = date("Y-m-d", strtotime("sunday this week")) . " 23:59:59";
			return ["start" => $startOfWeek, "end" => $endOfWeek];
		} elseif ($range == "monthly") {
			// Get first and last day of the current month
			$startOfMonth = date("Y-m-01") . " 00:00:00";
			$endOfMonth = date("Y-m-t") . " 23:59:59";
			return ["start" => $startOfMonth, "end" => $endOfMonth];
		}

		// Default: Today’s data
		$today = date("Y-m-d");
		return [
			"start" => $today . " 00:00:00",
			"end" => $today . " 23:59:59"
		];
	}

	public function sub_update()
	{
		$sub = $this->Crud->read('sub');
		if (!empty($sub)) {
			foreach ($sub as $s) {
				$id = $s->id;
				$this->Crud->updates('id', $id, 'sub', array('reg_date' => $s->start_date));
			}
		}
	}
}
