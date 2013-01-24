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
					$qSearch = '/search/all/' . func::encode(trim($searchKeyword));
				break;
				case 'keyword':
					$qSearch = '/search/keyword/' . func::encode(trim($searchKeyword));
				break;
				case 'archive':
					if($accessInfo['controller'] == 'day') {
						$qSearch = '/day/' . $accessInfo['action'];						
					} else {
						$qSearch = '/search/archive/' . func::encode(trim($searchKeyword));
					}
				break;
			}
		} else {
			if($accessInfo['controller'] == 'focus') {
				$qSearch = '/focus';
			}
		}

		$add = $service['path'];
		switch($accessInfo['controller']) {
			case 'group':
			case 'category':
				$add .= '/'. $accessInfo['controller'] .'/' . func::encode(trim($searchKeyword));
			break;
			case 'feedlist':
				$add .= '/feedlist';
			break;
		}

		$s_paging = $skin->parseTag('prev_page', $add.$qSearch.$paging['pageDatas'][$paging['pagePrev']], $src_paging);
		$s_paging = $skin->parseTag('next_page', $add.$qSearch.$paging['pageDatas'][$paging['pageNext']], $s_paging);
		$s_paging = $skin->parseTag('more_prev_page', ($paging['pagePrev'] == $page ? 'no_more' : 'more'), $s_paging);
		$s_paging = $skin->parseTag('more_next_page', ($paging['pageNext'] == $page ? 'no_more' : 'more'), $s_paging);

		$s_rep_paging = '';
		$src_rep_paging = $skin->cutSkinTag('paging_rep');
		for ($p=$paging['pageStart']; $p < $paging['pageEnd']+1; $p++) { 
			$sp_paging = $skin->parseTag('page_number', $p, $src_rep_paging);
			$sp_paging = $skin->parseTag('page_url', $add.$qSearch.$paging['pageDatas'][$p], $sp_paging);
			if ($p == $page) {
				$sp_paging = $skin->parseTag('page_highlight', 'selected', $sp_paging);
			} else {
				$sp_paging = $skin->parseTag('page_highlight', 'unselected', $sp_paging);
			}
			$s_rep_paging .= $sp_paging;
			$sp_paging = '';
		}

		if($paging['pageEnd'] < $paging['totalPages']) {
			$sp_paging = $skin->parseTag('page_number', $paging['totalPages'], $src_rep_paging);
			$sp_paging = $skin->parseTag('page_url', $add.$qSearch.$paging['pageDatas'][$paging['totalPages']], $sp_paging);

			$sp_paging = $skin->parseTag('page_highlight', 'last', $sp_paging);

			$s_rep_paging .= ('<span class="interword">...</span>' . $sp_paging);
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