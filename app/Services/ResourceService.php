<?php

namespace App\Services;

use App\Models\Resource;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class ResourceService
{
    public array $buttons = [];

    function __construct() 
    {
        $this->buttons = [
            'options' => [
                'add' => 'resources/create',
            ]
        ];
    }

    public function get_resources(Request $request)
    {
        return view('components.layouts.sec_level_layout', [
            'data' => [
                'title' => __('ui.resources'),
                'seo_title' => '',
                'seo_description' => '',
                'seo_keywords' => '',
                'component' => 'resource.resource-list',
                'buttons' => $this->buttons,
            ]
        ]);
    }

    public function get_resources_by_type(int $type_id, Request $request)
    {
        $type = \App\Models\Type::find($type_id);
        $typeName = $type ? (__('moonshine.types.values.' . $type->name) ?: $type->name) : '';
        $title = $typeName ? __('ui.resources') . ' - ' . $typeName : __('ui.resources');
        
        return view('components.layouts.sec_level_layout', [
            'data' => [
                'title' => $title,
                'seo_title' => '',
                'seo_description' => '',
                'seo_keywords' => '',
                'component' => 'resource.resource-list',
                'buttons' => $this->buttons,
                'type_id' => $type_id,
            ]
        ]);
    }

    public function create_resources(Request $request)
    {
        return view('components.layouts.sec_level_layout', [
            'data' => [
                'title' => __('ui.resource_create'),
                'seo_title' => '',
                'seo_description' => '',
                'seo_keywords' => '',
                'component' => 'resource.resource-create',
                'buttons' => $this->buttons,
            ]
        ]);
    }

    public function get_resource(int $id)
    {
        dd('get_resource');
        return ApiService::successResponse('Событие получено', self::formatEvent($event));
    }

    public function edit_resource(int $id, Request $request)
    {
        dd('edit_resource');
        return ApiService::successResponse('Событие обновлено', self::formatEvent($event));
    }

    public function delete_resource(int $id)
    {
        dd('delete_resource');
        return ApiService::successResponse('Событие удалено');
    }

    protected function formatEvent(Resource $resource): array
    {
        return [
            'id' => $resource->id,
            'name' => $resource->name,
            'description' => $resource->description,
            'active' => $resource->active,
            'type_id' => $resource->type_id,
            'type_name' => $resource->type->name,
            'start_at' => Carbon::parse($resource->start_at)->format('H:i d-m-Y'),
            'end_at' => Carbon::parse($resource->end_at)->format('H:i d-m-Y'),
            'created_at' => Carbon::parse($resource->created_at)->format('H:i d-m-Y'),
            'updated_at' => Carbon::parse($resource->updated_at)->format('H:i d-m-Y'),
        ];
    }
}

