<?php
defined('_JEXEC') or die;
// Вызов - https://stovesta.ru/index.php?option=com_cobalt&task=b0created.index

use Joomla\CMS\Date\Date;
use Joomla\CMS\Factory;
JImport('b0.Accessory.AccessoryIds');
JImport('b0.Sparepart.SparepartIds');
JImport('b0.Work.WorkIds');
JImport('phpexcel.PHPExcel');
JImport('phpexcel.PHPExcel.Writer.Excel2007');
JImport('b0.fixtures');
?>
<?php
class CobaltControllerB0Created extends JControllerForm
{
	public function index()
	{ ?>
		<div id="app">
            <h3>Выводим созданные за период</h3>
            <div class="uk-form">
                Раздел
                <select v-model="section_id">
                    <option value="<?= AccessoryIds::ID_SECTION ?>">Аксессуары</option>
                    <option value="<?= SparepartIds::ID_SECTION ?>">Запчасти</option>
                    <option value="<?= WorkIds::ID_SECTION ?>">Работы</option>
                </select>
                <span class="uk-margin-left">Дата начала</span>
                <input type="date" v-model.lazy="start">
                <span class="uk-margin-left">Дата окончания</span>
                <input type="date" v-model.lazy="finish">
                <span class="uk-margin-left">Текст</span>
                <input type="text" v-model.lazy="stext">
                <a href="/logs/b0-created.xlsx" class="uk-margin-left" title="Скачать отчет в формате Excel" download>
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
                            <th>Дата создания</th>
                            <th>Картинка</th>
                            <th>Описание</th>
                            <th>Работы</th>
                            <th>Аналоги</th>
                            <th>Сопутствующие</th>
                            <th>Видео</th>
                            <th>Мета</th>
                            <th>Маркет</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="(item, key) in items">
                            <td>{{ key }}</td>
                            <td>{{item.code}}</td>
                            <td><a :href="item.url" target="_blank">{{item.title}}</a></td>
                            <td>{{item.created}}</td>
                            <td class="uk-text-center"><span v-html="item.image"></span></td>
                            <td class="uk-text-center"><span v-html="item.description"></span></td>
                            <td class="uk-text-center"><span v-html="item.works"></span></td>
                            <td class="uk-text-center"><span v-html="item.analogs"></span></td>
                            <td class="uk-text-center"><span v-html="item.related"></span></td>
                            <td class="uk-text-center"><span v-html="item.video"></span></td>
                            <td class="uk-text-center"><span v-html="item.meta"></span></td>
                            <td class="uk-text-center"><span v-html="item.market"></span></td>
                        </tr>
                    </tbody>
                </table>
            </div>
		</div>

<!--        <script src="https://cdn.jsdelivr.net/npm/vue@2/dist/vue.js"></script>-->
		<script src="https://cdn.jsdelivr.net/npm/vue@2"></script>
        <script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
		<script>
            var app = new Vue({
                el: '#app',
                data: {
                    items: {},
                    section_id: <?= AccessoryIds::ID_SECTION ?>,
                    start: '2021-04-01',
                    finish: '2021-04-30',
                    stext: '',
                },
                mounted() {
                    this.getResults();
                },
                watch: {
                    section_id(value) { this.getResults(); },
                    start(value) { this.getResults(); },
                    finish(value) { this.getResults(); },
                    stext(value) { this.getResults(); }
                },
                methods: {
                    getResults(page = 1) {
                        axios.get('/index.php?option=com_cobalt&task=b0created.getItems'
                            + '&section_id=' + this.section_id
                            + '&start=' + this.start
                            + '&finish=' + this.finish
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
		$dateStart = $input->get('start', '2020-09-01');
		$dateFinish = $input->get('finish', '2020-09-30');
		$stext = $_GET['stext'];
//		jexit(b0debug($stext));
		$result = [];

		$db = JFactory::getDbo();
		$query = $db->getQuery(TRUE);
		$query->select('id, title, alias, ctime, fields, meta_descr');
		$query->from('#__js_res_record');
		$query->where("section_id = {$sectionId}");
		$query->where("ctime >= '{$dateStart}'");
		$query->where("ctime <= '{$dateFinish}'");
		$decText = urldecode($stext);
		if (!empty($stext)) {
		    $query->where("title LIKE '%{$stext}%'");
		}
		$query->where("published=1");
		$query->order('ctime');
//		$db->setQuery($query,0,10);
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
                case WorkIds::ID_SECTION:
                    $code = $fields[WorkIds::ID_SERVICE_CODE];
	                $url = '/repair/item/'.$item->id.'-'.$item->alias;
	                break;
            }
            
			$result += [
				++$count => [
                    'code' => $code,
					'title' => $item->title,
                    'url' => $url,
					'created' => $created->format('d-m-Y'),
                    'image' => $this->setImage($sectionId, $fields),
                    'description' => $this->setDescription($sectionId, $fields),
                    'works' => $this->setWorks($sectionId, $fields),
                    'analogs' => $this->setAnalogs($sectionId, $fields),
                    'related' => $this->setRelated($sectionId, $fields),
                    'video' => $this->setVideo($sectionId, $fields),
                    'meta' => $this->setMeta($item),
                    'market' => $this->setMarket($sectionId, $fields),
                ]
			];
		}
		$this->renderXls($result);
		$data = json_encode($result, JSON_THROW_ON_ERROR);
		JExit($data);
	}
	
	private function setImage($sectionId, $fields)
    {
        switch ($sectionId) {
            case AccessoryIds::ID_SECTION:
                if (!isset($fields[AccessoryIds::ID_IMAGE]) || empty($fields[AccessoryIds::ID_IMAGE])){
                    return '<span class="uk-text-danger">нет</span>';
                }
	            return '<span class="uk-text-success">да</span>';
	            break;
            case SparepartIds::ID_SECTION:
	            if (!isset($fields[SparepartIds::ID_IMAGE]) || empty($fields[SparepartIds::ID_IMAGE])){
		            return '<span class="uk-text-danger">нет</span>';
	            }
	            return '<span class="uk-text-success">да</span>';
                break;
            case WorkIds::ID_SECTION:
	            if (!isset($fields[WorkIds::ID_IMAGE]) || empty($fields[WorkIds::ID_IMAGE])){
		            return '<span class="uk-text-danger">нет</span>';
	            }
	            return '<span class="uk-text-success">да</span>';
                break;
        }
    }
	private function setVideo($sectionId, $fields)
    {
        switch ($sectionId) {
            case AccessoryIds::ID_SECTION:
                if (!isset($fields[AccessoryIds::ID_VIDEO]) || empty($fields[AccessoryIds::ID_VIDEO]['link'])){
                    return '<span class="uk-text-danger">нет</span>';
                }
	            return '<span class="uk-text-success">да</span>';
	            break;
            case SparepartIds::ID_SECTION:
	            if (!isset($fields[SparepartIds::ID_VIDEO]) || empty($fields[SparepartIds::ID_VIDEO]['link'])){
		            return '<span class="uk-text-danger">нет</span>';
	            }
	            return '<span class="uk-text-success">да</span>';
                break;
            case WorkIds::ID_SECTION:
	            if (!isset($fields[WorkIds::ID_VIDEO]) || empty($fields[WorkIds::ID_VIDEO]['link'])){
		            return '<span class="uk-text-danger">нет</span>';
	            }
	            return '<span class="uk-text-success">да</span>';
                break;
        }
    }
	private function setDescription($sectionId, $fields)
    {
        switch ($sectionId) {
            case AccessoryIds::ID_SECTION:
                if (!isset($fields[AccessoryIds::ID_DESCRIPTION]) || empty($fields[AccessoryIds::ID_DESCRIPTION])){
                    return '<span class="uk-text-danger">нет</span>';
                }
	            return '<span class="uk-text-success">да</span>';
	            break;
            case SparepartIds::ID_SECTION:
	            if (!isset($fields[SparepartIds::ID_DESCRIPTION]) || empty($fields[SparepartIds::ID_DESCRIPTION])){
		            return '<span class="uk-text-danger">нет</span>';
	            }
	            return '<span class="uk-text-success">да</span>';
                break;
            case WorkIds::ID_SECTION:
	            if (!isset($fields[WorkIds::ID_DESCRIPTION]) || empty($fields[WorkIds::ID_DESCRIPTION])){
		            return '<span class="uk-text-danger">нет</span>';
	            }
	            return '<span class="uk-text-success">да</span>';
                break;
        }
    }
	private function setWorks($sectionId, $fields)
    {
        switch ($sectionId) {
            case AccessoryIds::ID_SECTION:
                if (!isset($fields[AccessoryIds::ID_WORKS]) || empty($fields[AccessoryIds::ID_WORKS])){
                    return '<span class="uk-text-danger">нет</span>';
                }
	            return '<span class="uk-text-success">да</span>';
	            break;
            case SparepartIds::ID_SECTION:
	            if (!isset($fields[SparepartIds::ID_WORKS]) || empty($fields[SparepartIds::ID_WORKS])){
		            return '<span class="uk-text-danger">нет</span>';
	            }
	            return '<span class="uk-text-success">да</span>';
                break;
            case WorkIds::ID_SECTION:
	            if (!isset($fields[WorkIds::ID_WORKS]) || empty($fields[WorkIds::ID_WORKS])){
		            return '<span class="uk-text-danger">нет</span>';
	            }
	            return '<span class="uk-text-success">да</span>';
                break;
        }
    }
	private function setAnalogs($sectionId, $fields)
    {
        switch ($sectionId) {
            case AccessoryIds::ID_SECTION:
                if (!isset($fields[AccessoryIds::ID_ANALOGS]) || empty($fields[AccessoryIds::ID_ANALOGS])){
                    return '<span class="uk-text-danger">нет</span>';
                }
	            return '<span class="uk-text-success">да</span>';
	            break;
            case SparepartIds::ID_SECTION:
	            if (!isset($fields[SparepartIds::ID_ANALOGS]) || empty($fields[SparepartIds::ID_ANALOGS])){
		            return '<span class="uk-text-danger">нет</span>';
	            }
	            return '<span class="uk-text-success">да</span>';
                break;
            case WorkIds::ID_SECTION:
		            return '';
                break;
        }
    }
	private function setRelated($sectionId, $fields)
    {
        switch ($sectionId) {
            case AccessoryIds::ID_SECTION:
                if (!isset($fields[AccessoryIds::ID_ASSOCIATED]) || empty($fields[AccessoryIds::ID_ASSOCIATED])){
                    return '<span class="uk-text-danger">нет</span>';
                }
	            return '<span class="uk-text-success">да</span>';
	            break;
            case SparepartIds::ID_SECTION:
	            if (!isset($fields[SparepartIds::ID_ASSOCIATED]) || empty($fields[SparepartIds::ID_ASSOCIATED])){
		            return '<span class="uk-text-danger">нет</span>';
	            }
	            return '<span class="uk-text-success">да</span>';
                break;
            case WorkIds::ID_SECTION:
		            return '';
                break;
        }
    }
	private function setMeta($item)
	{
        if (!isset($item->meta_descr) || empty($item->meta_descr)){
            return '<span class="uk-text-danger">нет</span>';
        }
        return '<span class="uk-text-success">да</span>';
	}
	private function setMarket($sectionId, $fields)
	{
		switch ($sectionId) {
			case AccessoryIds::ID_SECTION:
				if (!isset($fields[AccessoryIds::ID_YM_UPLOAD_ENABLE]) || empty($fields[AccessoryIds::ID_YM_UPLOAD_ENABLE])){
					return '<span class="uk-text-danger">нет</span>';
				}
                if ($fields[AccessoryIds::ID_YM_UPLOAD_ENABLE] === 1) {
	                return '<span class="uk-text-success">да</span>';
                }
				return '<span class="uk-text-success">нет</span>';
				break;
			case SparepartIds::ID_SECTION:
				if (!isset($fields[SparepartIds::ID_YM_UPLOAD_ENABLE]) || empty($fields[SparepartIds::ID_YM_UPLOAD_ENABLE])){
					return '<span class="uk-text-danger">нет</span>';
				}
				if ($fields[SparepartIds::ID_YM_UPLOAD_ENABLE] === 1) {
					return '<span class="uk-text-success">да</span>';
				}
				return '<span class="uk-text-success">нет</span>';
				break;
			case WorkIds::ID_SECTION:
				return '';
				break;
		}
	}
 
	private function renderXls($items)
    {
        $siteName = JFactory::getApplication()->get('sitename');
	    $slogan = 'Анализ заполнения карточек товаров';
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
	
	    $active_sheet->setTitle("Анализ заполнения");
	
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
	    $active_sheet->setCellValue('D6','Дата создания');
	    $active_sheet->getColumnDimension('D')->setWidth(13);
	    $active_sheet->setCellValue('E6','Картинка');
	    $active_sheet->getColumnDimension('E')->setWidth(10);
	    $active_sheet->setCellValue('F6','Описание');
	    $active_sheet->getColumnDimension('F')->setWidth(10);
	    $active_sheet->setCellValue('G6','Работы');
	    $active_sheet->getColumnDimension('G')->setWidth(10);
	    $active_sheet->setCellValue('H6','Аналоги');
	    $active_sheet->getColumnDimension('H')->setWidth(10);
	    $active_sheet->setCellValue('I6','Сопутств');
	    $active_sheet->getColumnDimension('I')->setWidth(10);
	    $active_sheet->setCellValue('J6','Видео');
	    $active_sheet->getColumnDimension('J')->setWidth(10);
	    $active_sheet->setCellValue('K6','Мета');
	    $active_sheet->getColumnDimension('K')->setWidth(10);
	    $active_sheet->setCellValue('L6','Маркет');
	    $active_sheet->getColumnDimension('L')->setWidth(10);
	
	    //Шапка документа
//	    $active_sheet->mergeCells('A1:K1');
//	    $active_sheet->getRowDimension('1')->setRowHeight(40);
//	    $active_sheet->setCellValue('A1',$siteName);
//	    $style_header = array(
//		    'font'=>array(
//			    'bold' => true,
//			    'name' => 'Roboto Condensed',
//			    'size' => 20
//		    ),
//		    'alignment' => array(
//			    'horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_CENTER,
//			    'vertical' => PHPExcel_Style_Alignment::VERTICAL_CENTER,
//		    ),
//		    'fill' => array(
//			    'type' => PHPExcel_Style_Fill::FILL_SOLID,
//			    'color'=>array(
//				    'rgb' => 'F6F6F6'
//			    )
//		    )
//	    );
//	    $active_sheet->getStyle('A1:K1')->applyFromArray($style_header);
	
	    //Слоган документа
	    $active_sheet->mergeCells('A2:K2');
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
	    $active_sheet->getStyle('A2:K2')->applyFromArray($style_slogan);
	
	    //Дата создания
	    $active_sheet->mergeCells('A4:J4');
	    $active_sheet->setCellValue('A4','Дата создания:');
	
	    $date = date('d-m-Y');
	    $active_sheet->setCellValue('K4',$date);
	    $active_sheet->getStyle('K4')->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_DATE_XLSX14);
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
	    $active_sheet->getStyle('A4:J4')->applyFromArray($style_tdate);
	
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
	    $active_sheet->getStyle('K4')->applyFromArray($style_date);
	
	    $active_sheet->getStyle('K:K')
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
		    $active_sheet->setCellValueExplicit('D' . $row_next, $item['created'], PHPExcel_Cell_DataType::TYPE_STRING);
		    $active_sheet->setCellValueExplicit('E' . $row_next, strip_tags($item['image']), PHPExcel_Cell_DataType::TYPE_STRING);
		    $active_sheet->setCellValueExplicit('F' . $row_next, strip_tags($item['description']), PHPExcel_Cell_DataType::TYPE_STRING);
		    $active_sheet->setCellValueExplicit('G' . $row_next, strip_tags($item['works']), PHPExcel_Cell_DataType::TYPE_STRING);
		    $active_sheet->setCellValueExplicit('H' . $row_next, strip_tags($item['analogs']), PHPExcel_Cell_DataType::TYPE_STRING);
		    $active_sheet->setCellValueExplicit('I' . $row_next, strip_tags($item['related']), PHPExcel_Cell_DataType::TYPE_STRING);
		    $active_sheet->setCellValueExplicit('J' . $row_next, strip_tags($item['video']), PHPExcel_Cell_DataType::TYPE_STRING);
		    $active_sheet->setCellValueExplicit('K' . $row_next, strip_tags($item['meta']), PHPExcel_Cell_DataType::TYPE_STRING);
		    $active_sheet->setCellValueExplicit('L' . $row_next, strip_tags($item['market']), PHPExcel_Cell_DataType::TYPE_STRING);
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
	    $active_sheet->getStyle('A6:L6')->applyFromArray($style_hprice);
	
	    $style_price = array(
		    'alignment' => array(
			    'horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_CENTER
		    )
	    );
	    $active_sheet->getStyle('A7:B'.($i+6))->applyFromArray($style_price);
	    $active_sheet->getStyle('D7:L'.($i+6))->applyFromArray($style_price);
	
	    $style_price_title = array(
		    'alignment' => array(
			    'horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_LEFT
		    )
	    );
	    $active_sheet->getStyle('C7:C'.($i+6))->applyFromArray($style_price_title);
	
	    $objWriter = new PHPExcel_Writer_Excel2007($objPhpExcel);
	    $objWriter->save(JPATH_ROOT . '/logs/b0-created.xlsx');
    }
}
?>

