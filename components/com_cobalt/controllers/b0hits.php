<?php
defined('_JEXEC') or die;
// /index.php?option=com_cobalt&task=b0hits.index

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
class CobaltControllerB0Hits extends JControllerForm
{
	public function index()
	{ ?>
		<div id="app">
            <h3>Популярные</h3>
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
                <a href="/logs/b0-hits.xlsx" class="uk-margin-left" title="Скачать отчет в формате Excel" download>
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
                            <th class="uk-text-center">Кол-во просмотров</th>
                            <th class="uk-text-center">Цена</th>
                            <th class="uk-text-center">Артикул</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="(item, key) in items">
                            <td>{{ key }}</td>
                            <td>{{item.code}}</td>
                            <td><a :href="item.url" target="_blank">{{item.title}}</a></td>
                            <td class="uk-text-center"><span v-html="item.hits"></span></td>
                            <td class="uk-text-center"><span v-html="item.price"></span></td>
                            <td class="uk-text-center"><span v-html="item.vendorCode"></span></td>
                        </tr>
                    </tbody>
                </table>
            </div>
		</div>

        <script src="https://cdn.jsdelivr.net/npm/vue@2/dist/vue.js"></script>
<!--		<script src="https://cdn.jsdelivr.net/npm/vue@2"></script>-->
        <script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
		<script>
            var app = new Vue({
                el: '#app',
                data: {
                    items: {},
                    section_id: <?= AccessoryIds::ID_SECTION ?>,
                    start: '2022-12-01',
                    finish: '2022-12-31',
                    stext: '',
                },
                mounted() {
                    this.getResults();
                },
                watch: {
                    section_id(value) { this.getResults(); },
                    start(value) { this.getResults(); },
                    finish(value) { this.getResults(); },
                },
                methods: {
                    getResults(page = 1) {
                        axios.get('/index.php?option=com_cobalt&task=b0hits.getItems'
                            + '&section_id=' + this.section_id
                            + '&start=' + this.start
                            + '&finish=' + this.finish
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
		$dateStart = $input->get('start', '2022-12-01');
		$dateFinish = $input->get('finish', '2022-12-31');
		$result = [];

		$db = JFactory::getDbo();
        $sql = "SELECT h.record_id, r.title, r.alias, r.type_id, r.fields, COUNT(DISTINCT h.id) as hits FROM #__js_res_hits AS h
		LEFT JOIN #__js_res_record AS r ON r.id = h.record_id
        WHERE (h.section_id = {$sectionId} AND h.ctime >= '{$dateStart}' AND h.ctime <= '{$dateFinish}' AND r.published = 1)
		GROUP BY h.record_id
        ORDER BY hits DESC
        LIMIT 0,100
		";
        $db->setQuery($sql);
        $list = $db->loadObjectList();

        $count = 0;
		foreach ($list as $item) {
			$fields = json_decode($item->fields, true, 512, JSON_THROW_ON_ERROR);
			switch ($sectionId) {
                case AccessoryIds::ID_SECTION:
                    $code = $fields[AccessoryIds::ID_PRODUCT_CODE];
                    $price = $fields[AccessoryIds::ID_PRICE_GENERAL];
                    $vendorCode = $fields[AccessoryIds::ID_VENDOR_CODE];
	                $url = '/accessories/item/'.$item->record_id.'-'.$item->alias;
	                break;
                case SparepartIds::ID_SECTION:
                    $code = $fields[SparepartIds::ID_PRODUCT_CODE];
                    $price = $fields[SparepartIds::ID_PRICE_GENERAL];
                    $vendorCode = $fields[SparepartIds::ID_VENDOR_CODE];
	                $url = '/spareparts/item/'.$item->record_id.'-'.$item->alias;
	                break;
                case WorkIds::ID_SECTION:
                    $code = $fields[WorkIds::ID_SERVICE_CODE];
                    $price = $fields[WorkIds::ID_PRICE_GENERAL];
                    $vendorCode = '';
	                $url = '/repair/item/'.$item->record_id.'-'.$item->alias;
	                break;
            }
            
			$result += [
				++$count => [
                    'code' => $code,
					'title' => $item->title,
                    'url' => $url,
                    'hits' => $item->hits,
                    'vendorCode' => $vendorCode,
                    'price' => $price,
                ]
			];
		}
		$this->renderXls($result);
		$data = json_encode($result, JSON_THROW_ON_ERROR);
		JExit($data);
	}

	private function renderXls($items)
    {
        $siteName = JFactory::getApplication()->get('sitename');
	    $slogan = 'Топ 100';
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
	
	    $active_sheet->setTitle("Топ 100");
	
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
	    $active_sheet->setCellValue('D6','Кол-во просмотров');
	    $active_sheet->getColumnDimension('D')->setWidth(15);
	    $active_sheet->setCellValue('E6','Цена');
	    $active_sheet->getColumnDimension('E')->setWidth(15);
	    $active_sheet->setCellValue('F6','Артикул');
	    $active_sheet->getColumnDimension('F')->setWidth(15);

	    //Слоган документа
	    $active_sheet->mergeCells('A2:F2');
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
	    $active_sheet->getStyle('A2:F2')->applyFromArray($style_slogan);
	
	    //Дата создания
	    $active_sheet->mergeCells('A4:E4');
	    $active_sheet->setCellValue('A4','Дата создания:');
	
	    $date = date('d-m-Y');
	    $active_sheet->setCellValue('F4',$date);
	    $active_sheet->getStyle('F4')->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_DATE_XLSX14);
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
	    $active_sheet->getStyle('A4:F4')->applyFromArray($style_tdate);
	
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
	    $active_sheet->getStyle('F4')->applyFromArray($style_date);
	
	    $active_sheet->getStyle('F:F')
		    ->getNumberFormat()
		    ->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
	
	    $rowStart = 7;
	    $i = 0;
	    foreach($items as $key => $item) {
		    $row_next = $rowStart + $i;
		    $rowNumber = $i+1;

		    $active_sheet->setCellValueExplicit('A' . $row_next, $rowNumber, PHPExcel_Cell_DataType::TYPE_NUMERIC);
		    $active_sheet->setCellValueExplicit('B' . $row_next, $item['code'], PHPExcel_Cell_DataType::TYPE_STRING);
		    $active_sheet->setCellValueExplicit('C' . $row_next, $item['title'], PHPExcel_Cell_DataType::TYPE_STRING);
		    $active_sheet->setCellValueExplicit('D' . $row_next, $item['hits'], PHPExcel_Cell_DataType::TYPE_STRING);
		    $active_sheet->setCellValueExplicit('E' . $row_next, strip_tags($item['price']), PHPExcel_Cell_DataType::TYPE_STRING);
		    $active_sheet->setCellValueExplicit('F' . $row_next, strip_tags($item['vendorCode']), PHPExcel_Cell_DataType::TYPE_STRING);
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
	    $active_sheet->getStyle('A6:F6')->applyFromArray($style_hprice);
	
	    $style_price = array(
		    'alignment' => array(
			    'horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_CENTER
		    )
	    );
	    $active_sheet->getStyle('A7:B'.($i+6))->applyFromArray($style_price);
	    $active_sheet->getStyle('D7:F'.($i+6))->applyFromArray($style_price);
	
	    $style_price_title = array(
		    'alignment' => array(
			    'horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_LEFT
		    )
	    );
	    $active_sheet->getStyle('C7:C'.($i+6))->applyFromArray($style_price_title);
	
	    $objWriter = new PHPExcel_Writer_Excel2007($objPhpExcel);
	    $objWriter->save(JPATH_ROOT . '/logs/b0-hits.xlsx');
    }
}
