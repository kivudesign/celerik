<?php

use Celerik\Ui\Components\Chart;

return Chart::make('revenue')
    ->type('line')
    ->title('Revenue per region')
    ->labels(['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'])
    ->dataset('North', [1200, 1900, 1500, 2100, 2400, 2300])
    ->dataset('South', [900, 1100, 1700, 1600, 1900, 2200])
    ->dataset('East', [600, 800, 1000, 1400, 1300, 1700])
    ->height(300);
