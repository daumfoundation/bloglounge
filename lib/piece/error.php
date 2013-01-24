<?php
	if(isset($error)) {
		$src_error = $skin->cutSkinTag('error');				
		
		$sp_errors = '';
		$sp_errors = $skin->parseTag('error_title', $error['title'], $src_error);
		$sp_errors = $skin->parseTag('error_description', $error['description'], $sp_errors);
		$sp_errors = $skin->parseTag('error_type', $error['type'], $sp_errors);

		$skin->dress('error', $sp_errors);
	}
?>