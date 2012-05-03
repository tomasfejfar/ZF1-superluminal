TF_ZF1Superluminal plugin
===============
Version 0.0.1

Introduction
------------
This plugin is ZF1 port of  [EdpSuperluminal module for ZF2](https://github.com/EvanDotPro/EdpSuperluminal). 

Installation
------------

- Copy the library folder over your library folder
- Connect the plugin to your front controller
    <?php
	Zend_Controller_Front::getInstance()->registerPlugin(new Tf_Controller_Plugin_Superlumibnal());
- The cache is stored in path stored in ZF_CLASS_CACHE constant. 
- So ideally you define it in your index.php file
    <?php
	define('ZF_CLASS_CACHE', APPLICATION_PATH . '/cache.php');
	if (file_exists(ZF_CLASS_CACHE)) {
		@include ZF_CLASS_CACHE;
	}
	
- The cache is generated on every request, so you may conditionally add/remove it based on anything you like - e.g.: based on request params, time, server, etc.
- BEWARE! Any request generating the cache will be VERY SLOW. To feel the speed, disable the plugin and leave the cache file. 
	