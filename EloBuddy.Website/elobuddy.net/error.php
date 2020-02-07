<!DOCTYPE html>
<html lang="en">
	<head>
		<title>Error</title>
		<style type='text/css'>
			body {
				background: #f9f9f9;
				margin: 0;
				padding: 30px 20px;
				font-family: "Helvetica Neue", helvetica, arial, sans-serif;
			}

			#error {
				max-width: 800px;
				background: #fff;
				margin: 0 auto;
			}

			h1 {
				background: #151515;
				color: #fff;
				font-size: 22px;
				font-weight: 500;
				padding: 10px;
			}

				h1 span {
					color: #7a7a7a;
					font-size: 14px;
					font-weight: normal;
				}

			#content {
				padding: 20px;
				line-height: 1.6;
			}

			#reload_button {
				background: #151515;
				color: #fff;
				border: 0;
				line-height: 34px;
				padding: 0 15px;
				font-family: "Helvetica Neue", helvetica, arial, sans-serif;
				font-size: 14px;
				border-radius: 3px;
			}
		</style>
	</head>
	<body>
		<div id='error'>
			<h1>An error occurred <span>(500 Error)</span></h1>
			<div id='content'>
				We're sorry, but a temporary technical error has occurred which means we cannot display this site right now.
				<br><br>
				<?php if ( isset( $message ) and $message ): ?>
					<em><?php echo $message; ?></em><br><br>
				<?php endif; ?>
				You can try again by clicking the button below, or try again later.
				<br><br>
				<button onclick="window.location.reload();" id='reload_button'>Try again</button>
			</div>
		</div>
	</body>
</html>