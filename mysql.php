<?php 
/*
 * Creación : 2012-08-28. Alex Casaus y Alejandro Perez
 * Version-1: 2012-08-29. Alex Casaus
 * Version-2: 2012-08-30. Alex Casaus
 * Version-3: 2012-08-31. Alex Casaus
 * Puesta en Marcha: ????
 */ 
class mySQL{  
 	private $conexion;  
  	private $total_consultas;
        
 	public function mySQL(){  
  		if(!isset($this->conexion)){  
  			$this->conexion = ( mysql_connect(DB_HOSTNAME, DB_USERNAME, DB_PASSWORD) ) or die(mysql_error());  
  			mysql_select_db( DB_DATABASE, $this->conexion) or die(mysql_error());  
  		}  
  	}
        
        public function query($consulta){ 
                $this->total_consultas++; 
                $resultado = mysql_query($consulta,$this->conexion);
                if(!$resultado){ 
                  echo 'MySQL Error: ' . mysql_error();
                  exit;
                }
                return $resultado;
         }
  
 	public function getDataJob($select, $join, $where){  

 		$query = $select.''. $join.''.$where;

 		$result = mysql_query($query,$this->conexion);  
  		if(!$result){  
  			echo 'mySQL Error: ' . mysql_error();  
  			exit;  
  		}  
  		return $result;   
  	}  
  	
 	public function fetch_array($query){   
  		return mysql_fetch_array($query);  
  	}  
 
}?>
