<?php

class mailClass  {
    
    private $email;
    private $to;
    private $subject;
    private $message;
    
    function __construct($email, $to, $subject, $message) {
		$this->email        = $email;
                $this->to           = $to;
                $this->subject      = $subject;
                $this->message      = $message;
	}
        
    public function send(){
        $headers = "From: ".$this->email; 
        $sent = mail($this->to, $this->subject, $this->message, $headers);
                if($sent){
                    return true;
                } else {
                    return false;
                }
    }
    
}
?>
