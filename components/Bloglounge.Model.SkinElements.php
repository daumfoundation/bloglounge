<?php
	class SkinElement {
		function getTagCloud($order, $limit) {
			global $service, $skin;
			if (!$tagCloud = Tag::getTagCloud($order, $limit))
				return '';
			$s_rep_tagcloud = '';
			$src_tagcloud = $skin->cutSkinTag('tagcloud');
			$src_rep_tagcloud = $skin->cutSkinTag('tagcloud_rep');

			foreach ($tagCloud as $tagCloudItem) {
				$sp_rep_tagcloud = $skin->parseTag('tagcloud_url', htmlspecialchars($service['path'].'/search/tag/'.urlencode(trim($tagCloudItem['name']))), $src_rep_tagcloud);
				$sp_rep_tagcloud = $skin->parseTag('tagcloud_name', UTF8::clear($tagCloudItem['name']), $sp_rep_tagcloud);
				$sp_rep_tagcloud = $skin->parseTag('tagcloud_class', Tag::getFrequencyClass($tagCloudItem['frequency']), $sp_rep_tagcloud);
				$s_rep_tagcloud .= $sp_rep_tagcloud;
				$sp_rep_tagcloud = '';
			}
			$s_tagcloud = $skin->dressOn('tagcloud_rep', $src_rep_tagcloud, $s_rep_tagcloud, $src_tagcloud);
			return $s_tagcloud;
		}

		function getCalendarView($period = null) {
			global $service, $database, $db;

			if ((empty($period) === true) || !TimePeriod::checkPeriod($period))
				$period = Timestamp::getYearMonth();
	        $calendar = array('days' => array());
			$calendar['period'] = $period;
			$calendar['year'] = substr($period, 0, 4);
			$calendar['month'] = substr($period, 4, 2);
			if ($db->query("SELECT DISTINCT DAYOFMONTH(FROM_UNIXTIME(written)) FROM {$database['prefix']}FeedItems WHERE YEAR(FROM_UNIXTIME(written)) = {$calendar['year']} AND MONTH(FROM_UNIXTIME(written)) = {$calendar['month']}")) {
				while (list($day) = $db->fetchArray())
						array_push($calendar['days'], $day);
			}
			$calendar['days'] = array_flip($calendar['days']);

			$current = $calendar['year'] . $calendar['month'];
			$previous = TimePeriod::addPeriod($current, - 1);
			$next = TimePeriod::addPeriod($current, 1);
			$firstWeekday = date('w', mktime(0, 0, 0, $calendar['month'], 1, $calendar['year']));
			$lastDay = date('t', mktime(0, 0, 0, $calendar['month'], 1, $calendar['year']));
			$today = ($current == Timestamp::get('Ym') ? Timestamp::get('j') : null);

			$currentMonthStr = Timestamp::format('%Y.%m', TimePeriod::getTimeFromPeriod($current));

			define('CRLF', "\r\n");
			ob_start();
		?>
		<table class="calendar" cellpadding="0" cellspacing="1" style="width: 100%; table-layout: fixed">
			<caption class="cal_month">
				<?php echo $currentMonthStr;?>
			</caption>
			<thead>
				<tr>
					<th class="cal_week2"><?php echo _t('일요일');?></th>
					<th class="cal_week1"><?php echo _t('월요일');?></th>
					<th class="cal_week1"><?php echo _t('화요일');?></th>
					<th class="cal_week1"><?php echo _t('수요일');?></th>
					<th class="cal_week1"><?php echo _t('목요일');?></th>
					<th class="cal_week1"><?php echo _t('금요일');?></th>
					<th class="cal_week1"><?php echo _t('토요일');?></th>
				</tr>
			</thead>
			<tbody>
		<?php
			$day = 0;
			$totalDays = $firstWeekday + $lastDay;
			$lastWeek = ceil($totalDays / 7);

			for ($week=0; $week<$lastWeek; $week++) {
				// 주중에 현재 날짜가 포함되어 있으면 주를 현재 주 class(tt-current-week)를 부여한다.
				if (($today + $firstWeekday) >= $week * 7 && ($today + $firstWeekday) < ($week + 1) * 7) {
					echo '		<tr class="cal_week cal_current_week">'.CRLF;
				} else {
					echo '		<tr class="cal_week">'.CRLF;
				}

				for($weekday=0; $weekday<7; $weekday++) {
					$day++;
					$dayString = isset($calendar['days'][$day]) ? '<a class="cal_click" href="'.$service['path'].'/?archive='.$current.($day > 9 ? $day : "0$day").'">'.$day.'</a>' : $day;

					// 일요일, 평일, 토요일별로 class를 부여한다.
					switch ($weekday) {
						case 0:
							$className = " cal_day cal_day_sunday";
							break;
						case 1:
						case 2:
						case 3:
						case 4:
						case 5:
						case 6:
							$className = " cal_day";
							break;
					}

					// 오늘에 현재 class(tt-current-day)를 부여한다.
					$className .= $day == $today ? " cal_day4" : " cal_day3";

					if ($week == 0) {
						if ($weekday < $firstWeekday) {
							$day--;
							// 달의 첫째날이 되기 전의 빈 칸.
							echo '			<td class="cal_day1">&nbsp;</td>'.CRLF;
						} else {
							echo '			<td class="'.$className.'">'.$dayString.'</td>'.CRLF;
						}
					} else if ($week == ($lastWeek - 1)) {
						if ($day <= $lastDay) {
							echo '			<td class="'.$className.'">'.$dayString.'</td>'.CRLF;
						} else {
							// 달의 마지막날을 넘어간 날짜 빈 칸.
							echo '			<td class="cal_day2">&nbsp;</td>'.CRLF;
						}
					} else {
						echo '			<td class="'.$className.'">'.$dayString.'</td>'.CRLF;
					}
				}
				echo '		</tr>'.CRLF;

				if ($day >= $lastDay) {
					break;
				}
			}
		?>
			</tbody>
		</table>
		<?php
			$view = ob_get_contents();
			ob_end_clean();
			return $view;
		}
	}
?>