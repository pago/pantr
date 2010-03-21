<?php
use pantr\pantr;

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
		pantr::property('test', $data);
		$this->assertEquals('bar', pantr::property('test:foo'));
		$this->assertEquals('val', pantr::property('test:subarray:subkey:subsubkey'));
	} // support nested key fetching
	
	/**
	 * it should store a property
	 * @author Patrick Gotthardt
	 * @test
	 */
	public function it_should_store_a_property() {
		pantr::property('test', $id = uniqid());
		$this->assertEquals($id, pantr::property('test'));
	} // store a property
	
	/**
	 * it should throw an exception when colon is used in property name
	 * @author Patrick Gotthardt
	 * @test
	 * @expectedException InvalidArgumentException
	 */
	public function it_should_throw_an_exception_when_colon_is_used_in_property_name() {
		pantr::property('test:bar', 'foo');
	} // throw an exception when colon is used in property name
	
}