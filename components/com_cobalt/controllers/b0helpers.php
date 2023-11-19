<?php
defined('_JEXEC') or die;

use Joomla\CMS\Date\Date;
JImport('b0.Accessory.AccessoryIds');
JImport('b0.Sparepart.SparepartIds');
JImport('b0.Work.WorkIds');
JImport('b0.fixtures');

class CobaltControllerB0Helpers extends JControllerForm
{
	// Вызов - https://stovesta.ru/index.php?option=com_cobalt&task=b0helpers.accessory_attendance&end=2020-01-31
	public function accessory_attendance() {
		$end = $_GET['end'] ?? null;
		$endDate = new Date($end);
		$currentDate = new Date();
		$result = [];
		$sectionId = AccessoryIds::ID_SECTION;
		
		echo "<h3>Вычисляем посещаемость аксессуаров по {$endDate->format('d-m-Y')}</h3>";
		
		$db = JFactory::getDbo();
		$query = $db->getQuery(TRUE);
		$query->select('id, fields, title, alias, ctime, hits, section_id');
		$query->from('#__js_res_record');
		$query->where("section_id = {$sectionId}");
		$query->where("ctime < '{$end}'");
		$query->where("published=1");
//		$db->setQuery($query,0,10);
		$db->setQuery($query);

		$list = $db->loadObjectList();

//		b0dd($list);
		if (!$list) {
			echo '<p>Ничего не найдено</p>';
			exit();
		}
		foreach ($list as $item) {
			$created = new Date($item->ctime);
			$days = date_diff($currentDate, $created)->days;
			if ($days === 0) {
				continue;
			}
			$perDay = round($item->hits / $days, 3);
			$ind = $perDay*1000;
			$url = '/accessories/item/'.$item->id.'-'.$item->alias;
			$result += [
				$ind => [
					'title' => '<a href="'.$url.'" target="_blank">'.$item->title.'</a>',
					'created' => $created->format('d-m-Y'),
					'days' => date_diff($currentDate, $created)->days,
					'hits' => $item->hits,
					'perday' => round($item->hits / $days, 3),
				]
			];
			
		}
		ksort($result);
//		b0dd($result);
		echo '<table class="uk-table">';
			echo '<thead>';
				echo '<tr>';
					echo '<th>Наименование</th>';
					echo '<th>Дата создания</th>';
					echo '<th>Дней назад</th>';
					echo '<th>Просмотров</th>';
					echo '<th>В день</th>';
				echo '</tr>';
			echo '</thead>';
			echo '<tbody>';
				foreach ($result as $ind => $item) {
					echo '<tr>';
						echo '<td>'.$item['title'].'</td>';
						echo '<td>'.$item['created'].'</td>';
						echo '<td>'.$item['days'].'</td>';
						echo '<td>'.$item['hits'].'</td>';
						echo '<td>'.$item['perday'].'</td>';
					echo '</tr>';
				}
			echo '</tbody>';
		echo '</table>';
	}
	
	// Вызов - https://stovesta.ru/index.php?option=com_cobalt&task=b0helpers.sparepart_attendance&end=2020-01-31
	public function sparepart_attendance() {
		$end = $_GET['end'] ?? null;
		$endDate = new Date($end);
		$currentDate = new Date();
		$result = [];
		$sectionId = SparepartIds::ID_SECTION;
		
		echo "<h3>Вычисляем посещаемость запчастей по {$endDate->format('d-m-Y')}</h3>";
		
		$db = JFactory::getDbo();
		$query = $db->getQuery(TRUE);
		$query->select('id, fields, title, alias, ctime, hits, section_id');
		$query->from('#__js_res_record');
		$query->where("section_id = {$sectionId}");
		$query->where("ctime < '{$end}'");
		$query->where("published=1");
//		$db->setQuery($query,0,10);
		$db->setQuery($query);

		$list = $db->loadObjectList();

//		b0dd($list);
		if (!$list) {
			echo '<p>Ничего не найдено</p>';
			exit();
		}
		foreach ($list as $item) {
			$created = new Date($item->ctime);
			$days = date_diff($currentDate, $created)->days;
			if ($days === 0) {
				continue;
			}
			$perDay = round($item->hits / $days, 3);
			$ind = $perDay*1000;
			$url = '/spareparts/item/'.$item->id.'-'.$item->alias;
			$result += [
				$ind => [
					'title' => '<a href="'.$url.'" target="_blank">'.$item->title.'</a>',
					'created' => $created->format('d-m-Y'),
					'days' => date_diff($currentDate, $created)->days,
					'hits' => $item->hits,
					'perday' => round($item->hits / $days, 3),
				]
			];
			
		}
		ksort($result);
//		b0dd($result);
		echo '<table class="uk-table">';
		echo '<thead>';
		echo '<tr>';
		echo '<th>Наименование</th>';
		echo '<th>Дата создания</th>';
		echo '<th>Дней назад</th>';
		echo '<th>Просмотров</th>';
		echo '<th>В день</th>';
		echo '</tr>';
		echo '</thead>';
		echo '<tbody>';
		foreach ($result as $ind => $item) {
			echo '<tr>';
			echo '<td>'.$item['title'].'</td>';
			echo '<td>'.$item['created'].'</td>';
			echo '<td>'.$item['days'].'</td>';
			echo '<td>'.$item['hits'].'</td>';
			echo '<td>'.$item['perday'].'</td>';
			echo '</tr>';
		}
		echo '</tbody>';
		echo '</table>';
	}
	
	// Вызов - https://stovesta.ru/index.php?option=com_cobalt&task=b0helpers.work_attendance&end=2020-01-31
	public function work_attendance() {
		$end = $_GET['end'] ?? null;
		$endDate = new Date($end);
		$currentDate = new Date();
		$result = [];
		$sectionId = WorkIds::ID_SECTION;
		
		echo "<h3>Вычисляем посещаемость работ по {$endDate->format('d-m-Y')}</h3>";
		
		$db = JFactory::getDbo();
		$query = $db->getQuery(TRUE);
		$query->select('id, fields, title, alias, ctime, hits, section_id');
		$query->from('#__js_res_record');
		$query->where("section_id = {$sectionId}");
		$query->where("ctime < '{$end}'");
		$query->where("published=1");
//		$db->setQuery($query,0,10);
		$db->setQuery($query);

		$list = $db->loadObjectList();

//		b0dd($list);
		if (!$list) {
			echo '<p>Ничего не найдено</p>';
			exit();
		}
		foreach ($list as $item) {
			$created = new Date($item->ctime);
			$days = date_diff($currentDate, $created)->days;
			if ($days === 0) {
				continue;
			}
			$perDay = round($item->hits / $days, 3);
			$ind = $perDay*1000;
			$url = '/repair/item/'.$item->id.'-'.$item->alias;
			$result += [
				$ind => [
					'title' => '<a href="'.$url.'" target="_blank">'.$item->title.'</a>',
					'created' => $created->format('d-m-Y'),
					'days' => date_diff($currentDate, $created)->days,
					'hits' => $item->hits,
					'perday' => round($item->hits / $days, 3),
				]
			];
			
		}
		ksort($result);
//		b0dd($result);
		echo '<table class="uk-table">';
		echo '<thead>';
		echo '<tr>';
		echo '<th>Наименование</th>';
		echo '<th>Дата создания</th>';
		echo '<th>Дней назад</th>';
		echo '<th>Просмотров</th>';
		echo '<th>В день</th>';
		echo '</tr>';
		echo '</thead>';
		echo '<tbody>';
		foreach ($result as $ind => $item) {
			echo '<tr>';
			echo '<td>'.$item['title'].'</td>';
			echo '<td>'.$item['created'].'</td>';
			echo '<td>'.$item['days'].'</td>';
			echo '<td>'.$item['hits'].'</td>';
			echo '<td>'.$item['perday'].'</td>';
			echo '</tr>';
		}
		echo '</tbody>';
		echo '</table>';
	}
}