<?php
require_once dirname(__FILE__) . '/../core/class/honeywell.class.php';
 
try {
	$PROGRESS_FILE = $argv[1];
    $pythonRequests = honeywell::PYTHON_VERSION == 2 ? "python-requests" : "python3-requests";
    exec("apt-get install -y $pythonRequests");
    exec("echo 100 > $PROGRESS_FILE");
  	sleep(4);
    
    exit(0);

} catch(Exception $e) {
  echo "Exception : $e";
  exit(-1);
}

?>
