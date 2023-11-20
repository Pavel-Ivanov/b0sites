<?php
defined('_JEXEC') or die();

JImport('b0.Feed.Feed');
JImport('b0.Feed.FeedConfig');
JImport('b0.Feed.FeedConfigFeeds');
JImport('b0.fixtures');

/**
 * /index.php?option=com_cobalt&task=b0feed.create
 * /usr/bin/wget -O - -q /dev/null "https://stovesta.ru/index.php?option=com_cobalt&task=b0feed.create"
 */
class CobaltControllerB0Feed extends JControllerAdmin
{
//	public int $sectionId;
//	public string $modelName;
	public Feed $feed;
	
	public function create(): void
	{
		foreach (FeedConfigFeeds::FEED_CONFIG_FEEDS as $config) {
			if ($config['isNeed']) {
				$this->feed = new Feed($config);
			}
			else {
				continue;
			}
			
			if ($this->feed->render()) {
				$message = '200: Файл '. $config['name'] .' сформирован';
			}
			else {
				$message = '200: Файл '. $config['name'] .' не сформирован';
                $this->sendMail($message);
            }
		}
        $this->sendMail('200: Файлы сформированы');
		JExit('200: Файлы сформированы');
	}
	
	private function sendMail($messageBody): bool
	{
		return JFactory::getMailer()->sendMail(
			FeedConfig::FEED_EMAIL_FROM, FeedConfig::FEED_EMAIL_FROM_NAME,
			FeedConfig::FEED_EMAIL_RECIPIENT, FeedConfig::FEED_EMAIL_SUBJECT, $messageBody, TRUE
		);
	}
}
