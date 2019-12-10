<?php

Router::addRoutes(Array(
    Array('GET', '/', 'HomeController.index'),
    Array('GET', '/user/[i:id]', 'HomeController.index')
));

Router::get('/home', 'HomeController.index');
