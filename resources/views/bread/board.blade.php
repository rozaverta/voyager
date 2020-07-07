<!-- GET THE DISPLAY OPTIONS -->
@php

/**
 * Define vars for ide
 *
 * @var \TCG\Voyager\Views\Board[] $boards
 * @var \TCG\Voyager\Models\DataType $dataType
 * @var \Illuminate\Database\Eloquent\Model $dataTypeContent
 * @var \Illuminate\Support\ViewErrorBag $errors
 * @var bool $add
 * @var bool $edit
 * @var bool $actionBar
 * @var string $size
 */

if(!isset($size)) {
	$size = 'full';
}

if($size == 'body') {
	$class = 'col-md-8 col-lg-9';
}
else if($size == 'aside') {
	$class = 'col-md-4 col-lg-3';
}
else {
	$class = 'col-md-12';
}

if(!isset($actionBar)) {
	$actionBar = false;
}

@endphp

<div class="{{ $class }}">
    @foreach($boards as $board)
        <div class="panel panel-bordered panel-primary panel-form-groups">
            @if($board->hasLabel())
                <div class="panel-heading">
                    @if($board->hasIcon())
                        <h3 class="panel-title panel-icon"><i class="{{ $board->getIcon() }}"></i> {{ $board->getLabel() }}</h3>
                    @else
                        <h3 class="panel-title">{{ $board->getLabel() }}</h3>
                    @endif
                    @if($board->isCollapse())
                        <div class="panel-actions">
                            <a class="panel-action voyager-angle-up" data-toggle="panel-collapse" aria-hidden="true"></a>
                        </div>
                    @endif
                </div>
            @endif
            <div class="panel-body">
                @foreach($board->getItems() as $row)
                    @include("voyager::bread.form-field", compact('row', 'dataType', 'dataTypeContent', 'errors', 'edit', 'add'))
                @endforeach
            </div>
        </div>
    @endforeach
    @if($actionBar)
        <div class="panel panel-bordered panel-action-bar">
            <div class="panel-footer">
                @section('submit-buttons')
                    <button type="submit" class="btn btn-primary save">{{ __('voyager::generic.save') }}</button>
                @stop
                @yield('submit-buttons')
            </div>
        </div>
    @endif
</div>