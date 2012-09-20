<?php  
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
 
    $composition = new composeXml( PARTNERID, USERID, PASSWORD ); 

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
		    
		   $composition = new composeXml( PARTNERID, USERID, PASSWORD ); 
		        
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
		   
		   $composition = new composeXml( PARTNERID, USERID, PASSWORD ); 
		    
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