블로그라운지 (Bloglounge)
===========

비영리단체들이 메타사이트를 쉽게 구축 운영할 수 있는 솔루션을 개발/보급하고 있습니다. 설치형 메타 블로그 구축툴인 블로그라운지를 통해 별도의 비용없이 편리하게 메타 사이트를 구축하실 수 있으며 일반 공중 라이센스(GPL)로 배포되어 누구든지 자유롭게 이용할 수 있으며, 수정/배포도 가능합니다.

권장사양 (Recommend Specifications)
===========
- Apache 1.3 ( mod_rewrite 필수 ) 이상

- PHP 4.3.0 이상

- MySQL  4.1 이상

안내사항
===========

이번 버전은 그동안 발견된 오류와 새로운기능들을 안정화한 버전입니다.

업그레이드시 하단의 업그레이드법의 정석(?) 대로 설치해주시길 바립니다.

설치법 ( 자세한 정보는 하단의 링크를 참고하세요. )

http://bloglounge.itcanus.net/bloglounge_download/6987

업그레이드법 ( 자세한 정보는 하단의 링크를 참고하세요. )

http://bloglounge.itcanus.net/bloglounge_notice/6981

변경사항
===========

- IE6 호환 : 관리자 로그인시 스크립트 에러, 관리자페이지 상단메뉴 에러 수정

- 추가 : /lib/config.php ADMIN_MENU_CLICK_VIEW 추가 
       
true 시 관리자페이지 상단메뉴 클릭하여 메뉴 이동,  false 시 마우스 오버시 메뉴이동 ( 기본 false )

- 추가 : 리스트에 보여지는 글만 저장하는 옵션 추가 ( 관리자/설정 )  최대 1000자의 본문만 저장하는 기능 
       
http://bloglounge.itcanus.net/bloglounge_qna/17381/

- 수정 : 관리자페이지 디자인/스킨설정에서 포커스, 글 본문 갯수 최대 3000자에서 1000자로 변경

- 오류 : 분류갯수 오류 수정 ( 글, 블로그 삭제시 숫자오류 )

- 오류 : 스킨 다음 클릭시 현페이지에서 다음페이지가 아닌,  + 5페이지로 이동하는 문제 수정

- 오류 : 간혹 글의 썸네일이 겹쳐지는 문제 해결

http://bloglounge.itcanus.net/?mid=bloglounge_qna&document_srl=17724&rnd=17883

- 오류 : RSS 재출력 개수 오류 수정

http://bloglounge.itcanus.net/bloglounge_qna/17821

- 오류 : 매거진 플러그인 작성자 오류 수정

http://bloglounge.itcanus.net/bloglounge_qna/17771

- 그외 : 세세한 버그수정, 코드수정으로 안정화하였습니다.

- 오류 : PHP4 호환성 추가 ( htmlspecialchars_decode )

http://bloglounge.itcanus.net/bloglounge_qna/17855

- 오류 : 분류설정이 목록보기와, 자세히 보기가 달라.. 제대로 분류변경이 되지 않던 문제 수정

http://bloglounge.itcanus.net/bloglounge_qna/17794