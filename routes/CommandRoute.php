<?php
Router::addRoutes([
    ['command', 'make:controller', 'MakeController.controller'],
    ['command', 'make:model', 'MakeController.model'],
    ['command', 'make:route', 'MakeController.router'],
    ['command', 'list:controller', 'ListController.controller'],
    ['command', 'list:model', 'ListController.model'],
    ['command', 'list:route', 'ListController.router'],
    ['command', 'list:all', 'ListController.all'],
])->middleware('CommandMiddleware.ipFilter');

/*Router::command('make:controller', 'MakeController.controller')->middleware('CommandMiddleware.ipFilter');
Router::command('make:model', 'MakeController.model')->middleware('CommandMiddleware.ipFilter');
Router::command('make:router', 'MakeController.router')->middleware('CommandMiddleware.ipFilter');
Router::command('make:all', 'MakeController.all')->middleware('CommandMiddleware.ipFilter');

Router::command('list:controller', 'ListController.controller')->middleware('CommandMiddleware.ipFilter');
Router::command('list:model', 'ListController.model')->middleware('CommandMiddleware.ipFilter');
Router::command('list:router', 'ListController.router')->middleware('CommandMiddleware.ipFilter');*/
