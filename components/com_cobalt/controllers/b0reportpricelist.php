<?php
defined('_JEXEC') or die;

JImport('b0.Accessory.AccessoryIds');
JImport('b0.Sparepart.SparepartIds');
JImport('b0.Work.WorkIds');
JImport('phpexcel.PHPExcel');
JImport('phpexcel.PHPExcel.Writer.Excel2007');
JImport('b0.fixtures');

class CobaltControllerB0ReportPricelist extends JControllerAdmin
{
    protected $filePath = '/logs/pricelist.xlsx';
	protected $slogan = [
		AccessoryIds::ID_SECTION => 'Аксессуары для Lada Vesta, XRay, Granta',
		SparepartIds::ID_SECTION => 'Запчасти для Lada Vesta, XRay, Granta',
		WorkIds::ID_SECTION => 'Работы по ремонту Lada Vesta, XRay, Granta',
	];
	protected $siteName = '';
	
	public function index()
	{ ?>
        <div id="app">
            <h3>Сформировать прайс-лист</h3>
            <hr class="uk-article-divider">
            <div class="uk-navbar uk-margin-top">
                <div class="uk-navbar-nav">
                    <div class="uk-form">
                        <fieldset>
                            <div class="uk-form-row">
                                <select v-model="section_id">
                                    <option value="<?= AccessoryIds::ID_SECTION ?>">Аксессуары</option>
                                    <option value="<?= SparepartIds::ID_SECTION ?>">Запчасти</option>
                                    <option value="<?= WorkIds::ID_SECTION ?>">Работы</option>
                                </select>
                                <button class="uk-button uk-button-success uk-margin-large-left" v-on:click="getResults">
                                    Сформировать
                                </button>
                            </div>
                        </fieldset>
                    </div>
                </div>
    
                <div class="uk-navbar-flip">
                    <ul class="uk-subnav uk-subnav-line">
                        <li>
                            <a href="<?= $this->filePath ?>" class="uk-margin-left" title="Скачать прайс-лист в формате Excel" download>
                                <i class="uk-icon-file-excel-o uk-icon-small uk-margin-left uk-margin-right"></i>Скачать прайс-лист
                            </a>
                        </li>
                    </ul>
                </div>
            </div>
            <p v-if="result.length">
                {{result}}
            </p>
        </div>

        <script src="https://cdn.jsdelivr.net/npm/vue@2"></script>
        <script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
        <script>
            var app = new Vue({
                el: '#app',
                data: {
                    result: '',
                    section_id: <?= AccessoryIds::ID_SECTION ?>,
                },
                methods: {
                    getResults() {
                        this.result = '';
                        axios.get('/index.php?option=com_cobalt&task=b0reportpricelist.createXls'
                            + '&section_id=' + this.section_id
                        )
                        .then(response => {
                            this.result = 'Прайс-лист сформирован';
                            // console.log(this.result);
                        });
                    },
                },
            })
        </script>
	
	<?php }
	
	public function createXls() {
		$logs = [];
		$app = JFactory::getApplication();
		$this->siteName = $app->get('sitename');
		$section = $app->input->get('section_id', AccessoryIds::ID_SECTION);
		
		$items = $this->getPrice($section);
		
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
		
		$active_sheet->setTitle("Прайс-лист");
		
		$active_sheet->getHeaderFooter()->setOddHeader($this->siteName);
		$active_sheet->getHeaderFooter()->setOddFooter('&L&B'.$active_sheet->getTitle().'&RСтраница &P из &N');
		
		$objPhpExcel->getDefaultStyle()->getFont()->setName('Calibri');
		$objPhpExcel->getDefaultStyle()->getFont()->setSize(10);
		
		//Параметры столбцов
		$active_sheet->setCellValue('A6','№');
		$active_sheet->getColumnDimension('A')->setWidth(7);
		$active_sheet->setCellValue('B6','Код товара');
		$active_sheet->getColumnDimension('B')->setWidth(10);
		$active_sheet->setCellValue('C6','Наименование');
		$active_sheet->getColumnDimension('C')->setWidth(60);
		$active_sheet->setCellValue('D6','Цена, руб.');
		$active_sheet->getColumnDimension('D')->setWidth(10);
		
		//Шапка документа
		$active_sheet->mergeCells('A1:D1');
		$active_sheet->getRowDimension('1')->setRowHeight(40);
		$active_sheet->setCellValue('A1',$this->siteName);
		$style_header = array(
			'font'=>array(
				'bold' => true,
				'name' => 'Roboto Condensed',
				'size' => 20
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
			)
		);
		$active_sheet->getStyle('A1:D1')->applyFromArray($style_header);
		
		//Слоган документа
		$active_sheet->mergeCells('A2:D2');
		$active_sheet->setCellValue('A2',$this->slogan[$section]);
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
		$active_sheet->getStyle('A2:D2')->applyFromArray($style_slogan);
		
		//Дата создания
		$active_sheet->mergeCells('A4:C4');
		$active_sheet->setCellValue('A4','Дата создания:');
		
		$date = date('d-m-Y');
		$active_sheet->setCellValue('D4',$date);
		$active_sheet->getStyle('D4')->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_DATE_XLSX14);
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
		$active_sheet->getStyle('A4:C4')->applyFromArray($style_tdate);
		
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
		$active_sheet->getStyle('D4')->applyFromArray($style_date);
		
		$active_sheet->getStyle('D:D')
			->getNumberFormat()
			->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
		
		$rowStart = 7;
		$i = 0;
		foreach($items as $item) {
			$row_next = $rowStart + $i;
			$rowNumber = $i+1;
			
			$fields = json_decode($item->fields, TRUE);
			
			$active_sheet->setCellValueExplicit('A' . $row_next, $rowNumber, PHPExcel_Cell_DataType::TYPE_NUMERIC);
			$active_sheet->setCellValueExplicit('B' . $row_next, $this->getProductCode($section, $fields), PHPExcel_Cell_DataType::TYPE_STRING);
			$active_sheet->setCellValueExplicit('C' . $row_next, $item->title, PHPExcel_Cell_DataType::TYPE_STRING);
			$active_sheet->setCellValueExplicit('D' . $row_next, $this->getPriceGeneral($section, $fields), PHPExcel_Cell_DataType::TYPE_NUMERIC);
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
		$active_sheet->getStyle('A6:D6')->applyFromArray($style_hprice);
		
		$style_price = [
			'alignment' => [
				'horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_CENTER
			]
		];
		$active_sheet->getStyle('A7:B'.($i+6))->applyFromArray($style_price);
		$active_sheet->getStyle('D7:K'.($i+6))->applyFromArray($style_price);
		
		$style_price_title = array(
			'alignment' => array(
				'horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_LEFT
			)
		);
		$active_sheet->getStyle('C7:C'.($i+6))->applyFromArray($style_price_title);
		
		$objWriter = new PHPExcel_Writer_Excel2007($objPhpExcel);
		$objWriter->save(JPATH_ROOT . $this->filePath);
		
		$logs[] = 'Прайс-лист '. $section .' сформирован';
//		$this->sendMail($logs, $section);
		JExit('Прайс-лист '. $section .' сформирован');
	}
	
	private function getPrice($section)	{
		
		$db = JFactory::getDbo();
		$query = $db->getQuery(TRUE);
		$query->select('id,title,fields');
		$query->from('#__js_res_record');
		$query->where('section_id = ' . $section);
		$query->where('published = 1');
		$query->order('title');
		//$db->setQuery($query, 0, 5);
		$db->setQuery($query);
//		$items = $db->loadObjectList();
		return $db->loadObjectList();
	}
	
	private function getProductCode($section, $fields) {
		switch ($section) {
			case AccessoryIds::ID_SECTION:
				return $fields[AccessoryIds::ID_PRODUCT_CODE];
			case SparepartIds::ID_SECTION:
				return $fields[SparepartIds::ID_PRODUCT_CODE];
			case WorkIds::ID_SECTION:
				return $fields[WorkIds::ID_SERVICE_CODE];
			default:
				JExit('Прайс-лист не сформирован - getProductCode');
		}
	}
	
	private function getPriceGeneral($section, $fields) {
		switch ($section) {
			case AccessoryIds::ID_SECTION:
				return $fields[AccessoryIds::ID_PRICE_GENERAL];
			case SparepartIds::ID_SECTION:
				return $fields[SparepartIds::ID_PRICE_GENERAL];
			case WorkIds::ID_SECTION:
				return $fields[WorkIds::ID_PRICE_GENERAL];
			default:
				JExit('Прайс-лист не сформирован - getPriceGeneral');
		}
	}
}
