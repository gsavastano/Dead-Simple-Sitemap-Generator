<?php
 
require_once __DIR__.'/../src/Dssg.php';
use gsavastano\Dssg\Dssg;
 
class DssgTest extends PHPUnit_Framework_TestCase {
 
	public function testValProtocol()
	{
		$crawl = new Dssg;

		$this->assertTrue($crawl->valProtocol('https'));
		$this->assertTrue($crawl->valProtocol('http'));
		$this->assertFalse($crawl->valProtocol('non-http'));
	}

	public function testValFileName()
	{
		$crawl = new Dssg;

		$this->assertTrue($crawl->valFileName('sitemap.xml'));
		$this->assertTrue($crawl->valFileName('site_map_2.xml'));
		$this->assertFalse($crawl->valFileName('site_map.it.xml'));
		$this->assertFalse($crawl->valFileName('site_map.it'));
	}


	public function testValFrequency()
	{
		$crawl = new Dssg;

		$this->assertTrue($crawl->valFrequency('always'));
		$this->assertTrue($crawl->valFrequency('hourly'));
		$this->assertTrue($crawl->valFrequency('daily'));
		$this->assertTrue($crawl->valFrequency('weekly'));
		$this->assertTrue($crawl->valFrequency('monthly'));
		$this->assertTrue($crawl->valFrequency('yearly'));
		$this->assertTrue($crawl->valFrequency('never'));
		$this->assertFalse($crawl->valFrequency('falseAlways'));
		$this->assertFalse($crawl->valFrequency('falseHourly'));
		$this->assertFalse($crawl->valFrequency('falseDaily'));
		$this->assertFalse($crawl->valFrequency('falseWeekly'));
		$this->assertFalse($crawl->valFrequency('falseMonthly'));
		$this->assertFalse($crawl->valFrequency('falseYearly'));
		$this->assertFalse($crawl->valFrequency('falseNever'));
	}

	public function testValPriority()
	{
		$crawl = new Dssg;

		$this->assertTrue($crawl->valPriority(1));
		$this->assertFalse($crawl->valPriority(0));
		$this->assertTrue($crawl->valPriority(0.2));
		$this->assertFalse($crawl->valPriority(2));
		$this->assertFalse($crawl->valPriority(-1));
		$this->assertFalse($crawl->valPriority('d'));
	}






}