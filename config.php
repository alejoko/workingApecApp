<?php

    define('MAIL_SEND_INFO',    'alejandro.perez@concatel.com' );
    define('MAIL_AUTH',         'alejandro.perez@concatel.com' );
    
    define('DB_HOSTNAME', 'srv106.concatel.com');
    define('DB_USERNAME', 'siiuser');
    define('DB_PASSWORD', 'user00');
    define('DB_DATABASE', 'pfwecm');
    
    // Defined TEST values. These values must change in PROD
    define( 'PARTNERID', '277');
    define( 'USERID',    'rdesnos@sii.fr');
    define( 'PASSWORD',  'K2A2P7Y9');
    
    // Defined default dates (date initial and date end)
    define( 'INIDATE', date("Y-m-d 00:00:00") );
    define( 'ENDDATE', date("Y-m-d 23:59:59") );
    
    // Defined WSDL url TEST values. These values must change in PROD
    define( 'ENDPOINT',         'http://testadepsep.apec.fr/tools?wsdl');
    define( 'WSDL_METHODS',     'http://testadepsep.apec.fr/positions?wsdl');
    define( 'ENCODING',         'iso-8859-1');
    
    // Defined number of attempts of connection and number of seconds between attempts
    define('SOAP_CONNNECTION_ATTEMPS',6);
    define('SECONDS_BETWEEN_ATTEMPS',4);
?>
