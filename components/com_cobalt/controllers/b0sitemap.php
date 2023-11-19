<?php
defined('_JEXEC') or die();
/** /index.php?option=com_cobalt&task=b0sitemap.createsitemap
/** /sitemap.xml
 *
 */

class CobaltControllerB0SiteMap extends JControllerAdmin
{
	protected string $file_path = "/sitemap.xml";
    protected $file_handle;
	protected array $logs = [];
	protected array $map_items = [];
	protected array $menuParams = [
		// Главная
		'home' => [
			'type'       => 'link',
			'item_id'    => '',
			'loc'        => '',
			'priority'   => '1',
			'changefreq' => 'weekly'
		],
		// Обратная связь
		'feedback'       => [
			'type'       => 'link',
			'item_id'    => '5033',
			'loc'        => '/feedback',
			'priority'   => '0.8',
			'changefreq' => 'weekly'
		],
		// Запчасти
		'spareparts'        => [
			'type'       => 'section',
			'item_id'    => 1,
			'loc'        => '/spareparts',
			'priority'   => '0.8',
			'changefreq' => 'weekly'
		],
		// Аксессуары
		'accessories' => [
			'type'       => 'section',
			'item_id'    => 6,
			'loc'        => '/accessories',
			'priority'   => '0.8',
			'changefreq' => 'weekly'
		],
		// Доставка и оплата
/*		'delivery'          => [
			'type'       => 'link',
			'item_id'    => '32',
			'loc'        => '/delivery',
			'priority'   => '0.8',
			'changefreq' => 'weekly'
		],*/
		// Скидки и акции
		'discounts' => [
			'type'       => 'section',
			'item_id'    => '12',
			'loc'        => '/discounts',
			'priority'   => '0.8',
			'changefreq' => 'weekly'
		],
		// Техобслуживание
		'maintenance'       => [
			'type'       => 'section',
			'item_id'    => 5,
			'loc'        => '/maintenance',
			'priority'   => '0.8',
			'changefreq' => 'weekly'
		],
		// Организациям
/*		'companies'       => [
			'type'       => 'section',
			'item_id'    => 15,
			'loc'        => '/companies',
			'priority'   => '0.8',
			'changefreq' => 'weekly'
		],*/
		// Ремонт
		'repair'            => [
			'type'       => 'section',
			'item_id'    => 4,
			'loc'        => '/repair',
			'priority'   => '0.8',
			'changefreq' => 'weekly'
		],
		// Новости
		'news'              => [
			'type'       => 'section',
			'item_id'    => 3,
			'loc'        => '/news',
			'priority'   => '0.8',
			'changefreq' => 'weekly'
		],
		// Полезное
		'helpful'           => [
			'type'       => 'section',
			'item_id'    => 14,
			'loc'        => '/helpful',
			'priority'   => '0.8',
			'changefreq' => 'weekly'
		],
		// Партнеры
		'partners'      => [
			'type'       => 'section',
			'item_id'    => 9,
			'loc'        => '/partners',
			'priority'   => '0.8',
			'changefreq' => 'weekly'
		],
		// О нас
		'about-us'         => [
			'type'       => 'section',
			'item_id'    => 11,
			'loc'        => '/about-us',
			'priority'   => '0.8',
			'changefreq' => 'weekly'
		],
		// Контакты
		'contacts'          => [
			'type'       => 'link',
			'item_id'    => '33',
			'loc'        => '/contacts',
			'priority'   => '0.8',
			'changefreq' => 'weekly'
		],
	];

     public function createSiteMap(): void
     {
         $this->file_handle = fopen(JPATH_ROOT . $this->file_path, 'w+b');

         if (!$this->file_handle) {
             $this->logs[] = 'Ошибка открытия файла sitemap.xml';
             $this->sendMail($this->logs);
             return;
         }
         $title = '<?xml version="1.0" encoding="UTF-8"?>'."\n".
             '<urlset xmlns="https://www.sitemaps.org/schemas/sitemap/0.9">'."\n";
         $res = fwrite($this->file_handle, $title);
         if (!$res) {
             $this->logs[] = 'Ошибка записи в файл sitemap.xml';
             fclose($this->file_handle);
             $this->sendMail($this->logs);
             return;
         }

         foreach ($this->menuParams as $item) {
             switch ($item['type']) {
                 case 'link':
                     $this->add_link($item);
                     break;
                 case 'section':
                     $this->add_section($item);
                     break;
             }
         }
         $this->render_map_items();

         $footer = '</urlset>';
         $res = fwrite($this->file_handle, $footer);
         if (!$res) {
             $this->logs[] = 'Ошибка записи в файл sitemap.xml';
             fclose($this->file_handle);
             $this->sendMail($this->logs);
             return;
         }
//	     $this->logs[] = 'Карта сайта stoVesta сформирована';
//	     $this->sendMail($this->logs);
	     jexit("Карта сайта stoVesta сформирована");
     }

    private function add_link($item): bool
    {
         $node = new stdclass();
         $node->loc = 'https://stovesta.ru' . $item['loc'];
         $node->priority = $item['priority'];
         $node->changefreq = $item['changefreq'];
		 $node->lastmod = date("Y-m-d");
         $this->map_items[] = $node;
         return true;
    }

    private function add_section($item): void
    {
        $node = new stdclass();
        $node->loc = 'https://stovesta.ru' . $item['loc'];
        $node->priority = $item['priority'];
        $node->changefreq = $item['changefreq'];
	    $node->lastmod = date("Y-m-d");
        $this->map_items[] = $node;

        // добавляем категории из секции
        $this->addCategories($item);

        // добавляем статьи из секции
        $this->addArticles($item);
    }

    private function render_map_items(): bool
    {
         foreach ($this->map_items as $node){
             $node_content = '<url>' . "\n";
             $node_content .= '<loc>' . $node->loc . '</loc>' . "\n";
             $node_content .= '<priority>' . $node->priority . '</priority>' . "\n";
             $node_content .= '<changefreq>' . $node->changefreq . '</changefreq>' . "\n";
             $node_content .= '<lastmod>' . $node->lastmod . '</lastmod>' . "\n";
             $node_content .= '</url>' . "\n";
             $res = fwrite($this->file_handle, $node_content);
             if ($res == false) {
                 $this->logs[] = 'Ошибка записи в файл sitemap.xml';
                 fclose($this->file_handle);
                 $this->sendMail($this->logs);
                 return false;
             }
         }
         return true;
    }

     private function addCategories ($item): void
     {
         $db = JFactory::getDbo();
         $query = $db->getQuery(TRUE);
         $query->select('id, title, alias, path, section_id, published, modified_time');
         $query->from('#__js_res_categories');
         $query->where('section_id = ' . $item['item_id']);
         $query->where('published = 1');
         //$query->order("lft");
         $db->setQuery($query);
         $categories = $db->loadObjectList();

         foreach($categories as $category){
             $node = new stdclass();
             $node->loc = 'https://stovesta.ru' . JRoute::_(Url::records($category->section_id, $category->id));
             $node->priority = '0.7';
             $node->changefreq = 'weekly';
	         $node->lastmod = substr($category->modified_time, 0,10);
             $this->map_items[] = $node;
         }
     }

    private function addArticles ($item): void
    {
        $db = JFactory::getDbo();
        $query = $db->getQuery(TRUE);
        $query->select('id, title, alias, section_id, type_id, published, categories, mtime');
        $query->from('#__js_res_record');
        $query->where('section_id = ' . $item['item_id']);
        $query->where('published = 1');
        //$query->order("lft");
        $db->setQuery($query);
        $articles = $db->loadObjectList();

        foreach($articles as $article){
            $type = ItemsStore::getType($article->type_id);
            $section = ItemsStore::getSection($article->section_id);
            $node = new stdclass();
            $node->loc = 'https://stovesta.ru' . JRoute::_(Url::record($article->id, $type, $section));
            $node->priority = '0.5';
            $node->changefreq = 'weekly';
	        $node->lastmod = substr($article->mtime, 0,10);;
            $this->map_items[] = $node;
        }
    }

	private function sendMail(array $logs): void
	{
		$messageBody = '';
		foreach ($logs as $log) {
			$messageBody .= $log . "\n";
		}
		JFactory::getMailer()->sendMail('admin@stovesta.ru', 'Admin', 'p.ivanov@stovesta.ru', 'StoVesta SiteMap', $messageBody, TRUE);
	}
}
