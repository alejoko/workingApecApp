<?php

    define('MAIL_SEND_INFO',    'rsuarez@sii.fr' );
    define('MAIL_AUTH',         'alejandro.perez@concatel.com' );
/**
 * dev environment vars    
 */
    
    define('DB_HOSTNAME', 'srv106.concatel.com');
    define('DB_USERNAME', 'siiuser');
    define('DB_PASSWORD', 'user00');
    define('DB_DATABASE', 'pfwecm');
    
      
    // Defined TEST values. These values must change in PROD
    define( 'PARTNERID', '277');
    define( 'USERID',    'rdesnos@sii.fr');
    define( 'PASSWORD',  'K2A2P7Y9');
    
    // Defined WSDL url TEST values. These values must change in PROD
    define( 'ENDPOINT',         'http://testadepsep.apec.fr/tools?wsdl');
    define( 'WSDL_METHODS',     'http://testadepsep.apec.fr/positions?wsdl');
    define( 'ENCODING',         'UTF-8');

/**
 * prod environment vars    
 */
//    define('DB_HOSTNAME', '81.252.236.86');
//    define('DB_USERNAME', 'pfwuser');
//    define('DB_PASSWORD', 'user00');
//    define('DB_DATABASE', 'pfw');
//    
//    define( 'PARTNERID', '277');
//    define( 'USERID',    'rdesnos@sii.fr');
//    define( 'PASSWORD',  'K2A2P7Y9');
//    
//    define( 'ENDPOINT',         'http://testadepsep.apec.fr/tools?wsdl');
//    define( 'WSDL_METHODS',     'http://testadepsep.apec.fr/positions?wsdl');
//    define( 'ENCODING',         'UTF-8');

 /**
 * end environment vars    
 */
    // Defined number of attempts of connection and number of seconds between attempts
    define('SOAP_CONNNECTION_ATTEMPS',30);
    define('SECONDS_BETWEEN_ATTEMPS',6);
    
    define('SECONDS_SLEEP_AND_CHECK',90);
    define('TIME_SCRIPT_EXECUTION_LIMIT',2000);
?>
