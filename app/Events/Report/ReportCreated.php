<?php

namespace App\Events\Report;

use App\Models\Report;
use App\Models\Video;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ReportCreated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $report;
    public $model;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(Report $report, $model)
    {
        $this->report = $report;
        $this->model = $model;
    }

}
