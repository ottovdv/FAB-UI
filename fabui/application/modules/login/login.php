<?php 

class Login extends Module {

	public function __construct()
	{
		parent::__construct();

	}

	public function index(){
	   
        if(isset($_SESSION['logged_in']) && $_SESSION['logged_in'] == TRUE){
            redirect('dashboard', 'location');
        }
		
		
		$data['new_registration'] = false;
		$data['new_reset']        = false;
		$data['login_failed']     = false;
		$data['email']            = '';
		$data['body_class']       = 'animated fadeInDown';
		
		if(isset($_SESSION['new_registration']) && $_SESSION['new_registration'] == true){
			
			unset($_SESSION['new_registration']);	
			$data['new_registration'] = true;
			$data['body_class'] = '';
		}
		
		
		if(isset($_SESSION['new_reset']) && $_SESSION['new_reset'] == true){
			
			unset($_SESSION['new_reset']);
			$data['new_reset'] = true;
			$data['body_class'] = '';
		}
		
		
		if(isset($_SESSION['login_failed']) && $_SESSION['login_failed'] == true){
			
			$data['email'] = $_SESSION['login_failed_mail'];
			unset($_SESSION['login_failed']);
			unset($_SESSION['login_failed_mail']);
			$data['login_failed'] = true;
			$data['body_class'] = '';
			
		}
		
		
		
		//carico X class database
		$this->load->database();
		$this->load->model('configuration');
		
		$this->load->helper('ft_date_helper');
		$this->load->helper('update_helper');
		
		$this -> config->load('fabtotum', TRUE);

		$url = $this-> config -> item('fabtotum_suggestions_url', 'fabtotum');
		
		
		$data['fabui_version'] = $this->configuration->get_config_value('fabui_version');
        $data['fw_version']    = $this->configuration->get_config_value('fw_version');
		
		
		if(file_exists($this->config->item('fabtotum_instagram_hash', 'fabtotum'))){
			
			$instagram_hash = json_decode(file_get_contents($this->config->item('fabtotum_instagram_hash', 'fabtotum')), true); 
			$data['instagram_hash'] = $instagram_hash;
			
		}
		
		
		
		if(file_exists($this->config->item('fabtotum_instagram_feed', 'fabtotum'))){
			
			$instagram_feed = json_decode(file_get_contents($this->config->item('fabtotum_instagram_feed', 'fabtotum')), true); 
			$data['instagram_feed'] = $instagram_feed;
			
		}
		
		if(file_exists($this->config->item('fabtotum_twitter_feed', 'fabtotum'))){
			
			$twitter_feed = json_decode(file_get_contents($this->config->item('fabtotum_twitter_feed', 'fabtotum'))); 
			$data['twitter_feed'] = $twitter_feed;
			
			
			
			
			
		}
		
		
		
		

		$this->load->view('index/index', $data);

	}
    
    
    
    public function do_login(){
        
        
        if($this->input->post()){
            
            
            $this->load->helper('ft_file_helper');
            clean_temp('hour', 12);
            
            $post = $this->input->post();
            
			//carico X class database
			$this->load->database();
			$this->load->model('user');

			$email    = $this->input->post('email');
			$password = $this->input->post('password');
   
                 
			if($this->user->login($email, $password) == TRUE){
			 
				$user = $this->user->get_user($email);
                
				$_settings = json_decode($user->settings, TRUE);
               	
                $_user_session['id']         = $user->id;
                $_user_session['first_name'] = $user->first_name;
                $_user_session['last_name']  = $user->last_name;
                $_user_session['email']      = $user->email;
				$_user_session['avatar']     = $_settings['avatar'];
				$_user_session['theme-skin'] = $_settings['theme-skin'];
				$_user_session['lock-screen'] = isset($_settings['lock-screen']) ? $_settings['lock-screen'] : 0;
				$_user_session['layout'] = isset($_settings['layout']) ? $_settings['layout'] : '';
               
                $_SESSION['user']      = $_user_session;
                $_SESSION['logged_in'] = TRUE;
                $_SESSION['type']      = 'fabtotum';
				$_SESSION['ask_wizard'] = TRUE;
                
               
			  	/** LOAD HELPER */
        		$this->load->helper('update_helper');
				
				$_fabui_local = myfab_get_local_version();
				$_fw_local    = marlin_get_local_version();
				 
				$_fabui_update = false;
				$_fw_update    = false;
				
				$_updates =  array();
				$_updates['number'] = 0;
				$_updates['time'] = time();	
				
				if(is_internet_avaiable()){
					
					$_fabui_remote_version = myfab_get_remote_version();
					$_fw_remote_version    = marlin_get_remote_version();
					
					$_fabui_update = $_fabui_remote_version > $_fabui_local;
					$_fw_update    = $_fw_remote_version > $_fw_local;
					
					$_updates['number'] += $_fabui_update ? 1 : 0;
					$_updates['number'] += $_fw_update ? 1 : 0;
					$_updates['fabui']   = $_fabui_update;
					$_updates['fw']      = $_fw_update;
					
				}
				 
               
				$_SESSION['fabui_version'] = $_fabui_local;
				$_SESSION['updates'] = $_updates;
				
				
                $this->user->update_login($user->id);
               

				redirect('dashboard', 'location');

			}else{
				
				
			 /**  */
			 $_SESSION['login_failed'] = true;
			 $_SESSION['login_failed_mail'] = $email;	
			 redirect('login');
			}

		}

    }


	public function out(){
	   
        foreach($_SESSION as $key => $value){
            unset($_SESSION[$key]);    
        }
		
		
		session_destroy();
		redirect('login');
	}
	
	
	
	public function reset_mail(){
		
		
		$_email = $this->input->post('email');
		
		/** */
		$this->load->database();
		$this->load->model('user');
		$user = $this->user->get_user($_email);
		
		
		if($user){
			
			
			$_settings = json_decode($user->settings, TRUE);

			$_token =  md5($user->id.'-'.$_email.'-'.time());
			$_settings['token'] = $_token;
			$data_update['settings'] = json_encode($_settings);

			$this->user->update($user->id, $data_update);
			
			$data['user']  = $user;
			$data['token'] = $_token;
			
			$this->load->library('email');
		
			$config['mailtype'] = 'html';
			$this->email->initialize($config);
	
			$this->email->from('noreplay@fabtotum.com', 'Your Personal Fabricator - Fabtotum');
			$this->email->to($_email);  
			
			$this->email->subject('Reset Passoword');
			
			
			$body_message = $this->load->view('mail/reset', $data, true);
			
			
			$this->email->message($body_message);
			
			
			$response['user'] = 1;
			
			
			
			if ( ! $this->email->send()){
				  
				  $response['sent'] = 0;
				   
			}else{
				$response['sent'] = 1;
			}
			
			echo json_encode($response);
			
				
		}else{
			echo json_encode(array('user'=>0));
		}
		
		
	}
	
	
	
	public function reset($token){
		
		/** */
		$this->load->database();
		$this->load->model('user');
		
		$user = $this->user->get_by_token($token);
		
		if($user){
			$data['user'] = $user;
			$data['token'] = $token;
			$this->load->view('reset/index', $data);
		}else{
			redirect('login');
		}
		
	}
	
	
	public function do_reset(){
		
		
		$this->load->database();
		$this->load->model('user');
		
		$user = $this->user->get_by_token($this->input->post('token'));
		
		$_settings = json_decode($user->settings, true);
		
		$_settings['token'] = '';
		
		$data_update['password'] = md5($this->input->post('password'));
		$data_update['settings'] = json_encode($_settings);
		
		$this->user->update($user->id, $data_update);
		
		$_SESSION['new_reset'] = true;
		
		redirect('login');
		
		
	}
	
	
	public function register(){
		$this->load->view('register/index');
	}
	
	
	public function do_registration(){
		
		
		$data = $this->input->post();
		
		$this->load->database();
		$this->load->model('user');
		
		
		$settings['theme-skin'] = 'smart-style-0';
		$settings['avatar'] = '';
		$settings['token']  = '';
		
		$data['settings'] = json_encode($settings);
		
		$user = $this->user->add($data);
		
		$_SESSION['new_registration'] = true;
		
		redirect('login');
		
		
	}
	
	
	public function check_mail(){
		
		$_email = $this->input->get("email");
		

		$this->load->database();
		$this->load->model('user');
		
		$user = $this->user->get_user($_email);
		
		echo  $user == false ? 'true' : 'false';

		
	}
	
	
	
	public function lock(){
		$this->load->view('lock/index');
		
	}
	

}

