<?php
defined('_JEXEC') or die;
// Вызов - /index.php?option=com_cobalt&task=b0pagesquality.index
/*
 * Структура параметров
 * code - код товара/услуги
 * title - название товара/услуги
 * url - ссылка
 * created - дата создания
 * image - наличие картинки
 * description - наличие описания
 * works -
                    'analogs' => $this->setAnalogs($sectionId, $fields),
                    'related' => $this->setRelated($sectionId, $fields),
                    'video' => $this->setVideo($sectionId, $fields),
                    'meta' => $this->setMeta($item),

*/

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
class CobaltControllerB0PagesQuality extends JControllerForm
{
    const DESCRIPTION_LENGTH_MIN = 1000;
    const DESCRIPTION_LENGTH_MAX = 3000;
    const META_DESCRIPTION_LENGTH_MIN = 75;
    const META_DESCRIPTION_LENGTH_MAX = 250;
    const META_TITLE_LENGTH_MIN = 30;
    const META_TITLE_LENGTH_MAX = 70;
    const WORKS_NUM_MIN = 1;
    const ANALOGS_NUM_MIN = 2;
    const ASSOCIATED_NUM_MIN = 2;
    const GALLERY_NUM_MIN = 2;
    const ARTICLES_NUM_MIN = 1;

    private int $rating = 0;

	public final function index(): void
	{ ?>
		<div id="app">
            <h3>Анализируем качество страниц</h3>
            <div class="uk-form">
                Раздел
                <select v-model="section_id">
                    <option value="<?= AccessoryIds::ID_SECTION ?>">Аксессуары</option>
                    <option value="<?= SparepartIds::ID_SECTION ?>">Запчасти</option>
                    <option value="<?= WorkIds::ID_SECTION ?>">Работы</option>
                </select>
                <span class="uk-margin-left">Модель</span>
                <select v-model="car_model">
                    <option value="all">Все</option>
                    <option value="vesta">Vesta</option>
                    <option value="xray">XRay</option>
                    <option value="granta">Granta</option>
                    <option value="largus">Largus</option>
                </select>
                <a href="/logs/b0-pages-quality.xlsx" class="uk-margin-left" title="Скачать отчет в формате Excel" download>
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
                            <th>Посещ</th>
                            <th>Рейт</th>
                            <th>Просм</th>
                            <th>Карт</th>
                            <th>Опис</th>
                            <th>Работы</th>
                            <th>Аналоги</th>
                            <th>Сопутств</th>
                            <th>Статьи</th>
                            <th>Видео</th>
                            <th>Галер</th>
                            <th>M title</th>
                            <th>M descr</th>
<!--                            <th>Маркет</th>-->
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="(item, key) in items">
                            <td>{{ key }}</td>
                            <td>{{item.code}}</td>
                            <td><a :href="item.url" target="_blank">{{item.title}}</a></td>
                            <td class="uk-text-center">{{item.created}}</td>
                            <td class="uk-text-center">{{item.rating}}</td>
                            <td class="uk-text-center">{{item.hits}}</td>
                            <td class="uk-text-center"><span v-html="item.image"></span></td>
                            <td class="uk-text-center"><span v-html="item.description"></span></td>
                            <td class="uk-text-center"><span v-html="item.works"></span></td>
                            <td class="uk-text-center"><span v-html="item.analogs"></span></td>
                            <td class="uk-text-center"><span v-html="item.related"></span></td>
                            <td class="uk-text-center"><span v-html="item.articles"></span></td>
                            <td class="uk-text-center"><span v-html="item.video"></span></td>
                            <td class="uk-text-center"><span v-html="item.galery"></span></td>
                            <td class="uk-text-center"><span v-html="item.meta_title"></span></td>
                            <td class="uk-text-center"><span v-html="item.meta_description"></span></td>
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
                    car_model: 'all',
                },
                mounted() {
                    this.getResults();
                },
                watch: {
                    section_id(value) { this.getResults(); },
                    car_model(value) { this.getResults(); },
                },
                methods: {
                    getResults(page = 1) {
                        axios.get('/index.php?option=com_cobalt&task=b0pagesquality.getItems'
                            + '&section_id=' + this.section_id
                            + '&car_model=' + this.car_model
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
	
	public final function getItems(): array
	{
		$input = JFactory::getApplication()->input;
		$sectionId = $input->get('section_id', AccessoryIds::ID_SECTION);
		$carModel = $input->get('car_model', 'all');
//		jexit(b0debug($carModel));

		$modelValue = [
            'all' => '',
			'vesta' => 'Lada Vesta',
			'xray' => 'Lada XRay',
			'granta' => 'Lada Granta FL',
			'largus' => 'Lada Largus',
		];

		switch ($sectionId) {
			case SparepartIds::ID_SECTION:
                $fieldModelId = SparepartIds::ID_MODEL;
				break;
			case AccessoryIds::ID_SECTION:
				$fieldModelId = AccessoryIds::ID_MODEL;
				break;
			case WorkIds::ID_SECTION:
				$fieldModelId = WorkIds::ID_MODEL;
				break;
            default:
	            $fieldModelId = '';
		}
		$fieldModelValue = $modelValue[$carModel];
		
		$result = [];
		
		$db = JFactory::getDbo();
		$query = $db->getQuery(true);
		$query->select('*')
            ->from("#__js_res_record")
            ->where("section_id = $sectionId")
            ->where("published=1");
        if ($carModel !== 'all') {
            $queryIn = $db->getQuery(true);
            $queryIn->select(['record_id'])
                ->from('#__js_res_record_values')
                ->where(["field_id=$fieldModelId", "field_value='$fieldModelValue'"]);
//	        jexit($queryIn->dump());
	        $db->setQuery($queryIn);
            $listIn = implode(',', array_keys($db->loadRowList(0)));
//	        jexit(b0debug($listIn));
            $query->where("id IN ($listIn)");
        }
		$query->order('hits DESC');
//		$query->setLimit(5);
//		jexit($query->dump());
		$db->setQuery($query);
		$list = $db->loadObjectList();
//		jexit(b0dd($list));
		
        $count = 0;
		$today = new Date();
		foreach ($list as $item) {
            $this->rating =0;
			$created = new Date($item->ctime);
			$numDays = date_diff($created, $today, true)->format('%a');
            if ($numDays == 0) {
                $numDays++;
            }
            $attendance = number_format($item->hits / $numDays, 2, ',', ' ');
			
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
//					'created' => $created->format('d-m-Y'),
//					'created' => $numDays,
					'created' => $attendance,
                    'hits' => $item->hits,
//                    'hits' => $attendance,
                    'image' => $this->setImage($sectionId, $fields),
                    'description' => $this->setDescription($sectionId, $fields),
                    'works' => $this->setWorks($sectionId, $fields),
                    'analogs' => $this->setAnalogs($sectionId, $fields),
                    'related' => $this->setRelated($sectionId, $fields),
                    'articles' => $this->setArticles($sectionId, $fields),
                    'video' => $this->setVideo($sectionId, $fields),
                    'galery' => $this->setGalery($sectionId, $fields),
                    'meta_title' => $this->setMetaTitle($item),
                    'meta_description' => $this->setMetaDescription($item),
                    'rating' => $this->rating,
                ]
			];
		}
//		$this->renderXls($result);
		$data = json_encode($result, JSON_THROW_ON_ERROR);
		JExit($data);
	}
	
	private function setImage(int $sectionId, array $fields): string
    {
        switch ($sectionId) {
            case AccessoryIds::ID_SECTION:
                if (!isset($fields[AccessoryIds::ID_IMAGE]) || empty($fields[AccessoryIds::ID_IMAGE])){
                    return '<span class="uk-text-danger">нет</span>';
                }
                $this->rating++;
	            return '<span class="uk-text-success">да</span>';
//	            break;
            case SparepartIds::ID_SECTION:
	            if (!isset($fields[SparepartIds::ID_IMAGE]) || empty($fields[SparepartIds::ID_IMAGE])){
		            return '<span class="uk-text-danger">нет</span>';
	            }
	            $this->rating++;
	            return '<span class="uk-text-success">да</span>';
//                break;
            case WorkIds::ID_SECTION:
	            if (!isset($fields[WorkIds::ID_IMAGE]) || empty($fields[WorkIds::ID_IMAGE])){
		            return '<span class="uk-text-danger">нет</span>';
	            }
	            $this->rating++;
	            return '<span class="uk-text-success">да</span>';
//                break;
        }
    }
	private function setVideo($sectionId, $fields)
    {
        switch ($sectionId) {
            case AccessoryIds::ID_SECTION:
                if (!isset($fields[AccessoryIds::ID_VIDEO]) || empty($fields[AccessoryIds::ID_VIDEO])){
                    return '<span class="uk-text-danger">нет</span>';
                }
	            $this->rating++;
	            return '<span class="uk-text-success">да</span>';
//	            break;
            case SparepartIds::ID_SECTION:
	            if (!isset($fields[SparepartIds::ID_VIDEO]) || empty($fields[SparepartIds::ID_VIDEO])){
		            return '<span class="uk-text-danger">нет</span>';
	            }
	            $this->rating++;
	            return '<span class="uk-text-success">да</span>';
//                break;
            case WorkIds::ID_SECTION:
	            if (!isset($fields[WorkIds::ID_VIDEO]) || empty($fields[WorkIds::ID_VIDEO])){
		            return '<span class="uk-text-danger">нет</span>';
	            }
	            $this->rating++;
	            return '<span class="uk-text-success">да</span>';
//                break;
        }
    }
	private function setDescription($sectionId, $fields)
    {
        switch ($sectionId) {
            case AccessoryIds::ID_SECTION:
                if (!isset($fields[AccessoryIds::ID_DESCRIPTION]) || empty($fields[AccessoryIds::ID_DESCRIPTION])){
                    return '<span class="uk-text-danger uk-text-bold">нет</span>';
                }
                $length = strlen($fields[AccessoryIds::ID_DESCRIPTION]);
                break;
            case SparepartIds::ID_SECTION:
	            if (!isset($fields[SparepartIds::ID_DESCRIPTION]) || empty($fields[SparepartIds::ID_DESCRIPTION])){
		            return '<span class="uk-text-danger uk-text-bold">нет</span>';
	            }
	            $length = strlen($fields[SparepartIds::ID_DESCRIPTION]);

                break;
            case WorkIds::ID_SECTION:
	            if (!isset($fields[WorkIds::ID_DESCRIPTION]) || empty($fields[WorkIds::ID_DESCRIPTION])){
		            return '<span class="uk-text-danger uk-text-bold">нет</span>';
	            }
	            $length = strlen($fields[WorkIds::ID_DESCRIPTION]);

                break;
        }
	    if ($length >= self::DESCRIPTION_LENGTH_MIN){
		    $this->rating = $this->rating * 2;
		    return '<span class="uk-text-success">' . $length . '</span>';
	    }
	    else {
		    $this->rating++;
		    return '<span class="uk-text-danger uk-text-bold">' . $length . '</span>';
	    }
	    
    }
	private function setWorks($sectionId, $fields)
    {
        switch ($sectionId) {
            case AccessoryIds::ID_SECTION:
                if (!isset($fields[AccessoryIds::ID_WORKS]) || empty($fields[AccessoryIds::ID_WORKS])){
                    return '<span class="uk-text-danger uk-text-bold">нет</span>';
                }
                $num = count($fields[AccessoryIds::ID_WORKS]);
	            break;
            case SparepartIds::ID_SECTION:
	            if (!isset($fields[SparepartIds::ID_WORKS]) || empty($fields[SparepartIds::ID_WORKS])){
		            return '<span class="uk-text-danger uk-text-bold">нет</span>';
	            }
	            $num = count($fields[SparepartIds::ID_WORKS]);

                break;
            case WorkIds::ID_SECTION:
	            if (!isset($fields[WorkIds::ID_WORKS]) || empty($fields[WorkIds::ID_WORKS])){
		            return '<span class="uk-text-danger uk-text-bold">нет</span>';
	            }
	            $num = count($fields[WorkIds::ID_WORKS]);

                break;
        }
        
	    if ($num >= self::WORKS_NUM_MIN){
		    $this->rating = $this->rating * 2;
		    return '<span class="uk-text-success">' . $num . '</span>';
	    }
	    else {
		    $this->rating++;
		    return '<span class="uk-text-danger uk-text-bold">' . $num . '</span>';
	    }
	    
    }
	private function setAnalogs($sectionId, $fields)
    {
        switch ($sectionId) {
            case AccessoryIds::ID_SECTION:
                if (!isset($fields[AccessoryIds::ID_ANALOGS]) || empty($fields[AccessoryIds::ID_ANALOGS])){
                    return '<span class="uk-text-danger uk-text-bold">нет</span>';
                }
	            $num = count($fields[AccessoryIds::ID_ANALOGS]);
	            break;
            case SparepartIds::ID_SECTION:
	            if (!isset($fields[SparepartIds::ID_ANALOGS]) || empty($fields[SparepartIds::ID_ANALOGS])){
		            return '<span class="uk-text-danger uk-text-bold">нет</span>';
	            }
	            $num = count($fields[SparepartIds::ID_ANALOGS]);
                break;
            case WorkIds::ID_SECTION:
		            return '';
        }
	    
	    if ($num >= self::ANALOGS_NUM_MIN){
		    $this->rating = $this->rating * 2;
		    return '<span class="uk-text-success">' . $num . '</span>';
	    }
	    else {
		    $this->rating++;
		    return '<span class="uk-text-danger uk-text-bold">' . $num . '</span>';
	    }
	    
    }
	private function setRelated($sectionId, $fields)
    {
        switch ($sectionId) {
            case AccessoryIds::ID_SECTION:
                if (!isset($fields[AccessoryIds::ID_ASSOCIATED]) || empty($fields[AccessoryIds::ID_ASSOCIATED])){
                    return '<span class="uk-text-danger uk-text-bold">нет</span>';
                }
	            $num = count($fields[AccessoryIds::ID_ASSOCIATED]);
	            break;
            case SparepartIds::ID_SECTION:
	            if (!isset($fields[SparepartIds::ID_ASSOCIATED]) || empty($fields[SparepartIds::ID_ASSOCIATED])){
		            return '<span class="uk-text-danger uk-text-bold">нет</span>';
	            }
	            $num = count($fields[SparepartIds::ID_ASSOCIATED]);

                break;
            case WorkIds::ID_SECTION:
		            return '';
        }
	    
	    if ($num >= self::ASSOCIATED_NUM_MIN){
		    $this->rating = $this->rating * 2;
		    return '<span class="uk-text-success">' . $num . '</span>';
	    }
	    else {
		    $this->rating++;
		    return '<span class="uk-text-danger uk-text-bold">' . $num . '</span>';
	    }
    }
	private function setArticles($sectionId, $fields)
    {
        switch ($sectionId) {
            case AccessoryIds::ID_SECTION:
                if (!isset($fields[AccessoryIds::ID_ARTICLES]) || empty($fields[AccessoryIds::ID_ARTICLES])){
                    return '<span class="uk-text-danger uk-text-bold">нет</span>';
                }
	            $num = count($fields[AccessoryIds::ID_ARTICLES]);
	            break;
            case SparepartIds::ID_SECTION:
	            if (!isset($fields[SparepartIds::ID_ARTICLES]) || empty($fields[SparepartIds::ID_ARTICLES])){
		            return '<span class="uk-text-danger uk-text-bold">нет</span>';
	            }
	            $num = count($fields[SparepartIds::ID_ARTICLES]);

                break;
            case WorkIds::ID_SECTION:
	            if (!isset($fields[WorkIds::ID_ARTICLES]) || empty($fields[WorkIds::ID_ARTICLES])){
		            return '<span class="uk-text-danger uk-text-bold">нет</span>';
	            }
	            $num = count($fields[WorkIds::ID_ARTICLES]);
	            break;
        }
	    
	    if ($num >= self::ARTICLES_NUM_MIN){
		    $this->rating = $this->rating * 2;
		    return '<span class="uk-text-success">' . $num . '</span>';
	    }
	    else {
		    $this->rating++;
		    return '<span class="uk-text-danger uk-text-bold">' . $num . '</span>';
	    }
    }
	private function setGalery(int $sectionId, $fields)
    {
        switch ($sectionId) {
            case AccessoryIds::ID_SECTION:
                if (!isset($fields[AccessoryIds::ID_GALLERY]) || empty($fields[AccessoryIds::ID_GALLERY])){
                    return '<span class="uk-text-danger uk-text-bold">нет</span>';
                }
	            //$num = json_decode($fields[AccessoryIds::ID_GALLERY], true);
	            $num = count($fields[AccessoryIds::ID_GALLERY]);
	            break;
            case SparepartIds::ID_SECTION:
	            if (!isset($fields[SparepartIds::ID_GALLERY]) || empty($fields[SparepartIds::ID_GALLERY])){
		            return '<span class="uk-text-danger uk-text-bold">нет</span>';
	            }
	            $num = count($fields[SparepartIds::ID_GALLERY]);
                break;
            case WorkIds::ID_SECTION:
	            if (!isset($fields[WorkIds::ID_GALLERY]) || empty($fields[WorkIds::ID_GALLERY])){
		            return '<span class="uk-text-danger uk-text-bold">нет</span>';
	            }
	            $num = count($fields[WorkIds::ID_GALLERY]);
                break;
        }
	    
	    if ($num >= self::GALLERY_NUM_MIN){
		    $this->rating = $this->rating * 2;
		    return '<span class="uk-text-success">' . $num . '</span>';
	    }
	    else {
		    $this->rating++;
		    return '<span class="uk-text-danger uk-text-bold">' . $num . '</span>';
	    }
    }
	private function setMetaTitle($item)
	{
        if (!isset($item->meta_key) || empty($item->meta_key)){
            return '<span class="uk-text-danger uk-text-bold">нет</span>';
        }
		$length = strlen($item->meta_key);
  
		if ($length > self::META_TITLE_LENGTH_MIN || $length < self::META_TITLE_LENGTH_MAX){
			$this->rating = $this->rating * 2;
			return '<span class="uk-text-success">' . $length . '</span>';
		}
		else {
			$this->rating++;
			return '<span class="uk-text-danger uk-text-bold">' . $length . '</span>';
		}
	}
	private function setMetaDescription($item)
	{
        if (!isset($item->meta_descr) || empty($item->meta_descr)){
            return '<span class="uk-text-danger uk-text-bold">нет</span>';
        }
		$length = strlen($item->meta_descr);
		$equalH1 = ($item->meta_descr == $item->title);
		if ($length > self::META_DESCRIPTION_LENGTH_MIN || $length < self::META_DESCRIPTION_LENGTH_MAX){
			$this->rating = $this->rating * 2;
            $result = '<span class="uk-text-success">' . $length;
            if ($equalH1) {
                $result .= '=H1';
            }
			$result .= '</span>';
//			return '<span class="uk-text-success">' . $length . '</span>';
		}
		else {
			$this->rating++;
			$result = '<span class="uk-text-danger uk-text-bold">' . $length;
			if ($equalH1) {
				$result .= '=H1';
			}
			$result .= '</span>';
//			return '<span class="uk-text-danger uk-text-bold">' . $length . '</span>';
		}
        return $result;
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

