<?php

declare(strict_types=1);

namespace Celerik\Ui\Data;

/**
 * A source of rows for data-bound components (Grid today, Scheduler and others later).
 *
 * Field names in the request are already validated against FIELD_PATTERN and the
 * component's whitelist, so implementations may use them as column names.
 */
interface DataSourceInterface
{
    public function fetch(DataRequest $request): DataResult;
}
