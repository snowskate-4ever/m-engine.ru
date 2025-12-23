<?php

namespace App\Classes;
use App\Models\User;

class StatClass
{
    /**
     * Create a new class instance.
     */
    public function __construct()
    {
        //
    }

    /**
     * Create a new class instance.
     */
    public static function get_stats(User $user, $type)
    {
        $className = 'App\\Models\\'.$type;
        $my_items = 0;
        
        $count_all = $className::count();
        $data = [
            'count_all' => $count_all,
            'my_items' => $my_items,
        ];
        return $data;
    }
}
