<?php
defined('_JEXEC') or die();
// Вызов - /index.php?option=com_cobalt&task=b0reportmarket.index

use Joomla\CMS\Date\Date;
use Joomla\CMS\Factory;
JImport('b0.Accessory.AccessoryIds');
JImport('b0.Sparepart.SparepartIds');
JImport('phpexcel.PHPExcel');
JImport('phpexcel.PHPExcel.Writer.Excel2007');
JImport('b0.fixtures');
?>
<?php
class CobaltControllerB0ReportMarket extends JControllerForm
{
	public function index()
	{ ?>
		<div id="app">
            <h3>Выгрузка на Яндекс Маркет</h3>
            <hr>
            <div class="uk-form">
                <span class="uk-text-bold">Раздел</span>
                <select v-model="section_id">
                    <option value="<?= AccessoryIds::ID_SECTION ?>">Аксессуары</option>
                    <option value="<?= SparepartIds::ID_SECTION ?>">Запчасти</option>
                </select>
                <span class="uk-margin-left uk-text-bold">Модель</span>
                <select v-model="car_model">
                    <option value="Vesta">Vesta</option>
                    <option value="XRay">XRay</option>
                    <option value="Granta">Granta</option>
                </select>
                <span class="uk-margin-left uk-text-bold">Выгрузка</span>
                <select v-model="is_upload">
                    <option value="all">Все</option>
                    <option value="1">Да</option>
                    <option value="-1">Нет</option>
                </select>
                <span class="uk-margin-left uk-text-bold">Текст</span>
                <input type="text" v-model.lazy="stext">
                <a href="/logs/b0report-ymarket.xlsx" class="uk-margin-left" title="Скачать отчет в формате Excel" download>
                    <i class="uk-icon-file-excel-o uk-icon-small uk-margin-left"></i>
                    Отчет
                </a>
            </div>
            <hr class="uk-article-divider">
            <div>
                <table class="uk-table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Код</th>
                            <th>Наименование</th>
                            <th>Есть на Маркете</th>
                            <th>Заголовок</th>
                            <th>Картинка</th>
                            <th>Категория</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="(item, key) in items">
                            <td>{{ key }}</td>
                            <td>{{item.code}}</td>
                            <td><a :href="item.url" target="_blank">{{item.title}}</a></td>
                            <td class="uk-text-center"><span v-html="item.is_on_market"></span></td>
                            <td>{{item.ymtitle}}</td>
                            <td class="uk-text-center"><span v-html="item.ymimage"></span></td>
                            <td>{{item.ymcategory}}</td>
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
                    car_model: 'Vesta',
                    is_upload: 'all',
                    stext: '',
                },
                mounted() {
                    this.getResults();
                },
                watch: {
                    section_id(value) { this.getResults(); },
                    car_model(value) { this.getResults(); },
                    is_upload(value) { this.getResults(); },
                    stext(value) { this.getResults(); }
                },
                methods: {
                    getResults(page = 1) {
                        axios.get('/index.php?option=com_cobalt&task=b0reportmarket.getItems'
                            + '&section_id=' + this.section_id
                            + '&car_model=' + this.car_model
                            + '&is_upload=' + this.is_upload
                            + '&stext=' + this.stext
                            )
                            .then(response => {
                                this.items = response.data;
                                // console.log(this.items);
                            });
                    },
                },
                computed: {
                    plusCount: function () {
                        return ++this.count;
                    }
                }
            })
		</script>
	
	<?php }
	
	public function getItems()
	{
		$input = Factory::getApplication()->input;
		$sectionId = $input->get('section_id', AccessoryIds::ID_SECTION);
		$carModel = $input->get('car_model', 'Vesta');
		$isUpload = $input->get('is_upload', 'all');
		$stext = $_GET['stext'];
		switch ($sectionId) {
			case AccessoryIds::ID_SECTION:
				$fieldModel = AccessoryIds::ID_MODEL;
				$fieldIsYmarket = AccessoryIds::ID_YM_UPLOAD_ENABLE;
				break;
			case SparepartIds::ID_SECTION:
				$fieldModel = SparepartIds::ID_MODEL;
				$fieldIsYmarket = SparepartIds::ID_YM_UPLOAD_ENABLE;
				break;
            default:
	            $fieldModel = AccessoryIds::ID_MODEL;
	            $fieldIsYmarket = AccessoryIds::ID_YM_UPLOAD_ENABLE;
	            break;
		}
		$result = [];

		$db = JFactory::getDbo();
		$query = $db->getQuery(TRUE);
		$query->select('id, title, alias, ctime, fields, meta_descr');
		$query->from('#__js_res_record');
		$query->where("section_id = {$sectionId}");
		$query->where("id IN (SELECT record_id FROM #__js_res_record_values WHERE field_id = {$fieldModel} AND field_value LIKE '%{$carModel}%')");
		if ($isUpload !== 'all') {
			$query->where("id IN (SELECT record_id FROM #__js_res_record_values WHERE field_id = {$fieldIsYmarket} AND field_value = {$isUpload})");
		}
		$decText = urldecode($stext);
		if (!empty($stext)) {
		    $query->where("title LIKE '%{$stext}%'");
		}
		$query->where("published=1");
		$query->order('title');
//		$db->setQuery($query,0,10);
//        b0dd($query);
		$db->setQuery($query);
		$list = $db->loadObjectList();
//		jexit(b0debug($list));
		
        $count = 0;
		foreach ($list as $item) {
			$created = new Date($item->ctime);
			$fields = json_decode($item->fields, true, 512, JSON_THROW_ON_ERROR);
			switch ($sectionId) {
                case AccessoryIds::ID_SECTION:
                    $code = $fields[AccessoryIds::ID_PRODUCT_CODE];
	                $url = '/accessories/item/'.$item->id.'-'.$item->alias;
	                break;
                case SparepartIds::ID_SECTION:
                    $code = $fields[SparepartIds::ID_PRODUCT_CODE];
	                $url = '/spareparts/item/'.$item->id.'-'.$item->alias;
	                break;
            }
            
			$result += [
				++$count => [
                    'code' => $code,
					'title' => $item->title,
                    'url' => $url,
					'is_on_market' => $this->setIsOnMarket($sectionId, $fields),
					'ymtitle' => $this->setYMTitle($sectionId, $fields),
                    'ymimage' => $this->setYMImage($sectionId, $fields),
                    'ymcategory' => $this->setYMCategory($sectionId, $fields),
                ]
			];
		}
		$this->renderXls($result);
		$data = json_encode($result, JSON_THROW_ON_ERROR);
		JExit($data);
	}
	
	private function setIsOnMarket($sectionId, $fields): ?string
	{
        switch ($sectionId) {
            case AccessoryIds::ID_SECTION:
	            if (!isset($fields[AccessoryIds::ID_YM_UPLOAD_ENABLE]) || empty($fields[AccessoryIds::ID_YM_UPLOAD_ENABLE])){
		            return '<span class="uk-text-danger">нет</span>';
	            }
	            if ($fields[AccessoryIds::ID_YM_UPLOAD_ENABLE] == -1 ){
		            return '<span class="uk-text-danger">нет</span>';
	            }
	            return '<span class="uk-text-success">да</span>';
	        case SparepartIds::ID_SECTION:
		        if (!isset($fields[SparepartIds::ID_YM_UPLOAD_ENABLE]) || empty($fields[SparepartIds::ID_YM_UPLOAD_ENABLE])){
			        return '<span class="uk-text-danger">нет</span>';
		        }
		        if ($fields[SparepartIds::ID_YM_UPLOAD_ENABLE] == -1 ){
			        return '<span class="uk-text-danger">нет</span>';
		        }
		        return '<span class="uk-text-success">да</span>';
        }
    }
	private function setYMTitle($sectionId, $fields): ?string
	{
        switch ($sectionId) {
            case AccessoryIds::ID_SECTION:
                return $fields[AccessoryIds::ID_YM_TITLE] ?? '';
            case SparepartIds::ID_SECTION:
	            return $fields[SparepartIds::ID_YM_TITLE] ?? '';
        }
    }
	private function setYMImage($sectionId, $fields)
    {
        switch ($sectionId) {
            case AccessoryIds::ID_SECTION:
                if (!isset($fields[AccessoryIds::ID_YM_IMAGE]) || empty($fields[AccessoryIds::ID_YM_IMAGE])){
                    return '<span class="uk-text-danger">нет</span>';
                }
	            return '<span class="uk-text-success">да</span>';
            case SparepartIds::ID_SECTION:
	            if (!isset($fields[SparepartIds::ID_YM_IMAGE]) || empty($fields[SparepartIds::ID_YM_IMAGE])){
		            return '<span class="uk-text-danger">нет</span>';
	            }
	            return '<span class="uk-text-success">да</span>';
        }
    }
	private function setYMCategory($sectionId, $fields)
	{
		switch ($sectionId) {
			case AccessoryIds::ID_SECTION:
//			    b0debug(implode(' | ',$fields[AccessoryIds::ID_YM_CATEGORY][0]));
				return implode(' | ',$fields[AccessoryIds::ID_YM_CATEGORY][0]) ?? '';
			case SparepartIds::ID_SECTION:
				return implode(' | ',$fields[SparepartIds::ID_YM_CATEGORY][0]) ?? '';
		}
	}
	private function renderXls($items)
    {
        $siteName = JFactory::getApplication()->get('sitename');
	    $slogan = 'Анализ выгрузки на Яндекс Маркет';
	    $objPhpExcel = new PHPExcel;
	    $objPhpExcel->setActiveSheetIndex(0);
	    $active_sheet = $objPhpExcel->getActiveSheet();
	    // Параметры страницы
	    $active_sheet->getPageSetup()->setOrientation(PHPExcel_Worksheet_PageSetup::ORIENTATION_LANDSCAPE);
	    $active_sheet->getPageSetup()->setPaperSize(PHPExcel_Worksheet_PageSetup::PAPERSIZE_A4);
	
	    $active_sheet->getPageMargins()->setTop(1);
	    $active_sheet->getPageMargins()->setRight(0.75);
	    $active_sheet->getPageMargins()->setLeft(0.75);
	    $active_sheet->getPageMargins()->setBottom(1);
	
	    $active_sheet->setTitle("Выгрузка на Яндекс Маркет");
	
	    $active_sheet->getHeaderFooter()->setOddHeader($siteName);
	    $active_sheet->getHeaderFooter()->setOddFooter('&L&B'.$active_sheet->getTitle().'&RСтраница &P из &N');
	
	    $objPhpExcel->getDefaultStyle()->getFont()->setName('Calibri');
	    $objPhpExcel->getDefaultStyle()->getFont()->setSize(10);
	
	    //Параметры столбцов
	    $active_sheet->setCellValue('A6','№');
	    $active_sheet->getColumnDimension('A')->setWidth(7);
	    
	    $active_sheet->setCellValue('B6','Код товара');
	    $active_sheet->getColumnDimension('B')->setWidth(10);
	    
	    $active_sheet->setCellValue('C6','Наименование');
	    $active_sheet->getColumnDimension('C')->setWidth(75);
	    
	    $active_sheet->setCellValue('D6','Есть на Маркете');
	    $active_sheet->getColumnDimension('D')->setWidth(10);
	    
	    $active_sheet->setCellValue('E6','Название');
	    $active_sheet->getColumnDimension('E')->setWidth(75);
	    
	    $active_sheet->setCellValue('F6','Картинка');
	    $active_sheet->getColumnDimension('F')->setWidth(10);
	    
	    $active_sheet->setCellValue('G6','Категория');
	    $active_sheet->getColumnDimension('G')->setWidth(75);
	
	    //Слоган документа
	    $active_sheet->mergeCells('A2:G2');
	    $active_sheet->setCellValue('A2',$slogan);
	    $style_slogan = array(
		    'font'=>array(
			    //'bold' => true,
			    //'italic' => true,
			    'name' => 'Roboto',
			    'size' => 12,
			    'color'=>array(
				    'rgb' => '666666'
			    )
		    ),
		    'alignment' => array(
			    'horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_CENTER,
			    'vertical' => PHPExcel_Style_Alignment::VERTICAL_CENTER,
		    ),
		    'fill' => array(
			    'type' => PHPExcel_Style_Fill::FILL_SOLID,
			    'color'=>array(
				    'rgb' => 'F6F6F6'
			    )
		    ),
		    'borders' => array(
			    'bottom' => array(
				    'style'=>PHPExcel_Style_Border::BORDER_THIN
			    )
		    )
	    );
	    $active_sheet->getStyle('A2:G2')->applyFromArray($style_slogan);
	
	    //Дата создания
	    $active_sheet->mergeCells('A4:F4');
	    $active_sheet->setCellValue('A4','Дата создания:');
	
	    $date = date('d-m-Y');
	    $active_sheet->setCellValue('G4',$date);
	    $active_sheet->getStyle('G4')->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_DATE_XLSX14);
	    $style_tdate = array(
		    'alignment' => array(
			    'horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_RIGHT,
		    ),
		    'fill' => array(
			    'type' => PHPExcel_Style_Fill::FILL_SOLID,
			    'color'=>array(
				    'rgb' => 'F6F6F6'
			    )
		    ),
		    'borders' => array(
			    'right' => array(
				    'style'=>PHPExcel_Style_Border::BORDER_NONE
			    )
		    )
	    );
	    $active_sheet->getStyle('A4:G4')->applyFromArray($style_tdate);
	
	    $style_date = array(
		    'fill' => array(
			    'type' => PHPExcel_Style_Fill::FILL_SOLID,
			    'color'=>array(
				    'rgb' => 'F6F6F6'
			    )
		    ),
		    'borders' => array(
			    'left' => array(
				    'style'=>PHPExcel_Style_Border::BORDER_NONE
			    )
		    ),
	    );
	    $active_sheet->getStyle('G4')->applyFromArray($style_date);
	
	    $active_sheet->getStyle('G:G')
		    ->getNumberFormat()
		    ->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
	
	    $rowStart = 7;
	    $i = 0;
	    foreach($items as $key => $item) {
		    $row_next = $rowStart + $i;
		    $rowNumber = $i+1;
		
//		    $fields = json_decode($item->fields, TRUE);
		
		    $active_sheet->setCellValueExplicit('A' . $row_next, $rowNumber, PHPExcel_Cell_DataType::TYPE_NUMERIC);
		    $active_sheet->setCellValueExplicit('B' . $row_next, $item['code'], PHPExcel_Cell_DataType::TYPE_STRING);
		    $active_sheet->setCellValueExplicit('C' . $row_next, $item['title'], PHPExcel_Cell_DataType::TYPE_STRING);
		    $active_sheet->setCellValueExplicit('D' . $row_next, strip_tags($item['is_on_market']), PHPExcel_Cell_DataType::TYPE_STRING);
		    $active_sheet->setCellValueExplicit('E' . $row_next, $item['ymtitle'], PHPExcel_Cell_DataType::TYPE_STRING);
		    $active_sheet->setCellValueExplicit('F' . $row_next, strip_tags($item['ymimage']), PHPExcel_Cell_DataType::TYPE_STRING);
		    $active_sheet->setCellValueExplicit('G' . $row_next, $item['ymcategory'], PHPExcel_Cell_DataType::TYPE_STRING);
		    $i++;
	    }
	
	    //Основная таблица
	    $style_hprice = array(
		    'alignment' => array(
			    'horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_CENTER,
		    ),
		    'fill' => array(
			    'type' => PHPExcel_Style_Fill::FILL_SOLID,
			    'color'=>array(
				    'rgb' => 'F6F6F6'
			    )
		    ),
		    'font'=>array(
			    'bold' => true,
			    //'italic' => true,
			    'name' => 'Calibri',
			    'size' => 10
		    ),
	    );
	    $active_sheet->getStyle('A6:G6')->applyFromArray($style_hprice);
	
	    $style_price = array(
		    'alignment' => array(
			    'horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_CENTER
		    )
	    );
	    $active_sheet->getStyle('A7:B'.($i+6))->applyFromArray($style_price);
	    $active_sheet->getStyle('D7:D'.($i+6))->applyFromArray($style_price);
	    $active_sheet->getStyle('F7:F'.($i+6))->applyFromArray($style_price);
	
	    $style_price_title = array(
		    'alignment' => array(
			    'horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_LEFT
		    )
	    );
	    $active_sheet->getStyle('C7:C'.($i+6))->applyFromArray($style_price_title);
	
	    $objWriter = new PHPExcel_Writer_Excel2007($objPhpExcel);
	    $objWriter->save(JPATH_ROOT . '/logs/b0report-ymarket.xlsx');
    }
}
?>

