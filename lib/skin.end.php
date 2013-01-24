<?php
	// 페이징
	$s_paging = '';
	$src_paging = $skin->cutSkinTag('paging');

	// paging	

	if(isset($paging)) {

	/*	$qSearch = htmlspecialchars((($searchType=='blogURL')) ? '&blogURL='.urlencode(trim($searchKeyword)) : ((($searchType=='tag')) ? '&tag='.urlencode(trim($searchKeyword)) : ((($searchType=='all')) ? '&keyword='.urlencode(trim($searchKeyword)) : '')));*/

		$qSearch = '';
		
		if(!Validator::is_empty($searchKeyword)) {
			switch($searchType) {
				case 'blogURL':
					if($accessInfo['controller'] == 'blog') {
						$qSearch = '/blog/' . $accessInfo['action'];
					} else {
						$qSearch = '/search/blogURL/' . func::encode(trim($searchKeyword));
					}
				break;
				case 'tag':
					$qSearch = '/search/tag/' . func::encode(trim($searchKeyword));
				break;
				case 'all':
					$qSearch = '/search/keyword/' . func::encode(trim($searchKeyword));
				break;
				case 'archive':
					$qSearch = '/search/archive/' . func::encode(trim($searchKeyword));
				break;
			}
		} else {
			if($accessInfo['controller'] == 'focus') {
				$qSearch = '/focus';
			}
		}

		$add = $service['path'];
		switch($accessInfo['controller']) {
			case 'category':
				$add .= '/category/' . func::encode(trim($searchKeyword));
			break;
			case 'feedlist':
				$add .= '/feedlist';
			break;
		}

		$s_paging = $skin->parseTag('prev_page', $add.$qSearch.'/?page='.$paging['pagePrev'], $src_paging);
		$s_paging = $skin->parseTag('next_page', $add.$qSearch.'/?page='.$paging['pageNext'], $s_paging);

		$s_rep_paging = '';
		$src_rep_paging = $skin->cutSkinTag('paging_rep');
		for ($p=$paging['pageStart']; $p < $paging['pageEnd']+1; $p++) { 
			$sp_paging = $skin->parseTag('page_number', $p, $src_rep_paging);
			$sp_paging = $skin->parseTag('page_url', $add.$qSearch.'/?page='.$p, $sp_paging);
			if ($p == $page) {
				$sp_paging = $skin->parseTag('page_highlight', 'selected', $sp_paging);
			} else {
				$sp_paging = $skin->parseTag('page_highlight', 'unselected', $sp_paging);
			}
			$s_rep_paging .= $sp_paging;
			$sp_paging = '';
		}
		$s_paging = $skin->dressOut('paging_rep', $s_rep_paging, $s_paging);
		$skin->dress('paging',$s_paging);
	} else {
		$skin->dress('paging', '');
	}
	// ncloud : pluginEvent
	$event->handleTags();
	// ncloud end
	
	$event->on('Meta.skinEnd');

	$skin->output = str_replace('</body>', func::printFootHTML(), $skin->output);
	$skin->output = str_replace('</body>', $event->on('Disp.foot')."\n</body>\n", $skin->output);
	$skin->clearScopes();
	$skin->clearSkinTags();  // ncloud
	$skin->flush();
    flush();
?>