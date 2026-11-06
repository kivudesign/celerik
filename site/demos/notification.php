<?php

use Celerik\Ui\Components\Notification;

// Usually filled from the session: Notification::make('flash')->fromFlash($flash).
return Notification::make('flash')
    ->success('Profile saved.')
    ->info('A new version is available.')
    ->error('The export failed. Errors stay until dismissed.');
