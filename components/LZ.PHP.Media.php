<?php
	/*
		Thumbnail image creator for Wing!
		Special thanks to : hanyoung, crizin
	*/

	// imagecopyresampled requres gd 2.0.1 or later
	// alias imagecopyresized to imagecopyresampled for lower version
	if (!function_exists('imagecopyresampled') && function_exists('imagecopyresized')) {
		function imagecopyresampled($dst_im, $src_im, $dstX, $dstY, $srcX, $srcY, $dstW, $dstH, $srcW, $srcH) {
			return imagecopyresized($dst_im, $src_im, $dstX, $dstY, $srcX, $srcY, $dstW, $dstH, $srcW, $srcH);
		}
	}

	// also, imagecreate can be used instead of imagecreatetruecolor
	if (!function_exists('imagecreatetruecolor') && function_exists('imagecreate')) {
		function imagecreatetruecolor($x_size, $y_size) {
			return imagecreate($x_size, $y_size);
		}
	}

	// index 2 of getimagesize can be used instead of exif_imagetype
	if (!function_exists('exif_imagetype') && function_exists('getimagesize')) {
		function exif_imagetype($filename) {
			return ($result = getimagesize($filename)) ? $result[2] : false;
		}
	}

	class Media {
		var $request = null, $config = array();

		function downloadFile($url, $toDirectory) {						
				$filename = 'logo_' . md5($url);
				
				if(!file_exists($toDirectory.$filename)) {
					$file = $this->getRemoteFile($url, true);

					$fp = fopen($toDirectory.$filename,'w+');
					fwrite($fp, $file);
					fclose($fp);
				}

				return $filename;
		}

		function getRemoteFile($url, $raw = false) {
			if (!isset($this->request)) {
				requireComponent('LZ.PHP.HTTPRequest');
				$this->request = new HTTPRequest;
			}
			return (($raw) ? $this->request->getFile($url) : $this->request->getPage($url));
		}

		function getRemotePostPage($url, $content) {
			if (!isset($this->request)) {
				requireComponent('LZ.PHP.HTTPRequest');
				$this->request = new HTTPRequest;
			}
			return $this->request->getPostPage($url,$content);
		}

		function set($f, $v) {
			if (!isset($this->config)) $this->config = array();
			$this->config[$f] = $v;
		}

		function get($item, $limit = -1) { // permalink, description
			$result = array();
			$result['type'] = 'local';

			// use remote api if image functions unavailable
			$requiredFunctions = array('imagetypes', 	'imagecreatetruecolor', 'imagecopyresampled', 'imagedestroy',
														'getimagesize', 'imagesx', 'imagesy', 'imageinterlace', 'imagecreatefromstring');
			foreach ($requiredFunctions as $func) {
				if (!function_exists($func)) {
					$result['type'] = 'remote';
					break;
				}
			}

			$medias = $this->detectMediaAndSave( $item['permalink'], stripslashes($item['description']), $limit );
			if(count($medias['images']) == 0 && count($medias['movies']) == 0) {
				return false;
			} 

			return $medias;
		}
		
		function add($feeditem, $filename, $source, $width, $height, $type = 'image', $via = '') {
			global $db, $database;

			$db->execute("INSERT INTO {$database['prefix']}Medias (feeditem, thumbnail,source, width, height, type, via) VALUES ('$feeditem', '$filename', '$source', '$width', '$height', '$type', '$via')");
			return $db->insertId();
		}

		function delete($id) {
			global $db, $database;

			if(empty($id)) return false;

			if($result = $db->queryAll("SELECT thumbnail FROM {$database['prefix']}Medias WHERE feeditem='{$id}'")) { // delete thumbnail file
				foreach($result as $item) {
					$thumbnail = $item['thumbnail'];
					if (!empty($thumbnail) && substr($thumbnail, 0, 6) != 'http://') {
						if (file_exists(ROOT . '/cache/thumbnail/'. $thumbnail))
							@unlink(ROOT . '/cache/thumbnail/'. $thumbnail);
					}
				}
			}
			$db->execute('DELETE FROM '.$database['prefix'].'Medias WHERE feeditem='.$id);
		}

		function getThumbnail($permalink, $imageURL) {
			$result = array(null, null);
			if(empty($imageURL)) return $result;

			if (preg_match("/\"(.+)\"/", stripslashes($imageURL), $matches))
				$imageURL = $matches[1];
			if (!$cont = $this->getRemoteFile($imageURL, true))
				return false;
			if (!$imageSrc = imagecreatefromstring($cont))
				return false;	
			
			$w = 150;
			$h = 150;
			$org_w = imagesx($imageSrc);
			$org_h = imagesy($imageSrc);

			$imageDes = imagecreatetruecolor($w, $h);

			if($org_w < $org_h) {
				$temp = $h;
				$h = $temp * ($org_h / $org_w);
			} else {
				$temp = $w;
				$w = $temp * ($org_w / $org_h);
			}
			
			if(!imagecopyresampled(
			  $imageDes, $imageSrc,             // destination, source
			  0, 0, 0, 0,           // dstX, dstY, srcX, srcY
			  $w, $h,				// dstW, dstH
			  $org_w, $org_h)) {    // srcW, srcH
			  return false;
			}

			imagedestroy($imageSrc);
			imageinterlace($imageDes);

			$filename = (isset($this->config['filename']) && !empty($this->config['filename'])) ? $this->config['filename'] : 't_'.md5($permalink);
			if (!isset($this->config['outputPath']) || empty($this->config['outputPath']) || !func::mkpath($this->config['outputPath']))
				return false;

			$supportedTypes = $this->getSupportedImageTypes();
			if (in_array('jpg', $supportedTypes)) {
				$result['fullpath'] = $this->config['outputPath'].'/'.$filename.'.jpg';
				imagejpeg($imageDes, $result['fullpath'], 100);
			} else if (in_array('gif', $supportedTypes)) {
				$result['fullpath'] = $this->config['outputPath'].'/'.$filename.'.gif';
				imagegif($imageDes, $result['fullpath']);
			} else if (in_array('png', $supportedTypes)) {
				$result['fullpath'] = $this->config['outputPath'].'/'.$filename.'.png';
				imagepng($imageDes, $result['fullpath']);
			} else {
				imagedestroy($imageDes);
				return false;
			}

			$result['filename'] = basename($result['fullpath']);
			imagedestroy($imageDes);	

			return array('filename'=>$result, 'source'=>$imageURL, 'width'=>$org_w, 'height'=>$org_h);
		}

		function detectMediaAndSave($permalink, $content, $limit = -1) {
			$result = array();
			$result['images'] = array();
			$result['movies'] = array();
	
			if($images = $this->detectIMGsrc($content)) {		
				$count = count($images);
				if($count>0) {		
					if(($limit<0) || ($limit>$count)) {
						$limit = $count;
					}
					for($i=0;$i<$limit;$i++) {
						$item = $images[$i];					
						$this->set('filename','i_' . md5($permalink . $i));
						$datas = $this->getThumbnail($permalink,$item[0]);
						array_push($result['images'], $datas);
					}
				}
			}

			if($movies = $this->detectMovieIMGsrc($content)) {
				foreach($movies as $movie) {
					$this->set('filename', 'm_' . md5($permalink . $movie['url']));
					$datas = $this->getThumbnail($permalink, $movie['url']);
					$datas['via'] = $movie['via'];
					array_push($result['movies'], $datas);
				}
			}

			return $result;
		}

		function detectIMGsrc($str){
			if (!$count = preg_match_all('/<img\s[^>]+/i', $str, $matches))
				return false;
			$result = array();
			for($i=0;$i<$count;$i++) {				
				if (preg_match('/src\s*=\s*("|\')(.+?)\1/i', $matches[0][$i], $m))
					array_push($result, array_filter(array($m[2]), array(new ThumbnailFilter, 'filter')));
				else if (preg_match('/src\s*=\s*([^\s]+)/i', $matches[0][$i], $m))
					array_push($result, array_filter(array($m[1]), array(new ThumbnailFilter, 'filter')));
			}
			return $result;
		}

		function detectMovieIMGsrc($str) {
			if (!isset($str) || empty($str)) 
				return false;
			$response_text = $this->getRemotePostPage('http://openapi.itcanus.net/detectMovieImages/', 'source=' . rawurlencode($str));
			requireComponent('LZ.PHP.XMLStruct');
			$result = array();
			$xmls = new XMLStruct();
			if ($xmls->open($response_text)) {
				$captures = $xmls->selectNode("/itcanus/captures");
				foreach($captures['capture'] as $capture) {
					foreach( $capture['image'] as $source ) {
						$source = $source['.attributes'];
						array_push($result, array('id'=>rawurldecode($source['id']), 'via' => rawurldecode($source['via']), 'url' => rawurldecode($source['url'])));
					}
			
				}
			
			}
			return $result;
		}

		function getSupportedImageTypes() {
			$supportedTypes = array();
			$imageTypeBits = array(IMG_GIF, IMG_JPG, IMG_PNG, IMG_WBMP);

			foreach ($imageTypeBits as $bit) {
				$typeExt = null;
				if (imagetypes() & $bit) {
					switch ($bit) {
						case IMG_GIF:
						$typeExt = 'gif';
						break;

						case IMG_JPG:
						$typeExt = 'jpg';
						break;

						case IMG_PNG:
						$typeExt = 'png';
						break;

						case IMG_WBMP:
						$typeExt = 'bmp';
						break;
					}
					if (isset($typeExt)) array_push($supportedTypes, $typeExt);
				}
			}

			return $supportedTypes;
		}	

		function checkMedia($id) {
			global $db, $database;
			return $db->exists("SELECT id FROM {$database['prefix']}Medias WHERE id='{$id}'");
		}

		/** gets **/
		
		function getMedia($id) {
			global $db, $database;
			return $db->queryRow("SELECT * FROM {$database['prefix']}Medias WHERE id='{$id}'");
		}
		function getMediasByFeedItemId($id) {
			global $db, $database;
			return $db->queryAll("SELECT * FROM {$database['prefix']}Medias WHERE feeditem='{$id}'");
		}

		function getMediaFile($filename) {
			global $service;

			$thumbnailFile = '';
			if ((substr($filename, 0, 7) != 'http://')) {
				if (!is_file(ROOT . '/cache/thumbnail/' . $filename)) { 
					$thumbnailFile = '';
				} else {
					$thumbnailFile = str_replace('/cache/thumbnail//', '/cache/thumbnail/', $service['path']. '/cache/thumbnail/'.$filename);			
				}
			}

			return $thumbnailFile;
		}

	}

	class ThumbnailFilter {
		function filter($var) {
			$keywordsToBlock = array(
				'i.creativecommons.org', 'feeds.feedburner.com',
				'sex', 'porn', 'warez'
			);

			foreach ($keywordsToBlock as $keyword) {
				if (strpos($var, $keyword) !== false)
					return false;
			}

			return true;
		}
	}
?>