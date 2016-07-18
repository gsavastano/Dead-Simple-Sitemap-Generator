<?php
 
require_once __DIR__.'/../src/Dssg.php';
use gsavastano\Dssg\Dssg;
 
class DssgTest extends PHPUnit_Framework_TestCase {
 
    public function testValProtocol()
    {
        $method = new ReflectionMethod('gsavastano\Dssg\Dssg', 'valProtocol');
        $method->setAccessible(TRUE);
 
        $this->assertTrue( $method->invokeArgs(new Dssg, array('http')));
        $this->assertTrue( $method->invokeArgs(new Dssg, array('https')));
        $this->assertFalse( $method->invokeArgs(new Dssg, array('non-http')));
    }

	public function testValFileName()
	{
        $method = new ReflectionMethod('gsavastano\Dssg\Dssg', 'valFileName');
        $method->setAccessible(TRUE);

		$this->assertTrue($method->invokeArgs(new Dssg, array('sitemap.xml')));
		$this->assertTrue($method->invokeArgs(new Dssg, array('site_map_2.xml')));
		$this->assertFalse($method->invokeArgs(new Dssg, array('site_map.it.xml')));
		$this->assertFalse($method->invokeArgs(new Dssg, array('site_map.it')));
	}

	public function testValFrequency()
	{
        $method = new ReflectionMethod('gsavastano\Dssg\Dssg', 'valFrequency');
        $method->setAccessible(TRUE);

		$this->assertTrue($method->invokeArgs(new Dssg, array('always')));
		$this->assertTrue($method->invokeArgs(new Dssg, array('hourly')));
		$this->assertTrue($method->invokeArgs(new Dssg, array('daily')));
		$this->assertTrue($method->invokeArgs(new Dssg, array('weekly')));
		$this->assertTrue($method->invokeArgs(new Dssg, array('monthly')));
		$this->assertTrue($method->invokeArgs(new Dssg, array('yearly')));
		$this->assertTrue($method->invokeArgs(new Dssg, array('never')));
		$this->assertFalse($method->invokeArgs(new Dssg, array('falseAlways')));
		$this->assertFalse($method->invokeArgs(new Dssg, array('falseHourly')));
		$this->assertFalse($method->invokeArgs(new Dssg, array('falseDaily')));
		$this->assertFalse($method->invokeArgs(new Dssg, array('falseWeekly')));
		$this->assertFalse($method->invokeArgs(new Dssg, array('falseMonthly')));
		$this->assertFalse($method->invokeArgs(new Dssg, array('falseYearly')));
		$this->assertFalse($method->invokeArgs(new Dssg, array('falseNever')));
	}

	public function testValPriority()
	{
        $method = new ReflectionMethod('gsavastano\Dssg\Dssg', 'valPriority');
        $method->setAccessible(TRUE);

		$this->assertTrue($method->invokeArgs(new Dssg, array(1)));
		$this->assertTrue($method->invokeArgs(new Dssg, array(0.2)));
		$this->assertFalse($method->invokeArgs(new Dssg, array(0)));
		$this->assertFalse($method->invokeArgs(new Dssg, array(2)));
		$this->assertFalse($method->invokeArgs(new Dssg, array(-1)));
		$this->assertFalse($method->invokeArgs(new Dssg, array('d')));
	}


	public function testValUrl()
	{
		$method = new ReflectionMethod('gsavastano\Dssg\Dssg', 'valUrl');
        $method->setAccessible(TRUE);

		$this->assertTrue($method->invokeArgs(new Dssg, array('http://www.example.com')));
		$this->assertTrue($method->invokeArgs(new Dssg, array('http://example.com')));
		$this->assertTrue($method->invokeArgs(new Dssg, array('https://www.example.com')));
		$this->assertTrue($method->invokeArgs(new Dssg, array('https://example.com')));
		$this->assertFalse($method->invokeArgs(new Dssg, array('httplp://www.example.com')));
		$this->assertFalse($method->invokeArgs(new Dssg, array('http://-&example.com')));
		$this->assertFalse($method->invokeArgs(new Dssg, array('https://www.example')));
		$this->assertFalse($method->invokeArgs(new Dssg, array('http://1.com')));
	}

	public function testRelToAbs()
	{
		$method = new ReflectionMethod('gsavastano\Dssg\Dssg', 'relToAbs');
        $method->setAccessible(TRUE);

        $this->assertEquals('http://www.example.com/rel2abs',$method->invokeArgs(new Dssg, array('/rel2abs','http://www.example.com/')));
		$this->assertEquals('http://www.example.com/../rel2abs',$method->invokeArgs(new Dssg, array('../rel2abs','http://www.example.com/')));
		$this->assertNotEquals('http://www.example.com/rel2abs',$method->invokeArgs(new Dssg, array('http://example.com/rel2abs','http://www.example.com/')));
	}

}