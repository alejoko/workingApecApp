<?php

class LoopSoapConnection{
    
    private $attempts = SOAP_CONNNECTION_ATTEMPS;
    private $sleep = SECONDS_BETWEEN_ATTEMPS;
    public  $last_error; 
 
    // attemptsInLoopSoapConn
    /* 
     * 
     */
    function attemptsInLoopSoapConn($endpoint){ 
        $i = 1;
      
        while(TRUE) {
            
             try {
                    $soapClient = new MySoapClient($endpoint, 
                      array(
                       'trace'          => 1,
                       'exceptions'     => 1,
                       'soap_version'   => SOAP_1_1,
                       'encoding'       => ENCODING
                       )
                   ); 
                   return $soapClient;
             
            } catch (Exception $e) {
                
                $this->last_error = $e->getMessage();
                echo "<h2>Exception Error!</h2>"; 
                echo $e->getMessage(); 
                if ($i==$this->attempts){
                    
                    $semaphore = $composition->setSemaphore(0);
                    
                    $subject = "APEC SOAP PROCESS FAIL!";
                    $message = "
                        The APEC soap connnection fail after ".$this->attempts." attemps.
                        Please conctact Conactel Vanture Team Barcelona for more info to launch the cron process.
                    "; 
                    $mail = new mailClass( "info@concatel.com" , MAIL_SEND_INFO, $subject, $message);
                    $send = $mail->send();
                    if($send){
                            print "SOAP process fail: an email has been sent to managers";
                            echo "<br/>";
                        } else {
                            print "SOAP process fail! And is impossible to delivery an email by SMTP!!"; 
                            echo "<br/>";
                        }
                    
                   $composition = new composeXml(
                                PARTNERID,
                                USERID,
                                PASSWORD,
                                INIDATE,
                                ENDDATE
                            ); 
                            
                    // log results:
                    $arrData = array(
                             "tracking_id"  =>   '',
                             "request"      =>   '',
                             "response"     =>   '',
                             "daemonTaskId" =>   0,
                             "idSii"        =>   0,
                             "idApec"       =>   '0',
                             "SOAPOK"       =>   0,
                             "APECOK"       =>   0,
                             "errorCode"    =>   'Imposible launch SOAP WSDL',
                             "errorString"  =>   $e->getMessage(),
                             "method"       =>   '',
                             "offerStatus"  =>   ''
                    );
                    $composition->log($arrData);
                    throw $e;
                    return false; // important!!: return breaks the loop!
                    
                }
                sleep($this->sleep);
                ++$i;
                
            }   

        }
    }   
}

?>
