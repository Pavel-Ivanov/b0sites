<?php
defined('_JEXEC') or die;
// Вызов - https://logan-shop.spb.ru/index.php?option=com_cobalt&task=b0emptyfields.index

use Joomla\CMS\Date\Date;
use Joomla\CMS\Factory;
JImport('b0.Accessory.AccessoryIds');
JImport('b0.Sparepart.SparepartIds');
JImport('b0.Work.WorkIds');
JImport('phpexcel.PHPExcel');
JImport('phpexcel.PHPExcel.Writer.Excel2007');
JImport('b0.fixtures');

class CobaltControllerB0EmptyFields extends JControllerForm
{
	public function index()
	{ ?>
		<div id="app">
            <h3>Ищем пустые поля</h3>
            <div class="uk-form-row">
                Раздел
                <select class="uk-margin-left uk-margin-right" v-model="section_id">
                    <option value="<?= AccessoryIds::ID_SECTION ?>">Аксессуары</option>
                    <option value="<?= SparepartIds::ID_SECTION ?>">Запчасти</option>
                    <option value="<?= WorkIds::ID_SECTION ?>">Работы</option>
                </select>
                <span class="uk-margin-left">Поле</span>
                <select class="uk-margin-left" v-model="field_key">
                    <option value="image">Изображение</option>
                    <option value="description">Описание</option>
                    <option value="gallery">Галерея</option>
                    <option value="imagehit">Изображение Хит</option>
                </select>
                <a href="/logs/b0-empty-fields.xlsx" class="uk-margin-left" title="Скачать отчет в формате Excel" download>
                    <i class="uk-icon-file-excel-o uk-icon-small uk-margin-left"></i>
                    Отчет
                </a>
            </div>
            <p>Пустых - {{items.length}}</p>
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
                    field_key: 'image'
                },
                mounted() {
                    this.getResults();
                },
                watch: {
                    section_id(value) { this.getResults(); },
                    field_key(value) { this.getResults(); },
                },
                methods: {
                    getResults(page = 1) {
                        axios.get('/index.php?option=com_cobalt&task=b0emptyfields.getItems'
                            + '&section_id=' + this.section_id
                            + '&field_key=' + this.field_key
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
		$fieldKey = $input->get('field_key', 'image');
		$result = [];

		$db = JFactory::getDbo();
		$query = $db->getQuery(TRUE);
		$query->select('id, title, alias, fields');
		$query->from('#__js_res_record');
		$query->where("section_id = {$sectionId}");
		$query->where("published=1");
		$db->setQuery($query);
		$list = $db->loadObjectList();
		
		foreach ($list as $item) {
			$fields = json_decode($item->fields, true, 512, JSON_THROW_ON_ERROR);
			switch ($sectionId) {
				case AccessoryIds::ID_SECTION:
                    switch ($fieldKey) {
                        case 'image':
	                        if (isset($fields[AccessoryIds::ID_IMAGE]) && !empty($fields[AccessoryIds::ID_IMAGE])) {
		                        continue 3;
	                        }
	                        break;
                        case 'description':
                            if (isset($fields[AccessoryIds::ID_DESCRIPTION]) && !empty($fields[AccessoryIds::ID_DESCRIPTION])) {
                                continue 3;
                            }
                            break;
                        case 'gallery':
                            if (isset($fields[AccessoryIds::ID_GALLERY]) && !empty($fields[AccessoryIds::ID_GALLERY])) {
                                continue 3;
                            }
                            break;
                        case 'imagehit':
	                        if (!isset($fields[AccessoryIds::ID_IS_HIT])) {
		                        continue 3;
	                        }
	                        if (isset($fields[AccessoryIds::ID_IS_HIT]) && ($fields[AccessoryIds::ID_IS_HIT] === -1)) {
                                continue 3;
                            }
                            if (isset($fields[AccessoryIds::ID_HIT_IMAGE]) && !empty($fields[AccessoryIds::ID_HIT_IMAGE])) {
                                continue 3;
                            }
                            break;
                    }
                    $url = '/accessories/item/'.$item->id.'-'.$item->alias;
                    $code = $fields[AccessoryIds::ID_PRODUCT_CODE];
					break;
				case SparepartIds::ID_SECTION:
					switch ($fieldKey) {
						case 'image':
							if (isset($fields[SparepartIds::ID_IMAGE]) && !empty($fields[SparepartIds::ID_IMAGE])) {
								continue 3;
							}
							break;
						case 'description':
							if (isset($fields[SparepartIds::ID_DESCRIPTION]) && !empty($fields[SparepartIds::ID_DESCRIPTION])) {
								continue 3;
							}
							break;
						case 'gallery':
							if (isset($fields[SparepartIds::ID_GALLERY]) && !empty($fields[SparepartIds::ID_GALLERY])) {
								continue 3;
							}
							break;
						case 'imagehit':
							if (!isset($fields[SparepartIds::ID_IS_HIT])) {
								continue 3;
							}
							if (isset($fields[SparepartIds::ID_IS_HIT]) && ($fields[SparepartIds::ID_IS_HIT] === -1)) {
								continue 3;
							}
							if (isset($fields[SparepartIds::ID_HIT_IMAGE]) && !empty($fields[SparepartIds::ID_HIT_IMAGE])) {
								continue 3;
							}
							break;
					}
                    $url = '/spareparts/item/'.$item->id.'-'.$item->alias;
                    $code = $fields[SparepartIds::ID_PRODUCT_CODE];
					break;
				case WorkIds::ID_SECTION:
					switch ($fieldKey) {
						case 'image':
							if (isset($fields[WorkIds::ID_IMAGE]) && !empty($fields[WorkIds::ID_IMAGE])) {
								continue 3;
							}
							break;
						case 'description':
							if (isset($fields[WorkIds::ID_DESCRIPTION]) && !empty($fields[WorkIds::ID_DESCRIPTION])) {
								continue 3;
							}
							break;
						case 'gallery':
							if (isset($fields[WorkIds::ID_GALLERY]) && !empty($fields[WorkIds::ID_GALLERY])) {
								continue 3;
							}
							break;
						case 'imagehit':
                            continue 3;
							break;
					}
                    $url = '/repair/item/'.$item->id.'-'.$item->alias;
                    $code = $fields[WorkIds::ID_SERVICE_CODE];
					break;
			}
			
			$result[] = [
                'title' => $item->title,
                'url' => $url,
                'code' => $code,
			];
		}
		$this->renderXls($result, $sectionId, $fieldKey);
		$data = json_encode($result, JSON_THROW_ON_ERROR);
		JExit($data);
	}
	private function renderXls($items, $sectionId, $fieldKey)
	{
		$siteName = JFactory::getApplication()->get('sitename');

        switch ($sectionId) {
            case AccessoryIds::ID_SECTION:
                $section = 'Аксессуары';
                break;
            case SparepartIds::ID_SECTION:
                $section = 'Запчасти';
                break;
            case WorkIds::ID_SECTION:
                $section = 'Работы';
                break;
        }
        switch ($fieldKey) {
            case 'image':
                $field = 'Пустые изображения';
                break;
            case 'description':
                $field = 'Пустые описания';
                break;
            case 'gallery':
                $field = 'Пустая галерея';
                break;
	        case 'imagehit':
		        $field = 'Пустые изображения Хит';
		        break;
        }
        $slogan = $section . ' - ' . $field;

		$objPhpExcel = new PHPExcel;
		$objPhpExcel->setActiveSheetIndex(0);
		$active_sheet = $objPhpExcel->getActiveSheet();
  
		// Параметры страницы
		$active_sheet->getPageSetup()->setOrientation(PHPExcel_Worksheet_PageSetup::ORIENTATION_PORTRAIT);
		$active_sheet->getPageSetup()->setPaperSize(PHPExcel_Worksheet_PageSetup::PAPERSIZE_A4);
		
		$active_sheet->getPageMargins()->setTop(1);
		$active_sheet->getPageMargins()->setRight(0.75);
		$active_sheet->getPageMargins()->setLeft(0.75);
		$active_sheet->getPageMargins()->setBottom(1);
		
		$active_sheet->setTitle("Анализ заполнения");
		
		$active_sheet->getHeaderFooter()->setOddHeader($siteName);
		$active_sheet->getHeaderFooter()->setOddFooter('&L&B'.$active_sheet->getTitle().'&RСтраница &P из &N');
		
		$objPhpExcel->getDefaultStyle()->getFont()->setName('Calibri');
		$objPhpExcel->getDefaultStyle()->getFont()->setSize(10);
		
		//Параметры столбцов
		$active_sheet->setCellValue('A4','№');
		$active_sheet->getColumnDimension('A')->setWidth(7);
		$active_sheet->setCellValue('B4','Код');
		$active_sheet->getColumnDimension('B')->setWidth(10);
		$active_sheet->setCellValue('C4','Наименование');
		$active_sheet->getColumnDimension('C')->setWidth(75);
  
		//Слоган документа
		$active_sheet->mergeCells('B2:C2');
		$active_sheet->setCellValue('B2',$slogan);
		$style_slogan = [
			'font'=>[
				'name' => 'Roboto',
				'size' => 12,
			],
			'alignment' => [
				'horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_CENTER,
				'vertical' => PHPExcel_Style_Alignment::VERTICAL_CENTER,
			],
			'borders' => [
				'bottom' => [
					'style'=>PHPExcel_Style_Border::BORDER_NONE
				]
			]
		];
		$active_sheet->getStyle('B2:C2')->applyFromArray($style_slogan);
  
		$rowStart = 5;
		$i = 0;
		foreach($items as $key => $item) {
			$row_next = $rowStart + $i;
			$rowNumber = $i+1;
			$active_sheet->setCellValueExplicit('A' . $row_next, $rowNumber, PHPExcel_Cell_DataType::TYPE_NUMERIC);
			$active_sheet->setCellValueExplicit('B' . $row_next, $item['code']);
			$active_sheet->setCellValueExplicit('C' . $row_next, $item['title']);
			$i++;
		}
  
		$style_number = [
			'alignment' => [
				'horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_CENTER,
                'vertical' => PHPExcel_Style_Alignment::VERTICAL_CENTER,
			]
		];
        $active_sheet->getStyle('A5:A'.($i+4))->applyFromArray($style_number);

		$style_code = [
			'alignment' => [
				'horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_CENTER,
                'vertical' => PHPExcel_Style_Alignment::VERTICAL_CENTER,
			]
		];
        $active_sheet->getStyle('B5:B'.($i+4))->applyFromArray($style_code);

		$style_title = [
			'alignment' => [
				'horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_LEFT,
                'vertical' => PHPExcel_Style_Alignment::VERTICAL_CENTER,
			]
		];
		$active_sheet->getStyle('C5:C'.($i+4))->applyFromArray($style_title);
		
		$objWriter = new PHPExcel_Writer_Excel2007($objPhpExcel);
		$objWriter->save(JPATH_ROOT . '/logs/b0-empty-fields.xlsx');
	}
}
