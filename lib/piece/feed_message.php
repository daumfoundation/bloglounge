<?php			
	$src_condMessage = $skin->cutSkinTag('cond_message');

	$src_condSearchAll = $skin->cutSkinTag('cond_feedlist');	
	$src_condMessage = $skin->dressOn('cond_feedlist', $src_condSearchAll, $src_condSearchAll, $src_condMessage);
	
	$skin->dress('cond_message', $src_condMessage);
	
?>