<?php
defined('_JEXEC') or die;
// Вызов - /index.php?option=com_cobalt&task=b0reportproductsbymodels.index

use Joomla\CMS\Date\Date;
use Joomla\CMS\Factory;
JImport('b0.Accessory.AccessoryIds');
JImport('b0.Sparepart.SparepartIds');

class CobaltControllerB0ReportProductsByModels extends JControllerForm
{
    public array $models = [
        'vesta' => 'Lada Vesta',
        'xray' => 'Lada XRay',
        'granta' => 'Lada Granta FL',
        'largus' => 'Lada Largus',
        'moskvich' => 'Москвич',
        ];

	public function index()
	{ ?>
		<div id="app">
            <h3>Продукты по моделям</h3>
            <div class="uk-form-row">
                Раздел
                <select class="uk-margin-left uk-margin-right" v-model="section_id">
                    <option value="<?= AccessoryIds::ID_SECTION ?>">Аксессуары</option>
                    <option value="<?= SparepartIds::ID_SECTION ?>">Запчасти</option>
                </select>
                <span class="uk-margin-left">Модель</span>
                <select class="uk-margin-left" v-model="model">
                    <?php foreach ($this->models as $value => $title) {
                        echo '<option value="' . $value . '">' . $title . '</option>';
                    } ?>
                </select>
                <a href="/logs/b0-products-by-fields.csv" class="uk-margin-left" title="Скачать отчет в формате CSV" download>
                    <i class="uk-icon-file-excel-o uk-icon-small uk-margin-left"></i>
                    Отчет
                </a>
            </div>
            <p>Продуктов - {{items.length}}</p>
            <hr class="uk-article-divider">
            <div>
                <table class="uk-table">
                    <thead>
                    <tr>
                        <th>Код</th>
                        <th>Наименование</th>
                    </tr>
                    </thead>
                    <tbody>
                    <tr v-for="item in items">
                        <td>{{item.code}}</td>
                        <td><a :href="item.url" target="_blank">{{item.title}}</a></td>
                    </tr>
                    </tbody>
                </table>
            </div>
		</div>
		
<!--		<script src="https://cdn.jsdelivr.net/npm/vue@2"></script>-->
        <script src="https://cdn.jsdelivr.net/npm/vue@2.6.14/dist/vue.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
		<script>
            var app = new Vue({
                el: '#app',
                data: {
                    items: [],
                    section_id: <?= AccessoryIds::ID_SECTION ?>,
                    model: 'vesta'
                },
                mounted() {
                    this.getResults();
                },
                watch: {
                    section_id(value) { this.getResults(); },
                    model(value) { this.getResults(); },
                },
                methods: {
                    getResults(page = 1) {
                        axios.get('/index.php?option=com_cobalt&task=b0reportproductsbymodels.getItems'
                            + '&section_id=' + this.section_id
                            + '&model=' + this.model
                            )
                            .then(response => {
                                this.items = response.data;
                                // console.log(this.items);
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
        $model = $this->models[$input->get('model', 'vesta')];
        $fieldId = $sectionId == AccessoryIds::ID_SECTION ? AccessoryIds::ID_MODEL : SparepartIds::ID_MODEL;
		$result = [];
		$resultCsv = [];

		$db = JFactory::getDbo();
		$query = $db->getQuery(TRUE);
		$query->select('id, title, alias, fields');
		$query->from('#__js_res_record');
		$query->where("section_id = {$sectionId}");
		$query->where("published=1");
		$query->where("id IN (SELECT record_id FROM #__js_res_record_values WHERE (field_id={$fieldId} AND field_value='{$model}'))");
		$db->setQuery($query);
        $list = $db->loadObjectList();
        foreach ($list as $item) {
			$fields = json_decode($item->fields, true, 512, JSON_THROW_ON_ERROR);
			switch ($sectionId) {
				case AccessoryIds::ID_SECTION:
                    $url = '/accessories/item/'.$item->id.'-'.$item->alias;
                    $fullUrl = 'https://stovesta.ru/accessories/item/'.$item->id.'-'.$item->alias;
                    $code = $fields[AccessoryIds::ID_PRODUCT_CODE];
                    $imageUrl = isset($fields[AccessoryIds::ID_IMAGE]['image']) ? 'https://stovesta.ru/accessories/item/' . $fields[AccessoryIds::ID_IMAGE]['image'] : '';
                    $price = $fields[AccessoryIds::ID_PRICE_GENERAL];
					break;
				case SparepartIds::ID_SECTION:
                    $url = '/spareparts/item/'.$item->id.'-'.$item->alias;
                    $fullUrl = 'https://stovesta.ru/spareparts/item/'.$item->id.'-'.$item->alias;
                    $code = $fields[SparepartIds::ID_PRODUCT_CODE];
                    $imageUrl = isset($fields[SparepartIds::ID_IMAGE]['image']) ? 'https://stovesta.ru/spareparts/item/'.$fields[SparepartIds::ID_IMAGE]['image'] : '';
                    $price = $fields[SparepartIds::ID_PRICE_GENERAL];
					break;
			}
			
			$result[] = [
                'title' => $item->title,
                'url' => $url,
                'code' => $code,
			];
            $resultCsv[] = [$item->title,$fullUrl,$imageUrl,$price];
		}
		$this->renderCSV($resultCsv);
		$data = json_encode($result, JSON_THROW_ON_ERROR);
		JExit($data);
	}

	private function renderCSV($items)
	{
        $handle = fopen(JPATH_ROOT . '/logs/b0-products-by-fields.csv', 'w+');
        foreach($items as $item) {
            fputcsv($handle, $item, ',');
        }
        fclose($handle);
	}
}
