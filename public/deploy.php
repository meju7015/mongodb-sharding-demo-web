<?php
$result = exec("bash -c 'sudo git pull'");
print "<pre>".$result."</pre>";