<?php 
/*
 * Cron launched daily to treat SII group's offers in the APEC (France)
 * Created     : 2012-08-28 by Alejandro Perez & Alex Casaus 
 * Last Version: 2012-09-06 by Alejandro Pérez
 */
include("mysql.php");


class composeXml{ 
    
                private $method = array();
                private $daemonJobId = array();
                private $db_query;
                private $idOfferSii;
                private $partnerId;
                private $userId;
                private $password;
                private $inidate;
                private $enddate;
                
                public function __construct($partner,$user,$pwd,$idate,$edate){
			       $this->partnerId = $partner;
			       $this->userId = $user;
			       $this->password = $pwd;
			       $this->inidate = $idate;
			       $this->enddate = $edate;
                   
			       $this->db_query = new mySQL();
			    } 
                
                    
                public function getData(){

                        // DATA for build authentication element
                        $md5 = md5("id1=$this->userId&id2=$this->partnerId&pass=".$this->password."eRecrutement");

                        //SELECT Content
                        $select = "SELECT ";
                        $required_date_select = array (                 
                                                                        'aux.aux_pfw_id as daemonJobId',
                                                                        'aux.aux_job_operation as operation',
                                                                        'job.pfwid',
                                                                        'job_ref', 
                                                                        'DATE(job_expirationdate) AS job_expirationDateFormat',
                                                                        'job_profile',
                                                                        'job_expirationdate',
                                                                        'job_title', 
                                                                        'job_description',
                                                                        'job_remuneration',
                                                                        'job_experience',
                                                                        'job_vacancy',
                                                                        'jobdomain_codactivity',
                                                                        'jobdomain_name',
                                                                        'jobarea_location'
                                                                   );
                        for ($i=0;$i<count($required_date_select); $i++) {
                                if ($i==count($required_date_select)-1){ 
                                        $select .= $required_date_select[$i].' FROM aux_pfw_job AS aux';
                                }else{
                                        $select .= $required_date_select[$i].',';
                                }
                        }
                        //LEFT JOIN Content
                        $join = " LEFT JOIN pfw_5_job AS job ON aux.aux_job_id = job.pfwid";
                        $join .= " LEFT JOIN pfw_5_jobdomain AS domain ON job.job_domain = domain.pfwid";
                        $join .= " LEFT JOIN pfw_5_jobarea AS area ON job.job_area = area.pfwid";
                        //WHERE Content
                        $where = " WHERE 1";
                        $where .= " AND aux.aux_job_flag_make = 0";
                        $where .= " AND DATE(job.job_expirationdate) >= DATE( NOW() )";
                        $where .= " AND DATE(aux.aux_job_datetime) BETWEEN '".$this->inidate."' AND '".$this->enddate."'";

                        echo $select.$join.$where."\n";
                        $query = $this->db_query->getDataJob($select, $join, $where);  
                        
                        //XML Building
                        $requestXML = array();


                        while($result = $this->db_query->fetch_array($query)) {
                           
                           echo "<br/>mysql fetch_array<br/>";
                           $timeStamp = strtotime(date('Y-m-d H:i:s'));
                           $randTimeStamp = rand(0,$timeStamp);
                           $idTransaction = $randTimeStamp.$timeStamp.$this->partnerId;
                           echo $idTransaction."<br/>";
                           
                           // set job daemon id and offer id 
                           $this->pushDaemonJobId($result['daemonJobId']);
                           $this->pushIdOfferSii($result['pfwid']);
                        
                          $job_remuneration = substr($result['job_remuneration'], 0, 29);
                          if(trim($result['job_remuneration'])==""){
                                 $job_remuneration  = "Ã  nÃ©gocier";    
                          }
                           
                          switch ($result['job_experience']){
                          	
                                case 3: // 0 - 2 Years
                                        $job_experience = 1;
                                        $basepay_min = 0;
                                        $basepay_max = 1000;
                                break;
                                
                                case 1: // Indifferent
                                case 2: // Indifferent
                                case 6: // Indifferent
                                        $job_experience = 2;
                                        $basepay_min = 1000;
                                        $basepay_max = 2000;
                                break;
                                
                                case 4: // 2 - 5 Years
                                case 5: // > 5 Years
                                        $job_experience = 3;
                                        $basepay_min = 2000;
                                        $basepay_max = 3000;
                                break;
                                
                          }
                        
                          switch($result['operation']){
                          	
                              case "insert":
                                  $requestType = "openPositionRequest";
                                  $XMLBodyType = "A";
                                  $this->pushMethod("openPosition");
                                  // generate the id for apec SYS
                                  $timeStamp = str_replace("-", "", date("y-m-d") );
                                  $idAPEC = $result['pfwid']."/".$timeStamp;
                                  break;
                                  
                              case "update":
                                  $requestType = "updatePositionRequest";
                                  $XMLBodyType = "A";
                                  $this->pushMethod("updatePosition");
                                  // read the id for apec SYS
                                  $idAPEC = $this->getApecOfferId($result['pfwid']);
                                  break;
                                  
                              case "delete":
                                  $requestType = "updatePositionStatusRequest";
                                  $XMLBodyType = "B";
                                  $status = "FERMEE";
                                  $this->pushMethod("updatePositionStatus");
                                  // read the id for apec SYS
                                  $idAPEC = $this->getApecOfferId($result['pfwid']);
                                  break;
                                  
                              case "suspend":
                                  $requestType = "updatePositionStatusRequest";
                                  $XMLBodyType = "B";
                                  $status = "SUSPENDUE";
                                  $this->pushMethod("updatePositionStatus");
                                  // read the id for apec SYS
                                  $idAPEC = $this->getApecOfferId($result['pfwid']);
                                  break;
                                  
                              case "publish":
                                  $requestType = "updatePositionStatusRequest";
                                  $XMLBodyType = "B";
                                  $status = "PUBLIEE";
                                  $this->pushMethod("updatePositionStatus");
                                  // read the id for apec SYS
                                  $idAPEC = $this->getApecOfferId($result['pfwid']);
                                  break;
                                  
                          }
 
                          switch($XMLBodyType){
                          	
                              case "A":
                                                $requestXML[] ='
                                                    <S:Envelope xmlns:S="http://schemas.xmlsoap.org/soap/envelope/">
                                                      <S:Body>
                                                          <ns2:'.$requestType.' xmlns:ns2="http://adep.apec.fr/hrxml/sep" xmlns:ns3="http://ns.hr-xml.org/2006-02-28">

                                                             <ns2:authentication>
                                                                   <ns2:userId>'.$this->userId.'</ns2:userId>
                                                                   <ns2:partnerId>'.$this->partnerId.'</ns2:partnerId>
                                                                   <ns2:md5Key>'.$md5.'</ns2:md5Key>
                                                            </ns2:authentication>

                                                             <ns2:UniquePayloadTrackingId idOwner="CLIENT">
                                                                 <ns3:IdValue>'.$idTransaction.'</ns3:IdValue>
                                                             </ns2:UniquePayloadTrackingId>

                                                              <ns2:position>
                                                                      <ns3:PositionSupplier relationship="self"></ns3:PositionSupplier>
                                                                              <ns3:PositionProfile xml:lang="fr">
                                                                                      <ns3:ProfileId idOwner="CLIENT">
                                                                                              <ns3:IdValue>'.$idAPEC.'</ns3:IdValue>
                                                                                      </ns3:ProfileId>
                                                                                      <ns3:ProfileName>APEC</ns3:ProfileName>
                                                                                      <ns3:PositionDateInfo>
                                                                                          <ns3:StartAsSoonAsPossible>true</ns3:StartAsSoonAsPossible>
                                                                                          <ns3:MaximumEndDate>'.$result['job_expirationDateFormat'].'</ns3:MaximumEndDate>
                                                                                      </ns3:PositionDateInfo>
                                                                                      <ns3:Organization>
                                                                                              <ns3:ContactInfo>
                                                                                                      <ns3:ContactMethod>
                                                                                                              <ns3:InternetEmailAddress>rsuarez@sii.fr</ns3:InternetEmailAddress>
                                                                                                              <ns3:InternetWebAddress>http://www.groupe-sii.com/fr/offres_emploi/'.$result['pfwid'].'</ns3:InternetWebAddress>
                                                                                                      </ns3:ContactMethod>
                                                                                              </ns3:ContactInfo>
                                                                                      </ns3:Organization>
                                                                                      <ns3:PositionDetail>
                                                                                              <ns3:IndustryCode classificationName="INSEE">721Z</ns3:IndustryCode>
                                                                                              <ns3:PhysicalLocation>
                                                                                                      <ns3:Name>LOCATION_NAME</ns3:Name>
                                                                                                      <ns3:Area>
                                                                                                              <ns3:Value>'.utf8_encode($result['jobarea_location']).'</ns3:Value>
                                                                                                      </ns3:Area>
                                                                                              </ns3:PhysicalLocation>
                                                                                              <ns3:PhysicalLocation>
                                                                                                  <ns3:Name>LOCATION_CODE</ns3:Name>
                                                                                                  <ns3:Area>
                                                                                                      <ns3:Value>ES</ns3:Value>
                                                                                                  </ns3:Area>
                                                                                              </ns3:PhysicalLocation> 
                                                                                              <ns3:PositionTitle>'.utf8_decode($result['job_title']).'</ns3:PositionTitle>
                                                                                              <ns3:PositionClassification>Direct Hire</ns3:PositionClassification>
                                                                                              <ns3:Competency name="GLOBAL_EXPERIENCE_LEVEL">
                                                                                                      <ns3:CompetencyEvidence>
                                                                                                              <ns3:StringValue>best employer we can. </ns3:StringValue>
                                                                                                      </ns3:CompetencyEvidence>
                                                                                              </ns3:Competency>
                                                                                              <ns3:RemunerationPackage>
                                                                                                      <ns3:BasePay>
                                                                                                              <ns3:BasePayAmountMin>'.$basepay_min.'</ns3:BasePayAmountMin>
                                                                                                              <ns3:BasePayAmountMax>'.$basepay_max.'</ns3:BasePayAmountMax>
                                                                                                      </ns3:BasePay>
                                                                                                      <ns3:OtherPay>
                                                                                                              <ns3:OtherPayCalculation>'.utf8_decode($job_remuneration).'</ns3:OtherPayCalculation>
                                                                                                      </ns3:OtherPay>
                                                                                              </ns3:RemunerationPackage>
                                                                                      </ns3:PositionDetail>
                                                                                      <ns3:FormattedPositionDescription>
                                                                                              <ns3:Name>POSITION_TYPE</ns3:Name>
                                                                                              <ns3:Value>ODD</ns3:Value>
                                                                                      </ns3:FormattedPositionDescription>
                                                                                      <ns3:FormattedPositionDescription>
                                                                                              <ns3:Name>POSITION_DESCRIPTION</ns3:Name>
                                                                                              <ns3:Value>'.utf8_decode(strip_tags($result['job_description'])).'</ns3:Value>
                                                                                      </ns3:FormattedPositionDescription>
                                                                                      <ns3:FormattedPositionDescription>
                                                                                              <ns3:Name>POSITION_DISPLAY_LOGO</ns3:Name>
                                                                                              <ns3:Value>false</ns3:Value>
                                                                                      </ns3:FormattedPositionDescription>
                                                                              </ns3:PositionProfile>
                                                                              <ns3:NumberToFill>'.utf8_decode($result['job_vacancy']).'</ns3:NumberToFill>
                                                                      </ns2:position>
                                                                 </ns2:'.$requestType.'>
                                                          </S:Body>
                                                       </S:Envelope> ';	

                              break;
                              
                              case "B":
                                        $requestXML[] ='
                                                   <S:Envelope xmlns:S="http://schemas.xmlsoap.org/soap/envelope/">
                                                        <S:Body>

                                                          <ns2:'.$requestType.' xmlns:ns2="http://adep.apec.fr/hrxml/sep" xmlns:ns3="http://ns.hr-xml.org/2006-02-28">

                                                             <ns2:authentication>
                                                                   <ns2:userId>'.$this->userId.'</ns2:userId>
                                                                   <ns2:partnerId>'.$this->partnerId.'</ns2:partnerId>
                                                                   <ns2:md5Key>'.$md5.'</ns2:md5Key>
                                                            </ns2:authentication>

                                                             <ns2:UniquePayloadTrackingId idOwner="CLIENT">
                                                                 <ns3:IdValue>'.$idTransaction.'</ns3:IdValue>
                                                             </ns2:UniquePayloadTrackingId>

                                                             <ns2:clientPositionId idOwner="CLIENT">
                                                                    <ns3:IdValue>'.$idAPEC.'</ns3:IdValue>
                                                             </ns2:clientPositionId>

                                                             <ns2:newPositionStatus>'.$status.'</ns2:newPositionStatus>

                                                          </ns2:'.$requestType.'>

                                                        </S:Body>
                                                    </S:Envelope>
                                        ';
                              break;
                              
                          }
                        }

                //        $temp = array();
                //        $temp[] = $requestXML[0];
                //        return $temp;

                        return $requestXML;
                }
 
                public function getMethod(){
                    return $this->method; 
                }
                
                public function pushMethod($selMethod){
                    $this->method[] = $selMethod;
                }
                
                public function getDaemonJobId(){
                    return $this->daemonJobId; 
                }
                
                public function pushDaemonJobId($jobid){
                    $this->daemonJobId[] = $jobid;
                }
                
                public function getIdOfferSii(){
                    return $this->idOfferSii; 
                }
                
                public function pushIdOfferSii($id){
                    $this->idOfferSii[] = $id;
                }
                
                public function setApecOfferId($idSii,$idApec){
                    $sql="INSERT INTO aux_pfw_id_sii_apec (aux_sii_id,aux_apec_id) VALUES (".$idSii.",'".$idApec."')";
                    $this->db_query->query($sql);
                }
                
                public function getSiiOfferId($idApec){
                    $sql="SELECT  aux_sii_id FROM aux_pfw_id_sii_apec WHERE aux_apec_id='".$idApec."' limit 1";
                    $res = $this->db_query->query($sql);
                    $dataset=$this->db_query->fetch_array($res);
                    return $dataset["aux_sii_id"];
                }
                
                public function getApecOfferId($idSii){
                     $sql="SELECT  aux_apec_id FROM aux_pfw_id_sii_apec WHERE aux_sii_id='".$idSii."' ORDER BY aux_apec_id DESC limit 1";
                    $res = $this->db_query->query($sql);
                    $dataset=$this->db_query->fetch_array($res);
                    return $dataset["aux_apec_id"];
                }
                
                public function setWorkOk($TableAuxJobId){ 
                    $sql = "UPDATE aux_pfw_job SET aux_job_flag_make=1 WHERE aux_pfw_id='".$TableAuxJobId."'";
                    $this->db_query->query($sql);
                }
                
                public function openSameOfferOneMonth($idSii){
                    $fecha = date("Y-m-d");
                    $enunmes = explode ( "-", $fecha );     
                    $sumaunmes = mktime ( 0, 0, 0, date("$enunmes[1]") + 1, date("$enunmes[2]") + 1, date("$enunmes[0]") );
                    $dateInAMonth = date ("Y-m-d", $sumaunmes);
                    $sql="INSERT INTO aux_pfw_job (aux_job_id,aux_job_datetime,aux_job_flag_make,aux_job_operation) 
                        VALUES (".$idSii.",'".$dateInAMonth." 12:00:00',0,'insert')";
                    $this->db_query->query($sql);
                }
                
                public function deleteExpiredOffers(){
                      
                        $this->db_query = new mySQL();	
                        
                        $select =" SELECT pfwid as id FROM pfw_5_job ";
                        $join = "";
                        $where = " WHERE 1";
                        $where .= " AND job_exportAPEC = 1";
                        $where .= " AND job_active = 1 ";
                        $where .= " AND DATE(job_expirationdate) = DATE( NOW() )";

                        echo $select.$join.$where."\n";
                        
                        $query = $this->db_query->getDataJob($select, $join, $where); 
                        
                        while($result = $this->db_query->fetch_array($query)) {
                            $sql = " INSERT INTO aux_pfw_job (aux_job_id,  aux_job_datetime, aux_job_flag_make, aux_job_operation) VALUES(".$result["id"]." , NOW(), 0, 'delete')";
                            $this->db_query->query($sql);
                        }
                }
                
                public function log($arrData){
                        
                        $arrData["APECStatus"] = "";
                         
                        $tracking_id    = $arrData["tracking_id"];
                        $request        = mysql_real_escape_string($this->toISO($arrData["request"]));
                        $response       = mysql_real_escape_string($this->toISO($arrData["response"]));
                        $daemonTaskId   = $arrData["daemonTaskId"];
                        $idSii          = $arrData["idSii"];
                        $idApec         = $arrData["idApec"];
                        $SOAPOK         = $arrData["SOAPOK"];
                        $APECOK         = $arrData["APECOK"];
                        $errorCode      = $arrData["errorCode"];    
                        $errorString    = mysql_real_escape_string($this->toISO($arrData["errorString"]));       
                        $APECStatus     = mysql_real_escape_string($this->toISO($arrData["APECStatus"]));       //uso futuro
                        $method         = mysql_real_escape_string($this->toISO($arrData["method"]));
                        
                       echo  $sql = " INSERT INTO pfw_5_webservice_log 
                        (`pfw_wslog_tracking_id` ,
                        `pfw_wslog_request` ,
                        `pfw_wslog_response` ,
                        `id_task_auxTable` ,
                        `pfw_wslog_id_jobSii` ,
                        `pfw_wslog_id_jobAPEC` ,
                        `pfw_wslog_dateTime`,
                        `pfw_wslog_SOAP_ok` ,
                        `pfw_wslog_APEC_ok` ,
                        `pfw_wslog_error_code` ,
                        `pfw_wslog_error_string` ,
                        `pfw_wslog_APEC_status` ,
                        `pfw_wslog_APEC_method`) 
                        VALUES(
                        '".$tracking_id."', 
                        '".$request."' ,
                        '".$response."', 
                        ".$daemonTaskId.",    
                        ".$idSii." ,
                        '".$idApec."' , 
                        NOW(),
                        ".$SOAPOK." ,
                        ".$APECOK." , 
                        '".$errorCode."' ,
                        '".$errorString."' , 
                        '".$APECStatus."' ,
                        '".$method."' 
                        )";
                       
                        $this->db_query->query($sql);
                }
                
               public   function toISO($cadena){ 
                  //It converts from UTF-8 to ISO-8859-1 
                  $cadena = mb_convert_encoding($cadena, "ISO-8859-1", "UTF-8"); 
                  return $cadena; 
                } 
}
?> 