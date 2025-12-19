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
        $className = 'App\\Models\\';
        switch ($type) {
            case 'events':
                $className = $className.'Event';
                $my_items = 0;
                break;
            case 'resources':
                $className = $className.'Resource';
                $my_items = 0;
                break;
            case 'profiles':
                $className = $className.'UserProfile';
                $my_items = count($className::with('user')->where('user_id', '=', $user->id)->get());
                break;
        }
        
        $count_all = $className::count();
        $data = [
            'count_all' => $count_all,
            'my_items' => $my_items,
        ];
        return $data;
    }
}
