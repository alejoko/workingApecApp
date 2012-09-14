<?php

//TODO: alter mysql in prod by this:
//ALTER TABLE  `pfw_5_job`
//ADD COLUMN  `job_vacancy` INT(10) NOT NULL AFTER  `job_type`
//ALTER TABLE `pfw_5_job`
//ADD COLUMN `job_exportAPEC` TINYINT(1) NOT NULL AFTER `job_vacancy`
//
//TODO: create table
//DROP TABLE IF EXISTS `aux_pfw_job`;
//CREATE TABLE `aux_pfw_job` (
//	`aux_pfw_id` INT(11) NOT NULL AUTO_INCREMENT,
//	`aux_job_id` INT(11) NOT NULL,
//	`aux_job_date` DATE NOT NULL,
//	`aux_job_time` TIME NOT NULL,
//	`aux_job_flag_make` TINYINT(1) NOT NULL DEFAULT '0',
//	`aux_job_operation` MEDIUMTEXT NOT NULL,
//	`aux_job_trigger_sentence` VARCHAR(20) NOT NULL DEFAULT '',
//	PRIMARY KEY (`aux_pfw_id`)
//)
//COLLATE='utf8_general_ci'
//ENGINE=InnoDB;
//            
//TODO: create table
//DROP TABLE IF EXISTS `aux_pfw_id_SII_APEC`;
//CREATE TABLE `aux_pfw_id_SII_APEC` (
//	`aux_id` INT(11) NOT NULL AUTO_INCREMENT,
//	`aux_sii_id` INT(11) NOT NULL ,
//	`aux_apec_id` VARCHAR(20) NOT NULL ,
//		PRIMARY KEY (`aux_id`)
//)
//COLLATE='utf8_general_ci'
//ENGINE=InnoDB;
//
//TODO:create table
//DROP TABLE IF EXISTS `pfw_5_webservice_log`;
//CREATE TABLE `pfw_5_webservice_log` (
//	`pfw_wslog_id` INT(11) NOT NULL AUTO_INCREMENT,
//	`pfw_wslog_tracking_id` VARCHAR(100) NOT NULL,
//	`pfw_wslog_request` TEXT NOT NULL,
//	`pfw_wslog_response` TEXT NOT NULL,
//	`id_task_auxTable` INT(11) NOT NULL,
//	`pfw_wslog_id_jobSii` INT(11) NOT NULL,
//	`pfw_wslog_id_jobAPEC` VARCHAR(40) NOT NULL,
//	`pfw_wslog_dateTime` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
//	`pfw_wslog_SOAP_ok` TINYINT(1) NOT NULL,
//	`pfw_wslog_APEC_ok` TINYINT(1) NULL DEFAULT NULL,
//	`pfw_wslog_error_code` VARCHAR(40) NOT NULL DEFAULT '',
//	`pfw_wslog_error_string` VARCHAR(254) NOT NULL DEFAULT '',
//	`pfw_wslog_APEC_status` VARCHAR(40) NULL DEFAULT NULL,
//	`pfw_wslog_APEC_method` VARCHAR(40) NOT NULL,
//	PRIMARY KEY (`pfw_wslog_id`),
//	INDEX `pfw_wslog_id_jobSii` (`pfw_wslog_id_jobSii`)
//)
//COMMENT='pfw_wslog_tracking_id'
//COLLATE='latin1_swedish_ci'
//ENGINE=InnoDB;

//TODO:  make a insert value by default for field job_active.

    require_once "config.php";
    require_once "mailClass.php";
    require_once "pfwJobs.php";
    require_once "SOAP/Client.php";
    require_once "MySoapClient.php";
    require_once "XmlUtils.php";
    require_once "LoopSoapConnection.php";
    
    error_reporting(E_ALL);
    set_time_limit(600);
    $endpoint = ENDPOINT;    
    header('Content-Type: text/html; charset=UTF-8');
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
    
    
    // Delete tasks of today!!
    $composition->deleteExpiredOffers();
    
    // Get array of data and methods
    $dataXml =  $composition->getData();
    $method  =  $composition->getMethod();
    // Get Daemon Job Id and Offer Id (Sii system)
    $daemonJobId = $composition->getDaemonJobId();
    $idOfferSii  = $composition->getIdOfferSii();
    
    $loop = new LoopSoapConnection();
    $soapClient = $loop->attemptsInLoopSoapConn($endpoint);
    
    if(!$soapClient){
        die();
    }
    
//  process that gives status by id apec 
//  
//        $statusRequestXml = $composition->getStatusXml('10011/120913');
//           
//        echo "<pre>".print_r(htmlentities($statusRequestXml),true)."</pre>"; 
//        
//        $PostTransaction = $soapClient->__myDoRequest($statusRequestXml, 'getPositionStatus');

//        echo "<pre>".print_r(htmlentities($PostTransaction),true)."</pre>";
//        
//    die();
    
    foreach ($dataXml as $key => $strXml){
        echo "**METHOD:**".$method[$key];
        echo "<pre>".print_r(htmlentities($strXml),true)."</pre>"; 
       
       $PostTransaction = $soapClient->__myDoRequest($strXml, $method[$key]);
       
       echo "</br>";
       echo "<pre>".print_r(htmlentities($PostTransaction),true)."</pre>"; 
       
       $parseXml = new XmlUtils();
       $objResponse = $parseXml->XmlToSimpleObject($PostTransaction);

       echo "<pre>".print_r($objResponse,true)."</pre>";
       echo "</br>";
       echo "</br>";
       
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
                    "method"       =>   $method[$key],
                    "offerStatus"  =>   ""
           );
           $composition->log($arrData);
                  
       } else {
           
           // Log Results
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
                    "method"       =>   $method[$key],
                    "offerStatus"  =>   ""
           );
           $composition->log($arrData);
           
       }
       
    }
  
?>