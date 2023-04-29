<?php
/**
 * Coded by: Pran Krishna Das
 * Social: www.fb.com/pran93
 * CI: 3.0.6
 * Date: 09-07-17
 * Time: 10:00
 */

class Registration_m extends CI_Model {

    public function __construct() {
        parent::__construct();
    }

    public function admin_registration() {
        //log-in validation rules
        $this->form_validation->set_rules(
            'username', 'username or email address',
            'required',
            array(
                'required' => 'Enter your %s!'
            )
        );
        $this->form_validation->set_rules(
            'pass', 'password',
            'required',
            array(
                'required' => 'Enter your %s!'
            )
        );
        // /.log-in validation rules

        //if validation error occurred
        if($this->form_validation->run() == FALSE) {
            return array('type'=>'load_view', 'page'=>'registration');
        }

        $username = $this->input->post('username');
        $pass = $this->input->post('pass');
        $pass_encrypted = hash('sha256', $pass); //encrypting password with sha256 encoding
		
		//Insert data into User & User details table
		
		
        $this->db->where('username', $username);
        $this->db->or_where('email', $username);
        $rs_acc_chk = $this->db->get('users')->row();
		

        //if username/email does not exists
        if (count($rs_acc_chk) == 0) {
			$insertArray = array(
				'usertype' => 2,
				'username' => $username,
				'email' => $username, 
				'pass' => $pass_encrypted,
				'verified' => 1,
				'blocked' => 0
			);
			if($this->db->insert('users', $insertArray)){
				$user_id = $this->db->insert_id();
				$insertArray2 = array(
					'user_id' => $user_id,
					'firstname' => 'Mr.',
					'lastname' => 'User'
				);
				$this->db->insert('user_details', $insertArray2);
			}else{
				$this->session->set_flashdata('title', 'Uh-oh!');
				$this->session->set_flashdata('msg', 'Data Inser error in User Table');
				return array('type'=>'redirect', 'page'=>'registration');
			}

        }else{
			$this->session->set_flashdata('title', 'Uh-oh!');
            $this->session->set_flashdata('msg', 'Username already exists!');
            return array('type'=>'redirect', 'page'=>'registration');	
		}

        //if username/email exists
        /*$this->db->where('username', $username);
        $this->db->where('pass', $pass_encrypted);
        $this->db->or_where('email', $username);
        $this->db->where('pass', $pass_encrypted);
        $rs = $this->db->get('users');
        $row = $rs->row();

        //if username/email & pass is wrong
        if($rs->num_rows() == 0) {
            $this->session->set_flashdata('title', 'Uh-oh!');
            $this->session->set_flashdata('msg', 'Wrong Username and Password!');
            return array('type'=>'redirect', 'page'=>'login');
        }
        //if username/email & pass matched but account is blocked
        elseif($row->blocked == '1') {
            $this->session->set_flashdata('title', 'Suspended!');
            $this->session->set_flashdata('msg', 'Your account is blocked, please contact with administrator.');
            return array('type'=>'redirect', 'page'=>'login');
        }
        //if username/email & pass matched but account is not verified yet
        elseif($row->verified == '0') {
            $this->session->set_flashdata('title', 'Verification Pending!');
            $this->session->set_flashdata('msg', 'Your account is not verified yet, please contact with administrator.');
            return array('type'=>'redirect', 'page'=>'login');
        }*/

        //if all validation passed
        $email = isset($row->email) ? $row->email : 'N/A';
        $name = $this->db->get_where('user_details', array('user_id' => $user_id))->row()->lastname;

        $session_data = array(
            'user_id' => $user_id,
            'usertype' => 2,
            'username' => $username,
            'email' => $email,
            'name' => $name,
        );
        $this->session->set_userdata($session_data); //creating session
        return array('type'=>'redirect', 'page'=>'admin/dashboard'); //redirect to admin dashboard

    } // /.admin_login()

    public function admin_logout() { //admin logout
        $unset_data = $this->session->all_userdata();
        foreach ($unset_data as $key => $value) {
            $this->session->unset_userdata($key);
        }

        $this->session->set_flashdata('title', 'Logged Out!');
        $this->session->set_flashdata('msg','You have been logged-out successfully.');
        return 'login'; //redirect to user login page
    } // /.admin_logout()

    public function change_password($otp) {
        $rs = $this->db->get_where('user_reset_passwords', array('otp' => $otp));
        $row = $rs->row();
        if($rs->num_rows() == 0) { //if no such OTP exists
            $this->session->set_flashdata('title', 'Wrong Route!');
            $this->session->set_flashdata('msg', 'This link is not valid.');
            return array('type'=>'redirect', 'page'=>'login');
        }
        else { //if OTP exists
            $request_time = new DateTime($row->time); //time of password recovery request
            $current_time = new DateTime(date('d-m-y H:i:s')); //current time
            $time_diff_obj = $request_time->diff($current_time); //time difference

            $minutes = $time_diff_obj->days * 24 * 60; //1 day * 24 hour * 60 minutes
            $minutes += $time_diff_obj->h * 60; //24 hour * 60 minutes
            $minutes += $time_diff_obj->i; //60 minutes

            if($minutes > 30 ){ //if time difference is greater 30 minute
                $this->session->set_flashdata('title', 'Timeout!');
                $this->session->set_flashdata('msg', 'Password reset link has been expired, request again!');  //pass reset link expired
                return array('type'=>'redirect', 'page'=>'login');
            } else {
                if($row->used == '1') { //if pass change token is already used
                    $this->session->set_flashdata('title', 'Token Used!');
                    $this->session->set_flashdata('msg', 'Password reset token already used. To reset password, request again.');
                    return array('type'=>'redirect', 'page'=>'login');
                } else { //if pass change token is not used
                    $data['otp'] = $otp;
                    return array('type'=>'load_view', 'page'=>'reset_password_v', 'data'=>$data); //loading password recovery page
                }
            }
        }
    } // /.change_password()

    public function update_password() {
        $key = $this->input->post('otp');
        $new_pass = $this->input->post('new_pass');
        $data['otp'] = $key;

        //reset password validation rules
        $this->form_validation->set_rules(
            'new_pass', 'New Password',
            'required|min_length[8]',
            array(
                'required' => 'You must set a %s!',
                'min_length' => 'Password must be eight characters long!'
            )
        );
        $this->form_validation->set_rules(
            'conf_pass', 'Confirm Password',
            'required|matches[new_pass]',
            array(
                'required' => 'You must type the same password in %s field!',
                'matches' => '%s not matched!'
            )
        );
        // /.reset password validation rules

        if($this->form_validation->run() == FALSE) { //if validation error occurred
            return array('type'=>'load_view', 'page'=>'reset_password_v', 'data'=>$data); //loading reset password page
        }
        else { //if form validation passed
            $rs = $this->db->get_where('user_reset_passwords', array('otp' => $key));
            $row = $rs->row();
            if($rs->num_rows() == 0) { //if no such OTP exists
                $this->session->set_flashdata('title', 'Wrong Route!');
                $this->session->set_flashdata('msg', 'Do not mess up. This link is not valid.');
                return array('type'=>'redirect', 'page'=>'login');
            }
            else { //if OTP exists
                $request_time = new DateTime($row->time); //time of password recovery request
                $current_time = new DateTime(date('d-m-y H:i:s'));; //current time
                $time_diff_obj = $request_time->diff($current_time); //time difference`

                $minutes = $time_diff_obj->days * 24 * 60; //1 day * 24 hour * 60 minutes
                $minutes += $time_diff_obj->h * 60; //24 hour * 60 minutes
                $minutes += $time_diff_obj->i; //60 minutes

                if($minutes > 30 ){ //if time difference is greater 30 minute
                    $this->session->set_flashdata('title', 'Timeout!');
                    $this->session->set_flashdata('msg', 'Password reset link has been expired, request again!');  //pass reset link expired
                    return array('type'=>'redirect', 'page'=>'login');
                } else {
                    $pass_encrypted = hash('sha256', $new_pass); //encrypting password with sha256 encoding
                    $user_id = $row->user_id;

                    //updating password
                    $this->db->where('user_id', $user_id);
                    $this->db->update('users', array('pass'=>$pass_encrypted, 'verified'=>'1'));
                    //setting pass change token used
                    $this->db->where('otp', $key);
                    $this->db->update('user_reset_passwords', array('used' => '1'));

                    $this->session->set_flashdata('title', 'Woo-hoo!');
                    $this->session->set_flashdata('msg', 'You have updated your password successfully. You may log-in to your account now! :)');
                    return array('type'=>'redirect', 'page'=>'login');
                }
            }
        }
    } // /.update_password()

    public function sendmail($mail_to,$msg,$mail_sub='',$mail_from='',$attachments=array(),$mailer_name='',$smtp_host='',$smtp_port='',$smtp_user='',$smtp_pass='') {
        if($mail_from == '') $mail_from=default_mail_from; else $mail_from=$mail_from;
        if($mailer_name == '') $mailer_name=default_mailer_name; else $mailer_name=$mailer_name;
        if($mail_sub == '') $mail_sub=default_mail_sub; else $mail_sub=$mail_sub;
        if($smtp_host == '') $smtp_host=default_smtp_host; else $smtp_host=$smtp_host;
        if($smtp_port == '') $smtp_port=default_smtp_port; else $smtp_port=$smtp_port;
        if($smtp_user == '') $smtp_user=default_smtp_user; else $smtp_user=$smtp_user;
        if($smtp_pass == '') $smtp_pass=default_smtp_pass; else $smtp_pass=$smtp_pass;

        $config = Array(
            'smtp_host' => $smtp_host,
            'smtp_port' => $smtp_port,
            'smtp_user' => $smtp_user,
            'smtp_pass' => $smtp_pass,
            'protocol' => 'smtp',
            'mailtype' => 'html',
            'charset' => 'utf-8',
            'wordwrap' => TRUE
        );
        $this->load->library('email', $config);

        $this->email->clear(TRUE);
        $this->email->set_mailtype("html");
        $this->email->from($mail_from, $mailer_name);
        $this->email->reply_to($mail_from, $mailer_name);
        $this->email->to($mail_to);
        $this->email->subject($mail_sub);
        $this->email->message($msg);
        //attaching files
        foreach($attachments as $att) {
            $this->email->attach($att);
        }
        //sending mail
        $this->email->send();
    }

} // /.Login_m model