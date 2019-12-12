<?php

Router::get('/', 'HomeController.index');

Router::get('/command/find', 'HomeController.find');

Router::post('/command/insert', 'HomeController.insert');
Router::post('/command/update', 'HomeController.update');
Router::post('/command/replace', 'HomeController.replace');
Router::post('/command/delete', 'HomeController.delete');