<?php

use Jxlwqq\ComposerViewer\Http\Controllers\ComposerViewerController;

Route::get('helpers/composer-viewer', ComposerViewerController::class.'@index');