<?php
defined('_JEXEC') or die;

JImport('phpexcel.PHPExcel');
JImport('phpexcel.PHPExcel.Writer.Excel2007');
JImport('b0.Sparepart.SparepartIds');
JImport('b0.Accessory.AccessoryIds');
JImport('b0.Work.WorkIds');
JImport('b0.fixtures');

/**
 * Class CobaltControllerB0Xls
 */
class CobaltControllerB0Xls extends JControllerLegacy
{
	protected $siteName = '';
	protected $filePath = [
		'spareparts' => '/pricelist-spareparts.xlsx',
		'accessories' => '/pricelist-accessories.xlsx',
		'works' => '/pricelist-works.xlsx',
	];
	protected $slogan = [
		'spareparts' => 'Запчасти для Lada Vesta и Lada XRay',
		'accessories' => 'Аксессуары для Lada Vesta и Lada XRay',
		'works' => 'Работы по ремонту Lada Vesta и Lada XRay',
	];
	protected $email = [
		'from' => 'admin@stovesta.ru',
		'fromName' => 'Admin',
		'recipient' => 'p.ivanov@stovesta.ru',
	];

	// Вызов https://stovesta.ru/index.php?option=com_cobalt&task=b0xls.createXls&section=spareparts
	// Вызов https://stovesta.ru/index.php?option=com_cobalt&task=b0xls.createXls&section=accessories
	// Вызов https://stovesta.ru/index.php?option=com_cobalt&task=b0xls.createXls&section=works
	public function createXls() {
		/**
		 * @var array $logs Массив сообщений логов
		 */
		$logs = [];
		$this->siteName = JFactory::getApplication()->get('sitename');
		$section = $_GET['section'];

		$items = $this->getPrice($section);

		/** @var PHPExcel $objPhpExcel */
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

		$style_price = array(
			'alignment' => array(
				'horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_CENTER
			)
		);
		$active_sheet->getStyle('A7:B'.($i+6))->applyFromArray($style_price);
		$active_sheet->getStyle('D7:D'.($i+6))->applyFromArray($style_price);

		$style_price_title = array(
			'alignment' => array(
				'horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_LEFT
			)
		);
		$active_sheet->getStyle('C7:C'.($i+6))->applyFromArray($style_price_title);

		$objWriter = new PHPExcel_Writer_Excel2007($objPhpExcel);
		$objWriter->save(JPATH_ROOT . $this->filePath[$section]);

		$logs[] = 'Прайс-лист '. $section .' сформирован';
		$this->sendMail($logs, $section);
		JExit('Прайс-лист '. $section .' сформирован');
	}

	/**
	 * @return mixed
	 */
	private function getPrice($section)	{

		$db = JFactory::getDbo();
		$query = $db->getQuery(TRUE);
		$query->select('id,title,fields');
		$query->from('#__js_res_record');
		if ($section == 'spareparts') {
			$where = 'section_id = ' . SparepartIds::ID_SECTION .' AND published = 1';
		}
		elseif ($section == 'accessories') {
			$where = 'section_id = ' . AccessoryIds::ID_SECTION .' AND published = 1';
		}
		elseif ($section == 'works') {
			$where = 'section_id = ' . WorkIds::ID_SECTION .' AND published = 1';
		}
		else {
			JExit('Прайс-лист не сформирован');
		}
		//$query->where('section_id = 2 AND published = 1');
		$query->where($where);
		$query->order('title');
		//$db->setQuery($query, 0, 5);
		$db->setQuery($query);
		$items = $db->loadObjectList();
		return $items;
	}

	private function getProductCode($section, $fields) {
		if ($section == 'spareparts') {
			return $fields[SparepartIds::ID_PRODUCT_CODE];
		}
		elseif ($section == 'accessories') {
			return $fields[AccessoryIds::ID_PRODUCT_CODE];
		}
		elseif ($section == 'works') {
			return $fields[WorkIds::ID_SERVICE_CODE];
		}
		else {
			JExit('Прайс-лист не сформирован');
		}
	}

	private function getPriceGeneral($section, $fields) {
		if ($section == 'spareparts') {
			return $fields[SparepartIds::ID_PRICE_GENERAL];
		}
		elseif ($section == 'accessories') {
			return $fields[AccessoryIds::ID_PRICE_GENERAL];
		}
		elseif ($section == 'works') {
			return $fields[WorkIds::ID_PRICE_GENERAL];
		}
		else {
			JExit('Прайс-лист не сформирован');
		}
	}

	/**
	 * @param array $logs
	 */
	private function sendMail(array $logs, $section) {
		$messageBody = '';
		foreach ($logs as $log) {
			$messageBody .= $log . "\n";
		}
		$result = JFactory::getMailer()->sendMail($this->email['from'], $this->email['fromName'], $this->email['recipient'], 'Прайс-лист '.$section, $messageBody, TRUE);
		
	}

}