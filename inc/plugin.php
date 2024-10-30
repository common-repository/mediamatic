<?php

class Mediamatic_Plugin{

	public function __construct()
	{	
		$this->init_files();
	}

	private function init_files()
	{
		include_once ( MEDIAMATIC_PATH . 'inc/category.php');
		include_once ( MEDIAMATIC_PATH . 'inc/helper.php');
		include_once ( MEDIAMATIC_PATH . 'inc/sidebar.php');
	}

}

new Mediamatic_Plugin();