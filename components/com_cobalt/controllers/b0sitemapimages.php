<?php
defined('_JEXEC') or die();
/** /index.php?option=com_cobalt&task=b0sitemapimages.createsitemapimages
/** /sitemapimages.xml
 */

JImport('b0.Accessory.AccessoryIds');
JImport('b0.Sparepart.SparepartIds');
JImport('b0.Work.WorkIds');
JImport('b0.fixtures');

class CobaltControllerB0SiteMapImages extends JControllerAdmin
{
	protected string $file_path = "/sitemapimages.xml";
    protected $file_handle;
	protected array $logs = [];
	protected array $map_items = [];
	
     public function createSiteMapImages(): void
     {
         $this->file_handle = fopen(JPATH_ROOT . $this->file_path, 'w+b');

         if (!$this->file_handle) {
             $this->logs[] = 'Ошибка открытия файла sitemapimages.xml';
             $this->sendMail($this->logs);
             return;
         }
         $title = '<?xml version="1.0" encoding="UTF-8"?>'."\n";
	     $title .= '<urlset xmlns="https://www.sitemaps.org/schemas/sitemap/0.9" xmlns:image="http://www.google.com/schemas/sitemap-image/1.1">';
         $res = fwrite($this->file_handle, $title);
         if (!$res) {
             $this->logs[] = 'Ошибка записи в файл sitemapimages.xml';
             fclose($this->file_handle);
             $this->sendMail($this->logs);
             return;
         }

		 $this->addArticles();
         $this->render_map_items();

         $footer = '</urlset>';
         $res = fwrite($this->file_handle, $footer);
         if (!$res) {
             $this->logs[] = 'Ошибка записи в файл sitemapimages.xml';
             fclose($this->file_handle);
             $this->sendMail($this->logs);
             return;
         }
	     jexit("Карта изображений сайта StoVesta сформирована");
     }
	
	private function addArticles (): void
	{
		$db = JFactory::getDbo();
		$query = $db->getQuery(TRUE);
		$query->select('id, title, alias, section_id, type_id, published, categories, mtime, fields, meta_descr, meta_key');
		$query->from('#__js_res_record');
		$query->where('section_id IN (6,1,4)');
		$query->where('published = 1');
		$db->setQuery($query);
		$articles = $db->loadObjectList();
//		b0dd($articles);
		foreach($articles as $article){
			switch ($article->section_id) {
				case AccessoryIds::ID_SECTION:
					$node = $this->getArticlesAccessories($article);
					break;
				case SparepartIds::ID_SECTION:
					$node = $this->getArticlesSpareparts($article);
					break;
				case WorkIds::ID_SECTION:
					$node = $this->getArticlesWorks($article);
					break;
				default:
					$node = [];
					break;
			}
			
			$this->map_items[] = $node;
		}
	}
	
	private function getArticlesAccessories(object $item): object
	{
		$type = ItemsStore::getType($item->type_id);
		$section = ItemsStore::getSection($item->section_id);
		$fields = json_decode($item->fields, true);
		$node = new stdClass();
		$node->loc = JRoute::_(Url::record($item->id, $type, $section));
		$node->images = [];
		// Добавление основной картинки
		if (isset($fields[AccessoryIds::ID_IMAGE]) && $fields[AccessoryIds::ID_IMAGE]) {
			$node->images[] = [
				'url' => $fields[AccessoryIds::ID_IMAGE]['image'],
				'title' => $item->meta_key ?: $item->title,
				'alt' => $item->title,
			];
		}
		// Добавление фото из галереи
		if (isset($fields[AccessoryIds::ID_GALLERY]) && $fields[AccessoryIds::ID_GALLERY]) {
			foreach ($fields[AccessoryIds::ID_GALLERY] as $image) {
				$node->images[] = [
					'url' => $image['fullpath'],
					'title' => $image['title'] ?: $item->title,
					'alt' => $image['description'] ?: $item->title,
				];
			}
		}
		
		return $node;
	}
	
	private function getArticlesSpareparts(object $item): object
	{
		$type = ItemsStore::getType($item->type_id);
		$section = ItemsStore::getSection($item->section_id);
		$fields = json_decode($item->fields, true);
		$node = new stdClass();
		$node->loc = JRoute::_(Url::record($item->id, $type, $section));
		$node->images = [];
		// Добавление основной картинки
		if (isset($fields[SparepartIds::ID_IMAGE]) && $fields[SparepartIds::ID_IMAGE]) {
			$node->images[] = [
				'url' => $fields[SparepartIds::ID_IMAGE]['image'],
				'title' => $item->meta_key ?: $item->title,
				'alt' => $item->title,
			];
		}
		// Добавление фото из галереи
		if (isset($fields[SparepartIds::ID_GALLERY]) && $fields[SparepartIds::ID_GALLERY]) {
			foreach ($fields[SparepartIds::ID_GALLERY] as $image) {
				$node->images[] = [
					'url' => $image['fullpath'],
					'title' => $image['title'] ?: $item->title,
					'alt' => $image['description'] ?: $item->title,
				];
			}
		}
		
		return $node;
	}
	
	private function getArticlesWorks(object $item): object
	{
		$type = ItemsStore::getType($item->type_id);
		$section = ItemsStore::getSection($item->section_id);
		$fields = json_decode($item->fields, true);
		$node = new stdClass();
		$node->loc = JRoute::_(Url::record($item->id, $type, $section));
		$node->images = [];
		// Добавление основной картинки
		if (isset($fields[WorkIds::ID_IMAGE]) && $fields[WorkIds::ID_IMAGE]) {
			$node->images[] = [
				'url' => $fields[WorkIds::ID_IMAGE]['image'],
				'title' => $item->meta_key ?: $item->title,
				'alt' => $item->title,
			];
		}
		// Добавление фото из галереи
		if (isset($fields[WorkIds::ID_GALLERY]) && $fields[WorkIds::ID_GALLERY]) {
			foreach ($fields[WorkIds::ID_GALLERY] as $image) {
				$node->images[] = [
					'url' => $image['fullpath'],
					'title' => $image['title'] ?: $item->title,
					'alt' => $image['description'] ?: $item->title,
				];
			}
		}
		
		return $node;
	}
    private function render_map_items(): bool
    {
         foreach ($this->map_items as $node){
             $node_content = '<url>' . "\n";
		         $node_content .='<loc>https://stovesta.ru'. $node->loc .'</loc>' . "\n";
				 foreach ($node->images as $image) {
					 $node_content .= '<image:image>' . "\n";
					 $node_content .= '<image:loc>https://stovesta.ru/' . $image['url'] . '</image:loc>' . "\n";
					 $node_content .= '<image:title>' . $image['title'] . '</image:title>' . "\n";
					 $node_content .= '<image:caption>' . $image['alt'] . '</image:caption>' . "\n";
					 $node_content .= '</image:image>' . "\n";
				 }
             $node_content .= '</url>' . "\n";
             $res = fwrite($this->file_handle, $node_content);
             if (!$res) {
                 $this->logs[] = 'Ошибка записи в файл sitemap.xml';
                 fclose($this->file_handle);
                 $this->sendMail($this->logs);
                 return false;
             }
         }
         return true;
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
