<?php

namespace App\Console\Commands\ModifingDatabaseData;

use App\Models\PaymentDetails;
use App\Models\Tag;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ModifyTagNamesToLowerJustAlphaSpace extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tc:tags:names-to-lowercase-alphaspace';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Modify tag names to lower without any special characters';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        DB::statement("UPDATE tags SET name = REGEXP_REPLACE(LOWER(name), '[^[:alpha:][:space:]]', '')");

        return 0;
    }
}
