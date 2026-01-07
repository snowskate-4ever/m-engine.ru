<?php

namespace App\Services;

use App\Models\Type;

class MenuService
{
    /**
     * Get menu items based on configuration
     */
    public static function getMenuItems(): array
    {
        $source = config('menu.source', 'database');
        
        if ($source === 'config') {
            return config('menu.items', []);
        }
        
        // Load from database
        return self::loadFromDatabase();
    }
    
    /**
     * Load menu items from database
     */
    protected static function loadFromDatabase(): array
    {
        $config = config('menu.database', []);
        $model = $config['model'] ?? Type::class;
        $where = $config['where'] ?? [];
        $orderBy = $config['order_by'] ?? 'name';
        $orderDirection = $config['order_direction'] ?? 'asc';
        $nameField = $config['name_field'] ?? 'name';
        $translationPrefix = $config['translation_prefix'] ?? '';
        $routeName = $config['route_name'] ?? 'resources.by_type';
        $routeParam = $config['route_param'] ?? 'id';
        $guestRoute = $config['guest_route'] ?? 'resources.stub';
        
        $query = $model::query();
        
        foreach ($where as $field => $value) {
            $query->where($field, $value);
        }
        
        $items = $query->orderBy($orderBy, $orderDirection)->get();
        
        return $items->map(function ($item) use (
            $nameField,
            $translationPrefix,
            $routeName,
            $routeParam,
            $guestRoute
        ) {
            $id = $item->id ?? $item->{$routeParam};
            $name = $item->{$nameField} ?? '';
            
            return [
                'id' => $id,
                'name' => $name,
                'translation_key' => $translationPrefix . $name,
                'href' => $routeName,
                'href_params' => [$routeParam => $id],
                'auth_required' => true,
                'guest_href' => $guestRoute,
                'guest_href_params' => [$routeParam => $id],
                'children' => [], // Can be extended for sublevels
            ];
        })->toArray();
    }
    
    /**
     * Build URL for menu item
     */
    public static function buildUrl(array $item, bool $isAuthenticated = false): string
    {
        if (!$isAuthenticated && isset($item['guest_href'])) {
            $href = $item['guest_href'];
            $params = $item['guest_href_params'] ?? [];
        } else {
            $href = $item['href'] ?? '#';
            $params = $item['href_params'] ?? [];
        }
        
        // Check if it's a route name
        if (strpos($href, '.') !== false || strpos($href, '/') === false) {
            try {
                return route($href, $params);
            } catch (\Exception $e) {
                // If route doesn't exist, try as URL
                return url($href, $params);
            }
        }
        
        // It's a direct URL
        return url($href, $params);
    }
    
    /**
     * Get translated name for menu item
     */
    public static function getTranslatedName(array $item): string
    {
        if (isset($item['translation_key']) && $item['translation_key']) {
            $translated = __($item['translation_key']);
            if ($translated !== $item['translation_key']) {
                return $translated;
            }
        }
        
        return $item['name'] ?? '';
    }
}

