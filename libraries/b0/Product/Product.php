<?php
defined('_JEXEC') or die();

JImport('b0.Product.ProductConfig');
class Product
{
	public int $id;
	public array $controls;
	public array $tabsTemplate;
	public array $tabs;
	public $productIds;

	//*** Core
	public string $url;
	public string $title;
	
	//*** Fields
	public array $subtitle;
	
	public array $productCode;
	public array $vendorCode;
	public array $originalCode;
	
	public array $description;
	public array $characteristics;
	
	public bool $isSpecial;
	public bool $isOriginal;
	public bool $isByOrder;
	public bool $isGeneral;
	public bool $isHit;
	public bool $isSales;
	public array $priceGeneral;
	public array $priceSpecial;
	public array $priceSimple;
	public array $priceSilver;
	public array $priceGold;
	public array $priceDelivery;
	public array $priceDeliveryRegions;
	public array $priceDeliveryMyself;
	public array $priceCurrent;
	
	public array $image;
	public array $hitImage;
	public array $video;
	public $gallery;
	
	public array $manufacturer;
	public array $mpn;
	
	public array $model;
	public array $generation;
	public array $year;
	public array $body;
	public array $motor;
	public array $drive;
	
	public array $openGraph;
	public array $availability;
	public array $availabilitySpecial;

	public array $cart;
	
	//*** Meta
	public string $siteName;
	public string $metaTitle;
	public string $metaDescription;
	public string $metaKeywords;
	//***
	public array $phoneDelivery;
	
	public string $moduleCallback;
	public string $moduleMinibanners;

	public string $howToBuyUrl;
	public string $howToInstallUrl;
	public string $howToOrderDeliverySpbUrl;
	public string $howToOrderDeliveryRegionsUrl;
	public string $deliveryTermsSpbUrl;
	public string $deliveryTermsRegionsUrl;
	public string $discountUrl;
	public string $vkUrl;
	
	public string $vkIcon;
	public string $discountCardIcon;
	//*** Metrics
	public int $yandex;
	public string $yandexId;
	public string $yandexGoal;
	public int $google;
	public string $googleGoal;
	//*** Social
	public string $hits;
	public string $rating;
	
	public function __construct($item, $productIds, JRegistry $paramsRecord, JRegistry $paramsApp)
	{
		$this->productIds = $productIds;
		
		$this->tabsTemplate = [
			$productIds::ID_WORKS => [
				'title' => ProductConfig::TITLE_WORKS,
				'isActive' => 1,
			],
			$productIds::ID_ANALOGS => [
				'title' => ProductConfig::TITLE_ANALOGS,
				'isActive' => 0,
			],
			$productIds::ID_ASSOCIATED => [
				'title' => ProductConfig::TITLE_ASSOCIATED,
				'isActive' => 0,
			],
			$productIds::ID_ARTICLES => [
				'title' => ProductConfig::TITLE_ARTICLES,
				'isActive' => 0,
			],
			$productIds::ID_GALLERY => [
				'title' => ProductConfig::TITLE_GALLERY,
				'isActive' => 0,
			],
		];

		$fields = $item->fields_by_id;
		
		//*** Core
		$this->id = $item->id;
		$this->url = $item->url;
		$this->title = $item->title;

		//*** Fields
		$this->subtitle = $this->setField($fields[$productIds::ID_SUBTITLE], 'Подзаголовок');
		$this->productCode = $this->setField($fields[$productIds::ID_PRODUCT_CODE], 'Код продукта');
		$this->vendorCode = $this->setField($fields[$productIds::ID_VENDOR_CODE], 'Код производителя');
		$this->originalCode = $this->setField($fields[$productIds::ID_ORIGINAL_CODE], 'Код оригинала');
		
		$this->image = $this->setImage($fields[$productIds::ID_IMAGE]);
		$this->hitImage = $this->setImageHit($fields[$productIds::ID_HIT_IMAGE] ?? null);
		$this->video = $this->setVideo($fields[$productIds::ID_VIDEO], $paramsRecord->get('tmpl_core.default_video'), $item->title);
		$this->gallery = $this->setGallery($fields);
		
		$this->description = $this->setField($fields[$productIds::ID_DESCRIPTION], 'Описание');
		$this->characteristics = $this->setField($fields[$productIds::ID_CHARACTERISTICS], 'Характеристики');
		
		$this->isSpecial = $this->setFieldBoolean($fields[$productIds::ID_IS_SPECIAL]);
		$this->isOriginal = $this->setFieldBoolean($fields[$productIds::ID_IS_ORIGINAL]);
		$this->isByOrder = $this->setFieldBoolean($fields[$productIds::ID_IS_BY_ORDER]);
		$this->isGeneral = !$this->isSpecial && !$this->isByOrder;
		$this->isHit = $this->setFieldBoolean($fields[$productIds::ID_IS_HIT] ?? null);
		$this->isSales = $this->setFieldBoolean($fields[$productIds::ID_IS_SALES] ?? null);
		$this->priceGeneral = $this->setField($fields[$productIds::ID_PRICE_GENERAL], 'Цена в магазине');
		$this->priceSpecial = $this->setField($fields[$productIds::ID_PRICE_SPECIAL], 'Специальная цена');
        $this->priceSimple = $this->setFieldPrice(ProductConfig::PRICE_DISCOUNT_ORIGINAL_SIMPLE, ProductConfig::PRICE_DISCOUNT_NON_ORIGINAL_SIMPLE, 'Стандартный уровень');
        $this->priceSilver = $this->setFieldPrice(ProductConfig::PRICE_DISCOUNT_ORIGINAL_SILVER, ProductConfig::PRICE_DISCOUNT_NON_ORIGINAL_SILVER, 'Серебряный уровень');
        $this->priceGold = $this->setFieldPrice(ProductConfig::PRICE_DISCOUNT_ORIGINAL_GOLD, ProductConfig::PRICE_DISCOUNT_NON_ORIGINAL_GOLD, 'Золотой уровень');
        $this->priceDelivery = $this->setFieldPrice(ProductConfig::PRICE_DISCOUNT_ORIGINAL_DELIVERY, ProductConfig::PRICE_DISCOUNT_NON_ORIGINAL_DELIVERY, 'Цена при доставке');
        $this->priceDeliveryRegions = $this->setFieldPrice(ProductConfig::PRICE_DISCOUNT_ORIGINAL_DELIVERY_REGIONS, ProductConfig::PRICE_DISCOUNT_NON_ORIGINAL_DELIVERY_REGIONS, 'Цена при доставке');
        $this->priceDeliveryMyself = $this->setFieldPrice(ProductConfig::PRICE_DISCOUNT_ORIGINAL_DELIVERY_MYSELF, ProductConfig::PRICE_DISCOUNT_NON_ORIGINAL_DELIVERY_MYSELF, 'Цена');
        $this->priceCurrent = $this->isSpecial ? $this->priceSpecial : $this->priceGeneral;

		$this->manufacturer = $this->setField($fields[$productIds::ID_MANUFACTURER], 'Производитель');
		$this->mpn = $this->setField($fields[$productIds::ID_ORIGINAL_CODE]);
		//*** Filters
		$this->model = $this->setField($fields[$productIds::ID_MODEL], 'Модель');
		$this->generation = $this->setField($fields[$productIds::ID_GENERATION], 'Поколение');
		$this->year = $this->setField($fields[$productIds::ID_YEAR], 'Год выпуска');
		$this->body = $this->setField($fields[$productIds::ID_BODY], 'Кузов');
		$this->motor = $this->setField($fields[$productIds::ID_MOTOR], 'Мотор');
		$this->drive = $this->setField($fields[$productIds::ID_DRIVE], 'Привод');

		$this->openGraph = $this->setOpenGraph($item);
		$this->availability = [
			'ул. Кузнецовская, 52 к.13' => isset($fields[$productIds::ID_SEDOVA]) ? $this->setAvailability($fields[$productIds::ID_SEDOVA]) : 0,
			'ул. Химиков, 2' => isset($fields[$productIds::ID_KHIMIKOV]) ? $this->setAvailability($fields[$productIds::ID_KHIMIKOV]) : 0,
			'ул. Портовая, 15-Б' => isset($fields[$productIds::ID_ZHUKOVA]) ? $this->setAvailability($fields[$productIds::ID_ZHUKOVA]) : 0,
			'1-й Верхний пер., 10' => isset($fields[$productIds::ID_KULTURY]) ? $this->setAvailability($fields[$productIds::ID_KULTURY]) : 0,
			'ул. Планерная, 15-Б' => isset($fields[$productIds::ID_PLANERNAYA]) ? $this->setAvailability($fields[$productIds::ID_PLANERNAYA]) : 0,
		];
        $this->availabilitySpecial = ProductConfig::AVAILABILITY_SPECIAL;

		$cart = JFactory::getApplication()->getUserState('cart') ?? [];
		$this->cart = [
			'inCart' => (bool) array_key_exists($this->id, $cart),
			'quantity' => array_key_exists($this->id, $cart) ? (int) $cart[$this->id]['quantity'] : 0,
		];
		
		$this->siteName = JFactory::getApplication()->get('sitename');
		$this->metaTitle = $this->setMetaTitle($item);
		$this->metaDescription = $this->setMetaDescription($item);
		$this->metaKeywords = '';

		$this->controls = $item->controls;
		$this->tabs = $this->setTabs($fields);
		
		$this->phoneDelivery = [
			'url' => 'tel:'.str_ireplace('-', '', $paramsApp->get('phone_delivery')),
			'phone' => $paramsApp->get('phone_delivery'),
		];

		$this->yandex = $paramsRecord->get('tmpl_core.yandex', 0);
		$this->yandexId = $paramsApp->get('yandex_id', '');
		$this->yandexGoal = $paramsRecord->get('tmpl_core.yandex_goal', '');
		$this->google = $paramsRecord->get('tmpl_core.google', 0);
		$this->googleGoal = $paramsRecord->get('tmpl_core.google_goal', '');
		
		$this->moduleCallback = $paramsRecord->get('tmpl_core.module_callback', '');
		$this->moduleMinibanners = $paramsRecord->get('tmpl_core.module_minibanners', '');
		
		$this->howToBuyUrl = $paramsRecord->get('tmpl_core.how_to_buy_url', '');
		$this->howToInstallUrl = $paramsRecord->get('tmpl_core.how_to_install_url', '');
		$this->howToOrderDeliverySpbUrl = $paramsRecord->get('tmpl_core.how_to_order_delivery_spb_url', '');
		$this->howToOrderDeliveryRegionsUrl = $paramsRecord->get('tmpl_core.how_to_order_delivery_regions_url', '');
		$this->deliveryTermsSpbUrl = $paramsRecord->get('tmpl_core.delivery_terms_spb_url', '');
		$this->deliveryTermsRegionsUrl = $paramsRecord->get('tmpl_core.delivery_terms_regions_url', '');
		$this->discountUrl = $paramsRecord->get('tmpl_core.discounts_url', '');
		$this->vkUrl = $paramsRecord->get('tmpl_core.vk_url', '');
		
		$this->vkIcon = $paramsRecord->get('tmpl_core.vk_icon', '');
		$this->discountCardIcon = $paramsRecord->get('tmpl_core.discount_card_icon', '');
		
		$this->hits = $item->hits;
//		$this->rating = $item->rating;
		$this->rating = '';
	}
	
	//
	private function setField($field, $label = ''): array
	{
		return [
			'label' => $label,
			'value' => $field->raw,
			'result' => $field->result
		];
	}

    private function setFieldPrice($discountOriginal, $discountNonOriginal, $label = ''): array
    {
        $price = round($this->isOriginal ? $this->priceGeneral['value'] * $discountOriginal : $this->priceGeneral['value'] * $discountNonOriginal);

        return [
            'label' => $label,
            'value' => $price,
            'result' => number_format($price, 0, null, ' ' ) . ' руб.',
        ];
    }

    private function setAvailability($field): int
	{
		if ($field->raw <= 0) {
			return 0;
		}
		return $field->raw;
	}
	
	private function setFieldBoolean($field): bool
	{
		return !is_null($field) && $field->value === 1;
	}
	
	private function setImage($image): array
	{
		return [
			'url' => JUri::base() . $image->value['image'],
			'width' => $image->params->get('params.thumbs_width', '400') . 'px',
			'height' => $image->params->get('params.thumbs_height', '300') . 'px',
			'result' => $image->result ?? '',
			'real' => true,
		];
	}

	private function setImageHit($image): array
	{
        if (is_null($image)) {
            return [];
        }
        if (!$image->value) {
            return [];
        }
        return [
			'url' => JUri::base() . $image->value['image'],
			'width' => $image->params->get('params.thumbs_width', '400') . 'px',
			'height' => $image->params->get('params.thumbs_height', '300') . 'px',
			'result' => $image->result ?? '',
			'real' => true,
		];
	}

	private function setVideo($field, $defaultVideo, $title): array
	{
		if (!$field->result){
			return [
				'url' => $defaultVideo,
				'width' => '400px',
				'height' => '300px',
				'result' => '<img src="' . $defaultVideo . '" width="400" height="300" alt="' . $title . '" title="' . $title . '">',
				'real' => false,
			];
		}
		$link = $field->raw;
		if (stripos($link, 'youtube.com') > 0) {
			return [
				'url' => $link,
				'width' => '400px',
				'height' => '300px',
				'result' => '<div class="uk-cover"><iframe src="' . $link . '" width="400" height="300" allowfullscreen></iframe></div>',
				'real' => true,
			];
		}
		$url = 'https://www.youtube.com/embed/' . $link;
		return [
			'url' => $url,
			'width' => '400px',
			'height' => '300px',
			'result' => '<div class="uk-cover"><iframe src="' . $url . '" width="400" height="300" allowfullscreen></iframe></div>',
			'real' => true,
		];
	}
	
	private function setGallery($fields): array {
		return $fields[$this->productIds::ID_GALLERY]->raw ?? [];
	}
	
	private function setMetaTitle(object $item): string
	{
		return $item->meta_key !== '' ? $item->meta_key : $item->title.' купить в '. $this->siteName . ' за ' . $this->priceCurrent['value'] . ' рублей';
	}

	private function setMetaDescription(object $item): string
	{
		return $item->meta_descr !== '' ? $item->meta_descr : 'Вы можете купить '. $item->title . ' в магазинах ' . $this->siteName . ' за ' . $this->priceCurrent['value'] . ' рублей. ' .
			$item->title . '- описание, фото, характеристики, аналоги и сопутствующие товары.';
	}

	private function setOpenGraph(object $item):array
	{
		$openGraph = [
			'og:type' => 'article',
			'og:title' => $item->title,
			'og:url' => JRoute::_($item->url, false, 0,1),
			'og:description' => $item->meta_descr,
			'og:site_name' => JFactory::getApplication()->get('sitename'),
			'og:locale' => 'ru_RU',
		];
		
		$images = [];
		$images[] = [
		'og:image' => $this->image['url'],
		'og:image:secure_url' => $this->image['url'],
		'og:image:type' => 'image/jpeg',
		'og:image:width' => $this->image['width'],
		'og:image:height' => $this->image['height'],
		];
		
		if (isset($item->fields_by_id[$this->productIds::ID_GALLERY]->raw)) {
			$fieldGallery = $item->fields_by_id[$this->productIds::ID_GALLERY];
			$baseUrl = 'images/' . $fieldGallery->params->get('params.subfolder', 'gallery') . '/';
			foreach ($fieldGallery->raw as $picture) {
				$images[] = [
					'og:image' => JUri::base() . $baseUrl . $picture['fullpath'],
					'og:image:secure_url' => JUri::base() . $baseUrl . $picture['fullpath'],
					'og:image:type' => 'image/jpeg',
					'og:image:width' => $picture['width'].'px',
					'og:image:height' => $picture['height'].'px'
				];
			}
		}
		$openGraph += [
			'og:image' => $images
		];
		
		if ($this->video['real']) {
			$ogVideo = [
				'og:video' => $this->video['url'],
				'og:video:secure_url' => $this->video['url'],
				'og:video:type' => 'video/mp4',
				'og:video:width' => $this->video['width'],
				'og:video:height' => $this->video['height'],
			];
		}
		else {
			$ogVideo = [];
		}
		$openGraph += [
			'og:video' => $ogVideo
		];
		return $openGraph;
//		return array_merge($openGraph, $images, $ogVideo);
	}
	
	private function setTabs(array $fields) :array
	{
		$tabs = [];
		foreach ($this->tabsTemplate as $key => $tab) {
			if ($key === (int) $this->productIds::ID_GALLERY) {
				continue;
			}
			if (!isset($fields[$key])) {
				continue;
			}
			if ($fields[$key]->content['total'] === 0) {
				continue;
			}
			$tabs[$key] = [
				'title' => $tab['title'],
				'isActive' => $tab['isActive'],
				'total' => $fields[$key]->content['total'] ?? count($fields[$key]->raw),
				'result' => $fields[$key]->content['html'] ?? $fields[$key]->result,
			];
		}
		if (isset($fields[$this->productIds::ID_GALLERY]) && $fields[$this->productIds::ID_GALLERY]->result) {
			$tabs[$this->productIds::ID_GALLERY] = [
				'title' => $this->tabsTemplate[$this->productIds::ID_GALLERY]['title'],
				'isActive' => $this->tabsTemplate[$this->productIds::ID_GALLERY]['isActive'],
				'total' => count($fields[$this->productIds::ID_GALLERY]->raw),
				'result' => $fields[$this->productIds::ID_GALLERY]->result,
			];
		}
		return $tabs;
	}
	
	public function renderField($field, $tag = ''): void
	{
		if (!$field['result']){
			return;
		}
		if ($tag !== '') {
			echo  "<$tag>";
		}
		echo "<strong>{$field['label']}:</strong> {$field['result']}";
		if ($tag !== '') {
			echo  "</$tag>";
		}
	}
	
/*	public function renderPrice($price): string
	{
		return number_format($price, 0, '.', ' ') . ' RUB';
	}*/
	
	public function renderEconomy ($price1, $price2): string
	{
		$delta = (int) $price1 - (int) $price2;
//		$percent = number_format(($price1 - $price2 / $price1) * 100, 0);
		return number_format($delta, 0, '.', ' ') . ' руб.';
	}
}
