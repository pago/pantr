<?php
use pake\Pake;

class PropertyTest extends PHPUnit_Framework_TestCase {
	/**
	 * it should support nested key fetching
	 * @author Patrick Gotthardt
	 * @test
	 */
	public function it_should_support_nested_key_fetching() {
		$data = array(
			'foo' => 'bar',
			'subarray' => array(
				'subkey' => array('subsubkey' => 'val')));
		Pake::property('test', $data);
		$this->assertEquals('bar', Pake::property('test:foo'));
		$this->assertEquals('val', Pake::property('test:subarray:subkey:subsubkey'));
	} // support nested key fetching
	
	/**
	 * it should store a property
	 * @author Patrick Gotthardt
	 * @test
	 */
	public function it_should_store_a_property() {
		Pake::property('test', $id = uniqid());
		$this->assertEquals($id, Pake::property('test'));
	} // store a property
	
	/**
	 * it should throw an exception when colon is used in property name
	 * @author Patrick Gotthardt
	 * @test
	 * @expectedException InvalidArgumentException
	 */
	public function it_should_throw_an_exception_when_colon_is_used_in_property_name() {
		Pake::property('test:bar', 'foo');
	} // throw an exception when colon is used in property name
	
}