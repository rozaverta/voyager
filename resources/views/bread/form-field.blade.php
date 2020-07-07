<!-- GET THE DISPLAY OPTIONS -->
@php

/**
 * Define vars for ide
 *
 * @var \TCG\Voyager\Models\DataRow $row
 * @var \TCG\Voyager\Models\DataType $dataType
 * @var \Illuminate\Database\Eloquent\Model $dataTypeContent
 * @var \Illuminate\Support\ViewErrorBag $errors
 * @var bool $add
 * @var bool $edit
 * @var bool $hidden
 */

if( !isset($hidden)) {
	$hidden = false;
}

// override data type from row
if( isset($row->dataTypeContent) ) {
	$dataTypeContent = $row->dataTypeContent;
	$add = is_null($dataTypeContent->getKey());
	$edit = !$add;
}

$display_options = $row->details->display ?? NULL;
if ($dataTypeContent->{$row->field.'_'.($edit ? 'edit' : 'add')}) {
    $dataTypeContent->{$row->field} = $dataTypeContent->{$row->field.'_'.($edit ? 'edit' : 'add')};
}

// class attribute
$attributes = 'class="form-group';
if (!$hidden) {
    $attributes .= " col-md-" . ($display_options->width ?? 12);
}
if($errors->has($row->field)) {
    $attributes .= " has-error";
}
if($row->type === "hidden") {
	$attributes .= " hidden";
}
$attributes .= '"';

// id attribute
if( isset($display_options->id) ) {
	$attributes .= " id={$display_options->id}";
}

@endphp

@if (!$hidden && isset($row->details->legend) && isset($row->details->legend->text))
    <legend class="text-{{ $row->details->legend->align ?? 'center' }}" style="background-color: {{ $row->details->legend->bgcolor ?? '#f0f0f0' }}; padding: 5px;">{{ $row->details->legend->text }}</legend>
@endif

<div {!! $attributes !!}>
    {{ $row->slugify }}
    @if (!$hidden)
        <label class="control-label" for="name">{{ $row->getTranslatedAttribute('display_name') }}</label>
    @endif

    @include('voyager::multilingual.input-hidden-bread-edit-add')
    @if (isset($row->details->view))
        @include($row->details->view, ['row' => $row, 'dataType' => $dataType, 'dataTypeContent' => $dataTypeContent, 'content' => $dataTypeContent->{$row->field}, 'action' => ($edit ? 'edit' : 'add'), 'view' => ($edit ? 'edit' : 'add'), 'options' => $row->details])
    @elseif ($row->type == 'relationship')
        @include('voyager::formfields.relationship', ['options' => $row->details])
    @else
        {!! app('voyager')->formField($row, $dataType, $dataTypeContent) !!}
    @endif

    @foreach (app('voyager')->afterFormFields($row, $dataType, $dataTypeContent) as $after)
        {!! $after->handle($row, $dataType, $dataTypeContent) !!}
    @endforeach

    @if ($errors->has($row->field))
        @foreach ($errors->get($row->field) as $error)
            <span class="help-block">{{ $error }}</span>
        @endforeach
    @endif
</div>