<?php
defined('_JEXEC') or die();

//use Joomla\CMS\Factory;
JImport('b0.Sparepart.SparepartIds');
//JImport('b0.Sparepart.SparepartKeys');
JImport('b0.Accessory.AccessoryIds');
//JImport('b0.Accessory.AccessoryKeys');
JImport('b0.Work.WorkIds');
//JImport('b0.Work.WorkKeys');
JImport('b0.Yml.Yml');
require_once JPATH_ROOT . '/components/com_cobalt/api.php';

class CobaltControllerB0Sync extends JControllerLegacy
{
	private string $log_file_path = '/logs/b0_sync_1c.txt';
    private array $logs = [];
	private bool $needYml = false;

	// Разрешать синхронизацию из карточки товара
//	private bool $enableSyncSave = true;
	// Разрешать синхронизацию из расходных документов
	private bool $enableSyncSaveRemains = true;
	// Разрешать установку контроля версий
	private bool $enableVersionControl = false;
	// Разрешать формирование YML файла
	private bool $enableYml = true;
	// Разрешать отправку Email при успешном результате - карточка товара
	private bool $enableSuccessEmail = false;
	// Разрешать отправку Email при успешном результате - расходный документ
	private bool $enableSuccessEmailRemains = false;
	// Разрешать запись лога при успешном результате
	private bool $enableSuccessLogs = false;
	// Разрешать запись входного массива
	private bool $enableRequestLog = true;

	// Ключ поля Код товара - Запчасть
	private string $productCodeSparepart = SparepartIds::ID_PRODUCT_CODE;
	// Ключ поля Код товара - Аксессуар
	private string $productCodeAccessory = AccessoryIds::ID_PRODUCT_CODE;
	// Ключ поля Код услуги
	private string $serviceCode = WorkIds::ID_SERVICE_CODE;
	// Коэффициент цены при первом визите
	private float $discountFirstVisit = 0.8;
	// Параметры отправки почты
	private string $emailFrom = 'admin@stovesta.ru';
	private string $emailFromName  = 'Admin';
	private array $emailRecipient = ['p.ivanov@stovesta.ru'];
	private string $emailSubject = 'Синхронизация StoVesta';
	
	/**
	 * Синхронизация из карточки товара
	 * Вызов /index.php?option=com_cobalt&task=b0sync.save
	 */
	// Пример входного массива
/*	$request = [
	    'id' => '52203',
	    'type' => 1,    1- запчасть, 2- услуга, 3- аксессуар
	    'price_general' => 8120,
	    'price_simple' => 8039
	    'price_silver' => 7958,
	    'price_gold' => 7876,
	    'price_special' => 0,
	    'price_delivery' => 7714,
	    'sales' => -1,
	    'hit' => -1,
	    'is_original' => 1,
	    'is_by_order' => -1,
	    'is_wait' => 1,
	    'is_special' => -1,
	    're_sedova' => 0,
	    're_khimikov' => 1,
	    're_kultury' => 0,
	    're_zhukova' => 0,
		're_planernaya' => 1,
	];*/
	
	public function save(): void
	{
        $this->setParams();

//        $input = Factory::getApplication()->input;
/*        $mode = $input->get('mode', 'production');
        if ($mode === 'test') {
            $paramId = $input->get('param_id', '');
            $paramValue = $input->get('param_value', '');
            $this->$paramId = $paramValue;
        }*/

		// Если синхронизация запрещена- выход
//		if (!$this->enableSyncSave) {
		if (!JFactory::getApplication()->getParams()->get('enableSyncSave', true)) {
			jexit('500: Синхронизация запрещена');
		}
		$this->logs[] = '<p>Карточка товара/услуги</p>';

		// Получаем входной запрос и проверяем его
		$request = $this->getRequest();
		if (empty($request)) {
			$this->logs[] = '<p>Пустое тело входного запроса</p>';
			$this->emailSubject .= ' - Пустое тело входного запроса';
			$this->writeLogs();
			$this->sendMail();
			jexit('500: Пустое тело входного запроса');
		}

        // Получаем необходимые параметры входного запроса
//        $requestId = $request['id'];
        $requestTotal = $request['total'];
        $requestHit = $request['hit'];
        $requestSales = $request['sales'];

		// Записываем входной запрос в лог
		if ($this->enableRequestLog) {
			$this->logs[] = '<p>Входной массив:</p>';
			$this->logs[] = print_r($request, true);
//			$this->logs[] = 'total: ' . print_r($requestTotal, true);
		}

		// Проверяем входной запрос на валидность id
		if (!$this->verifyId($request)) {
			$this->logs[] = '<p>Входной запрос не прошел валидацию на id: ' . $request['id'] . '</p>';
			$this->emailSubject .= ' - Входной запрос не прошел валидацию на id';
			$this->writeLogs();
			$this->sendMail();
			jexit('500: Входной запрос не прошел валидацию на id');
		}

		// Получаем id записи по коду товара/услуги
		$recordId = $this->getRecordId($request['id']);
		if ($recordId === 0) {
			$this->logs[] = '<p>Запись с кодом товара/услуги ' . $request['id'] . ' не найдена</p>';
			$this->emailSubject .= ' - Запись с кодом товара/услуги не найдена';
			$this->writeLogs();
			$this->sendMail();
			jexit('500: Запись с кодом товара/услуги ' . $request['id'] . ' не найдена');
		}

		// Получаем запись по id
		/** @var StdClass $record */
		$record = $this->getRecord($recordId);
		//TODO сделать проверку на наличие записи

		// Получаем id типа записи
		/** @var string $typeId */
		$typeId = $record->type_id;
        switch ($typeId) {
            case (string)SparepartIds::ID_TYPE:
                $typeCompare = 1;
                break;
            case (string)AccessoryIds::ID_TYPE:
                $typeCompare = 3;
                break;
            case (string)WorkIds::ID_TYPE:
                $typeCompare = 2;
                break;
        }
        if ($request['type'] !== $typeCompare) {
            $this->logs[] = '<p>Несоответствие типов ' . $request['id'] . '</p>';
            $this->emailSubject .= ' - Несоответствие типов ' . $request['id'];
            $this->writeLogs();
            $this->sendMail();
            jexit('500: Несоответствие типов ' . $request['id']);
        }

		// Проверяем входной запрос
		if (!$this->verifyRequest($request, $typeId)) {
			$this->logs[] = '<p>Входной запрос не прошел верификацию</p>';
			$this->emailSubject .= ' - Входной запрос не прошел верификацию';
			$this->writeLogs();
			$this->sendMail();
			jexit('500: Входной запрос не прошел верификацию');
		}

		// Получаем поля записи
		$fields = json_decode($record->fields, true, 512, JSON_THROW_ON_ERROR);

		// Устанавливаем новые значения полей
		$newFields = $this->setFields($fields, $typeId, $request);

		// Обновляем запись
		if (!CobaltApi::updateRecord($recordId, [], $newFields)) {
			$this->logs[] = '<p>Ошибка обновления записи</p>';
			$this->emailSubject .= ' - Ошибка обновления записи';
			$this->writeLogs();
			$this->sendMail();
			jexit('500: Ошибка обновления записи');
		}
		$this->logs[] = '<p>Успешное обновление записи: ' . $recordId . '</p>';
		$this->emailSubject .= ' - Успешное обновление записи';

		// Запись контроля версий
		if ($this->enableVersionControl) {
			// Получаем объект типа записи
			$type = $this->getType($typeId);
			$versions = JTable::getInstance('Audit_versions', 'CobaltTable');
			$version  = $versions->snapshot($recordId, $type);
			$record->version = $version;
			$this->logs[] = 'Успешная запись контроля версий, версия: ' . print_r($version, true);
			
			ATlog::log($record, ATlog::REC_EDIT);
			
		}

		// Формирование YML файла
		if ($this->enableYml){
			if (($typeId == SparepartIds::ID_TYPE || $typeId == AccessoryIds::ID_TYPE) && $requestTotal == 0){
				$yml = new Yml('marketplace');
				if ($yml->render()){
					$this->logs[] = '<p>Успешное формирование YML файла</p>';
				}
				else {
					$this->logs[] = '<p>Ошибка формирования YML файла</p>';
					$this->writeLogs();
					$this->sendMail();
					jexit('Ошибка формирования YML файла: '.$recordId);
				}
			}
		}

		// Запись логов
		if ($this->enableSuccessLogs) {
			$this->writeLogs();
		}

		// Отправка почты
		if ($this->enableSuccessEmail) {
			$this->sendMail();
		}
		jexit('200: Успешное обновление записи: ' .$recordId);
	}
	
	/**
	 * Синхронизация из расходного / приходного документа
	 * Вызов /index.php?option=com_cobalt&task=b0sync.save_remains
	 */
	// $request - ассоциативный массив, ключ массива - код товара
	// Пример входного массива
	/* $request = [
				'51277' => [
					're_sedova' => 1,
					're_kultury' => 1,
					're_zhukova' => 1,
					're_planernaya' => 1,
					'doc_type' => 'Приходная накладная',
					'doc_number' => 'ВРФР-000070',
					'doc_date' => '2020-02-25 13:32:39',
				],
				'50767' => [
					're_sedova' => 1,
					're_kultury' => 1,
					're_zhukova' => 2,
					're_planernaya' => 1,
					'doc_type' => 'Приходная накладная',
					'doc_number' => 'ВРФР-000070',
					'doc_date' => '2020-02-25 13:32:39',
				],
			];*/
	
	public function save_remains (): void
	{
		$this->setParams();

		// Если синхронизация запрещена- выход
		if (!$this->enableSyncSaveRemains) {
			jexit('500: Синхронизация запрещена');
		}
		$this->logs[] = '<p>Расходный/приходный документ</p>';
		
		$request = $this->getRequest();
		if (empty($request)) {
			$this->logs[] = 'Пустое тело входного запроса (расходный документ)';
			$this->emailSubject .= ' - Пустое тело входного запроса (расходный документ)';
			$this->writeLogs();
			$this->sendMail();
			jexit('500: Пустое тело входного запроса (расходный документ)');
		}
		
		if ($this->enableRequestLog) {
			$this->logs[] = '<p>Входной массив:</p>';
			$this->logs[] = print_r($request, true);
		}

		// Проверка правильности запроса
		foreach ($request as $productCode => $product) {
			if (!$this->verifyRequestRemains($product)){
				$this->logs[] = 'Входной запрос не прошел верификацию, код товара: ' . $productCode;
				$this->emailSubject .= " - Входной запрос не прошел верификацию, код товара: ' . $productCode";
				$this->writeLogs();
				$this->sendMail();
				jexit('500: Входной запрос не прошел верификацию, код товара: ' . $productCode);
			}
		}
//		// Получаем параметры запроса
//		$requestDocType = $_GET['doc_type'] ?? '';
//		$requestDocNumber = $_GET['doc_number'] ?? '';
//		$requestDocDate = $_GET['doc_date'] ?? '';
//
//		$this->logs[] = '=====' . $requestDocType . ' ' . $requestDocNumber . ' от ' . $requestDocDate . '=====';
		
		// Проверка существования записей в record_values
		$recordsIds = [];
		foreach ($request as $productCode => $product) {
			$recId = $this->getRecordId($productCode);
			if ($recId === 0) {
				$this->logs[] = '<p>Запись с кодом товара/услуги ' . $productCode . ' не найдена</p>';
				$this->emailSubject .= ' - Запись с кодом товара/услуги не найдена';
				$this->writeLogs();
				$this->sendMail();
				jexit('500: Запись с кодом товара/услуги ' . $productCode . ' не найдена');
			}
			$recordsIds[$productCode]['record_id'] = $recId;
		}
		// Объединяем массивы- в $request добавляем id записи
		foreach ($request as $productCode => $product) {
			$request[$productCode]['record_id'] = $recordsIds[$productCode]['record_id'];
		}

		foreach ($request as $productCode => $product) {
			$recordId = $product['record_id'];
			// Получаем запись по id
			$record = $this->getRecord($recordId);
			// Получаем id типа записи (string)
			$typeId = $record->type_id;
            // Проверяем соответствие типов
            if ($typeId !== $this->convertPostId($product['type'])) {
                $this->logs[] = '<p>Запись с кодом товара/услуги ' . $productCode . '- несоответствие типов</p>';
                $this->emailSubject .= ' - Запись с кодом товара/услуги- несоответствие типов';
                $this->writeLogs();
                $this->sendMail();
                jexit('500: Запись с кодом товара/услуги ' . $productCode . '- несоответствие типов');
            }
			// Получаем поля записи
			$fields = json_decode($record->fields, true, 512, JSON_THROW_ON_ERROR);

			// Устанавливаем старые остатки (array)
			$oldRemains = $this->setOldRemains($fields, $typeId);

			// Устанавливаем новые значения полей
			$newFields = $this->setFieldsRemains($fields, $typeId, $product);

			// Обновляем запись
			if (!CobaltApi::updateRecord($recordId, [], $newFields)) {
				$this->logs[] = '<p>Ошибка обновления записи</p>';
				$this->emailSubject .= ' - Ошибка обновления записи';
				$this->writeLogs();
				$this->sendMail();
				jexit('500: Ошибка обновления записи');
			}
			$this->logs[] = '<p>Успешное обновление записи ' . $productCode . ' ==> ' .
				'sedova- '     . $oldRemains['sedova']     . '/' . $product['re_sedova']     . ', ' .
				'khimikov- '   . $oldRemains['khimikov']   . '/' . $product['re_khimikov']   . ', ' .
				'zhukova- '    . $oldRemains['zhukova']    . '/' . $product['re_zhukova']    . ', ' .
				'kultury- '    . $oldRemains['kultury']    . '/' . $product['re_kultury']    . ', ' .
				'planernaya- ' . $oldRemains['planernaya'] . '/' . $product['re_planernaya'] . '</p>';
			$this->setNeedYml($fields, $newFields, $typeId);
		}
		$this->emailSubject .= ' - Успешное обновление записи';
		// Создание YML файла
		if ($this->enableYml){
			if ($this->needYml) {
				$yml = new Yml('market');
				if ($yml->render()){
					$this->logs[] = '<p>Успешное формирование YML файла</p>';
				}
				else {
					$this->logs[] = 'Ошибка формирования YML файла';
					$this->writeLogs();
					$this->sendMail();
					jexit('500: Ошибка формирования YFull файла');
				}
			}
		}
		
		// Запись логов
		if ($this->enableSuccessLogs) {
			$this->writeLogs();
		}

		// Отправка почты
		if ($this->enableSuccessEmailRemains) {
			$this->sendMail();
		}
		JExit('200: Успешное обновление');
	}
	
	/**
	 * Устанавливает параметры на основании параметров Cobalt
	 */
	private function setParams(): void
	{
		/** @var JRegistry $paramsApp */
		$paramsApp = JFactory::getApplication()->getParams();
//        $this->enableSyncSave = (bool) $paramsApp->get('enableSyncSave', true);
        $this->enableSyncSaveRemains = (bool) $paramsApp->get('enableSyncSaveRemains', true);
		$this->enableVersionControl = $paramsApp->get('enableVersionControl', false);
		$this->enableYml = $paramsApp->get('enableYml', true);
		$this->enableSuccessEmail = $paramsApp->get('enableSuccessEmail', false);
		$this->enableSuccessEmailRemains = $paramsApp->get('enableSuccessEmailRemains', false);
		$this->enableSuccessLogs = $paramsApp->get('enableSuccessLogs', false);
		$this->enableRequestLog = $paramsApp->get('enableRequestLog', false);
	}

	/**
	 * Возвращает тело входного запроса
	 */
	private function getRequest() :array
	{
        if (!file_get_contents('php://input')) {
            return [];
        }
		return json_decode(file_get_contents('php://input'), true, 512, JSON_THROW_ON_ERROR);
	}

	/**
	 * Проверяет входной запрос на наличие и правильность кода товара/услуги
	 */
	private function verifyId(array $request): bool
	{
		if (!isset($request['id'])) {
			$this->logs[] = 'Отсутствует код товара/услуги';
			return false;
		}
		if (!preg_match("/^[L0-9]{1,6}$/", $request['id'])) {
			$this->logs[] = 'Id не число';
			return false;
		}
		return true;
	}

	/**
	 * @return int - результат выполнения:
	 *             id записи - запись найдена
	 *             0 - запись не найдена
	 */
	private function getRecordId(string $code1c): int
	{
		$db = JFactory::getDbo();
		$query = "SELECT record_id FROM #__js_res_record_values
			WHERE ((field_id='{$this->productCodeSparepart}' || field_id='{$this->productCodeAccessory}' || field_id='{$this->serviceCode}') && field_value='{$code1c}')";
		$db->setQuery($query);
		$id = $db->loadResult();

        return $id ?? 0;
	}

	/**
	 * Проверяет входной запрос на наличие всех параметров для карточки товара
	 */
	private function verifyRequest(array $request, string $typeId): bool
	{
		if (!isset($request['type'])) {
			$this->logs[] = "type отсутствует<br>";
			return false;
		}
		if (!isset($request['price_general'])) {
			$this->logs[] = "price_general отсутствует<br>";
			return false;
		}
		if (!isset($request['price_simple'])) {
			$this->logs[] = "price_simple отсутствует<br>";
			return false;
		}
		if (!isset($request['price_silver'])) {
			$this->logs[] = "price_silver отсутствует<br>";
			return false;
		}
		if (!isset($request['price_gold'])) {
			$this->logs[] = "price_gold отсутствует<br>";
			return false;
		}
		if (!isset($request['is_special'])) {
			$this->logs[] = "is_special отсутствует<br>";
			return false;
		}
		if (!isset($request['price_special'])) {
			$this->logs[] = "price_special отсутствует<br>";
			return false;
		}
		if ($typeId === SparepartIds::ID_TYPE || $typeId === AccessoryIds::ID_TYPE) {
			if (!isset($request['price_delivery'])) {
				$this->logs[] = "price_delivery отсутствует<br>";
				return false;
			}
			if (!isset($request['is_original'])) {
				$this->logs[] = "is_original отсутствует<br>";
				return false;
			}
			if (!isset($request['is_by_order'])) {
				$this->logs[] = "is_by_order отсутствует<br>";
				return false;
			}
			if (!isset($request['re_sedova'])) {
				$this->logs[] = "re_sedova отсутствует<br>";
				return false;
			}
			if (!isset($request['re_khimikov'])) {
				$this->logs[] = "re_khimikov отсутствует<br>";
				return false;
			}
			if (!isset($request['re_zhukova'])) {
				$this->logs[] = "re_zhukova отсутствует<br>";
				return false;
			}
			if (!isset($request['re_kultury'])) {
				$this->logs[] = "re_kultury отсутствует<br>";
				return false;
			}
			if (!isset($request['re_planernaya'])) {
				$this->logs[] = "re_planernaya отсутствует<br>";
				return false;
			}
		}
		return true;
	}

	/**
	 * Проверяет входной запрос на наличие всех параметров для расходного документа
	 */
	private function verifyRequestRemains(array $request): bool
	{
		if (!isset($request['type'])) {
			$this->logs[] = "type отсутствует<br>";
			return false;
		}
		if (!isset($request['re_sedova'])) {
			$this->logs[] = "re_sedova отсутствует<br>";
			return false;
		}
		if (!isset($request['re_khimikov'])) {
			$this->logs[] = "re_khimikov отсутствует<br>";
			return false;
		}
		if (!isset($request['re_zhukova'])) {
			$this->logs[] = "re_zhukova отсутствует<br>";
			return false;
		}
		if (!isset($request['re_kultury'])) {
			$this->logs[] = "re_kultury отсутствует<br>";
			return false;
		}
		if (!isset($request['re_planernaya'])) {
			$this->logs[] = "re_planernaya отсутствует<br>";
			return false;
		}
		return true;
	}

	/**
	 * Возвращает объект записи по id
	 */
	private function getRecord(string $recordId): stdClass
	{
		return ItemsStore::getRecord($recordId);
	}

	/**
	 * Возвращает объект типа записи по id типа
	 */
	private function getType(string $typeId)
	{
		return ItemsStore::getType($typeId);
	}

	/**
	 * Возвращает объект типа записи по id типа
	 */
	private function convertPostId(int $typeId): string
	{
		switch ($typeId) {
            case 1:
                return SparepartIds::ID_TYPE;
            case 2:
                return (string)WorkIds::ID_TYPE;
            case 3:
                return AccessoryIds::ID_TYPE;
			default:
				return 0;
		}
	}

	/**
	 * Устанавливает новые поля записи для карточки товара
	 */
	private function setFields(array $fields, string $typeId, array $request): array
	{
//		$newFields = $fields;
		$newFields = [];
		if ($typeId === (string)SparepartIds::ID_TYPE) { // Запчасть
			// Устанавливаем поля цен
			$newFields[SparepartIds::ID_PRICE_GENERAL] = $request['price_general'] ?? '0';
			$newFields[SparepartIds::ID_PRICE_SPECIAL] = $request['price_special'] ?? '0';
			$newFields[SparepartIds::ID_PRICE_SIMPLE] = $request['price_simple'] ?? '0';
			$newFields[SparepartIds::ID_PRICE_SILVER] = $request['price_silver'] ?? '0';
			$newFields[SparepartIds::ID_PRICE_GOLD] = $request['price_gold'] ?? '0';
			$newFields[SparepartIds::ID_PRICE_DELIVERY] = $request['price_delivery'] ?? '0';
			$newFields[SparepartIds::ID_IS_ORIGINAL] = $request['is_original'] ?? '-1';
			$newFields[SparepartIds::ID_IS_SPECIAL] = $request['is_special'] ?? '-1';
			$newFields[SparepartIds::ID_IS_HIT] = $request['hit'] ?? '-1';
			$newFields[SparepartIds::ID_IS_SALES] = $request['sales'] ?? '-1';
			$ibo = $request['is_by_order'] ?? '-1';
			$ibw = $request['is_wait'] ?? '-1';
			if ($ibo === -1 && $ibw === -1) {
				$newFields[SparepartIds::ID_IS_BY_ORDER] = -1;
			}
			else {
				$newFields[SparepartIds::ID_IS_BY_ORDER] = 1;
			}
			// Устанавливаем поля наличия
			$newFields[SparepartIds::ID_SEDOVA] = $request['re_sedova'] ?? '0';
			$newFields[SparepartIds::ID_KHIMIKOV] = $request['re_khimikov'] ?? '0';
			$newFields[SparepartIds::ID_ZHUKOVA] = $request['re_zhukova'] ?? '0';
			$newFields[SparepartIds::ID_KULTURY] = $request['re_kultury'] ?? '0';
			$newFields[SparepartIds::ID_PLANERNAYA] = $request['re_planernaya'] ?? '0';
		}
		elseif ($typeId === (string)AccessoryIds::ID_TYPE) {   // Аксессуар
			// Устанавливаем поля цен
			$newFields[AccessoryIds::ID_PRICE_GENERAL] = $request['price_general'] ?? '0';
			$newFields[AccessoryIds::ID_PRICE_SPECIAL] = $request['price_special'] ?? '0';
			$newFields[AccessoryIds::ID_PRICE_SIMPLE] = $request['price_simple'] ?? '0';
			$newFields[AccessoryIds::ID_PRICE_SILVER] = $request['price_silver'] ?? '0';
			$newFields[AccessoryIds::ID_PRICE_GOLD] = $request['price_gold'] ?? '0';
			$newFields[AccessoryIds::ID_PRICE_DELIVERY] = $request['price_delivery'] ?? '0';
			$newFields[AccessoryIds::ID_IS_ORIGINAL] = $request['is_original'] ?? '-1';
			$newFields[AccessoryIds::ID_IS_SPECIAL] = $request['is_special'] ?? '-1';
			$newFields[AccessoryIds::ID_IS_HIT] = $request['hit'] ?? '-1';
			$newFields[AccessoryIds::ID_IS_SALES] = $request['sales'] ?? '-1';
			$ibo = $request['is_by_order'] ?? '-1';
			$ibw = $request['is_wait'] ?? '-1';
			if ($ibo === -1 && $ibw === -1) {
				$newFields[AccessoryIds::ID_IS_BY_ORDER] = -1;
			}
			else {
				$newFields[AccessoryIds::ID_IS_BY_ORDER] = 1;
			}
			// Устанавливаем поля наличия
			$newFields[AccessoryIds::ID_SEDOVA] = $request['re_sedova'] ?? '0';
			$newFields[AccessoryIds::ID_KHIMIKOV] = $request['re_khimikov'] ?? '0';
			$newFields[AccessoryIds::ID_ZHUKOVA] = $request['re_zhukova'] ?? '0';
			$newFields[AccessoryIds::ID_KULTURY] = $request['re_kultury'] ?? '0';
			$newFields[AccessoryIds::ID_PLANERNAYA] = $request['re_planernaya'] ?? '0';
		}
		elseif ($typeId === (string)WorkIds::ID_TYPE) {   // Работа
			// Устанавливаем поля цен
			$newFields[WorkIds::ID_PRICE_GENERAL] = $request['price_general'] ?? '0';
			$newFields[WorkIds::ID_PRICE_SIMPLE] = $request['price_simple'] ?? '0';
			$newFields[WorkIds::ID_PRICE_SILVER] = $request['price_silver'] ?? '0';
			$newFields[WorkIds::ID_PRICE_GOLD] = $request['price_gold'] ?? '0';
			$newFields[WorkIds::ID_PRICE_FIRST_VISIT] = isset($request['price_general']) ? $request['price_general'] * $this->discountFirstVisit : '0';
			$newFields[WorkIds::ID_IS_SPECIAL] = $request['is_special'] ?? '-1';
			$newFields[WorkIds::ID_PRICE_SPECIAL] = $request['price_special'] ?? '0';
			$newFields[WorkIds::ID_IS_HIT] = $request['hit'] ?? '-1';
		}
		else {
			$this->logs[] = 'не тот тип- ' . $typeId . "\n";
			$this->writeLogs();
			$this->sendMail();
			jexit('500: не тот тип- ' . $typeId);
		}
		return $newFields;
	}
	
	/**
	 * Устанавливает значения старых остатков
	 */
	private function setOldRemains(array $fields, string $typeId) :array
	{
		if ($typeId == SparepartIds::ID_TYPE) { // Запчасть
            return [
                'sedova'     => $fields[SparepartIds::ID_SEDOVA],
                'khimikov'   => $fields[SparepartIds::ID_KHIMIKOV],
                'zhukova'    => $fields[SparepartIds::ID_ZHUKOVA],
                'kultury'    => $fields[SparepartIds::ID_KULTURY],
                'planernaya' => $fields[SparepartIds::ID_PLANERNAYA],
            ];
		}
		elseif ($typeId == AccessoryIds::ID_TYPE) {   // Аксессуар
            return [
                'sedova'     => $fields[AccessoryIds::ID_SEDOVA],
                'khimikov'   => $fields[AccessoryIds::ID_KHIMIKOV],
                'zhukova'    => $fields[AccessoryIds::ID_ZHUKOVA],
                'kultury'    => $fields[AccessoryIds::ID_KULTURY],
                'planernaya' => $fields[AccessoryIds::ID_PLANERNAYA],
            ];
        }
		else {
			return [];
		}
	}

	/**
	 * Устанавливает новые поля записи для расходного документа
	 */
	private function setFieldsRemains(array $fields, string $typeId, array $post) :array
	{
		$newFields = [];
		if ($typeId == SparepartIds::ID_TYPE) { // Запчасть
			$newFields[SparepartIds::ID_SEDOVA] = $post['re_sedova'] ?? 0;
			$newFields[SparepartIds::ID_KHIMIKOV] = $post['re_khimikov'] ?? 0;
			$newFields[SparepartIds::ID_ZHUKOVA] = $post['re_zhukova'] ?? 0;
			$newFields[SparepartIds::ID_KULTURY] = $post['re_kultury'] ?? 0;
			$newFields[SparepartIds::ID_PLANERNAYA] = $post['re_planernaya'] ?? 0;
			// устанавливаем поле Под Заказ
			if ($post['re_sedova']===0 && $post['re_khimikov']===0 && $post['re_zhukova']===0 && $post['re_kultury']===0 && $post['re_planernaya']===0) {
				$newFields[SparepartIds::ID_IS_BY_ORDER] = 1;
			}
			else {
				$newFields[SparepartIds::ID_IS_BY_ORDER] = -1;
			}
		}
		elseif ($typeId == AccessoryIds::ID_TYPE) {   // Аксессуар
			$newFields[AccessoryIds::ID_SEDOVA] = $post['re_sedova'] ?? 0;
			$newFields[AccessoryIds::ID_KHIMIKOV] = $post['re_khimikov'] ?? 0;
			$newFields[AccessoryIds::ID_ZHUKOVA] = $post['re_zhukova'] ?? 0;
			$newFields[AccessoryIds::ID_KULTURY] = $post['re_kultury'] ?? 0;
			$newFields[AccessoryIds::ID_PLANERNAYA] = $post['re_planernaya'] ?? 0;
			// устанавливаем поле Под Заказ
			if ($post['re_sedova']===0 && $post['re_khimikov']===0 && $post['re_zhukova']===0 && $post['re_kultury']===0 && $post['re_planernaya']===0) {
				$newFields[AccessoryIds::ID_IS_BY_ORDER] = 1;
			}
			else {
				$newFields[AccessoryIds::ID_IS_BY_ORDER] = -1;
			}
		}
		else {
			$this->logs[] ='не тот тип- ' . $typeId;
			$this->writeLogs();
			$this->sendMail();
			jexit('500: не тот тип- ' . $typeId);
		}
		return $newFields;
	}

	/**
	 * Устанавливает необходимость формирования Yml файла
	 */
	private function setNeedYml(array $fields, array $newFields, string $typeId): void
	{
		if ($typeId === (string) SparepartIds::ID_TYPE) { // Запчасть
			if ($fields[SparepartIds::ID_KHIMIKOV] !== $newFields[SparepartIds::ID_KHIMIKOV])
			{
				$this->logs[]  = 'Формируем YML: ' . $fields[SparepartIds::ID_KHIMIKOV] . '/' . $newFields[SparepartIds::ID_KHIMIKOV];
				$this->needYml = true;
			}
		}
		elseif ($typeId === (string) AccessoryIds::ID_TYPE) {   // Аксессуар
			if ($fields[AccessoryIds::ID_KHIMIKOV] !== $newFields[AccessoryIds::ID_KHIMIKOV])
			{
				$this->logs[]  = 'Формируем YML: ' . $fields[AccessoryIds::ID_KHIMIKOV] . '/' . $newFields[AccessoryIds::ID_KHIMIKOV];
				$this->needYml = true;
			}
		}
	}
	
	/**
	 * Записывает log файл
	 */
	private function writeLogs(): void
	{
		$log_file_handle = fopen(JPATH_ROOT . $this->log_file_path, 'ab+');
		if ($log_file_handle === false) {
			die('500: Ошибка открытия файла лога');
		}

		fwrite($log_file_handle, '===== ' . date_format(new DateTime(), 'Y-m-d H:i:s') . ' =====' . "\n");

		foreach ($this->logs as $log) {
			fwrite($log_file_handle, $log . "\n");
		}
		fclose($log_file_handle);
	}
	
	/**
	 * Отправляет почту
	 */
	private function sendMail(): bool
	{
		$messageBody = '';
		foreach ($this->logs as $log) {
			$messageBody .= $log . "\n";
		}
		return JFactory::getMailer()->sendMail($this->emailFrom, $this->emailFromName, $this->emailRecipient, $this->emailSubject, $messageBody, TRUE);
	}
}
