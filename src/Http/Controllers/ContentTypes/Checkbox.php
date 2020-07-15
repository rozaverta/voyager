<?php

namespace TCG\Voyager\Http\Controllers\ContentTypes;

class Checkbox extends BaseType
{
    /**
     * @return int
     */
    public function handle()
    {
        return (int) in_array($this->request->input($this->row->field), ['on', '1', 'yes', 'true'], true);
    }
}
