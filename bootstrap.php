<?php

// Package bootstrap for FuelPHP.
\Autoloader::add_core_namespace('FuelVue');

\Autoloader::add_classes(array(
	'FuelVue\\FuelVue' => __DIR__.'/src/classes/FuelVue.php',
	'FuelVue\\FuelVueException' => __DIR__.'/src/classes/FuelVueException.php',
	'FuelVue\\Inertia' => __DIR__.'/src/classes/Inertia.php',
	'FuelVue\\InertiaService' => __DIR__.'/src/classes/InertiaService.php',
	'FuelVue\\InertiaResponse' => __DIR__.'/src/classes/InertiaResponse.php',
	'FuelVue\\InertiaGlobalStore' => __DIR__.'/src/classes/InertiaGlobalStore.php',
	'FuelVue\\SessionKey' => __DIR__.'/src/classes/SessionKey.php',
	'FuelVue\\Deferrable' => __DIR__.'/src/classes/Deferrable.php',
	'FuelVue\\Mergeable' => __DIR__.'/src/classes/Mergeable.php',
	'FuelVue\\Onceable' => __DIR__.'/src/classes/Onceable.php',
	'FuelVue\\DefersProps' => __DIR__.'/src/classes/DefersProps.php',
	'FuelVue\\MergesProps' => __DIR__.'/src/classes/MergesProps.php',
	'FuelVue\\ResolvesOnce' => __DIR__.'/src/classes/ResolvesOnce.php',
	'FuelVue\\Support\\InertiaController' => __DIR__.'/src/classes/Support/InertiaController.php',
	'FuelVue\\Support\\InertiaControllerTrait' => __DIR__.'/src/classes/Support/InertiaControllerTrait.php',
	'FuelVue\\OptionalProp' => __DIR__.'/src/classes/OptionalProp.php',
	'FuelVue\\AlwaysProp' => __DIR__.'/src/classes/AlwaysProp.php',
	'FuelVue\\OnceProp' => __DIR__.'/src/classes/OnceProp.php',
	'FuelVue\\MergeProp' => __DIR__.'/src/classes/MergeProp.php',
	'FuelVue\\DeferredProp' => __DIR__.'/src/classes/DeferredProp.php',
	'FuelVue\\HttpResponseException' => __DIR__.'/src/classes/HttpResponseException.php',
	'FuelVue\\ResponseFactory' => __DIR__.'/src/classes/ResponseFactory.php',
));
