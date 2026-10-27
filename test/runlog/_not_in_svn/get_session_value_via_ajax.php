<?php
$sent_cookies = $_COOKIE;
session_start();
?>
<html>
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
	
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/1.6.1/jquery.min.js" type="text/javascript"></script>
	<script type='text/javascript'>
	
	function get_session_via_ajax()
	{
		$.ajax({
			url : 'php/get_session_value.php',
			dataType : 'json',
			success : function(doc) {
				var session_value = '<empty>';
				if (doc.session_value != '')
				{
					session_value = doc.session_value;
				}
				$('#session_value').text('Session value is: '+session_value);
				if (doc.sent_cookies != '')
				{
					$('#ajax_sent_cookies').text(doc.sent_cookies);
				}
			}
		});
	}
		
	$(document).ready(function() {
		get_session_via_ajax();
	});
					
	</script>
</head>
<body>
	<div id="session_value">Session value is: ...</div>
	<br>
	<div>Cookies sent to server:</div>
	<pre><?php echo json_encode($sent_cookies);?></pre>
	
	<div>Cookies sent to server via ajax:</div>
	<pre id="ajax_sent_cookies"></pre>
</body>
</html>