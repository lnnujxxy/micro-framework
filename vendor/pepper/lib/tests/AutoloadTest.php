<?php

final class AutoloadTest extends \PHPUnit\Framework\TestCase {

	public function testCanBeLoadedCacheClass(){
		$this->assertEquals(true, class_exists('\\Pepper\\Lib\\Cache'));
	}

	public function testS3Autoload(){
	    $this->assertEquals(true, class_exists('\\Qihoo\\OssSvc'));
    }

    public function testS3Upload(){
        $this->assertEquals(true, class_exists('\\Pepper\\Lib\\S3\\S3Upload'));
    }
}
