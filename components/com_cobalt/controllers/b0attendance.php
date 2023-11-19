<?php
defined('_JEXEC') or die;
// Вызов - https://stovesta.ru/index.php?option=com_cobalt&task=b0attendance.index

use Joomla\CMS\Date\Date;
use Joomla\CMS\Factory;
JImport('b0.Accessory.AccessoryIds');
JImport('b0.Sparepart.SparepartIds');
JImport('b0.Work.WorkIds');
JImport('b0.fixtures');
?>
<?php
class CobaltControllerB0Attendance extends JControllerForm
{
	public function index()
	{ ?>
		<div id="app">
            <h3>Вычисляем посещаемость</h3>
            <div>
                Раздел
                <select v-model="section_id">
                    <option value="<?= AccessoryIds::ID_SECTION ?>">Аксессуары</option>
                    <option value="<?= SparepartIds::ID_SECTION ?>">Запчасти</option>
                    <option value="<?= WorkIds::ID_SECTION ?>">Работы</option>
                </select>
                Создано до
                <input type="date" v-model="date_end">
            </div>
            <hr class="uk-article-divider">
            <div>
                <table class="uk-table">
                    <thead>
                        <tr>
                            <th>Наименование</th>
                            <th>Дата создания</th>
                            <th>Дней назад</th>
                            <th>Просмотров</th>
                            <th>В день</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="item in items">
<!--                            <td>{{item.title}}</td>-->
                            <td><a :href="item.url" target="_blank">{{item.title}}</a></td>
                            <td>{{item.created}}</td>
                            <td>{{item.days}}</td>
                            <td>{{item.hits}}</td>
                            <td>{{item.perday}}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
		</div>
		
		<script src="https://cdn.jsdelivr.net/npm/vue@2"></script>
        <script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
		<script>
            var app = new Vue({
                el: '#app',
                data: {
                    items: {},
                    section_id: <?= AccessoryIds::ID_SECTION ?>,
                    date_end: '2020-03-31',
                    message: 'Hello Vue!'
                },
                mounted() {
                    this.getResults();
                },
                watch: {
                    section_id(value) { this.getResults(); },
                    date_end(value) { this.getResults(); }
                },
                methods: {
                    getResults(page = 1) {
                        axios.get('/index.php?option=com_cobalt&task=b0attendance.getItems'
                            + '&section_id=' + this.section_id
                            + '&date_end=' + this.date_end
                            )
                            .then(response => {
                                this.items = response.data;
                                console.log(this.items);
                            });
                    },
                }
            })
		</script>
	
	<?php }
	
	public function getItems()
	{
		$input = Factory::getApplication()->input;
		$sectionId = $input->get('section_id', AccessoryIds::ID_SECTION);
		$dateEnd = $input->get('date_end', '2020-03-31');
		$currentDate = new Date();
		$result = [];

		$db = JFactory::getDbo();
		$query = $db->getQuery(TRUE);
		$query->select('id, title, alias, ctime, hits, fields');
		$query->from('#__js_res_record');
		$query->where("section_id = {$sectionId}");
		$query->where("ctime < '{$dateEnd}'");
		$query->where("published=1");
//		$db->setQuery($query,0,10);
		$db->setQuery($query);
		$list = $db->loadObjectList();
		
		foreach ($list as $item) {
			$created = new Date($item->ctime);
			$days = date_diff($currentDate, $created)->days;
			if ($days === 0) {
				continue;
			}
			$perDay = round($item->hits / $days, 3);
			$ind = $perDay*1000;
//			$url = '/accessories/item/'.$item->id.'-'.$item->alias;
			switch ($sectionId) {
				case AccessoryIds::ID_SECTION:
					$url = '/accessories/item/'.$item->id.'-'.$item->alias;
					break;
				case SparepartIds::ID_SECTION:
					$url = '/spareparts/item/'.$item->id.'-'.$item->alias;
					break;
				case WorkIds::ID_SECTION:
					$url = '/repair/item/'.$item->id.'-'.$item->alias;
					break;
			}
			
			$result += [
				$ind => [
					'title' => $item->title,
                    'url' => $url,
					'created' => $created->format('d-m-Y'),
					'days' => date_diff($currentDate, $created)->days,
					'hits' => $item->hits,
					'perday' => round($item->hits / $days, 3),
				]
			];
		}
		ksort($result);
		$data = json_encode($result, JSON_THROW_ON_ERROR);
		JExit($data);
	}
}
?>
