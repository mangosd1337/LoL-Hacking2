<?php

header("HTTP/1.0 404 Not Found");

print <<<EOF
<html>
	<title>404 Not Found</title>
	<body>
		The file you were looking for could not be found
	</body>
</html>
EOF;

exit;
