<?php

//TODO: alter mysql in prod by this:
//ALTER TABLE  `pfw_5_job`
//ADD COLUMN  `job_vacancy` INT(10) NOT NULL AFTER  `job_type`
//ALTER TABLE `pfw_5_job`
//ADD COLUMN `job_exportAPEC` TINYINT(1) NOT NULL AFTER `job_vacancy`
//ALTER TABLE `aux_pfw_id_sii_apec`
//ADD COLUMN `aux_offer_status` VARCHAR(40) NOT NULL AFTER `aux_apec_id`
//ALTER TABLE `pfw_5_webservice_log`
//ADD COLUMN `pfw_wslog_offer_status` VARCHAR(40) NOT NULL AFTER `pfw_wslog_APEC_method`
//
//TODO: create table
//DROP TABLE IF EXISTS `aux_pfw_job`;
//CREATE TABLE `aux_pfw_job` (
//	`aux_pfw_id` INT(11) NOT NULL AUTO_INCREMENT,
//	`aux_job_id` INT(11) NOT NULL,
//	`aux_job_date` DATE NOT NULL,
//	`aux_job_time` TIME NOT NULL,
//	`aux_job_flag_make` TINYINT(1) NOT NULL DEFAULT '0',
//	`aux_job_operation` VARCHAR(20) NOT NULL,
//	`aux_job_trigger_sentence` VARCHAR(20) NOT NULL DEFAULT '',
//	PRIMARY KEY (`aux_pfw_id`)
//)
//COLLATE='utf8_general_ci'
//ENGINE=InnoDB
//AUTO_INCREMENT=5;
//
//ALTER TABLE `aux_pfw_job` ADD UNIQUE INDEX `dateJobIdOperation` (`aux_job_operation`,`aux_job_id`,`aux_job_date`);

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
//	`pfw_wslog_APEC_ok` TINYINT(1) NOT NULL,
//	`pfw_wslog_error_code` VARCHAR(40) NOT NULL DEFAULT '',
//	`pfw_wslog_error_string` VARCHAR(254) NOT NULL DEFAULT '',
//	`pfw_wslog_APEC_status` VARCHAR(40) NULL DEFAULT NULL,
//	`pfw_wslog_APEC_method` VARCHAR(40) NOT NULL,
//	`pfw_wslog_offer_status` VARCHAR(40) NOT NULL,
//	PRIMARY KEY (`pfw_wslog_id`),
//	INDEX `pfw_wslog_id_jobSii` (`pfw_wslog_id_jobSii`)
//)
//COMMENT='pfw_wslog_tracking_id'
//COLLATE='latin1_swedish_ci'
//ENGINE=InnoDB
//AUTO_INCREMENT=71;
//TODO:  make a insert value by default for field job_active.
  
    error_reporting(E_ERROR & ~E_COMPILE_ERROR);
    
    require_once "config.php";
    require_once "mailClass.php";
    require_once "pfwJobs.php";
    require_once "SOAP/Client.php";
    require_once "MySoapClient.php";
    require_once "XmlUtils.php";
    require_once "LoopSoapConnection.php";
    
    set_time_limit(TIME_SCRIPT_EXECUTION_LIMIT);
    
    $endpoint = ENDPOINT;    
    header('Content-Type: text/html; charset=UTF-8');
 
    
    if (isset($_GET['idate_day']) && isset($_GET['idate_month']) && isset($_GET['idate_year']) 
    	&& ($_GET['idate_day']!= null) && ($_GET['idate_month']!= null) && ($_GET['idate_year']!= null)
    	&& ($_GET['idate_day'] >= 1 && $_GET['idate_day'] <= 31)
    	&& ($_GET['idate_month'] >= 1 && $_GET['idate_month'] <= 12) 
    	&& ($_GET['idate_year'] >= 1900))
    {
    	if($_GET['idate_day']<10){
    		$_GET['idate_day'] = "0".$_GET['idate_day'];
    	}
    	if($_GET['idate_month']<10){
    		$_GET['idate_month'] = "0".$_GET['idate_month'];
    	}
    	$idate = $_GET['idate_year']."-".$_GET['idate_month']."-".$_GET['idate_day']." 00:00:00";
    }
    
    if (isset($_GET['edate_day']) && isset($_GET['edate_month']) && isset($_GET['edate_year']) 
    	&& ($_GET['edate_day']!= null) && ($_GET['edate_month']!= null) && ($_GET['edate_year']!= null)
    	&& ($_GET['edate_day'] >= 1 && $_GET['edate_day'] <= 31)
    	&& ($_GET['edate_month'] >= 1 && $_GET['edate_month'] <= 12) && ($_GET['edate_year'] >= 1900))
    {
    	if($_GET['edate_day']<10){
    		$_GET['edate_day'] = "0".$_GET['edate_day'];
    	}
    	if($_GET['edate_month']<10){
    		$_GET['edate_month'] = "0".$_GET['edate_month'];
    	}
    	$edate = $_GET['edate_year']."-".$_GET['edate_month']."-".$_GET['edate_day']." 23:59:59";
    }

   if(!isset($idate) && !isset($edate) || (($idate == null) || ($edate == null))){
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
                    $idate,
                    $edate
                ); 
    }
    
    try{
    	
	    $semaphore = $composition->getSemaphore(); 
	
	    if($semaphore == 0){
	       
	       echo "************************** MAKING NEW XML COMPOSITION *********************************";
		   echo "<br/>";
		   echo "FIRS PART: WE CHECK STATUS OF OUR 'AVALIDER' (DANGER STATE) OFFERS";
		   echo "<br/>";
	    	
	       $semaphore = $composition->setSemaphore(1);
	       	
		   echo "SECOND PART: WE CREATE TASKS FOR DELETE EXPIRED OFFERS";
		   echo "<br/>";
	    
		    // Delete tasks of today!!
		    $composition->deleteExpiredOffers();
		 
		    $loop = new LoopSoapConnection();
		    $soapClient = $loop->attemptsInLoopSoapConn($endpoint);
		    
		    // Process that gives status by id apec 
		    $offerAValidee = $composition->getApecOfferAValidee(); 
		    
		
		  foreach ($offerAValidee as $result) {
		       	
		   $idOfferApec = $result['aux_apec_id'];
		 
		    $statusRequestXml = $composition->getStatusXml($idOfferApec); 
		    
		       echo "<pre>".print_r(htmlentities($statusRequestXml),true)."</pre>";
		       
		       $PostTransaction = $soapClient->__myDoRequest($statusRequestXml, 'getPositionStatus');
		       
		       $parseXml = new XmlUtils();
		       $objResponse = $parseXml->XmlToSimpleObject($PostTransaction);
		
		       echo "<pre>".print_r($objResponse,true)."</pre>";
		       
		   	   $statusOffer = $objResponse->Body->getPositionStatusResponse;
		           if ( ($statusOffer instanceof SimpleXMLElement) && (strlen((string)$statusOffer)>0) ) {
		               
		               $composition->setStatusOffer((string)$statusOffer, $idOfferApec);
		                // log results
		                $arrData = array(
		                         "tracking_id"  =>   $composition->trackingId,
		                         "request"      =>   $statusRequestXml,
		                         "response"     =>   $PostTransaction,
		                         "daemonTaskId" =>   0,
		                         "idSii"        =>   $composition->getSiiOfferId($idOfferApec),
		                         "idApec"       =>   $idOfferApec,
		                         "SOAPOK"       =>   1,
		                         "APECOK"       =>   1,
		                         "errorCode"    =>   0,
		                         "errorString"  =>   "",
		                         "method"       =>   "getPositionStatus",
		                         "offerStatus"  =>   (string)$statusOffer
		                );
		                $composition->log($arrData);
			   } 
		           
		    }
		    
		   echo "************************** MAKING NEW XML COMPOSITION *********************************";
		   echo "<br/>";
		   echo "THIRD PART: WE MAKE THE TASKS WE ASSIGNED IN TABLE";
		   echo "<br/>";
		    
		   if(!isset($idate) && !isset($edate) || (($idate == null) || ($edate == null))){
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
		                    $idate,
		                    $edate
		                ); 
		    }
		        
		     // Get array of data and methods
		    $dataXml =  $composition->getData();
		    $method  =  $composition->getMethod();
		    // Get Daemon Job Id and Offer Id (Sii system)
		    $daemonJobId = $composition->getDaemonJobId();
		    $idOfferSii  = $composition->getIdOfferSii();
		    
		    $idApecTransactionOk = array();
		 
		    foreach ($dataXml as $key => $strXml){
		        
		        echo "<pre>".print_r(htmlentities($strXml),true)."</pre>"; 
		       
		       $PostTransaction = $soapClient->__myDoRequest($strXml, $method[$key]);
		       
		       $parseXml = new XmlUtils();
		       $objResponse = $parseXml->XmlToSimpleObject($PostTransaction);
		
		       echo "<pre>".print_r($objResponse,true)."</pre>";
		       
		       
		       
		       if($parseXml->isResponseOK($objResponse)){
		           
		           $idApecTransactionOk[] = $parseXml->idOfferApec;
		           
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
		    
		   echo "************************** MAKING NEW XML COMPOSITION *********************************";
		   echo "<br/>";
		   echo "FOURTH PART: WE CHECK THE STATUS OF THE OFFERS WE HAVE PLAY TODAY AFTER SLEEP";
		   echo "<br/>";
		   
		    sleep(SECONDS_SLEEP_AND_CHECK);
		   
		      if(!isset($idate) && !isset($edate) || (($idate == null) || ($edate == null))){
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
		                    $idate,
		                    $edate
		                ); 
		    }
		    
		        if( (is_array($idApecTransactionOk)) && (count($idApecTransactionOk)>0) ){
		            
		                foreach ($idApecTransactionOk as $idApec){
		                    
		                        $statusRequestXml = $composition->getStatusXml($idApec);
		
		                        echo "<pre>".print_r(htmlentities($statusRequestXml),true)."</pre>";
		                        $PostTransaction = $soapClient->__myDoRequest($statusRequestXml, 'getPositionStatus');
		
		                       $parseXml = new XmlUtils();
		                       $objResponse = $parseXml->XmlToSimpleObject($PostTransaction);
		
		                       echo "<pre>".print_r($objResponse,true)."</pre>";
		                       
		                           $statusOffer = $objResponse->Body->getPositionStatusResponse;
		                           if ( ($statusOffer instanceof SimpleXMLElement) && (strlen((string)$statusOffer)>0) ) {
		
		                               $composition->setStatusOffer((string)$statusOffer, $idApec);
		
		                                // log results
		                                $arrData = array(
		                                         "tracking_id"  =>   $composition->trackingId,
		                                         "request"      =>   $statusRequestXml,
		                                         "response"     =>   $PostTransaction,
		                                         "daemonTaskId" =>   0,
		                                         "idSii"        =>   $composition->getSiiOfferId($idApec),
		                                         "idApec"       =>   $idApec,
		                                         "SOAPOK"       =>   1,
		                                         "APECOK"       =>   1,
		                                         "errorCode"    =>   0,
		                                         "errorString"  =>   "",
		                                         "method"       =>   "getPositionStatus",
		                                         "offerStatus"  =>   (string)$statusOffer
		                                );
		                                $composition->log($arrData);
		
		                           }
		                           
		                 }  
		        }
		        $semaphore = $composition->setSemaphore(0);
	    }else{
	       
	       echo "************************** ACTION NOT COMPLETED *********************************";
		   echo "<br/>";
		   echo "ANOTHER APPLICATION INSTANCE IS IN PROGRESS. PLEASE, YOU MUST WAIT TO FINISH IT.THANKS.";
		   echo "<br/>";
		   echo "*********************************************************************************";
	    }
    
    }catch(Exception $e){

    	$semaphore = $composition->setSemaphore(0);
        echo $e->getMessage(); 
    	
    }

  
?>