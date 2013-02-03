<?php
    require_once "config.php";
    require_once "mailClass.php";
    require_once "pfwJobs.php";
    require_once "SOAP/Client.php";
    require_once "MySoapClient.php";
    require_once "XmlUtils.php";
    require_once "LoopSoapConnection.php";
    
    error_reporting(E_ALL);
    set_time_limit(600);    

    // Passing the constant (see config.php) value
    $endpoint = ENDPOINT;
    
     $subject = "APEC SOAP PROCESS IS LAUNCHED";
     $message = "test mail"; 
     $mail = new mailClass( "info@concatel.com" , MAIL_AUTH, $subject, $message);
     $mail->send();

    if(!isset($_SERVER['argv'][1]) && !isset($_SERVER['argv'][2])){
    	$composition = new composeXml(
                    PARTNERID,
                    USERID,
                    PASSWORD,
                    INIDATE,
                    ENDDATE
                ); 
    }else{ 
    	$composition = new composeXml(
                    PARTNERID,
                    USERID,
                    PASSWORD,
                    $_SERVER['argv'][1],
                    $_SERVER['argv'][2]
                ); 
    }
    //FIRST JOB OF ALL: we program the delete tasks of today!!
    $composition->deleteExpiredOffers();
    
    // get array of data and methods
    $dataXml =  $composition->getData();
    $method =   $composition->getMethod();
    $daemonJobId = $composition->getDaemonJobId();
    $idOfferSii = $composition->getIdOfferSii();
    
    $loop = new LoopSoapConnection();
    $soapClient = $loop->attemptsInLoopSoapConn($endpoint);
    
    if(!$soapClient){
        die();
    }
    
    foreach ($dataXml as $key => $strXml){
       
       $PostTransaction = $soapClient->__myDoRequest($strXml, $method[$key]);
       
       $parseXml = new XmlUtils();
       $objResponse = $parseXml->XmlToSimpleObject($PostTransaction);
	   
       if($parseXml->isResponseOK($objResponse)){
           
           if($method[$key] == "openPosition"){
                $composition->setApecOfferId($idOfferSii[$key],$parseXml->idOfferApec);
                $composition->openSameOfferOneMonth($idOfferSii[$key]);
           }
           
           $composition->setWorkOk($daemonJobId[$key]);
            
           // log results
           $arrData = array(
                    "tracking_id"  =>   $parseXml->trackingId,
                    "request"      =>   $strXml,
                    "response"     =>   $PostTransaction,
                    "daemonTaskId" =>   $daemonJobId[$key],
                    "idSii"        =>   $idOfferSii[$key],
                    "idApec"       =>   $parseXml->idOfferApec,
                    "SOAPOK"       =>   1,
                    "APECOK"       =>   1,
                    "errorCode"    =>   0,
                    "errorString"  =>   "",
                    "method"       =>   $method[$key]
           );
           $composition->log($arrData);
                  
       } else {
           
           // log results:
           $arrData = array(
                    "tracking_id"  =>   $parseXml->trackingId,
                    "request"      =>   $strXml,
                    "response"     =>   $PostTransaction,
                    "daemonTaskId" =>   $daemonJobId[$key],
                    "idSii"        =>   $idOfferSii[$key],
                    "idApec"       =>   $composition->getApecOfferId($idOfferSii[$key]),
                    "SOAPOK"       =>   1,
                    "APECOK"       =>   0,
                    "errorCode"    =>   $parseXml->ApecErrorCode,
                    "errorString"  =>   $parseXml->ApecErrorStr,
                    "method"       =>   $method[$key]
           );
           $composition->log($arrData);
           
       }
       
    }
  
        
?>