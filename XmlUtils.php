<?php
Class XmlUtils {

    public $nameSpacesArr = array();
    public $ApecErrorCode;
    public $ApecErrorStr;
    public $idOfferApec;
    public $trackingId;
    
    public function XmlToSimpleObject($string){
            $nameSpacesArr = array();
            $findNames = explode(":",$string); 

            foreach ($findNames as $findName){
                $pos = strrpos($findName,"<");
                if ($pos !== false){
                     $xPathNamespace = substr($findName, (integer)$pos + 1);
                     if(strpos($xPathNamespace,"/")===false){
                            if(!in_array($xPathNamespace,$nameSpacesArr)){
                             $nameSpacesArr[] = $xPathNamespace; 
                             $string = str_replace( $xPathNamespace.":" , "" , $string); 
                         }
                     }
                }
            }
            $this->nameSpacesArr = $nameSpacesArr;
            return simplexml_load_string($string);
    }
    
    public function isResponseOK($SoapResponseObj){
         //TODO: code separate functions for different response request like
         //openPositionResponse or updatePositionResponse
        
        if($SoapResponseObj->Body->children() instanceof SimpleXMLElement){
                $objRequest = $SoapResponseObj->Body->children();
                    if($objRequest->children()->PayloadDisposition->EntityDisposition instanceof SimpleXMLElement){
                           $objAux = $objRequest->children()->PayloadDisposition->EntityDisposition;
                           $this->trackingId = $objRequest->children()->PayloadResponseSummary->UniquePayloadTrackingId->IdValue;
                           
                           if((string)$objAux->EntityNoException == "true"){
                                    $this->idOfferApec= $objAux->EntityIdentifier->IdValue;
                                    return true;
                               } else {
                                    $this->ApecErrorCode = $objAux->EntityException->Exception->ExceptionIdentifier;
                                    return false;
                               }
                   } else {
                                    if($objRequest->children()->Fault instanceof SimpleXMLElement){
                                        $this->ApecErrorStr = (string)$objRequest->children()->Fault->faultstring;
                                         $this->ApecErrorStr = "yeuch";
                                    }
                                    return false;
                   }
                   
                   //TODO: read error code or string of fault object (not method exception)
        } else {
              return false;
        }
        
    }
}

?>
